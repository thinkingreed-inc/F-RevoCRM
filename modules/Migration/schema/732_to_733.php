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
}
