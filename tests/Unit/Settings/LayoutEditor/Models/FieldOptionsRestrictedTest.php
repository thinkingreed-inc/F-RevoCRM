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

namespace Tests\Unit\Settings\LayoutEditor\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Settings_LayoutEditor_Field_Model;

$root = dirname(__DIR__, 5);
require_once $root . '/includes/runtime/BaseModel.php';
require_once $root . '/vtlib/Vtiger/Field.php';
require_once $root . '/modules/Vtiger/models/Field.php';
require_once $root . '/modules/Settings/LayoutEditor/models/Field.php';

/**
 * レイアウトエディタで設定変更を禁止する項目の判定 — #1854
 *
 * 対象: Settings_LayoutEditor_Field_Model::isOptionsRestrictedField()
 *
 *   1  isconvertedfromlead / isconvertedfrompotential はモジュールを問わず禁止する（従来どおり）
 *   2  converted はリードの項目のときだけ禁止する
 *   3  リード以外のモジュールにある converted という名前の項目は禁止しない
 *   4  モジュールが取れない converted は禁止しない
 *   5  それ以外の項目は禁止しない
 */
final class FieldOptionsRestrictedTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function restrictedFields(): array
    {
        return [
            'リードの converted' => ['converted', 'Leads'],
            'リードの isconvertedfromlead' => ['isconvertedfromlead', 'Leads'],
            '顧客担当者の isconvertedfromlead' => ['isconvertedfromlead', 'Contacts'],
            '顧客企業の isconvertedfromlead' => ['isconvertedfromlead', 'Accounts'],
            '案件の isconvertedfromlead' => ['isconvertedfromlead', 'Potentials'],
            'プロジェクトの isconvertedfrompotential' => ['isconvertedfrompotential', 'Project'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unrestrictedFields(): array
    {
        return [
            '顧客担当者の converted' => ['converted', 'Contacts'],
            'カスタムモジュールの converted' => ['converted', 'CustomModule'],
            'リードの lastname' => ['lastname', 'Leads'],
            'リードの converted を含む別名' => ['cf_converted', 'Leads'],
        ];
    }

    #[DataProvider('restrictedFields')]
    public function testRestrictedField(string $fieldName, string $moduleName): void
    {
        $this->assertTrue($this->createField($fieldName, $moduleName)->isOptionsRestrictedField());
    }

    #[DataProvider('unrestrictedFields')]
    public function testUnrestrictedField(string $fieldName, string $moduleName): void
    {
        $this->assertFalse($this->createField($fieldName, $moduleName)->isOptionsRestrictedField());
    }

    public function testConvertedWithoutModuleIsNotRestricted(): void
    {
        $field = new Settings_LayoutEditor_Field_Model();
        $field->name = 'converted';
        // ブロックもモジュールも持たない項目では getModule() が false を返す
        $field->block = new \stdClass();
        $field->block->module = null;

        $this->assertFalse($field->isOptionsRestrictedField());
    }

    private function createField(string $fieldName, string $moduleName): Settings_LayoutEditor_Field_Model
    {
        $field = new Settings_LayoutEditor_Field_Model();
        $field->name = $fieldName;
        $field->setModule(new class ($moduleName) {
            public function __construct(private string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }
        });

        return $field;
    }
}
