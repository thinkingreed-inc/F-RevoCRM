<?php
/**
 * マイグレーション: update_vtiger_tab_name_length
 * 生成日時: 20251111191427
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251111191427_UpdateVtigerTabNameLength extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        global $current_user, $adb;
        $db = PearDatabase::getInstance();

        $sql = "ALTER TABLE vtiger_tab MODIFY COLUMN name VARCHAR(50) NOT NULL";
         $db->pquery($sql, array());

        $this->log("マイグレーション update_vtiger_tab_name_length が正常に完了しました");
    }
}