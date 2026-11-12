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
use Vtiger_AutoNumberLegacy;

require_once dirname(__DIR__, 4) . '/modules/Vtiger/utils/AutoNumberLegacy.php';

/**
 * 連番項目（uitype 4）の設定を自動採番の設定に読み替える処理（Vtiger_AutoNumberLegacy）のテスト。DB は使わない。
 */
final class AutoNumberLegacyTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string, 2: string, 3: int, 4: int}> */
    public static function convertProvider(): array
    {
        return [
            '接頭辞と 1' => ['POT', '1', 'POT{0}', 1, 0],
            '途中の番号から' => ['PO', '25', 'PO{0}', 25, 24],
            '先頭 0 は桁をそろえる' => ['INV', '0001', 'INV{0000}', 1, 0],
            '先頭 0 の途中' => ['INV', '0042', 'INV{0000}', 42, 41],
            '接頭辞なし' => ['', '1', '{0}', 1, 0],
            '日本語の接頭辞' => ['見積-', '10', '見積-{0}', 10, 9],
            '番号が 0 でも最初は 1' => ['A', '0', 'A{0}', 1, 0],
            '数字でない番号は 1 から' => ['A', 'x', 'A{0}', 1, 0],
        ];
    }

    #[DataProvider('convertProvider')]
    public function test_設定を読み替える(string $prefix, string $curId, string $format, int $start, int $current): void
    {
        $result = Vtiger_AutoNumberLegacy::convert($prefix, $curId);
        $this->assertSame($format, $result['format']);
        $this->assertSame($start, $result['start_number']);
        $this->assertSame($current, $result['cur_value']);
        $this->assertSame([], $result['warnings']);
        $this->assertSame([], Vtiger_AutoNumberFormat::validate($result['format'], 'none'), '読み替えた書式は有効');
    }

    public function test_読み替えた書式は古い連番と同じ番号を作る(): void
    {
        $date = new DateTimeImmutable('2026-10-28');
        $result = Vtiger_AutoNumberLegacy::convert('POT', '1');
        $this->assertSame('POT1', Vtiger_AutoNumberFormat::render($result['format'], $date, $result['cur_value'] + 1));
        $this->assertSame('POT10', Vtiger_AutoNumberFormat::render($result['format'], $date, 10), '桁があふれたら桁が増える');

        $padded = Vtiger_AutoNumberLegacy::convert('INV', '0999');
        $this->assertSame('INV0999', Vtiger_AutoNumberFormat::render($padded['format'], $date, $padded['cur_value'] + 1));
        $this->assertSame('INV1000', Vtiger_AutoNumberFormat::render($padded['format'], $date, $padded['cur_value'] + 2), '桁があふれたら次の桁へ');
    }

    public function test_使えない文字は接頭辞から取り除いて知らせる(): void
    {
        $result = Vtiger_AutoNumberLegacy::convert('A{B}&C', '1');
        $this->assertSame('ABC{0}', $result['format']);
        $this->assertSame(['PREFIX_CHANGED'], $result['warnings']);
        $this->assertSame([], Vtiger_AutoNumberFormat::validate($result['format'], 'none'));
    }

    public function test_長すぎる接頭辞は書式の上限に収める(): void
    {
        $result = Vtiger_AutoNumberLegacy::convert(str_repeat('A', 150), '1');
        $this->assertContains('PREFIX_TRUNCATED', $result['warnings']);
        $this->assertLessThanOrEqual(Vtiger_AutoNumberFormat::MAX_LENGTH, mb_strlen($result['format']));
        $this->assertSame([], Vtiger_AutoNumberFormat::validate($result['format'], 'none'));
    }

    public function test_桁数は上限を超えない(): void
    {
        $this->assertSame(Vtiger_AutoNumberFormat::MAX_SEQ_DIGITS, Vtiger_AutoNumberLegacy::seqDigits('00000000000000'));
        $this->assertSame(1, Vtiger_AutoNumberLegacy::seqDigits('123'));
    }
}
