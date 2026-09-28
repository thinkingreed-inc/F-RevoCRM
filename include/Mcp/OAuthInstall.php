<?php
/**
 * MCP OAuth 2.1 テーブルインストーラー
 *
 * 3テーブルを CREATE TABLE IF NOT EXISTS で作成（冪等）
 * F-revoブート済み環境で使用（PearDatabase必須）
 */
class Mcp_OAuthInstall
{
	/**
	 * OAuthテーブル3つを作成
	 *
	 * @return array テーブル名 => 結果('OK' or 'ERROR: ...')
	 */
	public static function install(): array
	{
		$db = PearDatabase::getInstance();
		$results = [];

		$ddls = self::getDDLs();

		foreach ($ddls as $table => $sql) {
			try {
				$db->pquery($sql, []);
				$results[$table] = 'OK';
			} catch (\Exception $e) {
				$results[$table] = 'ERROR: ' . $e->getMessage();
			}
		}

		return $results;
	}

	/**
	 * DDL一覧取得
	 *
	 * @return array テーブル名 => CREATE TABLE SQL
	 */
	public static function getDDLs(): array
	{
		return [
			'vtiger_mcp_oauth_client' => "
				CREATE TABLE IF NOT EXISTS vtiger_mcp_oauth_client (
					id INT AUTO_INCREMENT PRIMARY KEY,
					client_id VARCHAR(128) NOT NULL COMMENT 'DCR発行のランダムID',
					client_secret_hash VARCHAR(64) NULL COMMENT 'client_secretのSHA-256（publicクライアントはNULL）',
					client_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'クライアント表示名',
					redirect_uris TEXT NOT NULL COMMENT 'JSON配列: 登録済みredirect_uri',
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					UNIQUE KEY idx_client_id (client_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP OAuth DCR登録クライアント'
			",

			'vtiger_mcp_oauth_code' => "
				CREATE TABLE IF NOT EXISTS vtiger_mcp_oauth_code (
					id INT AUTO_INCREMENT PRIMARY KEY,
					code_hash CHAR(64) NOT NULL COMMENT '認可コードのSHA-256',
					client_id VARCHAR(128) NOT NULL COMMENT '発行先client_id',
					userid INT NOT NULL COMMENT 'F-revoユーザーID',
					code_challenge VARCHAR(128) NOT NULL COMMENT 'PKCE code_challenge (S256)',
					redirect_uri TEXT NOT NULL COMMENT '認可時のredirect_uri',
					scope VARCHAR(255) NOT NULL DEFAULT '' COMMENT '認可スコープ',
					expires_at DATETIME NOT NULL COMMENT '有効期限（発行後10分）',
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					UNIQUE KEY idx_code_hash (code_hash)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP OAuth 認可コード（短命・使い捨て）'
			",

			'vtiger_mcp_oauth_token' => "
				CREATE TABLE IF NOT EXISTS vtiger_mcp_oauth_token (
					id INT AUTO_INCREMENT PRIMARY KEY,
					access_token_hash CHAR(64) NOT NULL COMMENT 'access_tokenのSHA-256',
					refresh_token_hash CHAR(64) NOT NULL COMMENT 'refresh_tokenのSHA-256',
					client_id VARCHAR(128) NOT NULL COMMENT '発行先client_id',
					userid INT NOT NULL COMMENT 'F-revoユーザーID',
					scope VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'トークンスコープ',
					expires_at DATETIME NOT NULL COMMENT 'access_token有効期限（1時間）',
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					UNIQUE KEY idx_access_hash (access_token_hash),
					KEY idx_refresh_hash (refresh_token_hash),
					KEY idx_userid (userid)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP OAuth アクセス/リフレッシュトークン'
			",
		];
	}
}
