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

$module = Vtiger_Module::getInstance('Potentials');
$blockInstance = Vtiger_Block::getInstance("LBL_OPPORTUNITY_INFORMATION", $module);

// 最終活動日
$field = new Vtiger_Field();
$field->name = 'last_action_date';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'date';
$field->uitype = 5;
$field->typeofdata = 'D~O';
$field->masseditable = 1;
$field->quickcreate = 2;
$field->summaryfield = 1;
$field->label= 'last_action_date';
$blockInstance->addField($field);

$module = Vtiger_Module::getInstance('Accounts');
$blockInstance = Vtiger_Block::getInstance("LBL_ACCOUNT_INFORMATION", $module);

// 最終活動日
$field = new Vtiger_Field();
$field->name = 'last_action_date';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'date';
$field->uitype = 5;
$field->typeofdata = 'D~O';
$field->masseditable = 1;
$field->quickcreate = 2;
$field->summaryfield = 1;
$field->label= 'last_action_date';
$blockInstance->addField($field);

$module = Vtiger_Module::getInstance('Contacts');
$blockInstance = Vtiger_Block::getInstance("LBL_CONTACT_INFORMATION", $module);

// 最終活動日
$field = new Vtiger_Field();
$field->name = 'last_action_date';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'date';
$field->uitype = 5;
$field->typeofdata = 'D~O';
$field->masseditable = 1;
$field->quickcreate = 2;
$field->summaryfield = 1;
$field->label= 'last_action_date';
$blockInstance->addField($field);

$module = Vtiger_Module::getInstance('Leads');
$blockInstance = Vtiger_Block::getInstance("LBL_LEAD_INFORMATION", $module);

// 最終活動日
$field = new Vtiger_Field();
$field->name = 'last_action_date';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'date';
$field->uitype = 5;
$field->typeofdata = 'D~O';
$field->masseditable = 1;
$field->quickcreate = 2;
$field->summaryfield = 1;
$field->label= 'last_action_date';
$blockInstance->addField($field);



