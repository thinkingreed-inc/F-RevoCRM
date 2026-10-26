<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use Migration20261026113427_AddFileuploadUitype as FileuploadMigration;
use PearDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * add_fileupload_uitype マイグレーション
 *
 * 対象: setup/migration/scripts/20261026113427_add_fileupload_uitype.php
 *
 *   - uitype 998 → fileupload の対応、vtiger_field.handwriting、FileFieldHandler の登録を行う
 *   - 2 回実行しても重複しない（冪等性）
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AddFileuploadUitypeTest extends TestCase
{
    private PearDatabase $db;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 3);
        require_once $root . '/include/utils/CommonUtils.php';
        require_once $root . '/setup/migration/scripts/20261026113427_add_fileupload_uitype.php';

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
        $this->db->pquery("DELETE FROM vtiger_ws_fieldtype WHERE uitype = '998'", []);
        $this->db->pquery("DELETE FROM vtiger_eventhandlers WHERE handler_class = 'FileFieldHandler'", []);
        $hasFileFields = $this->countRows("SELECT 1 FROM vtiger_field WHERE uitype = '998'") > 0;
        if (!$hasFileFields && $this->columnExists()) {
            $this->db->pquery('ALTER TABLE vtiger_field DROP COLUMN handwriting', []);
        }

        try {
            $this->runMigration();
            $this->runMigration();
        } finally {
            // 途中で失敗しても他のテストが動く状態に戻す
            $this->runMigration();
        }

        $this->assertSame(
            1,
            $this->countRows("SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = '998' AND fieldtype = 'fileupload'"),
            'uitype 998 は fileupload に対応づけ、重複させない'
        );
        $this->assertTrue($this->columnExists(), 'vtiger_field.handwriting がある');
        $this->assertSame(1, $this->countRows(
            "SELECT 1 FROM vtiger_eventhandlers WHERE handler_class = 'FileFieldHandler' AND event_name = 'vtiger.entity.beforesave'"
        ));
        $this->assertSame(1, $this->countRows(
            "SELECT 1 FROM vtiger_eventhandlers WHERE handler_class = 'FileFieldHandler' AND event_name = 'vtiger.entity.aftersave'"
        ));

        $result = $this->db->pquery(
            "SELECT handler_path, is_active FROM vtiger_eventhandlers WHERE handler_class = 'FileFieldHandler' LIMIT 1",
            []
        );
        $this->assertSame('modules/Documents/handlers/FileFieldHandler.php', $this->db->query_result($result, 0, 'handler_path'));
        $this->assertSame(1, (int) $this->db->query_result($result, 0, 'is_active'));
    }

    public function test_handwriting列は既定でゼロ(): void
    {
        $result = $this->db->pquery(
            "SELECT COLUMN_DEFAULT AS column_default, IS_NULLABLE AS is_nullable FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtiger_field' AND COLUMN_NAME = 'handwriting'",
            []
        );
        $this->assertSame(1, $this->db->num_rows($result));
        $this->assertSame('0', (string) $this->db->query_result($result, 0, 'column_default'));
        $this->assertSame('NO', $this->db->query_result($result, 0, 'is_nullable'));
    }

    private function runMigration(): void
    {
        // 進行状況のログは標準出力に出るため、テストの出力にしない
        ob_start();
        try {
            (new FileuploadMigration())->process();
        } finally {
            ob_end_clean();
        }
    }

    private function columnExists(): bool
    {
        return $this->countRows(
            "SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vtiger_field' AND COLUMN_NAME = 'handwriting'"
        ) > 0;
    }

    private function countRows(string $sql): int
    {
        $result = $this->db->pquery($sql, []);
        return $result === false ? 0 : (int) $this->db->num_rows($result);
    }
}
