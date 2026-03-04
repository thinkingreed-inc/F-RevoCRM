<?php
/**
 * マイグレーション: add_primary_key_to_user_lock_table
 * 生成日時: 20251020154809
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251020154809_AddPrimaryKeyToUserLockTable extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        $this->log("vtiger_user_lockテーブルの重複レコードをクリーンアップします");

        // 一時テーブルを作成して、各useridごとに最大のsignature_countとlock_timeを集約
        $sql = "CREATE TEMPORARY TABLE tmp_user_lock AS
                SELECT
                    userid,
                    MAX(signature_count) as signature_count,
                    MAX(lock_time) as lock_time
                FROM vtiger_user_lock
                GROUP BY userid";
        $this->db->pquery($sql, array());
        $this->log("一時テーブルを作成しました");

        // 既存のテーブルをクリア
        $sql = "TRUNCATE TABLE vtiger_user_lock";
        $this->db->pquery($sql, array());
        $this->log("既存テーブルをクリアしました");

        // 集約したデータを戻す
        $sql = "INSERT INTO vtiger_user_lock (userid, signature_count, lock_time)
                SELECT userid, signature_count, lock_time FROM tmp_user_lock";
        $this->db->pquery($sql, array());
        $this->log("クリーンアップされたデータを挿入しました");

        // PRIMARY KEY制約を追加
        $sql = "ALTER TABLE vtiger_user_lock ADD PRIMARY KEY (userid)";
        $this->db->pquery($sql, array());
        $this->log("useridにPRIMARY KEY制約を追加しました");

        $this->log("マイグレーション add_primary_key_to_user_lock_table が正常に完了しました");
    }
}