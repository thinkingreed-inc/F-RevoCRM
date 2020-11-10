<?php
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb;

echo '日本語追加st
';
$result = $adb->query("INSERT INTO vtiger_language (name,prefix, label,lastupdated,isdefault, active) VALUES ('Japanese','ja_jp', '日本語',NOW(),0, 1)");

// デフォルト値を日本語にする
$adb->query("UPDATE vtiger_field SET defaultvalue = 'ja_jp' WHERE tabid = (SELECT vtiger_tab.tabid FROM vtiger_tab WHERE vtiger_tab.name = 'Users') AND columnname = 'language'");

echo '日本語追加ed
';