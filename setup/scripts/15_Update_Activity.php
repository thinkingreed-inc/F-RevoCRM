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

$sql = "UPDATE vtiger_field SET quickcreate = 2 WHERE fieldname = ? AND tabid = (SELECT t.tabid FROM vtiger_tab t WHERE t.name = 'Events') ";
$adb->pquery($sql, array('parent_id'));
$adb->pquery($sql, array('sendnotification'));
$adb->pquery($sql, array('location'));
$adb->pquery($sql, array('taskpriority'));
$adb->pquery($sql, array('visibility'));
$adb->pquery($sql, array('description'));
$adb->pquery($sql, array('reminder_time'));
$adb->pquery($sql, array('contact_id'));