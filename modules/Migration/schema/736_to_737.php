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
    global $adb;

    // 過去に通知されなかったリマインダーのステータスを1(通知済み)に変更する
    $query = "UPDATE vtiger_activity_reminder_popup SET status = 1 WHERE concat(cast(date_start as char),' ',cast(time_start as char))<= ?";
    $adb->pquery($query, array(date('Y-m-d H:i:s', strtotime('today'))));
}