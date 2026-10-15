<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

namespace Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';

/**
 * Mcp_CrmTools::assertCrmId() を DB なしでテストする。
 *
 * 真偽値、小数、数字以外を含む文字列を拒否し、
 * 正の整数と数字のみの文字列を受理することを確認する。
 */
class CrmToolsIdGuardTest extends TestCase
{
    private \Mcp_CrmTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        // Users は ID 検証に使用しないため、コンストラクタを呼び出さずに生成する
        $this->tools = (new ReflectionClass(\Mcp_CrmTools::class))->newInstanceWithoutConstructor();
    }

    /** @param mixed $value */
    private function assertCrmId($value): int
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'assertCrmId');
        $m->setAccessible(true);
        return $m->invoke($this->tools, $value);
    }

    public function test_正の整数は通る(): void
    {
        $this->assertSame(12, $this->assertCrmId(12));
    }

    public function test_数字のみの文字列は通る(): void
    {
        $this->assertSame(12, $this->assertCrmId('12'));
    }

    public function test_真偽値trueは拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId(true);
    }

    public function test_真偽値falseは拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId(false);
    }

    public function test_数字混じり文字列は拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId('12abc');
    }

    public function test_数字でない文字列は拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId('abc');
    }

    public function test_負の数の文字列は拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId('-5');
    }

    public function test_ゼロの文字列は拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId('0');
    }

    public function test_ゼロの整数は拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId(0);
    }

    public function test_小数は拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId(12.5);
    }

    public function test_nullは拒否する(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertCrmId(null);
    }
}
