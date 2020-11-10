<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');

$db = PearDatabase::getInstance();

echo 'サイドメニューからGoogle関連のメニューを削除St
';
$db->query("delete from vtiger_links where linklabel like 'Google%'");
echo 'サイドメニューからGoogle関連のメニューを削除End
';