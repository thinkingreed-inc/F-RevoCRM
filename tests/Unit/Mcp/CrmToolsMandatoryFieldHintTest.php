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
use ReflectionProperty;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';
require_once $root . '/include/Webservices/WebServiceError.php';

/**
 * 必須項目不足エラーの補足文（explainMandatoryFields）を DB なしでテストする。
 *
 * create() は activitytype を補完してから vtws_create を呼ぶため、補足文に挙げるのは
 * 「送信した項目に無い、または空の必須項目」だけであることを確認する。
 * describe は Calendar の補正後（activitytype が mandatory）を模して差し替える。
 */
class CrmToolsMandatoryFieldHintTest extends TestCase
{
    /** describe を模した項目名 => 必須かどうか */
    private const FIELDS = [
        'Calendar' => [
            'subject'          => true,
            'activitytype'     => true,
            'assigned_user_id' => true,
            'date_start'       => true,
            'due_date'         => true,
            'description'      => false,
        ],
        'Accounts' => [
            'accountname'      => true,
            'assigned_user_id' => true,
            'employees'        => false,
        ],
    ];

    private \Mcp_CrmTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new class () extends \Mcp_CrmTools {
            public function __construct()
            {
            }

            /** @return array<string, mixed> */
            protected function describeModule(string $module): array
            {
                return CrmToolsMandatoryFieldHintTest::describe($module);
            }
        };

        // 補足文の担当者 ID に使う。Users 本体は DB を要求するため id だけ持つオブジェクトで代替する
        $user = new ReflectionProperty(\Mcp_CrmTools::class, 'user');
        $user->setAccessible(true);
        $user->setValue($this->tools, (object) ['id' => 5]);
    }

    /** @return array<string, mixed> */
    public static function describe(string $module): array
    {
        $fields = [];
        foreach (self::FIELDS[$module] ?? [] as $name => $mandatory) {
            $fields[] = ['name' => $name, 'label' => strtoupper($name), 'mandatory' => $mandatory];
        }
        return ['fields' => $fields];
    }

    /** @param array<string, mixed> $fields */
    private function explain(string $module, array $fields): \Throwable
    {
        $e = new \WebServiceException(\WebServiceErrorCode::$MANDFIELDSMISSING, 'Missing mandatory input values.');
        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'explainMandatoryFields');
        $m->setAccessible(true);
        $result = $m->invoke($this->tools, $module, $fields, $e);
        $this->assertInstanceOf(\Throwable::class, $result);

        return $result;
    }

    /**
     * create() と同じく activitytype を補完した後の項目を返す
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function withActivityTypeDefault(string $module, array $fields): array
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'applyActivityTypeDefault');
        $m->setAccessible(true);
        $applied = $m->invoke($this->tools, $module, $fields);
        $this->assertIsArray($applied);

        return $applied;
    }

    public function test_calendar_hint_omits_defaulted_activitytype_and_supplied_subject(): void
    {
        $fields = $this->withActivityTypeDefault('Calendar', ['subject' => 'テスト用ToDo_見積書の確認']);
        $message = $this->explain('Calendar', $fields)->getMessage();

        $this->assertStringNotContainsString('activitytype', $message);
        $this->assertStringNotContainsString('subject', $message);
        $this->assertStringContainsString(
            "Module 'Calendar' requires: assigned_user_id(ASSIGNED_USER_ID), date_start(DATE_START), due_date(DUE_DATE).",
            $message
        );
        $this->assertStringContainsString('pass assigned_user_id=5.', $message);
        $this->assertStringStartsWith('Missing mandatory input values.', $message);
    }

    public function test_only_the_single_missing_mandatory_field_is_listed(): void
    {
        $message = $this->explain('Accounts', ['accountname' => 'テスト用_株式会社サンプル'])->getMessage();

        $this->assertStringContainsString("Module 'Accounts' requires: assigned_user_id(ASSIGNED_USER_ID).", $message);
        $this->assertStringNotContainsString('accountname', $message);
    }

    public function test_empty_string_counts_as_missing(): void
    {
        $message = $this->explain('Accounts', ['accountname' => '', 'assigned_user_id' => 5])->getMessage();

        $this->assertStringContainsString("requires: accountname(ACCOUNTNAME).", $message);
    }

    public function test_zero_counts_as_provided(): void
    {
        // webservice 層（EntityMeta）と同じく '0' / 0 は値ありとして扱う
        $message = $this->explain('Accounts', ['accountname' => '0', 'assigned_user_id' => 0])->getMessage();

        $this->assertStringNotContainsString('accountname', $message);
        $this->assertStringNotContainsString('assigned_user_id(', $message);
    }

    public function test_non_mandatory_fields_are_never_listed(): void
    {
        $message = $this->explain('Accounts', [])->getMessage();

        $this->assertStringContainsString("requires: accountname(ACCOUNTNAME), assigned_user_id(ASSIGNED_USER_ID).", $message);
        $this->assertStringNotContainsString('employees', $message);
    }

    public function test_other_webservice_errors_are_returned_unchanged(): void
    {
        $e = new \WebServiceException(\WebServiceErrorCode::$ACCESSDENIED, 'Permission to perform the operation is denied');
        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'explainMandatoryFields');
        $m->setAccessible(true);

        $this->assertSame($e, $m->invoke($this->tools, 'Accounts', [], $e));
    }
}
