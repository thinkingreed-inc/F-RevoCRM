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
 * mcp_oauth_get_base_path() のパス解決をテストする。
 *
 * DocumentRoot が public/ 直下でない環境でも、
 * well-known が正しい registration_endpoint 等を返せることを確認する。
 */
class OAuthBasePathTest extends TestCase
{
    private ?string $originalDocRoot;
    private ?string $originalHttpHost;
    private ?string $originalSiteUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDocRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
        $this->originalHttpHost = $_SERVER['HTTP_HOST'] ?? null;
        $this->originalSiteUrl = $GLOBALS['site_URL'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalDocRoot === null) {
            unset($_SERVER['DOCUMENT_ROOT']);
        } else {
            $_SERVER['DOCUMENT_ROOT'] = $this->originalDocRoot;
        }
        if ($this->originalHttpHost === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $this->originalHttpHost;
        }
        if ($this->originalSiteUrl === null) {
            unset($GLOBALS['site_URL']);
        } else {
            $GLOBALS['site_URL'] = $this->originalSiteUrl;
        }
        parent::tearDown();
    }

    public function test_本番相当_DocumentRootがpublic直下なら空文字(): void
    {
        // public/ を DocumentRoot とした構成を再現する
        $publicDir = realpath(__DIR__ . '/../../../public');
        $_SERVER['DOCUMENT_ROOT'] = $publicDir;

        $this->assertSame('', mcp_oauth_get_base_path());
    }

    public function test_共有docroot配下ならgithub_publicを返す(): void
    {
        // リポジトリの親ディレクトリを DocumentRoot とした共有環境の構成を再現する
        $repoRoot = realpath(__DIR__ . '/../../..');
        $_SERVER['DOCUMENT_ROOT'] = dirname($repoRoot);

        $this->assertSame('/' . basename($repoRoot) . '/public', mcp_oauth_get_base_path());
    }

    public function test_DocumentRootが未設定なら空文字(): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);

        $this->assertSame('', mcp_oauth_get_base_path());
    }

    public function test_DocumentRootが実在しないパスなら空文字(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = '/no/such/path/' . bin2hex(random_bytes(8));

        $this->assertSame('', mcp_oauth_get_base_path());
    }

    /**
     * 共有 DocumentRoot 配下でも mcp_oauth_get_base_url() に
     * basePath が含まれることを確認する。
     */
    public function test_site_URL未設定ならget_base_urlはbase_pathを含む(): void
    {
        $GLOBALS['site_URL'] = '';
        $repoRoot = realpath(__DIR__ . '/../../..');
        $_SERVER['DOCUMENT_ROOT'] = dirname($repoRoot);
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->assertSame(
            'http://localhost/' . basename($repoRoot) . '/public',
            mcp_oauth_get_base_url()
        );
    }

    /**
     * site_URL が設定されていれば HTTP_HOST に依存せず
     * 末尾スラッシュを除いた site_URL を返すことを確認する。
     */
    public function test_site_URL設定時はHTTP_HOSTに依存せずsite_URLを返す(): void
    {
        $GLOBALS['site_URL'] = 'https://crm.example.test/sub/';
        $_SERVER['HTTP_HOST'] = 'evil.example';

        $this->assertSame('https://crm.example.test/sub', mcp_oauth_get_base_url());
    }
}
