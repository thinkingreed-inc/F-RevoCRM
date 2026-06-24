<?php
/**
 * UITypeテスト: uitype=10 (Reference) フィールドをDocumentsモジュールに追加
 */
require_once('include/logging.php');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Settings/MenuEditor/models/Module.php');
require_once('modules/Settings/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/MenuStructure.php');
require_once('modules/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/Record.php');
require_once('modules/Users/models/Record.php');
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');
include_once('includes/runtime/LanguageHandler.php');
include_once('includes/runtime/Globals.php');
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260616120121_AddReferenceTestFieldToDocuments extends FRMigrationClass {

    public function process() {
        global $current_user;
        $current_user = Users::getActiveAdminUser();

        $module = Vtiger_Module::getInstance('Documents');
        $block = Vtiger_Block::getInstance('LBL_UITYPE_TEST', $module);

        $f = new Vtiger_Field();
        $f->name = 'test_reference';
        $f->label = 'Test Reference';
        $f->table = 'vtiger_notes';
        $f->column = 'test_reference';
        $f->columntype = 'int(19)';
        $f->uitype = 10;
        $f->typeofdata = 'I~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $f->setRelatedModules(Array('Accounts', 'Contacts'));
        $this->log("test_reference (uitype=10 Reference → Accounts, Contacts) を追加しました");
    }
}
