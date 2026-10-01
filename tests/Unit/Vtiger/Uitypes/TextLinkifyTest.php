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

namespace Tests\Unit\Vtiger\Uitypes;

use PHPUnit\Framework\TestCase;
use Vtiger_Text_UIType;

$textLinkifyTestRoot = dirname(__DIR__, 4);
require_once $textLinkifyTestRoot . '/includes/runtime/BaseModel.php';
require_once $textLinkifyTestRoot . '/modules/Vtiger/uitypes/Base.php';
require_once $textLinkifyTestRoot . '/modules/Vtiger/uitypes/Text.php';

/**
 * テキスト項目内のURL自動リンク表示 (#1773) のテスト。
 *
 * 角括弧の対応判定 (閉じ括弧だけを本文へ戻す) と、href / リンクテキストへ
 * 埋め込む際のHTMLエスケープを対象にする。
 */
final class TextLinkifyTest extends TestCase
{
    /** 対応する '[' があるURL末尾の ']' はURLの一部として残す */
    public function testKeepsClosingBracketWhenPaired(): void
    {
        $result = Vtiger_Text_UIType::linkifyUrls('https://example.com/path[1]');

        $this->assertStringContainsString('href="https://example.com/path[1]"', $result);
        $this->assertStringContainsString('>https://example.com/path[1]</a>', $result);
    }

    /** 対応する '[' がない末尾の ']' はURLから外して本文側へ残す */
    public function testDropsUnpairedClosingBracket(): void
    {
        $result = Vtiger_Text_UIType::linkifyUrls('https://example.com/path]');

        $this->assertStringContainsString('href="https://example.com/path"', $result);
        $this->assertStringEndsWith('</a>]', $result);
    }

    /** クエリ文字列の '&' は href とリンクテキストの双方でエスケープする */
    public function testEscapesAmpersandInUrl(): void
    {
        $result = Vtiger_Text_UIType::linkifyUrls('https://example.com/?a=1&b=2');

        $this->assertStringContainsString('href="https://example.com/?a=1&amp;b=2"', $result);
        $this->assertStringContainsString('>https://example.com/?a=1&amp;b=2</a>', $result);
        $this->assertStringNotContainsString('?a=1&b=2', $result);
    }

    /** 既にエンティティ化された '&amp;' を二重エスケープしない */
    public function testDoesNotDoubleEscapeExistingEntity(): void
    {
        $result = Vtiger_Text_UIType::linkifyUrls('https://example.com/?a=1&amp;b=2');

        $this->assertStringContainsString('href="https://example.com/?a=1&amp;b=2"', $result);
        $this->assertStringNotContainsString('&amp;amp;', $result);
    }
}
