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
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';

/**
 * Mcp_CrmTools の activitytype 補完・検証を DB なしでテストする。
 *
 * Calendar / Events / Emails は vtiger_activity を共有し、activitytype で区別される。
 * activitytype が空の場合、各モジュールの検索結果に正しく含まれなくなるため、
 * MCP 経由で不正なレコードが作成されないことを確認する。
 *
 * create() は vtws_create を直接呼び出すため、テストでは create() 内と同じ順序で
 * 「未指定値の補完 → 値の検証」を実行し、実際の登録処理は行わない。
 * Events の許可値はキャッシュ用プロパティへ直接設定し、DB 参照を避ける。
 */
class CrmToolsActivityTypeTest extends TestCase
{
    /** vtiger_activitytype の実データ（presence = 0 の 3 件） */
    private const EVENT_TYPES = ['Call', 'Meeting', 'Mobile Call'];

    private \Mcp_CrmTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        // コンストラクタが要求する Users は activitytype の判定に使用しないため生成しない
        $this->tools = (new ReflectionClass(\Mcp_CrmTools::class))->newInstanceWithoutConstructor();

        $cache = new ReflectionProperty(\Mcp_CrmTools::class, 'eventActivityTypes');
        $cache->setAccessible(true);
        $cache->setValue($this->tools, self::EVENT_TYPES);
    }

    /** @return mixed */
    private function invoke(string $method, array $args)
    {
        $m = new ReflectionMethod(\Mcp_CrmTools::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->tools, $args);
    }

    /** create() と同じ順序で「未指定値の補完 → 値の検証」を行い、保存対象の値を返す */
    private function simulateCreate(string $module, array $fields): array
    {
        $fields = $this->invoke('applyActivityTypeDefault', [$module, $fields]);
        $this->invoke('assertActivityTypeMatchesModule', [$module, $fields]);

        return $fields;
    }

    /** update() は部分更新のため、値の検証のみ行う */
    private function simulateUpdate(string $module, array $fields): void
    {
        $this->invoke('assertActivityTypeMatchesModule', [$module, $fields]);
    }

    // ---------------------------------------------------------------- Calendar

    public function test_calendar_without_activitytype_is_defaulted_to_task(): void
    {
        $fields = $this->simulateCreate('Calendar', ['subject' => 'テスト用ToDo_見積書の確認']);

        $this->assertSame('Task', $fields['activitytype']);
    }

    public function test_calendar_rejects_event_activitytype(): void
    {
        // 案内するモジュール名のみを検証し、メッセージ本文は検証対象としない
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("module 'Events'");

        $this->simulateCreate('Calendar', ['subject' => 'テスト用打合せ', 'activitytype' => 'Meeting']);
    }

    // ---------------------------------------------------------------- Emails

    public function test_emails_without_activitytype_is_defaulted_to_emails(): void
    {
        // 補完されない場合は空文字で保存され、crm_search(Emails) の検索対象から外れる
        $fields = $this->simulateCreate('Emails', ['subject' => 'テスト用メール_納品のご案内']);

        $this->assertSame('Emails', $fields['activitytype']);
    }

    public function test_emails_rejects_task_activitytype(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Use module 'Calendar' instead.");

        $this->simulateCreate('Emails', ['subject' => 'テスト用メール', 'activitytype' => 'Task']);
    }

    // ---------------------------------------------------------------- Events

    public function test_events_without_activitytype_is_rejected_with_allowed_values(): void
    {
        // Events は既定値を設定せず、未指定の場合は許可値を案内してエラーとする
        try {
            $this->simulateCreate('Events', ['subject' => 'テスト用活動_定例会']);
            $this->fail('activitytype 未指定の Events が例外にならなかった');
        } catch (\InvalidArgumentException $e) {
            foreach (self::EVENT_TYPES as $type) {
                $this->assertStringContainsString("'{$type}'", $e->getMessage());
            }
        }
    }

    public function test_events_accepts_master_activitytype(): void
    {
        $fields = $this->simulateCreate('Events', ['subject' => 'テスト用活動_定例会', 'activitytype' => 'Meeting']);

        $this->assertSame('Meeting', $fields['activitytype']);
    }

    public function test_events_rejects_task_activitytype(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Use module 'Calendar' instead.");

        $this->simulateCreate('Events', ['subject' => 'テスト用活動', 'activitytype' => 'Task']);
    }

    public function test_events_rejects_value_absent_from_master(): void
    {
        // マスタに存在しない値は検索条件と一致しなくなるため保存を許可しない
        try {
            $this->simulateCreate('Events', ['subject' => 'テスト用活動', 'activitytype' => 'Lunch']);
            $this->fail("マスタに無い activitytype 'Lunch' が例外にならなかった");
        } catch (\InvalidArgumentException $e) {
            foreach (self::EVENT_TYPES as $type) {
                $this->assertStringContainsString("'{$type}'", $e->getMessage());
            }
        }
    }

    public function test_events_update_without_activitytype_is_allowed(): void
    {
        // 部分更新では activitytype を必須とせず、他項目のみの更新を許可する
        $this->simulateUpdate('Events', ['subject' => 'テスト用活動_定例会（更新）']);
        $this->addToAssertionCount(1);
    }

    // ---------------------------------------------------------------- 対象外モジュール

    public function test_non_activity_module_is_not_checked(): void
    {
        // 活動系以外のモジュールは、同名項目が存在しても検証対象としない
        $fields = $this->simulateCreate('Accounts', ['accountname' => 'テスト用取引先', 'activitytype' => 'Lunch']);

        $this->assertSame('Lunch', $fields['activitytype']);
    }
}
