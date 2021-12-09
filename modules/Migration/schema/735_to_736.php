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
    // 契約モジュールにprimary keyを追加
    $db->pquery("alter table vtiger_servicecontracts add primary key (servicecontractsid)", array());

    //uitype10にindexが付与される修正を加えた
    //しかし、過去に作成したものはそのままであるためバージョンアップ時に付与する
    $result = $adb->pquery("SELECT tablename,columnname FROM vtiger_field WHERE uitype=10");
    $rows = $adb->num_rows($result);
    for ($i = 0; $i < $rows; $i++) {
        $tablename = $adb->query_result($result, $i, 'tablename');
        $columnname = $adb->query_result($result, $i, 'columnname');
        
        $result_index = $adb->pquery('SHOW INDEX FROM '.$tablename.' WHERE Column_name="'.$columnname.'"');
        $rows_index = $adb->num_rows($result_index);
        if($rows_index == 0){
            $adb->pquery("CREATE INDEX ".$columnname." ON ".$tablename."(".$columnname.")");
        }
    }
}