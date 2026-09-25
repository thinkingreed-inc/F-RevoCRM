<?php
/**
 * テスト: ドキュメント（電子帳簿保存法対応）のマイグレーション
 *
 * 使い捨ての DB を作り、ブランチ適用前のダンプ（e2e/fixtures/e2e_dump.sql）から
 * マイグレーションを実際に流して検証する。開発 DB には一切触れない。
 *
 * 対象（docs/tests/Documents/TS-11_マイグレーション.md）:
 *   M-01 20260616034423_add_documents_to_menu
 *   M-02 20260616125641_setup_documents_compliance_schema
 *   M-03 20260617153210_setup_documents_folders
 *   M-04 20260806084006_setup_holidays_master
 *   M-05 20260806102225_setup_documents_settings
 *
 * テスト観点:
 *   1. 新規適用でテーブル・カラム・項目・ピックリスト・設定・メニュー・cron が揃う（TC-MG-001/004）
 *   2. 冪等性: 適用済み管理をクリアして再実行しても二重適用されない（TC-MG-002/012/022/030b/040b/092/112/121/130c）
 *   3. ファイルサイズの BIGINT 化のデシジョン表と値の保持（TC-MG-050〜054）
 *   4. 片方のテーブルだけ存在する部分適用（TC-MG-030c）
 *   5. LBL_CONFIGURATION ブロックが無い環境（TC-MG-083）
 *   6. 設定メニューが別ブロックにある環境からの移動（TC-MG-100）
 *   7. config.customize.php の $business_week_holidays の引き継ぎ（TC-MG-091）
 *   8. Documents が vtiger_tab に無い場合の明示的な失敗（TC-MG-011）
 *   9. 既存の設定を上書きしない（TC-MG-130d）
 *
 * Usage:
 *   php test/unit/migration/test_documents_migrations.php [--keep]
 */

chdir(dirname(__FILE__) . '/../../../');

// アプリ側のブートストラップを先に済ませる
// （config.php が config.inc.php を毎回 include し直すため、接続先は後から差し替える）
require_once 'setup/migration/FRMigrationClass.php';
require_once 'include/utils/utils.php';

/** 使い捨て DB の名前。この接頭辞以外には接続しない */
define('TEST_DB_PREFIX', 'frevocrm_migration_test');

class TestDocumentsMigrations {

    /** 適用対象のマイグレーション（ファイル名 => クラス名） */
    private $migrations = array(
        '20260616034423_add_documents_to_menu.php'
            => 'Migration20260616034423_AddDocumentsToMenu',
        '20260616125641_setup_documents_compliance_schema.php'
            => 'Migration20260616125641_SetupDocumentsComplianceSchema',
        '20260617153210_setup_documents_folders.php'
            => 'Migration20260617153210_SetupDocumentsFolders',
        '20260806084006_setup_holidays_master.php'
            => 'Migration20260806084006_SetupHolidaysMaster',
        '20260806102225_setup_documents_settings.php'
            => 'Migration20260806102225_SetupDocumentsSettings',
    );

    /** 電帳法で追加される項目（項目名 => ブロックラベル） */
    private $expectedFields = array(
        'document_category'     => 'LBL_COMPLIANCE_SECTION',
        'preservation_type'     => 'LBL_COMPLIANCE_SECTION',
        'receipt_date'          => 'LBL_SCANNER_SECTION',
        'input_deadline'        => 'LBL_SCANNER_SECTION',
        'input_deadline_status' => 'LBL_SCANNER_SECTION',
        'scan_resolution_dpi'   => 'LBL_SCANNER_SECTION',
        'scan_color_type'       => 'LBL_SCANNER_SECTION',
        'original_paper_size'   => 'LBL_SCANNER_SECTION',
        'scanned_by'            => 'LBL_SCANNER_SECTION',
        'scanned_at'            => 'LBL_SCANNER_SECTION',
        'compliance_status'     => 'LBL_COMPLIANCE_STATUS_SECTION',
        'compliance_checked_at' => 'LBL_COMPLIANCE_STATUS_SECTION',
        'compliance_notes'      => 'LBL_COMPLIANCE_STATUS_SECTION',
        'file_hash_algorithm'   => 'LBL_FILE_INFORMATION',
        'file_hash'             => 'LBL_FILE_INFORMATION',
    );

    /** 追加されるピックリスト（項目名 => 値） */
    private $expectedPicklists = array(
        'document_category'     => array('invoice', 'receipt', 'contract', 'estimate', 'order', 'delivery', 'other'),
        'preservation_type'     => array('electronic_transaction', 'scanner'),
        'input_deadline_status' => array('within', 'warning', 'overdue'),
        'scan_color_type'       => array('color', 'grayscale'),
        'original_paper_size'   => array('A3', 'A4', 'A5', 'B4', 'B5', 'other'),
        'compliance_status'     => array('compliant', 'non_compliant'),
    );

    /** マイグレーション実行用（アプリと同じ経路） */
    private $db;
    /** 検証用の生の接続 */
    private $raw;
    private $dbName;
    private $keepDatabase = false;
    private $passed = 0;
    private $failed = 0;
    private $errors = array();

    public function __construct($keepDatabase = false) {
        $this->keepDatabase = $keepDatabase;
        $this->dbName = TEST_DB_PREFIX;
    }

    public function run() {
        echo "=== ドキュメント関連マイグレーション テスト開始 ===\n\n";

        if (!$this->setUpDatabase()) {
            return false;
        }

        try {
            $this->testFreshInstall();
            $this->testIdempotency();
            $this->testFilesizeWidening();
            $this->testPartialComplianceTables();
            $this->testSettingsMenuWithoutBlock();
            $this->testSettingsMenuMove();
            $this->testWeeklyHolidaysFromConfig();
            $this->testDocumentsMenuWithoutTab();
            $this->testExistingSettingNotOverwritten();
        } catch (Throwable $e) {
            $this->fail('テスト実行中に例外: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->tearDownDatabase();

        echo "\n=== テスト結果 ===\n";
        echo "成功: {$this->passed}件\n";
        echo "失敗: {$this->failed}件\n";
        if ($this->failed > 0) {
            echo "\n--- 失敗詳細 ---\n";
            foreach ($this->errors as $err) {
                echo "  FAIL: {$err}\n";
            }
        }
        echo "\n";

        return $this->failed === 0;
    }

    // ------------------------------------------------------------------
    // テスト本体
    // ------------------------------------------------------------------

    /**
     * テスト1: 新規適用（TC-MG-001 / TC-MG-004）
     */
    private function testFreshInstall() {
        echo "[テスト1] ブランチ適用前の DB へ新規適用する\n";

        $this->applyAll();

        // テーブル
        foreach (array('vtiger_notes_audit_log', 'vtiger_notes_file_versions', 'vtiger_folder_permissions',
                       'vtiger_holidays', 'vtiger_holiday_settings', 'vtiger_documents_settings') as $table) {
            $this->assertTrue($this->tableExists($table), "テーブル {$table} が作られる");
        }

        // カラム
        $this->assertTrue($this->columnExists('vtiger_notes', 'indexed_content'), 'vtiger_notes.indexed_content が追加される');
        $this->assertTrue($this->columnExists('vtiger_attachmentsfolder', 'parent_folderid'), 'vtiger_attachmentsfolder.parent_folderid が追加される');
        $this->assertEquals('bigint', $this->columnType('vtiger_notes', 'filesize'), 'vtiger_notes.filesize が BIGINT になる');
        $this->assertEquals('bigint', $this->columnType('vtiger_notes_file_versions', 'file_size'), 'vtiger_notes_file_versions.file_size が BIGINT になる');

        // 項目定義とブロック
        $tabId = $this->documentsTabId();
        foreach ($this->expectedFields as $field => $blockLabel) {
            $this->assertTrue($this->columnExists('vtiger_notes', $field), "vtiger_notes.{$field} が追加される");
            $actualBlock = $this->fieldBlockLabel($tabId, $field);
            $this->assertEquals($blockLabel, $actualBlock, "項目 {$field} が {$blockLabel} に登録される");
        }

        // ピックリスト
        foreach ($this->expectedPicklists as $field => $values) {
            $actual = $this->picklistValues($field);
            $this->assertEquals(implode(',', $values), implode(',', $actual), "ピックリスト {$field} の値");
        }

        // アプリメニュー: ツールのみ表示ON
        $rows = $this->fetchAll("SELECT appname, visible FROM vtiger_app2tab WHERE tabid = {$tabId}");
        $this->assertTrue(count($rows) > 0, 'Documents がアプリメニューに登録される');
        $visibleApps = array();
        foreach ($rows as $row) {
            if ((int) $row['visible'] === 1) {
                $visibleApps[] = $row['appname'];
            }
        }
        $this->assertEquals('TOOLS', implode(',', $visibleApps), 'ツールのみ表示ONになる');

        // 設定メニュー
        $this->assertEquals('LBL_CONFIGURATION', $this->settingsFieldBlock('LBL_HOLIDAYS'), '休祝日マスタがシステム構成に登録される');
        $this->assertEquals('LBL_CONFIGURATION', $this->settingsFieldBlock('LBL_DOCUMENTS_COMPLIANCE'), '電帳法設定がシステム構成に登録される');

        // 定期ジョブ
        $cron = $this->fetchAll("SELECT frequency, status, module FROM vtiger_cron_task WHERE name = 'DocumentsInputDeadlineStatus'");
        $this->assertEquals(1, count($cron), '入力期限の定期ジョブが1件登録される');
        if ($cron) {
            $this->assertEquals('86400', (string) $cron[0]['frequency'], '定期ジョブの実行間隔は1日');
            $this->assertEquals('Documents', $cron[0]['module'], '定期ジョブのモジュールは Documents');
        }

        // 設定の既定値
        $this->assertEquals('prompt', $this->documentsSetting('input_deadline_policy'), '入力期限の方針の既定値');
        $this->assertEquals('7', $this->documentsSetting('input_deadline_business_days'), '入力期限の営業日数の既定値');
        $this->assertEquals('2', $this->documentsSetting('input_deadline_cycle_months'), '業務処理サイクルの月数の既定値');
        $this->assertEquals('3', $this->documentsSetting('input_deadline_warning_days'), '期限間近とする日数の既定値');
        $this->assertEquals('0,6', $this->holidaySetting('weekly_holidays'), '週休の曜日の既定値（日曜・土曜）');

        // 書類区分ごとの取引モジュール
        $modules = json_decode($this->documentsSetting('compliance_transaction_modules'), true);
        $this->assertTrue(is_array($modules), '書類区分ごとの取引モジュールが JSON で登録される');
        if (is_array($modules)) {
            $this->assertTrue(in_array('ServiceContracts', $modules['contract'], true),
                '契約書の適合判定に契約（ServiceContracts）が含まれる');
        }

        // 祝日の初期投入（実行年の前年から4年分）
        $startYear = max((int) date('Y') - 1, 2020);
        for ($i = 0; $i < 4; $i++) {
            $year = $startYear + $i;
            $count = (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_holidays WHERE YEAR(holiday_date) = {$year}", 'c');
            $this->assertTrue($count > 0, "{$year}年の祝日が投入される（{$count}件）");
        }
        $national = (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_holidays WHERE holiday_type <> 'national'", 'c');
        $this->assertEquals(0, $national, '初期投入はすべて国民の祝日として登録される');

        // フォルダの既定権限
        $folders = (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_attachmentsfolder", 'c');
        $perms = (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_folder_permissions WHERE permission_type='edit' AND target_type='everyone'", 'c');
        $this->assertEquals($folders, $perms, '既存フォルダに全員編集可の既定権限が付く');
    }

    /**
     * テスト2: 冪等性（TC-MG-002）
     *
     * 適用済み管理をクリアして再実行し、DB の状態が1回目と変わらないことを確認する。
     */
    private function testIdempotency() {
        echo "[テスト2] 適用済み管理をクリアして再実行しても二重適用されない\n";

        $before = $this->stateFingerprint();
        $log = $this->applyAll();
        $after = $this->stateFingerprint();

        $this->assertEquals($before, $after, '再実行しても DB の状態が変わらない');

        // 主要なものが重複していないこと
        $this->assertEquals(1, (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM vtiger_cron_task WHERE name='DocumentsInputDeadlineStatus'", 'c'),
            '定期ジョブが重複しない');
        $this->assertEquals(1, (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM vtiger_settings_field WHERE name='LBL_HOLIDAYS'", 'c'),
            '休祝日マスタの設定メニューが重複しない');
        $this->assertEquals(3, (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM vtiger_blocks WHERE tabid={$this->documentsTabId()} AND iscustom=1", 'c'),
            '電帳法のブロックが重複しない');
        $this->assertEquals(count($this->expectedFields), (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM vtiger_field WHERE tabid={$this->documentsTabId()}
             AND fieldname IN ('" . implode("','", array_keys($this->expectedFields)) . "')", 'c'),
            '電帳法の項目が重複しない');
        $this->assertEquals(7, (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM vtiger_document_category", 'c'),
            'ピックリストの値が重複しない');

        // スキップした理由がログに出る（TC-MG-003 / S-05）
        $this->assertTrue(strpos($log, 'スキップ') !== false, 'スキップ理由がログに出る');
    }

    /**
     * テスト3: ファイルサイズの BIGINT 化（TC-MG-050〜054 / DT-1）
     */
    private function testFilesizeWidening() {
        echo "[テスト3] ファイルサイズの BIGINT 化\n";

        // R4: INT の状態に戻し、境界値を入れてから適用する
        $this->query("ALTER TABLE vtiger_notes MODIFY filesize INT DEFAULT NULL");
        $this->insertNote(9900001, 'max', '2147483647');
        $this->insertNote(9900002, 'null', 'NULL');
        $this->insertNote(9900003, 'zero', '0');

        $log = $this->applyOne('20260616125641_setup_documents_compliance_schema.php');
        $this->assertEquals('bigint', $this->columnType('vtiger_notes', 'filesize'), 'INT のカラムが BIGINT になる');
        $this->assertEquals('2147483647', (string) $this->fetchOne("SELECT filesize AS v FROM vtiger_notes WHERE notesid=9900001", 'v'), '最大値が保持される');
        $this->assertEquals(null, $this->fetchOne("SELECT filesize AS v FROM vtiger_notes WHERE notesid=9900002", 'v'), 'NULL が保持される');
        $this->assertEquals('0', (string) $this->fetchOne("SELECT filesize AS v FROM vtiger_notes WHERE notesid=9900003", 'v'), '0 が保持される');

        // R3: 既に BIGINT なら何もしない
        $log = $this->applyOne('20260616125641_setup_documents_compliance_schema.php');
        $this->assertTrue(strpos($log, 'vtiger_notes.filesize は既に BIGINT') !== false, '既に BIGINT ならスキップする');

        // R1: 対象テーブルが無い
        $this->query("DROP TABLE vtiger_notes_file_versions");
        $log = $this->applyOne('20260616125641_setup_documents_compliance_schema.php');
        $this->assertTrue($this->tableExists('vtiger_notes_file_versions'), '無くなったテーブルは作り直される');
        $this->assertEquals('bigint', $this->columnType('vtiger_notes_file_versions', 'file_size'), '作り直したテーブルの file_size は最初から BIGINT');

        // R2: 対象カラムが無い
        $this->query("ALTER TABLE vtiger_notes_file_versions DROP COLUMN file_size");
        $log = $this->applyOne('20260616125641_setup_documents_compliance_schema.php');
        $this->assertTrue(strpos($log, 'file_size が存在しないためスキップ') !== false, 'カラムが無ければスキップする');
        $this->query("ALTER TABLE vtiger_notes_file_versions ADD COLUMN file_size BIGINT NOT NULL DEFAULT 0");

        $this->query("DELETE FROM vtiger_crmentity WHERE crmid IN (9900001, 9900002, 9900003)");
    }

    /**
     * 検証用のドキュメントを1件登録する（vtiger_notes は crmentity への外部キーを持つ）
     */
    private function insertNote($id, $title, $filesizeExpression) {
        $this->query("INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, modifiedby, setype, createdtime, modifiedtime, deleted)
                      VALUES ({$id}, 1, 1, 1, 'Documents', NOW(), NOW(), 0)");
        $this->query("INSERT INTO vtiger_notes (notesid, note_no, title, filesize)
                      VALUES ({$id}, 'TEST-{$id}', " . $this->quote($title) . ", {$filesizeExpression})");
    }

    /**
     * テスト4: 片方のテーブルだけ存在する部分適用（TC-MG-030c）
     */
    private function testPartialComplianceTables() {
        echo "[テスト4] 片方のテーブルだけ存在する状態からの適用\n";

        $this->query("DROP TABLE vtiger_notes_audit_log");
        $log = $this->applyOne('20260616125641_setup_documents_compliance_schema.php');

        $this->assertTrue($this->tableExists('vtiger_notes_audit_log'), '無い方のテーブルだけ作られる');
        $this->assertTrue(strpos($log, 'vtiger_notes_file_versions は既に存在するためスキップ') !== false,
            '存在する方はスキップされる');
    }

    /**
     * テスト5: LBL_CONFIGURATION ブロックが無い環境（TC-MG-083 / S-02）
     */
    private function testSettingsMenuWithoutBlock() {
        echo "[テスト5] システム構成ブロックが無い環境\n";

        $blockId = (int) $this->fetchOne("SELECT blockid AS v FROM vtiger_settings_blocks WHERE label='LBL_CONFIGURATION'", 'v');
        $this->query("DELETE FROM vtiger_settings_field WHERE name='LBL_HOLIDAYS'");
        $this->query("DELETE FROM vtiger_settings_blocks WHERE label='LBL_CONFIGURATION'");
        $this->query("DROP TABLE vtiger_holidays");

        $log = $this->applyOne('20260806084006_setup_holidays_master.php');

        $this->assertTrue($this->tableExists('vtiger_holidays'), 'ブロックが無くてもテーブルは作られる');
        $this->assertTrue((int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_holidays", 'c') > 0,
            'ブロックが無くても祝日は投入される');
        $this->assertTrue(strpos($log, 'LBL_CONFIGURATION が見つからないため') !== false,
            'メニュー登録のみスキップし、理由がログに出る');
        $this->assertEquals(0, (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_settings_field WHERE name='LBL_HOLIDAYS'", 'c'),
            'メニューは登録されない');

        // ブロックを戻す
        $this->query("INSERT INTO vtiger_settings_blocks (blockid, label, sequence) VALUES ({$blockId}, 'LBL_CONFIGURATION', 2)");
    }

    /**
     * テスト6: 設定メニューを別ブロックからシステム構成へ移動する（TC-MG-100 / TC-MG-101）
     */
    private function testSettingsMenuMove() {
        echo "[テスト6] 設定メニューを別ブロックからシステム構成へ移動する\n";

        $otherBlockId = (int) $this->fetchOne(
            "SELECT blockid AS v FROM vtiger_settings_blocks WHERE label <> 'LBL_CONFIGURATION' ORDER BY blockid LIMIT 1", 'v');
        $fieldId = $this->nextSettingsFieldId();
        $this->query("INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
                      VALUES ({$fieldId}, {$otherBlockId}, 'LBL_HOLIDAYS', 'adminIcon-calendar', 'LBL_HOLIDAYS_DESCRIPTION',
                              'index.php?module=Holidays&parent=Settings&view=List', 99, 0)");

        $log = $this->applyOne('20260806084006_setup_holidays_master.php');
        $this->assertEquals('LBL_CONFIGURATION', $this->settingsFieldBlock('LBL_HOLIDAYS'), 'システム構成へ移動する');
        $this->assertEquals(1, (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_settings_field WHERE name='LBL_HOLIDAYS'", 'c'),
            '移動であって追加ではない');

        // 既にシステム構成にある場合は何もしない
        $before = $this->fetchOne("SELECT sequence AS v FROM vtiger_settings_field WHERE name='LBL_HOLIDAYS'", 'v');
        $this->applyOne('20260806084006_setup_holidays_master.php');
        $after = $this->fetchOne("SELECT sequence AS v FROM vtiger_settings_field WHERE name='LBL_HOLIDAYS'", 'v');
        $this->assertEquals($before, $after, '既にシステム構成にあれば並び順も変えない');
    }

    /**
     * テスト7: config.customize.php の週休設定を引き継ぐ（TC-MG-091）
     */
    private function testWeeklyHolidaysFromConfig() {
        echo "[テスト7] config.customize.php の週休設定の引き継ぎ\n";

        $this->query("DELETE FROM vtiger_holiday_settings WHERE name='weekly_holidays'");
        $GLOBALS['business_week_holidays'] = array(0);

        $this->applyOne('20260806084006_setup_holidays_master.php');
        $this->assertEquals('0', $this->holidaySetting('weekly_holidays'), 'config の値を引き継ぐ');

        // 既に登録済みなら上書きしない（TC-MG-092）
        $GLOBALS['business_week_holidays'] = array(0, 6);
        $this->applyOne('20260806084006_setup_holidays_master.php');
        $this->assertEquals('0', $this->holidaySetting('weekly_holidays'), '登録済みの設定は上書きしない');

        unset($GLOBALS['business_week_holidays']);
        $this->query("UPDATE vtiger_holiday_settings SET value='0,6' WHERE name='weekly_holidays'");
    }

    /**
     * テスト8: Documents が vtiger_tab に無い場合（TC-MG-011 / S-02）
     */
    private function testDocumentsMenuWithoutTab() {
        echo "[テスト8] Documents が vtiger_tab に無い環境\n";

        $tabId = $this->documentsTabId();
        $this->query("DELETE FROM vtiger_app2tab WHERE tabid = {$tabId}");
        // vtiger_customview.entitytype が vtiger_tab.name を参照しているため、一時的に外部キーを外す
        $this->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->query("UPDATE vtiger_tab SET name='Documents_renamed' WHERE tabid = {$tabId}");
        $this->assertEquals('Documents_renamed',
            $this->fetchOne("SELECT name AS v FROM vtiger_tab WHERE tabid = {$tabId}", 'v'),
            'Documents が vtiger_tab から見つからない状態を作れる');

        $message = '';
        try {
            $this->applyOne('20260616034423_add_documents_to_menu.php');
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertTrue(strpos($message, 'vtiger_tab に見つかりません') !== false,
            '原因が分かる例外メッセージで失敗する（実際: ' . $message . '）');
        $this->assertEquals(0, (int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_app2tab WHERE tabid={$tabId}", 'c'),
            '失敗時はロールバックされ、中途半端に登録されない');

        // 元に戻して再適用できることを確認する
        $this->query("UPDATE vtiger_tab SET name='Documents' WHERE tabid = {$tabId}");
        $this->query('SET FOREIGN_KEY_CHECKS = 1');
        $this->applyOne('20260616034423_add_documents_to_menu.php');
        $this->assertTrue((int) $this->fetchOne("SELECT COUNT(*) AS c FROM vtiger_app2tab WHERE tabid={$tabId}", 'c') > 0,
            '復旧後は正常に登録される');
    }

    /**
     * テスト9: 手動で変更した設定を上書きしない（TC-MG-130d）
     */
    private function testExistingSettingNotOverwritten() {
        echo "[テスト9] 手動で変更した設定を上書きしない\n";

        $custom = json_encode(array('contract' => array('Accounts')));
        $this->query("UPDATE vtiger_documents_settings SET value=" . $this->quote($custom) .
                     " WHERE name='compliance_transaction_modules'");
        $this->query("UPDATE vtiger_documents_settings SET value='cycle' WHERE name='input_deadline_policy'");

        $this->applyOne('20260806102225_setup_documents_settings.php');

        $this->assertEquals($custom, $this->documentsSetting('compliance_transaction_modules'),
            '書類区分ごとの取引モジュールを上書きしない');
        $this->assertEquals('cycle', $this->documentsSetting('input_deadline_policy'),
            '入力期限の方針を上書きしない');
    }

    // ------------------------------------------------------------------
    // マイグレーション実行
    // ------------------------------------------------------------------

    /**
     * すべてのマイグレーションを適用する（適用済み管理はクリアしてから実行する）
     */
    private function applyAll() {
        $log = '';
        foreach ($this->migrations as $file => $className) {
            $log .= $this->applyOne($file);
        }
        return $log;
    }

    /**
     * マイグレーションを1本適用する
     */
    private function applyOne($file) {
        $className = $this->migrations[$file];
        require_once 'setup/migration/scripts/' . $file;

        $this->db->pquery('DELETE FROM com_vtiger_migrations WHERE migration_name = ?', array($className));

        ob_start();
        try {
            $migration = new $className();
            $migration->execute();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // 使い捨て DB の準備と後始末
    // ------------------------------------------------------------------

    private function setUpDatabase() {
        $dump = 'e2e/fixtures/e2e_dump.sql';
        if (!file_exists($dump)) {
            echo "SKIP: ブランチ適用前のダンプが見つかりません ({$dump})\n";
            return false;
        }
        if (!$this->commandExists('mysql')) {
            echo "SKIP: mysql コマンドが見つかりません\n";
            return false;
        }

        $config = $GLOBALS['dbconfig'];
        $this->mysqlUser = $config['db_username'];
        $this->mysqlPass = $config['db_password'];
        $this->mysqlHost = ($config['db_server'] === 'localhost') ? '127.0.0.1' : $config['db_server'];

        echo "使い捨て DB を作成します: {$this->dbName}\n";
        $this->mysql("DROP DATABASE IF EXISTS `{$this->dbName}`; CREATE DATABASE `{$this->dbName}` CHARACTER SET utf8mb4;");

        echo "ブランチ適用前のダンプを投入します（時間がかかります）\n";
        $command = $this->mysqlCommand() . ' ' . escapeshellarg($this->dbName) . ' < ' . escapeshellarg($dump) . ' 2>&1';
        exec($command, $output, $status);
        if ($status !== 0) {
            echo "SKIP: ダンプの投入に失敗しました: " . implode("\n", $output) . "\n";
            return false;
        }

        // 接続先を使い捨て DB へ差し替える
        $GLOBALS['dbconfig']['db_name'] = $this->dbName;
        unset($GLOBALS['adb']);
        $this->db = PearDatabase::getInstance();

        $this->raw = @new mysqli($this->mysqlHost, $this->mysqlUser, $this->mysqlPass, $this->dbName);
        if ($this->raw->connect_error) {
            echo "SKIP: 使い捨て DB へ接続できません: {$this->raw->connect_error}\n";
            return false;
        }
        $this->raw->set_charset('utf8mb4');

        $current = $this->fetchOne('SELECT DATABASE() AS v', 'v');
        if ($current !== $this->dbName || strpos($current, TEST_DB_PREFIX) !== 0) {
            echo "SKIP: 接続先が使い捨て DB になっていません（{$current}）\n";
            return false;
        }
        echo "接続先: {$current}\n\n";
        return true;
    }

    private function tearDownDatabase() {
        if ($this->keepDatabase) {
            echo "\n使い捨て DB を残します: {$this->dbName}\n";
            return;
        }
        $this->mysql("DROP DATABASE IF EXISTS `{$this->dbName}`;");
        echo "\n使い捨て DB を削除しました: {$this->dbName}\n";
    }

    private $mysqlUser;
    private $mysqlPass;
    private $mysqlHost;

    private function mysqlCommand() {
        $command = 'mysql -u' . escapeshellarg($this->mysqlUser) . ' -h' . escapeshellarg($this->mysqlHost);
        if ($this->mysqlPass !== '') {
            $command .= ' -p' . escapeshellarg($this->mysqlPass);
        }
        return $command;
    }

    private function mysql($sql) {
        $command = $this->mysqlCommand() . ' -e ' . escapeshellarg($sql) . ' 2>&1';
        exec($command, $output, $status);
        return $status === 0;
    }

    private function commandExists($name) {
        exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null', $output, $status);
        return $status === 0;
    }

    // ------------------------------------------------------------------
    // DB 参照ヘルパー
    //
    // 検証用の参照・更新は生の mysqli で行う。PearDatabase は取得値に
    // HTML エスケープをかけるため、JSON などの比較に使えない。
    // ------------------------------------------------------------------

    /** 使い捨て DB 以外では実行しない */
    private function query($sql) {
        $result = $this->raw->query($sql);
        if ($result === false) {
            $this->fail('SQL 実行に失敗: ' . $this->raw->error . ' / ' . $sql);
        }
        return $result;
    }

    private function quote($value) {
        return "'" . $this->raw->real_escape_string($value) . "'";
    }

    private function fetchAll($sql) {
        $result = $this->raw->query($sql);
        if ($result === false) {
            $this->fail('SQL 実行に失敗: ' . $this->raw->error . ' / ' . $sql);
            return array();
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function fetchOne($sql, $column) {
        $rows = $this->fetchAll($sql);
        return $rows ? $rows[0][$column] : null;
    }

    private function tableExists($table) {
        return (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $this->quote($table), 'c') > 0;
    }

    private function columnExists($table, $column) {
        return (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $this->quote($table) .
            " AND COLUMN_NAME = " . $this->quote($column), 'c') > 0;
    }

    private function columnType($table, $column) {
        return $this->fetchOne(
            "SELECT DATA_TYPE AS v FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $this->quote($table) .
            " AND COLUMN_NAME = " . $this->quote($column), 'v');
    }

    private function documentsTabId() {
        return (int) $this->fetchOne("SELECT tabid AS v FROM vtiger_tab WHERE name='Documents'", 'v');
    }

    private function fieldBlockLabel($tabId, $fieldName) {
        return $this->fetchOne(
            "SELECT b.blocklabel AS v FROM vtiger_field f JOIN vtiger_blocks b ON b.blockid = f.block
             WHERE f.tabid = {$tabId} AND f.fieldname = " . $this->quote($fieldName), 'v');
    }

    private function picklistValues($field) {
        $rows = $this->fetchAll("SELECT `{$field}` AS v FROM `vtiger_{$field}` ORDER BY sortorderid");
        $values = array();
        foreach ($rows as $row) {
            $values[] = $row['v'];
        }
        return $values;
    }

    private function settingsFieldBlock($name) {
        return $this->fetchOne(
            "SELECT b.label AS v FROM vtiger_settings_field f JOIN vtiger_settings_blocks b ON b.blockid = f.blockid
             WHERE f.name = " . $this->quote($name), 'v');
    }

    private function nextSettingsFieldId() {
        return (int) $this->fetchOne("SELECT COALESCE(MAX(fieldid), 0) + 1 AS v FROM vtiger_settings_field", 'v');
    }

    private function documentsSetting($name) {
        return $this->fetchOne("SELECT value AS v FROM vtiger_documents_settings WHERE name = " . $this->quote($name), 'v');
    }

    private function holidaySetting($name) {
        return $this->fetchOne("SELECT value AS v FROM vtiger_holiday_settings WHERE name = " . $this->quote($name), 'v');
    }

    /**
     * 冪等性の判定に使う DB 状態の要約
     */
    private function stateFingerprint() {
        $tabId = $this->documentsTabId();
        $parts = array();

        $parts[] = 'FIELDS:' . json_encode($this->fetchAll(
            "SELECT f.fieldname, f.columnname, f.uitype, f.typeofdata, f.displaytype, f.readonly,
                    f.masseditable, f.sequence, f.defaultvalue, b.blocklabel
             FROM vtiger_field f LEFT JOIN vtiger_blocks b ON b.blockid = f.block
             WHERE f.tabid = {$tabId} ORDER BY f.fieldname"));
        $parts[] = 'BLOCKS:' . json_encode($this->fetchAll(
            "SELECT blocklabel, sequence, iscustom FROM vtiger_blocks WHERE tabid = {$tabId} ORDER BY blocklabel"));
        $parts[] = 'APP2TAB:' . json_encode($this->fetchAll(
            "SELECT appname, sequence, visible FROM vtiger_app2tab WHERE tabid = {$tabId} ORDER BY appname"));
        $parts[] = 'SETTINGS_MENU:' . json_encode($this->fetchAll(
            "SELECT f.name, b.label, f.sequence, f.linkto FROM vtiger_settings_field f
             JOIN vtiger_settings_blocks b ON b.blockid = f.blockid ORDER BY f.name"));
        $parts[] = 'CRON:' . json_encode($this->fetchAll(
            "SELECT name, handler_file, frequency, module FROM vtiger_cron_task ORDER BY name"));
        $parts[] = 'DOC_SETTINGS:' . json_encode($this->fetchAll(
            "SELECT name, value FROM vtiger_documents_settings ORDER BY name"));
        $parts[] = 'HOLIDAY_SETTINGS:' . json_encode($this->fetchAll(
            "SELECT name, value FROM vtiger_holiday_settings ORDER BY name"));
        $parts[] = 'HOLIDAYS:' . json_encode($this->fetchAll(
            "SELECT holiday_date, holiday_name, day_type, holiday_type FROM vtiger_holidays ORDER BY holiday_date"));
        $parts[] = 'FOLDER_PERMS:' . json_encode($this->fetchAll(
            "SELECT folderid, permission_type, target_type, target_id FROM vtiger_folder_permissions
             ORDER BY folderid, permission_type, target_type"));
        foreach (array_keys($this->expectedPicklists) as $field) {
            $parts[] = 'PICKLIST_' . $field . ':' . json_encode($this->picklistValues($field));
        }
        $parts[] = 'COLUMNS:' . json_encode($this->fetchAll(
            "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('vtiger_notes','vtiger_attachmentsfolder','vtiger_notes_audit_log',
                                  'vtiger_notes_file_versions','vtiger_folder_permissions','vtiger_holidays',
                                  'vtiger_holiday_settings','vtiger_documents_settings')
             ORDER BY TABLE_NAME, COLUMN_NAME"));

        return implode("\n", $parts);
    }

    // ------------------------------------------------------------------
    // アサーション
    // ------------------------------------------------------------------

    private function assertTrue($condition, $message) {
        if ($condition) {
            $this->pass($message);
        } else {
            $this->fail($message);
        }
    }

    private function assertEquals($expected, $actual, $message) {
        if ($expected === $actual || (string) $expected === (string) $actual) {
            $this->pass($message);
        } else {
            $expectedText = is_scalar($expected) || $expected === null ? var_export($expected, true) : '(複合値)';
            $actualText = is_scalar($actual) || $actual === null ? var_export($actual, true) : '(複合値)';
            $this->fail("{$message}（期待: {$expectedText} / 実際: {$actualText}）");
        }
    }

    private function pass($message) {
        $this->passed++;
        echo "  OK: {$message}\n";
    }

    private function fail($message) {
        $this->failed++;
        $this->errors[] = $message;
        echo "  NG: {$message}\n";
    }
}

$keep = in_array('--keep', $argv, true);
$test = new TestDocumentsMigrations($keep);
exit($test->run() ? 0 : 1);
