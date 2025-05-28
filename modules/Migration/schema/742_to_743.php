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
    $db->query("ALTER TABLE vtiger_users ADD COLUMN confirmonmobileeditother tinyint(1) NOT NULL DEFAULT 0;");
    $moduleInstance = Vtiger_Module::getInstance('Users');
    $blockInstance = Vtiger_Block::getInstance('LBL_CALENDAR_SETTINGS', $moduleInstance);
    if ($blockInstance) {
        $fieldInstance = Vtiger_Field::getInstance('confirmonmobileeditother', $moduleInstance);
        if (!$fieldInstance) {
            $fieldInstance = new Vtiger_Field();
            $fieldInstance->name = 'confirmonmobileeditother';
            $fieldInstance->column = 'confirmonmobileeditother';
            $fieldInstance->label = 'LBL_WARN_ON_EDIT_OTHER_MOBILE';
            $fieldInstance->table = 'vtiger_users';
            $fieldInstance->columntype = 'TINYINT(1)';
            $fieldInstance->defaultvalue = '0';
            $fieldInstance->uitype = '56'; // Checkbox
            $fieldInstance->typeofdata = 'C~O'; // Checkbox, optional
            $fieldInstance->presence = 0;
    
            $blockInstance->addField($fieldInstance);
            echo "<br> Mobile edit warning field added.<br>";
        }
    }
}