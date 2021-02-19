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

	//課税計算の手数料額をnullから0円に変更
	$db->query("UPDATE vtiger_inventorycharges SET value = 0 WHERE value IS NULL;");

	// 日報モジュールの提出先のuitypeを10、V~Mに変更する
	$db->query("
		UPDATE vtiger_field, vtiger_tab 
		SET uitype = 10
		WHERE vtiger_tab.tabid = vtiger_field.tabid 
			and vtiger_field.fieldname = 'reports_to_id'
			and vtiger_tab.name = 'Dailyreports'
	");
	// type of dataもV~となるようにする
	// すでに必須から外されているケースを考慮し、適切に更新できるようにする
	$db->query("
		UPDATE vtiger_field, vtiger_tab 
		SET typeofdata = 'V~M'
		WHERE vtiger_tab.tabid = vtiger_field.tabid 
			and vtiger_field.fieldname = 'reports_to_id'
			and vtiger_field.typeofdata LIKE '%~M'
			and vtiger_tab.name = 'Dailyreports'
	");
	$db->query("
		UPDATE vtiger_field, vtiger_tab 
		SET typeofdata = 'V~O'
		WHERE vtiger_tab.tabid = vtiger_field.tabid 
			and vtiger_field.fieldname = 'reports_to_id'
			and vtiger_field.typeofdata LIKE '%~O'
			and vtiger_tab.name = 'Dailyreports'
	");
}
