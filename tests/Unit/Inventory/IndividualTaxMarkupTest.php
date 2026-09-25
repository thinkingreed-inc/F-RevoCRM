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

/**
 * 在庫管理系モジュール (見積・受注・請求) の品目で消費税を「個別」にした際に
 * 表示される税の popover 内、税率入力欄のマークアップのテスト。
 *
 * 既存レコードを開いた編集画面の行は LineItemsContent.tpl がサーバ側で描画するが、
 * 新規作成や製品/サービスを選び直した行は Edit.js の getTaxDiv がクライアント側で
 * 組み立てる。popover の幅は 350px 固定 (skins/*\/style.less の .lineItemPopover)
 * のため、幅指定クラス span1 が欠けると税率欄が広がり税ラベルが 1 文字ずつ折り返して
 * レイアウトが崩れる。2 つの経路のマークアップが揃っていることを検証する。
 */
final class IndividualTaxMarkupTest extends TestCase
{
    private const EDIT_JS  = '/public/layouts/v7/modules/Inventory/resources/Edit.js';
    private const LINE_TPL = '/layouts/v7/modules/Inventory/partials/LineItemsContent.tpl';

    private function readSource(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . $relativePath);
        $this->assertIsString($contents, $relativePath . ' が読み込めない');

        return $contents;
    }

    /**
     * 税率入力欄の class 属性値をトークンの配列として取り出す。
     *
     * @return array<int, string> ソート済みのクラス名一覧
     */
    private function taxPercentageClassTokens(string $source): array
    {
        $matched = preg_match('/class="([^"]*\btaxPercentage\b[^"]*)"/', $source, $matches);
        $this->assertSame(1, $matched, 'taxPercentage を持つ input が見つからない');

        $tokens = preg_split('/\s+/', trim($matches[1]));
        $this->assertNotFalse($tokens, 'class 属性値の分割に失敗した');
        sort($tokens);

        return $tokens;
    }

    public function test_js_generated_tax_percentage_input_has_width_class(): void
    {
        // span1 (width:100px) が無いと popover 内で税ラベルが縦に潰れる。
        $tokens = $this->taxPercentageClassTokens($this->readSource(self::EDIT_JS));

        $this->assertContains('span1', $tokens);
    }

    public function test_tax_percentage_classes_match_between_js_and_tpl(): void
    {
        $jsTokens  = $this->taxPercentageClassTokens($this->readSource(self::EDIT_JS));
        $tplTokens = $this->taxPercentageClassTokens($this->readSource(self::LINE_TPL));

        $this->assertSame($tplTokens, $jsTokens);
    }
}
