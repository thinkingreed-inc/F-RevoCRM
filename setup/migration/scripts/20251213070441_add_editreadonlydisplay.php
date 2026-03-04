<?php
/**
 * マイグレーション: add_editreadonlydisplay
 * 生成日時: 20251213070441
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251213070441_AddEditreadonlydisplay extends FRMigrationClass {
    
    /**
     * vtiger_tab テーブルに editreadonlydisplay カラムを追加するマイグレーション
     */
    public function process() {
        global $adb;

        $query = "ALTER TABLE vtiger_tab ADD COLUMN editreadonlydisplay boolean DEFAULT false";
        $adb->query($query);
        
        $this->log("マイグレーション add_editreadonlydisplay が正常に完了しました");
    }
}