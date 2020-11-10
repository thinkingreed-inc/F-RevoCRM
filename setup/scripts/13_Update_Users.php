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
require_once('includes/Loader.php');
require_once('includes/runtime/Globals.php');

require_once('modules/Users/Users.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/Record.php');
require_once('modules/Users/models/Record.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');

global $adb;

// スケジュールのデフォルト値を30分間隔にする
$adb->query("UPDATE vtiger_field SET defaultvalue = 30 WHERE tabid = (SELECT vtiger_tab.tabid FROM vtiger_tab WHERE vtiger_tab.name = 'Users') AND columnname in ('callduration','othereventduration')");

// 週の初めのデフォルト値を月曜日にする
$adb->query("UPDATE vtiger_field SET defaultvalue = 'Monday' WHERE tabid = (SELECT vtiger_tab.tabid FROM vtiger_tab WHERE vtiger_tab.name = 'Users') AND columnname = 'dayoftheweek'");

// 通貨の小数点を0桁にする
$adb->query("UPDATE vtiger_field SET defaultvalue = '0' WHERE tabid = (SELECT vtiger_tab.tabid FROM vtiger_tab WHERE vtiger_tab.name = 'Users') AND columnname = 'no_of_currency_decimals'");

// 時刻表示を24時表記にする
$adb->query("UPDATE vtiger_field SET defaultvalue = '24' WHERE tabid = (SELECT vtiger_tab.tabid FROM vtiger_tab WHERE vtiger_tab.name = 'Users') AND columnname = 'hour_format'");

global $current_user;
$current_user = new Users();
$current_user->id = 1;

// システム管理者の設定変更
$user = Users_Record_Model::getInstanceById(1, 'Users');
$user->set('language', 'ja_jp');
$user->set('dayoftheweek', 'Monday');
$user->set('callduration', '30');
$user->set('othereventduration', '30');
$user->set('no_of_currency_decimals', '0');
$user->set('hour_format', '24');
$user->set('mode', 'edit');
$user->save();