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

    // 使用単位項目を追加
    $modulearray = array("Quotes","PurchaseOrder","SalesOrder","Invoice");
    $itemFieldsName = array('usageunit');
    $itemFieldsLabel = array('使用単位');
    $itemFieldsTypeOfData = array('V~O~LE~200');
    $itemFieldsDisplayType = array('1');
    $itemFieldsColumnType = array('varchar(200)');
    foreach ($modulearray as $key => $modulename) {
        $moduleInstance = Vtiger_Module::getInstance($modulename);
        $blockInstance = Vtiger_Block::getInstance('LBL_ITEM_DETAILS', $moduleInstance);
        for ($j=0;$j<count($itemFieldsName);$j++) {
            //追加
            $field = new Vtiger_Field();
            $field->name = $itemFieldsName[$j];
            $field->label = $itemFieldsLabel[$j];
            $field->column = $itemFieldsName[$j];
            $field->table = 'vtiger_inventoryproductrel';
            $field->uitype = $itemFieldsDisplayType[$j];
            $field->typeofdata = $itemFieldsTypeOfData[$j];
            $field->readonly = '0';
            $field->displaytype = '5';
            $field->masseditable = '0';
            $field->columntype = '';
            $blockInstance->addField($field);
        }
    }
}
