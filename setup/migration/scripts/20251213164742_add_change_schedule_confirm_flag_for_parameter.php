<?php
/**
 * マイグレーション: add_change_schedule_confirm_flag_for_parameter
 * 生成日時: 20251213164742
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
require_once('includes/Loader.php');
require_once('includes/runtime/Globals.php');
require_once('includes/runtime/LanguageHandler.php');

require_once('modules/Users/Users.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/Record.php');
require_once('modules/Users/models/Record.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');
require_once('modules/Settings/Parameters/models/Record.php');

class Migration20251213164742_AddChangeScheduleConfirmFlagForParameter extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        $record = Settings_Parameters_Record_Model::getInstanceByKey("USER_LOCK_COUNT");
        $record->set("key", "SHOW_SCHEDULE_CONFIRM_FLAG");
        $record->set("value", "false");
        $record->set("description", vtranslate('LBL_SETUP_PARAMETER_MESSAGE_SHOW_SCHEDULE_CONFIRM_FLAG', 'Vtiger'));
        $record->save();
    }
}