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
use ReflectionMethod;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';
require_once $root . '/tests/Support/McpEntityMetaStub.php';

/**
 * Calendar（ToDo）と Events（予定）の取り違えを DB なしでテストする。
 *
 * 両者は crmentity 上は同じ setype='Calendar' だが webservice 上は別エンティティで、
 * webservice 層は id の実体と要求モジュールが違うと INVALIDID で拒否する。
 * MCP の crm_get / crm_update / crm_delete も同じ規則で、項目名の照合より前に拒否することを確認する。
 * webservice メタ（getEntityMeta）と describe（describeModule）を差し替えて検証する。
 */
class CrmToolsCalendarEntityGuardTest extends TestCase
{
    /** テスト用の crmid => 実体モジュール（Meeting=Events / Task=Calendar。vtiger_activity を模す） */
    private const ACTIVITIES = [
        12 => 'Events',
        7  => 'Calendar',
    ];

    /** describe を模した項目名 */
    private const FIELDS = [
        'Calendar' => ['subject', 'activitytype', 'assigned_user_id'],
        'Events'   => ['subject', 'activitytype', 'assigned_user_id', 'common_memo'],
        'Accounts' => ['accountname', 'assigned_user_id'],
    ];

    /** @var int[] webservice メタに照会された crmid の記録 */
    public static array $lookups = [];

    private \Mcp_CrmTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        self::$lookups = [];
        $this->tools = new class () extends \Mcp_CrmTools {
            public function __construct()
            {
            }

            protected function getEntityMeta(string $module): \EntityMeta
            {
                return CrmToolsCalendarEntityGuardTest::meta($module);
            }

            /** @return array<string, mixed> */
            protected function describeModule(string $module): array
            {
                return CrmToolsCalendarEntityGuardTest::describe($module);
            }
        };
    }

    public static function meta(string $module): \EntityMeta
    {
        if ($module !== 'Calendar') {
            throw new \LogicException('Unexpected meta lookup: ' . $module);
        }
        return new \McpEntityMetaStub('Calendar', 9, array_keys(self::ACTIVITIES), self::ACTIVITIES, static function (int $crmid): void {
            CrmToolsCalendarEntityGuardTest::$lookups[] = $crmid;
        });
    }

    /** @return array<string, mixed> */
    public static function describe(string $module): array
    {
        $fields = [];
        foreach (self::FIELDS[$module] ?? [] as $name) {
            $fields[] = ['name' => $name];
        }
        return ['fields' => $fields];
    }

    /**
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invoke(string $method, array $args)
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->tools, $args);
    }

    public function test_update_with_calendar_on_meeting_record_is_rejected_with_events_hint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Record 12 is an Events record; use module 'Events'");
        $this->invoke('update', [[
            'module' => 'Calendar',
            'id'     => 12,
            'fields' => ['common_memo' => 'テスト用メモ'],
        ]]);
    }

    public function test_update_with_events_on_task_record_is_rejected_with_calendar_hint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Record 7 is a Calendar (ToDo) record; use module 'Calendar'");
        $this->invoke('update', [[
            'module' => 'Events',
            'id'     => 7,
            'fields' => ['subject' => 'テスト用件名'],
        ]]);
    }

    public function test_update_with_matching_module_passes_entity_check_and_reaches_field_check(): void
    {
        // 実体が一致すれば次の項目名照合まで進むことを、未定義項目の拒否で確認する
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown field name(s) for module 'Events'");
        $this->invoke('update', [[
            'module' => 'Events',
            'id'     => 12,
            'fields' => ['no_such_field' => 'x'],
        ]]);
    }

    public function test_assert_passes_for_matching_module_and_missing_record(): void
    {
        $this->assertNull($this->invoke('assertCalendarEntityMatches', ['Events', 12]));
        $this->assertNull($this->invoke('assertCalendarEntityMatches', ['Calendar', 7]));
        // 存在しないレコードは既存の未存在時の挙動に委ねる
        $this->assertNull($this->invoke('assertCalendarEntityMatches', ['Calendar', 999999]));
        $this->assertSame([12, 7, 999999], self::$lookups);
    }

    public function test_non_calendar_module_does_not_look_up_entity(): void
    {
        try {
            $this->invoke('update', [[
                'module' => 'Accounts',
                'id'     => 12,
                'fields' => ['no_such_field' => 'x'],
            ]]);
            $this->fail('unknown field was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("Unknown field name(s) for module 'Accounts'", $e->getMessage());
        }
        $this->assertSame([], self::$lookups);
    }

    public function test_get_with_calendar_on_meeting_record_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("use module 'Events'");
        $this->invoke('get', [['module' => 'Calendar', 'id' => 12]]);
    }

    public function test_delete_with_events_on_task_record_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("use module 'Calendar'");
        $this->invoke('delete', [['module' => 'Events', 'id' => 7]]);
    }
}
