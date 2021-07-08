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

    // プロジェクトマイルストーンをプロジェクトから作るときに、自動でプロジェクトが含まれるように修正
    // projectidのfieldid
    $f_result = $db->query('select f.fieldid from vtiger_field f left join vtiger_tab t on f.tabid = t.tabid where t.name = "ProjectMilestone" and f.fieldname = "projectid" limit 1');
    $f_count = $db->num_rows($f_result);
    // relation_id
    $r_result = $db->query('select relation_id from vtiger_relatedlists where name = "get_dependents_list" and label = "Project Milestones" limit 1');
    $r_count = $db->num_rows($r_result);
    if($f_count > 0 && $r_count > 0){
        $fieldid = $db->query_result($f_result, 0, 'fieldid');
        $relation_id = $db->query_result($r_result, 0, 'relation_id');
        $db->pquery('update vtiger_relatedlists set relationfieldid = ? where relation_id = ?', array($fieldid, $relation_id));
    }
}
