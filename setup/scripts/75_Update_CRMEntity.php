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

$db = PearDatabase::getInstance();

$modules = array();
$ignoreModules = array('SMSNotifier', 'PBXManager', "Webmails");
$result = $db->pquery('SELECT name FROM vtiger_tab WHERE isentitytype=? AND name NOT IN ('.generateQuestionMarks($ignoreModules).')', array(1, $ignoreModules));
while ($row = $db->fetchByAssoc($result)) {
    $modules[] = $row['name'];
}

$executedTables = array();

foreach ($modules as $modulename) {
    $module = CRMEntity::getInstance($modulename);
    $baseTable = $module->table_name;
    $baseTableid = $module->table_index;

    if(empty($baseTable)) {
        continue;
    }

    if(in_array($baseTable, $executedTables)) {
        continue;
    }

    echo date('Y-m-d H:i:s').' '.$baseTable;
    echo PHP_EOL;

    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `smcreatorid` INT', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `smownerid` INT', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `modifiedby` INT', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `description` longtext', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `createdtime` datetime', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `modifiedtime` datetime', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `viewedtime` datetime', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `deleted` INT', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `label` varchar(255)', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `smgroupid` INT', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `source` varchar(100)', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `locked` INT', array());
    $adb->pquery('ALTER TABLE '.$baseTable.' ADD COLUMN `lockeduserid` INT', array());

    $adb->query("UPDATE
                    $baseTable,
                    vtiger_crmentity
                SET
                    $baseTable.smcreatorid = vtiger_crmentity.smcreatorid,
                    $baseTable.smownerid = vtiger_crmentity.smownerid,
                    $baseTable.modifiedby = vtiger_crmentity.modifiedby,
                    $baseTable.description = vtiger_crmentity.description,
                    $baseTable.createdtime = vtiger_crmentity.createdtime,
                    $baseTable.modifiedtime = vtiger_crmentity.modifiedtime,
                    $baseTable.viewedtime = vtiger_crmentity.viewedtime,
                    $baseTable.deleted = vtiger_crmentity.deleted,
                    $baseTable.label = vtiger_crmentity.label,
                    $baseTable.smgroupid = vtiger_crmentity.smgroupid,
                    $baseTable.source = vtiger_crmentity.source,
                    $baseTable.locked = vtiger_crmentity.locked,
                    $baseTable.lockeduserid = vtiger_crmentity.lockeduserid
                WHERE
                    $baseTable.$baseTableid = vtiger_crmentity.crmid
                    AND $baseTable.deleted is null
                    AND $baseTable.smownerid is null");

    $adb->pquery("CREATE INDEX idx_info ON $baseTable(deleted, modifiedtime)", array());
    $adb->pquery("CREATE INDEX idx_label ON $baseTable(deleted, label)", array());
    $adb->pquery("CREATE INDEX idx_owner ON $baseTable(deleted, smownerid)", array());

    // カラム情報の更新
    $adb->pquery("UPDATE vtiger_field SET tablename = ? WHERE tabid = ? AND tablename = ?", array($baseTable, $module->id, 'vtiger_crmentity'));

    $executedTables[] = $baseTable;
}

echo "実行が完了しました。<br>";
