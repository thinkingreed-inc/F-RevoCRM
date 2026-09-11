<?php
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the 
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
********************************************************************************/

// 本番環境/開発環境の設定
// 本番環境であれば true を指定してください。
$IS_PRODUCTION = true;

// Adjust error_reporting favourable to deployment.
// E_STRICT is deprecated since PHP 8.4
$_e_strict = (PHP_VERSION_ID < 80400) ? E_STRICT : 0;
error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED & E_ERROR & ~$_e_strict); // PRODUCTION
//ini_set('display_errors','on'); error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~$_e_strict);   // DEBUGGING
//ini_set('display_errors','on'); error_reporting(E_ALL); // STRICT DEVELOPMENT

ini_set('display_errors','on'); error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~$_e_strict & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

include('vtigerversion.php');

// more than 8MB memory needed for graphics
// memory limit default value = 64M
ini_set('memory_limit','512M');

// show or hide calendar, world clock, calculator, chat and Jodit editor
// Do NOT remove the quotes if you set these to false! 
$CALENDAR_DISPLAY = 'true';
$USE_RTE = 'true';

// helpdesk support email id and support name (Example: 'support@vtiger.com' and 'vtiger support')
$HELPDESK_SUPPORT_EMAIL_ID = '_USER_SUPPORT_EMAIL_';
$HELPDESK_SUPPORT_NAME = 'your-support name';
$HELPDESK_SUPPORT_EMAIL_REPLY_ID = $HELPDESK_SUPPORT_EMAIL_ID;

/* database configuration
      db_server
      db_port
      db_hostname
      db_username
      db_password
      db_name
*/

$dbconfig['db_server'] = '_DBC_SERVER_';
$dbconfig['db_port'] = ':_DBC_PORT_';
$dbconfig['db_username'] = '_DBC_USER_';
$dbconfig['db_password'] = '_DBC_PASS_';
$dbconfig['db_name'] = '_DBC_NAME_';
$dbconfig['db_type'] = '_DBC_TYPE_';
$dbconfig['db_status'] = '_DB_STAT_';

// TODO: test if port is empty
// TODO: set db_hostname dependending on db_type
$dbconfig['db_hostname'] = $dbconfig['db_server'].$dbconfig['db_port'];

// log_sql default value = false
$dbconfig['log_sql'] = false;

// persistent default value = true
$dbconfigoption['persistent'] = true;

// autofree default value = false
$dbconfigoption['autofree'] = false;

// debug default value = 0
$dbconfigoption['debug'] = 0;

// seqname_format default value = '%s_seq'
$dbconfigoption['seqname_format'] = '%s_seq';

// portability default value = 0
$dbconfigoption['portability'] = 0;

// ssl default value = false
$dbconfigoption['ssl'] = false;

$host_name = $dbconfig['db_hostname'];

$site_URL = '_SITE_URL_';

// url for customer portal (Example: http://vtiger.com/portal)
$PORTAL_URL = $site_URL.'/customerportal';
// root directory path
$root_directory = '_VT_ROOTDIR_';

// cache direcory path
$cache_dir = '_VT_CACHEDIR_';

// tmp_dir default value prepended by cache_dir = images/
$tmp_dir = '_VT_TMPDIR_';

// import_dir default value prepended by cache_dir = import/
$import_dir = 'cache/import/';

// upload_dir default value prepended by cache_dir = upload/
$upload_dir = '_VT_UPLOADDIR_';

// maximum file size for uploaded files in bytes also used when uploading import files
// upload_maxsize default value = 3000000
$upload_maxsize = 3145728;//3MB

// flag to allow export functionality
// 'all' to allow anyone to use exports 
// 'admin' to only allow admins to export 
// 'none' to block exports completely 
// allow_exports default value = all
$allow_exports = 'all';

// files with one of these extensions will have '.txt' appended to their filename on upload
// upload_badext default value = php, php3, php4, php5, pl, cgi, py, asp, cfm, js, vbs, html, htm
$upload_badext = array('php', 'php3', 'php4', 'php5', 'pl', 'cgi', 'py', 'asp', 'cfm', 'js', 'vbs', 'html', 'htm', 'exe', 'bin', 'bat', 'sh', 'dll', 'phps', 'phtml', 'xhtml', 'rb', 'msi', 'jsp', 'shtml', 'sth', 'shtm');

// list_max_entries_per_page default value = 20
$list_max_entries_per_page = '20';

// history_max_viewed default value = 5
$history_max_viewed = '5';

// default_action default value = index
$default_action = 'index';

// set default theme
// default_theme default value = blue
$default_theme = 'softed';

// default text that is placed initially in the login form for user name
// no default_user_name default value
$default_user_name = '';

// default text that is placed initially in the login form for password
// no default_password default value
$default_password = '';

// create user with default username and password
// create_default_user default value = false
$create_default_user = false;

//Master currency name
$currency_name = '_MASTER_CURRENCY_';

// default charset
// default charset default value = 'UTF-8' or 'ISO-8859-1'
$default_charset = '_VT_CHARSET_';

// default language
// default_language default value = en_us
$default_language = '_VT_DEFAULT_LANGUAGE_';

//Option to hide empty home blocks if no entries.
$display_empty_home_blocks = false;

//Disable Stat Tracking of vtiger CRM instance
$disable_stats_tracking = false;

// Generating Unique Application Key
$application_unique_key = '_VT_APP_UNIQKEY_';

// trim descriptions, titles in listviews to this value
$listview_max_textlength = 40;

// Maximum time limit for PHP script execution (in seconds)
$php_max_execution_time = 0;

// Set the default timezone as per your preference
$default_timezone = 'Asia/Tokyo';

/** If timezone is configured, try to set it */
if(isset($default_timezone) && function_exists('date_default_timezone_set')) {
	@date_default_timezone_set($default_timezone);
}

//Set the default layout 
$default_layout = 'v7';

// スケジュールワークフローの設定最大数
$max_scheduled_workflows = 50;

// メールの「クリック数」のカウント
$email_tracking = 'Yes';

//Maximum Listview Fields Selection Size
$maxListFieldsSelectionSize = 15;

// スケジューラー（vtigercron.php）が同時に実行する cron タスクの最大数。
// 1 タスクにつき 1 プロセスが起動するため、DB の最大接続数やサーバの負荷に合わせて調整する。
// 1 を指定すると並列実行を行わず、従来通り 1 プロセスで順番に実行する。
$cron_max_parallel = 4;

// 互いに同時実行させたくない cron タスク名。ここに挙げたタスクは同時に 1 つまでしか実行しない。
// 指定していないタスクとは並列に実行されるため、他タスクの実行を止めることはない。
// 例: $cron_serial_tasks = array('Workflow', 'RecurringInvoice');
$cron_serial_tasks = array();

// retry_timeout が設定されていない cron タスクに適用するタイムアウト秒数。
// 実行中のまま停止したタスクを、この秒数を過ぎたら再実行できるものとして扱う。
$cron_default_retry_timeout = 3600;

// retry_timeout を過ぎても終わらない cron タスクのプロセスを強制終了するか。
// true にすると強制終了したうえでタスクを解放し、次回以降の実行を自動的に再開する。
// false にすると警告をログに出すだけで再実行もしない（原因調査を優先したい場合に使う）。
$cron_kill_timed_out = true;

// 実行ログ（logs/cron/<タスク名>_<日付>.log）をタスクごとに残す世代数（ファイル数）。
// 新しいものをこの数だけ残し、それより古いファイルは自動で削除する。
// 0 を指定すると削除しない（無期限に残す）。
// タスクごとの指定はスケジューラー画面（システム設定）から行える。ここはその既定値。
$cron_log_retention_count = 30;

// --- アプリケーションサーバーが複数台ある構成向けの設定 ---------------------------
// 同じデータベースを共有する複数台で cron を動かしても、タスクが二重に実行されることは無い。
// 実行権の獲得と振り分けはデータベース側のロックで排他制御している。
//
// 【重要】各サーバーの時刻を NTP で同期しておくこと。
// 時刻の判定自体はデータベースの時計に揃えているが、ログの時刻表示がずれて調査しにくくなる。

// 担当サーバーが落ちたとみなすまでの秒数。
// 担当サーバーは自分が起動した子プロセスの生存を確認するたびに記録を更新する。
// この秒数を超えて更新が止まったら、他のサーバーがタスクを引き継ぐ。
// cron の起動間隔（1分）の数回分を見込んだ値にすること。短すぎると、
// 一時的に負荷が高いだけのサーバーからタスクを奪ってしまう。
$cron_heartbeat_timeout = 300;

// このサーバーを識別する名前。未設定ならホスト名を使う。
// コンテナ等でホスト名が毎回変わる環境では、固定の名前を明示的に指定すること。
// $cron_host_name = 'app01';

include_once 'config.security.php';
?>
