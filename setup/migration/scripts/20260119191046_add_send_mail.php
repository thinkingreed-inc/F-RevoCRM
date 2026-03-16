<?php
/**
 * マイグレーション: add_send_mail_field_to_event
 * 生成日時: 20260119191046
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
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260119191046_AddSendMail extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
     public function process() {
        global $current_user;
        $current_user = Users::getActiveAdminUser();
        $this->addSendMailBlock();
        $this->log("マイグレーション add_send_mail が正常に完了しました");
    }
    private function addSendMailBlock() {
        $moduleModel = Vtiger_Module_Model::getInstance('Events');

        // 基本情報 ブロック取得
        $descriptionBlock = Vtiger_Block_Model::getInstance('LBL_EVENT_INFORMATION', $moduleModel);
        $descriptionBlockId = $descriptionBlock->id;

        // Block / Module インスタンス取得
        $module = Vtiger_Module::getInstance('Events');
        $blockInstance = Vtiger_Block::getInstance($descriptionBlockId, $module);

        /**************************************************************
         * send_mail(送信メール制御) 
         **************************************************************/
        $field = new Vtiger_Field();
        $field->name        = 'send_mail';
        $field->label       = 'Send Mail';
        $field->table       = 'vtiger_activity';
        $field->column      = 'send_mail';
        $field->columntype  = 'VARCHAR(3)';
        $field->uitype      = 56;
        $field->typeofdata  = 'C~O';
        $field->presence    = 0;
        $field->readonly    = 1;
        $field->masseditable= 1;
        $field->quickcreate = 2;
        $blockInstance->addField($field);
    }

}