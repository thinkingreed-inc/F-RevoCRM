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
 * リード昇格で昇格先の各項目に入る値を決める
 *
 * 同じ項目に複数の値がある場合の優先順位:
 *   ユーザーの入力 ＞ マッピングの値（リードの値が空でない場合） ＞ 昇格時デフォルト値 ＞ 項目のデフォルト値
 *
 * 値はすべて DB 形式（日付は Y-m-d、数値は区切り無し、参照は crmid）で扱う。
 */
class Leads_ConvertLeadValues_Model
{
    /**
     * 4 種類の値を優先順位に従って 1 つにまとめる
     *
     * 入力はキーがあれば空文字でも採用する（ユーザーが消した値を復活させないため）。
     * マッピング・デフォルトは空のものを「値なし」として次の候補に譲る。
     *
     * @param array<string, mixed> $mapped         マッピングで入る値
     * @param array<string, mixed> $convertDefaults 昇格時デフォルト値
     * @param array<string, mixed> $fieldDefaults   項目のデフォルト値
     * @param array<string, mixed> $input          ユーザーの入力
     * @return array<string, mixed>
     */
    public static function resolve(array $mapped, array $convertDefaults, array $fieldDefaults, array $input): array
    {
        $values = [];
        foreach ([$fieldDefaults, $convertDefaults, $mapped] as $candidates) {
            foreach ($candidates as $fieldName => $value) {
                if (!self::isBlank($value)) {
                    $values[$fieldName] = $value;
                }
            }
        }
        foreach ($input as $fieldName => $value) {
            $values[$fieldName] = $value;
        }

        return $values;
    }

    /**
     * マッピング（vtiger_convertleadmapping）に従って、リードの値を昇格先の項目名で返す
     *
     * @return array<string, mixed>
     */
    public static function getMappedValues(Vtiger_Record_Model $lead, string $module): array
    {
        $column = ['Accounts' => 'accountfid', 'Contacts' => 'contactfid', 'Potentials' => 'potentialfid'][$module] ?? null;
        if ($column === null) {
            return [];
        }
        $leadModule = Vtiger_Module_Model::getInstance('Leads');
        $targetModule = Vtiger_Module_Model::getInstance($module);
        if (!$leadModule instanceof Vtiger_Module_Model || !$targetModule instanceof Vtiger_Module_Model) {
            return [];
        }
        $leadFields = $leadModule->getFieldsById();
        $targetFields = $targetModule->getFieldsById();

        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT leadfid, {$column} AS targetfid FROM vtiger_convertleadmapping", []);
        $values = [];
        $count = $result === false ? 0 : (int) $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $leadFieldId = $db->query_result($result, $i, 'leadfid');
            $targetFieldId = $db->query_result($result, $i, 'targetfid');
            $leadField = $leadFields[$leadFieldId] ?? null;
            $targetField = $targetFields[$targetFieldId] ?? null;
            if (!$leadField instanceof Vtiger_Field_Model || !$targetField instanceof Vtiger_Field_Model) {
                continue;
            }
            /** @var mixed $value */
            $value = $lead->get($leadField->getName());
            $values[$targetField->getName()] = is_string($value) ? decode_html($value) : $value;
        }

        return $values;
    }

    /**
     * 項目に設定されたデフォルト値（空のものは除く）
     *
     * @return array<string, mixed>
     */
    public static function getFieldDefaults(Vtiger_Module_Model $module): array
    {
        $defaults = [];
        foreach ($module->getFields() as $fieldName => $fieldModel) {
            if (!$fieldModel instanceof Vtiger_Field_Model || $fieldName === 'isconvertedfromlead') {
                continue;
            }
            $value = $fieldModel->getDefaultFieldValue();
            if (!self::isBlank($value)) {
                $defaults[(string) $fieldName] = is_string($value) ? decode_html($value) : $value;
            }
        }

        return $defaults;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || (is_string($value) && trim($value) === '');
    }
}
