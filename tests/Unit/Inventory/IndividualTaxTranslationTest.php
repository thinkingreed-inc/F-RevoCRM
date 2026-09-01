<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Inventory;

use PHPUnit\Framework\TestCase;

$individualTaxTestRoot = dirname(__DIR__, 3);
require_once $individualTaxTestRoot . '/tests/Support/LanguageHandlerStubs.php';
require_once $individualTaxTestRoot . '/includes/runtime/Controller.php';
require_once $individualTaxTestRoot . '/modules/Inventory/actions/GetTaxes.php';
require_once $individualTaxTestRoot . '/modules/PurchaseOrder/actions/GetTaxes.php';

/**
 * 在庫管理系モジュール (見積・受注・請求・仕入注文) の品目で消費税を「個別」に
 * 設定した際、税のモーダルに表示されるラベルの翻訳のテスト。
 *
 * vtiger_inventorytaxinfo.taxlabel には翻訳キー ('LBL_CONSUMPTION_TAX') が
 * 格納されるため (setup/scripts/17_Update_Inventory.php)、表示側で翻訳を通す
 * 必要がある。グループ課税側は翻訳済みだが個別課税側が素通しだった不具合への対応。
 */
final class IndividualTaxTranslationTest extends TestCase
{
    /** @return array<string, array<string, mixed>> GetTaxes が扱う税情報の形 */
    private function taxesFixture(): array
    {
        return [
            'tax4' => [
                'taxid'         => '4',
                'taxname'       => 'tax4',
                'taxlabel'      => 'LBL_CONSUMPTION_TAX',
                'taxpercentage' => '10.000',
                'compoundOn'    => [],
                'regionsList'   => '{"default":"10.000"}',
            ],
            'tax5' => [
                'taxid'         => '5',
                'taxname'       => 'tax5',
                'taxlabel'      => 'test',
                'taxpercentage' => '8.000',
                'compoundOn'    => [],
                'regionsList'   => '{"default":"8.000"}',
            ],
        ];
    }

    public function test_translation_key_label_is_translated(): void
    {
        $taxes = \Inventory_GetTaxes_Action::translateTaxLabels($this->taxesFixture(), 'Quotes');

        $this->assertSame('消費税', $taxes['tax4']['taxlabel']);
    }

    public function test_user_defined_label_is_returned_as_is(): void
    {
        // 管理画面から追加した任意の税名は翻訳キーではないためそのまま表示する。
        $taxes = \Inventory_GetTaxes_Action::translateTaxLabels($this->taxesFixture(), 'Quotes');

        $this->assertSame('test', $taxes['tax5']['taxlabel']);
    }

    public function test_other_tax_attributes_are_untouched(): void
    {
        // taxname は入力要素の name 生成に使うため書き換えてはならない。
        $taxes = \Inventory_GetTaxes_Action::translateTaxLabels($this->taxesFixture(), 'Quotes');

        $this->assertSame('tax4', $taxes['tax4']['taxname']);
        $this->assertSame('4', $taxes['tax4']['taxid']);
        $this->assertSame('10.000', $taxes['tax4']['taxpercentage']);
        $this->assertSame('{"default":"10.000"}', $taxes['tax4']['regionsList']);
        $this->assertSame(['tax4', 'tax5'], array_keys($taxes));
    }

    public function test_empty_taxes_returns_empty_array(): void
    {
        $this->assertSame([], \Inventory_GetTaxes_Action::translateTaxLabels([], 'Quotes'));
    }

    public function test_tax_without_label_is_kept(): void
    {
        // taxlabel が空の税情報でも例外を出さずそのまま返す。
        $taxes = \Inventory_GetTaxes_Action::translateTaxLabels(
            ['tax6' => ['taxname' => 'tax6', 'taxlabel' => '']],
            'Quotes'
        );

        $this->assertSame('', $taxes['tax6']['taxlabel']);
    }

    public function test_purchase_order_action_shares_the_translation(): void
    {
        // 仕入注文は独自の GetTaxes を持つが同じモーダル UI を使うため翻訳も共有する。
        $taxes = \PurchaseOrder_GetTaxes_Action::translateTaxLabels($this->taxesFixture(), 'PurchaseOrder');

        $this->assertSame('消費税', $taxes['tax4']['taxlabel']);
    }

    /**
     * モーダルのタイトル "課税対象：" は JS が動的に組み立てるため
     * jsLanguageStrings 側に定義が必要 (languageStrings 側の LBL_SET_TAX_FOR は
     * app.vtranslate から引けない)。
     */
    public function test_js_set_tax_for_is_defined_in_ja_jp_and_en_us(): void
    {
        $ja = \Vtiger_Language_Handler::getModuleStringsFromFile('ja_jp', 'Vtiger');
        $en = \Vtiger_Language_Handler::getModuleStringsFromFile('en_us', 'Vtiger');

        $this->assertArrayHasKey('JS_SET_TAX_FOR', $ja['jsLanguageStrings']);
        $this->assertArrayHasKey('JS_SET_TAX_FOR', $en['jsLanguageStrings']);
        $this->assertSame('課税対象：', $ja['jsLanguageStrings']['JS_SET_TAX_FOR']);
        $this->assertSame('Set Tax for', $en['jsLanguageStrings']['JS_SET_TAX_FOR']);
    }
}
