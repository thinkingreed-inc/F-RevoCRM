<?php

declare(strict_types=1);

namespace Tests\Unit\RecordVersion;

use PHPUnit\Framework\TestCase;
use RecordVersion_Util_Helper;

require_once dirname(__DIR__, 3) . '/modules/RecordVersion/helpers/Util.php';

/**
 * DB や JSON から読んだ値（型が決まらない値）の変換
 */
final class UtilHelperTest extends TestCase
{
    public function test_toIntは数値と数値の文字列だけを整数にする(): void
    {
        $this->assertSame(3, RecordVersion_Util_Helper::toInt(3));
        $this->assertSame(12, RecordVersion_Util_Helper::toInt('12'));
        $this->assertSame(0, RecordVersion_Util_Helper::toInt('abc'));
        $this->assertSame(0, RecordVersion_Util_Helper::toInt(null));
        $this->assertSame(0, RecordVersion_Util_Helper::toInt(['1']));
    }

    public function test_toStrはスカラーだけを文字列にする(): void
    {
        $this->assertSame('版', RecordVersion_Util_Helper::toStr('版'));
        $this->assertSame('5', RecordVersion_Util_Helper::toStr(5));
        $this->assertSame('', RecordVersion_Util_Helper::toStr(null));
        $this->assertSame('', RecordVersion_Util_Helper::toStr(['a']));
    }

    public function test_toArrayとtoListは配列でなければ空にする(): void
    {
        $this->assertSame(['1' => 'a'], RecordVersion_Util_Helper::toArray([1 => 'a']));
        $this->assertSame([], RecordVersion_Util_Helper::toArray('a'));
        $this->assertSame(['a', 'b'], RecordVersion_Util_Helper::toList([3 => 'a', 7 => 'b']));
        $this->assertSame([], RecordVersion_Util_Helper::toList(null));
    }

    public function test_isTruthyは値があるかを返す(): void
    {
        $this->assertTrue(RecordVersion_Util_Helper::isTruthy('1'));
        $this->assertTrue(RecordVersion_Util_Helper::isTruthy(true));
        $this->assertFalse(RecordVersion_Util_Helper::isTruthy(''));
        $this->assertFalse(RecordVersion_Util_Helper::isTruthy('0'));
        $this->assertFalse(RecordVersion_Util_Helper::isTruthy(null));
    }

    public function test_fetchRawRowsは結果でないものには空を返す(): void
    {
        $this->assertSame([], RecordVersion_Util_Helper::fetchRawRows(false));
    }
}
