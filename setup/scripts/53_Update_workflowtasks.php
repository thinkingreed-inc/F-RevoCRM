<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');

global $adb;

// デフォルトのワークフローのタスクを無効にする
$adb->query("update com_vtiger_workflowtasks set task = replace(task, '\"active\";s:1', '\"active\";s:0')");
$adb->query("update com_vtiger_workflowtasks set task = replace(task, '\"active\";b:1', '\"active\";b:0')");
