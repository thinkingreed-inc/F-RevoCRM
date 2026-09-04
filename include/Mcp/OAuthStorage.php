<?php
/**
 * MCP OAuth 2.1 DBストレージ
 *
 * vtiger_mcp_oauth_client / code / token テーブルの CRUD
 * F-revoブート済み環境で使用（PearDatabase必須）
 */
class Mcp_OAuthStorage
{
	// ================================================================
	//  クライアント操作 (DCR)
	// ================================================================

	/**
	 * クライアント登録
	 *
	 * @param string      $clientId         ランダム生成されたclient_id
	 * @param string|null $clientSecretHash  client_secret の SHA-256 ハッシュ（public clientはnull）
	 * @param string      $clientName       表示名
	 * @param array       $redirectUris     登録するredirect_uriの配列
	 */
	public static function createClient(
		string $clientId,
		?string $clientSecretHash,
		string $clientName,
		array $redirectUris
	): void {
		$db = PearDatabase::getInstance();
		$db->pquery(
			'INSERT INTO vtiger_mcp_oauth_client (client_id, client_secret_hash, client_name, redirect_uris, created_at) VALUES (?, ?, ?, ?, NOW())',
			[$clientId, $clientSecretHash, $clientName, json_encode($redirectUris, JSON_UNESCAPED_SLASHES)]
		);
	}

	/**
	 * client_id でクライアント取得
	 *
	 * @return array|null  見つかった場合は行データ + redirect_uris_array キー追加
	 */
	public static function getClient(string $clientId): ?array
	{
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT * FROM vtiger_mcp_oauth_client WHERE client_id = ?',
			[$clientId]
		);
		if (!$result || $db->num_rows($result) === 0) {
			return null;
		}
		$row = $db->fetchByAssoc($result);
		// vtiger の PearDatabase は読み出し時に値をHTMLエンコードする("→&quot;)ため、
		// JSONをパースする前に html_entity_decode で元に戻す。
		$redirectUrisJson = html_entity_decode($row['redirect_uris'], ENT_QUOTES, 'UTF-8');
		$row['redirect_uris_array'] = json_decode($redirectUrisJson, true) ?: [];
		return $row;
	}

	// ================================================================
	//  認可コード操作
	// ================================================================

	/**
	 * 認可コード保存
	 *
	 * @param string $codeHash      code の SHA-256 ハッシュ
	 * @param string $clientId      クライアントID
	 * @param int    $userid        F-revoユーザーID
	 * @param string $codeChallenge PKCE code_challenge（クライアント送信値をそのまま保存）
	 * @param string $redirectUri   コード発行時のredirect_uri
	 * @param string $scope         スコープ
	 * @param string $expiresAt     有効期限（Y-m-d H:i:s形式）
	 */
	public static function createAuthCode(
		string $codeHash,
		string $clientId,
		int $userid,
		string $codeChallenge,
		string $redirectUri,
		string $scope,
		string $expiresAt
	): void {
		$db = PearDatabase::getInstance();
		$db->pquery(
			'INSERT INTO vtiger_mcp_oauth_code (code_hash, client_id, userid, code_challenge, redirect_uri, scope, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
			[$codeHash, $clientId, $userid, $codeChallenge, $redirectUri, $scope, $expiresAt]
		);
	}

	/**
	 * 認可コード消費（取得 + 即座に削除、1回使い捨て）
	 *
	 * @param string $codeHash  code の SHA-256 ハッシュ
	 * @return array|null       見つかった場合は行データ
	 */
	public static function consumeAuthCode(string $codeHash): ?array
	{
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT * FROM vtiger_mcp_oauth_code WHERE code_hash = ?',
			[$codeHash]
		);
		if (!$result || $db->num_rows($result) === 0) {
			return null;
		}
		$row = $db->fetchByAssoc($result);

		// 1回使い捨て: DELETE の影響行数で「自分が消した」ことを保証する(TOCTOU/二重消費緩和)。
		// 同一コードの同時リクエストでは片方のDELETEのみ affected=1 となり、もう片方は null を返す。
		$del = $db->pquery('DELETE FROM vtiger_mcp_oauth_code WHERE code_hash = ?', [$codeHash]);
		if (!$del || (int) $db->getAffectedRowCount($del) < 1) {
			return null;
		}

		return $row;
	}

	// ================================================================
	//  トークン操作
	// ================================================================

	/**
	 * アクセストークン＋リフレッシュトークン保存
	 *
	 * @param string $accessTokenHash  access_token の SHA-256 ハッシュ
	 * @param string $refreshTokenHash refresh_token の SHA-256 ハッシュ
	 * @param string $clientId         クライアントID
	 * @param int    $userid           F-revoユーザーID
	 * @param string $scope            スコープ
	 * @param string $expiresAt        access_tokenの有効期限（Y-m-d H:i:s形式）
	 */
	public static function createToken(
		string $accessTokenHash,
		string $refreshTokenHash,
		string $clientId,
		int $userid,
		string $scope,
		string $expiresAt
	): void {
		$db = PearDatabase::getInstance();
		$db->pquery(
			'INSERT INTO vtiger_mcp_oauth_token (access_token_hash, refresh_token_hash, client_id, userid, scope, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
			[$accessTokenHash, $refreshTokenHash, $clientId, $userid, $scope, $expiresAt]
		);
	}

	/**
	 * アクセストークンハッシュで検索（有効期限チェック付き）
	 *
	 * @param string $accessTokenHash  access_token の SHA-256 ハッシュ
	 * @return array|null              有効なトークン行、無ければnull
	 */
	public static function getTokenByAccessHash(string $accessTokenHash): ?array
	{
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'SELECT * FROM vtiger_mcp_oauth_token WHERE access_token_hash = ? AND expires_at > NOW()',
			[$accessTokenHash]
		);
		if (!$result || $db->num_rows($result) === 0) {
			return null;
		}
		return $db->fetchByAssoc($result);
	}

	/**
	 * リフレッシュトークンハッシュで検索（期限なし＝リフレッシュトークン自体は無期限）
	 *
	 * @param string $refreshTokenHash  refresh_token の SHA-256 ハッシュ
	 * @return array|null               トークン行、無ければnull
	 */
	public static function getTokenByRefreshHash(string $refreshTokenHash): ?array
	{
		$db = PearDatabase::getInstance();
		// refresh_token の絶対有効期限: 初回発行(created_at)から30日。
		// rotateToken は created_at を更新しないため、リフレッシュを繰り返しても
		// 初回認可から30日でセッションは失効し、再認可が必要になる(漏洩トークンの恒久利用を防止)。
		$result = $db->pquery(
			'SELECT * FROM vtiger_mcp_oauth_token WHERE refresh_token_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)',
			[$refreshTokenHash]
		);
		if (!$result || $db->num_rows($result) === 0) {
			return null;
		}
		return $db->fetchByAssoc($result);
	}

	/**
	 * トークンローテーション
	 * リフレッシュ時にaccess_token/refresh_token/expires_atを全更新
	 *
	 * @param int    $id                  行ID
	 * @param string $newAccessTokenHash  新しいaccess_tokenハッシュ
	 * @param string $newRefreshTokenHash 新しいrefresh_tokenハッシュ
	 * @param string $newExpiresAt        新しいaccess_token有効期限
	 */
	public static function rotateToken(
		int $id,
		string $newAccessTokenHash,
		string $newRefreshTokenHash,
		string $newExpiresAt
	): void {
		$db = PearDatabase::getInstance();
		$db->pquery(
			'UPDATE vtiger_mcp_oauth_token SET access_token_hash = ?, refresh_token_hash = ?, expires_at = ? WHERE id = ?',
			[$newAccessTokenHash, $newRefreshTokenHash, $newExpiresAt, $id]
		);
	}

	/**
	 * 期限切れデータのクリーンアップ
	 * - 認可コード: expires_at 過ぎたら即削除
	 * - トークン: access_token期限切れ後30日経過で削除（リフレッシュ猶予）
	 */
	public static function cleanup(): void
	{
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_mcp_oauth_code WHERE expires_at < NOW()', []);
		$db->pquery(
			'DELETE FROM vtiger_mcp_oauth_token WHERE expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY)',
			[]
		);
	}
}
