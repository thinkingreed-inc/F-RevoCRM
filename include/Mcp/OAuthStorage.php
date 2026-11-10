<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/
/**
 * MCP OAuth 2.1 DBストレージ
 *
 * vtiger_mcp_oauth_client / code / token テーブルの CRUD
 * F-revoブート済み環境で使用（PearDatabase必須）
 */
class Mcp_OAuthStorage
{
    /** アクセストークンの有効期限（秒） */
    public const ACCESS_TOKEN_TTL_SECONDS = 3600;

    /** リフレッシュトークンの絶対有効期限（日）。清掃時の保持日数にも使う */
    public const REFRESH_TOKEN_TTL_DAYS = 30;

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
        $result = $db->pquery(
            'INSERT INTO vtiger_mcp_oauth_client (client_id, client_secret_hash, client_name, redirect_uris, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$clientId, $clientSecretHash, $clientName, json_encode($redirectUris, JSON_UNESCAPED_SLASHES)]
        );
        if ($result === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_CLIENT_REGISTER_FAILED', 'Settings:MCPOAuthApps'));
        }
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
        $result = $db->pquery(
            'INSERT INTO vtiger_mcp_oauth_code (code_hash, client_id, userid, code_challenge, redirect_uri, scope, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$codeHash, $clientId, $userid, $codeChallenge, $redirectUri, $scope, $expiresAt]
        );
        if ($result === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_CODE_SAVE_FAILED', 'Settings:MCPOAuthApps'));
        }
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

        // 同じクライアント・同じ利用者の既存の連携は失効させてから発行する。
        // 有効な行が1本であることをこの順序で担保する（一意制約は軟削除と両立しないため張らない）。
        $disabled = $db->pquery(
            'UPDATE vtiger_mcp_oauth_token SET enabled = 0 WHERE client_id = ? AND userid = ? AND enabled = 1',
            [$clientId, $userid]
        );
        if ($disabled === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_REVOKE_EXISTING_FAILED', 'Settings:MCPOAuthApps'));
        }

        $result = $db->pquery(
            'INSERT INTO vtiger_mcp_oauth_token (access_token_hash, refresh_token_hash, client_id, userid, scope, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$accessTokenHash, $refreshTokenHash, $clientId, $userid, $scope, $expiresAt]
        );
        if ($result === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_TOKEN_ISSUE_FAILED', 'Settings:MCPOAuthApps'));
        }
    }

    /**
     * アクセストークンハッシュで検索（有効期限・失効チェック付き）
     *
     * @param string $accessTokenHash  access_token の SHA-256 ハッシュ
     * @return array|null              有効なトークン行、無ければnull
     */
    public static function getTokenByAccessHash(string $accessTokenHash): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT * FROM vtiger_mcp_oauth_token WHERE access_token_hash = ? AND enabled = 1 AND expires_at > NOW()',
            [$accessTokenHash]
        );
        if (!$result || $db->num_rows($result) === 0) {
            return null;
        }
        return $db->fetchByAssoc($result);
    }

    /**
     * リフレッシュトークンハッシュで検索（失効チェック付き）
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
            'SELECT * FROM vtiger_mcp_oauth_token WHERE refresh_token_hash = ? AND enabled = 1 AND created_at > DATE_SUB(NOW(), INTERVAL ' . (int) self::REFRESH_TOKEN_TTL_DAYS . ' DAY)',
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
        $result = $db->pquery(
            'UPDATE vtiger_mcp_oauth_token SET access_token_hash = ?, refresh_token_hash = ?, expires_at = ? WHERE id = ?',
            [$newAccessTokenHash, $newRefreshTokenHash, $newExpiresAt, $id]
        );
        if ($result === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_TOKEN_REFRESH_FAILED', 'Settings:MCPOAuthApps'));
        }
    }

    // ================================================================
    //  連携（トークン）の一覧・失効
    // ================================================================

    /**
     * 利用者の有効な連携一覧を取得する（新しい順）。
     *
     * @param int $userid F-revoユーザーID
     * @return array<int, array<string, mixed>>
     */
    public static function listByUser(int $userid): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT t.id, t.client_id, c.client_name, t.created_at
			   FROM vtiger_mcp_oauth_token t
			   LEFT JOIN vtiger_mcp_oauth_client c ON c.client_id = t.client_id
			  WHERE t.userid = ? AND t.enabled = 1
			  ORDER BY t.created_at DESC',
            [$userid]
        );
        if ($result === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_LIST_FAILED', 'Settings:MCPOAuthApps'));
        }

        $rows = [];
        $num = $db->num_rows($result);
        for ($i = 0; $i < $num; $i++) {
            $rows[] = [
                'id'          => (int) $db->query_result($result, $i, 'id'),
                'client_id'   => $db->query_result($result, $i, 'client_id'),
                'client_name' => $db->query_result($result, $i, 'client_name'),
                'created_at'  => $db->query_result($result, $i, 'created_at'),
            ];
        }
        return $rows;
    }

    /**
     * 連携1件を取得する（解除時の所有者確認に使う）。
     *
     * @param int $id vtiger_mcp_oauth_token.id
     * @return array<string, mixed>|null 見つからなければ null
     */
    public static function getById(int $id): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT id, userid, enabled FROM vtiger_mcp_oauth_token WHERE id = ?', [$id]);
        if (!$result || $db->num_rows($result) === 0) {
            return null;
        }
        return [
            'id'      => (int) $db->query_result($result, 0, 'id'),
            'userid'  => (int) $db->query_result($result, 0, 'userid'),
            'enabled' => (int) $db->query_result($result, 0, 'enabled'),
        ];
    }

    /**
     * 連携を失効させる（物理削除ではなく enabled=0）。
     *
     * @param int $id vtiger_mcp_oauth_token.id
     */
    public static function disable(int $id): void
    {
        require_once 'include/Mcp/TokenCommon.php';
        mcp_disable_token_row('vtiger_mcp_oauth_token', $id, vtranslate('LBL_MCP_OAUTH_REVOKE_FAILED', 'Settings:MCPOAuthApps'));
    }

    /**
     * トークンのハッシュで連携を失効させる（失効端点用）。
     * access / refresh のどちらのハッシュでも引ける。
     *
     * @param string $tokenHash トークンの SHA-256 ハッシュ
     * @return int 失効させた行数
     */
    public static function disableByTokenHash(string $tokenHash): int
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'UPDATE vtiger_mcp_oauth_token SET enabled = 0
			  WHERE (access_token_hash = ? OR refresh_token_hash = ?) AND enabled = 1',
            [$tokenHash, $tokenHash]
        );
        if ($result === false) {
            throw new Exception(vtranslate('LBL_MCP_OAUTH_REVOKE_FAILED', 'Settings:MCPOAuthApps'));
        }
        return (int) $db->getAffectedRowCount($result);
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
            'DELETE FROM vtiger_mcp_oauth_token WHERE expires_at < DATE_SUB(NOW(), INTERVAL ' . (int) self::REFRESH_TOKEN_TTL_DAYS . ' DAY)',
            []
        );
    }
}
