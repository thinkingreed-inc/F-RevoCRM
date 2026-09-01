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
use mysqli;
use mysqli_result;
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
 * C. 振り分け（dispatch）— #1823
 *
 * 対象: FR_CronDispatcher::dispatch() / spawn() / isPhpBinaryExecutable()
 *
 *   C1 実行時刻を迎えたタスクは子プロセスへ振り分けられ、ログが残る（正常系・副作用）
 *   C2 実行時刻前のタスクは振り分けない
 *   C3 同時実行数の上限に達していたら振り分けない（境界）
 *   C4 直列指定したタスクは同時に 1 つしか振り分けない
 *   C5 他のディスパッチャがロックを保持していたら何も振り分けない
 *   C6 子プロセスの起動に失敗したらタスクを解放し failed として報告する（異常系）
 *   C7 実行中（自ホスト・生存）のタスクは振り分けない
 *   C8 実行中（他ホスト）のタスクは振り分けない
 */
final class DispatchTest extends TestCase
{
    use CronTestSupport;

    private string $taskA = '';
    private string $taskB = '';

    /** @var array<string, int|string> 実行時刻を迎えた状態 */
    private array $due = [];

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();

        $this->taskA = $this->makeTask('DispA', $this->stubHandler('noop1.service'));
        $this->taskB = $this->makeTask('DispB', $this->stubHandler('noop2.service'));

        $this->due = [
            'status'         => Vtiger_Cron::$STATUS_ENABLED,
            'next_run_at'    => 1,
            'laststart'      => 0,
            'lastend'        => 0,
            'owner_host'     => '',
            'owner_pid'      => 0,
            'last_heartbeat' => 0,
        ];
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['cron_max_parallel'],
            $GLOBALS['cron_serial_tasks'],
            $GLOBALS['cron_php_binary']
        );
        $this->cleanUpCron();
    }

    /** 空きスロットを確保する */
    private function allowParallel(int $extraSlots = 4): void
    {
        $GLOBALS['cron_max_parallel'] = FR_CronDispatcher::countRunning() + $extraSlots;
    }

    public function test_C1_実行時刻を迎えたタスクが子プロセスへ振り分けられる(): void
    {
        $this->setCols($this->taskA, $this->due);
        $this->setCols($this->taskB, array_merge($this->due, ['next_run_at' => $this->dbNow() + 86400]));

        $logFile = FR_CronDispatcher::getLogFile($this->reload($this->taskA));
        @unlink($logFile);

        // 子プロセスもテスト用DB へ接続させる
        $GLOBALS['cron_php_binary'] = dirname(__DIR__, 2) . '/Support/cron/php_test_db.sh';
        putenv('FREVOCRM_TEST_PHP=' . PHP_BINARY);

        $this->allowParallel();
        $dispatcher = new FR_CronDispatcher();
        $summary = $dispatcher->dispatch([$this->reload($this->taskA), $this->reload($this->taskB)]);

        self::assertSame([$this->taskA], $summary['dispatched'], 'C1 実行時刻を迎えたタスクが振り分けられる');
        self::assertSame(
            'not due',
            $summary['skipped'][$this->taskB] ?? null,
            'C2 実行時刻前のタスクは not due で除外される'
        );

        // 子プロセスの完了を待つ（副作用の確認）
        for ($i = 0; $i < 100; $i++) {
            if ($this->getColString($this->taskA, 'status') === (string) Vtiger_Cron::$STATUS_ENABLED
                && $this->getColInt($this->taskA, 'lastend') > 0) {
                break;
            }
            usleep(100000);
        }

        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($this->taskA, 'status'),
            'C1 子プロセスが実行を完了して解放される'
        );
        self::assertFileExists($logFile, 'C1 子プロセスの出力が logs/cron へ保存される');
        self::assertStringContainsString(
            'noop1 handler executed',
            (string) file_get_contents($logFile),
            'C1 ログにハンドラの出力が残る'
        );
    }

    public function test_C3_空きスロットが無ければ振り分けない(): void
    {
        $this->setCols($this->taskA, $this->due);
        $this->setCols($this->taskB, $this->due);

        $GLOBALS['cron_max_parallel'] = FR_CronDispatcher::countRunning();
        $dispatcher = new FR_CronDispatcher();
        $summary = $dispatcher->dispatch([$this->reload($this->taskA), $this->reload($this->taskB)]);

        self::assertSame([], $summary['dispatched'], 'C3 空きスロットが無ければ振り分けない');
        self::assertSame(
            ['no free slot', 'no free slot'],
            [$summary['skipped'][$this->taskA] ?? null, $summary['skipped'][$this->taskB] ?? null],
            'C3 no free slot として報告される'
        );
    }

    public function test_C4_直列指定タスクが実行中なら他の直列タスクを振り分けない(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskA, array_merge($this->due, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => FR_CronDispatcher::getHostName(),
            'owner_pid'      => 0,
            'laststart'      => $now,
            'last_heartbeat' => $now,
            'retry_timeout'  => 3600,
        ]));
        $this->setCols($this->taskB, $this->due);

        $this->allowParallel();
        $GLOBALS['cron_serial_tasks'] = [$this->taskA, $this->taskB];
        $dispatcher = new FR_CronDispatcher();
        $summary = $dispatcher->dispatch([$this->reload($this->taskA), $this->reload($this->taskB)]);

        self::assertSame(
            'serial task already running',
            $summary['skipped'][$this->taskB] ?? null,
            'C4 直列指定タスクが実行中なら他の直列タスクを振り分けない'
        );
        self::assertSame(
            'already running',
            $summary['skipped'][$this->taskA] ?? null,
            'C7 実行中（自ホスト）のタスクは already running で除外される'
        );
    }

    public function test_C8_実行中_他ホスト_のタスクは振り分けない(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskA, array_merge($this->due, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => self::OTHER_HOST,
            'owner_pid'      => 12345,
            'last_heartbeat' => $now,
            'laststart'      => $now,
            'retry_timeout'  => 3600,
        ]));

        $this->allowParallel();
        $dispatcher = new FR_CronDispatcher();
        $summary = $dispatcher->dispatch([$this->reload($this->taskA)]);

        self::assertSame(
            'running on ' . self::OTHER_HOST,
            $summary['skipped'][$this->taskA] ?? null,
            'C8 実行中（他ホスト）のタスクは振り分けない'
        );
    }

    public function test_C5_ロックを取得できなければ何も振り分けない(): void
    {
        /** @var array<string, string> $dbconfig */
        $dbconfig = $GLOBALS['dbconfig'];
        $other = @new mysqli(
            $dbconfig['db_hostname'],
            $dbconfig['db_username'],
            $dbconfig['db_password'],
            $dbconfig['db_name']
        );
        if ($other->connect_error) {
            self::markTestSkipped('DB へ別接続を張れなかった');
        }

        $lockName = FR_CronDispatcher::getLockName();
        $lock = $other->query("SELECT GET_LOCK('" . $other->real_escape_string($lockName) . "', 0) AS acquired");
        $lockRow = ($lock instanceof mysqli_result) ? $lock->fetch_assoc() : null;
        $acquired = is_array($lockRow) ? (int) $lockRow['acquired'] : 0;
        if ($acquired !== 1) {
            $other->close();
            self::markTestSkipped('別接続でロックを取得できなかった');
        }

        try {
            $this->setCols($this->taskA, $this->due);
            $this->allowParallel();
            $dispatcher = new FR_CronDispatcher();
            $summary = $dispatcher->dispatch([$this->reload($this->taskA)]);

            self::assertTrue($summary['locked'], 'C5 ロックを取得できなければ locked を返す');
            self::assertSame([], $summary['dispatched'], 'C5 ロック中は何も振り分けない');
            self::assertSame(
                (string) Vtiger_Cron::$STATUS_ENABLED,
                $this->getColString($this->taskA, 'status'),
                'C5 ロック中はタスクを実行中にしない'
            );
        } finally {
            $other->query("SELECT RELEASE_LOCK('" . $other->real_escape_string($lockName) . "')");
            $other->close();
        }
    }

    /**
     * spawn() は起動コマンドをバックグラウンド実行するため、exec() の終了コードでは
     * 失敗を検出できない（シェル自身の終了コードで常に 0 になる）。実行前に
     * PHP バイナリを確認することで、静かに失敗せず failed として報告する。
     */
    public function test_C6_子プロセスの起動に失敗したら解放して報告する(): void
    {
        $this->setCols($this->taskA, $this->due);
        $this->allowParallel();

        $GLOBALS['cron_php_binary'] = '/nonexistent/path/to/php';
        self::assertFalse(FR_CronDispatcher::isPhpBinaryExecutable(), 'C6 実行できない PHP バイナリを検出する');

        $dispatcher = new FR_CronDispatcher();
        $summary = $dispatcher->dispatch([$this->reload($this->taskA)]);

        self::assertSame([$this->taskA], $summary['failed'], 'C6 起動できなかったタスクは failed として報告される');
        self::assertSame([], $summary['dispatched'], 'C6 起動できなかったタスクは dispatched に入らない');
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($this->taskA, 'status'),
            'C6 起動できなかったタスクは解放される'
        );
    }

    public function test_C6_PHP_バイナリの実行可否を判定できる(): void
    {
        $GLOBALS['cron_php_binary'] = 'fr-test-no-such-command';
        self::assertFalse(FR_CronDispatcher::isPhpBinaryExecutable(), 'C6 PATH に無いコマンド名も検出する');

        $GLOBALS['cron_php_binary'] = PHP_BINARY;
        self::assertTrue(FR_CronDispatcher::isPhpBinaryExecutable(), 'C6 実行できる PHP バイナリは通す');
    }
}
