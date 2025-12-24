<?php
/**
 * マイグレーション: add_column_to_import_queue
 * 生成日時: 20250930102426
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20250930102426_AddColumnToImportQueue extends FRMigrationClass {
    
    /**
     * vtiger_import_queueにインポート開始時刻、終了時刻を追加
     * 
     */
    public function process() {
        $sql = "ALTER TABLE vtiger_import_queue ADD COLUMN time_start datetime";
        $this->db->query($sql);

        $sql = "ALTER TABLE vtiger_import_queue ADD COLUMN time_end datetime";
        $this->db->query($sql);

        $this->log("マイグレーション add_column_to_import_queue が正常に完了しました");
    }
}