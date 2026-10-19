<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * リード昇格まわりのシステム設定（パラメーター）を読み出すモデル。
 *
 * - SHOW_CONVERTED_LEADS: 昇格済みリードを一覧などに表示するか
 * - ALLOW_RECONVERT_LEAD: 昇格済みリードを再度昇格できるようにするか
 *
 * どちらも既定値は 'false' で、未設定の場合は従来どおりの挙動になる。
 */
class Leads_ConvertSetting_Model
{
    /** 昇格済みリードを表示するかどうかのパラメーターキー */
    public const SHOW_CONVERTED_LEADS = 'SHOW_CONVERTED_LEADS';

    /** 昇格済みリードの再昇格を許可するかどうかのパラメーターキー */
    public const ALLOW_RECONVERT_LEAD = 'ALLOW_RECONVERT_LEAD';

    /**
     * 昇格済みリードを一覧・検索・関連リストなどに表示するかどうか
     */
    public static function showConvertedLeads(): bool
    {
        return self::isEnabled(self::SHOW_CONVERTED_LEADS);
    }

    /**
     * 昇格済みリードを再度昇格できるかどうか
     */
    public static function allowReconvert(): bool
    {
        return self::isEnabled(self::ALLOW_RECONVERT_LEAD);
    }

    /**
     * 昇格済みリードを除外するための SQL 条件を返す。
     * 表示する設定のときは空文字を返すため、呼び出し側は戻り値をそのまま連結すればよい。
     *
     * @param string $conjunction 条件の前に付ける接続詞。条件だけが欲しい場合は空文字を渡す
     */
    public static function getConvertedFilterCondition(string $conjunction = 'AND'): string
    {
        if (self::showConvertedLeads()) {
            return '';
        }

        $condition = 'vtiger_leaddetails.converted = 0';
        if ($conjunction === '') {
            return $condition;
        }

        return ' ' . $conjunction . ' ' . $condition . ' ';
    }

    /**
     * パラメーターの値が有効（'true'）かどうかを判定する
     */
    private static function isEnabled(string $key): bool
    {
        $value = static::getParameterValue($key);
        if (!is_string($value)) {
            return false;
        }

        return strtolower(trim($value)) === 'true';
    }

    /**
     * パラメーターの生の値を取得する。
     * DB に依存しないテストから差し替えられるよう、この 1 箇所に閉じ込めている。
     *
     * @return mixed 未登録の場合は既定値の 'false'
     */
    protected static function getParameterValue(string $key): mixed
    {
        return Settings_Parameters_Record_Model::getParameterValue($key, 'false');
    }
}
