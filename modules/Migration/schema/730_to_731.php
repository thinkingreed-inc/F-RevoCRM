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

	$db->query("update vtiger_settings_field set pinned = 0 where name in ('LBL_EXTENSION_STORE','LBL_GOOGLE')");

	// 画像が保存できるように型を変更
	$db->query("alter table vtiger_troubletickets change solution solution longtext;");
	$db->query("alter table vtiger_crmentity change description description longtext;");
	$db->query("alter table vtiger_faq change question question longtext;");
	$db->query("alter table vtiger_faq change answer answer longtext;");

	// 日報モジュールがない場合に追加する
	$result = $db->query("select * from vtiger_tab where name = 'Dailyreports'");
	$count = $db->num_rows($result);
	if($count == 0){
		include_once("setup/scripts/01_Make_Dailyreports.php");
	}
	// 日報のコメントの文字数制限を変更
	$db->query("alter table vtiger_dailyreports change dailyreportscomment dailyreportscomment longtext;");
}
