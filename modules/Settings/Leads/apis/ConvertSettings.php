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
 * リードマッピング設定画面（<lead-mapping-settings>）の JSON API。システム管理者のみ
 *
 * GET  ?module=Leads&parent=Settings&api=ConvertSettings&mode=info
 *        マッピング・選べる項目・昇格画面の表示項目・昇格時デフォルト値・判定条件
 * POST mode=saveMapping    mapping=[{id?, lead, Accounts, Contacts, Potentials, deleted?}]（JSON）
 * POST mode=saveLayout     target=<昇格先>&fields=[項目名]（JSON）
 * POST mode=saveDefaults   target=<昇格先>&defaults={項目名: 値}（JSON。値は DB 形式）
 * POST mode=saveMatchRules target=Accounts|Contacts&rules=[[項目名]]（JSON）
 *
 * マッピングの保存は Settings_Leads_Mapping_Model::save() に任せる（editable=0 の行は更新・削除されない）。
 * 選べる項目と型の制約は旧設定画面（Settings_Leads_Module_Model / Field）と同じ。
 * 保存時の入力エラーは { saved: false, errors: [{ field, message }] } で返す。
 */
class Settings_Leads_ConvertSettings_Api extends Vtiger_Api_Controller
{
    private const QUALIFIED_MODULE = 'Settings:Leads';

    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * @return list<array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (empty($currentUser) || !$currentUser->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        return true;
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $mode = (string) $request->get('mode');
        if ($mode === 'info') {
            return $this->sendSuccess($this->getInfo());
        }
        $request->validateWriteAccess();
        switch ($mode) {
            case 'saveMapping':
                return $this->sendSuccess($this->saveMapping(self::jsonParam($request, 'mapping')));
            case 'saveLayout':
                return $this->sendSuccess($this->saveLayout(self::target($request), self::jsonParam($request, 'fields')));
            case 'saveDefaults':
                return $this->sendSuccess($this->saveDefaults(self::target($request), self::jsonParam($request, 'defaults')));
            case 'saveMatchRules':
                return $this->sendSuccess($this->saveMatchRules(self::target($request), self::jsonParam($request, 'rules')));
        }
        throw new ApiBadRequestException('Invalid mode: ' . $mode);
    }

    /**
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        $mappingModel = new Settings_Leads_Mapping_Model();
        $mapping = [];
        foreach ($mappingModel->getMapping() as $mappingId => $row) {
            $mapping[] = [
                'id' => (int) $mappingId,
                'editable' => (int) $row['editable'] === 1,
                'lead' => self::fieldIdOf($row['Leads'] ?? null),
                'Accounts' => self::fieldIdOf($row['Accounts'] ?? null),
                'Contacts' => self::fieldIdOf($row['Contacts'] ?? null),
                'Potentials' => self::fieldIdOf($row['Potentials'] ?? null),
            ];
        }

        $modules = [];
        foreach (Leads_ConvertLeadSettings_Model::SUPPORTED_MODULES as $module) {
            $modules[$module] = [
                'label' => vtranslate('SINGLE_' . $module, $module),
                'mappableFields' => self::mappableFields($module),
                'inputFields' => self::inputFields($module),
                'matchableFields' => in_array($module, Leads_ConvertLeadSettings_Model::MATCH_MODULES, true) ? self::matchableFields($module) : [],
                'layout' => Leads_ConvertLeadSettings_Model::getLayout($module),
                'defaults' => (object) Leads_ConvertLeadSettings_Model::getDefaults($module),
                'matchRules' => in_array($module, Leads_ConvertLeadSettings_Model::MATCH_MODULES, true)
                    ? Leads_ConvertLeadSettings_Model::getMatchRules($module) : [],
            ];
        }

        return [
            'mapping' => $mapping,
            'leadFields' => self::mappableFields('Leads'),
            'restrictedFieldIds' => array_map('intval', Settings_Leads_Mapping_Model::getRestrictedFieldIdsList()),
            'modules' => $modules,
        ];
    }

    /**
     * @param array<mixed> $rows
     * @return array{saved: bool, errors: list<array{field: string, message: string}>}
     */
    public function saveMapping(array $rows): array
    {
        $leadFields = self::fieldTypesById('Leads');
        $targetFields = [];
        foreach (Leads_ConvertLeadSettings_Model::SUPPORTED_MODULES as $module) {
            $targetFields[$module] = self::fieldTypesById($module);
        }
        $keys = ['Accounts' => 'account', 'Contacts' => 'contact', 'Potentials' => 'potential'];

        $lockedIds = self::lockedMappingIds();
        $mapping = [];
        $errors = [];
        foreach (array_values($rows) as $index => $row) {
            // editable=0 の行は画面から何を送られても触らない
            if (!is_array($row) || in_array(self::intOf($row['id'] ?? 0), $lockedIds, true)) {
                continue;
            }
            $leadFieldId = self::intOf($row['lead'] ?? 0);
            $entry = ['mappingId' => self::intOf($row['id'] ?? 0), 'lead' => $leadFieldId];
            if (!empty($row['deleted'])) {
                $entry['deletable'] = true;
            }
            if ($leadFieldId > 0 && !isset($leadFields[$leadFieldId])) {
                $errors[] = ['field' => "mapping.{$index}.lead", 'message' => vtranslate('LBL_CONVERT_SETTINGS_ERR_FIELD', self::QUALIFIED_MODULE)];
                continue;
            }
            foreach ($keys as $module => $key) {
                $targetId = self::intOf($row[$module] ?? 0);
                $entry[$key] = $targetId;
                if ($targetId === 0 || isset($entry['deletable'])) {
                    continue;
                }
                if (!isset($targetFields[$module][$targetId]) || $targetFields[$module][$targetId] !== ($leadFields[$leadFieldId] ?? null)) {
                    $errors[] = ['field' => "mapping.{$index}.{$module}", 'message' => vtranslate('LBL_CONVERT_SETTINGS_ERR_TYPE', self::QUALIFIED_MODULE)];
                }
            }
            $mapping[] = $entry;
        }
        if ($errors !== []) {
            return ['saved' => false, 'errors' => $errors];
        }
        Settings_Leads_Mapping_Model::getCleanInstance()->save($mapping);

        return ['saved' => true, 'errors' => []];
    }

    /**
     * @param array<mixed> $fields
     * @return array{saved: bool, errors: list<array{field: string, message: string}>}
     */
    public function saveLayout(string $module, array $fields): array
    {
        $allowed = array_column(self::inputFields($module), 'name');
        $names = [];
        foreach ($fields as $fieldName) {
            if (!is_string($fieldName) || !in_array($fieldName, $allowed, true)) {
                return ['saved' => false, 'errors' => [['field' => is_string($fieldName) ? $fieldName : '', 'message' => vtranslate('LBL_CONVERT_SETTINGS_ERR_FIELD', self::QUALIFIED_MODULE)]]];
            }
            $names[] = $fieldName;
        }
        Leads_ConvertLeadSettings_Model::saveLayout($module, $names);

        return ['saved' => true, 'errors' => []];
    }

    /**
     * @param array<mixed> $defaults
     * @return array{saved: bool, errors: list<array{field: string, message: string}>}
     */
    public function saveDefaults(string $module, array $defaults): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $allowed = array_column(self::inputFields($module), 'name');
        $values = [];
        $errors = [];
        foreach ($defaults as $fieldName => $value) {
            $fieldName = (string) $fieldName;
            $fieldModel = $moduleModel instanceof Vtiger_Module_Model ? $moduleModel->getField($fieldName) : null;
            if (!$fieldModel instanceof Vtiger_Field_Model || !in_array($fieldName, $allowed, true)) {
                $errors[] = ['field' => $fieldName, 'message' => vtranslate('LBL_CONVERT_SETTINGS_ERR_FIELD', self::QUALIFIED_MODULE)];
                continue;
            }
            if (is_array($value)) {
                $value = implode(' |##| ', array_map(static fn ($item): string => is_scalar($item) ? (string) $item : '', $value));
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            if (!is_scalar($value) && $value !== null) {
                $errors[] = ['field' => $fieldName, 'message' => vtranslate('LBL_CONVERT_ERR_INVALID', 'Leads', vtranslate((string) $fieldModel->get('label'), $module))];
                continue;
            }
            $value = trim((string) $value);
            $errorKey = Leads_ConvertLeadValidator_Model::validateValue((string) $fieldModel->getFieldDataType(), (string) $fieldModel->get('uitype'), $value);
            if ($errorKey !== null) {
                $errors[] = ['field' => $fieldName, 'message' => vtranslate($errorKey, 'Leads', vtranslate((string) $fieldModel->get('label'), $module))];
                continue;
            }
            $values[$fieldName] = $value;
        }
        if ($errors !== []) {
            return ['saved' => false, 'errors' => $errors];
        }
        Leads_ConvertLeadSettings_Model::saveDefaults($module, $values);

        return ['saved' => true, 'errors' => []];
    }

    /**
     * @param array<mixed> $rules
     * @return array{saved: bool, errors: list<array{field: string, message: string}>}
     */
    public function saveMatchRules(string $module, array $rules): array
    {
        if (!in_array($module, Leads_ConvertLeadSettings_Model::MATCH_MODULES, true)) {
            throw new ApiBadRequestException('Unsupported module: ' . $module);
        }
        $allowed = array_column(self::matchableFields($module), 'name');
        $normalized = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $fieldNames = [];
            foreach ($rule as $fieldName) {
                if (!is_string($fieldName) || !in_array($fieldName, $allowed, true)) {
                    return ['saved' => false, 'errors' => [['field' => is_string($fieldName) ? $fieldName : '', 'message' => vtranslate('LBL_CONVERT_SETTINGS_ERR_FIELD', self::QUALIFIED_MODULE)]]];
                }
                $fieldNames[] = $fieldName;
            }
            if ($fieldNames !== []) {
                $normalized[] = $fieldNames;
            }
        }
        Leads_ConvertLeadSettings_Model::saveMatchRules($module, $normalized);

        return ['saved' => true, 'errors' => []];
    }

    /**
     * マッピングで選べる項目（旧設定画面と同じ候補・型）
     *
     * @return list<array{id: int, name: string, label: string, type: string}>
     */
    private static function mappableFields(string $module): array
    {
        $fields = [];
        foreach (Settings_Leads_Module_Model::getInstance($module)->getFields() as $type => $fieldModels) {
            foreach ($fieldModels as $fieldId => $fieldModel) {
                $fields[] = [
                    'id' => (int) $fieldId,
                    'name' => (string) $fieldModel->getName(),
                    'label' => vtranslate((string) $fieldModel->get('label'), $module),
                    'type' => (string) $type,
                ];
            }
        }

        return $fields;
    }

    /**
     * @return array<int, string> 項目ID => 型
     */
    private static function fieldTypesById(string $module): array
    {
        $types = [];
        foreach (self::mappableFields($module) as $field) {
            $types[$field['id']] = $field['type'];
        }

        return $types;
    }

    /**
     * 昇格画面に出せる項目（表示項目・昇格時デフォルト値の候補）
     *
     * @return list<array{name: string, label: string, mandatory: bool}>
     */
    private static function inputFields(string $module): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            return [];
        }
        $fields = [];
        foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
            if ($fieldModel instanceof Vtiger_Field_Model && Leads_ConvertLeadService_Model::isInputField($module, $fieldModel)) {
                $fields[] = [
                    'name' => (string) $fieldName,
                    'label' => vtranslate((string) $fieldModel->get('label'), $module),
                    'mandatory' => $fieldModel->isMandatory(),
                ];
            }
        }

        return $fields;
    }

    /**
     * 判定条件に使える項目（関連づけ用の参照項目も含む）
     *
     * @return list<array{name: string, label: string, mandatory: bool}>
     */
    private static function matchableFields(string $module): array
    {
        $fields = self::inputFields($module);
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        foreach (Leads_ConvertLeadService_Model::LINK_FIELDS[$module] as $fieldName) {
            $fieldModel = $moduleModel instanceof Vtiger_Module_Model ? $moduleModel->getField($fieldName) : null;
            if ($fieldModel instanceof Vtiger_Field_Model && $fieldModel->isViewable()) {
                $fields[] = [
                    'name' => $fieldName,
                    'label' => vtranslate((string) $fieldModel->get('label'), $module),
                    'mandatory' => $fieldModel->isMandatory(),
                ];
            }
        }

        return $fields;
    }

    /**
     * @return list<int>
     */
    private static function lockedMappingIds(): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT cfmid FROM vtiger_convertleadmapping WHERE editable = 0', []);
        $ids = [];
        $count = $result === false ? 0 : (int) $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $ids[] = (int) $db->query_result($result, $i, 'cfmid');
        }

        return $ids;
    }

    private static function fieldIdOf(mixed $fieldInfo): ?int
    {
        return is_array($fieldInfo) && isset($fieldInfo['id']) ? self::intOf($fieldInfo['id']) : null;
    }

    private static function intOf(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function target(Vtiger_Request $request): string
    {
        $target = (string) $request->get('target');
        if (!in_array($target, Leads_ConvertLeadSettings_Model::SUPPORTED_MODULES, true)) {
            throw new ApiBadRequestException('Unsupported module: ' . $target);
        }

        return $target;
    }

    /**
     * @return array<mixed>
     */
    private static function jsonParam(Vtiger_Request $request, string $key): array
    {
        $raw = $request->getRaw($key);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded)) {
            throw new ApiBadRequestException('Invalid ' . $key);
        }

        return $decoded;
    }
}
