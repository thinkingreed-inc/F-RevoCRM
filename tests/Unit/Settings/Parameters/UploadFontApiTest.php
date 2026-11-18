<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Settings\Parameters;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/tests/Support/ParametersApiTestSupport.php';
require_once $root . '/includes/http/Response.php';
require_once $root . '/modules/Settings/Parameters/apis/UploadFont.php';
require_once $root . '/modules/Settings/Parameters/apis/GetFonts.php';

/**
 * アップロードされたファイルと変換先を差し替えるためのサブクラス
 *
 * CLI では is_uploaded_file() が常に false になるため、アップロード済みかの判定も差し替える。
 */
class UploadFontApiTestDouble extends \Settings_Parameters_UploadFont_Api
{
    /** @var array<string, mixed> */
    public array $file = [];

    public bool $uploaded = true;

    public function __construct(private string $fontDir)
    {
        parent::__construct();
    }

    public function exposeProcessApi(\Vtiger_Request $request): \Vtiger_Response
    {
        return $this->processApi($request);
    }

    protected function getUploadedFile()
    {
        return $this->file;
    }

    protected function isUploadedFile($path)
    {
        return $this->uploaded;
    }

    protected function createInstaller()
    {
        return new \Vtiger_PDF_FontInstaller($this->fontDir);
    }
}

class GetFontsApiTestDouble extends \Settings_Parameters_GetFonts_Api
{
    public function exposeProcessApi(\Vtiger_Request $request): \Vtiger_Response
    {
        return $this->processApi($request);
    }
}

/**
 * フォントのアップロード API と一覧 API（thinkingreed-inc#1901）
 */
final class UploadFontApiTest extends TestCase
{
    private string $fontDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fontDir = sys_get_temp_dir() . '/frevocrm_upload_font_test_' . bin2hex(random_bytes(4));
        mkdir($this->fontDir);

        \ParametersApiTestState::reset();
        $GLOBALS['__test_csrf_pass'] = true;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['HTTP_REFERER']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // ロックファイル（.font_install.lock）も消す
        foreach (array_merge((array)glob($this->fontDir . '/*'), (array)glob($this->fontDir . '/.*')) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->fontDir);

        \ParametersApiTestState::reset();
        unset($GLOBALS['__test_csrf_pass'], $_SERVER['REQUEST_METHOD']);
    }

    private function api(): UploadFontApiTestDouble
    {
        return new UploadFontApiTestDouble($this->fontDir);
    }

    private function request(): \Vtiger_Request
    {
        $values = ['module' => 'Parameters', 'parent' => 'Settings', 'api' => 'UploadFont'];

        return new \Vtiger_Request($values, $values);
    }

    /**
     * @return array<string, mixed>
     */
    private function ttfUpload(string $name = 'Test-Font Regular.ttf', int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name' => $name,
            'tmp_name' => dirname(__DIR__, 4) . '/public/resources/fonts/OpenSans-Regular-webfont.ttf',
            'error' => $error,
        ];
    }

    private function errorMessage(UploadFontApiTestDouble $api): string
    {
        try {
            $api->exposeProcessApi($this->request());
        } catch (\ApiException $e) {
            return $e->getMessage();
        }
        $this->fail('ApiException が投げられること');
    }

    public function test_TTFを変換して追加したフォント名を返す(): void
    {
        $api = $this->api();
        $api->file = $this->ttfUpload();

        $result = $api->exposeProcessApi($this->request())->getResult();

        $this->assertSame(['font' => 'testfont'], $result);
        $this->assertFileExists($this->fontDir . '/testfont.php');
    }

    public function test_変換できない場合は翻訳したメッセージを返す(): void
    {
        // vtranslate() はテストではキーをそのまま返す
        $api = $this->api();
        $api->file = $this->ttfUpload('Font.otf');

        $this->assertSame('LBL_FONT_UPLOAD_INVALID_EXTENSION', $this->errorMessage($api));
        $this->assertSame([], glob($this->fontDir . '/*'));
    }

    public function test_同名のフォントは追加しない(): void
    {
        $api = $this->api();
        $api->file = $this->ttfUpload();
        $api->exposeProcessApi($this->request());

        $this->assertSame('LBL_FONT_UPLOAD_ALREADY_EXISTS', $this->errorMessage($api));
    }

    public function test_ファイルが送られていなければ断る(): void
    {
        $api = $this->api();
        $api->file = [];

        $this->assertSame('LBL_FONT_UPLOAD_NO_FILE', $this->errorMessage($api));
    }

    public function test_PHPのアップロード上限を超えた場合はその旨を返す(): void
    {
        $api = $this->api();
        $api->file = $this->ttfUpload('Font.ttf', UPLOAD_ERR_INI_SIZE);

        $this->assertSame('LBL_FONT_UPLOAD_TOO_LARGE_SERVER', $this->errorMessage($api));
    }

    public function test_post_max_sizeを超えた場合はCSRFより先にその旨を返す(): void
    {
        // 超えると PHP が POST の中身（CSRF トークンを含む）を捨てるため、そのままでは理由がわからない
        $GLOBALS['__test_csrf_pass'] = false;
        $_SERVER['CONTENT_LENGTH'] = (string)(\Settings_Parameters_UploadFont_Api::toBytes((string)ini_get('post_max_size')) + 1);
        $api = $this->api();

        try {
            $this->assertSame('LBL_FONT_UPLOAD_TOO_LARGE_SERVER', $this->errorMessage($api));
        } finally {
            unset($_SERVER['CONTENT_LENGTH']);
        }
    }

    public function test_サイズ表記をバイト数にする(): void
    {
        $this->assertSame(8 * 1024 * 1024, \Settings_Parameters_UploadFont_Api::toBytes('8M'));
        $this->assertSame(2 * 1024 * 1024 * 1024, \Settings_Parameters_UploadFont_Api::toBytes('2G'));
        $this->assertSame(512 * 1024, \Settings_Parameters_UploadFont_Api::toBytes('512k'));
        $this->assertSame(1000, \Settings_Parameters_UploadFont_Api::toBytes('1000'));
        $this->assertSame(0, \Settings_Parameters_UploadFont_Api::toBytes(''));
    }

    public function test_HTTPでアップロードされたファイルでなければ断る(): void
    {
        // サーバー上の任意のファイルを変換元に指定させない
        $api = $this->api();
        $api->file = $this->ttfUpload();
        $api->uploaded = false;

        $this->assertSame('LBL_FONT_UPLOAD_FAILED', $this->errorMessage($api));
        $this->assertSame([], glob($this->fontDir . '/*'));
    }

    public function test_CSRF検証に失敗すると追加しない(): void
    {
        $GLOBALS['__test_csrf_pass'] = false;
        $api = $this->api();
        $api->file = $this->ttfUpload();

        try {
            $api->exposeProcessApi($this->request());
            $this->fail('CSRF 検証に失敗した場合は例外が投げられること');
        } catch (\Exception $e) {
            $this->assertSame('Unsupported request', $e->getMessage());
        }
        $this->assertSame([], glob($this->fontDir . '/*'));
    }

    public function test_GETでは追加しない(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $api = $this->api();
        $api->file = $this->ttfUpload();

        try {
            $api->exposeProcessApi($this->request());
            $this->fail('POST 以外は例外が投げられること');
        } catch (\Exception $e) {
            $this->assertSame('Invalid request', $e->getMessage());
        }
        $this->assertSame([], glob($this->fontDir . '/*'));
    }

    public function test_管理者以外はアップロードできない(): void
    {
        \ParametersApiTestState::$isAdmin = false;

        $this->expectException(\ApiForbiddenException::class);
        $this->api()->checkPermission($this->request());
    }

    public function test_管理者以外はフォントの一覧を取得できない(): void
    {
        \ParametersApiTestState::$isAdmin = false;

        $this->expectException(\ApiForbiddenException::class);
        (new GetFontsApiTestDouble())->checkPermission($this->request());
    }

    public function test_フォントの一覧と既定のフォントを返す(): void
    {
        $result = (new GetFontsApiTestDouble())->exposeProcessApi($this->request())->getResult();

        $this->assertIsArray($result);
        $this->assertSame('ume-tgo4', $result['fallback']);
        $this->assertContains('ume-tgo4', $result['fonts']);
        $this->assertNotContains('uni2cid_aj16', $result['fonts']);
    }
}
