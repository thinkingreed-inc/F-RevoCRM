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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vtiger_YearMonth_UIType;

$yearMonthTestRoot = dirname(__DIR__, 4);
require_once $yearMonthTestRoot . '/includes/runtime/BaseModel.php';
require_once $yearMonthTestRoot . '/modules/Vtiger/uitypes/Base.php';
require_once $yearMonthTestRoot . '/modules/Vtiger/uitypes/Date.php';
require_once $yearMonthTestRoot . '/modules/Vtiger/uitypes/YearMonth.php';

/**
 * 年月項目の値の正規化 (Vtiger_YearMonth_UIType::normalize) のテスト。
 *
 * どんな形で値が来ても、月初日 (YYYY-MM-01) に丸める。解釈できない値は null。
 */
final class YearMonthNormalizeTest extends TestCase
{
    /** @return array<string, array{0: mixed, 1: string|null}> */
    public static function provider(): array
    {
        return [
            '月初日' => ['2026-10-01', '2026-10-01'],
            '月の途中は月初日に丸める' => ['2026-10-28', '2026-10-01'],
            '月末も月初日に丸める' => ['2026-10-31', '2026-10-01'],
            '年月だけ' => ['2026-10', '2026-10-01'],
            '1桁の月' => ['2026-3', '2026-03-01'],
            '1桁の月と日' => ['2026-3-9', '2026-03-01'],
            'スラッシュ区切り' => ['2026/10/28', '2026-10-01'],
            'スラッシュ区切りの年月' => ['2026/10', '2026-10-01'],
            '日本語' => ['2026年10月', '2026-10-01'],
            '日本語（日付つき）' => ['2026年3月9日', '2026-03-01'],
            '日本語（空白あり）' => ['2026年 3月', '2026-03-01'],
            '6桁の数字' => ['202610', '2026-10-01'],
            '8桁の数字' => ['20261028', '2026-10-01'],
            '時刻つき' => ['2026-10-28 12:34:56', '2026-10-01'],
            '前後の空白' => ['  2026-10  ', '2026-10-01'],
            'うるう日' => ['2024-02-29', '2024-02-01'],
            '空文字' => ['', ''],
            'null' => [null, ''],
            '空白だけ' => ['   ', ''],
            '月が13' => ['2026-13', null],
            '月が0' => ['2026-00-01', null],
            '存在しない日' => ['2026-02-30', null],
            'うるう年でない2/29' => ['2025-02-29', null],
            '文字列' => ['abc', null],
            '年が3桁' => ['999-10', null],
            '配列' => [['2026-10'], null],
            'ゼロ日付' => ['0000-00-00', null],
        ];
    }

    #[DataProvider('provider')]
    public function testNormalize(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, Vtiger_YearMonth_UIType::normalize($input));
    }

    public function testIntegerInputIsAccepted(): void
    {
        $this->assertSame('2026-10-01', Vtiger_YearMonth_UIType::normalize(202610));
    }

    /** 表示の組み立て: {Y} 年、{M} 月（そのまま）、{MM} 月（2桁） */
    public function testFormatDisplay(): void
    {
        $this->assertSame('2026年3月', Vtiger_YearMonth_UIType::formatDisplay('2026-03-01', '{Y}年{M}月'));
        $this->assertSame('2026/03', Vtiger_YearMonth_UIType::formatDisplay('2026-03-01', '{Y}/{MM}'));
        $this->assertSame('', Vtiger_YearMonth_UIType::formatDisplay('', '{Y}年{M}月'));
        $this->assertSame('', Vtiger_YearMonth_UIType::formatDisplay('abc', '{Y}年{M}月'));
    }
}
