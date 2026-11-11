<?php

/**
 * ファイルアップロード項目の Unit テスト用スタブ
 *
 * Documents_FieldFile はエラーメッセージを vtranslate で作る。Unit テストでは
 * 言語ファイルを読まないため、キーをそのまま返す。既に定義されていれば何もしない。
 */

if (!function_exists('vtranslate')) {
    function vtranslate($key, $module = '', ...$args)
    {
        return (string) $key;
    }
}
