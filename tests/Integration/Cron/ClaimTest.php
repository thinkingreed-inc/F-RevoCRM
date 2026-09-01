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
 * A. 実行権の獲得（二重起動の防止）— #1823
 *
 * 対象: FR_CronDispatcher::claim()
 *
 * 実行権の獲得は「確認と更新を 1 クエリで行う」ことで、cron プロセスが重なっても
 * 同じタスクが二重に起動しないことを保証している。ここではその境界を確かめる。
 *
 *   A1 待機中のタスクは実行権を獲得できる（正常系）
 *   A2 同じタスクの実行権は 2 回目は獲得できない
 *   A3 無効化されたタスクの実行権は獲得できない（境界）
 *   A4 他サーバーが実行中でハートビートが生きていれば横取りしない
 *   A5 他サーバーのハートビートが途絶えていれば引き継げる
 *   A6 ハートビート未記録の旧データは retry_timeout 未超過なら引き継がない（境界）
 *   A7 ハートビート未記録の旧データは retry_timeout 超過なら引き継げる
 *   A8 複数プロセスが同時に獲得を試みても成功はちょうど 1 つ（並行性）
 */
final class ClaimTest extends TestCase
{
    use CronTestSupport;

    private string $taskName = '';

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->prepareCronCurrentUser();
        $this->cleanUpCronTasks();
        $this->taskName = $this->makeTask('ClaimA', $this->fixtureHandler('noop1.service'));
    }

    protected function tearDown(): void
    {
        $this->cleanUpCron();
    }

    public function test_A1_待機中のタスクは実行権を獲得できる(): void
    {
        $task = $this->reload($this->taskName);

        self::assertTrue(FR_CronDispatcher::claim($task), 'A1 待機中のタスクは実行権を獲得できる');
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($this->taskName, 'status'),
            'A1 status が実行中になる'
        );
        self::assertSame(
            FR_CronDispatcher::getHostName(),
            $this->getColString($this->taskName, 'owner_host'),
            'A1 owner_host に自ホストが記録される'
        );
        self::assertGreaterThan(
            0,
            $this->getColInt($this->taskName, 'last_heartbeat'),
            'A1 last_heartbeat が記録される'
        );
    }

    public function test_A2_実行中のタスクの実行権は獲得できない(): void
    {
        FR_CronDispatcher::claim($this->reload($this->taskName));

        self::assertFalse(
            FR_CronDispatcher::claim($this->reload($this->taskName)),
            'A2 実行中のタスクの実行権は獲得できない'
        );
    }

    public function test_A3_無効化されたタスクの実行権は獲得できない(): void
    {
        $this->setCols($this->taskName, ['status' => Vtiger_Cron::$STATUS_DISABLED]);

        self::assertFalse(
            FR_CronDispatcher::claim($this->reload($this->taskName)),
            'A3 無効化されたタスクの実行権は獲得できない'
        );
    }

    public function test_A4_他サーバーが稼働中のタスクは横取りしない(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'owner_pid'      => 999999,
            'laststart'      => $now - 10,
            'lastend'        => 0,
            'last_heartbeat' => $now - 5,
        ]);

        self::assertFalse(
            FR_CronDispatcher::claim($this->reload($this->taskName)),
            'A4 他サーバーが稼働中のタスクは横取りしない'
        );
    }

    public function test_A5_ハートビートが途絶えた他サーバーのタスクは引き継げる(): void
    {
        $now = $this->dbNow();
        $timeout = FR_CronDispatcher::getHeartbeatTimeout();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'owner_pid'      => 999999,
            'laststart'      => $now - 10,
            'lastend'        => 0,
            'last_heartbeat' => $now - ($timeout + 10),
        ]);

        self::assertTrue(
            FR_CronDispatcher::claim($this->reload($this->taskName)),
            'A5 ハートビートが途絶えた他サーバーのタスクは引き継げる'
        );
        self::assertSame(
            FR_CronDispatcher::getHostName(),
            $this->getColString($this->taskName, 'owner_host'),
            'A5 引き継いだ後 owner_host が自ホストになる'
        );
    }

    public function test_A6_旧データで_retry_timeout_未超過なら引き継がない(): void
    {
        $now = $this->dbNow();
        $retry = 60;
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'retry_timeout'  => $retry,
            'laststart'      => $now - ($retry - 30),
            'lastend'        => 0,
            'last_heartbeat' => 0,
        ]);

        self::assertFalse(
            FR_CronDispatcher::claim($this->reload($this->taskName)),
            'A6 旧データで retry_timeout 未超過なら引き継がない'
        );
    }

    public function test_A7_旧データで_retry_timeout_超過なら引き継げる(): void
    {
        $now = $this->dbNow();
        $retry = 60;
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'retry_timeout'  => $retry,
            'laststart'      => $now - ($retry + 10),
            'lastend'        => 0,
            'last_heartbeat' => 0,
        ]);

        self::assertTrue(
            FR_CronDispatcher::claim($this->reload($this->taskName)),
            'A7 旧データで retry_timeout 超過なら引き継げる'
        );
    }

    public function test_A8_同時に獲得を試みても成功はちょうど1つ(): void
    {
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_ENABLED,
            'owner_host'     => '',
            'owner_pid'      => 0,
            'last_heartbeat' => 0,
            'laststart'      => 0,
            'lastend'        => 0,
            'retry_timeout'  => 0,
        ]);

        $workers = 6;
        // 各プロセスの起動・DB 接続にかかる時間の差を吸収し、claim() の呼び出しを揃える
        $startAt = microtime(true) + 3.0;
        $handles = [];
        for ($i = 0; $i < $workers; $i++) {
            $command = sprintf(
                '%s -d xdebug.mode=off -f tests/fixtures/cron/claim_worker.php -- %s %s %s 2>/dev/null',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($this->taskName),
                escapeshellarg(sprintf('%.4f', $startAt)),
                escapeshellarg('fr-test-host-' . $i)
            );
            $pipes = [];
            $process = proc_open($command, [1 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process, 'A8 ワーカープロセスを起動できる');
            $handles[] = ['process' => $process, 'pipe' => $pipes[1]];
        }

        $successes = 0;
        $answers = [];
        foreach ($handles as $handle) {
            $answer = trim((string) stream_get_contents($handle['pipe']));
            fclose($handle['pipe']);
            proc_close($handle['process']);
            $answers[] = $answer === '' ? '-' : $answer;
            if ($answer === '1') {
                $successes++;
            }
        }

        self::assertSame(
            1,
            $successes,
            sprintf('A8 %d プロセスが同時に獲得を試みても成功は 1 つ（応答: %s）', $workers, implode(',', $answers))
        );
    }
}
