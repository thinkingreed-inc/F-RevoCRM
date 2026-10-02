<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Settings\Vtiger;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/tests/Support/ParametersApiTestSupport.php';
require_once $root . '/tests/Support/LanguageHandlerStubs.php';
require_once $root . '/includes/http/Response.php';
require_once $root . '/modules/Vtiger/apis/GetTranslations.php';
require_once $root . '/modules/Settings/Vtiger/apis/GetTranslations.php';

/**
 * protected の processApi をテストから呼ぶためのサブクラス
 */
class SettingsGetTranslationsApiTestDouble extends \Settings_Vtiger_GetTranslations_Api
{
    public function exposeProcessApi(\Vtiger_Request $request): \Vtiger_Response
    {
        return $this->processApi($request);
    }
}

/**
 * Settings 配下向け GetTranslations API
 *
 * parent=Settings のリクエストは Vtiger_Loader のフォールバックにより
 * このクラスへ解決される。コアの Vtiger_GetTranslations_Api（通常モジュール向け）の
 * 権限モデルを変えずに、設定画面の翻訳だけを管理者限定にすることが狙い。
 */
final class GetTranslationsApiTest extends TestCase
{
    private ?string $errorLogFile = null;

    private string $previousErrorLog = '';

    protected function setUp(): void
    {
        parent::setUp();

        // API は例外時に error_log へ出力する。テスト出力に混ざるので一時ファイルへ退避する
        $this->previousErrorLog = (string)ini_get('error_log');
        $logFile = tempnam(sys_get_temp_dir(), 'phpunit-errorlog-');
        if (is_string($logFile)) {
            $this->errorLogFile = $logFile;
            ini_set('error_log', $logFile);
        }

        \ParametersApiTestState::$isAdmin = true;
        unset($_SERVER['HTTP_REFERER']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        ini_set('error_log', $this->previousErrorLog);
        if ($this->errorLogFile !== null && file_exists($this->errorLogFile)) {
            unlink($this->errorLogFile);
        }
        $this->errorLogFile = null;

        \ParametersApiTestState::$isAdmin = true;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function makeRequest(array $values): \Vtiger_Request
    {
        return new \Vtiger_Request($values, $values);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function runApi(array $values): array
    {
        $api = new SettingsGetTranslationsApiTestDouble();
        $result = $api->exposeProcessApi($this->makeRequest($values))->getResult();

        $this->assertIsArray($result);
        $normalized = [];
        foreach ($result as $key => $value) {
            $normalized[(string)$key] = $value;
        }

        return $normalized;
    }

    /**
     * レスポンスから指定モジュールの翻訳を文字列マップとして取り出す
     *
     * @param array<string, mixed> $result
     * @return array<string, string>
     */
    private function translationsOf(array $result, string $moduleKey): array
    {
        $translations = $result['translations'] ?? null;
        $this->assertIsArray($translations);

        $moduleTranslations = $translations[$moduleKey] ?? null;
        $this->assertIsArray($moduleTranslations);

        $strings = [];
        foreach ($moduleTranslations as $key => $value) {
            if (is_string($value)) {
                $strings[(string)$key] = $value;
            }
        }

        return $strings;
    }

    // ------------------------------------------------------------------
    // 権限
    // ------------------------------------------------------------------

    public function test_親クラスのDetailView権限要求を引き継がない(): void
    {
        $api = new SettingsGetTranslationsApiTestDouble();

        // Settings 配下のモジュールは vtiger_tab に載らずモジュール権限を持たないため、
        // 親クラスが要求する DetailView 権限チェックは行わない
        $this->assertSame([], $api->requiresPermission($this->makeRequest([
            'module' => 'Parameters',
            'parent' => 'Settings',
        ])));
    }

    public function test_管理者以外は拒否される(): void
    {
        \ParametersApiTestState::$isAdmin = false;
        $api = new SettingsGetTranslationsApiTestDouble();

        $this->expectException(\ApiForbiddenException::class);
        $api->checkPermission($this->makeRequest([
            'module' => 'Parameters',
            'parent' => 'Settings',
        ]));
    }

    public function test_管理者は許可される(): void
    {
        $api = new SettingsGetTranslationsApiTestDouble();

        $this->assertTrue($api->checkPermission($this->makeRequest([
            'module' => 'Parameters',
            'parent' => 'Settings',
        ])));
    }

    public function test_コア側のAPIは管理者限定になっていない(): void
    {
        // 通常モジュール向けのコア API は従来どおりモジュール権限で判定する。
        // ここが管理者限定になると、非管理者のクイック作成・活動一覧が壊れる。
        $core = new \Vtiger_GetTranslations_Api();
        $permissions = $core->requiresPermission($this->makeRequest(['module' => 'Accounts']));

        $this->assertSame([
            ['module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => null],
        ], $permissions);
    }

    // ------------------------------------------------------------------
    // 翻訳の取得
    // ------------------------------------------------------------------

    public function test_Settings配下の言語ファイルから翻訳を返す(): void
    {
        $result = $this->runApi([
            'module' => 'Parameters',
            'parent' => 'Settings',
            'language' => 'ja_jp',
        ]);

        $this->assertSame('Parameters', $result['module']);
        $this->assertSame('ja_jp', $result['language']);

        $translations = $this->translationsOf($result, 'Parameters');
        $this->assertSame('システム変数', $translations['Parameters']);
        $this->assertSame('値を隠す', $translations['LBL_SECRET_ON']);
    }

    public function test_英語の言語ファイルからも翻訳を返す(): void
    {
        $result = $this->runApi([
            'module' => 'Parameters',
            'parent' => 'Settings',
            'language' => 'en_us',
        ]);

        $translations = $this->translationsOf($result, 'Parameters');

        // 日本語側にしか無いキーがあると英語環境で生キーが露出する
        $this->assertSame('Hide value', $translations['LBL_SECRET_ON']);
        $this->assertSame('Show value', $translations['LBL_SECRET_OFF']);
        $this->assertSame('true', $translations['LBL_TRUE']);
        $this->assertSame('false', $translations['LBL_FALSE']);
        $this->assertArrayHasKey('LBL_SECRET_VALUE_HIDDEN', $translations);
    }

    public function test_Vtiger共通翻訳を常に含める(): void
    {
        $result = $this->runApi([
            'module' => 'Parameters',
            'parent' => 'Settings',
            'language' => 'ja_jp',
        ]);

        $this->assertNotEmpty($this->translationsOf($result, 'Vtiger'));
    }

    public function test_言語未指定ならユーザー設定の言語を使う(): void
    {
        $result = $this->runApi([
            'module' => 'Parameters',
            'parent' => 'Settings',
        ]);

        $this->assertSame('ja_jp', $result['language']);
    }

    // ------------------------------------------------------------------
    // 入力値の検証
    // ------------------------------------------------------------------

    public function test_不正なモジュール名は汎用エラーを返す(): void
    {
        $api = new SettingsGetTranslationsApiTestDouble();

        // 翻訳ファイルのパス組み立てに使うため、想定外の文字列は弾く
        $this->expectException(\ApiException::class);
        $api->exposeProcessApi($this->makeRequest([
            'module' => '../../etc/passwd',
            'parent' => 'Settings',
        ]));
    }

    public function test_不正な言語コードは汎用エラーを返す(): void
    {
        $api = new SettingsGetTranslationsApiTestDouble();

        $this->expectException(\ApiException::class);
        $api->exposeProcessApi($this->makeRequest([
            'module' => 'Parameters',
            'parent' => 'Settings',
            'language' => '../ja_jp',
        ]));
    }
}
