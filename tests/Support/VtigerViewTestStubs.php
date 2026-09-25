<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

// PHPUnit Unit テスト用: Users_Login_View をロードするために必要な依存クラス群をロードする。
// VtigerActionTestSupport.php と同一プロセスで実行されることを想定し、
// require_once を使って重複ロードを回避する。

$root = dirname(__DIR__, 2);

// Controller.php には Vtiger_Controller / Vtiger_Action_Controller / Vtiger_View_Controller が定義されている。
require_once $root . '/includes/runtime/Controller.php';

// Vtiger_View_Controller のメソッドシグネチャに Vtiger_Request が型宣言されているため必須。
require_once $root . '/includes/http/Request.php';

// vimport() はファイルトップレベルで呼ばれるため、Login.php のロード前にスタブを提供する。
if (!function_exists('vimport')) {
    function vimport(string $qualifiedName): void
    {
    }
}

// Vtiger_Language_Handler スタブ: 実際の言語ファイルをディスクから読み込む。
// 本番の LanguageHandler.php は Vtiger_Loader 等の依存を持つため require 不可。
require_once __DIR__ . '/LanguageHandlerStubs.php';
