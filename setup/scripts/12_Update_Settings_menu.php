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

require_once('includes/runtime/BaseModel.php');
require_once('modules/Settings/Vtiger/models/Record.php');
require_once('modules/Settings/Roles/models/Record.php');
require_once('modules/Settings/Profiles/models/Record.php');

global $adb;

$adb->query("UPDATE vtiger_settings_field SET blockid = (SELECT blockid FROM vtiger_settings_blocks WHERE label = 'LBL_MODULE_MANAGER') WHERE name = 'LBL_PICKLIST_EDITOR'");
$adb->query("UPDATE vtiger_settings_field SET blockid = (SELECT blockid FROM vtiger_settings_blocks WHERE label = 'LBL_MODULE_MANAGER') WHERE name = 'LBL_PICKLIST_DEPENDENCY'");
$adb->query("UPDATE vtiger_settings_field SET blockid = (SELECT blockid FROM vtiger_settings_blocks WHERE label = 'LBL_AUTOMATION') WHERE name = 'LBL_MAIL_SCANNER'");

$adb->query("UPDATE vtiger_visibility SET sortorderid = 1 WHERE visibilityid = 2");
$adb->query("UPDATE vtiger_visibility SET sortorderid = 2 WHERE visibilityid = 1");