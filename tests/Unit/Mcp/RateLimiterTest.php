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
require_once $root . '/include/Mcp/RateLimiter.php';

/**
 * Mcp_RateLimiter の計数（check）と非計数の判定（isLimited）をテストする。
 *
 * McpServer は認証前に isLimited で IP を判定し、認証失敗時だけ check で IP に計数するため、
 * isLimited が枠を消費しないこととキーごとに独立して数えることを固定する。
 */
class RateLimiterTest extends TestCase
{
    private const WINDOW = 10;
    private const MAX    = 3;

    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/mcp_ratelimit_test_' . bin2hex(random_bytes(8));
        mkdir($this->baseDir, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->baseDir . '/mcp_ratelimit/*.json') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->baseDir . '/mcp_ratelimit')) {
            rmdir($this->baseDir . '/mcp_ratelimit');
        }
        rmdir($this->baseDir);
    }

    private function limiter(): \Mcp_RateLimiter
    {
        return new \Mcp_RateLimiter($this->baseDir, self::WINDOW, self::MAX);
    }

    private function counterFile(string $key): string
    {
        return $this->baseDir . '/mcp_ratelimit/' . md5($key) . '.json';
    }

    public function test_check_は上限まで許可し上限を超えると拒否する(): void
    {
        $limiter = $this->limiter();
        for ($i = 1; $i <= self::MAX; $i++) {
            $this->assertTrue($limiter->check('192.0.2.1'), "{$i} 回目は許可される");
        }
        $this->assertFalse($limiter->check('192.0.2.1'), '上限 + 1 回目は拒否される');
    }

    public function test_isLimited_は計数しないため何度呼んでも枠が減らない(): void
    {
        $limiter = $this->limiter();
        $limiter->check('192.0.2.1');
        for ($i = 0; $i < self::MAX * 5; $i++) {
            $this->assertFalse($limiter->isLimited('192.0.2.1'));
        }
        for ($i = 2; $i <= self::MAX; $i++) {
            $this->assertTrue($limiter->check('192.0.2.1'), "isLimited の後も check {$i} 回目は許可される");
        }
    }

    public function test_isLimited_は未計数のキーで上限未到達を返す(): void
    {
        $this->assertFalse($this->limiter()->isLimited('192.0.2.1'));
    }

    public function test_isLimited_は上限到達後に_true_を返す(): void
    {
        $limiter = $this->limiter();
        for ($i = 1; $i < self::MAX; $i++) {
            $limiter->check('192.0.2.1');
        }
        $this->assertFalse($limiter->isLimited('192.0.2.1'), '上限 - 1 回の時点では未到達');
        $limiter->check('192.0.2.1');
        $this->assertTrue($limiter->isLimited('192.0.2.1'), '上限回の時点で到達');
    }

    public function test_窓の秒数を過ぎた計数は数えず回復する(): void
    {
        $limiter = $this->limiter();
        for ($i = 1; $i <= self::MAX; $i++) {
            $limiter->check('192.0.2.1');
        }
        $this->assertTrue($limiter->isLimited('192.0.2.1'));

        $expired = time() - self::WINDOW;
        file_put_contents($this->counterFile('192.0.2.1'), json_encode(array_fill(0, self::MAX, $expired)));

        $this->assertFalse($limiter->isLimited('192.0.2.1'));
        $this->assertTrue($limiter->check('192.0.2.1'));
    }

    public function test_利用者キーは利用者ごとに独立して数える(): void
    {
        $limiter = $this->limiter();
        for ($i = 1; $i <= self::MAX; $i++) {
            $limiter->check('user:1');
        }
        $this->assertFalse($limiter->check('user:1'));
        $this->assertFalse($limiter->isLimited('user:2'));
        $this->assertTrue($limiter->check('user:2'));
    }

    public function test_IP_キーと利用者キーは独立して数える(): void
    {
        $limiter = $this->limiter();
        for ($i = 1; $i <= self::MAX; $i++) {
            $limiter->check('192.0.2.1');
        }
        $this->assertTrue($limiter->isLimited('192.0.2.1'));
        $this->assertTrue($limiter->check('user:1'));
        $this->assertFalse($limiter->isLimited('user:1'));
    }
}
