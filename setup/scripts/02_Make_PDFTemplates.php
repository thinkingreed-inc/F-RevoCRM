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


global $log;

$db = PearDatabase::getInstance();

$module_name = 'PDFTemplates';
$table_name = 'vtiger_pdftemplates';
$main_id =  'templateid';

$module = new Vtiger_Module();
$module->name = $module_name;
$module->parent = "Tools";
$module->isentitytype = 0;
$module->save();
$module->initTables($table_name, $main_id);
$tabid = $module->id;

// 初期共有設定を行う
// 本設定はモジュール内全てのデータを公開
$module->initWebservice();
$module->setDefaultSharing('Public_ReadWriteDelete');

//必須
Settings_MenuEditor_Module_Model::addModuleToApp($module->name, $module->parent);

$db->query('drop table vtiger_pdftemplates');
$db->query("
CREATE TABLE `vtiger_pdftemplates` (
    `foldername` varchar(100) DEFAULT NULL,
    `module` varchar(100) DEFAULT NULL,
    `templatename` varchar(100) DEFAULT NULL,
    `subject` varchar(100) DEFAULT NULL,
    `description` text,
    `body` text,
    `deleted` int(1) NOT NULL DEFAULT '0',
    `systemtemplate` int(1) NOT NULL DEFAULT '0',
    `templateid` int(19) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`templateid`),
    KEY `pdftemplates_foldernamd_templatename_subject_idx` (`foldername`,`templatename`,`subject`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
");

$db->query("
CREATE TABLE  `vtiger_pdftemplates_seq` (
    `id` int(11) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");


$db->query("INSERT INTO `vtiger_pdftemplates_seq` (`id`) VALUES (1)");

// デフォルトのテンプレート追加
$sql = "INSERT INTO vtiger_pdftemplates(foldername, module, templatename, subject, description, body, deleted, systemtemplate, templateid)VALUES(?,?,?,?,?,?,?,?,?)";

$foldername = '';
$module = 'Quotes';
$templatename = '見積書';
$subject = '';
$description = '';
$body = file_get_contents('layouts/v7/modules/PDFTemplates/templates/QuotesTemplate.tpl');
$deleted = 0;
$systemtemplate = 0;
$templateid = $db->getUniqueID('vtiger_pdftemplates');

$params = array($foldername, $module, $templatename, $subject, $description, $body, $deleted, $systemtemplate, $templatename);
$db->pquery($sql, $params);

$foldername = '';
$module = 'Invoice';
$templatename = '請求書';
$subject = '';
$description = '';
$body = file_get_contents('layouts/v7/modules/PDFTemplates/templates/InvoiceTemplate.tpl');
$deleted = 0;
$systemtemplate = 0;
$templateid = $db->getUniqueID('vtiger_pdftemplates');

$params = array($foldername, $module, $templatename, $subject, $description, $body, $deleted, $systemtemplate, $templatename);
$db->pquery($sql, $params);

$foldername = '';
$module = 'SalesOrder';
$templatename = '注文請書';
$subject = '';
$description = '';
$body = file_get_contents('layouts/v7/modules/PDFTemplates/templates/SalesOrderTemplate.tpl');
$deleted = 0;
$systemtemplate = 0;
$templateid = $db->getUniqueID('vtiger_pdftemplates');

$params = array($foldername, $module, $templatename, $subject, $description, $body, $deleted, $systemtemplate, $templatename);
$db->pquery($sql, $params);

$foldername = '';
$module = 'PurchaseOrder';
$templatename = '発注書';
$subject = '';
$description = '';
$body = file_get_contents('layouts/v7/modules/PDFTemplates/templates/PurchaseOrderTemplate.tpl');
$deleted = 0;
$systemtemplate = 0;
$templateid = $db->getUniqueID('vtiger_pdftemplates');

$params = array($foldername, $module, $templatename, $subject, $description, $body, $deleted, $systemtemplate, $templatename);
$db->pquery($sql, $params);

echo "実行が完了しました。<br>";
$log->debug("[END] Add Comments function");