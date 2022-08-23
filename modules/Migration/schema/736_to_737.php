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

    // PDFテンプレートに大きなサイズの画像を張り付ける場合、bodyが途切れるのでtextからlongtextへ変更する
    $db->query('ALTER TABLE vtiger_pdftemplates MODIFY body longtext;');
    $db->query("ALTER TABLE vtiger_notes MODIFY filetype varchar(255);");
}