<?php
/**
 * マイグレーション: update_vtiger_tab_name_length
 * 生成日時: 20251111191427
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251111191427_UpdateModuleNameFieldLength extends FRMigrationClass {
    
    public function process() {
        $db = PearDatabase::getInstance();

        // vtiger_tab.name
        $sql = "ALTER TABLE vtiger_tab MODIFY COLUMN name VARCHAR(50) NOT NULL";
        $db->pquery($sql, array());

        // vtiger_tracker.module_name
        $sql = "ALTER TABLE vtiger_tracker MODIFY COLUMN module_name VARCHAR(50) NOT NULL";
        $db->pquery($sql, array());

        // vtiger_ws_referencetype.type
        $sql = "ALTER TABLE vtiger_ws_referencetype MODIFY COLUMN type VARCHAR(50) NOT NULL";
        $db->pquery($sql, array());

        // vtiger_ws_entity.name
        $sql = "ALTER TABLE vtiger_ws_entity MODIFY COLUMN name VARCHAR(50) NOT NULL";
        $db->pquery($sql, array());

        // vtiger_ws_entity_referencetype.type
        $sql = "ALTER TABLE vtiger_ws_entity_referencetype MODIFY COLUMN type VARCHAR(50) NOT NULL";
        $db->pquery($sql, array());

        // vtiger_customview.entitytype
        $sql = "ALTER TABLE vtiger_customview MODIFY COLUMN entitytype VARCHAR(50) NOT NULL";
        $db->pquery($sql, array());

        $this->log("マイグレーション update_field_length_to_50 が正常に完了しました");
    }

}