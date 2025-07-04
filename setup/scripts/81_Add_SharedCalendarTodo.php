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

$db = PearDatabase::getInstance();

$moduleInstance = Vtiger_Module::getInstance('Users');
$blockInstance = Vtiger_Block::getInstance('LBL_CALENDAR_SETTINGS', $moduleInstance);
if ($blockInstance) {
    $fieldInstance = Vtiger_Field::getInstance('sharedcalendartodoview', $moduleInstance);
    if (!$fieldInstance) {
        $fieldInstance				= new Vtiger_Field();
        $fieldInstance->name		= 'sharedcalendartodoview';
        $fieldInstance->label		= 'Shared Calendar Todo View';
        $fieldInstance->table		= 'vtiger_users';
        $fieldInstance->column		= 'sharedcalendartodoview';
        $fieldInstance->uitype		= '16';
        $fieldInstance->presence	= '0';
        $fieldInstance->typeofdata	= 'V~O';
        $fieldInstance->columntype	= 'VARCHAR(100)';
        $fieldInstance->defaultvalue= 'Hidden';

        $blockInstance->addField($fieldInstance);
        $fieldInstance->setPicklistValues(array('Hidden', 'Self Todo', 'All Todo'));
        echo PHP_EOL.'Shared Calendar Todo View added'.PHP_EOL;

        $db->query("UPDATE vtiger_field SET displaytype = 1, quickcreate = 2, defaultvalue = 'Private' WHERE tabid = 9 AND fieldname = 'visibility'");
        $db->query("UPDATE vtiger_field SET quickcreate = 2 WHERE tabid = 9 AND fieldname = 'taskpriority'");
    }
}
