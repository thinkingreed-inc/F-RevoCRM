<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

// テスト実行時、ローカル開発DB ではなくテスト用DB (末尾 _test) を使うことを保証する。
// config.inc.php を読み込み、$dbconfig['db_name'] を上書きする。

$projectRoot = dirname(__DIR__);

if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', __DIR__);
}

// PearDatabase 等が 'include/logging.php' のような相対パスで依存ファイルを読み込むため、
// cwd をプロジェクトルートに設定する。
chdir($projectRoot);

if (!file_exists($projectRoot . '/config.inc.php')) {
    fwrite(STDERR, "FATAL: config.inc.php がありません。F-RevoCRMをまずインストールしてください。\n");
    exit(1);
}

// config.inc.php が CLI 未定義のサーバー変数を参照するためスタブを設定する。
$_SERVER['HTTPS']       = $_SERVER['HTTPS']       ?? '';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

require_once $projectRoot . '/config.inc.php';

if (!isset($dbconfig) || !is_array($dbconfig)) {
    fwrite(STDERR, "FATAL: config.inc.php から \$dbconfig を読み取れません。\n");
    exit(1);
}

// config.inc.php は環境ごとに書き換わるため値の型を保証できない。文字列として取り出す。
$dbConfigString = static function (array $config, string $key): string {
    $value = $config[$key] ?? '';

    return is_string($value) ? $value : '';
};

// テストDB名は環境変数 FREVOCRM_TEST_DB_NAME で指定する。
// 未指定なら config.inc.php の db_name に '_test' を付けた名前を使う
// (開発DBと同名になって取り違えるのを防ぐため、必ず別DBを指す)。
$testDbName = getenv('FREVOCRM_TEST_DB_NAME') ?: ($dbConfigString($dbconfig, 'db_name') . '_test');
$dbconfig['db_name'] = $testDbName;

// 接続情報は config.inc.php の値を既定とし、環境変数で上書きできるようにする。
// (Docker 構成では db サービス名を FREVOCRM_TEST_DB_HOST に渡す)
$envDbHost = getenv('FREVOCRM_TEST_DB_HOST');
if ($envDbHost !== false && $envDbHost !== '') {
    $dbconfig['db_server'] = $envDbHost;
}
$envDbUsername = getenv('FREVOCRM_TEST_DB_USERNAME');
if ($envDbUsername !== false && $envDbUsername !== '') {
    $dbconfig['db_username'] = $envDbUsername;
}
// 空パスワードの MySQL も指定できるよう、未設定 (false) のときだけ config の値を使う
$envDbPassword = getenv('FREVOCRM_TEST_DB_PASSWORD');
if ($envDbPassword !== false) {
    $dbconfig['db_password'] = $envDbPassword;
}
$dbconfig['db_hostname'] = $dbConfigString($dbconfig, 'db_server') . $dbConfigString($dbconfig, 'db_port');
$GLOBALS['dbconfig']       = $dbconfig;           // 全フィールドを GLOBALS にコピー
$GLOBALS['dbconfigoption'] = $dbconfigoption ?? []; // PearDatabase が参照する接続オプション

if (!preg_match('/_test$/', $GLOBALS['dbconfig']['db_name'])) {
    fwrite(STDERR, "FATAL: テスト bootstrap が非テストDB ({$GLOBALS['dbconfig']['db_name']}) を指しています。\n");
    exit(1);
}

// PearDatabase.php がファイルスコープで $log/$logsqltm/$adb を定義する。
// PHPUnit の bootstrap ローダはファンクションスコープなので、
// 明示的に global 宣言してグローバル空間に届ける。
global $log, $logsqltm, $adb, $site_URL, $default_charset;

require_once $projectRoot . '/vendor/autoload.php';

// Vtiger Loader は composer の autoload.files 経由で読み込まれ、
// spl_autoload_register('Vtiger_Loader::autoLoad') を仕掛けてしまうため、
// PHPUnit のクラス探索時に Vtiger 側の require が走って落ちる。
// テスト時はこれを外し、必要な Vtiger クラスはテスト側で明示 require する。
if (class_exists('Vtiger_Loader', false)) {
    spl_autoload_unregister(['Vtiger_Loader', 'autoLoad']);
}

require_once $projectRoot . '/includes/runtime/Globals.php';
require_once $projectRoot . '/include/utils/utils.php';
require_once $projectRoot . '/include/database/PearDatabase.php';

// 上の require で include/logging.php が config.php を読み、config.php は
// config.inc.php を include_once ではなく include で読み直す。この bootstrap が
// ファイルスコープで読み込まれた場合（PHPUnit のプロセス分離で作られる子プロセスなど）は
// その再読み込みがグローバルの $dbconfig へ届き、接続先が開発用DBへ戻ってしまう。
// テスト用DBの指定を読み直しの後にもう一度あてて、接続をやり直す。
$GLOBALS['dbconfig']['db_name'] = $testDbName;
if ($envDbHost !== false && $envDbHost !== '') {
    $GLOBALS['dbconfig']['db_server'] = $envDbHost;
}
if ($envDbUsername !== false && $envDbUsername !== '') {
    $GLOBALS['dbconfig']['db_username'] = $envDbUsername;
}
if ($envDbPassword !== false) {
    $GLOBALS['dbconfig']['db_password'] = $envDbPassword;
}
$GLOBALS['dbconfig']['db_hostname'] = $dbConfigString($GLOBALS['dbconfig'], 'db_server')
    . $dbConfigString($GLOBALS['dbconfig'], 'db_port');
try {
    $bootstrapReconnect = PearDatabase::getInstance();
    $bootstrapReconnect->resetSettings('', '', '', '', '');
    $bootstrapReconnect->connect();
} catch (\Throwable $bootstrapReconnectEx) {
    // DB未接続環境はテスト個別で扱う。bootstrap では強制終了しない。
}
unset($bootstrapReconnect, $bootstrapReconnectEx);

// 安全装置: 上記の $dbconfig 上書きは PearDatabase 内部の config.inc.php 直読みで
// 効かない場合があり、開発用DBに接続して TRUNCATE が走る危険がある。
// PearDatabase 初期化後に SELECT DATABASE() で実接続先を取得し _test suffix を強制する。
try {
    $bootstrapDb = PearDatabase::getInstance();
    $bootstrapRow = $bootstrapDb->pquery('SELECT DATABASE() AS dbname', []);
    $bootstrapActualDb = ($bootstrapRow !== false && $bootstrapDb->num_rows($bootstrapRow) === 1)
        ? (string)$bootstrapDb->query_result($bootstrapRow, 0, 'dbname')
        : '';
} catch (\Throwable $bootstrapEx) {
    // DB未接続環境はテスト個別で扱う。bootstrap では強制終了しない。
    $bootstrapActualDb = '';
}
if ($bootstrapActualDb !== '' && !preg_match('/_test$/', $bootstrapActualDb)) {
    fwrite(STDERR, "FATAL: テストの実接続先DB ({$bootstrapActualDb}) が _test suffix ではありません。中止します。\n");
    exit(1);
}
unset($bootstrapDb, $bootstrapRow, $bootstrapActualDb, $bootstrapEx);
