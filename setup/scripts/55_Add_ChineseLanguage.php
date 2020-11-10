<?php
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb;

echo '中国語追加st
';
$result = $adb->query("INSERT INTO vtiger_language (name,prefix, label,lastupdated,isdefault, active) VALUES ('Chinese(Simplified)','zh_cn', '简体中文',NOW(),0, 1)");
echo '中国語追加ed
';