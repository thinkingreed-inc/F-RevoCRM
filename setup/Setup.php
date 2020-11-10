<?php

ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_DEPRECATED);
ini_set('display_errors', 1 );

echo "Start Script";

require_once('include/logging.php');
require_once 'vtlib/Vtiger/Module.php';
require_once 'includes/main/WebUI.php';
require_once('include/utils/utils.php');
require_once('includes/Loader.php');
require_once('includes/runtime/Globals.php');

require_once('modules/Users/Users.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/Record.php');
require_once('modules/Users/models/Record.php');

require_once('setup/utils/FRMenuSetting.php');
require_once('setup/utils/FRModuleSetting.php');

// ログインしているユーザー作成
global $current_user;
$current_user = new Users();
$current_user->id = 1;

$db = PearDatabase::getInstance();

// カラム名とフィールド名に使える文字数を拡張
$db->query("alter table vtiger_field modify column fieldname varchar(100)");
$db->query("alter table vtiger_field modify column columnname varchar(100)");

// ここに新しいモジュールのスクリプトを書いていく
//require_once ("scripts/01_hoge.php");

// 不要なモジュールを削除
require_once ("scripts/10_Delete_Modules.php");

// 不要な項目を非表示にする
require_once ("scripts/11_Update_Identifer.php");

// 設定メニューを整理する
require_once ("scripts/12_Update_Settings_menu.php");

// ユーザーのデフォルト値を変更
require_once ("scripts/13_Update_Users.php");

// 最終更新日を追加
require_once ("scripts/14_Add_last_action_date.php");

// 活動のクイック作成の項目
require_once ("scripts/15_Update_Activity.php");

// 住所欄の並び替え
require_once ("scripts/16_Update_Address.php");

// 諸条件を空にする
require_once ("scripts/17_Update_Inventory.php");

// 不要な項目を非表示にする
require_once ("scripts/51_Update_role.php");

// デフォルトグループ削除
require_once ("scripts/52_Delete_Groups.php");

// デフォルトワークフロータスクの無効化
require_once ("scripts/53_Update_workflowtasks.php");

// サイドバーの整理
require_once ("scripts/54_Update_SidebarWidget.php");

// 日本語を追加
require_once ("scripts/56_Add_JapaneseLanguage.php");

// メニュー設定
// FRMenuSetting::apply(array(
//     'Accounts',
// ));

// モジュールの無効化はできないがメニュー表示させない
// FRModuleSetting::hide(array(
//     'Potentials',
// ));

// モジュールの無効化対応
// FRModuleSetting::disable(array(
// //    'Accounts',//無効化禁止
// //    'Potentials',//無効化禁止
//     'Contacts',
//     'Emails',
//     'Leads',
//     'Vendors',
//     'PriceBooks',
//     'Quotes',
//     'PurchaseOrder',
//     'SalesOrder',
//     'Invoice',
//     'Rss',
//     'ServiceContracts',
//     'Services',
//     'MailManager',
//     'SMSNotifier',
//     'EmailTemplates',
//     'Assets',
//     'Webforms',
//     'ProjectMilestone',
//     'ProjectTask',
//     'Project',
//     'Dailyreports',
//     'Campaigns',
//     'Products',
//     'Documents',
//     'Google',
//     'PBXManager',
//     'HelpDesk',
//     'Faq',
//     'Calendar',
//     'RecycleBin',
// ));

// インポートのcronを有効にする
$db->query("update vtiger_cron_task set status = 1 where module = 'Import'");

// 管理者ユーザーの言語を日本語に設定
$userRecordModel = Vtiger_Record_Model::getInstanceById(1, "Users");
$userRecordModel->set("mode", "edit");
$userRecordModel->set("language", "ja_jp");
$userRecordModel->save();

// ウィジェット表示プルダウン内メニュー削除
// $deleteWidgetMenu = array(
//     'Funnel',
//     'Potentials by Stage',
//     'Pipelined Amount',
//     'Total Revenue',
//     'Top Potentials',
//     'Key Metrics',
//     'Funnel Amount',
//     'Leads by Status'
// );
// $db->query("delete from vtiger_links where linklabel in ('".implode('\',\'', $deleteWidgetMenu)."')");

echo "End Script";





