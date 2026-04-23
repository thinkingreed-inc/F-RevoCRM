<?php
/**
 * マイグレーション: fix_url_fields_to_text
 * 生成日時: 20260423062801
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260423062801_FixUrlFieldsToText extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        // vtiger_project.projecturl
        $this->log("Updating vtiger_project.projecturl to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_project MODIFY COLUMN projecturl TEXT");
        
        // vtiger_service.website
        $this->log("Updating vtiger_service.website to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_service MODIFY COLUMN website TEXT");
        
        // vtiger_products.website
        $this->log("Updating vtiger_products.website to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_products MODIFY COLUMN website TEXT");
        
        // vtiger_webforms.returnurl
        $this->log("Updating vtiger_webforms.returnurl to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_webforms MODIFY COLUMN returnurl TEXT");
        
        // vtiger_account.website
        $this->log("Updating vtiger_account.website to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_account MODIFY COLUMN website TEXT");
        
        // vtiger_leadsubdetails.website
        $this->log("Updating vtiger_leadsubdetails.website to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_leadsubdetails MODIFY COLUMN website TEXT");
        
        // vtiger_links.linkurl
        $this->log("Updating vtiger_links.linkurl to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_links MODIFY COLUMN linkurl TEXT");
        
        // vtiger_organizationdetails.website
        $this->log("Updating vtiger_organizationdetails.website to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_organizationdetails MODIFY COLUMN website TEXT");
        
        // vtiger_vendor.website
        $this->log("Updating vtiger_vendor.website to TEXT");
        $this->db->pquery("ALTER TABLE vtiger_vendor MODIFY COLUMN website TEXT");
        
        // // vtiger_pbxmanager.recordingurl
        // $this->log("Updating vtiger_pbxmanager.recordingurl to TEXT");
        // $this->db->pquery("ALTER TABLE vtiger_pbxmanager MODIFY COLUMN recordingurl TEXT");

        // vtiger_fieldテーブルのmaximumlengthを更新
        // UIタイプ 17 (URL) のフィールドの最大長を0（無制限）に設定
        $this->log("Updating vtiger_field maximumlength for URL fields (uitype 17)");
        $this->db->pquery("UPDATE vtiger_field SET maximumlength = 0 WHERE uitype = 17");

        $this->log($message);
        
        $this->log("マイグレーション fix_url_fields_to_text が正常に完了しました");
    }
}