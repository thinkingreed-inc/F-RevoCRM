<?php
/**
 * マイグレーション: add_iframe_widget
 * 生成日時: 20251014141127
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251014141127_AddIframeWidget extends FRMigrationClass {

    public function process() {
        global $current_user, $adb;
        $db = PearDatabase::getInstance();

        // Check if IFrameWidget link already exists
        $result = $db->pquery("SELECT linkid FROM vtiger_links WHERE linklabel = ? AND linktype = ?", array('IFrame Widget', 'DASHBOARDWIDGET'));
        
        if ($db->num_rows($result) == 0) {
            // Get next available linkid
            $linkResult = $db->pquery("SELECT MAX(linkid) as max_id FROM vtiger_links", array());
            $maxId = $linkResult->fields['max_id'];
            $newLinkId = $maxId + 1;
            
            // Add IFrame Widget to dashboard widgets
            $db->pquery("INSERT INTO vtiger_links(linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence, handler_path, handler_class, handler, parent_link) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($newLinkId, 3, 'DASHBOARDWIDGET', 'IFrame Widget', 'index.php?module=Home&view=ShowWidget&name=IFrameWidget', '', 0, NULL, NULL, NULL, NULL));
        }
        
        $this->log("マイグレーション add_iframe_widget が正常に完了しました");
    }
}