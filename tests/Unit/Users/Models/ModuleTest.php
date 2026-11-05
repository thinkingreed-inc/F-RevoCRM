<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Users\Models;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/tests/Support/UsersModuleTestStubs.php';
require_once dirname(__DIR__, 4) . '/modules/Users/models/Module.php';

/**
 * ユーザー参照項目の候補検索 — Users_Module_Model (#1887)
 *
 * 入力補完（searchRecord）と「上司」項目のポップアップ（getQueryByModuleField）が、
 * 上司・部下関係（reports_to_id）にあるユーザーを候補から除外しないことを確かめる。
 *
 * 修正前の実装はログイン中のユーザー（Users_Record_Model::getCurrentUserModel()）を
 * 起点に部下を引いていた。このテストでは Users_Record_Model を読み込まないため、
 * その呼び出しが残っていればクラス未定義のエラーで失敗する。
 */
final class ModuleTest extends TestCase
{
    /** 編集中のユーザーの ID */
    private const RECORD_ID = 5;

    private const LIST_QUERY = 'SELECT vtiger_users.id FROM vtiger_users WHERE vtiger_users.status = \'Active\'';

    private \UsersModuleTestDatabase $db;

    private mixed $previousAdb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousAdb = $GLOBALS['adb'] ?? null;
        $this->db = new \UsersModuleTestDatabase();
        $GLOBALS['adb'] = $this->db;
    }

    protected function tearDown(): void
    {
        $GLOBALS['adb'] = $this->previousAdb;

        parent::tearDown();
    }

    public function test_入力補完は部下を除外せずActiveなユーザーを名前で検索する(): void
    {
        $module = new \Users_Module_Model();
        $records = $module->searchRecord('部下');

        $this->assertSame([], $records);
        $this->assertCount(1, $this->db->executed);
        $this->assertSame(
            'SELECT * FROM vtiger_users WHERE userlabel LIKE ? AND status = ?',
            $this->db->executed[0]['sql'],
        );
        $this->assertSame(['%部下%', 'Active'], $this->db->executed[0]['params']);
    }

    public function test_上司項目のポップアップは編集中のユーザー自身だけを除外する(): void
    {
        $module = new \Users_Module_Model();
        $query = $module->getQueryByModuleField('Users', 'reports_to_id', (string)self::RECORD_ID, self::LIST_QUERY);

        $this->assertSame(self::LIST_QUERY . " AND vtiger_users.id != '5' ", $query);
        $this->assertStringNotContainsString('NOT IN', (string)$query);
        $this->assertSame([], $this->db->executed, '部下を求めるクエリを発行しないこと');
    }

    public function test_上司項目のポップアップは新規作成時に一覧の条件を変えない(): void
    {
        $module = new \Users_Module_Model();
        $query = $module->getQueryByModuleField('Users', 'reports_to_id', '', self::LIST_QUERY);

        $this->assertSame(self::LIST_QUERY, $query);
    }

    public function test_上司項目以外のポップアップは対象外(): void
    {
        $module = new \Users_Module_Model();

        $this->assertNull($module->getQueryByModuleField('Accounts', 'assigned_user_id', '10', self::LIST_QUERY));
    }
}
