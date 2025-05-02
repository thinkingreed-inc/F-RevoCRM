<?php
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');
include_once('includes/runtime/BaseModel.php');
include_once('includes/runtime/LanguageHandler.php');
include_once('modules/Vtiger/models/Record.php');
include_once('includes/runtime/Globals.php');
include_once('modules/Vtiger/models/Record.php');
include_once('modules/Vtiger/models/Module.php');

global $adb;
$result = $adb->pquery('SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype=?', array('999'));
if (!$adb->num_rows($result)) {
    $adb->pquery('INSERT INTO vtiger_ws_fieldtype(uitype,fieldtype) VALUES(?, ?)', array('999', 'blank'));
}
