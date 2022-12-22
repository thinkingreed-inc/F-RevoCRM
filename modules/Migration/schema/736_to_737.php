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
    
    // 過去に通知されなかったリマインダーのステータスを1(通知済み)に変更する
    $query = "UPDATE vtiger_activity_reminder_popup SET status = 1 WHERE concat(cast(date_start as char),' ',cast(time_start as char))<= ?";
    $adb->pquery($query, array(date('Y-m-d H:i:s', strtotime('today'))));
    
    //チケットから資産・レンタルを参照できるようにする
    $moduleModel = Vtiger_Module_Model::getInstance('HelpDesk');
    if($moduleModel){
        $moduleModel->setRelatedList(Vtiger_Module_Model::getInstance('Assets'), 'Assets', 'ADD,SELECT', 'get_related_list');
    }

    // uitype10の「カレンダー」および「TODO」を非表示に変更する
    $db->query('UPDATE vtiger_field vf
        LEFT JOIN vtiger_relatedlists vr 
        ON vf.fieldid = vr.relationfieldid
        SET vf.presence = 1
        WHERE vf.presence = 2
        AND vf.uitype = 10
        AND vr.tabid in (9,16);
    ');
}