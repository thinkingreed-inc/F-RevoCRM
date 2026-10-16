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

namespace Tests\Unit\Vtiger\Models;

use PHPUnit\Framework\TestCase;
use Vtiger_Field_Model;

$fieldDefaultValueTestRoot = dirname(__DIR__, 4);
require_once $fieldDefaultValueTestRoot . '/includes/runtime/BaseModel.php';
require_once $fieldDefaultValueTestRoot . '/modules/Vtiger/models/Field.php';

/**
 * getFieldDataType() の戻り値を差し替え、呼ばれた回数を記録する。
 *
 * 本物の getFieldDataType() はその先でログイン中のユーザーの権限を参照するため、
 * ログインしていない状態(Webフォームの受信など)で呼ぶと Fatal error になる。
 */
class DataTypeRecordingFieldModel extends Vtiger_Field_Model
{
    public int $dataTypeCalls = 0;

    public function __construct(private string $dataType)
    {
    }

    public function getFieldDataType()
    {
        $this->dataTypeCalls++;

        return $this->dataType;
    }
}

/**
 * Vtiger_Field_Model::getDefaultFieldValue() のテスト。
 *
 * #1726 で日付項目の TODAY に対応した際、デフォルト値に関係なく
 * getFieldDataType() を呼ぶようになり、Webフォームの受信が Fatal error になった (#1917)。
 */
final class FieldDefaultValueTest extends TestCase
{
    /** TODAY 以外のデフォルト値では、項目の種類を調べずにそのまま返す */
    public function testDoesNotCheckDataTypeUnlessToday(): void
    {
        $fieldModel = new DataTypeRecordingFieldModel('date');
        $fieldModel->defaultvalue = 'default';

        $this->assertSame('default', $fieldModel->getDefaultFieldValue());
        $this->assertSame(0, $fieldModel->dataTypeCalls, 'TODAY 以外で getFieldDataType() が呼ばれた');
    }

    /** デフォルト値が空でも、項目の種類を調べない */
    public function testDoesNotCheckDataTypeWhenEmpty(): void
    {
        $fieldModel = new DataTypeRecordingFieldModel('date');
        $fieldModel->defaultvalue = '';

        $this->assertSame('', $fieldModel->getDefaultFieldValue());
        $this->assertSame(0, $fieldModel->dataTypeCalls, '空のデフォルト値で getFieldDataType() が呼ばれた');
    }

    /** 日付項目の TODAY は今日の日付になる */
    public function testReturnsTodayForDateField(): void
    {
        $fieldModel = new DataTypeRecordingFieldModel('date');
        $fieldModel->defaultvalue = 'TODAY';

        $this->assertSame(date('Y-m-d'), $fieldModel->getDefaultFieldValue());
    }

    /** 日付以外の項目の TODAY は文字列のまま返す */
    public function testReturnsTodayAsIsForNonDateField(): void
    {
        $fieldModel = new DataTypeRecordingFieldModel('string');
        $fieldModel->defaultvalue = 'TODAY';

        $this->assertSame('TODAY', $fieldModel->getDefaultFieldValue());
    }
}
