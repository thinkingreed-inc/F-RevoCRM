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

    //related_tabidが16(Events)だとレコード詳細画面が正常に表示されないので9(Calendar)へ変更
    $db->query("UPDATE vtiger_relatedlists SET related_tabid = 9 WHERE related_tabid = 16");
}