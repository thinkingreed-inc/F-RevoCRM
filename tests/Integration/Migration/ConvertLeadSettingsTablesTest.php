<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use PearDatabase;
use PHPUnit\Framework\TestCase;

/**
 * リード昇格画面の設定テーブルを作るマイグレーション
 *
 * 本物のテーブルは退避して、空の状態から 2 回実行する（冪等性の確認）。
 * process() だけを呼ぶため com_vtiger_migrations の記録には触れない。
 */
final class ConvertLeadSettingsTablesTest extends TestCase
{
    private const TABLES = ['vtiger_convertlead_layout', 'vtiger_convertlead_defaults', 'vtiger_convertlead_matchrules'];

    private PearDatabase $db;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 3);
        require_once $root . '/setup/migration/FRMigrationClass.php';
        if (class_exists('Vtiger_Loader', false)) {
            spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        }
        try {
            $this->db = PearDatabase::getInstance();
            $result = $this->db->pquery('SELECT DATABASE() AS db', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない');
        }
        foreach (self::TABLES as $table) {
            if ($this->tableExists($table . '_lcbak')) {
                $this->db->pquery("DROP TABLE IF EXISTS {$table}", []);
                $this->db->pquery("RENAME TABLE {$table}_lcbak TO {$table}", []);
            }
            if ($this->tableExists($table)) {
                $this->db->pquery("RENAME TABLE {$table} TO {$table}_lcbak", []);
            }
        }
        require_once $root . '/setup/migration/scripts/20261021000000_create_convertlead_settings_tables.php';
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            if ($this->tableExists($table . '_lcbak')) {
                $this->db->pquery("DROP TABLE IF EXISTS {$table}", []);
                $this->db->pquery("RENAME TABLE {$table}_lcbak TO {$table}", []);
            }
        }
    }

    public function test_空の状態から2回実行しても初期データが重複しない(): void
    {
        ob_start();
        try {
            (new \Migration20261021000000_CreateConvertleadSettingsTables())->process();
            (new \Migration20261021000000_CreateConvertleadSettingsTables())->process();
        } finally {
            ob_end_clean();
        }

        self::assertSame(
            ['accountname', 'industry'],
            $this->column("SELECT fieldname FROM vtiger_convertlead_layout WHERE module = 'Accounts' ORDER BY sequence")
        );
        self::assertSame(
            ['lastname', 'firstname', 'email'],
            $this->column("SELECT fieldname FROM vtiger_convertlead_layout WHERE module = 'Contacts' ORDER BY sequence")
        );
        self::assertSame(
            ['["accountname"]'],
            $this->column("SELECT fieldnames FROM vtiger_convertlead_matchrules WHERE module = 'Accounts'")
        );
        self::assertSame([], $this->column("SELECT fieldnames FROM vtiger_convertlead_matchrules WHERE module = 'Contacts'"));
        self::assertSame([], $this->column('SELECT fieldname FROM vtiger_convertlead_defaults'));
    }

    public function test_設定済みのモジュールには初期データを足さない(): void
    {
        ob_start();
        try {
            (new \Migration20261021000000_CreateConvertleadSettingsTables())->process();
            $this->db->pquery("DELETE FROM vtiger_convertlead_layout WHERE module = 'Accounts' AND fieldname = 'industry'", []);
            (new \Migration20261021000000_CreateConvertleadSettingsTables())->process();
        } finally {
            ob_end_clean();
        }

        self::assertSame(
            ['accountname'],
            $this->column("SELECT fieldname FROM vtiger_convertlead_layout WHERE module = 'Accounts' ORDER BY sequence")
        );
    }

    /**
     * @return list<string>
     */
    private function column(string $sql): array
    {
        $result = $this->db->pquery($sql, []);
        $values = [];
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $values[] = decode_html((string) $this->db->query_result($result, $i, 0));
        }

        return $values;
    }

    private function tableExists(string $table): bool
    {
        $result = $this->db->pquery('SHOW TABLES LIKE ?', [$table]);

        return $result !== false && $this->db->num_rows($result) > 0;
    }
}
