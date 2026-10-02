<<<<<<< HEAD
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

    //"Canceled"が"Canceled[スペース]"で登録されていた箇所の修正
    $db->query("UPDATE vtiger_projecttaskstatus SET projecttaskstatus = 'Canceled' WHERE projecttaskstatus = 'Canceled '");
    //activity_reminder_popupの高速化
    $db->query("CREATE INDEX reminder_popup ON vtiger_activity_reminder_popup(status, date_start, time_start);");

	//共有カレンダー情報のユーザー表示をnullから0に変更
	$db->query("INSERT INTO vtiger_shareduserinfo(userid,shareduserid,visible)
				SELECT u1.id AS id, u2.id AS shareduserid, 1 AS visible
				FROM vtiger_users u1
				LEFT OUTER JOIN vtiger_users u2 ON u2.id != u1.id
				LEFT OUTER JOIN vtiger_shareduserinfo s ON s.shareduserid = u2.id AND s.userid = u1.id
				WHERE u2.status = 'Active' AND u2.id IS NOT NULL AND (s.userid IS NULL OR s.userid <> '' ) AND s.visible IS NULL
				ORDER BY u1.id ASC, u2.id ASC;");
}
