<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';

/**
 * Mcp_CrmTools の値検証を DB なしでテストする。
 *
 * validateFieldValue / assertDateTimeFormat は型定義と値のみで検証できるため、
 * コンストラクタ（Users が必要）を呼び出さずにインスタンスを生成して直接テストする。
 * MCP 経由で不正な値が保存されないよう、型ごとの受理・拒否条件を固定する。
 */
class CrmToolsFieldValidationTest extends TestCase
{
    private \Mcp_CrmTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        // Users は値検証に使用しないため、コンストラクタを呼び出さずに生成する
        $this->tools = (new ReflectionClass(\Mcp_CrmTools::class))->newInstanceWithoutConstructor();
    }

    /** @param mixed $value */
    private function validate(string $name, $value, array $meta): void
    {
        $m = new \ReflectionMethod(\Mcp_CrmTools::class, 'validateFieldValue');
        $m->setAccessible(true);
        $m->invoke($this->tools, $name, $value, $meta);
    }

    private static function meta(string $type, array $extra = []): array
    {
        return array_merge(['type' => ['name' => $type]], $extra);
    }

    /** 例外が発生せず検証を通過することを確認する */
    private function assertAccepted(string $name, $value, array $meta): void
    {
        $this->validate($name, $value, $meta);
        $this->addToAssertionCount(1);
    }

    private function assertRejected(string $name, $value, array $meta): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validate($name, $value, $meta);
    }

    // ---------------------------------------------------------------- date

    public function test_date_accepts_iso_date(): void
    {
        $this->assertAccepted('birthday', '2026-09-10', self::meta('date'));
    }

    public function test_date_rejects_overflowing_day(): void
    {
        // DateTime::createFromFormat は 2026-02-31 を 2026-03-03 に補正するため、
        // 往復比較によって不正な日付を拒否することを確認する
        $this->assertRejected('birthday', '2026-02-31', self::meta('date'));
    }

    public function test_date_rejects_mysql_zero_date(): void
    {
        $this->assertRejected('birthday', '0000-00-00', self::meta('date'));
    }

    public function test_date_rejects_month_without_leading_zero(): void
    {
        // createFromFormat は '2026-9-10' も警告なしで受理するため、
        // 往復比較によって YYYY-MM-DD 形式以外を拒否することを確認する
        $this->assertRejected('birthday', '2026-9-10', self::meta('date'));
    }

    public function test_date_rejects_slash_separated_value(): void
    {
        $this->assertRejected('birthday', '2026/09/10', self::meta('date'));
    }

    public function test_date_rejects_datetime_value(): void
    {
        $this->assertRejected('birthday', '2026-09-10 12:00:00', self::meta('date'));
    }

    // ---------------------------------------------------------------- datetime

    public function test_datetime_accepts_full_timestamp(): void
    {
        $this->assertAccepted('createdtime', '2026-09-10 23:59:59', self::meta('datetime'));
    }

    public function test_datetime_rejects_date_only(): void
    {
        $this->assertRejected('createdtime', '2026-09-10', self::meta('datetime'));
    }

    public function test_datetime_rejects_invalid_hour(): void
    {
        $this->assertRejected('createdtime', '2026-09-10 24:00:00', self::meta('datetime'));
    }

    public function test_datetime_rejects_hour_without_leading_zero(): void
    {
        // '2026-09-10 9:05:00' を拒否し、YYYY-MM-DD HH:MM:SS 形式を厳密に検証する
        $this->assertRejected('createdtime', '2026-09-10 9:05:00', self::meta('datetime'));
    }

    // ---------------------------------------------------------------- time

    public function test_time_accepts_hhmm_and_hhmmss(): void
    {
        $this->assertAccepted('time_start', '09:30', self::meta('time'));
        $this->assertAccepted('time_start', '09:30:15', self::meta('time'));
    }

    public function test_time_rejects_out_of_range_hour(): void
    {
        $this->assertRejected('time_start', '24:00', self::meta('time'));
    }

    public function test_time_rejects_out_of_range_minute(): void
    {
        $this->assertRejected('time_start', '09:60', self::meta('time'));
    }

    // ---------------------------------------------------------------- integer

    public function test_integer_accepts_numeric_string_and_int(): void
    {
        $this->assertAccepted('employees', '120', self::meta('integer'));
        $this->assertAccepted('employees', 120, self::meta('integer'));
    }

    public function test_integer_rejects_decimal(): void
    {
        $this->assertRejected('employees', '12.5', self::meta('integer'));
    }

    public function test_integer_rejects_boolean(): void
    {
        // true が整数値として扱われないことを確認する
        $this->assertRejected('employees', true, self::meta('integer'));
    }

    public function test_integer_rejects_non_numeric_string(): void
    {
        $this->assertRejected('employees', '12abc', self::meta('integer'));
    }

    // ---------------------------------------------------------------- double / boolean / email

    public function test_double_accepts_decimal_but_rejects_text(): void
    {
        $this->assertAccepted('probability', '12.5', self::meta('double'));
        $this->assertRejected('probability', 'high', self::meta('double'));
    }

    public function test_boolean_accepts_zero_one_and_php_bool(): void
    {
        $this->assertAccepted('is_active', '0', self::meta('boolean'));
        $this->assertAccepted('is_active', '1', self::meta('boolean'));
        $this->assertAccepted('is_active', false, self::meta('boolean'));
    }

    public function test_boolean_rejects_yes(): void
    {
        $this->assertRejected('is_active', 'yes', self::meta('boolean'));
    }

    public function test_email_rejects_malformed_address(): void
    {
        $this->assertRejected('email1', 'not-an-email', self::meta('email'));
    }

    // ---------------------------------------------------------------- picklist

    private static function picklistMeta(array $values): array
    {
        return ['type' => ['name' => 'picklist',
            'picklistValues' => array_map(static fn ($v) => ['value' => $v], $values)]];
    }

    public function test_picklist_accepts_declared_option(): void
    {
        $this->assertAccepted('leadstatus', 'Cold', self::picklistMeta(['Cold', 'Hot']));
    }

    public function test_picklist_rejects_undeclared_option(): void
    {
        $this->assertRejected('leadstatus', 'Lukewarm', self::picklistMeta(['Cold', 'Hot']));
    }

    public function test_picklist_without_options_is_not_checked(): void
    {
        // 選択肢を取得できない項目では値を拒否しない
        $this->assertAccepted('leadstatus', 'anything', self::picklistMeta([]));
    }

    public function test_multipicklist_rejects_when_one_part_is_undeclared(): void
    {
        $meta = ['type' => ['name' => 'multipicklist',
            'picklistValues' => [['value' => 'A'], ['value' => 'B']]]];
        $this->assertRejected('tags', 'A |##| Z', $meta);
    }

    // ---------------------------------------------------------------- null / array

    public function test_array_is_rejected_for_any_type(): void
    {
        // 配列は保存対象として扱えないため拒否する
        $this->assertRejected('accountname', ['a', 'b'], self::meta('string'));
    }

    public function test_null_is_rejected_when_field_is_not_nullable(): void
    {
        $this->assertRejected('accountname', null, self::meta('string', ['nullable' => false]));
    }

    public function test_null_is_accepted_when_field_is_nullable(): void
    {
        $this->assertAccepted('description', null, self::meta('string', ['nullable' => true]));
    }

    // ---------------------------------------------------------------- 未知の型

    public function test_unknown_type_is_passed_through(): void
    {
        // 未知の型は既存の書き込み処理との互換性を保つため拒否しない
        $this->assertAccepted('somefield', 'any value', self::meta('some_future_type'));
    }
}
