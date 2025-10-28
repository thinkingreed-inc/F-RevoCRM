<?php
/**
 * マイグレーション: add_import_system_variable
 * 生成日時: 20251028162441
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
include_once('include/utils/CommonUtils.php');
include_once('includes/runtime/LanguageHandler.php');
include_once('includes/runtime/Globals.php');

class Migration20251028162441_AddImportSystemVariable extends FRMigrationClass {
    
    /**
     * ユーザーごとの最大履歴件数をシステム変数として保持
     * 
     */
    public function process() {
        $record = Settings_Parameters_Record_Model::getInstanceByKey("IMPORT_MAX_HISTORY_COUNT");
        $record->set("key", "IMPORT_MAX_HISTORY_COUNT");
        $record->set("value", "10");
        $record->set("description", vtranslate('LBL_SETUP_PARAMETER_MESSAGE_IMPORT_MAX_HISTORY_COUNT', 'Import'));
        $record->save();

        $this->log("マイグレーション add_import_system_variable が正常に完了しました");
    }
}