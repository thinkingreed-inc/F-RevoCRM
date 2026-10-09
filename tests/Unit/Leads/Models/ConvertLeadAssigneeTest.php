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

namespace Tests\Unit\Leads\Models;

use Leads_ConvertLeadAssignee_Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/modules/Leads/models/ConvertLeadAssignee.php';

/**
 * リード昇格画面の担当の初期値 — #1864
 *
 * 対象: modules/Leads/models/ConvertLeadAssignee.php
 *
 *   1  フラグが true ならログインユーザーを初期値にする
 *   2  フラグが false・未登録・不正な値ならリードの担当を初期値にする（従来どおり）
 *   3  リードの担当がグループでも、フラグが true ならログインユーザーにする
 *   4  ログインユーザーが取れないときはリードの担当のままにする
 */
final class ConvertLeadAssigneeTest extends TestCase
{
    private const LEAD_ASSIGNED_USER_ID = '5';
    private const CURRENT_USER_ID = '1';
    private const GROUP_ID = '3';

    /**
     * @return array<string, array{mixed}>
     */
    public static function enabledValues(): array
    {
        return [
            'true' => ['true'],
            '大文字' => ['TRUE'],
            '前後の空白' => [' true '],
            '文字列の 1' => ['1'],
            '数値の 1' => [1],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function disabledValues(): array
    {
        return [
            'false' => ['false'],
            '文字列の 0' => ['0'],
            '空文字' => [''],
            '未登録（null）' => [null],
            'yes は有効扱いしない' => ['yes'],
            '配列' => [['true']],
        ];
    }

    #[DataProvider('enabledValues')]
    public function testCurrentUserIsDefaultWhenFlagIsEnabled(mixed $parameterValue): void
    {
        $this->assertTrue(Leads_ConvertLeadAssignee_Model::isAssignCurrentUserEnabled($parameterValue));
        $this->assertSame(
            self::CURRENT_USER_ID,
            Leads_ConvertLeadAssignee_Model::resolveDefaultAssignedUserId(
                self::LEAD_ASSIGNED_USER_ID,
                self::CURRENT_USER_ID,
                $parameterValue
            )
        );
    }

    #[DataProvider('disabledValues')]
    public function testLeadAssigneeIsDefaultWhenFlagIsDisabled(mixed $parameterValue): void
    {
        $this->assertFalse(Leads_ConvertLeadAssignee_Model::isAssignCurrentUserEnabled($parameterValue));
        $this->assertSame(
            self::LEAD_ASSIGNED_USER_ID,
            Leads_ConvertLeadAssignee_Model::resolveDefaultAssignedUserId(
                self::LEAD_ASSIGNED_USER_ID,
                self::CURRENT_USER_ID,
                $parameterValue
            )
        );
    }

    public function testCurrentUserReplacesGroupAssigneeWhenFlagIsEnabled(): void
    {
        $this->assertSame(
            self::CURRENT_USER_ID,
            Leads_ConvertLeadAssignee_Model::resolveDefaultAssignedUserId(self::GROUP_ID, self::CURRENT_USER_ID, 'true')
        );
    }

    public function testLeadAssigneeIsKeptWhenCurrentUserIsUnknown(): void
    {
        foreach (['', null, 0] as $currentUserId) {
            $this->assertSame(
                self::LEAD_ASSIGNED_USER_ID,
                Leads_ConvertLeadAssignee_Model::resolveDefaultAssignedUserId(
                    self::LEAD_ASSIGNED_USER_ID,
                    $currentUserId,
                    'true'
                )
            );
        }
    }
}
