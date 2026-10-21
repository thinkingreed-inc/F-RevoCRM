<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 *************************************************************************************/

vimport('~~/include/Webservices/ConvertLead.php');

/**
 * リード昇格（Web Components 版）の画面情報の組み立てと、検証つきの昇格
 *
 * 昇格そのものは vtws_convertlead を strict モードで呼ぶ（既存レコードは existingId で渡す）。
 * 呼ぶ前に、保存される最終的な値（マッピング・昇格時デフォルト・入力をまとめた値）を
 * 画面に出していない項目も含めて検証し、エラーがあれば昇格しない。
 *
 * 値はすべて DB 形式（日付は Y-m-d、数値は区切り無し、参照は crmid）で受け渡す。
 */
class Leads_ConvertLeadService_Model
{
    public const MODE_CREATE = 'create';
    public const MODE_EXISTING = 'existing';
    public const MODE_NONE = 'none';

    /** 昇格処理が関連づけのために値を入れる項目（入力・検証の対象外） */
    public const LINK_FIELDS = [
        'Accounts' => [],
        'Contacts' => ['account_id'],
        'Potentials' => ['related_to', 'contact_id'],
    ];

    private Vtiger_Record_Model $lead;

    public function __construct(Vtiger_Record_Model $lead)
    {
        $this->lead = $lead;
    }

    /**
     * 昇格画面の担当者（assigned user）の初期値
     *
     * #1864（担当の初期値をログインユーザーにするパラメータ）を取り込む場合はここを差し替える。
     */
    public function getDefaultAssignedUserId(): string
    {
        /** @var mixed $value */
        $value = $this->lead->get('assigned_user_id');

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * 昇格画面の初期表示に必要な情報
     *
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        $modules = [];
        $resolved = [];
        foreach (Leads_ConvertLeadSettings_Model::SUPPORTED_MODULES as $module) {
            $available = self::isModuleAvailable($module);
            $displayed = $available ? self::getDisplayedFields($module) : [];
            $values = $available ? $this->resolveValues($module, []) : [];
            $resolved[$module] = $values;
            $initial = [];
            $labels = [];
            foreach ($displayed as $fieldName) {
                $value = $values[$fieldName] ?? '';
                $initial[$fieldName] = is_scalar($value) ? (string) $value : '';
                $label = $this->referenceLabel($module, $fieldName, $initial[$fieldName]);
                if ($label !== null) {
                    $labels[$fieldName] = $label;
                }
            }
            $modules[$module] = [
                'available' => $available,
                'label' => vtranslate('SINGLE_' . $module, $module),
                'fields' => $displayed,
                'values' => (object) $initial,
                'referenceLabels' => (object) $labels,
                'match' => null,
            ];
        }

        // 判定は企業を先に行い、担当者の「顧客企業名」の比較に当たった企業を使う
        $accountMatch = $modules['Accounts']['available'] ? Leads_ConvertLeadMatcher_Model::findMatches('Accounts', $resolved['Accounts']) : null;
        $modules['Accounts']['match'] = $accountMatch;
        if ($modules['Contacts']['available']) {
            $contactValues = $resolved['Contacts'];
            $contactValues['account_id'] = $resolved['Accounts']['accountname'] ?? '';
            $referenceIds = $accountMatch !== null ? ['account_id' => $accountMatch['records'][0]['id']] : [];
            $modules['Contacts']['match'] = Leads_ConvertLeadMatcher_Model::findMatches('Contacts', $contactValues, $referenceIds);
        }

        $transferOptions = [];
        foreach (['Contacts', 'Accounts'] as $module) {
            if ($modules[$module]['available']) {
                $transferOptions[] = ['module' => $module, 'label' => $modules[$module]['label']];
            }
        }

        return [
            'record' => ['id' => (int) $this->lead->getId(), 'label' => decode_html((string) $this->lead->getName())],
            'assignedUserId' => $this->getDefaultAssignedUserId(),
            'transferOptions' => $transferOptions,
            'constraints' => [
                'contactAccountMandatory' => self::isFieldMandatory('Contacts', 'account_id'),
                'potentialAccountMandatory' => self::isFieldMandatory('Potentials', 'related_to'),
                'potentialContactMandatory' => self::isFieldMandatory('Potentials', 'contact_id'),
            ],
            'modules' => $modules,
        ];
    }

    /**
     * 検証して昇格する
     *
     * @param array<string, mixed> $data { modules: {M: {mode, existingId?, values}}, assignedUserId, transferTo }
     * @return array{converted: bool, errors: list<array<string, mixed>>, redirectUrl?: string, entityIds?: array<string, int>}
     */
    public function convert(array $data): array
    {
        if (self::isConverted((int) $this->lead->getId())) {
            return self::failure([self::generalError(vtranslate('LBL_CONVERT_ERR_ALREADY_CONVERTED', 'Leads'))]);
        }
        $requests = $this->normalizeRequests($data['modules'] ?? null);
        $errors = $this->validateStructure($requests);
        if ($errors !== []) {
            return self::failure($errors);
        }

        $entities = [];
        foreach ($requests as $module => $request) {
            if ($request['mode'] === self::MODE_NONE) {
                continue;
            }
            $entity = ['create' => true, 'name' => $module, 'source' => 'CRM'];
            if ($request['mode'] === self::MODE_EXISTING) {
                $entity['existingId'] = vtws_getWebserviceEntityId($module, $request['existingId']);
                $entities[$module] = $entity;
                continue;
            }
            $values = $this->resolveValues($module, $request['values']);
            $errors = array_merge(
                $errors,
                Leads_ConvertLeadValidator_Model::validateModule($module, $values, self::getDisplayedFields($module), self::LINK_FIELDS[$module])
            );
            $entities[$module] = array_merge($entity, self::toWebserviceValues($module, $values));
        }
        if ($errors !== []) {
            return self::failure($errors);
        }

        return $this->runConvert($entities, $data);
    }

    /**
     * 昇格画面に出す項目: 必須項目（設定に無いもの）を先頭に、続けて設定の並び順
     *
     * 編集できない項目・関連づけ用の参照項目・システム項目は出さない。
     *
     * @return list<string>
     */
    public static function getDisplayedFields(string $module): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            return [];
        }
        $layout = Leads_ConvertLeadSettings_Model::getLayout($module);
        $mandatory = [];
        $configured = [];
        foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
            $fieldName = (string) $fieldName;
            if (!$fieldModel instanceof Vtiger_Field_Model || !self::isInputField($module, $fieldModel)) {
                continue;
            }
            $position = array_search($fieldName, $layout, true);
            if ($position !== false) {
                $configured[$position] = $fieldName;
            } elseif ($fieldModel->isMandatory()) {
                $mandatory[] = $fieldName;
            }
        }
        ksort($configured);

        return array_merge($mandatory, array_values($configured));
    }

    /**
     * 昇格画面で入力できる項目かどうか
     */
    public static function isInputField(string $module, Vtiger_Field_Model $fieldModel): bool
    {
        $fieldName = (string) $fieldModel->getName();
        if (in_array($fieldName, self::LINK_FIELDS[$module] ?? [], true)
            || in_array($fieldName, Leads_ConvertLeadValidator_Model::SYSTEM_FIELDS, true)) {
            return false;
        }

        return $fieldModel->isEditable() && in_array((int) $fieldModel->get('presence'), [0, 2], true);
    }

    /**
     * リードが昇格済みかどうか
     */
    public static function isConverted(int $leadId): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT 1 FROM vtiger_leaddetails WHERE leadid = ? AND converted = 1', [$leadId]);

        return $result !== false && (int) $db->num_rows($result) > 0;
    }

    public static function isModuleAvailable(string $module): bool
    {
        return vtlib_isModuleActive($module) && Users_Privileges_Model::isPermitted($module, 'CreateView');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function resolveValues(string $module, array $input): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            return [];
        }
        $values = Leads_ConvertLeadValues_Model::resolve(
            Leads_ConvertLeadValues_Model::getMappedValues($this->lead, $module),
            Leads_ConvertLeadSettings_Model::getDefaults($module),
            Leads_ConvertLeadValues_Model::getFieldDefaults($moduleModel),
            self::acceptedInput($moduleModel, $module, $input)
        );
        foreach (self::LINK_FIELDS[$module] as $fieldName) {
            unset($values[$fieldName]);
        }

        return $values;
    }

    /**
     * 入力のうち、ユーザーが編集できる項目だけを受け付ける
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function acceptedInput(Vtiger_Module_Model $moduleModel, string $module, array $input): array
    {
        $accepted = [];
        foreach ($input as $fieldName => $value) {
            $fieldModel = $moduleModel->getField((string) $fieldName);
            if (!$fieldModel instanceof Vtiger_Field_Model || !self::isInputField($module, $fieldModel)) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(' |##| ', array_map(static fn ($item): string => is_scalar($item) ? (string) $item : '', $value));
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value !== null && !is_scalar($value)) {
                continue;
            }
            $accepted[(string) $fieldName] = $value;
        }

        return $accepted;
    }

    /**
     * @return array<string, array{mode: string, existingId: int, values: array<string, mixed>}>
     */
    private function normalizeRequests(mixed $modules): array
    {
        $requests = [];
        foreach (Leads_ConvertLeadSettings_Model::SUPPORTED_MODULES as $module) {
            $request = is_array($modules) && isset($modules[$module]) && is_array($modules[$module]) ? $modules[$module] : [];
            $mode = $request['mode'] ?? self::MODE_NONE;
            if (!in_array($mode, [self::MODE_CREATE, self::MODE_EXISTING, self::MODE_NONE], true)) {
                $mode = self::MODE_NONE;
            }
            $existingId = $request['existingId'] ?? 0;
            $values = $request['values'] ?? [];
            $normalizedValues = [];
            if (is_array($values)) {
                foreach ($values as $fieldName => $value) {
                    $normalizedValues[(string) $fieldName] = $value;
                }
            }
            $requests[$module] = [
                'mode' => $mode,
                'existingId' => is_numeric($existingId) ? (int) $existingId : 0,
                'values' => $normalizedValues,
            ];
        }

        return $requests;
    }

    /**
     * 作成・紐づけの組み合わせと権限の確認
     *
     * @param array<string, array{mode: string, existingId: int, values: array<string, mixed>}> $requests
     * @return list<array<string, mixed>>
     */
    private function validateStructure(array $requests): array
    {
        $errors = [];
        $isOn = static fn (string $module): bool => $requests[$module]['mode'] !== self::MODE_NONE;
        if (!$isOn('Accounts') && !$isOn('Contacts')) {
            $errors[] = self::generalError(vtranslate('LBL_CONVERT_ERR_ACCOUNT_OR_CONTACT', 'Leads'));
        }
        if ($requests['Potentials']['mode'] === self::MODE_EXISTING) {
            $errors[] = self::generalError(vtranslate('LBL_CONVERT_ERR_INVALID_REQUEST', 'Leads'), 'Potentials');
        }
        foreach ($requests as $module => $request) {
            if ($request['mode'] === self::MODE_CREATE && !self::isModuleAvailable($module)) {
                $errors[] = self::generalError(vtranslate('LBL_CONVERT_ERR_NO_PERMISSION', 'Leads', vtranslate('SINGLE_' . $module, $module)), $module);
            }
            if ($request['mode'] === self::MODE_EXISTING && !self::canAttach($module, $request['existingId'])) {
                $errors[] = self::generalError(vtranslate('LBL_CONVERT_ERR_EXISTING_NOT_FOUND', 'Leads', vtranslate('SINGLE_' . $module, $module)), $module);
            }
        }
        $required = [
            ['Contacts', 'account_id', 'Accounts'],
            ['Potentials', 'related_to', 'Accounts'],
            ['Potentials', 'contact_id', 'Contacts'],
        ];
        foreach ($required as [$module, $fieldName, $target]) {
            // 既存レコードに紐づける場合は項目を保存しないので、必須の参照先は要らない
            if ($requests[$module]['mode'] === self::MODE_CREATE && !$isOn($target) && self::isFieldMandatory($module, $fieldName)) {
                $errors[] = self::generalError(vtranslate(
                    'LBL_CONVERT_ERR_LINK_REQUIRED',
                    'Leads',
                    vtranslate('SINGLE_' . $module, $module),
                    vtranslate('SINGLE_' . $target, $target)
                ), $module);
            }
        }

        return $errors;
    }

    private static function canAttach(string $module, int $recordId): bool
    {
        return in_array($module, Leads_ConvertLeadSettings_Model::MATCH_MODULES, true)
            && $recordId > 0
            && isRecordExists($recordId)
            && getSalesEntityType($recordId) === $module
            && Users_Privileges_Model::isPermitted($module, 'DetailView', $recordId);
    }

    private static function isFieldMandatory(string $module, string $fieldName): bool
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $fieldModel = $moduleModel instanceof Vtiger_Module_Model ? $moduleModel->getField($fieldName) : null;

        return $fieldModel instanceof Vtiger_Field_Model && $fieldModel->isMandatory();
    }

    /**
     * vtws_create が受け付ける形にする（参照は Webservice ID）
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private static function toWebserviceValues(string $module, array $values): array
    {
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            return $values;
        }
        foreach ($values as $fieldName => $value) {
            $fieldModel = $moduleModel->getField($fieldName);
            if (!$fieldModel instanceof Vtiger_Field_Model || $fieldModel->getFieldDataType() !== 'reference') {
                continue;
            }
            if (is_numeric($value) && (int) $value > 0) {
                $values[$fieldName] = vtws_getWebserviceEntityId((string) getSalesEntityType((int) $value), (int) $value);
            }
        }

        return $values;
    }

    /**
     * @param array<string, array<string, mixed>> $entities
     * @param array<string, mixed> $data
     * @return array{converted: bool, errors: list<array<string, mixed>>, redirectUrl?: string, entityIds?: array<string, int>}
     */
    private function runConvert(array $entities, array $data): array
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $assignedUserId = $data['assignedUserId'] ?? '';
        if (!is_numeric($assignedUserId) || (int) $assignedUserId <= 0) {
            $assignedUserId = $this->getDefaultAssignedUserId();
        }
        $transferTo = $data['transferTo'] ?? '';
        if (!in_array($transferTo, ['Accounts', 'Contacts'], true) || !isset($entities[$transferTo])) {
            $transferTo = isset($entities['Contacts']) ? 'Contacts' : 'Accounts';
        }
        $element = [
            'leadId' => vtws_getWebserviceEntityId('Leads', (int) $this->lead->getId()),
            'assignedTo' => vtws_getWebserviceEntityId(vtws_getOwnerType((int) $assignedUserId), (int) $assignedUserId),
            'transferRelatedRecordsTo' => $transferTo,
            'entities' => $entities,
            'strict' => true,
        ];
        $imageDetails = $this->lead->getImageDetails();
        $firstImage = is_array($imageDetails) ? ($imageDetails[0] ?? null) : null;
        if (is_array($firstImage) && isset($firstImage['id'])) {
            $element['imageAttachmentId'] = $firstImage['id'];
        }

        try {
            $result = vtws_convertlead($element, $this->lead->getId(), null, null, null, null, $currentUser);
        } catch (DuplicateException $e) {
            return self::failure([self::generalError(decode_html((string) $e->getDuplicationMessage()))]);
        } catch (Exception $e) {
            return self::failure([self::generalError(vtranslate('LBL_CONVERT_ERR_FAILED', 'Leads', $e->getMessage()))]);
        }
        if (!is_array($result) || ($result['Accounts'] ?? '') === '' && ($result['Contacts'] ?? '') === '') {
            return self::failure([self::generalError(vtranslate('LBL_CONVERT_ERR_FAILED', 'Leads', ''))]);
        }

        $entityIds = [];
        foreach ($result as $module => $wsId) {
            if (!is_string($wsId) || $wsId === '') {
                continue;
            }
            $components = vtws_getIdComponents($wsId);
            $entityIds[(string) $module] = (int) $components[1];
        }
        $target = isset($entityIds['Accounts']) ? 'Accounts' : 'Contacts';

        return [
            'converted' => true,
            'errors' => [],
            'redirectUrl' => "index.php?module={$target}&view=Detail&record={$entityIds[$target]}",
            'entityIds' => $entityIds,
        ];
    }

    /**
     * 既存レコードに紐づけるときに見せる主な情報（概要欄に出す項目）。閲覧権限が無ければ null
     *
     * @return array{id: int, module: string, label: string, accountId: int|null, accountLabel: string|null, fields: list<array{label: string, value: string}>}|null
     */
    public static function getRecordSummary(string $module, int $recordId): ?array
    {
        if (!self::canAttach($module, $recordId)) {
            return null;
        }
        $record = Vtiger_Record_Model::getInstanceById($recordId, $module);
        $moduleModel = $record->getModule();
        $fields = [];
        foreach ($moduleModel->getSummaryViewFieldsList() as $fieldName => $fieldModel) {
            if (!$fieldModel instanceof Vtiger_Field_Model || !$fieldModel->isViewableInDetailView()) {
                continue;
            }
            $display = $record->getDisplayValue((string) $fieldName);
            $text = trim(decode_html(strip_tags(is_scalar($display) ? (string) $display : '')));
            if ($text === '') {
                continue;
            }
            $fields[] = ['label' => vtranslate((string) $fieldModel->get('label'), $module), 'value' => $text];
            if (count($fields) >= 6) {
                break;
            }
        }
        $summary = [
            'id' => $recordId,
            'module' => $module,
            'label' => decode_html((string) $record->getName()),
            'accountId' => null,
            'accountLabel' => null,
            'fields' => $fields,
        ];
        if ($module === 'Contacts') {
            $withAccount = Leads_ConvertLeadMatcher_Model::withAccounts([
                ['id' => $recordId, 'label' => $summary['label'], 'accountId' => null, 'accountLabel' => null],
            ]);
            $summary['accountId'] = $withAccount[0]['accountId'];
            $summary['accountLabel'] = $withAccount[0]['accountLabel'];
        }

        return $summary;
    }

    private function referenceLabel(string $module, string $fieldName, string $value): ?string
    {
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $fieldModel = $moduleModel instanceof Vtiger_Module_Model ? $moduleModel->getField($fieldName) : null;
        if (!$fieldModel instanceof Vtiger_Field_Model || $fieldModel->getFieldDataType() !== 'reference') {
            return null;
        }

        $label = Vtiger_Functions::getCRMRecordLabel((int) $value);

        return is_scalar($label) ? decode_html((string) $label) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function generalError(string $message, string $module = ''): array
    {
        return ['module' => $module, 'field' => '', 'label' => '', 'message' => $message, 'hidden' => false, 'editable' => true, 'value' => ''];
    }

    /**
     * @param list<array<string, mixed>> $errors
     * @return array{converted: bool, errors: list<array<string, mixed>>}
     */
    private static function failure(array $errors): array
    {
        return ['converted' => false, 'errors' => $errors];
    }
}
