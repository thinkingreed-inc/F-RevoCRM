<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

namespace Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/OAuthHelper.php';

/**
 * PKCE S256 の処理を RFC 7636 Appendix A のテストベクタで検証する。
 */
class OAuthPkceTest extends TestCase
{
    /** RFC 7636 Appendix A の code_verifier */
    private const RFC_VERIFIER  = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
    /** RFC 7636 Appendix A の code_challenge（base64url(sha256(verifier))） */
    private const RFC_CHALLENGE = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    public function test_rfc7636_appendix_a_vector_is_accepted(): void
    {
        $this->assertTrue(mcp_oauth_pkce_verify(self::RFC_VERIFIER, self::RFC_CHALLENGE));
    }

    public function test_base64url_encode_matches_rfc7636_appendix_a(): void
    {
        $hash = hash('sha256', self::RFC_VERIFIER, true);
        $this->assertSame(self::RFC_CHALLENGE, mcp_oauth_base64url_encode($hash));
    }

    public function test_base64url_encode_has_no_padding_and_no_plus_or_slash(): void
    {
        // base64 の '+' '/' '=' が含まれないことを確認する
        $encoded = mcp_oauth_base64url_encode(hex2bin('fbff00'));
        $this->assertSame('-_8A', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
    }

    public function test_wrong_verifier_is_rejected(): void
    {
        $this->assertFalse(mcp_oauth_pkce_verify('not-the-verifier', self::RFC_CHALLENGE));
    }

    public function test_empty_verifier_is_rejected(): void
    {
        $this->assertFalse(mcp_oauth_pkce_verify('', self::RFC_CHALLENGE));
    }

    public function test_plain_verifier_sent_as_challenge_is_rejected(): void
    {
        // plain 方式の challenge は受理しない
        $this->assertFalse(mcp_oauth_pkce_verify(self::RFC_VERIFIER, self::RFC_VERIFIER));
    }

    public function test_challenge_with_trailing_padding_is_rejected(): void
    {
        // '=' 付きの challenge は base64url の正規形ではないため受理しない
        $this->assertFalse(mcp_oauth_pkce_verify(self::RFC_VERIFIER, self::RFC_CHALLENGE . '='));
    }
}
