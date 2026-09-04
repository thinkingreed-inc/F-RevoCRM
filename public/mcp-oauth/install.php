<?php
/**
 * MCP OAuth テーブルインストールスクリプト
 *
 * 使い方:
 *   CLI:  php public/mcp-oauth/install.php
 *   Web:  http://localhost/ct/public/mcp-oauth/install.php?confirm=yes
 *
 * 3テーブルを CREATE TABLE IF NOT EXISTS で作成（冪等・安全に再実行可能）
 */

// F-revoブート
chdir(dirname(__DIR__, 2));
require_once 'config.inc.php';
if (file_exists('config_override.php')) {
	include_once 'config_override.php';
}
require_once 'vendor/autoload.php';
require_once 'include/utils/CommonUtils.php';
vimport('includes.runtime.EntryPoint');

require_once 'include/Mcp/OAuthInstall.php';

// CLI以外の場合は確認パラメータ必須
$isCli = (php_sapi_name() === 'cli');
if (!$isCli && ($_GET['confirm'] ?? '') !== 'yes') {
	header('Content-Type: text/plain; charset=utf-8');
	echo "MCP OAuth テーブルインストーラー\n";
	echo "================================\n\n";
	echo "テーブル作成を実行するには ?confirm=yes を付けてアクセスしてください。\n";
	echo "例: http://localhost/ct/public/mcp-oauth/install.php?confirm=yes\n\n";

	echo "作成されるテーブル:\n";
	foreach (Mcp_OAuthInstall::getDDLs() as $table => $sql) {
		echo "  - {$table}\n";
	}
	exit;
}

// インストール実行
$results = Mcp_OAuthInstall::install();

// 結果出力
if ($isCli) {
	echo "MCP OAuth テーブルインストール結果:\n";
	foreach ($results as $table => $status) {
		echo "  {$table}: {$status}\n";
	}
} else {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode([
		'action'  => 'MCP OAuth Install',
		'results' => $results,
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
