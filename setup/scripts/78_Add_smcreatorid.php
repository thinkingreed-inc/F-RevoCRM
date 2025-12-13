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

$module = Vtiger_Module::getInstance('Events');
$blockInstance = Vtiger_Block::getInstance("LBL_EVENT_INFORMATION", $module);
$creatorfield = Vtiger_Field_Model::getInstance('creator', $module);

if ($creatorfield === false) {
// 作成者
    $field = new Vtiger_Field();
    $field->name = 'creator';
    $field->table = 'vtiger_activity';
    $field->column = 'smcreatorid';
    $field->columntype = 'int';
    $field->uitype = 52;
    $field->typeofdata = 'V~O';
    $field->masseditable = 0;
    $field->quickcreate = 0;
    $field->summaryfield = 0;
    $field->label= 'LBL_CREATOR';
    $field->displaytype = 2;
    $blockInstance->addField($field);
}

