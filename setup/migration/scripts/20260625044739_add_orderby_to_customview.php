<?php
/**
 * マイグレーション: add_orderby_to_customview
 * 生成日時: 20260625044739
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260625044739_AddOrderbyToCustomview extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        $sql = "ALTER TABLE vtiger_customview ADD orderby varchar(250)";
        $this->db->pquery($sql, array());
        
        $this->log("マイグレーション add_orderby_to_customview が正常に完了しました");
    }
}