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

    // レポートの関連の参照元を格納するカラムの追加
    $db->pquery("alter table vtiger_reportmodules add column join_column text", array());

    // Reports_Record_Model::getRelationTables()にて, vtiger_fieldmodulerelを参照しているため足りないフィールドを追加
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(72,'Contacts','Accounts')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(129,'Campaigns','Products')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(159,'HelpDesk','Products')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(279,'Faq','Products')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(316,'Quotes','Potentials')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(319,'Quotes','Contacts')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(330,'Quotes','Accounts')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(356,'PurchaseOrder','Contacts')", array());
    $db->pquery("INSERT INTO vtiger_fieldmodulerel(fieldid, module, relmodule) VALUES(452,'Invoice','Accounts')", array());

    // システム変数モジュールの追加
    require_once ("setup/scripts/03_Make_Parameters.php");
}