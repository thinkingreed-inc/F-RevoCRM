<?php
/*+********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*********************************************************************************/

if (defined('VTIGER_UPGRADE')) {
    global $current_user, $adb;
    $db = PearDatabase::getInstance();

    // Check if IFrameWidget link already exists
    $result = $db->pquery("SELECT linkid FROM vtiger_links WHERE linklabel = ? AND linktype = ?", array('IFrame Widget', 'DASHBOARDWIDGET'));
    
    if ($db->num_rows($result) == 0) {
        // Get next available linkid
        $linkResult = $db->pquery("SELECT MAX(linkid) as max_id FROM vtiger_links", array());
        $maxId = $db->query_result($linkResult, 0, 'max_id');
        $newLinkId = $maxId + 1;
        
        // Add IFrame Widget to dashboard widgets
        $db->pquery("INSERT INTO vtiger_links(linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence, handler_path, handler_class, handler, parent_link) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($newLinkId, 3, 'DASHBOARDWIDGET', 'IFrame Widget', 'index.php?module=Home&view=ShowWidget&name=IFrameWidget', '', 0, NULL, NULL, NULL, NULL));
    }
}