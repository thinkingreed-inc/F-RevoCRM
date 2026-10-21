<?php

declare(strict_types=1);

namespace Tests\Unit\Leads;

use Leads_ConvertLeadValidator_Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/modules/Leads/models/ConvertLeadValidator.php';

/**
 * 昇格で保存する値の型の検証（サーバー側）
 */
final class ConvertLeadValidatorTest extends TestCase
{
    /**
     * @return array<string, array{string, string, mixed, ?string}>
     */
    public static function cases(): array
    {
        return [
            'メール正常' => ['email', '13', 'taro@example.co.jp', null],
            'メールのアポストロフィは許容' => ['email', '13', "o'brien@example.com", null],
            'メール不正' => ['email', '13', 'taro@', 'LBL_CONVERT_ERR_EMAIL'],
            'URL正常' => ['url', '17', 'https://example.com/a', null],
            'URL不正' => ['url', '17', 'not a url', 'LBL_CONVERT_ERR_URL'],
            '整数正常' => ['integer', '7', '-12', null],
            '整数不正' => ['integer', '7', '1.5', 'LBL_CONVERT_ERR_INTEGER'],
            '小数正常' => ['double', '7', '1.5', null],
            '通貨正常' => ['currency', '71', '1000000', null],
            '通貨不正' => ['currency', '71', '100円', 'LBL_CONVERT_ERR_NUMBER'],
            'パーセント不正' => ['percentage', '9', 'abc', 'LBL_CONVERT_ERR_NUMBER'],
            '日付正常' => ['date', '5', '2026-02-28', null],
            '日付不正' => ['date', '5', '2026-02-30', 'LBL_CONVERT_ERR_DATE'],
            '日付形式違い' => ['date', '5', '2026/02/28', 'LBL_CONVERT_ERR_DATE'],
            '時刻正常' => ['time', '14', '09:30', null],
            '時刻不正' => ['time', '14', '25:00', 'LBL_CONVERT_ERR_TIME'],
            'チェック正常' => ['boolean', '56', '1', null],
            'チェック不正' => ['boolean', '56', 'yes!', 'LBL_CONVERT_ERR_BOOLEAN'],
            '文字列は何でも可' => ['string', '1', 'なんでも', null],
            '配列は不正' => ['string', '1', ['a'], 'LBL_CONVERT_ERR_INVALID'],
        ];
    }

    #[DataProvider('cases')]
    public function test_型ごとの検証(string $dataType, string $uitype, mixed $value, ?string $expected): void
    {
        self::assertSame($expected, Leads_ConvertLeadValidator_Model::validateValue($dataType, $uitype, $value));
    }

    public function test_空の値は型を検証しない(): void
    {
        self::assertNull(Leads_ConvertLeadValidator_Model::validateValue('email', '13', ''));
        self::assertNull(Leads_ConvertLeadValidator_Model::validateValue('date', '5', null));
    }

    public function test_空白だけの値は空とみなす(): void
    {
        self::assertTrue(Leads_ConvertLeadValidator_Model::isEmptyValue('  '));
        self::assertTrue(Leads_ConvertLeadValidator_Model::isEmptyValue(null));
        self::assertFalse(Leads_ConvertLeadValidator_Model::isEmptyValue('0'));
    }

    public function test_必須のチェックボックスはオフを空とみなす(): void
    {
        self::assertTrue(Leads_ConvertLeadValidator_Model::isEmptyValue('0', 'boolean'));
        self::assertFalse(Leads_ConvertLeadValidator_Model::isEmptyValue('1', 'boolean'));
    }
}
