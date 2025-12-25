<?php
/**
 * マイグレーション: alter_user_lock_table_allow_null_lock_time
 * 生成日時: 20251020154523
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251020154523_AlterUserLockTableAllowNullLockTime extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        // vtiger_user_lockテーブルのlock_timeカラムをNULL許可に変更
        $sql = "ALTER TABLE `vtiger_user_lock`
                MODIFY COLUMN `lock_time` datetime NULL DEFAULT NULL";
        $this->db->pquery($sql, array());

        $this->log("vtiger_user_lockテーブルのlock_timeカラムをNULL許可に変更しました");

        // 既存のロックされていないレコードのlock_timeをNULLに設定
        $sql = "UPDATE `vtiger_user_lock`
                SET `lock_time` = NULL
                WHERE `signature_count` = 0";
        $this->db->pquery($sql, array());

        $this->log("既存レコードのlock_timeを適切に設定しました");
        $this->log("マイグレーション alter_user_lock_table_allow_null_lock_time が正常に完了しました");
    }
}