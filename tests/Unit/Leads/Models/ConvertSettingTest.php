<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Leads\Models;

use Leads_ConvertSetting_Model;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/modules/Leads/models/ConvertSetting.php';

/**
 * パラメーターの取得だけを差し替えたテスト用サブクラス。
 * DB (Settings_Parameters_Record_Model) に触れずに判定ロジックを検証する。
 */
final class ConvertSettingForTest extends Leads_ConvertSetting_Model
{
    /** @var array<string, mixed> key => value のマップ */
    public static array $values = [];

    protected static function getParameterValue(string $key): mixed
    {
        return self::$values[$key] ?? 'false';
    }
}

/**
 * 昇格済みリードの表示／再昇格を制御するパラメーターの読み取りを検証する。
 */
final class ConvertSettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ConvertSettingForTest::$values = [];
    }

    protected function tearDown(): void
    {
        ConvertSettingForTest::$values = [];
        parent::tearDown();
    }

    public function test_パラメーター未登録なら昇格済みリードは表示も再昇格もしない(): void
    {
        $this->assertFalse(ConvertSettingForTest::showConvertedLeads());
        $this->assertFalse(ConvertSettingForTest::allowReconvert());
    }

    public function test_trueなら昇格済みリードを表示する(): void
    {
        ConvertSettingForTest::$values = [
            Leads_ConvertSetting_Model::SHOW_CONVERTED_LEADS => 'true',
        ];

        $this->assertTrue(ConvertSettingForTest::showConvertedLeads());
        // もう一方のパラメーターは独立していること
        $this->assertFalse(ConvertSettingForTest::allowReconvert());
    }

    public function test_trueなら再昇格を許可する(): void
    {
        ConvertSettingForTest::$values = [
            Leads_ConvertSetting_Model::ALLOW_RECONVERT_LEAD => 'true',
        ];

        $this->assertTrue(ConvertSettingForTest::allowReconvert());
        $this->assertFalse(ConvertSettingForTest::showConvertedLeads());
    }

    /**
     * @dataProvider 有効とみなす値
     */
    public function test_大文字小文字や前後の空白を無視して有効と判定する(string $value): void
    {
        ConvertSettingForTest::$values = [
            Leads_ConvertSetting_Model::SHOW_CONVERTED_LEADS => $value,
        ];

        $this->assertTrue(ConvertSettingForTest::showConvertedLeads());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function 有効とみなす値(): array
    {
        return [
            'true'    => ['true'],
            'TRUE'    => ['TRUE'],
            'True'    => ['True'],
            '前後に空白' => ["  true \n"],
        ];
    }

    /**
     * @dataProvider 無効とみなす値
     */
    public function test_true以外はすべて無効と判定する(mixed $value): void
    {
        ConvertSettingForTest::$values = [
            Leads_ConvertSetting_Model::SHOW_CONVERTED_LEADS => $value,
        ];

        $this->assertFalse(ConvertSettingForTest::showConvertedLeads());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function 無効とみなす値(): array
    {
        return [
            'false'   => ['false'],
            '空文字'    => [''],
            '数値の1'   => ['1'],
            'yes'     => ['yes'],
            '文字列以外' => [true],
            'null'    => [null],
        ];
    }

    public function test_表示しない設定なら絞り込み条件を返す(): void
    {
        $this->assertSame(
            ' AND vtiger_leaddetails.converted = 0 ',
            ConvertSettingForTest::getConvertedFilterCondition()
        );
    }

    public function test_接続詞を指定できる(): void
    {
        $this->assertSame(
            ' and vtiger_leaddetails.converted = 0 ',
            ConvertSettingForTest::getConvertedFilterCondition('and')
        );
        $this->assertSame(
            'vtiger_leaddetails.converted = 0',
            ConvertSettingForTest::getConvertedFilterCondition('')
        );
    }

    public function test_表示する設定なら絞り込み条件は空になる(): void
    {
        ConvertSettingForTest::$values = [
            Leads_ConvertSetting_Model::SHOW_CONVERTED_LEADS => 'true',
        ];

        $this->assertSame('', ConvertSettingForTest::getConvertedFilterCondition());
        $this->assertSame('', ConvertSettingForTest::getConvertedFilterCondition('and'));
        $this->assertSame('', ConvertSettingForTest::getConvertedFilterCondition(''));
    }
}
