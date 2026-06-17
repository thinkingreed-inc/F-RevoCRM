<?php
/**
 * UIType動作確認用テストフィールドをDocumentsモジュールに追加
 * レイアウトエディタから追加可能な全UIType（14種類）の項目を1つずつ作成
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
require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');
include_once('includes/runtime/LanguageHandler.php');
include_once('includes/runtime/Globals.php');
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260616111832_AddUitypeTestFieldsToDocuments extends FRMigrationClass {

    public function process() {
        global $current_user;
        $current_user = Users::getActiveAdminUser();

        $module = Vtiger_Module::getInstance('Documents');

        // テスト用ブロック作成
        $block = new Vtiger_Block();
        $block->label = 'LBL_UITYPE_TEST';
        $block->sequence = 10;
        $block->iscustom = 1;
        $module->addBlock($block);
        $this->log("テスト用ブロック LBL_UITYPE_TEST を追加");

        // 1. Text (uitype=1)
        $f = new Vtiger_Field();
        $f->name = 'test_text';
        $f->label = 'Test Text';
        $f->table = 'vtiger_notes';
        $f->column = 'test_text';
        $f->columntype = 'VARCHAR(255)';
        $f->uitype = 1;
        $f->typeofdata = 'V~O~LE~255';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_text (uitype=1 Text)");

        // 2. Integer (uitype=7, INT)
        $f = new Vtiger_Field();
        $f->name = 'test_integer';
        $f->label = 'Test Integer';
        $f->table = 'vtiger_notes';
        $f->column = 'test_integer';
        $f->columntype = 'INT';
        $f->uitype = 7;
        $f->typeofdata = 'I~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_integer (uitype=7 Integer)");

        // 3. Decimal (uitype=7, NUMERIC)
        $f = new Vtiger_Field();
        $f->name = 'test_decimal';
        $f->label = 'Test Decimal';
        $f->table = 'vtiger_notes';
        $f->column = 'test_decimal';
        $f->columntype = 'NUMERIC(8,2)';
        $f->uitype = 7;
        $f->typeofdata = 'NN~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_decimal (uitype=7 Decimal)");

        // 4. Percent (uitype=9)
        $f = new Vtiger_Field();
        $f->name = 'test_percent';
        $f->label = 'Test Percent';
        $f->table = 'vtiger_notes';
        $f->column = 'test_percent';
        $f->columntype = 'NUMERIC(5,2)';
        $f->uitype = 9;
        $f->typeofdata = 'N~O~2~2';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_percent (uitype=9 Percent)");

        // 5. Currency (uitype=71)
        $f = new Vtiger_Field();
        $f->name = 'test_currency';
        $f->label = 'Test Currency';
        $f->table = 'vtiger_notes';
        $f->column = 'test_currency';
        $f->columntype = 'NUMERIC(13,2)';
        $f->uitype = 71;
        $f->typeofdata = 'N~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_currency (uitype=71 Currency)");

        // 6. Date (uitype=5)
        $f = new Vtiger_Field();
        $f->name = 'test_date';
        $f->label = 'Test Date';
        $f->table = 'vtiger_notes';
        $f->column = 'test_date';
        $f->columntype = 'DATE';
        $f->uitype = 5;
        $f->typeofdata = 'D~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_date (uitype=5 Date)");

        // 7. Email (uitype=13)
        $f = new Vtiger_Field();
        $f->name = 'test_email';
        $f->label = 'Test Email';
        $f->table = 'vtiger_notes';
        $f->column = 'test_email';
        $f->columntype = 'VARCHAR(256)';
        $f->uitype = 13;
        $f->typeofdata = 'E~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_email (uitype=13 Email)");

        // 8. Phone (uitype=11)
        $f = new Vtiger_Field();
        $f->name = 'test_phone';
        $f->label = 'Test Phone';
        $f->table = 'vtiger_notes';
        $f->column = 'test_phone';
        $f->columntype = 'VARCHAR(30)';
        $f->uitype = 11;
        $f->typeofdata = 'V~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_phone (uitype=11 Phone)");

        // 9. Picklist - role based (uitype=15)
        $f = new Vtiger_Field();
        $f->name = 'test_picklist';
        $f->label = 'Test Picklist';
        $f->table = 'vtiger_notes';
        $f->column = 'test_picklist';
        $f->columntype = 'VARCHAR(255)';
        $f->uitype = 15;
        $f->typeofdata = 'V~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $f->setPicklistValues(array('Option A', 'Option B', 'Option C'));
        $this->log("test_picklist (uitype=15 Picklist)");

        // 10. URL (uitype=17)
        $f = new Vtiger_Field();
        $f->name = 'test_url';
        $f->label = 'Test URL';
        $f->table = 'vtiger_notes';
        $f->column = 'test_url';
        $f->columntype = 'VARCHAR(255)';
        $f->uitype = 17;
        $f->typeofdata = 'V~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_url (uitype=17 URL)");

        // 11. Checkbox (uitype=56)
        $f = new Vtiger_Field();
        $f->name = 'test_checkbox';
        $f->label = 'Test Checkbox';
        $f->table = 'vtiger_notes';
        $f->column = 'test_checkbox';
        $f->columntype = "VARCHAR(3) DEFAULT '0'";
        $f->uitype = 56;
        $f->typeofdata = 'C~O';
        $f->defaultvalue = '0';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_checkbox (uitype=56 Checkbox)");

        // 12. TextArea (uitype=21)
        $f = new Vtiger_Field();
        $f->name = 'test_textarea';
        $f->label = 'Test TextArea';
        $f->table = 'vtiger_notes';
        $f->column = 'test_textarea';
        $f->columntype = 'TEXT';
        $f->uitype = 21;
        $f->typeofdata = 'V~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_textarea (uitype=21 TextArea)");

        // 13. MultiSelectCombo (uitype=33)
        $f = new Vtiger_Field();
        $f->name = 'test_multiselect';
        $f->label = 'Test MultiSelect';
        $f->table = 'vtiger_notes';
        $f->column = 'test_multiselect';
        $f->columntype = 'TEXT';
        $f->uitype = 33;
        $f->typeofdata = 'V~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $f->setPicklistValues(array('Red', 'Green', 'Blue', 'Yellow'));
        $this->log("test_multiselect (uitype=33 MultiSelectCombo)");

        // 14. Time (uitype=14)
        $f = new Vtiger_Field();
        $f->name = 'test_time';
        $f->label = 'Test Time';
        $f->table = 'vtiger_notes';
        $f->column = 'test_time';
        $f->columntype = 'TIME';
        $f->uitype = 14;
        $f->typeofdata = 'T~O';
        $f->generatedtype = 2;
        $f->presence = 2;
        $f->displaytype = 1;
        $f->readonly = 1;
        $f->masseditable = 1;
        $f->quickcreate = 2;
        $block->addField($f);
        $this->log("test_time (uitype=14 Time)");

        $this->log("全14種類のUITypeテストフィールドを追加しました");
    }
}
