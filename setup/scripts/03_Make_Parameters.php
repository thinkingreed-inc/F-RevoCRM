<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb, $log;

$module_name = "Parameters";

# SHOW TABLES LIKE 'vtiger_parameters'でテーブルが存在するか確認する
$result = $adb->query("SHOW TABLES LIKE 'vtiger_parameters'");
if($adb->num_rows($result) == 0) {
    $adb->query("
        CREATE TABLE `vtiger_parameters` (
            `id` int(19) NOT NULL AUTO_INCREMENT,
            `key` varchar(200) DEFAULT NULL,
            `value` text DEFAULT NULL,
            `description` text,
            PRIMARY KEY (`id`),
            KEY `key` (`key`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ");
}

$query = "SELECT * FROM vtiger_settings_field WHERE name = ?";
$result = $adb->pquery($query, array($module_name));

//Add link to the module in the Setting Panel
$fieldid = $adb->getUniqueID('vtiger_settings_field');
$blockid = getSettingsBlockId('LBL_CONFIGURATION');

if($adb->num_rows($result) == 0)
{			
    $seq_res = $adb->query("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid=$blockid");
    $seq = 1;
    if ($adb->num_rows($seq_res) > 0)
    {
        $cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
        
        if ($cur_seq != null)
        {
            $seq = $cur_seq + 1;
        }
    }
        
    $adb->pquery
    (
        'INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence,active) VALUES (?,?,?,?,?,?,?,?)',
        array
        (
            $fieldid,
            $blockid,
            $module_name,
            null,
            null,
            'index.php?module='.$module_name.'&view=List&parent=Settings',
            $seq,
            0
        )
    );
}
