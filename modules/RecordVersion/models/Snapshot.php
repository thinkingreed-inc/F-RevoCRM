<?php

/**
 * 改版した時点のレコードの内容（過去の版に保存する JSON）を作る
 *
 * 保存する内容（仕様 4-3）
 * - 項目：すべての項目の元の値と、表示名（翻訳前の名前・日本語・英語）。
 *   関連項目・担当・通貨などは当時の名前も残す
 * - 品目明細：行（品目名を含む）と金額、税の名前・税率・計算方法、手数料の名前、通貨の記号と換算レート
 * - 版に含める関連のレコード：すべての項目の元の値と表示名、関連そのものに付く値、関連の名前
 * - ドキュメント：改版した時点で現在だったファイルバージョン（外部リンクは URL）
 *
 * 項目の権限に関係なくすべて保存し、表示するときに今の権限で絞る。
 */
class RecordVersion_Snapshot_Model
{
    public const FORMAT_VERSION = 1;

    /** 当時の名前も残す項目の種類（表示するときに、今の名前ではなく当時の名前で出す） */
    private const NAMED_DATA_TYPES = [
        'reference', 'multireference', 'owner', 'ownergroup', 'multiowner', 'userReference',
        'currencyList', 'documentsFolder',
    ];

    /** 関連そのものに付く値（多対多の関連の付加情報） */
    private const RELATION_VALUE_COLUMNS = ['listprice', 'campaignrelstatus', 'quantity'];

    /** 関連レコードを一度に読む件数 */
    private const RELATED_PAGE_SIZE = 500;

    /** 過去の版の内容が大きすぎて作れないときの例外コード */
    public const ERROR_TOO_LARGE = 4131;

    /** PHP のメモリの上限のうち、過去の版を作るのに使ってよい割合（超えたら止める） */
    private const MEMORY_RATIO = 0.8;

    /** @var int|null メモリの上限（バイト）。null なら PHP の memory_limit。テストで小さくするために使う */
    public static ?int $memoryLimitBytes = null;

    /**
     * @return array<string, mixed>
     */
    public static function build(Vtiger_Record_Model $record, int $versionNo, string $revisedByName): array
    {
        $moduleName = $record->getModuleName();
        $snapshot = [
            'format' => self::FORMAT_VERSION,
            'module' => $moduleName,
            'record_id' => RecordVersion_Util_Helper::toInt($record->getId()),
            'record_label' => RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($record->getName()))),
            'version_no' => $versionNo,
            'revised_by_name' => $revisedByName,
            'fields' => self::captureFields($record),
            'inventory' => null,
            'relations' => [],
        ];
        if ($record instanceof Inventory_Record_Model) {
            $snapshot['inventory'] = self::captureInventory($record);
        }
        $tabId = RecordVersion_Util_Helper::toInt(getTabid($moduleName));
        foreach (RecordVersion_Config_Model::getIncludedRelationIds($tabId) as $relationId) {
            $relation = self::captureRelation($record, $relationId);
            if ($relation !== null) {
                $snapshot['relations'][] = $relation;
            }
        }
        self::ensureMemory();
        return $snapshot;
    }

    /**
     * メモリが足りなくなりそうなら止める（関連するレコードが多すぎるとき）
     *
     * メモリ不足は PHP の致命的エラーになり受け止められないため、上限に近づいた時点で例外にして、
     * 改版の処理で「関連するレコードが多すぎるため改版できません」にする。
     */
    public static function ensureMemory(): void
    {
        $limit = self::$memoryLimitBytes ?? self::getPhpMemoryLimit();
        if ($limit > 0 && memory_get_usage(true) > $limit * self::MEMORY_RATIO) {
            throw new Exception('The snapshot is too large', self::ERROR_TOO_LARGE);
        }
    }

    /**
     * PHP の memory_limit（バイト。上限なしなら 0）
     */
    private static function getPhpMemoryLimit(): int
    {
        $value = trim(RecordVersion_Util_Helper::toStr(ini_get('memory_limit')));
        if ($value === '' || $value === '-1') {
            return 0;
        }
        $number = (int) $value;
        switch (strtoupper(substr($value, -1))) {
            case 'G':
                return $number * 1024 * 1024 * 1024;
            case 'M':
                return $number * 1024 * 1024;
            case 'K':
                return $number * 1024;
            default:
                return $number;
        }
    }

    /**
     * レコードのすべての項目（品目明細の列は品目明細として別に残す）
     *
     * @return array<int, array<string, mixed>> 項目 ID => 項目の内容
     */
    public static function captureFields(Vtiger_Record_Model $record): array
    {
        $moduleName = $record->getModuleName();
        $data = RecordVersion_Util_Helper::toArray($record->getData());
        $fields = [];
        foreach ($record->getModule()->getFields() as $fieldName => $fieldModel) {
            if (!($fieldModel instanceof Vtiger_Field_Model)) {
                continue;
            }
            if ($fieldModel->get('table') === 'vtiger_inventoryproductrel') {
                continue;
            }
            $label = RecordVersion_Util_Helper::toStr($fieldModel->get('label'));
            $dataType = RecordVersion_Util_Helper::toStr($fieldModel->getFieldDataType());
            $value = $data[(string) $fieldName] ?? null;
            $entry = [
                'fieldid' => RecordVersion_Util_Helper::toInt($fieldModel->getId()),
                'name' => (string) $fieldName,
                'label' => $label,
                'label_ja' => self::translate($label, $moduleName, 'ja_jp'),
                'label_en' => self::translate($label, $moduleName, 'en_us'),
                'uitype' => RecordVersion_Util_Helper::toInt($fieldModel->get('uitype')),
                'datatype' => $dataType,
                'value' => is_scalar($value) || $value === null ? $value : RecordVersion_Util_Helper::toStr($value),
            ];
            if (in_array($dataType, self::NAMED_DATA_TYPES, true) && !self::isEmpty($value)) {
                $entry['display'] = self::plainDisplayValue($fieldModel, $value, $record);
            }
            $fields[$entry['fieldid']] = $entry;
        }
        return $fields;
    }

    /**
     * 品目明細（行・金額・税・手数料・通貨）
     *
     * @return array<string, mixed>
     */
    private static function captureInventory(Inventory_Record_Model $record): array
    {
        $moduleName = $record->getModuleName();
        $products = RecordVersion_Util_Helper::toArray($record->getProducts());
        $lines = [];
        $finalDetails = [];
        foreach ($products as $index => $product) {
            $product = RecordVersion_Util_Helper::toArray($product);
            $i = (string) $index;
            if (!isset($product['hdnProductId' . $i])) {
                continue;
            }
            if (isset($product['final_details'])) {
                $finalDetails = RecordVersion_Util_Helper::toArray($product['final_details']);
            }
            $lineTaxes = [];
            $totalAfterDiscount = (float) RecordVersion_Util_Helper::toStr($product['totalAfterDiscount' . $i] ?? '0');
            foreach (RecordVersion_Util_Helper::toList($product['taxes'] ?? []) as $tax) {
                $tax = RecordVersion_Util_Helper::toArray($tax);
                $percentage = (float) RecordVersion_Util_Helper::toStr($tax['percentage'] ?? '0');
                $lineTaxes[] = self::taxEntry($tax, $moduleName, $percentage, $totalAfterDiscount * $percentage / 100);
            }
            $lines[] = [
                'product_id' => RecordVersion_Util_Helper::toInt($product['hdnProductId' . $i]),
                'entity_type' => RecordVersion_Util_Helper::toStr($product['entityType' . $i] ?? ''),
                'name' => RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($product['productName' . $i] ?? ''))),
                'code' => RecordVersion_Util_Helper::toStr($product['hdnProductcode' . $i] ?? ''),
                'comment' => RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($product['comment' . $i] ?? ''))),
                'quantity' => RecordVersion_Util_Helper::toStr($product['qty' . $i] ?? '0'),
                'list_price' => RecordVersion_Util_Helper::toStr($product['listPrice' . $i] ?? '0'),
                'product_total' => RecordVersion_Util_Helper::toStr($product['productTotal' . $i] ?? '0'),
                'discount_type' => RecordVersion_Util_Helper::toStr($product['discount_type' . $i] ?? ''),
                'discount_percent' => RecordVersion_Util_Helper::toStr($product['discount_percent' . $i] ?? '0'),
                'discount_amount' => RecordVersion_Util_Helper::toStr($product['discount_amount' . $i] ?? '0'),
                'discount_total' => RecordVersion_Util_Helper::toStr($product['discountTotal' . $i] ?? '0'),
                'total_after_discount' => RecordVersion_Util_Helper::toStr($product['totalAfterDiscount' . $i] ?? '0'),
                'tax_total' => RecordVersion_Util_Helper::toStr($product['taxTotal' . $i] ?? '0'),
                'net_price' => RecordVersion_Util_Helper::toStr($product['netPrice' . $i] ?? '0'),
                'taxes' => $lineTaxes,
            ];
        }

        $groupTaxes = [];
        foreach (RecordVersion_Util_Helper::toArray($finalDetails['taxes'] ?? []) as $tax) {
            $tax = RecordVersion_Util_Helper::toArray($tax);
            $groupTaxes[] = self::taxEntry(
                $tax,
                $moduleName,
                (float) RecordVersion_Util_Helper::toStr($tax['percentage'] ?? '0'),
                (float) RecordVersion_Util_Helper::toStr($tax['amount'] ?? '0')
            );
        }

        return [
            'tax_mode' => RecordVersion_Util_Helper::toStr($finalDetails['taxtype'] ?? ''),
            'lines' => $lines,
            'sub_total' => RecordVersion_Util_Helper::toStr($finalDetails['hdnSubTotal'] ?? '0'),
            'discount_type' => RecordVersion_Util_Helper::toStr($finalDetails['discount_type_final'] ?? ''),
            'discount_percent' => RecordVersion_Util_Helper::toStr($finalDetails['discount_percentage_final'] ?? '0'),
            'discount_amount' => RecordVersion_Util_Helper::toStr($finalDetails['discount_amount_final'] ?? '0'),
            'discount_total' => RecordVersion_Util_Helper::toStr($finalDetails['discountTotal_final'] ?? '0'),
            'charges' => self::captureCharges($finalDetails, $moduleName),
            'charges_total' => RecordVersion_Util_Helper::toStr($finalDetails['shipping_handling_charge'] ?? '0'),
            'pre_tax_total' => RecordVersion_Util_Helper::toStr($finalDetails['preTaxTotal'] ?? '0'),
            'group_taxes' => $groupTaxes,
            'tax_total' => RecordVersion_Util_Helper::toStr($finalDetails['tax_totalamount'] ?? '0'),
            'charges_tax_total' => RecordVersion_Util_Helper::toStr($finalDetails['shtax_totalamount'] ?? '0'),
            'adjustment' => RecordVersion_Util_Helper::toStr($finalDetails['adjustment'] ?? '0'),
            'grand_total' => RecordVersion_Util_Helper::toStr($finalDetails['grandTotal'] ?? '0'),
            'currency' => self::captureCurrency($record),
        ];
    }

    /**
     * @param array<string, mixed> $finalDetails
     * @return array<int, array<string, mixed>>
     */
    private static function captureCharges(array $finalDetails, string $moduleName): array
    {
        $chargeInfos = RecordVersion_Util_Helper::toArray(getAllCharges());
        $shippingTaxes = [];
        foreach (RecordVersion_Util_Helper::toList(getAllTaxes('all', 'sh')) as $tax) {
            $tax = RecordVersion_Util_Helper::toArray($tax);
            $shippingTaxes[RecordVersion_Util_Helper::toStr($tax['taxid'] ?? '')] = $tax;
        }
        $charges = [];
        foreach (RecordVersion_Util_Helper::toArray($finalDetails['chargesAndItsTaxes'] ?? []) as $chargeId => $charge) {
            $charge = RecordVersion_Util_Helper::toArray($charge);
            $value = (float) RecordVersion_Util_Helper::toStr($charge['value'] ?? '0');
            $info = RecordVersion_Util_Helper::toArray($chargeInfos[$chargeId] ?? []);
            $name = RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($info['name'] ?? '')));
            $taxes = [];
            foreach (RecordVersion_Util_Helper::toArray($charge['taxes'] ?? []) as $taxId => $percentage) {
                $percent = (float) RecordVersion_Util_Helper::toStr($percentage);
                if ($percent == 0.0) {
                    continue;
                }
                $taxInfo = RecordVersion_Util_Helper::toArray($shippingTaxes[$taxId] ?? []);
                $taxes[] = self::taxEntry($taxInfo, $moduleName, $percent, $value * $percent / 100);
            }
            $charges[] = [
                'name' => $name,
                'name_ja' => self::translate($name, $moduleName, 'ja_jp'),
                'name_en' => self::translate($name, $moduleName, 'en_us'),
                'value' => RecordVersion_Util_Helper::toStr($charge['value'] ?? '0'),
                'taxes' => $taxes,
            ];
        }
        return $charges;
    }

    /**
     * @param array<string, mixed> $tax
     * @return array<string, mixed>
     */
    private static function taxEntry(array $tax, string $moduleName, float $percentage, float $amount): array
    {
        $label = RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($tax['taxlabel'] ?? '')));
        return [
            'name' => RecordVersion_Util_Helper::toStr($tax['taxname'] ?? ''),
            'label' => $label,
            'label_ja' => self::translate($label, $moduleName, 'ja_jp'),
            'label_en' => self::translate($label, $moduleName, 'en_us'),
            'percentage' => $percentage,
            'amount' => $amount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function captureCurrency(Vtiger_Record_Model $record): array
    {
        $currencyId = RecordVersion_Util_Helper::toInt($record->get('currency_id'));
        $info = RecordVersion_Util_Helper::toArray(getCurrencySymbolandCRate($currencyId));
        $name = '';
        $code = '';
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT currency_name, currency_code FROM vtiger_currency_info WHERE id = ?',
            [$currencyId]
        ));
        if (!empty($rows)) {
            $name = RecordVersion_Util_Helper::toStr($rows[0]['currency_name'] ?? '');
            $code = RecordVersion_Util_Helper::toStr($rows[0]['currency_code'] ?? '');
        }
        return [
            'id' => $currencyId,
            'name' => $name,
            'code' => $code,
            'symbol' => RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($info['symbol'] ?? ''))),
            'conversion_rate' => RecordVersion_Util_Helper::toStr($record->get('conversion_rate')),
        ];
    }

    /**
     * 版に含める関連 1 本分（関連が今は無ければ null）
     *
     * @return array<string, mixed>|null
     */
    private static function captureRelation(Vtiger_Record_Model $record, int $relationId): ?array
    {
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT r.label, t.name AS related_module FROM vtiger_relatedlists r
             INNER JOIN vtiger_tab t ON t.tabid = r.related_tabid WHERE r.relation_id = ?',
            [$relationId]
        ));
        if (empty($rows)) {
            return null;
        }
        $label = RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($rows[0]['label'] ?? '')));
        $relatedModule = RecordVersion_Util_Helper::toStr($rows[0]['related_module'] ?? '');
        $relationListView = Vtiger_RelationListView_Model::getInstance($record, $relatedModule, $label);
        if (!$relationListView || !$relationListView->getRelationModel()) {
            return null;
        }

        $records = [];
        $page = 1;
        do {
            $pagingModel = new Vtiger_Paging_Model();
            $pagingModel->set('page', $page);
            $pagingModel->set('limit', self::RELATED_PAGE_SIZE);
            // 並び順を固定する（ページをまたいでも同じ行を二度読まないように）
            $relationListView->set('orderby', 'vtiger_crmentity.crmid');
            $relationListView->set('sortorder', 'ASC');
            $entries = $relationListView->getEntries($pagingModel);
            foreach ($entries as $entryId => $entry) {
                $relatedId = RecordVersion_Util_Helper::toInt($entryId);
                if ($relatedId <= 0 || isset($records[$relatedId])) {
                    continue;
                }
                $records[$relatedId] = self::captureRelatedRecord($relatedId, $relatedModule, $entry);
                self::ensureMemory();
            }
            $page++;
        } while (RecordVersion_Util_Helper::isTruthy($pagingModel->get('nextPageExists')));

        return [
            'relation_id' => $relationId,
            'label' => $label,
            'label_ja' => self::translate($label, $relatedModule, 'ja_jp'),
            'label_en' => self::translate($label, $relatedModule, 'en_us'),
            'related_module' => $relatedModule,
            'records' => array_values(array_filter($records)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function captureRelatedRecord(int $relatedId, string $relatedModule, Vtiger_Record_Model $entry): ?array
    {
        try {
            $related = Vtiger_Record_Model::getInstanceById($relatedId, $relatedModule);
        } catch (Exception $e) {
            return null;
        }
        $relationValues = [];
        $raw = RecordVersion_Util_Helper::toArray($entry->getRawData());
        foreach (self::RELATION_VALUE_COLUMNS as $column) {
            if (array_key_exists($column, $raw) && !self::isEmpty($raw[$column])) {
                $relationValues[$column] = RecordVersion_Util_Helper::toStr($raw[$column]);
            }
        }
        $item = [
            'id' => $relatedId,
            'label' => RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($related->getName()))),
            'fields' => self::captureFields($related),
            'relation_values' => $relationValues,
        ];
        if ($relatedModule === 'Documents') {
            $item['document'] = self::captureDocument($related);
        }
        return $item;
    }

    /**
     * ドキュメントは、改版した時点で現在だったファイルバージョンを残す（ファイルそのものはコピーしない）
     *
     * @return array<string, mixed>
     */
    private static function captureDocument(Vtiger_Record_Model $document): array
    {
        $locationType = RecordVersion_Util_Helper::toStr($document->get('filelocationtype'));
        $fileName = RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($document->get('filename'))));
        $fileVersion = 0;
        if ($locationType === 'I') {
            $db = PearDatabase::getInstance();
            $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
                'SELECT version_number FROM vtiger_notes_file_versions WHERE notesid = ? AND is_current = 1',
                [RecordVersion_Util_Helper::toInt($document->getId())]
            ));
            if (!empty($rows)) {
                $fileVersion = RecordVersion_Util_Helper::toInt($rows[0]['version_number'] ?? 0);
            }
        }
        return [
            'location_type' => $locationType,
            'file_name' => $fileName,
            'file_version' => $fileVersion,
        ];
    }

    private static function plainDisplayValue(Vtiger_Field_Model $fieldModel, mixed $value, Vtiger_Record_Model $record): string
    {
        $display = $fieldModel->getDisplayValue($value, $record->getId(), $record);
        $text = RecordVersion_Util_Helper::toStr(decode_html(RecordVersion_Util_Helper::toStr($display)));
        return trim(strip_tags($text));
    }

    private static function translate(string $label, string $moduleName, string $language): string
    {
        if ($label === '') {
            return '';
        }
        return RecordVersion_Util_Helper::toStr(Vtiger_Language_Handler::getTranslatedString($label, $moduleName, $language));
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === '0' || $value === 0;
    }
}
