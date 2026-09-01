<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

// PHPUnit テスト環境用: vtiger アクション継承チェーンを手動でロードする。
// 通常は Vtiger_Loader が自動解決するが、テスト bootstrap ではローダーを起動しない。
//
// 読み込み順序は継承階層に従う（親クラスが先）。
// Settings_Vtiger_Basic_Action の継承チェーン:
//   Vtiger_Controller → Vtiger_Action_Controller → Vtiger_View_Controller
//   → Vtiger_Header_View → Vtiger_Footer_View → Vtiger_Basic_View
//   → Settings_Vtiger_Index_View → Settings_Vtiger_IndexAjax_View
//   → Settings_Vtiger_Basic_Action

$root = dirname(__DIR__, 2);

require_once $root . '/includes/runtime/Controller.php';
require_once $root . '/modules/Vtiger/views/Header.php';
require_once $root . '/modules/Vtiger/views/Footer.php';
require_once $root . '/modules/Vtiger/views/Basic.php';
require_once $root . '/modules/Settings/Vtiger/views/Index.php';
require_once $root . '/modules/Settings/Vtiger/views/IndexAjax.php';
require_once $root . '/modules/Settings/Vtiger/actions/Basic.php';

// Vtiger_Request のコンストラクタが Vtiger_Functions::validateRequestParameters() を呼ぶため必須
require_once $root . '/vtlib/Vtiger/Functions.php';
require_once $root . '/includes/http/Request.php';

// Vtiger_Session は HTTP_Session2 経由で $_SESSION を操作する静的クラス
require_once $root . '/libraries/HTTP_Session2/HTTP/Session2.php';
require_once $root . '/includes/http/Session.php';

// vglobal() は includes/runtime/Globals.php で定義される。
// テスト環境では明示的にロードが必要。
if (!function_exists('vglobal')) {
    require_once $root . '/includes/runtime/Globals.php';
}

// AppException は Settings_Vtiger_Index_View::checkPermission() がスローするため必須。
if (!class_exists('AppException')) {
    require_once $root . '/includes/exceptions/AppException.php';
}

// vtranslate() はフレームワーク全体ロード時に LanguageHandler.php で定義される。
// テスト環境では未定義になるため、フォールバックスタブを提供する。
if (!function_exists('vtranslate')) {
    function vtranslate(string $key, string $module = ''): string
    {
        return $key;
    }
}

// Vtiger_Language_Handler は SamlFlowTestErrorMapper 等が翻訳のために使用する。
// テスト環境では実際の言語ファイルを読み込んで翻訳を解決するスタブを提供する。
require_once __DIR__ . '/LanguageHandlerStubs.php';

// csrf_check() は csrf-magic ライブラリで定義される。
// テスト環境では未定義になるため、$GLOBALS['__test_csrf_pass'] で制御できるスタブを提供する。
if (!function_exists('csrf_check')) {
    function csrf_check(bool $fatal = true): bool
    {
        // $GLOBALS 経由の値は型が保証されないため bool に正規化する
        return (bool)($GLOBALS['__test_csrf_pass'] ?? true);
    }
}

// csrf_get_tokens() は csrf-magic ライブラリで定義される。
// テスト環境では未定義になるため、$GLOBALS['__test_csrf_token'] で制御できるスタブを提供する。
if (!function_exists('csrf_get_tokens')) {
    function csrf_get_tokens(): string
    {
        // $GLOBALS 経由の値は型が保証されないため string 以外は既定値に倒す
        $token = $GLOBALS['__test_csrf_token'] ?? null;

        return is_string($token) ? $token : 'test-csrf-token';
    }
}
// csrf-magic は $GLOBALS['csrf'] に設定値を持つ。未定義・非配列なら初期化してから既定値を入れる。
if (!isset($GLOBALS['csrf']) || !is_array($GLOBALS['csrf'])) {
    $GLOBALS['csrf'] = [];
}
$GLOBALS['csrf']['input-name'] ??= '__vtrftk';
