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
 * リード昇格画面の設定（表示項目・昇格時デフォルト値・昇格済み判定条件）を読み書きする
 *
 * テーブルは setup/migration/scripts/20261021000000_create_convertlead_settings_tables.php で作る。
 * 項目は fieldid ではなく「昇格先モジュール名 + 項目名」で持つ。
 * 項目のマッピング（vtiger_convertleadmapping）は Settings_Leads_Mapping_Model が扱う。
 */
class Leads_ConvertLeadSettings_Model
{
    /** 昇格先モジュール */
    public const SUPPORTED_MODULES = ['Accounts', 'Contacts', 'Potentials'];

    /** 昇格済み判定（既存レコードへのアタッチ）ができるモジュール */
    public const MATCH_MODULES = ['Accounts', 'Contacts'];

    /**
     * 昇格画面に出す項目（並び順どおり）
     *
     * @return list<string>
     */
    public static function getLayout(string $module): array
    {
        self::assertModule($module, self::SUPPORTED_MODULES);
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT fieldname FROM vtiger_convertlead_layout WHERE module = ? ORDER BY sequence, fieldname',
            [$module]
        );
        $fieldNames = [];
        foreach (self::rows($db, $result) as $row) {
            $fieldNames[] = self::toString($row['fieldname'] ?? '');
        }

        return $fieldNames;
    }

    /**
     * 昇格画面に出す項目を保存する（指定した順に並ぶ）
     *
     * @param list<string> $fieldNames
     */
    public static function saveLayout(string $module, array $fieldNames): void
    {
        self::assertModule($module, self::SUPPORTED_MODULES);
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_convertlead_layout WHERE module = ?', [$module]);
        $sequence = 0;
        foreach (array_values(array_unique($fieldNames)) as $fieldName) {
            $sequence++;
            $db->pquery(
                'INSERT INTO vtiger_convertlead_layout (module, fieldname, sequence) VALUES (?, ?, ?)',
                [$module, $fieldName, $sequence]
            );
        }
    }

    /**
     * 昇格時デフォルト値（項目名 => 値）
     *
     * @return array<string, string>
     */
    public static function getDefaults(string $module): array
    {
        self::assertModule($module, self::SUPPORTED_MODULES);
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT fieldname, defaultvalue FROM vtiger_convertlead_defaults WHERE module = ? ORDER BY fieldname',
            [$module]
        );
        $defaults = [];
        foreach (self::rows($db, $result) as $row) {
            $defaults[self::toString($row['fieldname'] ?? '')] = decode_html(self::toString($row['defaultvalue'] ?? ''));
        }

        return $defaults;
    }

    /**
     * 昇格時デフォルト値を保存する。空文字の項目は「設定なし」として保存しない
     *
     * @param array<string, string> $defaults
     */
    public static function saveDefaults(string $module, array $defaults): void
    {
        self::assertModule($module, self::SUPPORTED_MODULES);
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_convertlead_defaults WHERE module = ?', [$module]);
        foreach ($defaults as $fieldName => $value) {
            if ($value === '') {
                continue;
            }
            $db->pquery(
                'INSERT INTO vtiger_convertlead_defaults (module, fieldname, defaultvalue) VALUES (?, ?, ?)',
                [$module, $fieldName, $value]
            );
        }
    }

    /**
     * 昇格済み判定条件。条件ごとに AND で比べる項目名の配列を返す（条件同士は OR）
     *
     * @return list<list<string>>
     */
    public static function getMatchRules(string $module): array
    {
        self::assertModule($module, self::MATCH_MODULES);
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT fieldnames FROM vtiger_convertlead_matchrules WHERE module = ? ORDER BY sequence, ruleid',
            [$module]
        );
        $rules = [];
        foreach (self::rows($db, $result) as $row) {
            $decoded = json_decode(decode_html(self::toString($row['fieldnames'] ?? '')), true);
            if (!is_array($decoded)) {
                continue;
            }
            $fieldNames = [];
            foreach ($decoded as $fieldName) {
                if (is_string($fieldName) && $fieldName !== '') {
                    $fieldNames[] = $fieldName;
                }
            }
            if ($fieldNames !== []) {
                $rules[] = $fieldNames;
            }
        }

        return $rules;
    }

    /**
     * 昇格済み判定条件を保存する。項目の無い条件は保存しない
     *
     * @param list<list<string>> $rules
     */
    public static function saveMatchRules(string $module, array $rules): void
    {
        self::assertModule($module, self::MATCH_MODULES);
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_convertlead_matchrules WHERE module = ?', [$module]);
        $sequence = 0;
        foreach ($rules as $fieldNames) {
            $fieldNames = array_values(array_unique($fieldNames));
            if ($fieldNames === []) {
                continue;
            }
            $sequence++;
            $db->pquery(
                'INSERT INTO vtiger_convertlead_matchrules (module, fieldnames, sequence) VALUES (?, ?, ?)',
                [$module, json_encode($fieldNames), $sequence]
            );
        }
    }

    /**
     * @param list<string> $allowed
     */
    private static function assertModule(string $module, array $allowed): void
    {
        if (!in_array($module, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported module: ' . $module);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rows(PearDatabase $db, mixed $result): array
    {
        $rows = [];
        if ($result === false) {
            return $rows;
        }
        $count = (int) $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private static function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
