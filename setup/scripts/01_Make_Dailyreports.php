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

$module_name = 'Dailyreports';
$table_name = 'vtiger_dailyreports';
//$main_name = 'dailyreportsname';
$main_id =  'dailyreportsid';

$module = new Vtiger_Module();
$module->name = $module_name;
$module->parent = "Sales";
$module->save();
$module->initTables($table_name, $main_id);
$tabid = $module->id;


//=========
//日報情報
//=========

/* 基本情報 */
$blockInstance = new Vtiger_Block();
$blockInstance->label = 'LBL_DAYILYREPORTS_INFORMATION';
$module->addBlock($blockInstance);

// 件名
$field = new Vtiger_Field();
$field->name = 'dailyreportsname';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'varchar(100)';
$field->uitype = 2;
$field->typeofdata = 'V~M';
$field->masseditable = 1;
$field->quickcreate = 1;
$field->summaryfield = 1;
$field->label = '件名';
$blockInstance->addField($field);

/*
* モジュール内でキーとなるカラム1つに対して実行
* 複数回は実施しないこと
*/
$module->setEntityIdentifier($field);

// 日報/週報
$field = new Vtiger_Field();
$field->name = 'reportsterm';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'varchar(100)';
$field->uitype = 15;
$field->typeofdata = 'V~M';
$field->masseditable = 1;
$field->quickcreate = 1;
$field->summaryfield = 1;
$field->label = '日報/週報';
$field->defaultvalue = 'Day';
$array = array("Day","Week");
$field->setPicklistValues( $array );
$blockInstance->addField($field);

// 提出日
$field = new Vtiger_Field();
$field->name = 'ReportsDate';
$field->table = $module->basetable;
$field->column = 'reportsdate';
$field->columntype = 'date';
$field->uitype = 23;
$field->typeofdata = 'D~M';
$field->masseditable = 1;
$field->quickcreate = 1;
$field->summaryfield = 1;
$field->label = '提出日';
$blockInstance->addField($field);

// ステータス
$field = new Vtiger_Field();
$field->name = 'dailyreportsstatus';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'varchar(100)';
$field->uitype = 15;
$field->typeofdata = 'V~M';
$field->masseditable = 1;
$field->quickcreate = 1;
$field->summaryfield = 1;
$field->label = 'ステータス';
$field->defaultvalue = '提出中';
$array = array("提出中","承認済");
$field->setPicklistValues( $array );
$blockInstance->addField($field);

// 提出先
$field = new Vtiger_Field();
$field->name = 'reports_to_id';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'int(19)';
$field->uitype = 53;
$field->typeofdata = 'V~M';
$field->masseditable = 1;
$field->quickcreate = 1;
$field->summaryfield = 1;
$field->label = '提出先';
$blockInstance->addField($field);
$field->setRelatedModules(Array('Users'));

// 担当
$field = new Vtiger_Field();
$field->name = 'assigned_user_id';
$field->table = 'vtiger_crmentity';
$field->column = 'smownerid';
$field->columntype = 'int(19)';
$field->uitype = 53;
$field->typeofdata = 'V~M';
$field->masseditable = 0;
$field->quickcreate = 0;
$field->summaryfield = 1;
$field->label = '担当';
$blockInstance->addField($field);
$field->setRelatedModules(Array('Users'));

// コメント
$field = new Vtiger_Field();
$field->name = 'dailyreportscomment';
$field->table = $module->basetable;
$field->column = $field->name;
$field->columntype = 'varchar(250)';
$field->uitype = 21;
$field->typeofdata = 'V~O';
$field->masseditable = 1;
$field->quickcreate = 1;
$field->summaryfield = 1;
$field->label = 'コメント';
$blockInstance->addField($field);

// 作成日時
$field = new Vtiger_Field();
$field->name = 'createdtime';
$field->table = 'vtiger_crmentity';
$field->column = 'createdtime';
$field->uitype = 70;
$field->typeofdata = 'D~O';
$field->displaytype= 2;
$field->masseditable = 0;
$field->quickcreate = 0;
$field->summaryfield = 0;
$field->label= '作成日時';
$blockInstance->addField($field);

// 更新日時
$field = new Vtiger_Field();
$field->name = 'modifiedtime';
$field->table = 'vtiger_crmentity';
$field->column = 'modifiedtime';
$field->columntype = 'datetime';
$field->uitype = 70;
$field->typeofdata = 'D~O';
$field->masseditable = 0;
$field->quickcreate = 0;
$field->summaryfield = 0;
$field->displaytype= 2;
$field->label= '更新日時';
$blockInstance->addField($field);

$field = new Vtiger_Field();
$field->name		= 'tags';
$field->label		= 'tags';
$field->table		= $module->basetable;
$field->presence	= 2;
$field->displaytype	= 6;
$field->readonly	= 1;
$field->uitype		= 1;
$field->typeofdata	= 'V~O';
$field->columntype	= 'VARCHAR(1)';
$field->quickcreate	= 3;
$field->masseditable= 0;
$blockInstance->addField($field);

// 初期共有設定を行う
// 本設定はモジュール内全てのデータを公開
$module->initWebservice();
$module->setDefaultSharing('Public_ReadWriteDelete');

//必須
Settings_MenuEditor_Module_Model::addModuleToApp($module->name, $module->parent);

// インデックスをはる
$sql = "ALTER TABLE $table_name ADD PRIMARY KEY (`$main_id`)";
$db->query($sql);
$sql = "ALTER TABLE ".$table_name."cf ADD PRIMARY KEY(`$main_id`)";
$db->query($sql);

//ModTrackerに追跡
$module = Vtiger_Module::getInstance($module_name);
ModTracker::enableTrackingForModule($module->id);

//　************　一覧の設定　************
FRFilterSetting::deleteAll($module);
FRFilterSetting::add($module, 'All', array(
    'dailyreportsstatus',
    'ReportsDate',
    'assigned_user_id',
    'reports_to_id',
), true);

/* 関連付対応 日報　→　活動*/
$module = Vtiger_Module::getInstance('Calendar');
$parentModule = Vtiger_Module::getInstance('Dailyreports');
$function_name = 'get_activities';
$parentModule->setRelatedList($module, 'Activities', Array('add'), $function_name);

//更新履歴の関連付け
$module = Vtiger_Module::getInstance($module_name);
ModTracker::enableTrackingForModule($module->id);

//インポート等の有効化
$module = Vtiger_Module::getInstance('Dailyreports');
$module->enableTools(array('Import', 'Export', 'Merge'));

/**
 * ModCommentsモジュールを関連に追加
 */
$log->debug("[START] Add Comments function");
$modules = array('Dailyreports');
for( $i=0; $i<count($modules); $i++) {
    $modulename = $modules[$i];
    $moduleinstance = vtiger_module::getinstance($modulename);

    require_once 'modules/ModComments/ModComments.php';
    $commentsmodule = Vtiger_Module::getInstance( 'ModComments' );
    $fieldinstance = Vtiger_Field::getInstance( 'related_to', $commentsmodule );
    $fieldinstance->setRelatedModules( array($modulename) );
    $detailviewblock = ModComments::addWidgetTo( $modulename );
    echo "comment widget for module $modulename has been created";
}

echo "実行が完了しました。<br>";
$log->debug("[END] Add Comments function");