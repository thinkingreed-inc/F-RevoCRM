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

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/RateLimiter.php';

/**
 * Mcp_RateLimiter::parseLimitValue() のシステム変数の解釈をテストする。
 *
 * mcp.php・register.php・MCPCleanup.service の 3 箇所が同じ解釈を使う。
 *
 * 小数を整数へ切り捨てると 0.5 で全拒否、1.5 で上限 1 になるため、
 * 1 以上の整数以外は既定値になることを固定する。
 */
class RateLimiterParseLimitValueTest extends TestCase
{
    private const DEFAULT_VALUE = 20;

    public function test_positive_integer_string_is_used(): void
    {
        $this->assertSame(30, \Mcp_RateLimiter::parseLimitValue('30', self::DEFAULT_VALUE));
    }

    public function test_one_is_used(): void
    {
        $this->assertSame(1, \Mcp_RateLimiter::parseLimitValue('1', self::DEFAULT_VALUE));
    }

    /**
     * @dataProvider invalidValues
     * @param mixed $value
     */
    public function test_invalid_value_falls_back_to_default($value): void
    {
        $this->assertSame(self::DEFAULT_VALUE, \Mcp_RateLimiter::parseLimitValue($value, self::DEFAULT_VALUE));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidValues(): array
    {
        return [
            'less than one decimal' => ['0.5'],
            'decimal above one'     => ['1.5'],
            'exponent notation'     => ['1e2'],
            'zero'                  => ['0'],
            'negative'              => ['-1'],
            'non numeric'           => ['abc'],
            'trailing letters'      => ['5abc'],
            'empty string'          => [''],
            'null'                  => [null],
        ];
    }
}
