<?php
/**
 * マイグレーション: add_login_type_to_loginhistory
 * 生成日時: 20251106184202
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251106184202_AddLoginTypeToLoginhistory extends FRMigrationClass {
    
    /**
     * ログイン履歴にlogin_type columnを追加する
     */
    public function process() {
        $db = PearDatabase::getInstance();
        // add login_type column to vtiger_loginhistory table
        $sql = "ALTER TABLE vtiger_loginhistory ADD COLUMN login_type VARCHAR(255) NULL";
        $db->query($sql);
        
        $this->log("マイグレーション add_login_type_to_loginhistory が正常に完了しました");
    }
}