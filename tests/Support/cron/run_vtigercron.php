<?php

/**
 * vtigercron.php を「テスト用DB へ接続した状態」で実行するための入口。
 *
 * vtigercron.php を直接起動すると config.inc.php をそのまま読み、開発用DB へ
 * 接続してしまう。ここでは先に tests/bootstrap.php を通し、$dbconfig の接続先を
 * テスト用DB へ差し替えてから vtigercron.php を読み込む。
 *
 * bootstrap は必ず関数の中で読み込むこと。include/logging.php が読む config.php が
 * config.inc.php を include_once ではなく include で読み直すため、ファイルスコープで
 * 通すとグローバルの $dbconfig が開発用DB の値へ上書きされてしまう。関数の中なら
 * 上書きは関数ローカルに閉じ、bootstrap が $GLOBALS へ入れたテスト用DB の設定が残る。
 * PHPUnit が bootstrap を関数スコープで読み込むのと同じ理屈。
 *
 * 引数はそのまま vtigercron.php へ渡す。
 *   php tests/Support/cron/run_vtigercron.php --serial
 */

(static function (): void {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
})();

$projectRoot = dirname(__DIR__, 3);

// 逐次実行（--serial）はタスクごとに子プロセスを起こし、振り分けモードも子プロセスを使う。
// それらの子も vtigercron.php を直接起動するため、放っておくと開発用DB へ接続してしまう。
// 起動に使う PHP をラッパーへ差し替えて、孫プロセスまでテスト用DB を指すようにする。
$GLOBALS['cron_php_binary'] = __DIR__ . '/php_test_db.sh';
putenv('FREVOCRM_TEST_PHP=' . PHP_BINARY);

// vtigercron.php は array_slice($argv, 1) で引数を読むため、
// 実行時と同じく先頭にスクリプト名を置いた形へ組み立て直す。
$argv = array_merge([$projectRoot . '/vtigercron.php'], array_slice($argv, 1));
$argc = count($argv);
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;

require $projectRoot . '/vtigercron.php';
