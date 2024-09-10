<?php
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Settings/Vtiger/models/Module.php');
require_once('modules/Settings/MenuEditor/models/Module.php');
require_once('modules/Vtiger/models/MenuStructure.php');
require_once('modules/Vtiger/models/Module.php');

global $adb, $log;

/**
 * sync.syncType
 */
$result = $adb->query("SELECT wso.operationid FROM vtiger_ws_operation wso LEFT JOIN vtiger_ws_operation_parameters wsop ON wso.operationid = wsop.operationid WHERE name = 'sync'");
$count = $adb->num_rows($result);
if ($count > 0) {
  $operationId = $adb->query_result($result, 0, 'operationid');

  // syncTypeがあるか確認する
  $result = $adb->query("SELECT * FROM vtiger_ws_operation_parameters WHERE operationid = ? AND name = 'syncType'", array($operationId));
  if ($adb->num_rows($result) == 0) {
    $adb->query("INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) VALUES (?, 'syncType', 'string', ?)", array($operationId, ($count + 1)));
  }
  echo "実行が完了しました。<br>".PHP_EOL;  
} else {
  echo "実行が完了しました。<br>".PHP_EOL;  
}



/**
 * convertlead.element
 */
$result = $adb->query("SELECT wso.operationid FROM vtiger_ws_operation wso LEFT JOIN vtiger_ws_operation_parameters wsop ON wso.operationid = wsop.operationid WHERE name = 'convertlead'");
$count = $adb->num_rows($result);
if ($count > 0) {
  $operationId = $adb->query_result($result, 0, 'operationid');

  // syncTypeがあるか確認する
  $result = $adb->query("SELECT * FROM vtiger_ws_operation_parameters WHERE operationid = ? AND name = 'element'", array($operationId));
  if ($adb->num_rows($result) == 0) {
    $adb->query("INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) VALUES (?, 'element', 'encoded', ?)", array($operationId, ($count + 1)));
  }
  echo "実行が完了しました。<br>".PHP_EOL;  
} else {
  echo "実行が完了しました。<br>".PHP_EOL;  
}

