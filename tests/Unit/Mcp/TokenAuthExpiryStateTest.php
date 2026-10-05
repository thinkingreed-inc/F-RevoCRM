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
require_once $root . '/include/Mcp/TokenAuth.php';

/**
 * Mcp_TokenAuth::getExpiryState() の期限判定をテストする。
 *
 * 文字列比較による現在の判定仕様を固定し、
 * 日時処理の変更による回帰を検出する。
 */
class TokenAuthExpiryStateTest extends TestCase
{
    public function test_null_is_treated_as_no_expiry(): void
    {
        $this->assertSame(\Mcp_TokenAuth::EXPIRY_NONE, \Mcp_TokenAuth::getExpiryState(null));
    }

    public function test_empty_string_is_treated_as_no_expiry(): void
    {
        $this->assertSame(\Mcp_TokenAuth::EXPIRY_NONE, \Mcp_TokenAuth::getExpiryState(''));
    }

    public function test_mysql_zero_date_is_treated_as_no_expiry(): void
    {
        // MySQL のゼロ日付は無期限として扱う
        $this->assertSame(
            \Mcp_TokenAuth::EXPIRY_NONE,
            \Mcp_TokenAuth::getExpiryState('0000-00-00 00:00:00')
        );
    }

    public function test_past_datetime_is_expired(): void
    {
        $past = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->assertSame(\Mcp_TokenAuth::EXPIRY_EXPIRED, \Mcp_TokenAuth::getExpiryState($past));
    }

    public function test_future_datetime_is_active(): void
    {
        $future = date('Y-m-d H:i:s', strtotime('+1 day'));
        $this->assertSame(\Mcp_TokenAuth::EXPIRY_ACTIVE, \Mcp_TokenAuth::getExpiryState($future));
    }

    public function test_boundary_one_second_before_now_is_expired(): void
    {
        // 現在時刻以前は期限切れとして扱う
        $justPast = date('Y-m-d H:i:s', time() - 1);
        $this->assertSame(\Mcp_TokenAuth::EXPIRY_EXPIRED, \Mcp_TokenAuth::getExpiryState($justPast));
    }

    public function test_allowed_expires_days_are_the_four_documented_values(): void
    {
        // 画面の選択肢と DB 登録値の対応を固定する
        $this->assertSame([0, 30, 60, 90], \Mcp_TokenAuth::ALLOWED_EXPIRES_DAYS);
    }

    public function test_token_prefix_is_stable(): void
    {
        // 接頭辞は発行済みトークンの照合に使用するため固定する
        $this->assertSame('frevo_pat_', \Mcp_TokenAuth::TOKEN_PREFIX);
    }
}
