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
use ReflectionMethod;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';
require_once $root . '/tests/Support/McpEntityMetaStub.php';

/**
 * crm_search の id・参照・担当者の検索値の検証を DB なしでテストする。
 *
 * VTQL はこれらの項目の値から引用符を外して SQL に埋め込むため、
 * crmid か webservice ID 以外の値が VTQL の実行前に拒否されることを確認する。
 * describe・webservice メタ・VTQL の実行を差し替え、組み立てた VTQL を記録して検証する。
 */
class CrmToolsSearchIdValueTest extends TestCase
{
    /** テスト用の webservice エンティティ id */
    private const WS_ENTITY_IDS = ['Contacts' => 12, 'Accounts' => 11, 'Users' => 19];

    /** @var string[] runQuery() に渡された VTQL */
    public static array $queries = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::$queries = [];
    }

    /** Contacts の describe の一部を模す */
    public static function contactsDescribe(): array
    {
        return ['fields' => [
            ['name' => 'lastname', 'type' => ['name' => 'string'], 'editable' => true],
            ['name' => 'account_id', 'type' => ['name' => 'reference', 'refersTo' => ['Accounts']], 'editable' => true],
            ['name' => 'assigned_user_id', 'type' => ['name' => 'owner'], 'editable' => true],
        ]];
    }

    private static function tools(): \Mcp_CrmTools
    {
        return new class () extends \Mcp_CrmTools {
            public function __construct()
            {
            }

            protected function describeModule(string $module): array
            {
                return CrmToolsSearchIdValueTest::contactsDescribe();
            }

            protected function getEntityMeta(string $module): \EntityMeta
            {
                return new \McpEntityMetaStub($module, CrmToolsSearchIdValueTest::wsEntityId($module), [], []);
            }

            protected function runQuery(string $query): array
            {
                CrmToolsSearchIdValueTest::$queries[] = $query;
                return [];
            }
        };
    }

    public static function wsEntityId(string $module): int
    {
        return self::WS_ENTITY_IDS[$module];
    }

    /** @param mixed $value */
    private static function search(string $field, $value, string $operator = '='): void
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'search');
        $m->setAccessible(true);
        $m->invoke(self::tools(), [
            'module'     => 'Contacts',
            'conditions' => [['field' => $field, 'operator' => $operator, 'value' => $value]],
        ]);
    }

    /** @return array<string, array{string, mixed, string}> */
    public static function injectedValues(): array
    {
        return [
            'id: 括弧を閉じて OR'              => ['id', '12x0) OR (1=1', '='],
            'id: 数値に OR'                    => ['id', '1 OR 1=1', '='],
            'id: 単引用符で閉じて OR'          => ['id', "12x0' OR '1'='1", '='],
            'id: 二重引用符で囲む'             => ['id', '"12x0) OR (1=1"', '='],
            'id: like で括弧を閉じて OR'       => ['id', '12x0) OR (1=1', 'like'],
            'id: 配列'                         => ['id', ['12x34'], '='],
            'account_id: 括弧を閉じて OR'      => ['account_id', '11x0) OR (1=1', '='],
            'account_id: 単引用符で閉じて OR'  => ['account_id', "11x0' OR '1'='1", 'like'],
            'assigned_user_id: 括弧を閉じて OR' => ['assigned_user_id', '19x0) OR (1=1', '='],
            'assigned_user_id: 数値に OR'      => ['assigned_user_id', '1 OR 1=1', '!='],
        ];
    }

    /**
     * @dataProvider injectedValues
     * @param mixed $value
     */
    public function test_injected_id_values_are_rejected_before_query(string $field, $value, string $operator): void
    {
        try {
            self::search($field, $value, $operator);
            $this->fail('accepted and ran VTQL: ' . implode(' | ', self::$queries));
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString($field, $e->getMessage());
            $this->assertSame([], self::$queries);
        }
    }

    public function test_rejection_message_does_not_contain_value(): void
    {
        try {
            self::search('id', '12x0) OR (1=1');
            $this->fail('injected value was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString('OR (1=1', $e->getMessage());
        }
    }

    /** @return array<string, array{string, mixed, string, string}> */
    public static function validValues(): array
    {
        return [
            'id: webservice ID'              => ['id', '12x34', '=', "id = '12x34'"],
            'id: crmid 文字列'               => ['id', '34', '=', "id = '12x34'"],
            'id: crmid 整数'                 => ['id', 34, '=', "id = '12x34'"],
            'id: like'                       => ['id', '12x34', 'like', "id like '12x34'"],
            'account_id: webservice ID'      => ['account_id', '11x34', '=', "account_id = '11x34'"],
            'account_id: crmid'              => ['account_id', '34', '=', "account_id = '11x34'"],
            'assigned_user_id: webservice ID' => ['assigned_user_id', '19x5', '=', "assigned_user_id = '19x5'"],
            'assigned_user_id: crmid'        => ['assigned_user_id', '5', '!=', "assigned_user_id != '19x5'"],
        ];
    }

    /**
     * @dataProvider validValues
     * @param mixed $value
     */
    public function test_valid_id_values_are_searched_as_webservice_id(string $field, $value, string $operator, string $expected): void
    {
        self::search($field, $value, $operator);

        $this->assertSame(["SELECT * FROM Contacts WHERE {$expected} LIMIT 20;"], self::$queries);
    }

    public function test_plain_string_field_is_still_searchable(): void
    {
        self::search('lastname', 'テスト用_山田', 'like');

        $this->assertSame(["SELECT * FROM Contacts WHERE lastname like 'テスト用_山田' LIMIT 20;"], self::$queries);
    }

    public function test_plain_string_field_keeps_quote_escape(): void
    {
        // id 系以外の項目は従来どおり単引用符の二重化で検索できる
        self::search('lastname', "テスト用_O'Brien OR 1=1");

        $this->assertSame(["SELECT * FROM Contacts WHERE lastname = 'テスト用_O''Brien OR 1=1' LIMIT 20;"], self::$queries);
    }
}
