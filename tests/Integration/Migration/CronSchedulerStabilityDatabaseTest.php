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

namespace Tests\Integration\Migration;

use Migration20260825112920_SetupCronSchedulerStability as CronMigration;
use PearDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vtiger_Cron;

require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

/**
 * setup_cron_scheduler_stability マイグレーション（データベース側）— #1823
 *
 * 対象: setup/migration/scripts/20260825112920_setup_cron_scheduler_stability.php の
 *       applyDatabaseChanges()
 *
 *   13 データベースの列を追加し、2 回実行しても壊れない（冪等性）
 *   14 既に値が入っているタイムアウト・次回実行予定を上書きしない
 *   15 実行タイミングの種別が空のタスクを周期実行に揃える
 *
 * 検証用のタスク（FRTestMigration_ で始まる名前）だけを操作し、既存タスクには触らない。
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CronSchedulerStabilityDatabaseTest extends TestCase
{
    private const TASK_NAME = 'FRTestMigration_Cron';

    /** マイグレーションが揃える列 */
    private const EXPECTED_COLUMNS = [
        'retry_timeout', 'next_run_at', 'owner_host', 'owner_pid', 'last_heartbeat',
        'schedule_type', 'run_at_minutes', 'run_on_weekdays', 'run_on_day', 'log_retention_count',
    ];

    private ?PearDatabase $db = null;

    private function db(): PearDatabase
    {
        $db = $this->db;
        if (!$db instanceof PearDatabase) {
            $db = PearDatabase::getInstance();
            $this->db = $db;
        }

        return $db;
    }

    protected function setUp(): void
    {
        // 分離した子プロセス側で読み込む。setUpBeforeClass は親プロセスで動くため
        // ここで読まないと、スタブを持つ親で二重宣言になる。
        $root = dirname(__DIR__, 3);
        require_once $root . '/include/utils/CommonUtils.php';
        require_once $root . '/vtlib/Vtiger/Cron.php';
        require_once $root . '/setup/migration/scripts/20260825112920_setup_cron_scheduler_stability.php';

        try {
            $result = $this->db()->pquery('SELECT 1 AS ok FROM vtiger_cron_task LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_cron_task がないため実行しない');
        }

        Vtiger_Cron::deregister(self::TASK_NAME);
        Vtiger_Cron::register(
            self::TASK_NAME,
            'tests/Support/cron/noop1.service',
            900,
            'Home',
            1,
            0,
            'migration test'
        );
    }

    protected function tearDown(): void
    {
        Vtiger_Cron::deregister(self::TASK_NAME);
    }

    /** マイグレーションのデータベース側を実行する（ログは出力に混ぜない） */
    private function applyDatabaseChanges(): string
    {
        $migration = new CronMigration();
        ob_start();
        try {
            $migration->applyDatabaseChanges();
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    /** @return array<string, mixed> 検証用タスクの行 */
    private function fetchTask(): array
    {
        $result = $this->db()->pquery('SELECT * FROM vtiger_cron_task WHERE name = ?', [self::TASK_NAME]);
        $row = $this->db()->query_result_rowdata($result, 0);

        return is_array($row) ? $row : [];
    }

    /** 検証用タスクの列を文字列で取り出す */
    private function taskColumn(string $column): string
    {
        $value = $this->fetchTask()[$column] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }

    /** @return array<int, array{0: string}> */
    public static function expectedColumns(): array
    {
        return array_map(static fn (string $column): array => [$column], self::EXPECTED_COLUMNS);
    }

    #[DataProvider('expectedColumns')]
    public function test_13_必要な列が存在する(string $column): void
    {
        $this->applyDatabaseChanges();

        $result = $this->db()->pquery('SHOW COLUMNS FROM vtiger_cron_task LIKE ?', [$column]);

        self::assertNotFalse($result, $column . ' 列を確認できる');
        self::assertGreaterThan(0, $this->db()->num_rows($result), $column . ' 列が存在する');
    }

    public function test_14_15_既に入っている値を上書きせず空の種別だけ揃える(): void
    {
        // 運用者が値を変えている状況を作る
        $this->db()->pquery(
            "UPDATE vtiger_cron_task SET retry_timeout = 111, next_run_at = 222,
                schedule_type = '' WHERE name = ?",
            [self::TASK_NAME]
        );

        $this->applyDatabaseChanges();

        self::assertSame('111', $this->taskColumn('retry_timeout'), '14 既に入っているタイムアウトを上書きしない');
        self::assertSame('222', $this->taskColumn('next_run_at'), '14 既に入っている次回実行予定を上書きしない');
        self::assertSame('interval', $this->taskColumn('schedule_type'), '15 空の実行タイミングを周期実行に揃える');
    }

    public function test_13_未設定の次回実行予定が埋まる(): void
    {
        $this->db()->pquery(
            'UPDATE vtiger_cron_task SET retry_timeout = 0, next_run_at = 0 WHERE name = ?',
            [self::TASK_NAME]
        );

        $this->applyDatabaseChanges();

        self::assertGreaterThan(0, (int) $this->taskColumn('next_run_at'), '13 未設定の次回実行予定が埋まる');
    }

    public function test_13_2回実行しても壊れない(): void
    {
        $this->applyDatabaseChanges();
        $output = $this->applyDatabaseChanges();

        self::assertStringContainsString('列は既に揃っています', $output, '13 2 回実行しても列は揃ったままと報告される');
    }
}
