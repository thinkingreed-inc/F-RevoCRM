<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use Migration20261028090000_AddYearmonthUitype as YearmonthMigration;
use PearDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * add_yearmonth_uitype マイグレーション
 *
 * 対象: setup/migration/scripts/20261028090000_add_yearmonth_uitype.php
 *
 *   - uitype 996 → date の対応と、YearMonthHandler の登録を行う
 *   - 2 回実行しても重複しない（冪等性）
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AddYearmonthUitypeTest extends TestCase
{
    private PearDatabase $db;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 3);
        require_once $root . '/include/utils/CommonUtils.php';
        require_once $root . '/setup/migration/scripts/20261028090000_add_yearmonth_uitype.php';

        try {
            $this->db = PearDatabase::getInstance();
            $result = $this->db->pquery('SELECT 1 FROM vtiger_field LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_field がないため実行しない');
        }
    }

    public function test_未適用の環境に適用でき2回実行しても重複しない(): void
    {
        // 未適用の状態を作る（失敗しても後で戻せるよう、最後に必ず適用し直す）
        $this->db->pquery("DELETE FROM vtiger_ws_fieldtype WHERE uitype = '996'", []);
        $this->db->pquery("DELETE FROM vtiger_eventhandlers WHERE handler_class = 'YearMonthHandler'", []);

        try {
            $this->runMigration();
            $this->runMigration();
        } finally {
            // 途中で失敗しても他のテストが動く状態に戻す
            $this->runMigration();
        }

        $this->assertSame(
            1,
            $this->countRows("SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = '996' AND fieldtype = 'date'"),
            'uitype 996 は date に対応づけ、重複させない'
        );
        $this->assertSame(1, $this->countRows(
            "SELECT 1 FROM vtiger_eventhandlers WHERE handler_class = 'YearMonthHandler' AND event_name = 'vtiger.entity.beforesave'"
        ));

        $result = $this->db->pquery(
            "SELECT handler_path, is_active FROM vtiger_eventhandlers WHERE handler_class = 'YearMonthHandler' LIMIT 1",
            []
        );
        $this->assertSame('modules/Vtiger/handlers/YearMonthHandler.php', $this->db->query_result($result, 0, 'handler_path'));
        $this->assertSame(1, (int) $this->db->query_result($result, 0, 'is_active'));
    }

    public function test_登録済みの対応は書き換えない(): void
    {
        $this->db->pquery("DELETE FROM vtiger_ws_fieldtype WHERE uitype = '996'", []);
        $this->db->pquery("INSERT INTO vtiger_ws_fieldtype (uitype, fieldtype) VALUES ('996', 'date')", []);
        $before = $this->countRows('SELECT 1 FROM vtiger_ws_fieldtype');

        $this->runMigration();

        $this->assertSame($before, $this->countRows('SELECT 1 FROM vtiger_ws_fieldtype'));
    }

    private function runMigration(): void
    {
        // 進行状況のログは標準出力に出るため、テストの出力にしない
        ob_start();
        try {
            (new YearmonthMigration())->process();
        } finally {
            ob_end_clean();
        }
    }

    private function countRows(string $sql): int
    {
        $result = $this->db->pquery($sql, []);
        return $result === false ? 0 : (int) $this->db->num_rows($result);
    }
}
