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
use Vtiger_Base_UIType;

$uiTypeClassResolutionTestRoot = dirname(__DIR__, 4);
require_once $uiTypeClassResolutionTestRoot . '/includes/runtime/BaseModel.php';
require_once $uiTypeClassResolutionTestRoot . '/modules/Vtiger/uitypes/Base.php';

/**
 * UIType クラス名の解決 (Vtiger_Base_UIType::resolveClassSuffix) のテスト。
 *
 * uitype 専用クラスの対応表を持たせても、対応表に無い既存の uitype は
 * 従来どおり「データ型名の先頭を大文字にしたもの」で解決されることを固定する。
 */
final class UiTypeClassResolutionTest extends TestCase
{
    /** 対応表に無い uitype は従来どおりデータ型名から解決する */
    public function testUnmappedUiTypeFallsBackToDataTypeName(): void
    {
        $cases = [
            ['1', 'string', 'String'],
            ['5', 'date', 'Date'],
            ['15', 'picklist', 'Picklist'],
            ['33', 'multipicklist', 'Multipicklist'],
            ['56', 'boolean', 'Boolean'],
            ['69', 'image', 'Image'],
            ['998', 'fileupload', 'Fileupload'],
            ['999', 'blank', 'Blank'],
        ];

        foreach ($cases as [$uitype, $dataType, $expected]) {
            $this->assertSame(
                $expected,
                Vtiger_Base_UIType::resolveClassSuffix($uitype, $dataType),
                "uitype={$uitype} dataType={$dataType}"
            );
        }
    }

    /** 対応表にある uitype は、データ型名に関係なく専用クラスで解決する */
    public function testMappedUiTypeResolvesToDedicatedClassRegardlessOfDataType(): void
    {
        $this->assertSame('YearMonth', Vtiger_Base_UIType::resolveClassSuffix('996', 'date'));
        $this->assertSame('AutoNumber', Vtiger_Base_UIType::resolveClassSuffix('997', 'string'));
        // データ型名が何であっても専用クラスが優先される
        $this->assertSame('YearMonth', Vtiger_Base_UIType::resolveClassSuffix('996', 'string'));
    }

    /** uitype は DB から文字列で来ることも整数で来ることもある */
    public function testAcceptsIntegerAndStringUiType(): void
    {
        $this->assertSame('YearMonth', Vtiger_Base_UIType::resolveClassSuffix(996, 'date'));
        $this->assertSame('Date', Vtiger_Base_UIType::resolveClassSuffix(5, 'date'));
    }

    /** uitype が未設定（null・空）でもデータ型名で解決でき、例外にならない */
    public function testMissingUiTypeFallsBackToDataTypeName(): void
    {
        $this->assertSame('String', Vtiger_Base_UIType::resolveClassSuffix(null, 'string'));
        $this->assertSame('String', Vtiger_Base_UIType::resolveClassSuffix('', 'string'));
    }

    /** 対応表のキーは、既存の使用済み番号と重ならない（PHP は数字の文字列キーを整数に変換する） */
    public function testMapDoesNotCollideWithKnownUiTypes(): void
    {
        $knownUiTypes = [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28,
            30, 31, 32, 33, 51, 52, 53, 54, 55, 56, 57, 58, 59, 61, 63, 66, 69, 70, 71, 72, 73,
            75, 76, 77, 78, 80, 81, 83, 85, 98, 99, 101, 104, 105, 106, 115, 116, 117, 156, 255, 357, 401,
            998, 999,
        ];

        foreach (array_keys(Vtiger_Base_UIType::UITYPE_CLASS_MAP) as $uitype) {
            $this->assertNotContains((int) $uitype, $knownUiTypes, "uitype {$uitype} は既に使われている");
        }
    }
}
