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

    // 契約からチケットへ関連のラベル名が「サービスリクエスト」であったため「チケット」へ変更する
    $db->query('UPDATE vtiger_relatedlists SET label = "HelpDesk" WHERE tabid = 34 AND related_tabid = 13 AND name = "get_related_list"');
}