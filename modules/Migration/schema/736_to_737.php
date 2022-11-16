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

    //製品の価格情報に項目「軽減税率対象」（チェックボックス）を追加、請求書等で表示できるようにする。
    $columns = $db->getColumnNames('vtiger_products');
    $columnName = "reducedtaxrate";
    if(!in_array($columnName,$columns)) {
        $db->query('ALTER TABLE vtiger_products ADD COLUMN reducedtaxrate int(1) NOT NULL default 0', array());
        $db->query('ALTER TABLE vtiger_inventoryproductrel ADD COLUMN reducedtaxrate int(1) NOT NULL default 0', array());

    }
    
    $module = Vtiger_Module::getInstance('Products');
    if ($module) {
        $blockInstance = Vtiger_Block::getInstance('LBL_PRICING_INFORMATION', $module);
        if ($blockInstance) {
            $newField = Vtiger_Field::getInstance('reducedtaxrate', $module);
            if(!$newField) {
            $fieldInstance = new Vtiger_Field();
            $fieldInstance->name = 'reducedtaxrate';
            $fieldInstance->label = 'Reduced TaxRate';
            $fieldInstance->column = $fieldInstance->name;
            $fieldInstance->uitype = 56;
            $fieldInstance->columntype = 'int(1)';
            $fieldInstance->typeofdata = 'C~O';
            $fieldInstance->diplaytype = '1';
            $fieldInstance->defaultvalue = 0;
            $blockInstance->addField($fieldInstance);
            }
        }
    }

    // 項目「軽減税率対象」を請求書等に追加
    $modulearray = array("Quotes","PurchaseOrder","SalesOrder","Invoice");
    $itemFieldsName = array('reducedtaxrate');
    $itemFieldsLabel = array('軽減税率対象');
    $itemFieldsTypeOfData = array('C~O');
    $itemFieldsDisplayType = array('56');
    $itemFieldsColumnType = array('int(1)');
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
            $field->readonly = '1';
            $field->displaytype = '5';
            $field->masseditable = '1';
            $field->columntype = '';
            $blockInstance->addField($field);
        }
    }

}