<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * リード昇格画面の「担当」の初期値を決める — #1864
 *
 * パラメータ設定 LEAD_CONVERT_ASSIGN_CURRENT_USER が true のときはログインユーザー、
 * それ以外（未登録・false）は昇格元リードの担当を初期値にする。
 * 決めるのは初期値だけで、昇格画面で担当を選び直すことは今までどおりできる。
 */
class Leads_ConvertLeadAssignee_Model
{
    public const PARAMETER_KEY = 'LEAD_CONVERT_ASSIGN_CURRENT_USER';

    /**
     * パラメータ設定を読んで、昇格画面の担当の初期値を返す
     */
    public static function getDefaultAssignedUserId(mixed $leadAssignedUserId, mixed $currentUserId): mixed
    {
        $parameterValue = Settings_Parameters_Record_Model::getParameterValue(self::PARAMETER_KEY, 'false');

        return self::resolveDefaultAssignedUserId($leadAssignedUserId, $currentUserId, $parameterValue);
    }

    /**
     * パラメータの値に応じて、昇格画面の担当の初期値を返す
     */
    public static function resolveDefaultAssignedUserId(
        mixed $leadAssignedUserId,
        mixed $currentUserId,
        mixed $parameterValue
    ): mixed {
        if (self::isAssignCurrentUserEnabled($parameterValue) && !empty($currentUserId)) {
            return $currentUserId;
        }

        return $leadAssignedUserId;
    }

    /**
     * パラメータの値が「ログインユーザーを初期値にする」を表すか
     *
     * 他のフラグ（CALENDAR_REMEMBER_FEED_SELECTION など）と同じく、
     * 大文字小文字と前後の空白を無視した true と 1 を有効とみなす。
     */
    public static function isAssignCurrentUserEnabled(mixed $parameterValue): bool
    {
        if (!is_scalar($parameterValue)) {
            return false;
        }
        $value = strtolower(trim((string) $parameterValue));

        return $value === 'true' || $value === '1';
    }
}
