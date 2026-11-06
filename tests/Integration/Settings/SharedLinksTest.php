<?php

declare(strict_types=1);

namespace Tests\Integration\Settings;

use ApiForbiddenException;
use ApiNotFoundException;
use CRMEntity;
use Migration20261106093622_AddSharedLinksWidget;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Settings_SharedLinks_Module_Model as LinkModel;
use Settings_SharedLinks_SharedLinkAPI_Api;
use Users_Privileges_Model;
use Users_Record_Model;
use Vtiger_Request;

/**
 * 共有リンクの結合テスト（テスト用 DB を使う）
 *
 * 仕様: docs/specs/shared_links_widget.md
 *   3.4 公開範囲 / 4 ユーザー情報の差し込み / 6.4 権限 / 7 データ構造 / 8.1 API
 *
 * テストで作るリンクのタイトルには接頭辞 SPECTEST_ を付け、tearDown() で消す。
 */
final class SharedLinksTest extends TestCase
{
    private const PREFIX = 'SPECTEST_';
    private const ADMIN_USER_ID = 1;

    private static bool $initialized = false;

    private PearDatabase $db;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::boot();
        self::requireTestDatabase();
        // テスト用 DB にテーブル・ウィジェット・設定メニューを用意する
        self::runMigration();
    }

    private static function boot(): void
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
        require_once $root . '/modules/Users/models/Record.php';
        require_once $root . '/modules/Settings/Vtiger/models/Module.php';
        require_once $root . '/vtlib/Vtiger/Module.php';
        require_once $root . '/setup/migration/scripts/20261106093622_add_shared_links_widget.php';
        self::$initialized = true;
    }

    /** マイグレーションを実行する（ログの出力は捨てる） */
    private static function runMigration(): void
    {
        ob_start();
        try {
            (new Migration20261106093622_AddSharedLinksWidget())->process();
        } finally {
            ob_end_clean();
        }
    }

    private static function requireTestDatabase(): void
    {
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT DATABASE() AS dbname', []);
            $connected = $result !== false ? (string) $db->query_result($result, 0, 'dbname') : '';
        } catch (\Throwable $e) {
            $connected = '';
        }
        if ($connected === '') {
            self::markTestSkipped('テスト用DBに接続できないため飛ばします（tests/README.md のテスト用DBを参照）');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::ADMIN_USER_ID);
        $this->cleanLinks();
    }

    protected function tearDown(): void
    {
        $this->cleanLinks();
        $this->loginAs(self::ADMIN_USER_ID);
        parent::tearDown();
    }

    private function cleanLinks(): void
    {
        $ids = [];
        $result = $this->db->pquery('SELECT id FROM vtiger_shared_links WHERE title LIKE ?', [self::PREFIX . '%']);
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $ids[] = (int) $this->db->query_result($result, $i, 'id');
        }
        foreach ($ids as $id) {
            LinkModel::deleteLink($id);
        }
    }

    private function loginAs(int $userId): Users_Record_Model
    {
        $user = CRMEntity::getInstance('Users');
        $user->id = $userId;
        $user->retrieve_entity_info($userId, 'Users');
        $user->column_fields = (array) $user->column_fields;
        $GLOBALS['current_user'] = $user;
        vglobal('current_user', $user);

        $model = new Users_Record_Model();
        $model->setData($user->column_fields);
        $model->setModule('Users');
        $model->setEntity($user);
        foreach (get_object_vars($user) as $key => $value) {
            if (!is_object($value)) {
                $model->$key = $value;
            }
        }
        Users_Record_Model::$currentUserModels[$userId] = $model;
        return $model;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function linkInput(array $overrides = []): array
    {
        return array_merge([
            'title' => self::PREFIX . 'link',
            'url' => 'https://kintai.example.com/login?lang=ja',
            'description' => '',
            'method' => 'GET',
            'open_mode' => 'tab',
            'window_width' => null,
            'window_height' => null,
            'noreferrer' => false,
            'active' => true,
            'visibility' => 'all',
            'params' => [],
            'targets' => [],
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createLink(array $overrides = []): int
    {
        return LinkModel::saveLink(\Settings_SharedLinks_Rules_Model::normalize($this->linkInput($overrides)));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findLink(int $id): ?array
    {
        foreach (LinkModel::getAllLinks() as $link) {
            if ($link['id'] === $id) {
                return $link;
            }
        }
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findVisible(int $id): ?array
    {
        foreach (LinkModel::getVisibleLinksForCurrentUser() as $link) {
            if ($link['id'] === $id) {
                return $link;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function callApi(string $mode, array $params = []): array
    {
        $values = array_merge(
            ['module' => 'SharedLinks', 'parent' => 'Settings', 'api' => 'SharedLinkAPI', 'mode' => $mode],
            $params
        );
        $request = new Vtiger_Request($values, $values);
        $api = new Settings_SharedLinks_SharedLinkAPI_Api();
        $api->checkPermission($request);
        $result = $api->process($request)->getResult();
        $this->assertIsArray($result);
        return $result;
    }

    /** 管理者でない有効なユーザーの ID。いなければテストを飛ばす */
    private function nonAdminUserId(): int
    {
        $result = $this->db->pquery(
            "SELECT id FROM vtiger_users WHERE is_admin = 'off' AND status = 'Active' AND deleted = 0 ORDER BY id LIMIT 1",
            []
        );
        if ($this->db->num_rows($result) === 0) {
            $this->markTestSkipped('テスト用DBに管理者でない有効なユーザーがいないため飛ばします');
        }
        return (int) $this->db->query_result($result, 0, 'id');
    }

    // ---- マイグレーション --------------------------------------------

    public function test_マイグレーションは2回実行しても重複しない(): void
    {
        self::runMigration();

        foreach (['vtiger_shared_links', 'vtiger_shared_link_params', 'vtiger_shared_link_targets'] as $table) {
            $result = $this->db->pquery('SHOW TABLES LIKE ?', [$table]);
            $this->assertSame(1, $this->db->num_rows($result), $table);
            $collation = $this->db->pquery(
                'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );
            $this->assertSame('utf8mb4_general_ci', $this->db->query_result($collation, 0, 'table_collation'), $table);
        }

        $links = $this->db->pquery(
            "SELECT tabid, linkurl FROM vtiger_links WHERE linktype = 'DASHBOARDWIDGET' AND linklabel = 'Shared Links'",
            []
        );
        $this->assertSame(1, $this->db->num_rows($links));
        $this->assertSame((string) getTabid('Home'), (string) $this->db->query_result($links, 0, 'tabid'));
        $this->assertSame(
            'index.php?module=Home&view=ShowWidget&name=SharedLinks',
            decode_html((string) $this->db->query_result($links, 0, 'linkurl'))
        );

        $menu = $this->db->pquery(
            'SELECT f.linkto, b.label FROM vtiger_settings_field f'
            . ' INNER JOIN vtiger_settings_blocks b ON b.blockid = f.blockid WHERE f.name = ?',
            ['LBL_SHARED_LINKS']
        );
        $this->assertSame(1, $this->db->num_rows($menu));
        $this->assertSame('LBL_CONFIGURATION', $this->db->query_result($menu, 0, 'label'));
    }

    public function test_マイグレーションは採番が遅れていてもウィジェットを登録できる(): void
    {
        // 過去のマイグレーションが MAX(linkid)+1 で直接 INSERT し、採番が遅れた状態を作る
        $this->db->pquery("DELETE FROM vtiger_links WHERE linktype = 'DASHBOARDWIDGET' AND linklabel = 'Shared Links'", []);
        $maxResult = $this->db->pquery('SELECT MAX(linkid) AS max_id FROM vtiger_links', []);
        $maxId = (int) $this->db->query_result($maxResult, 0, 'max_id');
        $this->db->pquery('UPDATE vtiger_links_seq SET id = ?', [$maxId - 1]);

        self::runMigration();

        $links = $this->db->pquery(
            "SELECT linkid FROM vtiger_links WHERE linktype = 'DASHBOARDWIDGET' AND linklabel = 'Shared Links'",
            []
        );
        $this->assertSame(1, $this->db->num_rows($links));
        $this->assertGreaterThan($maxId, (int) $this->db->query_result($links, 0, 'linkid'));
        $seq = $this->db->pquery('SELECT id FROM vtiger_links_seq', []);
        $this->assertGreaterThan($maxId, (int) $this->db->query_result($seq, 0, 'id'));
    }

    // ---- 保存・更新・削除・並び替え -----------------------------------

    public function test_保存した内容をそのまま読み出せる(): void
    {
        $id = $this->createLink([
            'url' => 'https://a.example.com/x?a=1&b=<2>',
            'description' => "1行目\n2行目",
            'method' => 'POST',
            'open_mode' => 'window',
            'window_width' => 800,
            'window_height' => 600,
            'noreferrer' => true,
            'visibility' => 'targets',
            'params' => [
                ['name' => 'from', 'value_type' => 'fixed', 'value' => 'crm & "co"'],
                ['name' => 'mail', 'value_type' => 'user', 'value' => 'email1'],
            ],
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);

        $link = $this->findLink($id);
        $this->assertNotNull($link);
        // 記号をエンティティ化せずに保存している（URL の & が壊れない）
        $this->assertSame('https://a.example.com/x?a=1&b=<2>', $link['url']);
        $this->assertSame("1行目\n2行目", $link['description']);
        $this->assertSame('POST', $link['method']);
        $this->assertSame('window', $link['open_mode']);
        $this->assertSame(800, $link['window_width']);
        $this->assertSame(600, $link['window_height']);
        $this->assertTrue($link['noreferrer']);
        $this->assertTrue($link['active']);
        $this->assertSame([
            ['name' => 'from', 'value_type' => 'fixed', 'value' => 'crm & "co"'],
            ['name' => 'mail', 'value_type' => 'user', 'value' => 'email1'],
        ], $link['params']);
        $this->assertSame([['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]], $link['targets']);
    }

    public function test_更新するとパラメータと対象をまとめて置き換える(): void
    {
        $id = $this->createLink([
            'visibility' => 'targets',
            'params' => [['name' => 'a', 'value_type' => 'fixed', 'value' => '1']],
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);

        LinkModel::saveLink(\Settings_SharedLinks_Rules_Model::normalize($this->linkInput([
            'title' => self::PREFIX . 'renamed',
            'params' => [['name' => 'b', 'value_type' => 'fixed', 'value' => '2']],
            'visibility' => 'all',
        ])), $id);

        $link = $this->findLink($id);
        $this->assertNotNull($link);
        $this->assertSame(self::PREFIX . 'renamed', $link['title']);
        $this->assertSame([['name' => 'b', 'value_type' => 'fixed', 'value' => '2']], $link['params']);
        $this->assertSame('all', $link['visibility']);
        $this->assertSame([], $link['targets']);
    }

    public function test_削除するとパラメータと対象も消える(): void
    {
        $id = $this->createLink([
            'visibility' => 'targets',
            'params' => [['name' => 'a', 'value_type' => 'fixed', 'value' => '1']],
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);

        LinkModel::deleteLink($id);

        $this->assertNull($this->findLink($id));
        foreach (['vtiger_shared_link_params', 'vtiger_shared_link_targets'] as $table) {
            $result = $this->db->pquery("SELECT 1 FROM $table WHERE linkid = ?", [$id]);
            $this->assertSame(0, $this->db->num_rows($result), $table);
        }
    }

    public function test_新しいリンクは末尾に並び並び替えで順序が変わる(): void
    {
        $first = $this->createLink(['title' => self::PREFIX . '1']);
        $second = $this->createLink(['title' => self::PREFIX . '2']);
        $third = $this->createLink(['title' => self::PREFIX . '3']);

        $order = fn (): array => array_values(array_filter(
            array_map(fn ($l) => $l['id'], LinkModel::getAllLinks()),
            fn ($id) => in_array($id, [$first, $second, $third], true)
        ));
        $this->assertSame([$first, $second, $third], $order());

        LinkModel::reorder([$third, $first, $second]);
        $this->assertSame([$third, $first, $second], $order());
    }

    // ---- ウィジェットに出すリンク ------------------------------------

    public function test_無効のリンクはウィジェットに出ない(): void
    {
        $active = $this->createLink(['title' => self::PREFIX . 'on']);
        $inactive = $this->createLink(['title' => self::PREFIX . 'off', 'active' => false]);

        $this->assertNotNull($this->findVisible($active));
        $this->assertNull($this->findVisible($inactive));
    }

    public function test_公開範囲の役割とユーザーで表示が切り替わる(): void
    {
        $privileges = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $roleId = (string) $privileges->get('roleid');
        $chain = explode('::', (string) $privileges->get('parent_role_seq'));
        $this->assertNotSame('', $roleId, '管理者の役割が取れない');
        $parentRole = count($chain) >= 2 ? $chain[count($chain) - 2] : null;

        $sameRole = $this->createLink([
            'visibility' => 'targets', 'targets' => [['type' => 'Roles', 'id' => $roleId]],
        ]);
        $otherUser = $this->createLink([
            'visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => '999999']],
        ]);
        $me = $this->createLink([
            'visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);

        $this->assertNotNull($this->findVisible($sameRole), '自分の役割');
        $this->assertNull($this->findVisible($otherUser), '他のユーザーだけ');
        $this->assertNotNull($this->findVisible($me), '自分');

        if ($parentRole !== null) {
            $parentOnly = $this->createLink([
                'visibility' => 'targets', 'targets' => [['type' => 'Roles', 'id' => $parentRole]],
            ]);
            $parentAndBelow = $this->createLink([
                'visibility' => 'targets', 'targets' => [['type' => 'RoleAndSubordinates', 'id' => $parentRole]],
            ]);
            $this->assertNull($this->findVisible($parentOnly), '上位の役割（下位を含まない）');
            $this->assertNotNull($this->findVisible($parentAndBelow), '上位の役割（下位を含む）');
        }
    }

    public function test_管理者でも公開範囲外のリンクは出ない(): void
    {
        $this->assertTrue(Users_Record_Model::getCurrentUserModel()->isAdminUser());
        $id = $this->createLink([
            'visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => '999999']],
        ]);

        $this->assertNull($this->findVisible($id));
    }

    public function test_GETはユーザー情報を差し込んだURLを返す(): void
    {
        $user = Users_Record_Model::getCurrentUserModel();
        $id = $this->createLink([
            'url' => 'https://kintai.example.com/login?lang=ja#top',
            'params' => [
                ['name' => 'uid', 'value_type' => 'user', 'value' => 'id'],
                ['name' => 'login', 'value_type' => 'user', 'value' => 'user_name'],
                ['name' => 'name', 'value_type' => 'user', 'value' => 'full_name'],
                ['name' => 'from', 'value_type' => 'fixed', 'value' => 'crm'],
            ],
        ]);

        $link = $this->findVisible($id);
        $this->assertNotNull($link);
        $fullName = \Settings_SharedLinks_Rules_Model::fullName(
            decode_html((string) $user->get('last_name')),
            decode_html((string) $user->get('first_name'))
        );
        $this->assertSame(
            'https://kintai.example.com/login?lang=ja'
            . '&uid=' . self::ADMIN_USER_ID
            . '&login=' . rawurlencode(decode_html((string) $user->get('user_name')))
            . '&name=' . rawurlencode($fullName)
            . '&from=crm#top',
            $link['url']
        );
        $this->assertSame([], $link['params']);
    }

    public function test_POSTはURLをそのままにしてパラメータを解決して返す(): void
    {
        $id = $this->createLink([
            'method' => 'POST',
            'params' => [
                ['name' => 'uid', 'value_type' => 'user', 'value' => 'id'],
                ['name' => 'gone', 'value_type' => 'user', 'value' => 'cf_not_exists'],
            ],
        ]);

        $link = $this->findVisible($id);
        $this->assertNotNull($link);
        $this->assertSame('https://kintai.example.com/login?lang=ja', $link['url']);
        $this->assertSame([
            ['name' => 'uid', 'value' => (string) self::ADMIN_USER_ID],
            // 削除された項目は空文字を送る
            ['name' => 'gone', 'value' => ''],
        ], $link['params']);
    }

    // ---- 差し込める項目・公開範囲の選択肢 ------------------------------

    public function test_差し込める項目にパスワードなどが含まれない(): void
    {
        $fields = LinkModel::getInsertableUserFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('full_name', $fields);
        $this->assertArrayHasKey('user_name', $fields);
        $this->assertArrayHasKey('email1', $fields);
        foreach (\Settings_SharedLinks_Rules_Model::EXCLUDED_USER_FIELDS as $excluded) {
            $this->assertArrayNotHasKey($excluded, $fields);
        }
        $passwordFields = $this->db->pquery(
            'SELECT fieldname FROM vtiger_field WHERE tabid = ? AND uitype = 99',
            [getTabid('Users')]
        );
        for ($i = 0; $i < $this->db->num_rows($passwordFields); $i++) {
            $this->assertArrayNotHasKey($this->db->query_result($passwordFields, $i, 'fieldname'), $fields);
        }
    }

    public function test_役割は親の後ろに子が並ぶ(): void
    {
        $roles = LinkModel::getRoles();
        $this->assertNotEmpty($roles);

        $position = array_flip(array_column($roles, 'id'));
        $result = $this->db->pquery('SELECT roleid, parentrole FROM vtiger_role', []);
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $chain = explode('::', (string) $this->db->query_result($result, $i, 'parentrole'));
            $roleId = (string) $this->db->query_result($result, $i, 'roleid');
            if (count($chain) >= 2) {
                $parent = $chain[count($chain) - 2];
                $this->assertLessThan($position[$roleId], $position[$parent], "$parent → $roleId");
            }
        }
    }

    // ---- API ----------------------------------------------------------

    public function test_API_管理者でなければ拒否する(): void
    {
        $this->loginAs($this->nonAdminUserId());

        $this->expectException(ApiForbiddenException::class);
        $this->callApi('list');
    }

    public function test_API_入力に誤りがあれば保存せず項目ごとのエラーを返す(): void
    {
        $result = $this->callApi('save', ['link' => json_encode($this->linkInput([
            'title' => '',
            'url' => 'javascript:alert(1)',
            'params' => [['name' => 'a', 'value_type' => 'user', 'value' => 'user_password']],
            'visibility' => 'targets',
            'targets' => [['type' => 'Groups', 'id' => '999999']],
        ]))]);

        $this->assertFalse($result['saved']);
        $this->assertSame(
            ['title', 'url', 'params.0.value', 'targets.0'],
            array_keys($result['errors'])
        );
        // 言語ファイルのキーではなく文言を返す
        $this->assertStringNotContainsString('LBL_', implode('', $result['errors']));
        $count = $this->db->pquery("SELECT 1 FROM vtiger_shared_links WHERE url = 'javascript:alert(1)'", []);
        $this->assertSame(0, $this->db->num_rows($count));
    }

    public function test_API_保存して一覧に出す(): void
    {
        $result = $this->callApi('save', ['link' => json_encode($this->linkInput([
            'url' => 'https://a.example.com/?x=1&y=2',
            'visibility' => 'targets',
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
            'params' => [['name' => 'm', 'value_type' => 'user', 'value' => 'email1']],
        ]))]);
        $this->assertTrue($result['saved']);
        $id = $result['id'];

        $list = $this->callApi('list');
        $saved = array_values(array_filter($list['links'], fn ($l) => $l['id'] === $id))[0] ?? null;
        $this->assertNotNull($saved);
        $this->assertSame('https://a.example.com/?x=1&y=2', $saved['url']);
        $this->assertFalse($saved['has_warning']);
        $this->assertFalse($saved['targets'][0]['deleted']);
        $this->assertNotNull($saved['targets'][0]['name']);
        $this->assertFalse($saved['params'][0]['missing']);
        $this->assertNotNull($saved['params'][0]['label']);
    }

    public function test_API_削除された対象や項目を含むリンクには警告を付ける(): void
    {
        $id = $this->createLink([
            'visibility' => 'targets',
            'targets' => [['type' => 'Groups', 'id' => '999999']],
            'params' => [['name' => 'g', 'value_type' => 'user', 'value' => 'cf_not_exists']],
        ]);

        $list = $this->callApi('list');
        $saved = array_values(array_filter($list['links'], fn ($l) => $l['id'] === $id))[0] ?? null;
        $this->assertNotNull($saved);
        $this->assertTrue($saved['has_warning']);
        $this->assertTrue($saved['targets'][0]['deleted']);
        $this->assertNull($saved['targets'][0]['name']);
        $this->assertTrue($saved['params'][0]['missing']);
    }

    public function test_API_保存済みの対象は無効なユーザーでも残せるが新しくは選べない(): void
    {
        // 管理者でないユーザーを一時的に無効にする（最後に元へ戻す）
        $userId = $this->nonAdminUserId();
        $this->db->pquery("UPDATE vtiger_users SET status = 'Inactive' WHERE id = ?", [$userId]);
        try {
            $input = $this->linkInput([
                'visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => (string) $userId]],
            ]);

            $created = $this->callApi('save', ['link' => json_encode($input)]);
            $this->assertFalse($created['saved']);
            $this->assertArrayHasKey('targets.0', $created['errors']);

            $id = $this->createLink($input);
            $updated = $this->callApi('save', ['id' => (string) $id, 'link' => json_encode($input)]);
            $this->assertTrue($updated['saved']);
        } finally {
            $this->db->pquery("UPDATE vtiger_users SET status = 'Active' WHERE id = ?", [$userId]);
        }
    }

    public function test_API_存在しないリンクの更新と削除はエラー(): void
    {
        try {
            $this->callApi('save', ['id' => '999999999', 'link' => json_encode($this->linkInput())]);
            $this->fail('更新でエラーにならない');
        } catch (ApiNotFoundException $e) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(ApiNotFoundException::class);
        $this->callApi('delete', ['id' => 'abc']);
    }

    public function test_API_更新系はPOSTでなければ受け付けない(): void
    {
        $values = ['module' => 'SharedLinks', 'parent' => 'Settings', 'api' => 'SharedLinkAPI', 'mode' => 'delete'];
        $request = new Vtiger_Request($values, $values);
        $_SERVER['REQUEST_METHOD'] = 'GET';

        try {
            $this->expectExceptionMessage('Invalid request');
            (new Settings_SharedLinks_SharedLinkAPI_Api())->validateRequest($request);
        } finally {
            unset($_SERVER['REQUEST_METHOD']);
        }
    }

    public function test_API_並び替えのIDに数字以外があればエラー(): void
    {
        $this->expectException(\ApiBadRequestException::class);
        $this->callApi('reorder', ['ids' => '1,x']);
    }
}
