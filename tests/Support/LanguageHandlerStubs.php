<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

// PHPUnit Unit テスト用の Vtiger_Language_Handler スタブ。
// 本番の includes/runtime/LanguageHandler.php は Vtiger_Loader / DB (LanguageConverter)
// への依存があり require できないため、言語ファイルをディスクから直接読み込む。
//
// 本番同様、モジュールの言語ファイルで見つからないキーは共通の Vtiger.php に
// フォールバックする。選択肢の値 (例 'Existing Customer') は Vtiger.php 側にしか
// 定義されていないため、このフォールバックが無いと翻訳を検証できない。
//
// VtigerViewTestStubs.php も同名クラスを定義するため、同一プロセスで
// どちらが先にロードされても同じ結果になるよう挙動を揃えてある。

if (!class_exists('Vtiger_Language_Handler')) {
    class Vtiger_Language_Handler
    {
        public static function getLanguage(): string
        {
            return 'ja_jp';
        }

        public static function getTranslatedString(string $key, string $module = '', string $currentLanguage = ''): string
        {
            if (empty($currentLanguage)) {
                $currentLanguage = self::getLanguage();
            }
            $strings = self::getModuleStringsFromFile($currentLanguage, $module);
            $translated = $strings['languageStrings'][$key] ?? $strings['jsLanguageStrings'][$key] ?? null;
            if ($translated !== null) {
                return $translated;
            }
            // モジュール側に無ければ共通言語ファイルへフォールバックする。
            if ($module !== 'Vtiger') {
                $commonStrings = self::getModuleStringsFromFile($currentLanguage, 'Vtiger');
                $translated = $commonStrings['languageStrings'][$key]
                    ?? $commonStrings['jsLanguageStrings'][$key]
                    ?? null;
                if ($translated !== null) {
                    return $translated;
                }
            }
            return $key;
        }

        /**
         * @return array{languageStrings: array<string, string>, jsLanguageStrings: array<string, string>}
         */
        public static function getModuleStringsFromFile(string $language, string $module = 'Vtiger'): array
        {
            // 'Settings:SSO' → 'Settings/SSO'
            $modulePath = str_replace(['.', ':'], ['/', '/'], $module);
            $root = dirname(__DIR__, 2);
            $file = $root . '/languages/' . $language . '/' . $modulePath . '.php';
            $languageStrings = [];
            $jsLanguageStrings = [];
            if (file_exists($file)) {
                require $file;
            }
            return ['languageStrings' => $languageStrings, 'jsLanguageStrings' => $jsLanguageStrings];
        }
    }
}
