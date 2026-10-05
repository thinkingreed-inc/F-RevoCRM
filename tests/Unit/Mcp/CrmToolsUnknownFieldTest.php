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
use ReflectionMethod;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';

/**
 * Mcp_CrmTools の未定義項目の拒否を DB なしでテストする。
 *
 * 書き込み経路は未定義の項目を黙って捨てて成功を返すため、
 * 呼び出し側が渡した項目名を crm_describe の項目名・editable と照合して拒否することを確認する。
 * describe は vtws_describe の戻り値の一部（name / editable）を模して直接渡す。
 */
class CrmToolsUnknownFieldTest extends TestCase
{
    /** Accounts の describe の一部を模した項目名 */
    private const ACCOUNT_FIELDS = ['id', 'accountname', 'phone', 'email1', 'assigned_user_id'];

    private \Mcp_CrmTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        // Users は項目名の照合に使用しないため、コンストラクタを呼び出さずに生成する
        $this->tools = (new ReflectionClass(\Mcp_CrmTools::class))->newInstanceWithoutConstructor();
    }

    /** @return mixed */
    private function invoke(string $method, array $args)
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->tools, $args);
    }

    private function assertKnown(string $module, array $fields, array $validNames, array $readOnlyNames = []): void
    {
        $this->invoke('assertKnownFields', [$module, $fields, self::describeOf($validNames, $readOnlyNames)]);
    }

    public function test_known_fields_are_accepted(): void
    {
        $this->assertKnown('Accounts', [
            'accountname'      => 'テスト用_株式会社サンプル',
            'phone'            => '000-0000-0000',
            'assigned_user_id' => 1,
        ], self::ACCOUNT_FIELDS);
        $this->addToAssertionCount(1);
    }

    public function test_single_unknown_field_is_rejected_with_its_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown field name(s) for module 'Accounts': phone_number.");
        $this->assertKnown('Accounts', [
            'accountname'  => 'テスト用_株式会社サンプル',
            'phone_number' => '000-0000-0000',
        ], self::ACCOUNT_FIELDS);
    }

    public function test_all_unknown_fields_are_reported_at_once(): void
    {
        try {
            $this->assertKnown('Accounts', [
                'phone_number' => '000-0000-0000',
                'accountname'  => 'テスト用_株式会社サンプル',
                'mail'         => 'test@example.invalid',
            ], self::ACCOUNT_FIELDS);
            $this->fail('unknown fields were accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('phone_number, mail', $e->getMessage());
            $this->assertStringContainsString('Use crm_describe', $e->getMessage());
        }
    }

    public function test_message_does_not_contain_values(): void
    {
        // ログには値を残さない方針のため、エラーにも値を含めない
        try {
            $this->assertKnown('Accounts', ['phone_number' => '000-0000-0000'], self::ACCOUNT_FIELDS);
            $this->fail('unknown field was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString('000-0000-0000', $e->getMessage());
        }
    }

    public function test_list_shaped_fields_are_rejected(): void
    {
        // 連想配列でない fields は項目名を持たないため拒否する
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(': 0.');
        $this->assertKnown('Accounts', ['テスト用_株式会社サンプル'], self::ACCOUNT_FIELDS);
    }

    // ---------------------------------------------------------------- create / update の経路

    /**
     * describeModule() を差し替えたスタブを返す。
     * 1 回目（項目名の照合）は $desc を返し、2 回目（prepareElementForWrite）で例外を投げて
     * vtws_create / vtws_revise に到達する前に止める。
     */
    private static function stubWithDescribe(array $desc): \Mcp_CrmTools
    {
        return new class ($desc) extends \Mcp_CrmTools {
            private array $desc;
            private int $calls = 0;

            public function __construct(array $desc)
            {
                $this->desc = $desc;
            }

            protected function describeModule(string $module): array
            {
                if (++$this->calls > 1) {
                    throw new \LogicException('reached prepareElementForWrite');
                }
                return $this->desc;
            }
        };
    }

    /** @param string[] $readOnlyNames describe で editable=false になる項目名 */
    private static function describeOf(array $names, array $readOnlyNames = []): array
    {
        $fields = [];
        foreach ($names as $n) {
            $fields[] = ['name' => $n, 'type' => ['name' => 'string'], 'editable' => true];
        }
        foreach ($readOnlyNames as $n) {
            $fields[] = ['name' => $n, 'type' => ['name' => 'string'], 'editable' => false];
        }
        return ['fields' => $fields];
    }

    /** @return mixed */
    private static function call(\Mcp_CrmTools $tools, string $method, array $args)
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, $method);
        $m->setAccessible(true);

        return $m->invoke($tools, $args);
    }

    public function test_create_rejects_unknown_field_before_writing(): void
    {
        $tools = self::stubWithDescribe(self::describeOf(['accountname', 'phone']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("module 'Accounts': phone_number.");
        self::call($tools, 'create', [
            'module' => 'Accounts',
            'fields' => ['accountname' => 'テスト用_株式会社サンプル', 'phone_number' => '000-0000-0000'],
        ]);
    }

    public function test_update_rejects_unknown_field_before_writing(): void
    {
        $tools = self::stubWithDescribe(self::describeOf(['accountname', 'phone']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("module 'Accounts': phone_number.");
        self::call($tools, 'update', [
            'module' => 'Accounts',
            'id'     => 1,
            'fields' => ['phone_number' => '000-0000-0000'],
        ]);
    }

    public function test_create_does_not_check_activitytype_defaulted_internally(): void
    {
        // describe に activitytype が無くても（項目権限で非表示など）、
        // 呼び出し側が渡していない補完値は照合対象にならず、書き込み準備まで進む
        $tools = self::stubWithDescribe(self::describeOf(['subject', 'assigned_user_id']));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('reached prepareElementForWrite');
        self::call($tools, 'create', [
            'module' => 'Calendar',
            'fields' => ['subject' => 'テスト用ToDo_見積書の確認'],
        ]);
    }

    public function test_create_accepts_id_listed_by_describe(): void
    {
        // getValidFieldNames() は describe の項目に id を加えるため、id は未定義扱いにならない
        $tools = self::stubWithDescribe(self::describeOf(['accountname']));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('reached prepareElementForWrite');
        self::call($tools, 'create', [
            'module' => 'Accounts',
            'fields' => ['accountname' => 'テスト用_株式会社サンプル', 'id' => 1],
        ]);
    }

    // ---------------------------------------------------------------- 編集不可の項目

    /** Accounts の describe で editable=false になる項目の一部（id は vtws_describe が editable=false で返す） */
    private const ACCOUNT_READONLY_FIELDS = ['id', 'createdtime', 'modifiedtime', 'modifiedby'];

    public function test_update_rejects_non_editable_fields_with_their_names(): void
    {
        // 保存時に CRMEntity が捨てるため、成功を返すと値が反映されたと誤解される
        $tools = self::stubWithDescribe(self::describeOf(['accountname'], self::ACCOUNT_READONLY_FIELDS));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Field(s) not editable for module 'Accounts': createdtime, modifiedby. These fields cannot be set."
        );
        self::call($tools, 'update', [
            'module' => 'Accounts',
            'id'     => 2,
            'fields' => ['createdtime' => '2026-01-01 00:00:00', 'modifiedby' => 5],
        ]);
    }

    public function test_update_with_editable_and_non_editable_fields_is_rejected_as_a_whole(): void
    {
        // 編集可能な項目だけを保存して成功を返さない（2 回目の describe = 書き込み準備に進まない）
        $tools = self::stubWithDescribe(self::describeOf(['accountname'], self::ACCOUNT_READONLY_FIELDS));

        try {
            self::call($tools, 'update', [
                'module' => 'Accounts',
                'id'     => 2,
                'fields' => ['accountname' => 'テスト用_株式会社サンプル', 'createdtime' => '2026-01-01 00:00:00'],
            ]);
            $this->fail('non-editable field was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("module 'Accounts': createdtime.", $e->getMessage());
            $this->assertStringNotContainsString('accountname', $e->getMessage());
        }
    }

    public function test_editable_fields_are_accepted_when_describe_has_non_editable_fields(): void
    {
        $this->assertKnown('Accounts', [
            'accountname' => 'テスト用_株式会社サンプル',
            'phone'       => '000-0000-0000',
        ], ['accountname', 'phone'], self::ACCOUNT_READONLY_FIELDS);
        $this->addToAssertionCount(1);
    }

    public function test_id_is_accepted_although_describe_marks_it_non_editable(): void
    {
        // update は引数の id で上書きし、create は従来どおり受け付けるため、id は照合対象外
        $this->assertKnown('Accounts', ['id' => 2, 'accountname' => 'テスト用_株式会社サンプル'], ['accountname'], self::ACCOUNT_READONLY_FIELDS);
        $this->addToAssertionCount(1);
    }

    public function test_non_editable_message_does_not_contain_values(): void
    {
        try {
            $this->assertKnown('Accounts', ['createdtime' => '2026-01-01 00:00:00'], ['accountname'], self::ACCOUNT_READONLY_FIELDS);
            $this->fail('non-editable field was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString('2026-01-01', $e->getMessage());
        }
    }

    public function test_create_rejects_non_editable_field_before_writing(): void
    {
        $tools = self::stubWithDescribe(self::describeOf(['accountname'], self::ACCOUNT_READONLY_FIELDS));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field(s) not editable for module 'Accounts': modifiedby.");
        self::call($tools, 'create', [
            'module' => 'Accounts',
            'fields' => ['accountname' => 'テスト用_株式会社サンプル', 'modifiedby' => 5],
        ]);
    }

    public function test_search_whitelist_still_contains_non_editable_fields(): void
    {
        // crm_search は作成日時などで絞り込めるよう、編集不可の項目も条件・取得項目に使える
        $tools = self::stubWithDescribe(self::describeOf(['accountname'], self::ACCOUNT_READONLY_FIELDS));

        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'getValidFieldNames');
        $m->setAccessible(true);
        $names = $m->invoke($tools, 'Accounts');

        $this->assertContains('createdtime', $names);
        $this->assertContains('modifiedby', $names);
    }
}
