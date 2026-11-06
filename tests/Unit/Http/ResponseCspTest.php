<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Vtiger_Response;

require_once dirname(__DIR__, 3) . '/includes/http/Response.php';

/**
 * CSP ヘッダー（Vtiger_Response::buildCSPHeader）のテスト
 *
 * form-action を config.customize.php の $csp_allowed_domains['form-action'] で足せるようにした（#1963）。
 * テスト仕様書: docs/tests/SharedLinks/TS-04_リンクの開き方.md（TC-OPN-040〜043）
 */
final class ResponseCspTest extends TestCase
{
    private mixed $saved = null;

    protected function setUp(): void
    {
        $this->saved = $GLOBALS['csp_allowed_domains'] ?? null;
    }

    protected function tearDown(): void
    {
        $GLOBALS['csp_allowed_domains'] = $this->saved;
    }

    /**
     * @return array<string, string>
     */
    private function directives(): array
    {
        $header = Vtiger_Response::buildCSPHeader();
        $this->assertStringStartsWith('Content-Security-Policy: ', $header);
        $directives = [];
        foreach (explode('; ', substr($header, strlen('Content-Security-Policy: '))) as $part) {
            [$name, $value] = array_pad(explode(' ', $part, 2), 2, '');
            $this->assertArrayNotHasKey($name, $directives, "$name が重複している");
            $directives[$name] = $value;
        }
        return $directives;
    }

    public function test_TC_OPN_040_設定が無ければform_actionはselfだけ(): void
    {
        $GLOBALS['csp_allowed_domains'] = null;

        $directives = $this->directives();

        $this->assertSame("'self'", $directives['form-action']);
        // 既存の指定は変わらない
        $this->assertSame("'self'", $directives['default-src']);
        $this->assertSame("'self'", $directives['frame-ancestors']);
        $this->assertSame("'self'", $directives['base-uri']);
    }

    public function test_TC_OPN_041_form_actionに送り先を足せる(): void
    {
        $GLOBALS['csp_allowed_domains'] = [
            'form-action' => ['https://kintai.example.com', 'https://*.sso.example.com'],
        ];

        $this->assertSame(
            "'self' https://kintai.example.com https://*.sso.example.com",
            $this->directives()['form-action']
        );
    }

    public function test_TC_OPN_042_空の配列やform_actionが無い設定ではselfだけ(): void
    {
        $GLOBALS['csp_allowed_domains'] = ['form-action' => []];
        $this->assertSame("'self'", $this->directives()['form-action']);

        $GLOBALS['csp_allowed_domains'] = ['script-src' => ['https://maps.google.com']];
        $directives = $this->directives();
        $this->assertSame("'self'", $directives['form-action']);
        $this->assertSame("'self' 'unsafe-inline' 'unsafe-eval' https://maps.google.com", $directives['script-src']);
    }

    public function test_TC_OPN_043_form_actionは1回だけ出す(): void
    {
        $GLOBALS['csp_allowed_domains'] = ['form-action' => ['https://a.example.com']];

        $header = Vtiger_Response::buildCSPHeader();

        $this->assertSame(1, substr_count($header, 'form-action'));
    }
}
