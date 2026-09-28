<?php
/**
 * MCPトークン管理 - vtlib_handler (インストール/アンインストールハンドラ)
 *
 * vtlib パッケージインポート時に自動呼び出しされるハンドラ。
 *
 * インストール時:
 *   1) vtiger_mcp_token テーブルを CREATE TABLE IF NOT EXISTS
 *   2) vtiger_settings_field に「MCPトークン管理」メニュー項目を登録
 *
 * アンインストール時:
 *   1) vtiger_settings_field の該当行を削除
 *   2) vtiger_mcp_token テーブルは残す（トークン消滅を防ぐ方針）
 */

class MCPTokens {

	/**
	 * vtlib_handler: モジュールインストール時に呼ばれる
	 * @param string $moduleName
	 */
	function vtlib_handler($moduleName, $event_type) {
		if ($event_type === 'module.postinstall') {
			$this->postInstall();
		} elseif ($event_type === 'module.disabled') {
			// 無効化時は特に何もしない
		} elseif ($event_type === 'module.enabled') {
			// 有効化時は特に何もしない
		} elseif ($event_type === 'module.preuninstall') {
			$this->preUninstall();
		} elseif ($event_type === 'module.preupdate') {
			// アップデート前
		} elseif ($event_type === 'module.postupdate') {
			// アップデート後
		}
	}

	/**
	 * インストール後処理
	 */
	private function postInstall() {
		$db = PearDatabase::getInstance();

		// -----------------------------------------------------------
		// 1) vtiger_mcp_token テーブルを作成（既存なら何もしない）
		// -----------------------------------------------------------
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

		// -----------------------------------------------------------
		// 2) vtiger_settings_field にメニュー登録
		// -----------------------------------------------------------
		// まず重複チェック
		$existCheck = $db->pquery(
			"SELECT fieldid FROM vtiger_settings_field WHERE name = ?",
			array('LBL_MCP_TOKENS')
		);
		if ($db->num_rows($existCheck) > 0) {
			// 既に登録済み
			return;
		}

		// 「セキュリティ管理」ブロックのblockidを取得
		// F-revoCRM 7.x では label='LBL_SECURITY_MANAGEMENT' が一般的
		// 見つからなければ最初のブロックにフォールバック
		$blockResult = $db->pquery(
			"SELECT blockid FROM vtiger_settings_blocks WHERE label = ? LIMIT 1",
			array('LBL_SECURITY_MANAGEMENT')
		);
		if ($db->num_rows($blockResult) > 0) {
			$blockId = $db->query_result($blockResult, 0, 'blockid');
		} else {
			// フォールバック: 最初のブロックを使う
			$blockResult = $db->pquery(
				"SELECT blockid FROM vtiger_settings_blocks ORDER BY sequence LIMIT 1",
				array()
			);
			$blockId = $db->query_result($blockResult, 0, 'blockid');
		}

		// sequence の最大値を取得して +1
		$seqResult = $db->pquery(
			"SELECT COALESCE(MAX(sequence), 0) AS maxseq FROM vtiger_settings_field WHERE blockid = ?",
			array($blockId)
		);
		$sequence = (int) $db->query_result($seqResult, 0, 'maxseq') + 1;

		// fieldid の最大値を取得して +1
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
				'LBL_MCP_TOKENS',                                                   // name（翻訳キーまたは表示名）
				'',                                                                   // iconpath
				'MCPトークンの発行・一覧・無効化を管理します',                                // description
				'index.php?module=MCPTokens&parent=Settings&view=List',              // linkto
				$sequence,
				0,                                                                    // active (0=表示)
				0,                                                                    // pinned
			)
		);
	}

	/**
	 * アンインストール前処理
	 * vtiger_mcp_token テーブルは残す（トークンデータ消滅防止）
	 */
	private function preUninstall() {
		$db = PearDatabase::getInstance();

		// vtiger_settings_field からメニュー項目を削除
		$db->pquery(
			"DELETE FROM vtiger_settings_field WHERE name = ?",
			array('LBL_MCP_TOKENS')
		);

		// 注意: vtiger_mcp_token テーブルは意図的に削除しない
		// トークンデータの消滅を防ぐため。再インストール時は既存データがそのまま使える。
	}
}
