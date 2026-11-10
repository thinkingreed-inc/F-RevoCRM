<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use RecordVersion_Config_Model;
use RecordVersion_Display_Model;
use RecordVersion_Label_Model;
use RecordVersion_Revision_Model;
use RecordVersion_Setup_Model;
use RecordVersion_Util_Helper;
use RecordVersion_Version_Model;
use Tests\Support\RecordVersionTestCase;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 改版したあとに項目設定や参照先が変わっても、過去の版を当時の内容で読めるか（確認事項 Q36・Q37）
 */
final class ChangedSettingsTest extends RecordVersionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVersioning();
    }

    public function test_非表示の項目の値も過去の版に保存する(): void
    {
        $fieldId = $this->fieldId('probability');
        $recordId = $this->createPotential('非表示の値', ['probability' => '30']);
        $this->db->pquery('UPDATE vtiger_field SET presence = 1 WHERE fieldid = ?', [$fieldId]);
        try {
            \Vtiger_Cache::$cacheEnable = false;
            RecordVersion_Revision_Model::revise($recordId, '改版');
        } finally {
            $this->db->pquery('UPDATE vtiger_field SET presence = 2 WHERE fieldid = ?', [$fieldId]);
        }
        $this->assertSame(30.0, (float) RecordVersion_Util_Helper::toStr($this->snapshotFieldsByName($recordId)['probability']['value'] ?? ''));
    }

    public function test_項目の改名とブロックの移動は今の表示名と位置で出しJSONには当時の表示名が残る(): void
    {
        $recordId = $this->createPotential('改名と移動');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $amountId = $this->fieldId('amount');
        $closingId = $this->fieldId('closingdate');
        $oldLabel = $this->scalar('SELECT fieldlabel FROM vtiger_field WHERE fieldid = ?', [$amountId]);
        $oldBlock = $this->scalar('SELECT block FROM vtiger_field WHERE fieldid = ?', [$closingId]);
        $otherBlock = $this->scalar('SELECT blockid FROM vtiger_blocks WHERE tabid = ? AND blockid <> ? ORDER BY sequence LIMIT 1', [$this->tabId, $oldBlock]);
        $this->db->pquery("UPDATE vtiger_field SET fieldlabel = 'SPECTEST 改名した金額' WHERE fieldid = ?", [$amountId]);
        $this->db->pquery('UPDATE vtiger_field SET block = ? WHERE fieldid = ?', [$otherBlock, $closingId]);
        try {
            \Vtiger_Cache::$cacheEnable = false;
            $blocks = $this->displayedBlocks($this->snapshotFields($recordId));
        } finally {
            $this->db->pquery('UPDATE vtiger_field SET fieldlabel = ? WHERE fieldid = ?', [$oldLabel, $amountId]);
            $this->db->pquery('UPDATE vtiger_field SET block = ? WHERE fieldid = ?', [$oldBlock, $closingId]);
        }
        $this->assertArrayHasKey('SPECTEST 改名した金額', $this->flatten($blocks), '今の表示名で出す');
        $otherBlockLabel = vtranslate($this->scalar('SELECT blocklabel FROM vtiger_blocks WHERE blockid = ?', [$otherBlock]), self::MODULE);
        $this->assertArrayHasKey(vtranslate('Expected Close Date', self::MODULE), $blocks[$otherBlockLabel] ?? [], '今のブロックの位置に出す');
        $this->assertSame('Amount', $this->snapshotFieldsByName($recordId)['amount']['label'], 'JSON には当時の表示名が残る');
    }

    public function test_項目ごとに当時のブロックとブロックの順と項目の順を保存する(): void
    {
        $recordId = $this->createPotential('ブロック');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $entry = $this->snapshotFieldsByName($recordId)['potentialname'];
        $expected = RecordVersion_Util_Helper::fetchRows($this->db->pquery(
            'SELECT b.blocklabel, b.sequence AS block_sequence, f.sequence FROM vtiger_field f JOIN vtiger_blocks b ON b.blockid = f.block WHERE f.fieldid = ?',
            [$this->fieldId('potentialname')]
        ))[0];
        $this->assertSame($expected['blocklabel'], $entry['block_label'], '当時のブロック（翻訳前）');
        $snapshot = RecordVersion_Version_Model::getByNumber($recordId, 1)?->getSnapshot() ?? [];
        $blockNames = RecordVersion_Util_Helper::toArray(RecordVersion_Util_Helper::toArray(RecordVersion_Util_Helper::toArray($snapshot['labels'] ?? [])[self::MODULE] ?? [])[RecordVersion_Util_Helper::toStr($entry['block_label'])] ?? []);
        foreach (['ja_jp', 'en_us'] as $language) {
            $this->assertSame(
                \Vtiger_Language_Handler::getLanguageTranslatedString($language, RecordVersion_Util_Helper::toStr($expected['blocklabel']), self::MODULE),
                $blockNames[$language] ?? null,
                '当時のブロック名の訳は表示名の辞書に残す'
            );
        }
        $this->assertSame(RecordVersion_Util_Helper::toInt($expected['block_sequence']), $entry['block_sequence']);
        $this->assertSame(RecordVersion_Util_Helper::toInt($expected['sequence']), $entry['sequence']);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function changedTypes(): array
    {
        return [
            '文字→日付' => [5, 'D~O'],
            '文字→関連項目' => [10, 'V~O'],
        ];
    }

    /**
     * @dataProvider changedTypes
     */
    public function test_当時と今で項目の種類が違うときは当時の値をそのまま出す(int $uitype, string $typeofdata): void
    {
        $fieldId = $this->fieldId('nextstep');
        $recordId = $this->createPotential('種類の変更', ['nextstep' => '来期の段取り']);
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $old = RecordVersion_Util_Helper::fetchRows($this->db->pquery('SELECT uitype, typeofdata FROM vtiger_field WHERE fieldid = ?', [$fieldId]))[0];
        $this->db->pquery('UPDATE vtiger_field SET uitype = ?, typeofdata = ? WHERE fieldid = ?', [$uitype, $typeofdata, $fieldId]);
        try {
            \Vtiger_Cache::$cacheEnable = false;
            $values = $this->flatten($this->displayedBlocks($this->snapshotFields($recordId)));
        } finally {
            $this->db->pquery('UPDATE vtiger_field SET uitype = ?, typeofdata = ? WHERE fieldid = ?', [$old['uitype'], $old['typeofdata'], $fieldId]);
        }
        $this->assertSame('来期の段取り', $values[vtranslate('Next Step', self::MODULE)] ?? null, '今の種類で整えずに当時の値を出す');
    }

    public function test_品目明細の無いモジュールの金額は改版した時点の基本通貨の記号で出す(): void
    {
        $recordId = $this->createPotential('基本通貨', ['amount' => 1500]);
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $snapshot = RecordVersion_Version_Model::getByNumber($recordId, 1)?->getSnapshot() ?? [];
        $base = RecordVersion_Util_Helper::toArray($snapshot['base_currency'] ?? []);
        $this->assertSame(
            $this->scalar('SELECT currency_code FROM vtiger_currency_info WHERE id = ?', [\CurrencyField::getDBCurrencyId()]),
            $base['code'] ?? null,
            '改版した時点の基本通貨を保存する'
        );
        // 基本通貨の記号が当時と今で違っても、当時の記号で出す
        $snapshot['base_currency'] = ['code' => 'XXX', 'symbol' => 'X$'];
        $display = new RecordVersion_Display_Model(RecordVersion_Display_Model::getCurrencySymbol($snapshot), new RecordVersion_Label_Model($snapshot));
        $values = [];
        foreach ($display->buildFieldBlocks(self::MODULE, RecordVersion_Util_Helper::toArray($snapshot['fields'] ?? []))['blocks'] as $block) {
            foreach ($block['fields'] as $field) {
                $values[$field['label']] = html_entity_decode($field['value']);
            }
        }
        $this->assertStringStartsWith('X$', $values[vtranslate('Amount', self::MODULE)] ?? '');
    }

    public function test_今は無い選択肢は当時の値のまま出す(): void
    {
        $recordId = $this->createPotential('選択肢');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $fields = $this->snapshotFields($recordId);
        foreach ($fields as $key => $entry) {
            if (($entry['name'] ?? '') === 'sales_stage') {
                $fields[$key]['value'] = 'SPECTEST 消えた選択肢';
            }
        }
        $this->assertSame('SPECTEST 消えた選択肢', $this->flatten($this->displayedBlocks($fields))[vtranslate('Sales Stage', self::MODULE)] ?? null);
    }

    public function test_参照先と品目明細の製品と税をあとで改名削除しても当時の名前と税率で出す(): void
    {
        $quotesTab = (int) getTabid('Quotes');
        $wasEnabled = RecordVersion_Config_Model::isEnabledTab($quotesTab);
        $quoteId = (int) $this->scalar("SELECT q.quoteid FROM vtiger_quotes q JOIN vtiger_crmentity c ON c.crmid = q.quoteid AND c.deleted = 0 WHERE q.taxtype = 'group' ORDER BY q.quoteid LIMIT 1");
        if ($quoteId === 0) {
            self::markTestSkipped('グループ税の見積がテスト用DBに無いため飛ばします');
        }
        $versionBefore = $this->currentVersion($quoteId);
        $accountId = (int) $this->scalar('SELECT accountid FROM vtiger_quotes WHERE quoteid = ?', [$quoteId]);
        $accountName = $this->scalar('SELECT accountname FROM vtiger_account WHERE accountid = ?', [$accountId]);
        $productId = (int) $this->scalar('SELECT productid FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no LIMIT 1', [$quoteId]);
        $productName = $this->scalar('SELECT productname FROM vtiger_products WHERE productid = ?', [$productId]);
        $taxLabel = $this->scalar("SELECT taxlabel FROM vtiger_inventorytaxinfo WHERE taxname = 'tax4'");
        $taxPercent = $this->scalar("SELECT percentage FROM vtiger_inventorytaxinfo WHERE taxname = 'tax4'");
        RecordVersion_Setup_Model::enable($quotesTab);
        RecordVersion_Config_Model::clearCache();
        try {
            $this->assertTrue(RecordVersion_Revision_Model::revise($quoteId, '改版')['success']);
            $snapshot = RecordVersion_Version_Model::getByNumber($quoteId, max(1, $versionBefore))?->getSnapshot() ?? [];
            $this->db->pquery("UPDATE vtiger_account SET accountname = 'SPECTEST 改名後' WHERE accountid = ?", [$accountId]);
            $this->db->pquery("UPDATE vtiger_products SET productname = 'SPECTEST 改名後' WHERE productid = ?", [$productId]);
            $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid IN (?, ?)', [$accountId, $productId]);
            $this->db->pquery("UPDATE vtiger_inventorytaxinfo SET taxlabel = 'SPECTEST 改名後', percentage = 8 WHERE taxname = 'tax4'", []);

            $display = new RecordVersion_Display_Model(RecordVersion_Display_Model::getCurrencySymbol($snapshot), new RecordVersion_Label_Model($snapshot));
            $values = $this->flatten($this->displayedBlocks(RecordVersion_Util_Helper::toArray($snapshot['fields'] ?? []), 'Quotes'));
            $this->assertSame($accountName, $values[vtranslate('Account Name', 'Quotes')] ?? null, '参照先は当時の名前');
            $inventory = $display->buildInventory('Quotes', RecordVersion_Util_Helper::toArray($snapshot['inventory'] ?? []));
            $lineNames = [];
            foreach (RecordVersion_Util_Helper::toList($inventory['lines'] ?? []) as $line) {
                $lineNames[] = RecordVersion_Util_Helper::toStr(RecordVersion_Util_Helper::toArray($line)['name'] ?? '');
            }
            $this->assertContains($productName, $lineNames, '製品は当時の名前');
            $groupTaxes = RecordVersion_Util_Helper::toList($inventory['group_taxes'] ?? []);
            $taxLabelShown = RecordVersion_Util_Helper::toStr(RecordVersion_Util_Helper::toArray($groupTaxes[0] ?? [])['label'] ?? '');
            $this->assertStringContainsString('(10%)', $taxLabelShown, '税は当時の税率');
            $this->assertStringNotContainsString('SPECTEST', $taxLabelShown, '税は当時の名前');
        } finally {
            $this->db->pquery('UPDATE vtiger_account SET accountname = ? WHERE accountid = ?', [$accountName, $accountId]);
            $this->db->pquery('UPDATE vtiger_products SET productname = ? WHERE productid = ?', [$productName, $productId]);
            $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 0 WHERE crmid IN (?, ?)', [$accountId, $productId]);
            $this->db->pquery("UPDATE vtiger_inventorytaxinfo SET taxlabel = ?, percentage = ? WHERE taxname = 'tax4'", [$taxLabel, $taxPercent]);
            $this->db->pquery('DELETE FROM vtiger_recordversion WHERE crmid = ? AND version_no >= ?', [$quoteId, max(1, $versionBefore)]);
            $this->db->pquery('UPDATE vtiger_crmentity SET version = ? WHERE crmid = ?', [$versionBefore, $quoteId]);
            if (!$wasEnabled) {
                RecordVersion_Setup_Model::disable($quotesTab);
            }
        }
    }

    private function fieldId(string $name): int
    {
        return (int) $this->scalar('SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?', [$this->tabId, $name]);
    }

    /**
     * @param array<int|string, mixed> $fields
     * @return array<string, array<string, string>> ブロック名 => (表示名 => 値)
     */
    private function displayedBlocks(array $fields, string $module = self::MODULE): array
    {
        $blocks = [];
        foreach ((new RecordVersion_Display_Model('¥', new RecordVersion_Label_Model(['format' => RecordVersion_Label_Model::FORMAT_DICTIONARY])))->buildFieldBlocks($module, $fields)['blocks'] as $block) {
            foreach ($block['fields'] as $field) {
                $blocks[$block['label']][$field['label']] = trim(strip_tags(html_entity_decode($field['value'])));
            }
        }
        return $blocks;
    }

    /**
     * @param array<string, array<string, string>> $blocks
     * @return array<string, string>
     */
    private function flatten(array $blocks): array
    {
        return array_merge(...array_values($blocks));
    }
}
