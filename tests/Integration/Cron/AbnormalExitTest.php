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
 * F. 子プロセスが異常終了した場合の実行状態 / G. 並列実行が使えない場合のフォールバック — #1823
 *
 * 対象: vtigercron.php（shutdown handler・逐次実行・単体実行）
 *
 *   F1 捕捉できない fatal error で停止しても実行状態が残らない（異常系）
 *   F2 処理途中の exit() で停止しても実行状態が残らない（異常系）
 *   F3 Error（PHP7 以降の Throwable）が投げられても実行状態が残らない
 *   F4 1 タスクの異常終了で後続タスクの実行が止まらない
 *   G1 $cron_max_parallel = 1 なら並列実行を使わない
 *   G2 単体実行（--service=）でタスクが同期実行され、完了が記録される
 */
final class AbnormalExitTest extends TestCase
{
    use CronTestSupport;

    /** @var array<string, int> 一時的に先送りした既存タスクの next_run_at（name => 元の値） */
    private array $frozenTasks = [];

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();
    }

    protected function tearDown(): void
    {
        $this->thawExistingTasks();
        unset($GLOBALS['cron_max_parallel']);
        $this->cleanUpCron();
    }

    /**
     * 既存タスクの next_run_at を先送りして、--serial の実行対象から外す。
     * テスト用タスクだけを逐次実行させるため。値は tearDown で必ず復元する。
     */
    private function freezeExistingTasks(): void
    {
        $db = $this->cronDb();
        $result = $db->pquery(
            'SELECT name, next_run_at FROM vtiger_cron_task WHERE name NOT LIKE ?',
            [self::PREFIX . '%']
        );
        while ($row = $db->fetch_array($result)) {
            $this->frozenTasks[$row['name']] = (int) $row['next_run_at'];
        }
        $future = $this->dbNow() + 86400;
        foreach (array_keys($this->frozenTasks) as $name) {
            $db->pquery('UPDATE vtiger_cron_task SET next_run_at = ? WHERE name = ?', [$future, $name]);
        }
    }

    private function thawExistingTasks(): void
    {
        $db = $this->cronDb();
        foreach ($this->frozenTasks as $name => $original) {
            $db->pquery('UPDATE vtiger_cron_task SET next_run_at = ? WHERE name = ?', [$original, $name]);
        }
        $this->frozenTasks = [];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function abnormalExitCases(): array
    {
        return [
            'F1 捕捉できない fatal error' => ['Fatal', 'fatal.service'],
            'F2 処理途中の exit()'       => ['Exiter', 'exiter.service'],
            'F3 Error（Throwable）'      => ['Thrower', 'thrower.service'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('abnormalExitCases')]
    public function test_F1_F3_異常終了しても実行状態が残らない(string $suffix, string $handler): void
    {
        $name = $this->makeTask($suffix, $this->stubHandler($handler));

        // 子プロセスモードは実行権を親が獲得済みである前提
        FR_CronDispatcher::claim($this->reload($name));
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_RUNNING,
            $this->getColString($name, 'status'),
            '前提として実行中になっている'
        );

        $this->runCli('--child --service=' . escapeshellarg($name));

        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($name, 'status'),
            '異常終了でも実行状態が残らない'
        );
        self::assertGreaterThan(0, $this->getColInt($name, 'lastend'), '異常終了でも lastend が記録される');
        self::assertSame('0', $this->getColString($name, 'owner_pid'), '異常終了でも owner_pid が消される');
    }

    public function test_F4_1タスクの異常終了で後続タスクが止まらない(): void
    {
        $this->freezeExistingTasks();

        $first = $this->makeTask('SerialFail', $this->stubHandler('thrower.service'));
        $second = $this->makeTask('SerialOk', $this->stubHandler('noop1.service'));
        $this->setCols($first, ['next_run_at' => 1, 'status' => Vtiger_Cron::$STATUS_ENABLED]);
        $this->setCols($second, ['next_run_at' => 1, 'status' => Vtiger_Cron::$STATUS_ENABLED]);

        $run = $this->runCli('--serial');

        self::assertStringContainsString(
            '[ERROR]: ' . $first,
            $run['output'],
            'F4 先行タスクの失敗が記録される'
        );
        self::assertStringContainsString(
            'noop1 handler executed',
            $run['output'],
            'F4 後続タスクが実行される'
        );
        self::assertGreaterThan(0, $this->getColInt($second, 'lastend'), 'F4 後続タスクの lastend が記録される');
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($second, 'status'),
            'F4 後続タスクが正常に完了する'
        );
    }

    public function test_G1_並列実行を使うかは_cron_max_parallel_で決まる(): void
    {
        $GLOBALS['cron_max_parallel'] = 1;
        self::assertFalse(FR_CronDispatcher::isSupported(), 'G1 $cron_max_parallel = 1 なら並列実行を使わない');

        $GLOBALS['cron_max_parallel'] = 4;
        self::assertTrue(FR_CronDispatcher::isSupported(), 'G1 $cron_max_parallel = 4 なら並列実行を使う');
    }

    public function test_G2_単体実行でタスクが同期実行され完了が記録される(): void
    {
        $name = $this->makeTask('Single', $this->stubHandler('noop1.service'));
        $this->setCols($name, [
            'next_run_at' => 1,
            'status'      => Vtiger_Cron::$STATUS_ENABLED,
            'lastend'     => 0,
        ]);

        $run = $this->runCli('--service=' . escapeshellarg($name));

        self::assertStringContainsString(
            'noop1 handler executed',
            $run['output'],
            'G2 単体実行でハンドラが実行される'
        );
        self::assertSame(
            (string) Vtiger_Cron::$STATUS_ENABLED,
            $this->getColString($name, 'status'),
            'G2 単体実行後にタスクが解放される'
        );
        self::assertGreaterThan(0, $this->getColInt($name, 'lastend'), 'G2 単体実行で lastend が記録される');
    }
}
