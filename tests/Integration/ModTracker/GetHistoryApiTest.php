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

namespace Tests\Integration\ModTracker;

use ApiException;
use CRMEntity;
use ModTracker_GetHistory_Api;
use ModTracker_Record_Model;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Users_Record_Model;
use Vtiger_Request;
use Vtiger_Response;

/**
 * 更新履歴 API — ModTracker_GetHistory_Api（#1943）
 *
 * record-history（assets/react-web-components）が受け取る JSON の中身を固定する。
 * 項目値・増減の書式、システム項目の判定、項目の並び順、関連付けの扱い、
 * 入力値の検証を、本体をまるごと読み込んだ状態で検証する。
 *
 * 既存データと衝突しない ID 帯に顧客企業と履歴を投入し、tearDown() でまとめて消す。
 *
 * @phpstan-type Value array{raw: string, display: string}
 * @phpstan-type Delta array{sign: string, display: string, percent: int|null}
 * @phpstan-type Change array{field: string, before: Value|null, after: Value|null, delta?: Delta}
 * @phpstan-type Related array{module: string, moduleLabel: string, recordId: int, label: string, url: string|null}
 * @phpstan-type Event array{id: int, type: string, userId: int, changedOn: string, changes: list<Change>, related?: Related}
 * @phpstan-type Field array{name: string, label: string, type: string, system: bool, order: int}
 * @phpstan-type User array{id: int, name: string, imageUrl: string|null}
 * @phpstan-type HistoryResponse array{success: bool, now: string, format: array{dateFormat: string, hourFormat: string}, events: list<Event>, users: array<int|string, User>, fields: array<string, Field>}
 */
final class GetHistoryApiTest extends TestCase
{
    private const ADMIN_USER_ID = 1;

    /** 履歴をぶら下げる顧客企業 */
    private const ACCOUNT_ID = 992001;

    /** 関連付けの相手（顧客企業） */
    private const LINKED_ACCOUNT_ID = 992002;

    /** 存在しない関連先 */
    private const MISSING_RECORD_ID = 992099;

    private const HISTORY_CREATE = 992101;
    private const HISTORY_UPDATE = 992102;
    private const HISTORY_IGNORED_ONLY = 992103;
    private const HISTORY_LINK = 992104;
    private const HISTORY_LINK_MISSING = 992105;

    private const LABEL = 'SPECTEST_更新履歴';

    private static bool $initialized = false;

    private PearDatabase $db;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    /**
     * テスト用 DB につながらなければ、このクラスのテストをまるごと飛ばす
     * （CI は DB を立てずに実行するため。tests/README.md 参照）
     */
    private static function requireTestDatabase(): void
    {
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_tab', []);
            $count = ($result !== false && $db->num_rows($result) === 1)
                ? (int) $db->query_result($result, 0, 'cnt')
                : 0;
        } catch (\Throwable $e) {
            $count = 0;
        }
        if ($count === 0) {
            self::markTestSkipped('テスト用DBに接続できないか初期データがないため飛ばします（tests/README.md のテスト用DBを参照）');
        }
    }

    /**
     * F-RevoCRM 本体を読み込む（tests/Support/ReportsTestCase.php と同じ手順）
     *
     * tests/bootstrap.php が Vtiger_Loader のオートロードを外しているため、ここで戻す。
     */
    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 3);

        if (class_exists('Vtiger_Loader', false)) {
            spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        }

        require_once $root . '/include/utils/utils.php';
        require_once $root . '/include/database/PearDatabase.php';
        vimport('includes.runtime.Globals');
        vimport('includes.runtime.LanguageHandler');
        vimport('includes.runtime.BaseModel');
        vimport('includes.runtime.Controller');
        vimport('includes.http.Request');
        vimport('includes.http.Response');
        vimport('includes.exceptions.AppException');

        require_once $root . '/modules/Users/Users.php';
        require_once $root . '/vtlib/Vtiger/Module.php';
        require_once $root . '/modules/ModTracker/apis/GetHistory.php';

        date_default_timezone_set('Asia/Tokyo');
        self::$initialized = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAsAdmin([
            'date_format' => 'yyyy-mm-dd',
            'hour_format' => '24',
            'currency_grouping_pattern' => '123,456,789',
            'currency_grouping_separator' => ',',
            'currency_decimal_separator' => '.',
            'no_of_currency_decimals' => '2',
            'currency_symbol_placement' => '$1.0',
            'truncate_trailing_zeros' => '0',
        ]);
        $this->cleanUp();
        $this->createAccount(self::ACCOUNT_ID, self::LABEL);
        $this->createAccount(self::LINKED_ACCOUNT_ID, 'SPECTEST_関連先');
        $this->insertFixtureHistory();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }

    // ---- テスト ---------------------------------------------------------

    public function test_履歴を新しい順に返し_閲覧できる項目の変更が無い更新は返さない(): void
    {
        $result = $this->callApi('Accounts', (string) self::ACCOUNT_ID);

        $this->assertTrue($result['success']);
        $this->assertSame(
            [self::HISTORY_LINK, self::HISTORY_UPDATE, self::HISTORY_CREATE],
            array_column($result['events'], 'id'),
            '内部管理用の項目（label）だけの更新と、存在しない関連先への関連付けは返さない'
        );
        $this->assertSame(['link', 'update', 'create'], array_column($result['events'], 'type'));
    }

    public function test_内部管理用の項目と存在しない項目は変更に含めない(): void
    {
        $create = $this->eventById($this->callApi('Accounts', (string) self::ACCOUNT_ID), self::HISTORY_CREATE);

        $fields = array_column($create['changes'], 'field');
        $this->assertNotContains('record_id', $fields);
        $this->assertNotContains('record_module', $fields);
        $this->assertNotContains('spectest_no_such_field', $fields);
        $this->assertContains('accountname', $fields);
    }

    public function test_変更は詳細画面の並び順で返し_最終更新者はシステム項目になる(): void
    {
        $result = $this->callApi('Accounts', (string) self::ACCOUNT_ID);
        $update = $this->eventById($result, self::HISTORY_UPDATE);

        // 投入は逆順（modifiedby → annual_revenue → phone）にしてある
        $this->assertSame(['phone', 'annual_revenue', 'modifiedby'], array_column($update['changes'], 'field'));

        $fields = $result['fields'];
        $this->assertTrue($fields['modifiedby']['system'], '最終更新者は既定で隠すシステム項目');
        $this->assertFalse($fields['phone']['system']);
        $this->assertLessThan($fields['annual_revenue']['order'], $fields['phone']['order']);
        $this->assertLessThan($fields['modifiedby']['order'], $fields['annual_revenue']['order']);
    }

    public function test_金額はユーザー設定の書式と通貨記号で返し_増減も同じ書式で返す(): void
    {
        $update = $this->eventById($this->callApi('Accounts', (string) self::ACCOUNT_ID), self::HISTORY_UPDATE);
        $revenue = $this->changeByField($update, 'annual_revenue');

        $symbol = $this->userCurrencySymbol();
        $this->assertSame(['raw' => '500000000.00000000', 'display' => "$symbol 500,000,000.00"], $revenue['before']);
        $this->assertSame(['raw' => '720000000.00000000', 'display' => "$symbol 720,000,000.00"], $revenue['after']);
        if (!isset($revenue['delta'])) {
            self::fail('金額の増減が返らなかった');
        }
        $this->assertSame(['sign' => '+', 'display' => "$symbol 220,000,000.00", 'percent' => 44], $revenue['delta']);

        $phone = $this->changeByField($update, 'phone');
        $this->assertArrayNotHasKey('delta', $phone, '数値以外の項目は増減を返さない');
    }

    public function test_桁区切りと通貨記号の位置はユーザーごとの設定に従う(): void
    {
        $this->loginAsAdmin([
            'currency_grouping_separator' => '.',
            'currency_decimal_separator' => ',',
            'no_of_currency_decimals' => '0',
            'currency_symbol_placement' => '1.0$',
        ]);

        $update = $this->eventById($this->callApi('Accounts', (string) self::ACCOUNT_ID), self::HISTORY_UPDATE);
        $revenue = $this->changeByField($update, 'annual_revenue');

        $symbol = $this->userCurrencySymbol();
        $this->assertSame("720.000.000 $symbol", $revenue['after']['display'] ?? null);
        $this->assertSame("220.000.000 $symbol", $revenue['delta']['display'] ?? null);
    }

    public function test_空値はnullで返す(): void
    {
        $create = $this->eventById($this->callApi('Accounts', (string) self::ACCOUNT_ID), self::HISTORY_CREATE);
        $phone = $this->changeByField($create, 'phone');

        $this->assertNull($phone['before']);
        $this->assertSame('03-1234-5678', $phone['after']['display'] ?? null);
    }

    public function test_変更日時の書式としてユーザー設定の日付形式と時刻形式を返す(): void
    {
        $this->loginAsAdmin(['date_format' => 'dd-mm-yyyy', 'hour_format' => '12']);

        $result = $this->callApi('Accounts', (string) self::ACCOUNT_ID);

        $this->assertSame(['dateFormat' => 'dd-mm-yyyy', 'hourFormat' => '12'], $result['format']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $result['now']);
        foreach ($result['events'] as $event) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $event['changedOn']);
        }
    }

    public function test_関連付けは相手のモジュールとレコード名を返す(): void
    {
        $result = $this->callApi('Accounts', (string) self::ACCOUNT_ID);
        $link = $this->eventById($result, self::HISTORY_LINK);

        $this->assertSame([], $link['changes']);
        if (!isset($link['related'])) {
            self::fail('関連付けの相手が返らなかった');
        }
        $related = $link['related'];
        $this->assertSame('Accounts', $related['module']);
        $this->assertSame(self::LINKED_ACCOUNT_ID, $related['recordId']);
        $this->assertSame('SPECTEST_関連先', $related['label']);
        $this->assertStringContainsString('record=' . self::LINKED_ACCOUNT_ID, $related['url'] ?? '');
    }

    public function test_変更したユーザーの情報を返す(): void
    {
        $result = $this->callApi('Accounts', (string) self::ACCOUNT_ID);

        $this->assertSame([(string) self::ADMIN_USER_ID], array_map('strval', array_keys($result['users'])));
        // JSON の数値文字列キーはデコード時に整数キーになる
        $this->assertArrayHasKey(self::ADMIN_USER_ID, $result['users']);
        $admin = $result['users'][self::ADMIN_USER_ID];
        $this->assertSame(self::ADMIN_USER_ID, $admin['id']);
        $this->assertNotSame('', $admin['name']);
    }

    public function test_親レコードの閲覧権限を要求する(): void
    {
        $api = new ModTracker_GetHistory_Api();

        $this->assertTrue($api->loginRequired());
        $this->assertSame(
            [['module_parameter' => 'parent_module', 'action' => 'DetailView', 'record_parameter' => 'parent_id']],
            $api->requiresPermission(new Vtiger_Request([]))
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int}>
     */
    public static function invalidRequestProvider(): iterable
    {
        yield 'モジュール名が不正' => ['Accounts;DROP', (string) self::ACCOUNT_ID, 400];
        // 数字以外を含む ID は Vtiger_Request の入力検証で先に弾かれるため、API 自身の検証は空文字で確かめる
        yield 'レコードIDが空' => ['Accounts', '', 400];
        yield '存在しないモジュール' => ['SpecTestNoSuchModule', (string) self::ACCOUNT_ID, 404];
        yield '存在しないレコード' => ['Accounts', (string) self::MISSING_RECORD_ID, 404];
        yield 'モジュールとレコードが一致しない' => ['Contacts', (string) self::ACCOUNT_ID, 404];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidRequestProvider')]
    public function test_不正な指定はエラーにする(string $module, string $recordId, int $expectedStatus): void
    {
        try {
            $this->callApi($module, $recordId);
            self::fail('ApiException が送出されなかった');
        } catch (ApiException $e) {
            $this->assertSame($expectedStatus, $e->getHttpStatusCode());
        }
    }

    // ---- ヘルパ ---------------------------------------------------------

    /**
     * API を呼び、レスポンスの JSON を連想配列で返す
     *
     * @return HistoryResponse
     */
    private function callApi(string $module, string $recordId): array
    {
        $api = new ModTracker_GetHistory_Api();
        $response = $api->process(new Vtiger_Request([
            'module' => 'ModTracker',
            'api' => 'GetHistory',
            'parent_module' => $module,
            'parent_id' => $recordId,
        ]));
        $this->assertInstanceOf(Vtiger_Response::class, $response);

        // クライアントが受け取るのと同じく JSON を経由させる（object で返す users / fields も配列になる）
        $json = json_encode($response->getResult(), JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $this->assertSame(
            ['success', 'now', 'format', 'events', 'users', 'fields'],
            array_keys($decoded),
            'レスポンスの項目は src/types/history.ts の GetHistoryResponse と一致する'
        );

        /** @var HistoryResponse $decoded */
        return $decoded;
    }

    /**
     * @param HistoryResponse $result
     * @return Event
     */
    private function eventById(array $result, int $id): array
    {
        foreach ($result['events'] as $event) {
            if ($event['id'] === $id) {
                return $event;
            }
        }
        self::fail("履歴 $id が返らなかった");
    }

    /**
     * @param Event $event
     * @return Change
     */
    private function changeByField(array $event, string $field): array
    {
        foreach ($event['changes'] as $change) {
            if ($change['field'] === $field) {
                return $change;
            }
        }
        self::fail("項目 $field の変更が返らなかった");
    }

    private function userCurrencySymbol(): string
    {
        $currency = getCurrencySymbolandCRate(Users_Record_Model::getCurrentUserModel()->get('currency_id'));
        $this->assertIsArray($currency);
        $this->assertIsString($currency['symbol']);

        return html_entity_decode($currency['symbol'], ENT_QUOTES, 'UTF-8');
    }

    /**
     * 管理者でログインし、表示書式の設定を差し替える
     *
     * Users_Record_Model::getCurrentUserModel() は更新日時が同じならキャッシュを返すため、
     * 設定を変えるたびに更新日時もずらす。
     *
     * @param array<string, string> $preferences
     */
    private function loginAsAdmin(array $preferences): void
    {
        static $sequence = 0;
        $user = CRMEntity::getInstance('Users');
        $user->retrieveCurrentUserInfoFromFile(self::ADMIN_USER_ID);
        $preferences['modifiedtime'] = sprintf('2000-01-01 00:00:%02d', ++$sequence % 60);
        foreach ($preferences as $key => $value) {
            $user->$key = $value;
            $user->column_fields[$key] = $value;
        }
        $GLOBALS['current_user'] = $user;
    }

    private function createAccount(int $id, string $name): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->pquery(
            'INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, modifiedby, setype, createdtime, modifiedtime, deleted, label)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)',
            [$id, self::ADMIN_USER_ID, self::ADMIN_USER_ID, self::ADMIN_USER_ID, 'Accounts', $now, $now, $name]
        );
        $this->db->pquery(
            'INSERT INTO vtiger_account (accountid, accountname, account_no, smcreatorid, smownerid, modifiedby, createdtime, modifiedtime, deleted, label)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)',
            [$id, $name, 'SPECTEST' . $id, self::ADMIN_USER_ID, self::ADMIN_USER_ID, self::ADMIN_USER_ID, $now, $now, $name]
        );
        $this->db->pquery('INSERT INTO vtiger_accountbillads (accountaddressid) VALUES (?)', [$id]);
        $this->db->pquery('INSERT INTO vtiger_accountshipads (accountaddressid) VALUES (?)', [$id]);
        $this->db->pquery('INSERT INTO vtiger_accountscf (accountid) VALUES (?)', [$id]);
    }

    private function insertFixtureHistory(): void
    {
        // 作成（record_id などの内部管理用の項目と、存在しない項目を混ぜる）
        $this->insertBasic(self::HISTORY_CREATE, '2026-09-01 10:12:00', ModTracker_Record_Model::CREATE);
        $this->insertDetail(self::HISTORY_CREATE, 'accountname', null, self::LABEL);
        $this->insertDetail(self::HISTORY_CREATE, 'phone', null, '03-1234-5678');
        $this->insertDetail(self::HISTORY_CREATE, 'record_id', null, (string) self::ACCOUNT_ID);
        $this->insertDetail(self::HISTORY_CREATE, 'record_module', null, 'Accounts');
        $this->insertDetail(self::HISTORY_CREATE, 'spectest_no_such_field', null, 'x');

        // 更新（並び替えを確かめるため、詳細画面の並びと逆順に投入する）
        $this->insertBasic(self::HISTORY_UPDATE, '2026-10-15 11:48:00', ModTracker_Record_Model::UPDATE);
        $this->insertDetail(self::HISTORY_UPDATE, 'modifiedby', '5', (string) self::ADMIN_USER_ID);
        $this->insertDetail(self::HISTORY_UPDATE, 'annual_revenue', '500000000.00000000', '720000000.00000000');
        $this->insertDetail(self::HISTORY_UPDATE, 'phone', '03-1234-5678', '03-9876-5432');

        // 内部管理用の項目だけの更新（返さない）
        $this->insertBasic(self::HISTORY_IGNORED_ONLY, '2026-10-20 09:00:00', ModTracker_Record_Model::UPDATE);
        $this->insertDetail(self::HISTORY_IGNORED_ONLY, 'label', 'a', 'b');

        // 関連付け（存在する相手と、存在しない相手）
        $this->insertBasic(self::HISTORY_LINK, '2026-11-01 09:30:00', ModTracker_Record_Model::LINK);
        $this->insertRelation(self::HISTORY_LINK, 'Accounts', self::LINKED_ACCOUNT_ID, '2026-11-01 09:30:00');
        $this->insertBasic(self::HISTORY_LINK_MISSING, '2026-11-01 09:40:00', ModTracker_Record_Model::LINK);
        $this->insertRelation(self::HISTORY_LINK_MISSING, 'Accounts', self::MISSING_RECORD_ID, '2026-11-01 09:40:00');
    }

    private function insertBasic(int $id, string $changedOn, int $status): void
    {
        $this->db->pquery(
            'INSERT INTO vtiger_modtracker_basic (id, crmid, module, whodid, changedon, status) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, self::ACCOUNT_ID, 'Accounts', self::ADMIN_USER_ID, $changedOn, $status]
        );
    }

    private function insertDetail(int $id, string $fieldName, ?string $before, ?string $after): void
    {
        $this->db->pquery(
            'INSERT INTO vtiger_modtracker_detail (id, fieldname, prevalue, postvalue) VALUES (?, ?, ?, ?)',
            [$id, $fieldName, $before, $after]
        );
    }

    private function insertRelation(int $id, string $targetModule, int $targetId, string $changedOn): void
    {
        $this->db->pquery(
            'INSERT INTO vtiger_modtracker_relations (id, targetmodule, targetid, changedon) VALUES (?, ?, ?, ?)',
            [$id, $targetModule, $targetId, $changedOn]
        );
    }

    private function cleanUp(): void
    {
        $historyIds = [
            self::HISTORY_CREATE,
            self::HISTORY_UPDATE,
            self::HISTORY_IGNORED_ONLY,
            self::HISTORY_LINK,
            self::HISTORY_LINK_MISSING,
        ];
        $marks = generateQuestionMarks($historyIds);
        $this->db->pquery("DELETE FROM vtiger_modtracker_detail WHERE id IN ($marks)", $historyIds);
        $this->db->pquery("DELETE FROM vtiger_modtracker_relations WHERE id IN ($marks)", $historyIds);
        $this->db->pquery("DELETE FROM vtiger_modtracker_basic WHERE id IN ($marks)", $historyIds);

        foreach ([self::ACCOUNT_ID, self::LINKED_ACCOUNT_ID] as $id) {
            $this->db->pquery('DELETE FROM vtiger_accountscf WHERE accountid = ?', [$id]);
            $this->db->pquery('DELETE FROM vtiger_accountshipads WHERE accountaddressid = ?', [$id]);
            $this->db->pquery('DELETE FROM vtiger_accountbillads WHERE accountaddressid = ?', [$id]);
            $this->db->pquery('DELETE FROM vtiger_account WHERE accountid = ?', [$id]);
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$id]);
        }
    }
}
