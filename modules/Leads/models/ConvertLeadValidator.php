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
 * リード昇格で保存する最終的な値（マッピング・昇格時デフォルト・入力をまとめた値）を検証する
 *
 * 画面に出していない項目も対象にする。旧来の vtws_convertlead は必須が空だと '????' で埋めて
 * 保存していたが、新画面からの昇格では保存前にここでエラーにする。
 *
 * カスタムバリデーション: Settings_CustomValidation_Module_Model::validateFieldValueForApi()
 * があれば項目ごとに呼ぶ（GetFields API と同じく class_exists で判定する。OSS 版には無い）。
 * 戻り値はエラー文言（問題なければ null）。
 */
class Leads_ConvertLeadValidator_Model
{
    /** 値を検証しない（システムが入れる・昇格処理が別途入れる）項目 */
    public const SYSTEM_FIELDS = [
        'assigned_user_id', 'isconvertedfromlead', 'imagename', 'createdtime', 'modifiedtime',
        'modifiedby', 'created_user_id', 'source', 'starred', 'tags',
    ];

    /** uitype: 自動採番・日時スタンプ */
    private const SYSTEM_UITYPES = ['4', '70'];

    private const EMAIL_PATTERN = '/^[\'_\/a-zA-Z0-9*]+([!"#$%&\'()*+,.\/:;<=>?\^_`\'{|}~-]?[\'a-zA-Z0-9\/_-])*@[a-zA-Z0-9]+([\_\.]?[a-zA-Z0-9\-]+)*\.([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)?$/';

    private const URL_PATTERN = '/(^|\s)((https?:\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/i';

    /**
     * 値が「空」かどうか。必須のチェックボックスはオフも空とみなす
     */
    public static function isEmptyValue(mixed $value, string $dataType = ''): bool
    {
        if ($value === null || $value === false) {
            return true;
        }
        if (is_array($value)) {
            return $value === [];
        }
        if (!is_scalar($value)) {
            return true;
        }
        $string = trim((string) $value);
        if ($string === '') {
            return true;
        }

        return $dataType === 'boolean' && in_array(strtolower($string), ['0', 'off', 'false', 'no'], true);
    }

    /**
     * 型の検証。空の値は検証しない（必須は isEmptyValue で別に見る）
     *
     * @return string|null エラーの言語キー
     */
    public static function validateValue(string $dataType, string $uitype, mixed $value): ?string
    {
        if (self::isEmptyValue($value) && !is_array($value) && !is_object($value)) {
            return null;
        }
        if (!is_scalar($value)) {
            return 'LBL_CONVERT_ERR_INVALID';
        }
        $string = trim((string) $value);

        switch ($dataType) {
            case 'email':
                return preg_match(self::EMAIL_PATTERN, $string) === 1 ? null : 'LBL_CONVERT_ERR_EMAIL';
            case 'url':
                return preg_match(self::URL_PATTERN, $string) === 1 ? null : 'LBL_CONVERT_ERR_URL';
            case 'integer':
                return preg_match('/^[-+]?\d+$/', $string) === 1 ? null : 'LBL_CONVERT_ERR_INTEGER';
            case 'double':
            case 'currency':
            case 'percentage':
                return is_numeric($string) ? null : 'LBL_CONVERT_ERR_NUMBER';
            case 'date':
                return self::isDate($string) ? null : 'LBL_CONVERT_ERR_DATE';
            case 'time':
                return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $string) === 1 ? null : 'LBL_CONVERT_ERR_TIME';
            case 'boolean':
                return in_array(strtolower($string), ['0', '1', 'on', 'off', 'true', 'false', 'yes', 'no'], true)
                    ? null : 'LBL_CONVERT_ERR_BOOLEAN';
        }

        return null;
    }

    /**
     * 昇格先 1 モジュール分の最終的な値を検証する
     *
     * @param array<string, mixed> $values     保存される最終的な値
     * @param list<string>         $displayed  昇格画面に出している項目
     * @param list<string>         $skipFields 昇格処理が値を入れる項目（関連づけ用の参照など）
     * @return list<array{module: string, field: string, label: string, message: string, hidden: bool, editable: bool, value: string}>
     */
    public static function validateModule(string $module, array $values, array $displayed, array $skipFields = []): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            return [];
        }
        $errors = [];
        foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
            $fieldName = (string) $fieldName;
            if (!$fieldModel instanceof Vtiger_Field_Model || !self::isTarget($fieldModel, $fieldName, $skipFields)) {
                continue;
            }
            $value = $values[$fieldName] ?? null;
            $dataType = (string) $fieldModel->getFieldDataType();
            $errorKey = null;
            if ($fieldModel->isMandatory() && self::isEmptyValue($value, $dataType)) {
                $errorKey = 'LBL_CONVERT_ERR_REQUIRED';
            } elseif (!self::isEmptyValue($value)) {
                $errorKey = self::validateValue($dataType, (string) $fieldModel->get('uitype'), $value)
                    ?? self::validatePicklist($fieldModel, $dataType, $value);
            }
            $label = vtranslate((string) $fieldModel->get('label'), $module);
            $message = $errorKey === null ? null : vtranslate($errorKey, 'Leads', $label);
            if ($message === null && !self::isEmptyValue($value)) {
                $message = self::runCustomValidation($fieldModel, $value, $values);
            }
            if ($message === null) {
                continue;
            }
            $editable = $fieldModel->isEditable();
            if (!$editable) {
                $message = vtranslate('LBL_CONVERT_ERR_NOT_EDITABLE', 'Leads', $label, $message);
            }
            $errors[] = [
                'module' => $module,
                'field' => $fieldName,
                'label' => $label,
                'message' => $message,
                'hidden' => !in_array($fieldName, $displayed, true),
                'editable' => $editable,
                // 追加表示した項目に、保存しようとした値をそのまま出して直せるようにする
                'value' => is_scalar($value) ? (string) $value : '',
            ];
        }

        return $errors;
    }

    /**
     * @param list<string> $skipFields
     */
    private static function isTarget(Vtiger_Field_Model $fieldModel, string $fieldName, array $skipFields): bool
    {
        if (in_array($fieldName, self::SYSTEM_FIELDS, true) || in_array($fieldName, $skipFields, true)) {
            return false;
        }
        if (in_array((string) $fieldModel->get('uitype'), self::SYSTEM_UITYPES, true)) {
            return false;
        }
        if (!in_array((int) $fieldModel->get('presence'), [0, 2], true)) {
            return false;
        }

        return (int) $fieldModel->get('displaytype') === 1;
    }

    private static function validatePicklist(Vtiger_Field_Model $fieldModel, string $dataType, mixed $value): ?string
    {
        if (($dataType !== 'picklist' && $dataType !== 'multipicklist') || !is_scalar($value)) {
            return null;
        }
        $allowed = $fieldModel->getPicklistValues();
        if (!is_array($allowed)) {
            return null;
        }
        $selected = $dataType === 'multipicklist' ? explode(' |##| ', (string) $value) : [(string) $value];
        foreach ($selected as $item) {
            if ($item !== '' && !array_key_exists($item, $allowed) && !array_key_exists(decode_html($item), $allowed)) {
                return 'LBL_CONVERT_ERR_PICKLIST';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function runCustomValidation(Vtiger_Field_Model $fieldModel, mixed $value, array $values): ?string
    {
        $hook = ['Settings_CustomValidation_Module_Model', 'validateFieldValueForApi'];
        if (!class_exists($hook[0]) || !is_callable($hook)) {
            return null;
        }
        $message = call_user_func($hook, $fieldModel->getId(), $value, $values);

        return is_string($message) && $message !== '' ? $message : null;
    }

    private static function isDate(string $value): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) !== 1) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }
}
