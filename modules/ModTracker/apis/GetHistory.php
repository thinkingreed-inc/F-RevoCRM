<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * ModTracker GetHistory API
 *
 * 親レコードの更新履歴をすべて返す。絞り込み・ページングはクライアント（record-history）で行う。
 *
 * Usage:
 *   GET ?module=ModTracker&api=GetHistory&parent_module=Accounts&parent_id=123
 *
 * Response:
 *   {
 *     "success": true,
 *     "now": "2026-11-02T16:00:00",          // ユーザーのタイムゾーンでの現在時刻
 *     "format": { "dateFormat": "yyyy-mm-dd", "hourFormat": "24" },
 *     "events": [HistoryEvent...],            // 新しい順
 *     "users": { "5": { "id": 5, "name": "山田 太郎", "imageUrl": null } },
 *     "fields": { "phone": { "name": "phone", "label": "代表電話番号", "type": "phone", "system": false, "order": 3 } }
 *   }
 *
 * 型は assets/react-web-components/src/types/history.ts と対応する。
 *
 * @phpstan-type DbRow array<string, mixed>
 * @phpstan-type HistoryValue array{raw: string, display: string}
 * @phpstan-type HistoryDelta array{sign: string, display: string, percent: int|null}
 * @phpstan-type HistoryChange array{field: string, before: HistoryValue|null, after: HistoryValue|null, delta?: HistoryDelta}
 * @phpstan-type HistoryField array{name: string, label: string, type: string, system: bool, order: int}
 * @phpstan-type HistoryRelated array{module: string, moduleLabel: string, recordId: int, label: string, url: string|null}
 * @phpstan-type HistoryUser array{id: int, name: string, imageUrl: string|null}
 * @phpstan-type CurrencyContext array{userSymbol: string, baseSymbol: string, placement: string}
 */
class ModTracker_GetHistory_Api extends Vtiger_Api_Controller
{
    /** 一度に IN 句へ渡す ID 数 */
    public const CHUNK_SIZE = 500;

    /** @var list<string> 履歴の内部管理用で、項目として表示しないもの */
    private static array $ignoredFieldNames = ['record_id', 'record_module', 'label'];

    /** @var array<int, string> vtiger_modtracker_basic.status => 種別 */
    private static array $statusTypes = [
        ModTracker_Record_Model::UPDATE => 'update',
        ModTracker_Record_Model::DELETE => 'delete',
        ModTracker_Record_Model::CREATE => 'create',
        ModTracker_Record_Model::RESTORE => 'restore',
        ModTracker_Record_Model::LINK => 'link',
        ModTracker_Record_Model::UNLINK => 'unlink',
    ];

    /** @var list<string> 増減を表示する数値系の型 */
    private static array $numericTypes = ['currency', 'integer', 'double', 'percentage'];

    /** @var array<string, Vtiger_Field_Model|false> */
    private array $fieldModelCache = [];

    /** @var array<string, int> fieldname => 詳細画面での並び順 */
    private array $layoutOrder = [];

    /** @var CurrencyContext 金額に付ける通貨記号の情報 */
    private array $currencyContext = ['userSymbol' => '', 'baseSymbol' => '', 'placement' => ''];

    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * @return list<array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [
            ['module_parameter' => 'parent_module', 'action' => 'DetailView', 'record_parameter' => 'parent_id'],
        ];
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $parentModule = self::toStr($request->get('parent_module'));
        $parentIdParam = self::toStr($request->get('parent_id'));

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $parentModule)) {
            throw new ApiException('parent_module is invalid', 400);
        }
        if (!ctype_digit($parentIdParam)) {
            throw new ApiException('parent_id must be numeric', 400);
        }
        $parentId = (int) $parentIdParam;

        $moduleModel = Vtiger_Module_Model::getInstance($parentModule);
        if (!$moduleModel instanceof Vtiger_Module_Model) {
            throw new ApiException("Module '$parentModule' not found", 404);
        }
        if (!isRecordExists($parentId) || getSalesEntityType($parentId) !== $parentModule) {
            throw new ApiException("Record '$parentId' not found in module '$parentModule'", 404);
        }

        // Calendar は ToDo 以外が Events として記録されている（ModTracker_Record_Model::getUpdates と同じ扱い）
        $trackedModule = $parentModule;
        if ($parentModule === 'Calendar' && getActivityType($parentId) !== 'Task') {
            $trackedModule = 'Events';
            $eventsModel = Vtiger_Module_Model::getInstance($trackedModule);
            if ($eventsModel instanceof Vtiger_Module_Model) {
                $moduleModel = $eventsModel;
            }
        }

        $this->layoutOrder = $this->fetchLayoutOrder(self::toInt($moduleModel->getId()));
        $this->currencyContext = $this->buildCurrencyContext($parentId, $parentModule);

        $basicRows = $this->fetchBasicRows($parentId, $trackedModule);
        $ids = array_map(static fn (array $row): int => self::toInt($row['id'] ?? 0), $basicRows);
        $detailsById = $this->fetchDetails($ids);
        $relationsById = $this->fetchRelations($ids);

        /** @var array<string, HistoryField> $fields */
        $fields = [];
        $events = [];
        $userIds = [];
        $parentRecord = $this->buildParentForRelation($parentId, $trackedModule);

        foreach ($basicRows as $row) {
            $id = self::toInt($row['id'] ?? 0);
            $status = self::toInt($row['status'] ?? -1);
            if (!isset(self::$statusTypes[$status])) {
                continue;
            }
            $type = self::$statusTypes[$status];
            $userId = self::toInt($row['whodid'] ?? 0);
            /** @var list<HistoryChange> $changes */
            $changes = [];
            $related = null;

            if ($type === 'create' || $type === 'update') {
                foreach ($detailsById[$id] ?? [] as $detail) {
                    $change = $this->buildChange($detail, $moduleModel, $fields);
                    if ($change !== null) {
                        $changes[] = $change;
                    }
                }
                if ($type === 'update' && $changes === []) {
                    continue; // 閲覧できる項目の変更が無い更新は出さない
                }
                $changes = $this->sortChangesByLayout($changes);
            } elseif ($type === 'link' || $type === 'unlink') {
                if (!isset($relationsById[$id])) {
                    continue;
                }
                $related = $this->buildRelated($relationsById[$id], $parentRecord);
                if ($related === null) {
                    continue; // 権限が無い・削除済みの関連先は出さない（旧テンプレートと同じ）
                }
            }

            $event = [
                'id' => $id,
                'type' => $type,
                'userId' => $userId,
                'changedOn' => $this->toUserDateTime(self::toStr($row['changedon'] ?? '')),
                'changes' => $changes,
            ];
            if ($related !== null) {
                $event['related'] = $related;
            }
            $userIds[$userId] = true;
            $events[] = $event;
        }

        $currentUser = Users_Record_Model::getCurrentUserModel();
        $dateFormat = self::toStr($currentUser->get('date_format'));

        return $this->sendSuccess([
            'success' => true,
            'now' => $this->toUserDateTime(date('Y-m-d H:i:s')),
            // 変更日時はクライアントで整形するため、ユーザー設定の書式を渡す（項目値はサーバーで整形済み）
            'format' => [
                'dateFormat' => $dateFormat !== '' ? $dateFormat : 'yyyy-mm-dd',
                'hourFormat' => self::toStr($currentUser->get('hour_format')) === '12' ? '12' : '24',
            ],
            'events' => $events,
            'users' => $this->buildUsers(array_keys($userIds)),
            'fields' => (object) $fields,
        ]);
    }

    /**
     * @return list<DbRow>
     */
    private function fetchBasicRows(int $crmId, string $moduleName): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT id, whodid, changedon, status FROM vtiger_modtracker_basic
                WHERE crmid = ? AND module = ? ORDER BY changedon DESC, id DESC',
            [$crmId, $moduleName]
        );
        return $this->fetchAllRows($db, $result);
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<DbRow>>
     */
    private function fetchDetails(array $ids): array
    {
        $grouped = [];
        foreach ($this->selectByIds('SELECT id, fieldname, prevalue, postvalue FROM vtiger_modtracker_detail', $ids) as $row) {
            $grouped[self::toInt($row['id'] ?? 0)][] = $row;
        }
        return $grouped;
    }

    /**
     * @param list<int> $ids
     * @return array<int, DbRow>
     */
    private function fetchRelations(array $ids): array
    {
        $byId = [];
        foreach ($this->selectByIds('SELECT * FROM vtiger_modtracker_relations', $ids) as $row) {
            $byId[self::toInt($row['id'] ?? 0)] = $row;
        }
        return $byId;
    }

    /**
     * @param list<int> $ids
     * @return list<DbRow>
     */
    private function selectByIds(string $baseQuery, array $ids): array
    {
        $db = PearDatabase::getInstance();
        $rows = [];
        foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
            $result = $db->pquery($baseQuery . ' WHERE id IN (' . generateQuestionMarks($chunk) . ')', $chunk);
            array_push($rows, ...$this->fetchAllRows($db, $result));
        }
        return $rows;
    }

    /**
     * @return list<DbRow>
     */
    private function fetchAllRows(PearDatabase $db, mixed $result): array
    {
        $rows = [];
        while (is_array($row = $db->fetchByAssoc($result, -1, false))) {
            /** @var DbRow $row */
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * 詳細画面と同じ並び（ブロック順 → ブロック内の項目順）の順位を返す
     *
     * @return array<string, int>
     */
    private function fetchLayoutOrder(int $tabId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT vtiger_field.fieldname FROM vtiger_field
                INNER JOIN vtiger_blocks ON vtiger_blocks.blockid = vtiger_field.block
                WHERE vtiger_field.tabid = ?
                ORDER BY vtiger_blocks.sequence, vtiger_field.sequence, vtiger_field.fieldid',
            [$tabId]
        );
        $order = [];
        foreach ($this->fetchAllRows($db, $result) as $index => $row) {
            $order[self::toStr($row['fieldname'] ?? '')] = $index;
        }
        return $order;
    }

    private function getLayoutOrder(string $fieldName): int
    {
        return $this->layoutOrder[$fieldName] ?? PHP_INT_MAX;
    }

    /**
     * @param list<HistoryChange> $changes
     * @return list<HistoryChange>
     */
    private function sortChangesByLayout(array $changes): array
    {
        usort($changes, fn (array $a, array $b): int => $this->getLayoutOrder($a['field']) <=> $this->getLayoutOrder($b['field']));
        return $changes;
    }

    /**
     * 詳細画面（uitypes/StringDetailView.tpl）と同じ規則で通貨記号を決めるための情報
     *
     * @return CurrencyContext
     */
    private function buildCurrencyContext(int $parentId, string $parentModule): array
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userCurrency = getCurrencySymbolandCRate($currentUser->get('currency_id'));
        $baseSymbol = '';
        try {
            $recordModel = Vtiger_Record_Model::getInstanceById($parentId, $parentModule);
            if (method_exists($recordModel, 'getBaseCurrencyDetails')) {
                $baseCurrency = $recordModel->getBaseCurrencyDetails();
                if (is_array($baseCurrency)) {
                    $baseSymbol = self::toStr($baseCurrency['symbol'] ?? '');
                }
            }
        } catch (Exception $e) {
            // 記号なしで表示する
        }
        $userSymbol = is_array($userCurrency) ? self::toStr($userCurrency['symbol'] ?? '') : '';

        // vtiger_currency_info の記号は &yen; のように HTML エンティティで保存されている
        return [
            'userSymbol' => html_entity_decode($userSymbol, ENT_QUOTES, 'UTF-8'),
            'baseSymbol' => html_entity_decode($baseSymbol, ENT_QUOTES, 'UTF-8'),
            'placement' => self::toStr($currentUser->get('currency_symbol_placement')),
        ];
    }

    private function getCurrencySymbol(Vtiger_Field_Model $fieldModel): string
    {
        $uiType = self::toStr($fieldModel->get('uitype'));
        if ($uiType === '71') {
            return $this->currencyContext['userSymbol'];
        }
        if ($uiType === '72' && $fieldModel->getName() === 'unit_price') {
            return $this->currencyContext['baseSymbol'];
        }
        return '';
    }

    /**
     * @param DbRow $detail
     * @param array<string, HistoryField> $fields 履歴に登場した項目のメタ情報（初出時に追加する）
     * @return HistoryChange|null 表示できない項目は null
     */
    private function buildChange(array $detail, Vtiger_Module_Model $moduleModel, array &$fields): ?array
    {
        $fieldName = self::toStr($detail['fieldname'] ?? '');
        if ($fieldName === '' || in_array($fieldName, self::$ignoredFieldNames, true)) {
            return null;
        }
        $fieldModel = $this->getFieldModel($fieldName, $moduleModel);
        if ($fieldModel === false || !$fieldModel->isViewable()) {
            return null;
        }
        $displayType = self::toInt($fieldModel->getDisplayType());
        if ($displayType === 5) {
            return null;
        }

        if (!isset($fields[$fieldName])) {
            $fields[$fieldName] = [
                'name' => $fieldName,
                'label' => self::toStr(vtranslate($fieldModel->get('label'), $moduleModel->getName())),
                'type' => self::toStr($fieldModel->getFieldDataType()),
                // 編集画面に出ない項目（最終更新者・作成日時など）はシステム項目として既定で隠す
                'system' => !in_array($displayType, [1, 4], true),
                'order' => $this->getLayoutOrder($fieldName),
            ];
        }

        $before = $this->buildValue($fieldModel, $detail['prevalue'] ?? null);
        $after = $this->buildValue($fieldModel, $detail['postvalue'] ?? null);
        $change = ['field' => $fieldName, 'before' => $before, 'after' => $after];
        $delta = $this->buildDelta($fieldModel, $before, $after);
        if ($delta !== null) {
            $change['delta'] = $delta;
        }
        return $change;
    }

    /**
     * 数値項目の増減。差分も項目値と同じくユーザー設定の書式（桁区切り・小数・通貨記号）で整形する。
     *
     * @param HistoryValue|null $before
     * @param HistoryValue|null $after
     * @return HistoryDelta|null
     */
    private function buildDelta(Vtiger_Field_Model $fieldModel, ?array $before, ?array $after): ?array
    {
        if ($before === null || $after === null || !in_array($fieldModel->getFieldDataType(), self::$numericTypes, true)) {
            return null;
        }
        if (!is_numeric($before['raw']) || !is_numeric($after['raw'])) {
            return null;
        }
        $beforeNumber = (float) $before['raw'];
        $diff = (float) $after['raw'] - $beforeNumber;
        if (abs($diff) < 1e-9) {
            return null;
        }
        $display = $this->formatDisplay($fieldModel, (string) abs($diff));
        if ($display === '') {
            return null;
        }
        return [
            'sign' => $diff > 0 ? '+' : '-',
            'display' => $display,
            'percent' => $beforeNumber != 0 ? (int) round($diff / abs($beforeNumber) * 100) : null,
        ];
    }

    private function getFieldModel(string $fieldName, Vtiger_Module_Model $moduleModel): Vtiger_Field_Model|false
    {
        if (!array_key_exists($fieldName, $this->fieldModelCache)) {
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
            $this->fieldModelCache[$fieldName] = $fieldModel instanceof Vtiger_Field_Model ? $fieldModel : false;
        }
        return $this->fieldModelCache[$fieldName];
    }

    /**
     * @return HistoryValue|null 空値は null
     */
    private function buildValue(Vtiger_Field_Model $fieldModel, mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        $raw = self::toStr(decode_html(self::toStr($value)));
        if ($raw === '' || ($fieldModel->getFieldDataType() === 'reference' && $raw === '0')) {
            return null;
        }
        return [
            'raw' => $raw,
            'display' => $this->formatDisplay($fieldModel, $raw),
        ];
    }

    /**
     * 詳細画面と同じ表示値（ユーザー設定の書式で整形し、金額は通貨記号を付ける）
     */
    private function formatDisplay(Vtiger_Field_Model $fieldModel, string $value): string
    {
        $html = Vtiger_Util_Helper::toVtiger6SafeHTML($fieldModel->getDisplayValue($value));
        $text = $this->toPlainText(self::toStr($html));
        if ($text === '' || $fieldModel->getFieldDataType() !== 'currency') {
            return $text;
        }
        $symbol = $this->getCurrencySymbol($fieldModel);
        if ($symbol === '') {
            return $text;
        }
        return $this->currencyContext['placement'] === '$1.0' ? $symbol . ' ' . $text : $text . ' ' . $symbol;
    }

    /**
     * getDisplayValue はリンクや改行タグを含む HTML を返すため、プレーンテキストに落とす。
     * apis は JSON のみを返し、HTML はクライアントに渡さない。
     */
    private function toPlainText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>\s*\n?/i', "\n", $html) ?? $html;
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }

    private function buildParentForRelation(int $parentId, string $moduleName): ModTracker_Record_Model
    {
        $parent = new ModTracker_Record_Model();
        $parent->set('crmid', $parentId);
        $parent->set('module', $moduleName);
        return $parent;
    }

    /**
     * @param DbRow $relationRow
     * @return HistoryRelated|null
     */
    private function buildRelated(array $relationRow, ModTracker_Record_Model $parentRecord): ?array
    {
        $relation = new ModTracker_Relation_Model();
        $relation->setData($relationRow);
        $relation->setParent($parentRecord);

        $linkedRecord = $relation->getLinkedRecord();
        if (!$linkedRecord instanceof Vtiger_Record_Model) {
            return null;
        }
        $targetModule = self::toStr($relationRow['targetmodule'] ?? '');
        $url = null;
        if ($targetModule !== 'ModComments') {
            $detailUrl = $relation->getRecordDetailViewUrl();
            // Emails などは javascript: の URL を返すため、遷移先として扱わない
            if (is_string($detailUrl) && $detailUrl !== '' && stripos($detailUrl, 'javascript:') !== 0) {
                $url = $detailUrl;
            }
        }
        return [
            'module' => $targetModule,
            'moduleLabel' => self::toStr(vtranslate($targetModule, $targetModule)),
            'recordId' => self::toInt($relationRow['targetid'] ?? 0),
            'label' => self::toStr(decode_html(self::toStr($linkedRecord->getName()))),
            'url' => $url,
        ];
    }

    /**
     * @param list<int> $userIds
     */
    private function buildUsers(array $userIds): object
    {
        /** @var array<string, HistoryUser> $users */
        $users = [];
        foreach ($userIds as $userId) {
            $name = '';
            $imageUrl = null;
            try {
                $userModel = Users_Record_Model::getInstanceById($userId, 'Users');
                if ($userModel instanceof Users_Record_Model) {
                    $name = self::toStr(decode_html(self::toStr($userModel->getDisplayName())));
                    $images = $userModel->getImageDetails();
                    if (is_array($images) && is_array($images[0] ?? null) && !empty($images[0]['url'])) {
                        $imageUrl = self::toStr($images[0]['url']);
                    }
                }
            } catch (Exception $e) {
                // 削除済みユーザーなどは名前なしで返す（クライアント側で表示を補う）
            }
            $users[(string) $userId] = ['id' => $userId, 'name' => $name, 'imageUrl' => $imageUrl];
        }
        return (object) $users;
    }

    private function toUserDateTime(string $dbDateTime): ?string
    {
        if ($dbDateTime === '') {
            return null;
        }
        // コア（include/fields/DateTimeField.php）の PHPDoc が引数型を「type」と誤記しているため除外する
        $dateTime = DateTimeField::convertToUserTimeZone($dbDateTime); // @phpstan-ignore argument.type
        return $dateTime instanceof DateTime ? $dateTime->format('Y-m-d\TH:i:s') : null;
    }

    /** DB・リクエスト由来の mixed をスカラーの文字列にする（配列などは空文字） */
    private static function toStr(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
