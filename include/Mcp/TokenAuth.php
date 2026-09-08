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
    const TOKEN_PREFIX = 'frevo_pat_';

    /**
     * Authenticate a Bearer token and return the user ID.
     *
     * 有効期限内・有効なトークンのみ通す。成功時に最終使用日時を更新する。
     *
     * @param string $bearerToken Authorization ヘッダの生トークン
     * @return int User ID
     * @throws Exception 無効・失効・期限切れ・不明なトークンのとき
     */
    public static function authenticate(string $bearerToken): int
    {
        if ($bearerToken === '') {
            throw new Exception('Missing Bearer token');
        }

        $hash = hash('sha256', $bearerToken);
        $db = PearDatabase::getInstance();

        // 有効期限を判定に含める（列を足すだけでは期限切れトークンが通ってしまうため）
        $result = $db->pquery(
            'SELECT id, userid FROM vtiger_mcp_token
             WHERE token_hash = ? AND enabled = 1
               AND (expires_at IS NULL OR expires_at > NOW())',
            [$hash]
        );

        if (!$result || $db->num_rows($result) === 0) {
            throw new Exception('Invalid or disabled token');
        }

        $id = (int) $db->query_result($result, 0, 'id');
        $userid = (int) $db->query_result($result, 0, 'userid');

        // 最終使用日時を更新する（間引きせず毎回更新する）
        $db->pquery('UPDATE vtiger_mcp_token SET last_used_at = NOW() WHERE id = ?', [$id]);

        return $userid;
    }

    /**
     * Generate a new token and insert into DB.
     * Returns the raw token (show once to user, never stored in plaintext).
     *
     * @param int      $userid      F-revo user ID
     * @param string   $label       用途名（識別用ラベル）
     * @param int|null $expiresDays 有効期限（日数）。null または 0 で無期限
     * @return string 生トークン（frevo_pat_ + 64hex）
     */
    public static function generateToken(int $userid, string $label = '', ?int $expiresDays = null): string
    {
        // 接頭辞込みの文字列を生成し、そのハッシュを保存する
        $raw = self::TOKEN_PREFIX . bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        // 接頭辞 + hex 先頭 4 文字を表示用に持つ
        $prefix = substr($raw, 0, strlen(self::TOKEN_PREFIX) + 4);

        // 有効期限は PHP 側で算出して束縛する（NULL = 無期限）
        $expiresAt = null;
        if ($expiresDays !== null && $expiresDays > 0) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) $expiresDays . ' days'));
        }

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'INSERT INTO vtiger_mcp_token
                (token_hash, userid, label, token_prefix, enabled, created_at, expires_at)
             VALUES (?, ?, ?, ?, 1, NOW(), ?)',
            [$hash, $userid, $label, $prefix, $expiresAt]
        );
        if ($result === false) {
            throw new Exception('トークンの発行に失敗しました');
        }

        return $raw;
    }
}
