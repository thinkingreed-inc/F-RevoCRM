<?php
/**
 * MCPトークン管理 - 手動インストールスクリプト
 *
 * vtlibパッケージインポートが使えない場合の手動セットアップ用。
 * CLI: php -f modules/Settings/MCPTokens/install.php
 * または F-revoCRM のルートディレクトリから実行。
 *
 * 処理内容:
 *   1) vtiger_mcp_token テーブルを CREATE TABLE IF NOT EXISTS
 *   2) vtiger_settings_field にメニュー項目を登録
 *
 * ===【注意】このスクリプトは取締役(親)が検証用に実行するもの===
 */

// F-revoCRM ブートストラップ
if (php_sapi_name() === 'cli') {
	chdir(dirname(__FILE__) . '/../../..');
}

require_once 'config.inc.php';
if (file_exists('config_override.php')) {
	include_once 'config_override.php';
}
require_once 'include/utils/utils.php';
vimport('includes.runtime.EntryPoint');

global $current_user;
$current_user = Users::getActiveAdminUser();

$db = PearDatabase::getInstance();

echo "=== MCPトークン管理モジュール インストール ===\n\n";

// -----------------------------------------------------------
// 1) vtiger_mcp_token テーブル作成
// -----------------------------------------------------------
echo "[Step 1] vtiger_mcp_token テーブル作成...\n";
$db->pquery("CREATE TABLE IF NOT EXISTS vtiger_mcp_token (
	id          INT AUTO_INCREMENT PRIMARY KEY,
	token_hash  CHAR(64)     NOT NULL COMMENT 'SHA-256 hash of the Bearer token',
	userid      INT          NOT NULL COMMENT 'F-revo user ID (vtiger_users.id)',
	label       VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Human-readable label',
	enabled     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=active, 0=disabled',
	created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY idx_token_hash (token_hash),
	KEY idx_userid (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP Bearer token to F-revo user mapping'", array());
echo "  完了（既存の場合はスキップ）\n\n";

// -----------------------------------------------------------
// 2) vtiger_settings_field にメニュー登録
// -----------------------------------------------------------
echo "[Step 2] 設定メニューに登録...\n";

// 重複チェック
$existCheck = $db->pquery(
	"SELECT fieldid FROM vtiger_settings_field WHERE name = ?",
	array('LBL_MCP_TOKENS')
);
if ($db->num_rows($existCheck) > 0) {
	echo "  既に登録済み（スキップ）\n";
} else {
	// vtiger_settings_blocks の一覧を表示（blockid確認用）
	echo "  利用可能な設定ブロック:\n";
	$blocksResult = $db->pquery("SELECT blockid, label, sequence FROM vtiger_settings_blocks ORDER BY sequence", array());
	$blocksCount = $db->num_rows($blocksResult);
	for ($i = 0; $i < $blocksCount; $i++) {
		printf("    blockid=%d  label=%s  sequence=%d\n",
			$db->query_result($blocksResult, $i, 'blockid'),
			$db->query_result($blocksResult, $i, 'label'),
			$db->query_result($blocksResult, $i, 'sequence')
		);
	}

	// 「セキュリティ管理」ブロックを優先的に使用
	$blockResult = $db->pquery(
		"SELECT blockid FROM vtiger_settings_blocks WHERE label = ? LIMIT 1",
		array('LBL_SECURITY_MANAGEMENT')
	);
	if ($db->num_rows($blockResult) > 0) {
		$blockId = $db->query_result($blockResult, 0, 'blockid');
		echo "  → LBL_SECURITY_MANAGEMENT ブロック(blockid={$blockId})を使用\n";
	} else {
		// フォールバック: 最後のブロック
		$blockResult = $db->pquery(
			"SELECT blockid FROM vtiger_settings_blocks ORDER BY sequence DESC LIMIT 1",
			array()
		);
		$blockId = $db->query_result($blockResult, 0, 'blockid');
		echo "  → セキュリティ管理ブロック未検出。blockid={$blockId} を使用\n";
	}

	// sequenceの最大値+1
	$seqResult = $db->pquery(
		"SELECT COALESCE(MAX(sequence), 0) AS maxseq FROM vtiger_settings_field WHERE blockid = ?",
		array($blockId)
	);
	$sequence = (int) $db->query_result($seqResult, 0, 'maxseq') + 1;

	// fieldidの最大値+1
	$fidResult = $db->pquery(
		"SELECT COALESCE(MAX(fieldid), 0) AS maxfid FROM vtiger_settings_field",
		array()
	);
	$newFieldId = (int) $db->query_result($fidResult, 0, 'maxfid') + 1;

	$db->pquery(
		"INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
		array(
			$newFieldId,
			$blockId,
			'LBL_MCP_TOKENS',
			'',
			'MCPトークンの発行・一覧・無効化を管理します',
			'index.php?module=MCPTokens&parent=Settings&view=List',
			$sequence,
			0,
			0,
		)
	);
	echo "  登録完了 (fieldid={$newFieldId}, blockid={$blockId}, sequence={$sequence})\n";
}

echo "\n=== インストール完了 ===\n";
echo "URL: index.php?module=MCPTokens&parent=Settings&view=List\n";
echo "設定 > 該当ブロックに「MCPトークン管理」が表示されます。\n";
