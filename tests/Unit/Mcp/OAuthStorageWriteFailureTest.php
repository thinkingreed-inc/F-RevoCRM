<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 3);
require_once $root . '/tests/Support/VtigerActionTestSupport.php';
require_once $root . '/include/Mcp/OAuthStorage.php';

/**
 * 書き込み系クエリが失敗（pquery が false を返す）したとき例外になることを DB なしでテストする。
 *
 * PearDatabase::pquery は失敗時に例外を投げず false を返すため、
 * 戻り値を見ていないと呼び出し側は成功したものとして処理を続けてしまう。
 * PearDatabase::getInstance() が返す $adb を差し替え、SQL ごとの成否を制御する。
 */
class OAuthStorageWriteFailureTest extends TestCase
{
    /** @var mixed 差し替え前の $adb */
    private $originalAdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAdb = $GLOBALS['adb'] ?? null;
    }

    protected function tearDown(): void
    {
        $GLOBALS['adb'] = $this->originalAdb;
        parent::tearDown();
    }

    private function useDb(string $failingSqlPrefix): OAuthWriteResultStub
    {
        $stub = new OAuthWriteResultStub($failingSqlPrefix);
        $GLOBALS['adb'] = $stub;

        return $stub;
    }

    public function test_create_client_throws_when_insert_fails(): void
    {
        $this->useDb('INSERT INTO vtiger_mcp_oauth_client');

        $this->expectException(\Exception::class);
        \Mcp_OAuthStorage::createClient('client-test', null, 'Test Client', ['https://example.test/cb']);
    }

    public function test_create_auth_code_throws_when_insert_fails(): void
    {
        $this->useDb('INSERT INTO vtiger_mcp_oauth_code');

        $this->expectException(\Exception::class);
        \Mcp_OAuthStorage::createAuthCode('h', 'client-test', 1, 'challenge', 'https://example.test/cb', 'crm', '2026-01-01 00:00:00');
    }

    public function test_create_token_throws_when_insert_fails_after_revoking_existing(): void
    {
        $stub = $this->useDb('INSERT INTO vtiger_mcp_oauth_token');

        try {
            \Mcp_OAuthStorage::createToken('a', 'r', 'client-test', 1, 'crm', '2026-01-01 00:00:00');
            $this->fail('Exception was not thrown');
        } catch (\Exception $e) {
            // 既存連携の失効 UPDATE は成功し、その後の INSERT で失敗していることを確認する
            $this->assertCount(2, $stub->issued);
            $this->assertStringStartsWith('UPDATE vtiger_mcp_oauth_token SET enabled = 0', $stub->issued[0]);
            $this->assertStringStartsWith('INSERT INTO vtiger_mcp_oauth_token', $stub->issued[1]);
        }
    }

    public function test_rotate_token_throws_when_update_fails(): void
    {
        $this->useDb('UPDATE vtiger_mcp_oauth_token SET access_token_hash');

        $this->expectException(\Exception::class);
        \Mcp_OAuthStorage::rotateToken(1, 'a2', 'r2', '2026-01-01 00:00:00');
    }

    public function test_writes_succeed_when_no_query_fails(): void
    {
        // 陽性対照: 失敗させる SQL を指定しなければ例外にならない
        $stub = $this->useDb('');

        \Mcp_OAuthStorage::createClient('client-test', null, 'Test Client', ['https://example.test/cb']);
        \Mcp_OAuthStorage::createAuthCode('h', 'client-test', 1, 'challenge', 'https://example.test/cb', 'crm', '2026-01-01 00:00:00');
        \Mcp_OAuthStorage::createToken('a', 'r', 'client-test', 1, 'crm', '2026-01-01 00:00:00');
        \Mcp_OAuthStorage::rotateToken(1, 'a2', 'r2', '2026-01-01 00:00:00');

        $this->assertCount(5, $stub->issued);
    }
}

/**
 * 書き込みクエリの成否だけを返す PearDatabase の代替。
 * 指定した接頭辞で始まる SQL のみ false（失敗）を返し、それ以外は true を返す。
 */
class OAuthWriteResultStub extends \PearDatabase
{
    private string $failingSqlPrefix;

    /** @var string[] 発行された SQL の記録 */
    public array $issued = [];

    public function __construct(string $failingSqlPrefix)
    {
        // 親は接続設定を要求するため呼ばない
        $this->failingSqlPrefix = $failingSqlPrefix;
    }

    /**
     * @param string $sql
     * @param array<int, mixed> $params
     * @param bool $dieOnError
     * @param string $msg
     * @return bool
     */
    public function pquery($sql, $params = [], $dieOnError = false, $msg = '')
    {
        $this->issued[] = $sql;
        if ($this->failingSqlPrefix !== '' && strpos($sql, $this->failingSqlPrefix) === 0) {
            return false;
        }

        return true;
    }
}
