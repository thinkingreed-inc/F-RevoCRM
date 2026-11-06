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

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vtiger_AutoNumberFormat;

require_once dirname(__DIR__, 4) . '/modules/Vtiger/utils/AutoNumberFormat.php';

/**
 * 自動採番の書式（Vtiger_AutoNumberFormat）のテスト。DB は使わない。
 */
final class AutoNumberFormatTest extends TestCase
{
    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value);
    }

    // ---- render -----------------------------------------------------------

    /** @return array<string, array{0: string, 1: string, 2: int, 3: array<string, string>, 4: string}> */
    public static function renderProvider(): array
    {
        return [
            '年月と連番' => ['INV-{YYYY}{MM}-{0000}', '2026-10-27', 7, [], 'INV-202610-0007'],
            '2桁の年' => ['Q{YY}-{0000}', '2026-10-27', 12, [], 'Q26-0012'],
            '日' => ['{YYYY}{MM}{DD}-{000}', '2026-01-05', 3, [], '20260105-003'],
            '連番だけ' => ['{0}', '2026-10-27', 5, [], '5'],
            '桁あふれは桁が増える' => ['N{000}', '2026-10-27', 1234, [], 'N1234'],
            '項目の値' => ['{cf_1}-{0000}', '2026-10-27', 1, ['cf_1' => 'A'], 'A-0001'],
            '項目の値が空なら空文字' => ['{cf_1}-{0000}', '2026-10-27', 1, ['cf_1' => ''], '-0001'],
            '項目の値が無ければ空文字' => ['{cf_1}-{0000}', '2026-10-27', 1, [], '-0001'],
            '項目の値の禁止文字は空文字' => ['{cf_1}-{0000}', '2026-10-27', 1, ['cf_1' => '<b>'], '-0001'],
            '項目の値が長すぎれば空文字' => ['{cf_1}-{0000}', '2026-10-27', 1, ['cf_1' => str_repeat('a', 51)], '-0001'],
            '項目の値は日本語も可' => ['{cf_1}-{0000}', '2026-10-27', 1, ['cf_1' => '法人'], '法人-0001'],
            '年末' => ['{YYYY}{MM}{DD}-{00}', '2026-12-31', 1, [], '20261231-01'],
            'うるう日' => ['{YYYY}{MM}{DD}-{00}', '2024-02-29', 1, [], '20240229-01'],
            '固定文字の記号' => ['A/B_C.D#E(1)-{00}', '2026-10-27', 9, [], 'A/B_C.D#E(1)-09'],
            '日本語の固定文字' => ['見積{YY}-{000}', '2026-10-27', 2, [], '見積26-002'],
        ];
    }

    /**
     * @param array<string, string> $fields
     */
    #[DataProvider('renderProvider')]
    public function testRender(string $format, string $date, int $seq, array $fields, string $expected): void
    {
        $this->assertSame($expected, Vtiger_AutoNumberFormat::render($format, $this->date($date), $seq, $fields));
    }

    // ---- scopeKey ---------------------------------------------------------

    public function testScopeKey(): void
    {
        $date = $this->date('2026-10-27 12:34:56');
        $this->assertSame('', Vtiger_AutoNumberFormat::scopeKey('none', $date));
        $this->assertSame('2026', Vtiger_AutoNumberFormat::scopeKey('year', $date));
        $this->assertSame('202610', Vtiger_AutoNumberFormat::scopeKey('month', $date));
        $this->assertSame('20261027', Vtiger_AutoNumberFormat::scopeKey('day', $date));
    }

    public function testScopeKeyAtBoundaries(): void
    {
        $this->assertSame('2026', Vtiger_AutoNumberFormat::scopeKey('year', $this->date('2026-12-31 23:59:59')));
        $this->assertSame('2027', Vtiger_AutoNumberFormat::scopeKey('year', $this->date('2027-01-01 00:00:00')));
        $this->assertSame('202602', Vtiger_AutoNumberFormat::scopeKey('month', $this->date('2026-02-28')));
        $this->assertSame('20240229', Vtiger_AutoNumberFormat::scopeKey('day', $this->date('2024-02-29')));
    }

    public function testScopeKeyRejectsUnknownResetType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Vtiger_AutoNumberFormat::scopeKey('week', $this->date('2026-10-27'));
    }

    // ---- validate ---------------------------------------------------------

    /** @return array<string, array{0: string, 1: string, 2: list<string>}> */
    public static function validateProvider(): array
    {
        return [
            '正しい書式' => ['INV-{YYYY}{MM}-{0000}', 'month', []],
            'リセットなし' => ['INV-{0000}', 'none', []],
            '項目の値を含む' => ['{cf_1}-{000}', 'none', []],
            '空' => ['', 'none', ['FORMAT_EMPTY']],
            '連番が無い' => ['INV-{YYYY}', 'none', ['FORMAT_NO_SEQ']],
            '連番が2つ' => ['{000}-{000}', 'none', ['FORMAT_MULTI_SEQ']],
            '連番の桁が多すぎる' => ['{0000000000}', 'none', ['SEQ_DIGITS_RANGE']],
            '未知のトークン' => ['{YYYY}-{??}-{000}', 'none', ['FORMAT_UNKNOWN_TOKEN']],
            '閉じていない括弧' => ['{YYYY-{000}', 'none', ['FORMAT_UNKNOWN_TOKEN']],
            '開いていない括弧' => ['YYYY}-{000}', 'none', ['FORMAT_UNKNOWN_TOKEN']],
            '禁止文字（タグ）' => ['<b>{000}', 'none', ['FORMAT_INVALID_CHAR']],
            '禁止文字（引用符）' => ['"A"{000}', 'none', ['FORMAT_INVALID_CHAR']],
            '禁止文字（制御文字）' => ["A\n{000}", 'none', ['FORMAT_INVALID_CHAR']],
            '年リセットに年が無い' => ['INV-{0000}', 'year', ['RESET_NEEDS_YEAR']],
            '年リセットは2桁の年でも可' => ['INV{YY}-{0000}', 'year', []],
            '月リセットに月が無い' => ['INV{YYYY}-{0000}', 'month', ['RESET_NEEDS_MONTH']],
            '月リセットに年が無い' => ['INV{MM}-{0000}', 'month', ['RESET_NEEDS_YEAR']],
            '日リセットに日が無い' => ['INV{YYYY}{MM}-{0000}', 'day', ['RESET_NEEDS_DAY']],
            '日リセット' => ['{YYYY}{MM}{DD}-{0000}', 'day', []],
            '不明なリセット' => ['{0000}', 'week', ['RESET_INVALID']],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('validateProvider')]
    public function testValidate(string $format, string $reset, array $expected): void
    {
        $this->assertSame($expected, Vtiger_AutoNumberFormat::validate($format, $reset));
    }

    public function testValidateRejectsTooLongFormat(): void
    {
        $format = str_repeat('A', 95) . '{000}';
        $this->assertContains('FORMAT_TOO_LONG', Vtiger_AutoNumberFormat::validate($format, 'none'));
    }

    public function testValidateRejectsFormatWhoseOutputCanExceedColumn(): void
    {
        // 展開すると 4 + 10 + 2 + 9 + ... が列長（100）を超えうる書式
        $format = str_repeat('A', 90) . '{YYYY}{MM}{DD}{000000000}';
        $this->assertContains('FORMAT_TOO_LONG', Vtiger_AutoNumberFormat::validate($format, 'none'));
    }

    public function testFieldTokensAreListed(): void
    {
        $this->assertSame(['cf_1', 'cf_2'], Vtiger_AutoNumberFormat::getFieldTokens('{cf_1}-{YYYY}-{cf_2}-{000}-{cf_1}'));
        $this->assertSame([], Vtiger_AutoNumberFormat::getFieldTokens('{YYYY}-{000}'));
    }

    // ---- 固定文字 ---------------------------------------------------------

    public function testLiteralAllowList(): void
    {
        foreach (['INV', 'inv-1', '見積', 'A_B/C.D#E(1)', 'ＡＢＣ', 'A B'] as $ok) {
            $this->assertTrue(Vtiger_AutoNumberFormat::isValidLiteral($ok), $ok);
        }
        foreach (['<script>', "a'b", 'a"b', "a\tb", 'a;b', 'a&b', 'a%b', "a\0b", '😀', 'a\\b', 'a,b', 'a:b'] as $ng) {
            $this->assertFalse(Vtiger_AutoNumberFormat::isValidLiteral($ng), $ng);
        }
    }

    /** 展開後の最大の長さ: 固定文字 + 年4/2・月2・日2 + 連番（最大 10 桁）+ 項目の値（最大 50） */
    public function testMaxRenderedLength(): void
    {
        $this->assertSame(4 + 4 + 2 + 1 + 10, Vtiger_AutoNumberFormat::maxRenderedLength('INV-{YYYY}{MM}-{0000}'));
        $this->assertSame(2 + 10, Vtiger_AutoNumberFormat::maxRenderedLength('{YY}{0}'));
        $this->assertSame(50 + 1 + 10, Vtiger_AutoNumberFormat::maxRenderedLength('{cf_1}-{000}'));
        $this->assertSame(2 + 1 + 12, Vtiger_AutoNumberFormat::maxRenderedLength('見積-{000000000000}') - 0);
    }
}
