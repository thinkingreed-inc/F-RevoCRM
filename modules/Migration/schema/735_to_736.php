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
    //しかし過去に作成したものはそのままであるため、バージョンアップ時に付与する
    $result = $adb->pquery('SELECT f.tablename, f.columnname
        FROM vtiger_fieldmodulerel AS fmrel
        LEFT JOIN vtiger_field AS f
        ON fmrel.fieldid = f.fieldid
        WHERE f.tablename != "NULL"
        ORDER BY f.tablename, f.columnname;');
    $rows = $adb->num_rows($result);
    for ($i = 0; $i < $rows; $i++) {
        $tablename = $adb->query_result($result, $i, 'tablename');
        $columnname = $adb->query_result($result, $i, 'columnname');
        if($tablename != $tablename_old || $columnname != $columnname_old){ //同じクエリが連続して飛ぶケースを除外する
            $result_index = $adb->pquery('SHOW INDEX FROM '.$tablename.' WHERE Column_name="'.$columnname.'"');
            $rows_index = $adb->num_rows($result_index);
            if($rows_index == 0){
                $adb->pquery("CREATE INDEX ".$columnname." ON ".$tablename."(".$columnname.")");
            }
        }
        $tablename_old = $tablename;
        $columnname_old = $columnname;
    }
}