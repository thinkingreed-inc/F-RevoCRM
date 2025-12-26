<?php
/**
 * マイグレーション: add_has_initial_login
 * 生成日時: 20251224173134
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251224173134_AddHasInitialLogin extends FRMigrationClass {
    /**
     * vtiger_users に初回ログイン判定用フラグを追加し、
     * 既存のログイン履歴があるユーザーを has_initial_login = 1 に更新する
     */
    public function process() {
        $db = PearDatabase::getInstance();
        $sql = "ALTER TABLE vtiger_users ADD COLUMN has_initial_login  tinyint(1) DEFAULT 0";
        $db->query($sql);
        
        $sql = "UPDATE vtiger_users u INNER JOIN vtiger_loginhistory lh ON lh.user_name = u.user_name SET u.has_initial_login = 1";
        $db->query($sql);
        
        $this->log("マイグレーション add_has_initial_login が正常に完了しました");
    }
}