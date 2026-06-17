<?php
/**
 * 電子帳簿保存法対応: Documentsモジュールに電帳法フィールドをVtiger_Fieldとして登録
 *
 * vtiger_field に登録し、ブロック・ピックリスト・表示順を設定する。
 * vtlib の Vtiger_Field API を使用して正規の方法で追加する。
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

class Migration20260616125641_AddComplianceColumnsToNotes extends FRMigrationClass {

    public function process() {
        global $current_user;
        $current_user = Users::getActiveAdminUser();

        $module = Vtiger_Module::getInstance('Documents');

        // 電子帳簿保存法ブロックを追加
        $complianceBlock = $this->addComplianceBlock($module);

        // スキャナ保存ブロックを追加
        $scannerBlock = $this->addScannerBlock($module);

        // 適合管理ブロックを追加
        $complianceStatusBlock = $this->addComplianceStatusBlock($module);

        // 電帳法フィールド登録
        $this->addComplianceFields($module, $complianceBlock);

        // スキャナ保存フィールド登録
        $this->addScannerFields($module, $scannerBlock);

        // 適合管理フィールド登録
        $this->addComplianceStatusFields($module, $complianceStatusBlock);

        // ファイル真正性フィールド（既存のファイル情報ブロックに追加）
        $fileInfoBlock = Vtiger_Block::getInstance('LBL_FILE_INFORMATION', $module);
        $this->addFileIntegrityFields($module, $fileInfoBlock);

        $this->log("電帳法フィールドを Vtiger_Field として登録しました");
    }

    /**
     * 電子帳簿保存法ブロック
     */
    private function addComplianceBlock($module) {
        $block = new Vtiger_Block();
        $block->label = 'LBL_COMPLIANCE_SECTION';
        $block->sequence = 4;
        $block->iscustom = 1;
        $module->addBlock($block);
        $this->log("ブロック LBL_COMPLIANCE_SECTION を追加しました");
        return $block;
    }

    /**
     * スキャナ保存ブロック
     */
    private function addScannerBlock($module) {
        $block = new Vtiger_Block();
        $block->label = 'LBL_SCANNER_SECTION';
        $block->sequence = 5;
        $block->iscustom = 1;
        $module->addBlock($block);
        $this->log("ブロック LBL_SCANNER_SECTION を追加しました");
        return $block;
    }

    /**
     * 適合管理ブロック
     */
    private function addComplianceStatusBlock($module) {
        $block = new Vtiger_Block();
        $block->label = 'LBL_COMPLIANCE_STATUS_SECTION';
        $block->sequence = 6;
        $block->iscustom = 1;
        $module->addBlock($block);
        $this->log("ブロック LBL_COMPLIANCE_STATUS_SECTION を追加しました");
        return $block;
    }

    /**
     * 電帳法基本フィールド（書類区分・保存区分）
     */
    private function addComplianceFields($module, $blockInstance) {
        // 書類区分（ピックリスト uitype=16: ロール非依存）
        $field = new Vtiger_Field();
        $field->name        = 'document_category';
        $field->label       = 'LBL_DOCUMENT_CATEGORY';
        $field->table       = 'vtiger_notes';
        $field->column      = 'document_category';
        $field->columntype  = 'VARCHAR(50)';
        $field->uitype      = 16;
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 1;
        $blockInstance->addField($field);
        $field->setPicklistValues(array(
            'invoice', 'receipt', 'contract', 'estimate', 'order', 'delivery', 'other'
        ));
        $this->log("フィールド document_category を追加しました");

        // 保存区分（ピックリスト uitype=16: ロール非依存）
        $field = new Vtiger_Field();
        $field->name        = 'preservation_type';
        $field->label       = 'LBL_PRESERVATION_TYPE';
        $field->table       = 'vtiger_notes';
        $field->column      = 'preservation_type';
        $field->columntype  = 'VARCHAR(30)';
        $field->uitype      = 16;
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 2;
        $blockInstance->addField($field);
        $field->setPicklistValues(array(
            'electronic_transaction', 'scanner'
        ));
        $this->log("フィールド preservation_type を追加しました");
    }

    /**
     * スキャナ保存フィールド
     */
    private function addScannerFields($module, $blockInstance) {
        // 受領日
        $field = new Vtiger_Field();
        $field->name        = 'receipt_date';
        $field->label       = 'LBL_RECEIPT_DATE';
        $field->table       = 'vtiger_notes';
        $field->column      = 'receipt_date';
        $field->columntype  = 'DATE';
        $field->uitype      = 5; // Date
        $field->typeofdata  = 'D~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 1;
        $blockInstance->addField($field);
        $this->log("フィールド receipt_date を追加しました");

        // 入力期限
        $field = new Vtiger_Field();
        $field->name        = 'input_deadline';
        $field->label       = 'LBL_INPUT_DEADLINE';
        $field->table       = 'vtiger_notes';
        $field->column      = 'input_deadline';
        $field->columntype  = 'DATE';
        $field->uitype      = 5; // Date
        $field->typeofdata  = 'D~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用（自動計算）
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $field->sequence    = 2;
        $blockInstance->addField($field);
        $this->log("フィールド input_deadline を追加しました");

        // 入力期限状態
        $field = new Vtiger_Field();
        $field->name        = 'input_deadline_status';
        $field->label       = 'LBL_INPUT_DEADLINE_STATUS';
        $field->table       = 'vtiger_notes';
        $field->column      = 'input_deadline_status';
        $field->columntype  = 'VARCHAR(20)';
        $field->uitype      = 16;
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用（自動計算）
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $field->defaultvalue = 'within';
        $field->sequence    = 3;
        $blockInstance->addField($field);
        $field->setPicklistValues(array('within', 'warning', 'overdue'));
        $this->log("フィールド input_deadline_status を追加しました");

        // スキャン解像度
        $field = new Vtiger_Field();
        $field->name        = 'scan_resolution_dpi';
        $field->label       = 'LBL_SCAN_RESOLUTION';
        $field->table       = 'vtiger_notes';
        $field->column      = 'scan_resolution_dpi';
        $field->columntype  = 'INT';
        $field->uitype      = 7; // Integer
        $field->typeofdata  = 'I~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 4;
        $blockInstance->addField($field);
        $this->log("フィールド scan_resolution_dpi を追加しました");

        // カラー区分（ピックリスト uitype=16）
        $field = new Vtiger_Field();
        $field->name        = 'scan_color_type';
        $field->label       = 'LBL_COLOR_TYPE';
        $field->table       = 'vtiger_notes';
        $field->column      = 'scan_color_type';
        $field->columntype  = 'VARCHAR(10)';
        $field->uitype      = 16;
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 5;
        $blockInstance->addField($field);
        $field->setPicklistValues(array('color', 'grayscale'));
        $this->log("フィールド scan_color_type を追加しました");

        // 原本サイズ（ピックリスト uitype=16）
        $field = new Vtiger_Field();
        $field->name        = 'original_paper_size';
        $field->label       = 'LBL_ORIGINAL_PAPER_SIZE';
        $field->table       = 'vtiger_notes';
        $field->column      = 'original_paper_size';
        $field->columntype  = 'VARCHAR(10)';
        $field->uitype      = 16;
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 6;
        $blockInstance->addField($field);
        $field->setPicklistValues(array('A3', 'A4', 'A5', 'B4', 'B5', 'other'));
        $this->log("フィールド original_paper_size を追加しました");

        // スキャン実施者（ユーザー参照 uitype=53 相当だが、単純なintで記録）
        $field = new Vtiger_Field();
        $field->name        = 'scanned_by';
        $field->label       = 'LBL_SCANNED_BY';
        $field->table       = 'vtiger_notes';
        $field->column      = 'scanned_by';
        $field->columntype  = 'INT';
        $field->uitype      = 7;
        $field->typeofdata  = 'I~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 7;
        $blockInstance->addField($field);
        $this->log("フィールド scanned_by を追加しました");

        // スキャン日時
        $field = new Vtiger_Field();
        $field->name        = 'scanned_at';
        $field->label       = 'LBL_SCANNED_AT';
        $field->table       = 'vtiger_notes';
        $field->column      = 'scanned_at';
        $field->columntype  = 'DATETIME';
        $field->uitype      = 70; // DateTime
        $field->typeofdata  = 'DT~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 1;
        $field->readonly    = 1;
        $field->masseditable = 1;
        $field->quickcreate = 2;
        $field->sequence    = 8;
        $blockInstance->addField($field);
        $this->log("フィールド scanned_at を追加しました");
    }

    /**
     * 適合管理フィールド
     */
    private function addComplianceStatusFields($module, $blockInstance) {
        // 適合状態（ピックリスト uitype=16）
        $field = new Vtiger_Field();
        $field->name        = 'compliance_status';
        $field->label       = 'LBL_COMPLIANCE_STATUS';
        $field->table       = 'vtiger_notes';
        $field->column      = 'compliance_status';
        $field->columntype  = 'VARCHAR(20)';
        $field->uitype      = 16;
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用（システム判定）
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $field->sequence    = 1;
        $blockInstance->addField($field);
        $field->setPicklistValues(array('compliant', 'non_compliant'));
        $this->log("フィールド compliance_status を追加しました");

        // 最終適合チェック日時
        $field = new Vtiger_Field();
        $field->name        = 'compliance_checked_at';
        $field->label       = 'LBL_COMPLIANCE_CHECKED_AT';
        $field->table       = 'vtiger_notes';
        $field->column      = 'compliance_checked_at';
        $field->columntype  = 'DATETIME';
        $field->uitype      = 70;
        $field->typeofdata  = 'DT~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $field->sequence    = 2;
        $blockInstance->addField($field);
        $this->log("フィールド compliance_checked_at を追加しました");

        // 適合チェック備考
        $field = new Vtiger_Field();
        $field->name        = 'compliance_notes';
        $field->label       = 'LBL_COMPLIANCE_NOTES';
        $field->table       = 'vtiger_notes';
        $field->column      = 'compliance_notes';
        $field->columntype  = 'TEXT';
        $field->uitype      = 19; // TextArea
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $field->sequence    = 3;
        $blockInstance->addField($field);
        $this->log("フィールド compliance_notes を追加しました");
    }

    /**
     * ファイル真正性フィールド（既存ファイル情報ブロックに追加）
     */
    private function addFileIntegrityFields($module, $blockInstance) {
        // ハッシュアルゴリズム
        $field = new Vtiger_Field();
        $field->name        = 'file_hash_algorithm';
        $field->label       = 'LBL_FILE_HASH_ALGORITHM';
        $field->table       = 'vtiger_notes';
        $field->column      = 'file_hash_algorithm';
        $field->columntype  = 'VARCHAR(10)';
        $field->uitype      = 1; // Text
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用（システム自動設定）
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $field->defaultvalue = 'SHA-256';
        $blockInstance->addField($field);
        $this->log("フィールド file_hash_algorithm を追加しました");

        // ファイルハッシュ値
        $field = new Vtiger_Field();
        $field->name        = 'file_hash';
        $field->label       = 'LBL_FILE_HASH';
        $field->table       = 'vtiger_notes';
        $field->column      = 'file_hash';
        $field->columntype  = 'VARCHAR(64)';
        $field->uitype      = 1; // Text
        $field->typeofdata  = 'V~O';
        $field->generatedtype = 2;
        $field->presence    = 2;
        $field->displaytype = 2; // 読み取り専用（システム自動計算）
        $field->readonly    = 0;
        $field->masseditable = 2;
        $field->quickcreate = 2;
        $blockInstance->addField($field);
        $this->log("フィールド file_hash を追加しました");
    }
}
