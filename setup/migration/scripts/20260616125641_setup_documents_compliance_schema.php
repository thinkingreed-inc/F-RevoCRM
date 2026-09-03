<?php
/**
 * マイグレーション: setup_documents_compliance_schema
 * 生成日時: 20260616125641
 *
 * 電子帳簿保存法対応のドキュメント機能が必要とするスキーマを一括で用意する。
 *
 *   1. 訂正削除履歴（vtiger_notes_audit_log）とファイルバージョン（vtiger_notes_file_versions）
 *   2. ファイルサイズカラムの BIGINT 化（2GB 超のファイルで桁あふれさせない）
 *   3. 全文検索用の vtiger_notes.indexed_content
 *   4. 電帳法・スキャナ保存・適合管理の項目定義（vtiger_field / ブロック / ピックリスト）
 *
 * 既存のテーブル・カラム・項目はスキップするため、途中まで適用済みの環境でも実行できる。
 */

require_once('include/logging.php');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Settings/MenuEditor/models/Module.php');
require_once('modules/Settings/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/MenuStructure.php');
require_once('modules/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/Record.php');
require_once('modules/Users/models/Record.php');
require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');
include_once('includes/runtime/LanguageHandler.php');
include_once('includes/runtime/Globals.php');
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260616125641_SetupDocumentsComplianceSchema extends FRMigrationClass {

    /** 対象モジュール */
    const MODULE_NAME = 'Documents';

    /** 追加するブロック（ラベル => 表示順） */
    private $blocks = array(
        'LBL_COMPLIANCE_SECTION'        => 4,
        'LBL_SCANNER_SECTION'           => 5,
        'LBL_COMPLIANCE_STATUS_SECTION' => 6,
    );

    /**
     * 追加する項目
     *
     * 配列の順序がそのまま登録順（＝クイック作成の並び順）になるため、並べ替えないこと。
     * sequence を省略した項目はブロック内の末尾に追加される。
     */
    private $fields = array(
        // 電子帳簿保存法
        array(
            'block' => 'LBL_COMPLIANCE_SECTION',
            'name' => 'document_category', 'label' => 'LBL_DOCUMENT_CATEGORY',
            'columntype' => 'VARCHAR(50)', 'uitype' => 16, 'typeofdata' => 'V~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 1,
            'picklist' => array('invoice', 'receipt', 'contract', 'estimate', 'order', 'delivery', 'other'),
        ),
        array(
            'block' => 'LBL_COMPLIANCE_SECTION',
            'name' => 'preservation_type', 'label' => 'LBL_PRESERVATION_TYPE',
            'columntype' => 'VARCHAR(30)', 'uitype' => 16, 'typeofdata' => 'V~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 2,
            'picklist' => array('electronic_transaction', 'scanner'),
        ),
        // スキャナ保存
        array(
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'receipt_date', 'label' => 'LBL_RECEIPT_DATE',
            'columntype' => 'DATE', 'uitype' => 5, 'typeofdata' => 'D~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 1,
        ),
        array(
            // 入力期限は自動計算のため読み取り専用
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'input_deadline', 'label' => 'LBL_INPUT_DEADLINE',
            'columntype' => 'DATE', 'uitype' => 5, 'typeofdata' => 'D~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2, 'sequence' => 2,
        ),
        array(
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'input_deadline_status', 'label' => 'LBL_INPUT_DEADLINE_STATUS',
            'columntype' => 'VARCHAR(20)', 'uitype' => 16, 'typeofdata' => 'V~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2, 'sequence' => 3,
            'defaultvalue' => 'within',
            'picklist' => array('within', 'warning', 'overdue'),
        ),
        array(
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'scan_resolution_dpi', 'label' => 'LBL_SCAN_RESOLUTION',
            'columntype' => 'INT', 'uitype' => 7, 'typeofdata' => 'I~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 4,
        ),
        array(
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'scan_color_type', 'label' => 'LBL_COLOR_TYPE',
            'columntype' => 'VARCHAR(10)', 'uitype' => 16, 'typeofdata' => 'V~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 5,
            'picklist' => array('color', 'grayscale'),
        ),
        array(
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'original_paper_size', 'label' => 'LBL_ORIGINAL_PAPER_SIZE',
            'columntype' => 'VARCHAR(10)', 'uitype' => 16, 'typeofdata' => 'V~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 6,
            'picklist' => array('A3', 'A4', 'A5', 'B4', 'B5', 'other'),
        ),
        array(
            // スキャン実施者はユーザーIDを整数で記録する
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'scanned_by', 'label' => 'LBL_SCANNED_BY',
            'columntype' => 'INT', 'uitype' => 7, 'typeofdata' => 'I~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 7,
        ),
        array(
            'block' => 'LBL_SCANNER_SECTION',
            'name' => 'scanned_at', 'label' => 'LBL_SCANNED_AT',
            'columntype' => 'DATETIME', 'uitype' => 70, 'typeofdata' => 'DT~O',
            'displaytype' => 1, 'readonly' => 1, 'masseditable' => 1, 'sequence' => 8,
        ),
        // 適合管理（システムが判定するため読み取り専用）
        array(
            'block' => 'LBL_COMPLIANCE_STATUS_SECTION',
            'name' => 'compliance_status', 'label' => 'LBL_COMPLIANCE_STATUS',
            'columntype' => 'VARCHAR(20)', 'uitype' => 16, 'typeofdata' => 'V~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2, 'sequence' => 1,
            'picklist' => array('compliant', 'non_compliant'),
        ),
        array(
            'block' => 'LBL_COMPLIANCE_STATUS_SECTION',
            'name' => 'compliance_checked_at', 'label' => 'LBL_COMPLIANCE_CHECKED_AT',
            'columntype' => 'DATETIME', 'uitype' => 70, 'typeofdata' => 'DT~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2, 'sequence' => 2,
        ),
        array(
            'block' => 'LBL_COMPLIANCE_STATUS_SECTION',
            'name' => 'compliance_notes', 'label' => 'LBL_COMPLIANCE_NOTES',
            'columntype' => 'TEXT', 'uitype' => 19, 'typeofdata' => 'V~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2, 'sequence' => 3,
        ),
        // ファイル真正性（既存のファイル情報ブロックに追加する）
        array(
            'block' => 'LBL_FILE_INFORMATION',
            'name' => 'file_hash_algorithm', 'label' => 'LBL_FILE_HASH_ALGORITHM',
            'columntype' => 'VARCHAR(10)', 'uitype' => 1, 'typeofdata' => 'V~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2,
            'defaultvalue' => 'SHA-256',
        ),
        array(
            'block' => 'LBL_FILE_INFORMATION',
            'name' => 'file_hash', 'label' => 'LBL_FILE_HASH',
            'columntype' => 'VARCHAR(64)', 'uitype' => 1, 'typeofdata' => 'V~O',
            'displaytype' => 2, 'readonly' => 0, 'masseditable' => 2,
        ),
    );

    /** BIGINT に拡張するカラム: テーブル => array(カラム => 定義) */
    private $filesizeColumns = array(
        'vtiger_notes' => array(
            'filesize' => 'BIGINT DEFAULT NULL',
        ),
        'vtiger_notes_file_versions' => array(
            'file_size' => 'BIGINT NOT NULL DEFAULT 0',
        ),
    );

    public function process() {
        $this->createComplianceTables();
        $this->widenFilesizeColumns();
        $this->addIndexedContentColumn();
        $this->registerFields();
    }

    /**
     * 訂正削除履歴・ファイルバージョンのテーブルを作成する
     */
    private function createComplianceTables() {
        if ($this->checkTableExists('vtiger_notes_audit_log')) {
            $this->log("テーブル vtiger_notes_audit_log は既に存在するためスキップします");
        } else {
            $this->db->pquery("CREATE TABLE vtiger_notes_audit_log (
                audit_id BIGINT AUTO_INCREMENT,
                notesid INT NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                action_detail TEXT DEFAULT NULL,
                file_hash_before VARCHAR(64) DEFAULT NULL,
                file_hash_after VARCHAR(64) DEFAULT NULL,
                performed_by INT NOT NULL,
                performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                PRIMARY KEY (audit_id),
                INDEX idx_audit_notesid (notesid),
                INDEX idx_audit_action (action_type),
                INDEX idx_audit_performed_at (performed_at),
                INDEX idx_audit_user (performed_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
            $this->log("vtiger_notes_audit_log テーブルを作成しました");
        }

        if ($this->checkTableExists('vtiger_notes_file_versions')) {
            $this->log("テーブル vtiger_notes_file_versions は既に存在するためスキップします");
            return;
        }
        // file_size は 2GB 超を扱うため最初から BIGINT で作る
        $this->db->pquery("CREATE TABLE vtiger_notes_file_versions (
            version_id BIGINT AUTO_INCREMENT,
            notesid INT NOT NULL,
            version_number INT NOT NULL DEFAULT 1,
            attachmentsid INT NOT NULL,
            file_hash VARCHAR(64) NOT NULL,
            file_size BIGINT NOT NULL DEFAULT 0,
            change_reason TEXT DEFAULT NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_current TINYINT(1) DEFAULT 0,
            PRIMARY KEY (version_id),
            INDEX idx_version_notesid (notesid),
            INDEX idx_version_current (notesid, is_current),
            UNIQUE INDEX idx_version_unique (notesid, version_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log("vtiger_notes_file_versions テーブルを作成しました");
    }

    /**
     * ファイルサイズのカラムを BIGINT に拡張する
     *
     * signed INT は上限が 2,147,483,647（2GB-1）のため、2GB のファイルで桁あふれする。
     * 既に BIGINT の環境（新規作成時を含む）では何もしない。
     */
    private function widenFilesizeColumns() {
        foreach ($this->filesizeColumns as $table => $columns) {
            if (!$this->checkTableExists($table)) {
                $this->log("テーブル {$table} が存在しないためスキップします");
                continue;
            }
            foreach ($columns as $column => $definition) {
                $currentType = $this->getColumnType($table, $column);
                if ($currentType === null) {
                    $this->log("カラム {$table}.{$column} が存在しないためスキップします");
                    continue;
                }
                if (stripos($currentType, 'bigint') !== false) {
                    $this->log("カラム {$table}.{$column} は既に BIGINT のためスキップします");
                    continue;
                }
                $this->db->pquery("ALTER TABLE {$table} MODIFY {$column} {$definition}", array());
                $this->log("カラム {$table}.{$column} を {$currentType} から BIGINT に変更しました");
            }
        }
    }

    /**
     * 全文検索用のカラムを追加する
     *
     * PDF / Word / Excel / PowerPoint / テキストから抽出した本文を保持し、
     * 一覧のキーワード検索（タイトル・ファイル名との OR 条件）で参照する。
     * 既存ドキュメントの本文は入らないため、必要に応じて次のスクリプトで作り直す。
     *   php modules/Documents/schema/reindex_documents.php --execute
     */
    private function addIndexedContentColumn() {
        if ($this->checkColumnExists('vtiger_notes', 'indexed_content')) {
            $this->log("カラム vtiger_notes.indexed_content は既に存在するためスキップします");
            return;
        }

        $this->db->pquery(
            "ALTER TABLE vtiger_notes ADD COLUMN indexed_content LONGTEXT AFTER notecontent",
            array()
        );
        $this->log("カラム vtiger_notes.indexed_content を追加しました");
    }

    /**
     * 電帳法の項目をブロックごとに登録する
     */
    private function registerFields() {
        global $current_user;
        $current_user = Users::getActiveAdminUser();

        $module = Vtiger_Module::getInstance(self::MODULE_NAME);
        if (!$module) {
            throw new Exception(self::MODULE_NAME . ' モジュールが見つかりません');
        }

        foreach ($this->blocks as $label => $sequence) {
            $this->ensureBlock($module, $label, $sequence);
        }

        foreach ($this->fields as $definition) {
            $this->ensureField($module, $definition);
        }

        $this->log("電帳法の項目を Vtiger_Field として登録しました");
    }

    /**
     * ブロックが無ければ追加する
     */
    private function ensureBlock($module, $label, $sequence) {
        if ($this->findBlockId($module, $label) !== null) {
            $this->log("ブロック {$label} は既に存在するためスキップします");
            return;
        }

        $block = new Vtiger_Block();
        $block->label = $label;
        $block->sequence = $sequence;
        $block->iscustom = 1;
        $module->addBlock($block);
        $this->log("ブロック {$label} を追加しました");
    }

    /**
     * 項目が無ければ追加する
     */
    private function ensureField($module, $definition) {
        $name = $definition['name'];

        if ($this->findFieldId($module, $name) !== null) {
            $this->log("フィールド {$name} は既に存在するためスキップします");
            return;
        }

        $blockId = $this->findBlockId($module, $definition['block']);
        if ($blockId === null) {
            $this->log("ブロック {$definition['block']} が無いためフィールド {$name} の追加をスキップします");
            return;
        }
        $blockInstance = Vtiger_Block::getInstance($blockId, $module);

        $field = new Vtiger_Field();
        $field->name          = $name;
        $field->label         = $definition['label'];
        $field->table         = 'vtiger_notes';
        $field->column        = $name;
        $field->columntype    = $definition['columntype'];
        $field->uitype        = $definition['uitype'];
        $field->typeofdata    = $definition['typeofdata'];
        $field->generatedtype = 2;
        $field->presence      = 2;
        $field->displaytype   = $definition['displaytype'];
        $field->readonly      = $definition['readonly'];
        $field->masseditable  = $definition['masseditable'];
        $field->quickcreate   = 2;
        if (isset($definition['defaultvalue'])) {
            $field->defaultvalue = $definition['defaultvalue'];
        }
        if (isset($definition['sequence'])) {
            $field->sequence = $definition['sequence'];
        }
        $blockInstance->addField($field);

        if (!empty($definition['picklist'])) {
            $field->setPicklistValues($definition['picklist']);
        }
        $this->log("フィールド {$name} を追加しました");
    }

    /**
     * ブロックIDを取得する（存在しない場合は null）
     */
    private function findBlockId($module, $label) {
        $result = $this->db->pquery(
            'SELECT blockid FROM vtiger_blocks WHERE tabid = ? AND blocklabel = ?',
            array($module->id, $label)
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return (int) $this->db->query_result($result, 0, 'blockid');
    }

    /**
     * 項目IDを取得する（存在しない場合は null）
     */
    private function findFieldId($module, $fieldName) {
        $result = $this->db->pquery(
            'SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?',
            array($module->id, $fieldName)
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return (int) $this->db->query_result($result, 0, 'fieldid');
    }

    /**
     * カラムの現在の型を取得する（存在しない場合は null）
     */
    private function getColumnType($table, $column) {
        $result = $this->db->pquery(
            "SELECT COLUMN_TYPE AS ctype FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            array($table, $column)
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return $this->db->query_result($result, 0, 'ctype');
    }
}
