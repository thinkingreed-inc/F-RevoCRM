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

    // #1030 ユーザーが活動を作成した際, 他ユーザーの活動と時間的に重複している場合に確認ダイアログを表示する.
    // vtiger_calendar_overlapsテーブルを参照することで, ダイアログに表示すべきユーザーを判断する.
    include_once 'setup/scripts/Add_vtiger_calendar_overlaps.php';
}