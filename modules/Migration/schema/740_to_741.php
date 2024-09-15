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

    // ドキュメントにてファイルのURLが途切れてしまい所望のページに遷移できないバグが発生していた
    // filenameのデータ型をvarchar(200)からtextに変更する
    $db->pquery("ALTER TABLE vtiger_notes MODIFY COLUMN filename TEXT", array());

    //ログイン履歴のテーブル変更
    include_once 'setup/scripts/76_Update_LoginHistory.php';

    //個人カレンダーの設定テーブル変更
    include_once 'setup/scripts/77_Update_CalendarUserActivityTypes.php';

    // 'vtiger_activity' テーブルに 'smcreatorid' フィールドを追加
    include_once 'setup/scripts/78_Add_smcreatorid.php';
}