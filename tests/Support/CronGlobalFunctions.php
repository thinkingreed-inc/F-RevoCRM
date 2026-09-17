<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

// cron スケジューラーのテストで使うグローバル関数の補完。
//
// 管理画面のモデルは vtranslate() を使う。実物は includes/runtime/EntryPoint.php 経由で
// 定義されるが、tests/Support/ の Vtiger_Language_Handler スタブが先に読み込まれている
// プロセスでは EntryPoint を読めない（LanguageHandler.php がクラス宣言を条件で囲って
// いないため二重宣言になる）。その場合でも翻訳が引けるよう、ここで補う。
//
// 既に定義されている場合は何もしないので、実物・他のスタブと衝突しない。

if (!function_exists('vtranslate')) {
    function vtranslate(string $key, string $module = '', string $currentLanguage = ''): string
    {
        return Vtiger_Language_Handler::getTranslatedString($key, $module, $currentLanguage);
    }
}
