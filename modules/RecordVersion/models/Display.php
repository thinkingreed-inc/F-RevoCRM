<?php

/**
 * 過去の版を表示用に組み立てる（見ている利用者の今の権限で絞り、今の書式で出す）
 *
 * 項目（仕様 表C）
 * - 今もある・表示している・見られる項目：今の項目設定のブロック・並び順で、今の表示名と当時の値を出す
 * - 当時あって今は削除された項目：管理者だけ「削除された項目」ブロックに、当時の表示名と値を出す
 *   （表示名は、見ている利用者の言語が日本語なら日本語、それ以外は英語）
 * - 当時まだ無かった項目・今は非表示の項目・見られない項目：出さない
 * 「今もその項目がある」は項目 ID で判定する（同じ名前で作り直した項目は別の項目）。
 *
 * 関連レコード（仕様 表D）
 * - 今もあり見られる：当時の内容で出し、「今のレコードを開く」で今の詳細画面を開ける
 * - 今もあるが見られない：出さない
 * - 削除された（ごみ箱・完全削除）：管理者だけに当時の内容で出す
 * 関連の表そのものは、関連先モジュールの参照権限が無ければ出さない
 * （関連先モジュールが無効・削除されているときは管理者だけに出す）。
 */
class RecordVersion_Display_Model
{
    /** 当時の名前を出す項目の種類 */
    private const NAMED_DATA_TYPES = [
        'reference', 'multireference', 'owner', 'ownergroup', 'multiowner', 'userReference',
        'currencyList', 'documentsFolder',
    ];

    /** 関連そのものに付く値の表示名（翻訳のキーとモジュール） */
    private const RELATION_VALUE_LABELS = [
        'listprice' => ['List Price', 'PriceBooks'],
        'campaignrelstatus' => ['Status', 'Campaigns'],
        'quantity' => ['Quantity', 'Products'],
    ];

    private bool $isAdmin;
    private bool $isJapanese;
    private string $currencySymbol;

    /**
     * @param string $currencySymbol 金額の項目に付ける通貨の記号（当時のレコードの通貨）
     */
    public function __construct(string $currencySymbol)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $this->isAdmin = (bool) $currentUser->isAdminUser();
        $this->isJapanese = RecordVersion_Util_Helper::toStr(Vtiger_Language_Handler::getLanguage()) === 'ja_jp';
        $this->currencySymbol = $currencySymbol;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * 過去の版の金額に付ける通貨の記号（品目明細があればそのレコードの通貨、無ければ基準通貨）
     *
     * @param array<string, mixed> $snapshot
     */
    public static function getCurrencySymbol(array $snapshot): string
    {
        $inventory = RecordVersion_Util_Helper::toArray($snapshot['inventory'] ?? null);
        $currency = RecordVersion_Util_Helper::toArray($inventory['currency'] ?? null);
        if (isset($currency['symbol'])) {
            return RecordVersion_Util_Helper::toStr($currency['symbol']);
        }
        $info = RecordVersion_Util_Helper::toArray(getCurrencySymbolandCRate(CurrencyField::getDBCurrencyId()));
        return RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($info['symbol'] ?? '')));
    }

    /**
     * 項目をブロックごとに並べる（表C）
     *
     * @param array<string, mixed> $snapshotFields 過去の版に保存した項目（項目 ID => 内容）
     * @return array{blocks: array<int, array{label: string, fields: array<int, array{label: string, value: string}>}>, deleted: array<int, array{label: string, value: string}>}
     */
    public function buildFieldBlocks(string $moduleName, array $snapshotFields): array
    {
        $byId = [];
        foreach ($snapshotFields as $entry) {
            $entry = RecordVersion_Util_Helper::toArray($entry);
            $byId[RecordVersion_Util_Helper::toInt($entry['fieldid'] ?? 0)] = $entry;
        }

        $blocks = [];
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if ($moduleModel) {
            foreach ($moduleModel->getBlocks() as $blockModel) {
                $fields = [];
                foreach ($blockModel->getFields() as $fieldModel) {
                    if (!($fieldModel instanceof Vtiger_Field_Model)) {
                        continue;
                    }
                    $fieldId = RecordVersion_Util_Helper::toInt($fieldModel->getId());
                    // 当時まだ無かった項目（表C ルール6）
                    if (!isset($byId[$fieldId])) {
                        continue;
                    }
                    // 非表示にしている・見られない項目（表C ルール2・3）
                    if (!$fieldModel->isViewableInDetailView()) {
                        continue;
                    }
                    $fields[] = [
                        'label' => $this->currentLabel($fieldModel, $moduleName),
                        'value' => $this->formatValue($fieldModel, $byId[$fieldId], $moduleName),
                    ];
                }
                if (!empty($fields)) {
                    $blocks[] = [
                        'label' => RecordVersion_Util_Helper::toStr(vtranslate(RecordVersion_Util_Helper::toStr($blockModel->get('label')), $moduleName)),
                        'fields' => $fields,
                    ];
                }
            }
        }

        $deleted = [];
        if ($this->isAdmin) {
            $currentIds = self::getCurrentFieldIds($moduleName);
            foreach ($byId as $fieldId => $entry) {
                if (in_array($fieldId, $currentIds, true)) {
                    continue;
                }
                $deleted[] = [
                    'label' => $this->savedLabel($entry, 'label'),
                    'value' => $this->formatDeletedValue($entry, $moduleName),
                ];
            }
        }
        return ['blocks' => $blocks, 'deleted' => $deleted];
    }

    /**
     * 品目明細を表示用に整える（今の税・手数料の設定は見ない）
     *
     * @param array<string, mixed> $inventory
     * @return array<string, mixed>
     */
    public function buildInventory(array $inventory): array
    {
        $lines = [];
        foreach (RecordVersion_Util_Helper::toList($inventory['lines'] ?? []) as $line) {
            $line = RecordVersion_Util_Helper::toArray($line);
            $taxes = [];
            foreach (RecordVersion_Util_Helper::toList($line['taxes'] ?? []) as $tax) {
                $tax = RecordVersion_Util_Helper::toArray($tax);
                $taxes[] = $this->savedLabel($tax, 'label') . ' (' . $this->formatPercent($tax['percentage'] ?? 0) . '%) '
                    . $this->formatAmount($tax['amount'] ?? 0);
            }
            $lines[] = [
                'name' => RecordVersion_Util_Helper::toStr($line['name'] ?? ''),
                'code' => RecordVersion_Util_Helper::toStr($line['code'] ?? ''),
                'comment' => RecordVersion_Util_Helper::toStr($line['comment'] ?? ''),
                'quantity' => $this->formatNumber($line['quantity'] ?? 0),
                'list_price' => $this->formatAmount($line['list_price'] ?? 0),
                'product_total' => $this->formatAmount($line['product_total'] ?? 0),
                'discount_total' => $this->formatAmount($line['discount_total'] ?? 0),
                'total_after_discount' => $this->formatAmount($line['total_after_discount'] ?? 0),
                'taxes' => $taxes,
                'net_price' => $this->formatAmount($line['net_price'] ?? 0),
            ];
        }
        $charges = [];
        foreach (RecordVersion_Util_Helper::toList($inventory['charges'] ?? []) as $charge) {
            $charge = RecordVersion_Util_Helper::toArray($charge);
            $chargeTaxes = [];
            foreach (RecordVersion_Util_Helper::toList($charge['taxes'] ?? []) as $tax) {
                $tax = RecordVersion_Util_Helper::toArray($tax);
                $chargeTaxes[] = [
                    'label' => $this->savedLabel($tax, 'label') . ' (' . $this->formatPercent($tax['percentage'] ?? 0) . '%)',
                    'amount' => $this->formatAmount($tax['amount'] ?? 0),
                ];
            }
            $charges[] = [
                'name' => $this->savedLabel($charge, 'name'),
                'value' => $this->formatAmount($charge['value'] ?? 0),
                'taxes' => $chargeTaxes,
            ];
        }
        $groupTaxes = [];
        foreach (RecordVersion_Util_Helper::toList($inventory['group_taxes'] ?? []) as $tax) {
            $tax = RecordVersion_Util_Helper::toArray($tax);
            $groupTaxes[] = [
                'label' => $this->savedLabel($tax, 'label') . ' (' . $this->formatPercent($tax['percentage'] ?? 0) . '%)',
                'amount' => $this->formatAmount($tax['amount'] ?? 0),
            ];
        }
        $currency = RecordVersion_Util_Helper::toArray($inventory['currency'] ?? []);
        $discountPercent = (float) RecordVersion_Util_Helper::toStr($inventory['discount_percent'] ?? '0');
        $taxMode = RecordVersion_Util_Helper::toStr($inventory['tax_mode'] ?? '');
        return [
            'is_individual' => $taxMode === 'individual',
            'tax_mode_label' => RecordVersion_Util_Helper::toStr(vtranslate(
                $taxMode === 'individual' ? 'LBL_TAX_MODE_INDIVIDUAL' : 'LBL_TAX_MODE_GROUP',
                RecordVersion_Config_Model::MODULE_NAME
            )),
            'lines' => $lines,
            'sub_total' => $this->formatAmount($inventory['sub_total'] ?? 0),
            'discount_label' => $discountPercent > 0 ? '(' . $this->formatPercent($discountPercent) . '%)' : '',
            'discount_total' => $this->formatAmount($inventory['discount_total'] ?? 0),
            'charges' => $charges,
            'pre_tax_total' => $this->formatAmount($inventory['pre_tax_total'] ?? 0),
            'group_taxes' => $groupTaxes,
            'charges_tax_total' => $this->formatAmount($inventory['charges_tax_total'] ?? 0),
            'adjustment' => $this->formatAmount($inventory['adjustment'] ?? 0),
            'grand_total' => $this->formatAmount($inventory['grand_total'] ?? 0),
            'currency' => trim(RecordVersion_Util_Helper::toStr($currency['name'] ?? '') . ' ' . RecordVersion_Util_Helper::toStr($currency['symbol'] ?? '')),
            'conversion_rate' => RecordVersion_Util_Helper::toStr($currency['conversion_rate'] ?? ''),
        ];
    }

    /**
     * 版に含めた関連の表（表D）
     *
     * @param array<string, mixed> $relation 過去の版に保存した関連 1 本分
     * @return array<string, mixed>|null 表を出さないときは null
     */
    public function buildRelation(Vtiger_Record_Model $parentRecord, array $relation, int $relationIndex, int $versionNo): ?array
    {
        $relatedModule = RecordVersion_Util_Helper::toStr($relation['related_module'] ?? '');
        $relatedModel = Vtiger_Module_Model::getInstance($relatedModule);
        $moduleAvailable = $relatedModel && $relatedModel->isActive();
        if ($moduleAvailable) {
            if (!Users_Privileges_Model::isPermitted($relatedModule, 'DetailView')) {
                return null;
            }
        } elseif (!$this->isAdmin) {
            // 関連先モジュールが無効・削除されているときは管理者にだけ出す
            return null;
        }

        $label = $this->savedLabel($relation, 'label');
        $headers = $moduleAvailable ? $this->getRelationHeaders($parentRecord, $relatedModule, RecordVersion_Util_Helper::toStr($relation['label'] ?? '')) : [];
        $records = RecordVersion_Util_Helper::toList($relation['records'] ?? []);

        $valueColumns = [];
        foreach ($records as $item) {
            foreach (array_keys(RecordVersion_Util_Helper::toArray(RecordVersion_Util_Helper::toArray($item)['relation_values'] ?? [])) as $column) {
                $valueColumns[$column] = true;
            }
        }

        $columns = [];
        foreach ($headers as $fieldModel) {
            $columns[] = $this->currentLabel($fieldModel, $relatedModule);
        }
        if (empty($headers)) {
            $columns[] = $label;
        }
        foreach (array_keys($valueColumns) as $column) {
            $labelInfo = self::RELATION_VALUE_LABELS[$column] ?? [$column, $relatedModule];
            $columns[] = RecordVersion_Util_Helper::toStr(vtranslate($labelInfo[0], $labelInfo[1]));
        }
        $isDocuments = $relatedModule === 'Documents';

        $rows = [];
        foreach ($records as $item) {
            $item = RecordVersion_Util_Helper::toArray($item);
            $relatedId = RecordVersion_Util_Helper::toInt($item['id'] ?? 0);
            $exists = self::recordExists($relatedId);
            if ($exists) {
                if (!Users_Privileges_Model::isPermitted($relatedModule, 'DetailView', $relatedId)) {
                    continue;
                }
            } elseif (!$this->isAdmin) {
                continue;
            }
            $fieldsByName = [];
            foreach (RecordVersion_Util_Helper::toArray($item['fields'] ?? []) as $entry) {
                $entry = RecordVersion_Util_Helper::toArray($entry);
                $fieldsByName[RecordVersion_Util_Helper::toStr($entry['name'] ?? '')] = $entry;
            }
            $cells = [];
            foreach ($headers as $fieldName => $fieldModel) {
                $entry = $fieldsByName[(string) $fieldName] ?? null;
                // 当時無かった項目は空欄
                $cells[] = $entry === null ? '' : $this->formatValue($fieldModel, $entry, $relatedModule);
            }
            if (empty($headers)) {
                $cells[] = htmlspecialchars(RecordVersion_Util_Helper::toStr($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            }
            $relationValues = RecordVersion_Util_Helper::toArray($item['relation_values'] ?? []);
            foreach (array_keys($valueColumns) as $column) {
                $value = RecordVersion_Util_Helper::toStr($relationValues[$column] ?? '');
                $cells[] = $column === 'listprice' && $value !== '' ? $this->formatAmount($value) : $this->translatePicklist($value, $relatedModule);
            }
            $rows[] = [
                'id' => $relatedId,
                'cells' => $cells,
                'current_url' => $exists ? 'index.php?module=' . rawurlencode($relatedModule) . '&view=Detail&record=' . $relatedId : '',
                'detail_url' => 'index.php?module=RecordVersion&view=RelatedRecord&record=' . RecordVersion_Util_Helper::toInt($parentRecord->getId())
                    . '&version=' . $versionNo . '&relation=' . $relationIndex . '&related=' . $relatedId,
                'document' => $isDocuments ? $this->buildDocument($relatedId, $exists, RecordVersion_Util_Helper::toArray($item['document'] ?? [])) : null,
            ];
        }
        return [
            'label' => $label,
            'related_module' => $relatedModule,
            'columns' => $columns,
            'is_documents' => $isDocuments,
            'rows' => $rows,
            'empty_message' => sprintf(RecordVersion_Util_Helper::toStr(vtranslate('LBL_NO_RELATED_RECORDS', RecordVersion_Config_Model::MODULE_NAME)), $label),
        ];
    }

    /**
     * 関連レコードの行を出してよいか（表D）
     */
    public function canShowRelatedRecord(string $relatedModule, int $relatedId): bool
    {
        if (self::recordExists($relatedId)) {
            return (bool) Users_Privileges_Model::isPermitted($relatedModule, 'DetailView', $relatedId);
        }
        return $this->isAdmin;
    }

    /**
     * ドキュメントの行：当時のファイル名と、そのときのファイルバージョンのダウンロード
     * ダウンロードできるかはドキュメント自体の権限とフォルダの権限で判定する（今のダウンロードの処理を使う）
     *
     * @param array<string, mixed> $document
     * @return array{file_name: string, url: string, download_url: string, version_label: string}
     */
    private function buildDocument(int $documentId, bool $exists, array $document): array
    {
        $locationType = RecordVersion_Util_Helper::toStr($document['location_type'] ?? '');
        $fileName = RecordVersion_Util_Helper::toStr($document['file_name'] ?? '');
        $fileVersion = RecordVersion_Util_Helper::toInt($document['file_version'] ?? 0);
        $result = ['file_name' => $fileName, 'url' => '', 'download_url' => '', 'version_label' => ''];
        if ($locationType === 'E') {
            // 外部リンクは当時の URL を出す
            $result['url'] = $fileName;
            return $result;
        }
        if (!$exists || $fileVersion <= 0 || !Users_Privileges_Model::isPermitted('Documents', 'DetailView', $documentId)) {
            return $result;
        }
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT 1 FROM vtiger_notes_file_versions WHERE notesid = ? AND version_number = ?',
            [$documentId, $fileVersion]
        ));
        if (empty($rows)) {
            return $result;
        }
        $result['download_url'] = 'index.php?module=Documents&action=DownloadVersion&record=' . $documentId . '&version=' . $fileVersion;
        $result['version_label'] = sprintf(RecordVersion_Util_Helper::toStr(vtranslate('LBL_FILE_VERSION', RecordVersion_Config_Model::MODULE_NAME)), $fileVersion);
        return $result;
    }

    /**
     * @return array<string, Vtiger_Field_Model> 項目名 => 項目
     */
    private function getRelationHeaders(Vtiger_Record_Model $parentRecord, string $relatedModule, string $relationLabel): array
    {
        try {
            $relationListView = Vtiger_RelationListView_Model::getInstance($parentRecord, $relatedModule, $relationLabel);
            if (!$relationListView || !$relationListView->getRelationModel()) {
                return [];
            }
            $headers = [];
            foreach ($relationListView->getHeaders() as $fieldName => $fieldModel) {
                if ($fieldModel instanceof Vtiger_Field_Model && $fieldModel->isViewable()) {
                    $headers[(string) $fieldName] = $fieldModel;
                }
            }
            return $headers;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 当時の値を、今の項目の種類と見ている利用者の書式で出す（HTML）
     *
     * @param array<string, mixed> $entry
     */
    public function formatValue(Vtiger_Field_Model $fieldModel, array $entry, string $moduleName): string
    {
        $value = $entry['value'] ?? '';
        $dataType = RecordVersion_Util_Helper::toStr($fieldModel->getFieldDataType());
        $savedType = RecordVersion_Util_Helper::toStr($entry['datatype'] ?? '');
        if (in_array($dataType, self::NAMED_DATA_TYPES, true) || in_array($savedType, self::NAMED_DATA_TYPES, true)) {
            // 関連項目・担当などは当時の名前で出す
            return htmlspecialchars(RecordVersion_Util_Helper::toStr($entry['display'] ?? ''), ENT_QUOTES, 'UTF-8');
        }
        $text = RecordVersion_Util_Helper::toStr($value);
        if ($text === '') {
            return '';
        }
        switch ($dataType) {
            case 'currency':
                // 当時のレコードの通貨と金額のまま出す（今のレートや見ている利用者の通貨に換算しない）
                return $this->formatAmount($text);
            case 'picklist':
                return $this->translatePicklist($text, $moduleName);
            case 'multipicklist':
                $values = array_map(fn (string $v): string => $this->translatePicklist(trim($v), $moduleName), explode('|##|', $text));
                return implode(', ', $values);
            case 'image':
                return htmlspecialchars(decode_html($text), ENT_QUOTES, 'UTF-8');
            default:
                return RecordVersion_Util_Helper::toStr($fieldModel->getDisplayValue($text));
        }
    }

    /**
     * 削除された項目の値（今の項目の種類がわからないので、当時の名前・値をそのまま出す）
     *
     * @param array<string, mixed> $entry
     */
    private function formatDeletedValue(array $entry, string $moduleName): string
    {
        if (isset($entry['display'])) {
            return htmlspecialchars(RecordVersion_Util_Helper::toStr($entry['display']), ENT_QUOTES, 'UTF-8');
        }
        $text = RecordVersion_Util_Helper::toStr($entry['value'] ?? '');
        $dataType = RecordVersion_Util_Helper::toStr($entry['datatype'] ?? '');
        if ($dataType === 'picklist') {
            return $this->translatePicklist($text, $moduleName);
        }
        if ($dataType === 'multipicklist') {
            return implode(', ', array_map(fn (string $v): string => $this->translatePicklist(trim($v), $moduleName), explode('|##|', $text)));
        }
        if ($dataType === 'currency' && $text !== '') {
            return $this->formatAmount($text);
        }
        return nl2br(htmlspecialchars(decode_html($text), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * 今もある項目の表示名（見ている利用者の言語）
     */
    private function currentLabel(Vtiger_Field_Model $fieldModel, string $moduleName): string
    {
        $label = RecordVersion_Util_Helper::toStr($fieldModel->get('label'));
        if ($label === RecordVersion_Config_Model::FIELD_LABEL) {
            return RecordVersion_Util_Helper::toStr(vtranslate($label, RecordVersion_Config_Model::MODULE_NAME));
        }
        return RecordVersion_Util_Helper::toStr(vtranslate($label, $moduleName));
    }

    /**
     * 保存した表示名（見ている利用者の言語が日本語なら日本語、それ以外は英語）
     *
     * @param array<string, mixed> $entry
     */
    private function savedLabel(array $entry, string $key): string
    {
        $localized = RecordVersion_Util_Helper::toStr($entry[$key . ($this->isJapanese ? '_ja' : '_en')] ?? '');
        if ($localized !== '' && $localized !== RecordVersion_Config_Model::FIELD_LABEL) {
            return $localized;
        }
        $raw = RecordVersion_Util_Helper::toStr($entry[$key] ?? '');
        if ($raw === RecordVersion_Config_Model::FIELD_LABEL) {
            return RecordVersion_Util_Helper::toStr(vtranslate($raw, RecordVersion_Config_Model::MODULE_NAME));
        }
        return $raw;
    }

    /**
     * 選択肢は当時の値を見ている利用者の言語に翻訳して出す（翻訳が無いときは当時の値のまま）
     */
    private function translatePicklist(string $value, string $moduleName): string
    {
        if ($value === '') {
            return '';
        }
        return htmlspecialchars(RecordVersion_Util_Helper::toStr(vtranslate(decode_html($value), $moduleName)), ENT_QUOTES, 'UTF-8');
    }

    private function formatAmount(mixed $value): string
    {
        $number = RecordVersion_Util_Helper::toStr($value);
        if ($number === '') {
            return '';
        }
        $formatted = RecordVersion_Util_Helper::toStr(CurrencyField::convertToUserFormat($number, null, true));
        return htmlspecialchars($this->currencySymbol . $formatted, ENT_QUOTES, 'UTF-8');
    }

    private function formatNumber(mixed $value): string
    {
        $number = RecordVersion_Util_Helper::toStr($value);
        return htmlspecialchars(RecordVersion_Util_Helper::toStr(CurrencyField::convertToUserFormat($number, null, true)), ENT_QUOTES, 'UTF-8');
    }

    private function formatPercent(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) RecordVersion_Util_Helper::toStr($value), 3, '.', ''), '0'), '.');
    }

    /**
     * @return int[]
     */
    private static function getCurrentFieldIds(string $moduleName): array
    {
        $db = PearDatabase::getInstance();
        $ids = [];
        foreach (RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT fieldid FROM vtiger_field WHERE tabid = ?',
            [RecordVersion_Util_Helper::toInt(getTabid($moduleName))]
        )) as $row) {
            $ids[] = RecordVersion_Util_Helper::toInt($row['fieldid'] ?? 0);
        }
        return $ids;
    }

    /**
     * 今もある（ごみ箱・完全削除でない）レコードか
     */
    private static function recordExists(int $recordId): bool
    {
        if ($recordId <= 0) {
            return false;
        }
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT 1 FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
            [$recordId]
        ));
        return !empty($rows);
    }
}
