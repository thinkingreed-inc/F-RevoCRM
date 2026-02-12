<?php
/**
 * マイグレーション: CALENDAR_REMEMBER_FEED_SELECTION
 * 生成日時: 20260116155919
 */
include_once 'vtlib/Vtiger/Module.php';

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260116155919_CalendarRememberFeedSelection extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        $record = Settings_Parameters_Record_Model::getInstanceByKey("CALENDAR_REMEMBER_FEED_SELECTION");
        $record->set("key", "CALENDAR_REMEMBER_FEED_SELECTION");
        $record->set("value", "false"); 
        $record->set("description", vtranslate('LBL_SETUP_PARAMETER_MESSAGE_CALENDAR_REMEMBER_FEED_SELECTION', 'Calendar'));
        $record->save();
        $this->log("マイグレーション CALENDAR_REMEMBER_FEED_SELECTION が正常に完了しました");
    }
}