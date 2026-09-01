<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Integration\Cron;

use FR_CronDispatcher;
use PHPUnit\Framework\TestCase;
use Tests\Support\CronTestSupport;
use Vtiger_Cron;

require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

$cronTestRoot = dirname(__DIR__, 3);

require_once $cronTestRoot . '/include/database/PearDatabase.php';
require_once $cronTestRoot . '/include/utils/CommonUtils.php';
require_once $cronTestRoot . '/vtlib/Vtiger/Cron.php';
require_once $cronTestRoot . '/include/utils/CronDispatcher.php';

/**
 * B. 実行中のまま終わらないタスクの後始末（reap）— #1823
 *
 * 対象: FR_CronDispatcher::reap() / isTaskProcessRunning()
 *
 *   B1  自ホスト担当でプロセスが存在しない → 解放する（異常系）
 *   B2  自ホスト担当でプロセス生存・タイムアウト内 → ハートビートを更新し解放しない
 *   B3  自ホスト担当でプロセス生存・タイムアウト超過 → 強制終了して解放する
 *   B4  強制終了を無効にした場合 → 警告だけで解放しない
 *   B5  他サーバー担当でハートビート生存 → 触らない（remote）
 *   B6  他サーバー担当でハートビート途絶 → 触らないが引き継ぎ対象として報告（stale）
 *   B7  実行権獲得直後（PID 未記録・猶予内） → 何もしない（境界）
 *   B8  PID が別のプロセスに再利用されていても、そのプロセスを終了させない（異常系）
 *   B9  実行中でないタスクは対象外
 *   B10 猶予を過ぎても PID が記録されない → 起動できていないので解放する（異常系）
 */
final class ReapTest extends TestCase
{
    use CronTestSupport;

    private string $taskName = '';
    private string $host = '';

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();
        $this->host = FR_CronDispatcher::getHostName();
        $this->taskName = $this->makeTask('ReapB', $this->fixtureHandler('noop1.service'));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['cron_kill_timed_out']);
        $this->cleanUpCron();
    }

    public function test_B1_プロセス不在のタスクは解放される(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => 4194303, // 存在しない PID
            'laststart'      => $now - 10,
            'lastend'        => 0,
            'last_heartbeat' => $now - 5,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertArrayHasKey($this->taskName, $findings['dead'], 'B1 プロセス不在のタスクは dead として報告される');
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($this->taskName, 'status'),
            'B1 dead のタスクは解放される（retry_timeout を待たない）'
        );
    }

    public function test_B2_正常に実行中ならハートビートを更新し解放しない(): void
    {
        $pid = $this->startFakeTaskProcess($this->taskName);
        if ($pid <= 0) {
            self::markTestSkipped('擬似プロセスを起動できなかった');
        }

        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => $pid,
            'retry_timeout'  => 3600,
            'laststart'      => $now - 30,
            'lastend'        => 0,
            'last_heartbeat' => $now - 30,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertSame(
            [],
            array_merge($findings['dead'], $findings['hung']),
            'B2 正常に実行中なら dead/hung として報告されない'
        );
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($this->taskName, 'status'),
            'B2 実行中のまま維持される'
        );
        self::assertGreaterThan(
            $now - 30,
            $this->getColInt($this->taskName, 'last_heartbeat'),
            'B2 ハートビートが更新される'
        );
    }

    /**
     * B4 を B3 より先に確かめる。B3 でプロセスを終了させてしまうため。
     */
    public function test_B4_強制終了を無効にすると警告だけで解放しない(): void
    {
        $pid = $this->startFakeTaskProcess($this->taskName);
        if ($pid <= 0) {
            self::markTestSkipped('擬似プロセスを起動できなかった');
        }

        $now = $this->dbNow();
        $GLOBALS['cron_kill_timed_out'] = false;
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => $pid,
            'retry_timeout'  => 10,
            'laststart'      => $now - 600,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertArrayHasKey($this->taskName, $findings['hung'], 'B4 タイムアウト超過は hung として報告される');
        self::assertSame([], $findings['killed'], 'B4 強制終了を無効にすると killed に入らない');
        self::assertTrue($this->isPidAlive($pid), 'B4 プロセスは終了させられない');
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($this->taskName, 'status'),
            'B4 タスクは解放されない'
        );
    }

    public function test_B3_タイムアウト超過のプロセスは強制終了して解放する(): void
    {
        $pid = $this->startFakeTaskProcess($this->taskName);
        if ($pid <= 0) {
            self::markTestSkipped('擬似プロセスを起動できなかった');
        }

        $now = $this->dbNow();
        $GLOBALS['cron_kill_timed_out'] = true;
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => $pid,
            'retry_timeout'  => 10,
            'laststart'      => $now - 600,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertArrayHasKey(
            $this->taskName,
            $findings['killed'],
            'B3 タイムアウト超過のプロセスは killed として報告される'
        );

        $this->waitForPidToExit($pid);
        self::assertFalse($this->isPidAlive($pid), 'B3 プロセスが実際に終了している');
        self::assertFalse(
            FR_CronDispatcher::isTaskProcessRunning($pid, $this->taskName),
            'B3 終了後は担当プロセスとして認識されない'
        );
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($this->taskName, 'status'),
            'B3 タスクが解放される'
        );
    }

    public function test_B8_再利用された_PID_は無関係なプロセスを終了させない(): void
    {
        $unrelatedPid = $this->startUnrelatedProcess();
        if ($unrelatedPid <= 0) {
            self::markTestSkipped('無関係なプロセスを起動できなかった');
        }

        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => $unrelatedPid,
            'retry_timeout'  => 10,
            'laststart'      => $now - 600,
            'lastend'        => 0,
            'last_heartbeat' => $now - 600,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertArrayHasKey(
            $this->taskName,
            $findings['dead'],
            'B8 再利用された PID は dead として扱う（hung にしない）'
        );
        self::assertTrue($this->isPidAlive($unrelatedPid), 'B8 無関係なプロセスを終了させない');
    }

    public function test_B5_他サーバーで稼働中なら_remote_として報告し触らない(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'owner_pid'      => 12345,
            'retry_timeout'  => 3600,
            'laststart'      => $now - 30,
            'lastend'        => 0,
            'last_heartbeat' => $now - 5,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertSame(
            self::OTHER_HOST,
            $findings['remote'][$this->taskName] ?? null,
            'B5 他サーバーで稼働中なら remote として報告される'
        );
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($this->taskName, 'status'),
            'B5 他サーバーのタスクには手を出さない'
        );
    }

    public function test_B6_他サーバーのハートビート途絶は_stale_として報告する(): void
    {
        $now = $this->dbNow();
        $heartbeatTimeout = FR_CronDispatcher::getHeartbeatTimeout();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'owner_pid'      => 12345,
            'retry_timeout'  => 3600,
            'laststart'      => $now - 30,
            'lastend'        => 0,
            'last_heartbeat' => $now - ($heartbeatTimeout + 10),
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertArrayHasKey(
            $this->taskName,
            $findings['stale'],
            'B6 他サーバーのハートビート途絶は stale として報告される'
        );
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($this->taskName, 'status'),
            'B6 stale でもプロセスには手を出さない'
        );
    }

    public function test_B7_実行権獲得直後は猶予内なら何もしない(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => 0,
            'laststart'      => $now - 5,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertSame([], $findings['dead'], 'B7 PID 未記録でも猶予内なら dead 扱いしない');
        self::assertSame([], $findings['notstarted'], 'B7 PID 未記録でも猶予内なら notstarted にしない');
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($this->taskName, 'status'),
            'B7 実行中のまま維持される'
        );
    }

    public function test_B10_猶予超過で_PID_未記録なら起動失敗として解放する(): void
    {
        $now = $this->dbNow();
        $grace = FR_CronDispatcher::CHILD_STARTUP_GRACE;
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => 0,
            'laststart'      => $now - ($grace + 10),
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertArrayHasKey(
            $this->taskName,
            $findings['notstarted'],
            'B10 猶予超過で PID 未記録なら notstarted として報告される'
        );
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($this->taskName, 'status'),
            'B10 notstarted のタスクは解放される'
        );
    }

    public function test_B9_実行中でないタスクは対象外(): void
    {
        $this->setCols($this->taskName, ['status' => Vtiger_Cron::$STATUS_ENABLED]);

        $findings = FR_CronDispatcher::reap([$this->reload($this->taskName)]);

        self::assertSame(
            [],
            array_merge($findings['dead'], $findings['hung'], $findings['remote'], $findings['notstarted']),
            'B9 実行中でないタスクは何も報告されない'
        );
    }
}
