<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use Migration20261028100000_AddAutonumberUitype as AutonumberMigration;
use Tests\Support\DocumentsTestCase;

require_once dirname(__DIR__, 2) . '/Support/DocumentsTestCase.php';

/**
 * add_autonumber_uitype マイグレーション
 *
 * 対象: setup/migration/scripts/20261028100000_add_autonumber_uitype.php
 *
 *   - uitype 997 → string の対応・3 つのテーブル・AutoNumberHandler・定期ジョブを用意する
 *   - 2 回実行しても重複しない
 *   - 既にあるテーブルの中身・登録済みの定期ジョブの設定は書き換えない
 *
 * 基底クラスが本体を読み込むため、本体を起動する結合テストの共通基盤の上で動かす。
 */
final class AddAutonumberUitypeTest extends DocumentsTestCase
{
    private const TABLES = ['vtiger_autonumber_config', 'vtiger_autonumber_counter', 'vtiger_autonumber_job'];

    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261028100000_add_autonumber_uitype.php';
        if ($this->rowCount("SHOW TABLES LIKE 'vtiger_autonumber_config'") === 0) {
            self::markTestSkipped('テスト用DBに自動採番のマイグレーションが適用されていない');
        }
    }

    public function test_適用済みの環境で2回実行しても重複しない(): void
    {
        $this->runMigration();
        $this->runMigration();

        $this->assertSame(1, $this->rowCount("SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = '997'"));
        $this->assertSame(
            'string',
            $this->value("SELECT fieldtype AS v FROM vtiger_ws_fieldtype WHERE uitype = '997'")
        );
        $this->assertSame(1, $this->rowCount(
            "SELECT 1 FROM vtiger_eventhandlers WHERE handler_class = 'AutoNumberHandler' AND event_name = 'vtiger.entity.beforesave'"
        ));
        $this->assertSame(1, $this->rowCount("SELECT 1 FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'"));
        foreach (self::TABLES as $table) {
            $this->assertSame(1, $this->rowCount("SHOW TABLES LIKE '{$table}'"), $table);
        }
    }

    public function test_ハンドラは有効で定期ジョブは指定のファイルを指す(): void
    {
        $this->runMigration();

        $this->assertSame('modules/Vtiger/handlers/AutoNumberHandler.php', $this->value(
            "SELECT handler_path AS v FROM vtiger_eventhandlers WHERE handler_class = 'AutoNumberHandler' LIMIT 1"
        ));
        $this->assertSame('1', $this->value("SELECT is_active AS v FROM vtiger_eventhandlers WHERE handler_class = 'AutoNumberHandler' LIMIT 1"));
        $this->assertSame(
            AutonumberMigration::CRON_HANDLER,
            $this->value("SELECT handler_file AS v FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'")
        );
        $this->assertFileExists(dirname(__DIR__, 3) . '/' . AutonumberMigration::CRON_HANDLER);
        $this->assertFileExists(dirname(__DIR__, 3) . '/' . AutonumberMigration::HANDLER_PATH);
    }

    public function test_再実行しても設定の中身と登録済みの定期ジョブの設定は変わらない(): void
    {
        $this->db->pquery("UPDATE vtiger_cron_task SET frequency = 300, status = 0 WHERE name = 'AutoNumberBackfill'", []);
        $configBefore = $this->rowCount('SELECT 1 FROM vtiger_autonumber_config');
        $counterBefore = $this->rowCount('SELECT 1 FROM vtiger_autonumber_counter');
        try {
            $this->runMigration();

            $this->assertSame('300', $this->value("SELECT frequency AS v FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'"));
            $this->assertSame('0', $this->value("SELECT status AS v FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'"));
            $this->assertSame($configBefore, $this->rowCount('SELECT 1 FROM vtiger_autonumber_config'));
            $this->assertSame($counterBefore, $this->rowCount('SELECT 1 FROM vtiger_autonumber_counter'));
        } finally {
            $this->db->pquery(
                "UPDATE vtiger_cron_task SET frequency = ?, status = 1 WHERE name = 'AutoNumberBackfill'",
                [AutonumberMigration::CRON_FREQUENCY]
            );
        }
    }

    public function test_登録が消えていても適用し直せる(): void
    {
        $cron = $this->db->pquery("SELECT * FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'", []);
        $row = $this->db->fetchByAssoc($cron, -1, false);
        $this->assertIsArray($row);
        $this->db->pquery("DELETE FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'", []);
        $this->db->pquery("DELETE FROM vtiger_ws_fieldtype WHERE uitype = '997'", []);
        try {
            $this->runMigration();

            $this->assertSame(1, $this->rowCount("SELECT 1 FROM vtiger_cron_task WHERE name = 'AutoNumberBackfill'"));
            $this->assertSame(1, $this->rowCount("SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = '997' AND fieldtype = 'string'"));
        } finally {
            $this->runMigration();
        }
    }

    private function runMigration(): void
    {
        // 進行状況のログは標準出力に出るため、テストの出力にしない
        ob_start();
        try {
            (new AutonumberMigration())->process();
        } finally {
            ob_end_clean();
        }
    }

    private function rowCount(string $sql): int
    {
        $result = $this->db->pquery($sql, []);
        return $result === false ? 0 : (int) $this->db->num_rows($result);
    }

    private function value(string $sql): string
    {
        $result = $this->db->pquery($sql, []);
        if ($result === false || $this->db->num_rows($result) === 0) {
            return '';
        }
        return (string) $this->db->query_result($result, 0, 'v');
    }
}
