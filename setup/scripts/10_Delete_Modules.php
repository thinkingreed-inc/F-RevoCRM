<?php

$Vtiger_Utils_Log = true;
include_once 'config.php';
//include_once 'include/Webservices/Relation.php';

include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';

include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');

include_once('modules/Vtiger/models/Module.php');
require_once 'includes/Loader.php';

$db = PearDatabase::getInstance();

$module = Vtiger_Module::getInstance('ExtensionStore');
$module->delete();

$db->query("delete from vtiger_links where linklabel like 'ExtensionStoreCommonHeaderScript'");

$module = Vtiger_Module::getInstance('PBXManager');
$module->delete();

$module = Vtiger_Module::getInstance('Google');
$module->delete();

$db->query("delete from vtiger_settings_blocks where label like 'LBL_EXTENSIONS'");

// Report permissions are invalid. '0' is valid, '1' is invalid.
$db->query("UPDATE vtiger_profile2standardpermissions p SET p.permissions = 0 WHERE p.operation in (1,2,7) AND p.tabid = (SELECT t.tabid FROM vtiger_tab t WHERE t.name = 'Reports')");
RecalculateSharingRules();

//uitype 68を廃止する
$result = $db->query("select fieldid from vtiger_field where uitype = 68");
if($db->num_rows($result) > 0) {
    $id = $db->query_result($result, 0, "fieldid");
    $db->query("insert into vtiger_fieldmodulerel(fieldid, module, relmodule, status, sequence) values ($id, 'HelpDesk', 'Accounts',null, null)");
    $db->query("insert into vtiger_fieldmodulerel(fieldid, module, relmodule, status, sequence) values ($id, 'HelpDesk', 'Contacts',null, null)");
    $db->query("update vtiger_field set uitype = 10 where uitype = 68");
}
