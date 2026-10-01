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
use Vtiger_Base_UIType;
use Vtiger_Field_Model;

$fieldDisplayValueTestRoot = dirname(__DIR__, 4);
require_once $fieldDisplayValueTestRoot . '/includes/runtime/BaseModel.php';
require_once $fieldDisplayValueTestRoot . '/modules/Vtiger/models/Field.php';
require_once $fieldDisplayValueTestRoot . '/modules/Vtiger/uitypes/Base.php';
require_once $fieldDisplayValueTestRoot . '/modules/Vtiger/uitypes/Text.php';
require_once $fieldDisplayValueTestRoot . '/modules/Vtiger/uitypes/String.php';

/**
 * uitype_instance の差し替え用。vtiger は動的プロパティへ代入するため、
 * PHP 8.2 以降の Deprecated を出さないようこのテスト専用に許可する。
 */
#[\AllowDynamicProperties]
class DynamicPropertyFieldModel extends Vtiger_Field_Model
{
    /** @var Vtiger_Base_UIType|null 差し替えた UIType */
    public $uitype_instance;
}

/**
 * 受け取った引数を記録するだけの UIType スタブ。
 */
class RecordingUIType extends Vtiger_Base_UIType
{
    /** @var array<int, mixed> */
    public array $received = [];

    /**
     * @param  mixed $value
     * @param  mixed $record
     * @param  mixed $recordInstance
     * @param  bool  $removeTags
     * @return mixed
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false, $removeTags = false)
    {
        $this->received = func_get_args();

        return $value;
    }
}

/**
 * メール差込 (include/utils/EmailTemplate.php) が渡す $removeTags が
 * UIType まで届くことのテスト。
 *
 * 届かないと Vtiger_Text_UIType 側でリンク化を抑止できず、テキストメールの
 * 本文に <a> タグがそのまま入る (#1773 のレビュー指摘 B-1)。
 */
final class FieldDisplayValueRemoveTagsTest extends TestCase
{
    /** Vtiger_Field_Model が第4引数 $removeTags を UIType へ委譲する */
    public function testDelegatesRemoveTagsToUiType(): void
    {
        $fieldModel = new DynamicPropertyFieldModel();
        $uiType = new RecordingUIType();
        $fieldModel->uitype_instance = $uiType;

        $fieldModel->getDisplayValue('value', 10, false, true);

        $this->assertCount(4, $uiType->received, '$removeTags が UIType へ渡っていない');
        $this->assertTrue($uiType->received[3]);
    }

    /** $removeTags を省略した場合は既定の false として扱う */
    public function testDefaultsRemoveTagsToFalse(): void
    {
        $fieldModel = new DynamicPropertyFieldModel();
        $uiType = new RecordingUIType();
        $fieldModel->uitype_instance = $uiType;

        $fieldModel->getDisplayValue('value', 10, false);

        $this->assertFalse($uiType->received[3] ?? false);
    }

    /** $removeTags が真のときテキスト項目はリンク化しない */
    public function testTextUiTypeSkipsLinkifyWhenRemoveTagsIsTrue(): void
    {
        $fieldModel = new DynamicPropertyFieldModel();
        $fieldModel->set('fieldname', 'description');
        $uiType = new \Vtiger_Text_UIType();
        $uiType->set('field', $fieldModel);
        $fieldModel->uitype_instance = $uiType;

        $displayValue = $fieldModel->getDisplayValue('see https://example.com/a', 10, false, true);

        $this->assertStringNotContainsString('<a ', (string) $displayValue);
    }

    /** $removeTags が真のとき単数行テキスト項目もリンク化しない */
    public function testStringUiTypeSkipsLinkifyWhenRemoveTagsIsTrue(): void
    {
        $fieldModel = new DynamicPropertyFieldModel();
        $uiType = new \Vtiger_String_UIType();
        $fieldModel->uitype_instance = $uiType;

        $displayValue = $fieldModel->getDisplayValue('see https://example.com/a', 10, false, true);

        $this->assertStringNotContainsString('<a ', (string) $displayValue);
    }

    /** $removeTags が偽のときは単数行テキスト項目をリンク化する */
    public function testStringUiTypeLinkifiesWhenRemoveTagsIsFalse(): void
    {
        $fieldModel = new DynamicPropertyFieldModel();
        $uiType = new \Vtiger_String_UIType();
        $fieldModel->uitype_instance = $uiType;

        $displayValue = $fieldModel->getDisplayValue('see https://example.com/a', 10, false);

        $this->assertStringContainsString('<a ', (string) $displayValue);
    }

    /**
     * Vtiger_Field_Model を継承するモジュール側 Field Model は、第4引数まで含めた
     * 互換シグネチャで getDisplayValue() を宣言する。
     *
     * 第4引数が欠けていると PHP 8 がクラス宣言の時点で Fatal error を出し、
     * その Field Model を使う画面がまったく描画されなくなる。
     * クラス宣言エラーは catch できないため、別プロセスで読み込んで終了コードを見る。
     *
     * @dataProvider fieldModelFileProvider
     */
    public function testSubclassFieldModelIsLoadable(string $relativePath): void
    {
        $root = dirname(__DIR__, 4);
        $code = sprintf(
            'require %s; require %s; require %s;',
            var_export($root . '/includes/runtime/BaseModel.php', true),
            var_export($root . '/modules/Vtiger/models/Field.php', true),
            var_export($root . '/' . $relativePath, true)
        );

        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1', $output, $status);

        $this->assertSame(0, $status, $relativePath . ' の読み込みに失敗: ' . implode("\n", $output));
    }

    /** @return array<string, array{string}> */
    public static function fieldModelFileProvider(): array
    {
        return [
            'Calendar' => ['modules/Calendar/models/Field.php'],
            'Documents' => ['modules/Documents/models/Field.php'],
            'Faq' => ['modules/Faq/models/Field.php'],
            'HelpDesk' => ['modules/HelpDesk/models/Field.php'],
            'Users' => ['modules/Users/models/Field.php'],
            'Settings:Webforms' => ['modules/Settings/Webforms/models/Field.php'],
        ];
    }
}
