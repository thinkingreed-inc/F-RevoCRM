<?php
/*
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
*/

//ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_DEPRECATED);
//ini_set('display_errors', 1 );

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');

global $adb;

$adb->query("update vtiger_inventory_tandc set tandc = ''");
$adb->query("update vtiger_inventorytaxinfo set deleted = 1");

require_once('modules/Settings/Vtiger/models/TaxRecord.php');
$record = new Inventory_TaxRecord_Model();
$record->set('taxlabel', '消費税');
$record->set('percentage', 10.000);
$record->set('deleted', 0);
$record->set('type', 'Fixed');
$record->set('method', 'Simple');
$record->set('taxType', Inventory_TaxRecord_Model::PRODUCT_AND_SERVICE_TAX);
$record->save();

// $adb->pquery("INSERT INTO vtiger_inventorytaxinfo(taxid, taxname, taxlabel, percentage, deleted, method, type, compoundon, regions)values(?, ?, ?, ?, ?, ?, ?, ?, ?)"
//             ,array(4, 'tax4', '消費税', 10.000, 0, 'Simple', 'Fixed', '[]', '[]'));

$adb->pquery("INSERT INTO vtiger_taxregions(regionid, name)values(?, ?)", array('1', '日本'));

