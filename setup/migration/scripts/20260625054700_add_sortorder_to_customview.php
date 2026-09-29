<?php
/**
 * マイグレーション: add_sortorder_to_customview
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260625054700_AddSortorderToCustomview extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     */
    public function process() {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SHOW COLUMNS FROM vtiger_customview LIKE 'sortorder'", array());
        if ($db->num_rows($result) == 0) {
            $db->pquery("ALTER TABLE vtiger_customview ADD sortorder varchar(250) DEFAULT 'ASC'", array());
            $this->log("sortorderカラムをvtiger_customviewに追加しました。");
        }
        $this->log("マイグレーション add_sortorder_to_customview が正常に完了しました");
    }
}
