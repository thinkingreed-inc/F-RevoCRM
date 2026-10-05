<?php
/**
 * MCP Token Authentication
 *
 * Bearer token -> vtiger_mcp_token -> F-revo user ID
 * Token is SHA-256 hashed in DB for security.
 */
class Mcp_TokenAuth
{
    /** トークン接頭辞。書式は frevo_pat_ + 64hex */
    public const TOKEN_PREFIX = 'frevo_pat_';
    /**
     * Authenticate a Bearer token and return the user ID.
     *
     * @param string $bearerToken Raw token from Authorization header
     * 有効期限内・有効なトークンのみ通す。
     * @return int User ID
     * @throws Exception On invalid/missing/disabled token
     */
    public static function authenticate(string $bearerToken): int
    {
        if ($bearerToken === '') {
            throw new Exception('Missing Bearer token');
        }

        $hash = hash('sha256', $bearerToken);
        $db = PearDatabase::getInstance();

        $result = $db->pquery(
            'SELECT userid FROM vtiger_mcp_token WHERE token_hash = ? AND enabled = 1',
            [$hash]
        );

        if (!$result || $db->num_rows($result) === 0) {
            throw new Exception('Invalid or disabled token');
        }

        return (int) $db->query_result($result, 0, 'userid');
    }

    /**
     * 最終使用日時を更新する。
     * 接続確立(initialize)時のみ呼ぶ。tools/call ごとに更新すると同一行への書き込みが集中する。
     *
     * @param string $bearerToken Authorization ヘッダの生トークン
     */
    public static function touchLastUsed(string $bearerToken): void
    {
        if ($bearerToken === '') {
            return;
        }
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE vtiger_mcp_token SET last_used_at = NOW() WHERE token_hash = ?',
            [hash('sha256', $bearerToken)]
        );
    }

    /**
     * Generate a new token and insert into DB.
     * Returns the raw token (show once to user, never stored in plaintext).
     *
     * @param int    $userid F-revo user ID
     * @param string $label  Human-readable label
     * @return string Raw token (64 hex chars)
     */
    public static function generateToken(int $userid, string $label = ''): string
    {
        $raw = bin2hex(random_bytes(32)); // 64 hex chars
        $hash = hash('sha256', $raw);
        $db = PearDatabase::getInstance();

        $db->pquery(
            'INSERT INTO vtiger_mcp_token (token_hash, userid, label, enabled, created_at) VALUES (?, ?, ?, 1, NOW())',
            [$hash, $userid, $label]
        );

        return $raw;
    }
}
