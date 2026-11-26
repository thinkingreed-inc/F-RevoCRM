<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

namespace Tests\Integration\Mcp;

use Mcp_TokenAuth;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Tests\Support\CronTestSupport;

$root = dirname(__DIR__, 3);
require_once $root . '/include/database/PearDatabase.php';
require_once $root . '/include/Mcp/TokenAuth.php';
require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

/**
 * 固定 Bearer トークンの 発行 → 認証 → 期限切れ → 失効 をテスト用DB で通しで確かめる。
 *
 * McpServer は Bearer トークンを Mcp_TokenAuth::authenticate() でユーザーIDに解決し、
 * initialize のときだけ touchLastUsed() で最終使用日時を更新する。この 2 つを実際の
 * vtiger_mcp_token に対して呼び、発行・失効の結果と認証の可否が噛み合うことを固定する。
 *
 * 作成するトークンはラベルが LABEL_PREFIX で始まるものだけで、終了時に削除する。
 */
final class TokenAuthFlowTest extends TestCase
{
    use CronTestSupport;

    /** テスト用トークンのラベル接頭辞。後始末の対象を識別する */
    private const LABEL_PREFIX = 'TMCP_IT_';

    /** トークンを発行するユーザー（既存の結合テストと同じく管理ユーザーを使う） */
    private const USER_ID = 1;

    private PearDatabase $db;

    public static function setUpBeforeClass(): void
    {
        // 接続できないまま言語処理を読むと、スキップではなくエラーとして落ちるため先に確かめる
        self::skipUnlessTokenTableIsAvailable();

        self::enableVtigerAutoload();
        self::primeLanguageCache(['Settings:MCPTokens']);
    }

    public static function tearDownAfterClass(): void
    {
        self::disableVtigerAutoload();
    }

    protected function setUp(): void
    {
        $this->db = PearDatabase::getInstance();
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function test_issue_returns_plaintext_once_and_stores_only_hash_prefix_and_expiry(): void
    {
        $raw = Mcp_TokenAuth::generateToken(self::USER_ID, self::LABEL_PREFIX . 'issue', 30);

        $this->assertMatchesRegularExpression('/^frevo_pat_[0-9a-f]{64}$/', $raw, '生トークンは接頭辞 + 64hex');

        $row = $this->rowByHash(hash('sha256', $raw));
        $this->assertNotNull($row, '発行した行がハッシュで引ける');
        $this->assertSame(self::USER_ID, (int) $row['userid']);
        $this->assertSame(1, (int) $row['enabled']);
        $this->assertSame(substr($raw, 0, strlen(Mcp_TokenAuth::TOKEN_PREFIX) + 4), $row['token_prefix']);
        $this->assertNotContains($raw, array_values($row), '生トークンはどの列にも保存しない');
        $this->assertNull($row['last_used_at'], '発行直後は未使用');

        // 有効期限は発行時点から 30 日後（秒の境界をまたいでも通るよう幅を持たせる）
        $expectedExpiry = strtotime('+30 days');
        $this->assertEqualsWithDelta($expectedExpiry, strtotime((string) $row['expires_at']), 60);
        $this->assertSame(Mcp_TokenAuth::EXPIRY_ACTIVE, Mcp_TokenAuth::getExpiryState($row['expires_at']));
    }

    public function test_issued_token_authenticates_to_user_and_touch_updates_last_used_at(): void
    {
        $raw = Mcp_TokenAuth::generateToken(self::USER_ID, self::LABEL_PREFIX . 'auth', 30);

        $this->assertSame(self::USER_ID, Mcp_TokenAuth::authenticate($raw));

        Mcp_TokenAuth::touchLastUsed($raw);

        $row = $this->rowByHash(hash('sha256', $raw));
        $this->assertNotNull($row);
        $this->assertNotNull($row['last_used_at'], 'touchLastUsed で最終使用日時が入る');

        // タイムゾーンに依存しないよう、現在時刻との差は DB 内で計算する
        $result = $this->db->pquery(
            'SELECT TIMESTAMPDIFF(SECOND, last_used_at, NOW()) AS diff FROM vtiger_mcp_token WHERE id = ?',
            [(int) $row['id']]
        );
        $this->assertNotFalse($result);
        $diff = $this->db->query_result($result, 0, 'diff');
        $this->assertNotNull($diff);
        $this->assertGreaterThanOrEqual(0, (int) $diff, '最終使用日時は DB の現在時刻');
        $this->assertLessThanOrEqual(60, (int) $diff, '最終使用日時は DB の現在時刻');
    }

    public function test_token_without_expiry_authenticates(): void
    {
        $raw = Mcp_TokenAuth::generateToken(self::USER_ID, self::LABEL_PREFIX . 'noexpiry', 0);

        $row = $this->rowByHash(hash('sha256', $raw));
        $this->assertNotNull($row);
        $this->assertNull($row['expires_at'], '0 日は無期限（NULL）');
        $this->assertSame(self::USER_ID, Mcp_TokenAuth::authenticate($raw));
    }

    public function test_expired_token_is_rejected(): void
    {
        $raw = Mcp_TokenAuth::generateToken(self::USER_ID, self::LABEL_PREFIX . 'expired', 30);
        $hash = hash('sha256', $raw);

        // 期限切れを作る経路は無いため、テスト用DB 上で有効期限を過去にする
        $result = $this->db->pquery(
            'UPDATE vtiger_mcp_token SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE token_hash = ?',
            [$hash]
        );
        $this->assertNotFalse($result);

        $row = $this->rowByHash($hash);
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row['enabled'], '期限切れでも行は有効のまま');
        $this->assertSame(Mcp_TokenAuth::EXPIRY_EXPIRED, Mcp_TokenAuth::getExpiryState($row['expires_at']));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or disabled token');
        Mcp_TokenAuth::authenticate($raw);
    }

    public function test_disabled_token_is_rejected_and_row_remains_with_enabled_zero(): void
    {
        $raw = Mcp_TokenAuth::generateToken(self::USER_ID, self::LABEL_PREFIX . 'disabled', 30);
        $row = $this->rowByHash(hash('sha256', $raw));
        $this->assertNotNull($row);
        $id = (int) $row['id'];
        $this->assertSame(self::USER_ID, Mcp_TokenAuth::authenticate($raw), '失効前は認証できる');

        Mcp_TokenAuth::disable($id);

        $this->assertSame(
            ['id' => $id, 'userid' => self::USER_ID, 'enabled' => 0],
            Mcp_TokenAuth::getById($id),
            '物理削除ではなく enabled=0 で残る'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or disabled token');
        Mcp_TokenAuth::authenticate($raw);
    }

    public function test_unknown_token_is_rejected(): void
    {
        Mcp_TokenAuth::generateToken(self::USER_ID, self::LABEL_PREFIX . 'other', 30);
        $unknown = Mcp_TokenAuth::TOKEN_PREFIX . bin2hex(random_bytes(32));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or disabled token');
        Mcp_TokenAuth::authenticate($unknown);
    }

    /**
     * テスト用DB が無い環境（CI など）や、MCP のマイグレーションを当てていない DB では実行しない
     */
    private static function skipUnlessTokenTableIsAvailable(): void
    {
        try {
            $result = PearDatabase::getInstance()->pquery('SELECT 1 AS ok FROM vtiger_mcp_token LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_mcp_token がないため実行しない');
        }
    }

    /**
     * @return array<string, string|null>|null 列名 => 値（NULL 列は null）
     */
    private function rowByHash(string $hash): ?array
    {
        $result = $this->db->pquery(
            'SELECT id, token_hash, userid, label, token_prefix, enabled, expires_at, last_used_at
             FROM vtiger_mcp_token WHERE token_hash = ?',
            [$hash]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }

        $row = $this->db->fetchByAssoc($result, 0, false);
        if (!is_array($row)) {
            return null;
        }

        $values = [];
        foreach ($row as $column => $value) {
            $values[(string) $column] = is_scalar($value) ? (string) $value : null;
        }

        return $values;
    }

    private function cleanUp(): void
    {
        $this->db->pquery(
            'DELETE FROM vtiger_mcp_token WHERE label LIKE ?',
            [str_replace('_', '\\_', self::LABEL_PREFIX) . '%']
        );
    }
}
