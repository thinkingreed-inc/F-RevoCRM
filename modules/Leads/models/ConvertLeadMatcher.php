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
 * 昇格済み判定: 昇格で作る予定の値と一致する既存レコード（企業・担当者）を探す
 *
 * - 条件（vtiger_convertlead_matchrules の 1 行）の項目同士は AND、条件同士は OR。
 *   上から順に調べ、最初に当たった条件の結果を返す。
 * - 比較は前後の空白を除いた完全一致（大文字小文字は DB の照合順序に従う）。
 *   値が空の項目を含む条件は調べない（空同士を一致とみなさない）。
 * - 参照項目（担当者の「顧客企業名」など）は、既存レコードを選んでいればその ID で、
 *   そうでなければ参照先レコードの名前（vtiger_crmentity.label）で比べる。
 * - 結果は閲覧権限のあるレコードだけ。更新日時の新しい順に最大 MAX_RECORDS 件を返す。
 */
class Leads_ConvertLeadMatcher_Model
{
    public const MAX_RECORDS = 20;

    /** 権限で絞る前に DB から取る件数の上限 */
    private const FETCH_LIMIT = 200;

    /**
     * @param array<string, mixed> $values       昇格で入る予定の値（項目名 => 値）
     * @param array<string, int>   $referenceIds 参照項目に入る既存レコードの ID（項目名 => crmid）
     * @param (callable(string, int): bool)|null $canView 閲覧権限の判定（省略時は Users_Privileges_Model::isPermitted）
     * @return array{rule: list<string>, ruleLabel: string, total: int, records: list<array{id: int, label: string, accountId: int|null, accountLabel: string|null}>}|null
     */
    public static function findMatches(string $module, array $values, array $referenceIds = [], ?callable $canView = null): ?array
    {
        $canView ??= static fn (string $module, int $id): bool => (bool) Users_Privileges_Model::isPermitted($module, 'DetailView', $id);
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            return null;
        }
        foreach (Leads_ConvertLeadSettings_Model::getMatchRules($module) as $rule) {
            $records = self::findByRule($moduleModel, $rule, $values, $referenceIds, $canView);
            if ($records === null || $records === []) {
                continue;
            }

            return [
                'rule' => $rule,
                'ruleLabel' => self::ruleLabel($moduleModel, $rule),
                'total' => count($records),
                'records' => array_slice($records, 0, self::MAX_RECORDS),
            ];
        }

        return null;
    }

    /**
     * 条件の表示名（例: 姓 + 名 + 顧客企業名）
     *
     * @param list<string> $rule
     */
    public static function ruleLabel(Vtiger_Module_Model $moduleModel, array $rule): string
    {
        $labels = [];
        foreach ($rule as $fieldName) {
            $fieldModel = $moduleModel->getField($fieldName);
            $labels[] = $fieldModel instanceof Vtiger_Field_Model
                ? vtranslate((string) $fieldModel->get('label'), $moduleModel->getName())
                : $fieldName;
        }

        return implode(' + ', $labels);
    }

    /**
     * @param list<string>         $rule
     * @param array<string, mixed> $values
     * @param array<string, int>   $referenceIds
     * @param callable(string, int): bool $canView
     * @return list<array{id: int, label: string, accountId: int|null, accountLabel: string|null}>|null 調べられない条件は null
     */
    private static function findByRule(Vtiger_Module_Model $moduleModel, array $rule, array $values, array $referenceIds, callable $canView): ?array
    {
        $module = (string) $moduleModel->getName();
        $focus = CRMEntity::getInstance($module);
        $baseTable = (string) $focus->table_name;
        $baseIndex = (string) $focus->table_index;
        /** @var array<string, string> $tableIndex */
        $tableIndex = (array) $focus->tab_name_index;

        $joins = [];
        $conditions = [];
        $params = [];
        foreach ($rule as $fieldName) {
            $fieldModel = $moduleModel->getField($fieldName);
            if (!$fieldModel instanceof Vtiger_Field_Model) {
                return null;
            }
            $table = (string) $fieldModel->get('table');
            $column = (string) $fieldModel->get('column');
            if ($table !== 'vtiger_crmentity' && $table !== $baseTable) {
                if (!isset($tableIndex[$table])) {
                    return null;
                }
                $joins[$table] = "LEFT JOIN {$table} ON {$table}.{$tableIndex[$table]} = {$baseTable}.{$baseIndex}";
            }
            $qualified = "{$table}.{$column}";

            if ($fieldModel->getFieldDataType() === 'reference' && !empty($referenceIds[$fieldName])) {
                $conditions[] = "{$qualified} = ?";
                $params[] = (int) $referenceIds[$fieldName];
                continue;
            }
            $value = $values[$fieldName] ?? null;
            if (!is_scalar($value) || trim((string) $value) === '') {
                return null;
            }
            $value = trim((string) $value);
            if ($fieldModel->getFieldDataType() === 'reference') {
                $referenceModules = $fieldModel->getReferenceList();
                if ($referenceModules === []) {
                    return null;
                }
                $conditions[] = "{$qualified} IN (SELECT ref.crmid FROM vtiger_crmentity ref WHERE ref.deleted = 0"
                    . ' AND ref.setype IN (' . generateQuestionMarks($referenceModules) . ') AND ref.label IN (?, ?))';
                $params = array_merge($params, $referenceModules, [$value, htmlentities($value, ENT_QUOTES, 'UTF-8')]);
                continue;
            }
            // 保存経路によって HTML エンティティに変換された値と生の値が混在するため両方と比べる
            $conditions[] = "{$qualified} IN (?, ?)";
            $params[] = $value;
            $params[] = htmlentities($value, ENT_QUOTES, 'UTF-8');
        }
        if ($conditions === []) {
            return null;
        }

        $db = PearDatabase::getInstance();
        $sql = "SELECT vtiger_crmentity.crmid, vtiger_crmentity.label FROM {$baseTable}"
            . " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = {$baseTable}.{$baseIndex} "
            . implode(' ', $joins)
            . ' WHERE vtiger_crmentity.deleted = 0 AND ' . implode(' AND ', $conditions)
            . ' ORDER BY vtiger_crmentity.modifiedtime DESC, vtiger_crmentity.crmid DESC LIMIT ' . self::FETCH_LIMIT;
        $result = $db->pquery($sql, $params);
        if ($result === false) {
            return null;
        }

        $records = [];
        $count = (int) $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $id = (int) $db->query_result($result, $i, 'crmid');
            if (!$canView($module, $id)) {
                continue;
            }
            $records[] = [
                'id' => $id,
                'label' => decode_html((string) $db->query_result($result, $i, 'label')),
                'accountId' => null,
                'accountLabel' => null,
            ];
        }
        if ($module === 'Contacts') {
            $records = self::withAccounts($records);
        }

        return $records;
    }

    /**
     * 担当者の所属企業を付ける（別の企業に所属している担当者に紐づける場合の警告に使う）
     *
     * @param list<array{id: int, label: string, accountId: int|null, accountLabel: string|null}> $records
     * @return list<array{id: int, label: string, accountId: int|null, accountLabel: string|null}>
     */
    public static function withAccounts(array $records): array
    {
        if ($records === []) {
            return $records;
        }
        $ids = array_column($records, 'id');
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT c.contactid, a.crmid AS accountid, a.label AS accountlabel FROM vtiger_contactdetails c'
            . ' LEFT JOIN vtiger_crmentity a ON a.crmid = c.accountid AND a.deleted = 0'
            . ' WHERE c.contactid IN (' . generateQuestionMarks($ids) . ')',
            $ids
        );
        $accounts = [];
        $count = $result === false ? 0 : (int) $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $accountId = (int) $db->query_result($result, $i, 'accountid');
            if ($accountId > 0) {
                $accounts[(int) $db->query_result($result, $i, 'contactid')] = [
                    $accountId,
                    decode_html((string) $db->query_result($result, $i, 'accountlabel')),
                ];
            }
        }
        foreach ($records as $index => $record) {
            if (isset($accounts[$record['id']])) {
                $records[$index]['accountId'] = $accounts[$record['id']][0];
                $records[$index]['accountLabel'] = $accounts[$record['id']][1];
            }
        }

        return $records;
    }
}
