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

	// Added column storedname for vtiger_attachments to support reverse mapping.
    $columns = $db->getColumnNames('vtiger_attachments');
    $columnName = "storedname";
    if(!in_array($columnName,$columns)) {
        $db->pquery('ALTER TABLE vtiger_attachments ADD COLUMN storedname varchar(255) NULL AFTER path', array());
    }
}
