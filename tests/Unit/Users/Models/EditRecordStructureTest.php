<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Users\Models;

use PHPUnit\Framework\TestCase;
use Users_EditRecordStructure_Model;

require_once dirname(__DIR__, 4) . '/includes/runtime/BaseModel.php';
require_once dirname(__DIR__, 4) . '/modules/Vtiger/models/RecordStructure.php';
require_once dirname(__DIR__, 4) . '/modules/Vtiger/models/EditRecordStructure.php';
require_once dirname(__DIR__, 4) . '/modules/Users/models/EditRecordStructure.php';

class EditRecordStructureTest extends TestCase
{
    /**
     * layouts/v7/modules/Vtiger/uitypes/Boolean.tpl の checked 判定を再現する。
     * コンパイル結果:
     *   $fieldvalue == true && $fieldvalue != 'no' && $fieldvalue != vtranslate('LBL_NO')
     */
    private static function isCheckedInTemplate(mixed $fieldValue): bool
    {
        return $fieldValue == true && $fieldValue != 'no' && $fieldValue != 'いいえ';
    }

    /**
     * Users_EditRecordStructure_Model::getStructure() の
     * 「$recordModel->get($fieldName) != '' なら fieldvalue にセット」判定を再現する。
     */
    private static function isAssignedAsFieldValue(mixed $fieldValue): bool
    {
        return $fieldValue != '';
    }

    public function testAdminOnIsRenderedAsChecked(): void
    {
        $value = Users_EditRecordStructure_Model::normalizeAdminFieldValue('on');

        $this->assertTrue(self::isAssignedAsFieldValue($value), 'is_admin=on は fieldvalue にセットされる必要がある');
        $this->assertTrue(self::isCheckedInTemplate($value), 'is_admin=on はチェック済みで表示される必要がある');
    }

    public function testAdminOffIsRenderedAsUnchecked(): void
    {
        $value = Users_EditRecordStructure_Model::normalizeAdminFieldValue('off');

        $this->assertFalse(self::isAssignedAsFieldValue($value), 'is_admin=off は fieldvalue にセットされない');
        $this->assertFalse(self::isCheckedInTemplate($value), 'is_admin=off は非チェックで表示される必要がある');
    }

    public function testEmptyAdminValueIsRenderedAsUnchecked(): void
    {
        foreach (['', null] as $rawValue) {
            $value = Users_EditRecordStructure_Model::normalizeAdminFieldValue($rawValue);
            $this->assertFalse(self::isCheckedInTemplate($value));
        }
    }

    /**
     * 同一レコードモデルに対して正規化が繰り返し適用されても結果が変わらないこと。
     * (編集画面では getStructure() が複数回呼ばれる経路があるため)
     */
    public function testNormalizationIsIdempotent(): void
    {
        $first = Users_EditRecordStructure_Model::normalizeAdminFieldValue('on');
        $second = Users_EditRecordStructure_Model::normalizeAdminFieldValue($first);

        $this->assertSame($first, $second);
        $this->assertTrue(self::isCheckedInTemplate($second));
    }
}
