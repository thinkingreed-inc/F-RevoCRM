<?php
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb;

$module_name = "HolidayManager";
$table_name = 'vtiger_holiday';
$query = "SELECT * FROM vtiger_settings_field WHERE name = ?";
$result = $adb->pquery($query, array($module_name));

//Add link to the module in the Setting Panel
$fieldid = $adb->getUniqueID('vtiger_settings_field');
$blockid = getSettingsBlockId('LBL_MODULE_MANAGER');

$adb->query("CREATE TABLE vtiger_holiday (
    id int(19) NOT NULL AUTO_INCREMENT,
    holidayname varchar(100) NOT NULL,
    date DATE NOT NULL,
    holidaystatus varchar(100),
    PRIMARY KEY (id)
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

$db->query("CREATE TABLE vtiger_holiday_api (
    id int(19) NOT NULL AUTO_INCREMENT,
    exist int(10) NOT NULL,
    year int(100) NOT NULL,
    PRIMARY KEY (id)
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");



if($adb->num_rows($result) == 0){
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
            'LBL_'.strtoupper($module_name).'_DESCRIPTION',
            'index.php?module='.$module_name.'&view=List&parent=Settings',
            $seq,
            0
        )
    );

}

