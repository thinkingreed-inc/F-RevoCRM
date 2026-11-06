<?php

declare(strict_types=1);

namespace Tests\Integration\Settings;

use ApiBadRequestException;
use ApiForbiddenException;
use ApiNotFoundException;
use Settings_SharedLinks_Module_Model as LinkModel;
use Settings_SharedLinks_Rules_Model as Rules;
use Settings_SharedLinks_SharedLinkAPI_Api;
use Tests\Support\SharedLinksTestCase;
use Users_Record_Model;
use Vtiger_Request;

require_once dirname(__DIR__, 3) . '/tests/Support/SharedLinksTestCase.php';

/**
 * 共有リンクの結合テスト（モデル・API・マイグレーション。テスト用 DB を使う）
 *
 * テスト仕様書: docs/tests/SharedLinks/
 *   TS-01 入力の検証 / TS-02 公開範囲 / TS-03 値の解決と URL / TS-05 API / TS-07 マイグレーション
 * メソッド名に仕様書のケース ID を入れている。
 */
final class SharedLinksTest extends SharedLinksTestCase
{
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
        foreach (LinkModel::getVisibleLinksForCurrentUser($this->setId) as $link) {
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

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function saveViaApi(array $overrides, ?int $id = null): array
    {
        $params = ['link' => json_encode($this->linkInput($overrides)), 'set_id' => (string) $this->setId];
        if ($id !== null) {
            $params['id'] = (string) $id;
        }
        return $this->callApi('save', $params);
    }

    /**
     * @return array<string, mixed>
     */
    private function listedLink(int $id): array
    {
        foreach ($this->callApi('list')['links'] as $link) {
            if ($link['id'] === $id) {
                return $link;
            }
        }
        $this->fail("一覧にリンク $id が無い");
    }

    private function countLinks(): int
    {
        return (int) $this->db->query_result($this->db->pquery('SELECT COUNT(*) AS c FROM vtiger_shared_links', []), 0, 'c');
    }

    // ==== TS-07 マイグレーション ===========================================

    public function test_TC_MIG_002_2回実行しても重複しない(): void
    {
        $setsBefore = count(LinkModel::getSets());
        self::runMigration();
        self::runMigration();

        foreach (['vtiger_shared_link_sets', 'vtiger_shared_links', 'vtiger_shared_link_params', 'vtiger_shared_link_targets'] as $table) {
            $collation = $this->db->pquery(
                'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );
            $this->assertSame(1, $this->db->num_rows($collation), $table);
            $this->assertSame('utf8mb4_general_ci', $this->db->query_result($collation, 0, 'table_collation'), $table);
        }
        // ウィジェットがあれば最初のウィジェットを作り直さない
        $this->assertCount($setsBefore, LinkModel::getSets());

        $menu = $this->db->pquery(
            'SELECT f.linkto, b.label FROM vtiger_settings_field f'
            . ' INNER JOIN vtiger_settings_blocks b ON b.blockid = f.blockid WHERE f.name = ?',
            ['LBL_SHARED_LINKS']
        );
        $this->assertSame(1, $this->db->num_rows($menu));
        $this->assertSame('LBL_CONFIGURATION', $this->db->query_result($menu, 0, 'label'));
        $this->assertSame(
            'index.php?module=SharedLinks&parent=Settings&view=List',
            decode_html((string) $this->db->query_result($menu, 0, 'linkto'))
        );
    }

    public function test_TC_MIG_001_ウィジェットが無ければ最初のウィジェット共有リンクを作る(): void
    {
        // テストで作ったもの以外のウィジェットを一時的に退避して、空の状態を作る
        $others = array_filter(LinkModel::getSets(), fn ($set) => !str_starts_with($set['title'], self::PREFIX));
        $this->db->pquery('CREATE TEMPORARY TABLE spectest_sets SELECT * FROM vtiger_shared_link_sets', []);
        $this->db->pquery('DELETE FROM vtiger_shared_link_sets', []);
        try {
            self::runMigration();

            $sets = LinkModel::getSets();
            $this->assertCount(1, $sets);
            $this->assertSame('共有リンク', $sets[0]['title']);
            $link = $this->db->pquery(
                'SELECT tabid, linktype, linklabel, linkurl FROM vtiger_links WHERE linkid = ?',
                [$sets[0]['linkid']]
            );
            $this->assertSame([
                'tabid' => (string) getTabid('Home'),
                'linktype' => 'DASHBOARDWIDGET',
                'linklabel' => '共有リンク',
                'linkurl' => 'index.php?module=Home&view=ShowWidget&name=SharedLinks',
            ], array_map('decode_html', array_intersect_key(
                $this->db->query_result_rowdata($link, 0),
                array_flip(['tabid', 'linktype', 'linklabel', 'linkurl'])
            )));
        } finally {
            // 作られたウィジェットを消し、退避したものを戻す
            foreach (LinkModel::getSets() as $set) {
                $this->db->pquery('DELETE FROM vtiger_links WHERE linkid = ?', [$set['linkid']]);
            }
            $this->db->pquery('DELETE FROM vtiger_shared_link_sets', []);
            $this->db->pquery('INSERT INTO vtiger_shared_link_sets SELECT * FROM spectest_sets', []);
            $this->db->pquery('DROP TEMPORARY TABLE spectest_sets', []);
        }
        $this->assertCount(count($others) + 1, LinkModel::getSets());
    }

    public function test_TC_MIG_003_採番が遅れていてもウィジェットを作れる(): void
    {
        // 過去のマイグレーションが MAX(linkid)+1 で直接 INSERT し、採番が遅れた状態を作る
        $maxId = (int) $this->db->query_result($this->db->pquery('SELECT MAX(linkid) AS m FROM vtiger_links', []), 0, 'm');
        $this->db->pquery('UPDATE vtiger_links_seq SET id = ?', [$maxId - 1]);

        $setId = $this->createSet('採番');

        $set = LinkModel::getSet($setId);
        $this->assertNotNull($set);
        $this->assertGreaterThan($maxId, $set['linkid']);
        $count = $this->db->query_result(
            $this->db->pquery('SELECT COUNT(*) AS c FROM vtiger_links WHERE linkid = ?', [$set['linkid']]),
            0,
            'c'
        );
        $this->assertSame('1', (string) $count);
    }

    // ==== TS-05 API: 権限と要求の種類 =====================================

    public function test_TC_API_001_管理者でなければすべてのmodeを拒否しDBを変えない(): void
    {
        $id = $this->createLink();
        $before = $this->countLinks();
        $this->loginAs($this->nonAdminUserId());

        $requests = [
            'info' => [],
            'list' => [],
            'save' => ['link' => json_encode($this->linkInput()), 'set_id' => (string) $this->setId],
            'save_set' => ['title' => self::PREFIX . '一般ユーザー'],
            'delete_set' => ['id' => (string) $this->setId],
            'delete' => ['id' => (string) $id],
            'reorder' => ['ids' => (string) $id],
        ];
        foreach ($requests as $mode => $params) {
            try {
                $this->callApi($mode, $params);
                $this->fail("$mode が拒否されない");
            } catch (ApiForbiddenException $e) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame($before, $this->countLinks());
        $this->assertNotNull($this->findLink($id));
    }

    public function test_TC_API_002_003_読み取りはGETで受け付け更新系はGETを拒否する(): void
    {
        $api = new Settings_SharedLinks_SharedLinkAPI_Api();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        try {
            foreach (['info', 'list'] as $mode) {
                $request = new Vtiger_Request(['mode' => $mode], ['mode' => $mode]);
                $this->assertTrue($api->validateRequest($request), $mode);
            }
            foreach (['save', 'delete', 'reorder', 'save_set', 'delete_set'] as $mode) {
                $request = new Vtiger_Request(['mode' => $mode], ['mode' => $mode]);
                try {
                    $api->validateRequest($request);
                    $this->fail("$mode が GET で受け付けられた");
                } catch (\Exception $e) {
                    $this->assertSame('Invalid request', $e->getMessage(), $mode);
                }
            }
        } finally {
            unset($_SERVER['REQUEST_METHOD']);
        }
    }

    // ==== TS-05 API: save =================================================

    public function test_TC_API_010_linkがJSONでなければ400(): void
    {
        $this->expectException(ApiBadRequestException::class);
        $this->callApi('save', ['link' => 'not-json', 'set_id' => (string) $this->setId]);
    }

    public function test_TC_API_011_034_追加すると末尾に並び一覧は無効も含め並び順で返す(): void
    {
        $ids = [];
        foreach (['1', '2', '3'] as $n) {
            $result = $this->saveViaApi(['title' => self::PREFIX . $n, 'active' => $n !== '2']);
            $this->assertTrue($result['saved']);
            $this->assertIsInt($result['id']);
            $ids[] = $result['id'];
        }

        $listed = array_values(array_filter(
            array_column($this->callApi('list')['links'], 'id'),
            fn ($id) => in_array($id, $ids, true)
        ));
        // 無効（2 件目）も含めて、追加した順に並ぶ
        $this->assertSame($ids, $listed);
    }

    public function test_TC_API_012_更新すると内容とパラメータと対象が置き換わる(): void
    {
        $id = $this->createLink([
            'visibility' => 'targets',
            'params' => [['name' => 'a', 'value_type' => 'fixed', 'value' => '1']],
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);

        $result = $this->saveViaApi([
            'title' => self::PREFIX . 'renamed',
            'method' => 'POST',
            'open_mode' => 'window',
            'window_width' => 640,
            'window_height' => 480,
            'noreferrer' => true,
            'params' => [['name' => 'b', 'value_type' => 'user', 'value' => 'email1']],
            'visibility' => 'targets',
            'targets' => [['type' => 'Roles', 'id' => 'H2']],
        ], $id);

        $this->assertSame(['saved' => true, 'id' => $id], $result);
        $link = $this->findLink($id);
        $this->assertNotNull($link);
        $this->assertSame(self::PREFIX . 'renamed', $link['title']);
        $this->assertSame(['POST', 'window', 640, 480, true], [
            $link['method'], $link['open_mode'], $link['window_width'], $link['window_height'], $link['noreferrer'],
        ]);
        $this->assertSame([['name' => 'b', 'value_type' => 'user', 'value' => 'email1']], $link['params']);
        $this->assertSame([['type' => 'Roles', 'id' => 'H2']], $link['targets']);
    }

    public function test_TC_API_013_014_存在しないIDや数字でないIDの更新は404(): void
    {
        foreach (['999999999', 'abc', '1 OR 1=1'] as $id) {
            try {
                $this->callApi('save', [
                    'id' => $id, 'link' => json_encode($this->linkInput()), 'set_id' => (string) $this->setId,
                ]);
                $this->fail("id=$id がエラーにならない");
            } catch (ApiNotFoundException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_TC_API_015_TC_VAL_063_入力に誤りがあれば保存せず項目ごとのエラーを返す(): void
    {
        $before = $this->countLinks();
        $result = $this->saveViaApi([
            'title' => '',
            'url' => 'javascript:alert(1)',
            'params' => [['name' => 'a', 'value_type' => 'user', 'value' => 'user_password']],
            'visibility' => 'targets',
            'targets' => [['type' => 'Groups', 'id' => '999999']],
        ]);

        $this->assertFalse($result['saved']);
        $this->assertSame(['title', 'url', 'params.0.value', 'targets.0'], array_keys($result['errors']));
        // 言語ファイルのキーではなく文言を返す
        foreach ($result['errors'] as $message) {
            $this->assertStringNotContainsString('LBL_', $message);
            $this->assertNotSame('', $message);
        }
        $this->assertSame($before, $this->countLinks());
    }

    public function test_TC_API_016_URLの記号を加工せずに保存して返す(): void
    {
        $url = 'https://a.example.com/?x=1&y=<2>&z="3"';
        $result = $this->saveViaApi(['url' => $url, 'title' => self::PREFIX . '<b>&amp;</b>']);
        $this->assertTrue($result['saved']);

        $listed = $this->listedLink($result['id']);
        $this->assertSame($url, $listed['url']);
        $this->assertSame(self::PREFIX . '<b>&amp;</b>', $listed['title']);
    }

    // ==== TS-01 入力の検証（DB を使うもの） ================================

    public function test_TC_VAL_050_053_有効なユーザーと存在するグループと役割は選べる(): void
    {
        $groups = $this->db->pquery('SELECT groupid FROM vtiger_groups LIMIT 1', []);
        $targets = [
            ['type' => 'Users', 'id' => (string) $this->nonAdminUserId()],
            ['type' => 'Roles', 'id' => 'H2'],
            ['type' => 'RoleAndSubordinates', 'id' => 'H2'],
        ];
        if ($this->db->num_rows($groups) > 0) {
            $targets[] = ['type' => 'Groups', 'id' => (string) $this->db->query_result($groups, 0, 'groupid')];
        }

        $result = $this->saveViaApi(['visibility' => 'targets', 'targets' => $targets]);

        $this->assertTrue($result['saved'], (string) json_encode($result));
    }

    public function test_TC_VAL_051_052_無効なユーザーは保存済みなら残せるが新しくは選べない(): void
    {
        // 管理者でないユーザーを一時的に無効にする（最後に元へ戻す）
        $userId = $this->nonAdminUserId();
        $this->db->pquery("UPDATE vtiger_users SET status = 'Inactive' WHERE id = ?", [$userId]);
        try {
            $input = ['visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => (string) $userId]]];

            $created = $this->saveViaApi($input);
            $this->assertFalse($created['saved']);
            $this->assertArrayHasKey('targets.0', $created['errors']);

            $id = $this->createLink($input);
            $this->assertTrue($this->saveViaApi($input, $id)['saved']);
        } finally {
            $this->db->pquery("UPDATE vtiger_users SET status = 'Active' WHERE id = ?", [$userId]);
        }
    }

    public function test_TC_VAL_054_存在しない役割とグループとユーザーは選べない(): void
    {
        $cases = [['Groups', '999999'], ['Roles', 'H99999'], ['RoleAndSubordinates', 'H99999'], ['Users', '999999']];
        foreach ($cases as [$type, $id]) {
            $result = $this->saveViaApi(['visibility' => 'targets', 'targets' => [['type' => $type, 'id' => $id]]]);
            $this->assertFalse($result['saved'], "$type:$id");
            $this->assertArrayHasKey('targets.0', $result['errors'], "$type:$id");
        }
    }

    // ==== TS-05 API: delete / reorder / mode ==============================

    public function test_TC_API_020_削除するとパラメータと対象も消える(): void
    {
        $id = $this->createLink([
            'visibility' => 'targets',
            'params' => [['name' => 'a', 'value_type' => 'fixed', 'value' => '1']],
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);

        $this->assertSame(['id' => $id, 'deleted' => true], $this->callApi('delete', ['id' => (string) $id]));

        $this->assertNull($this->findLink($id));
        foreach (['vtiger_shared_link_params', 'vtiger_shared_link_targets'] as $table) {
            $result = $this->db->pquery("SELECT 1 FROM $table WHERE linkid = ?", [$id]);
            $this->assertSame(0, $this->db->num_rows($result), $table);
        }
    }

    public function test_TC_API_021_存在しないIDの削除は404(): void
    {
        foreach (['999999999', 'abc', ''] as $id) {
            try {
                $this->callApi('delete', ['id' => $id]);
                $this->fail("id=$id がエラーにならない");
            } catch (ApiNotFoundException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_TC_API_022_024_並び替えは指定した順になり重複は除く(): void
    {
        $first = $this->createLink(['title' => self::PREFIX . '1']);
        $second = $this->createLink(['title' => self::PREFIX . '2']);
        $third = $this->createLink(['title' => self::PREFIX . '3']);
        $order = fn (): array => array_values(array_filter(
            array_map(fn ($l) => $l['id'], LinkModel::getAllLinks()),
            fn ($id) => in_array($id, [$first, $second, $third], true)
        ));

        $this->callApi('reorder', ['ids' => "$third,$first,$second"]);
        $this->assertSame([$third, $first, $second], $order());

        $this->callApi('reorder', ['ids' => "$second,$second,$third,$first"]);
        $this->assertSame([$second, $third, $first], $order());
    }

    public function test_TC_API_023_並び替えのIDに数字以外があれば400で何も変えない(): void
    {
        $first = $this->createLink(['title' => self::PREFIX . '1']);
        $second = $this->createLink(['title' => self::PREFIX . '2']);
        try {
            $this->callApi('reorder', ['ids' => "$second,x"]);
            $this->fail('エラーにならない');
        } catch (ApiBadRequestException $e) {
            $this->addToAssertionCount(1);
        }
        $ids = array_column(LinkModel::getAllLinks(), 'id');
        $this->assertLessThan(array_search($second, $ids, true), array_search($first, $ids, true));
    }

    public function test_TC_API_025_不明なmodeは400(): void
    {
        $this->expectException(ApiBadRequestException::class);
        $this->callApi('unknown');
    }

    // ==== TS-05 API: list の警告 ==========================================

    public function test_TC_API_030_033_削除された項目や対象を含むリンクに警告を付ける(): void
    {
        $adminTarget = ['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID];
        $deletedGroup = ['type' => 'Groups', 'id' => '999999'];
        $clean = $this->createLink([
            'visibility' => 'targets', 'targets' => [$adminTarget],
            'params' => [['name' => 'm', 'value_type' => 'user', 'value' => 'email1']],
        ]);
        $missingField = $this->createLink([
            'params' => [['name' => 'g', 'value_type' => 'user', 'value' => 'cf_not_exists']],
        ]);
        $partlyDeleted = $this->createLink(['visibility' => 'targets', 'targets' => [$adminTarget, $deletedGroup]]);
        $allDeleted = $this->createLink(['visibility' => 'targets', 'targets' => [$deletedGroup]]);

        $link = $this->listedLink($clean);                               // TC-API-030
        $this->assertFalse($link['has_warning']);
        $this->assertFalse($link['targets'][0]['deleted']);
        $this->assertNotNull($link['targets'][0]['name']);
        $this->assertFalse($link['params'][0]['missing']);
        $this->assertNotNull($link['params'][0]['label']);

        $link = $this->listedLink($missingField);                        // TC-API-031
        $this->assertTrue($link['has_warning']);
        $this->assertTrue($link['params'][0]['missing']);
        $this->assertNull($link['params'][0]['label']);

        $link = $this->listedLink($partlyDeleted);                       // TC-API-032
        $this->assertTrue($link['has_warning']);
        $this->assertSame([false, true], array_column($link['targets'], 'deleted'));

        $link = $this->listedLink($allDeleted);                          // TC-API-033
        $this->assertTrue($link['has_warning']);
        $this->assertNull($link['targets'][0]['name']);
    }

    public function test_TC_API_035_CSPで許可されていないPOSTの送り先に印を付ける(): void
    {
        $saved = $GLOBALS['csp_allowed_domains'] ?? null;
        $GLOBALS['csp_allowed_domains'] = ['form-action' => ['https://allowed.example.com']];
        try {
            $allowed = $this->createLink(['method' => 'POST', 'url' => 'https://allowed.example.com/post']);
            $blocked = $this->createLink(['method' => 'POST', 'url' => 'https://blocked.example.com/post']);
            $get = $this->createLink(['method' => 'GET', 'url' => 'https://blocked.example.com/get']);

            $this->assertFalse($this->listedLink($allowed)['post_blocked']);
            $this->assertTrue($this->listedLink($blocked)['post_blocked']);
            // GET は CSP の form-action に関係しない
            $this->assertFalse($this->listedLink($get)['post_blocked']);

            // ウィジェットに渡すデータにも付く
            $this->assertFalse($this->findVisible($allowed)['post_blocked']);
            $this->assertTrue($this->findVisible($blocked)['post_blocked']);
            $this->assertFalse($this->findVisible($get)['post_blocked']);
        } finally {
            $GLOBALS['csp_allowed_domains'] = $saved;
        }
    }

    // ==== TS-08 ウィジェット（リンクのまとまり） ==============================

    /**
     * @return array<string, string>
     */
    private function widgetLink(int $setId): array
    {
        $set = LinkModel::getSet($setId);
        $this->assertNotNull($set);
        $row = $this->db->query_result_rowdata(
            $this->db->pquery('SELECT * FROM vtiger_links WHERE linkid = ?', [$set['linkid']]),
            0
        );
        return array_map('decode_html', array_map('strval', $row));
    }

    public function test_TC_SET_001_作ると末尾に並びウィジェットの追加に出る(): void
    {
        $result = $this->callApi('save_set', ['title' => '  ' . self::PREFIX . '社内システム  ']);

        $this->assertTrue($result['saved']);
        $this->assertSame(self::PREFIX . '社内システム', $result['title']);
        $sets = LinkModel::getSets();
        $this->assertSame($result['id'], end($sets)['id']);
        $link = $this->widgetLink($result['id']);
        $this->assertSame([(string) getTabid('Home'), 'DASHBOARDWIDGET', self::PREFIX . '社内システム', LinkModel::WIDGET_URL], [
            $link['tabid'], $link['linktype'], $link['linklabel'], $link['linkurl'],
        ]);
        $listed = array_column($this->callApi('list')['sets'], 'title');
        $this->assertContains(self::PREFIX . '社内システム', $listed);
    }

    public function test_TC_SET_002_見出しを変えるとウィジェットの追加の名前も変わる(): void
    {
        $linkId = LinkModel::getSet($this->setId)['linkid'];

        $result = $this->callApi('save_set', ['id' => (string) $this->setId, 'title' => self::PREFIX . '申請']);

        $this->assertSame(['saved' => true, 'id' => $this->setId, 'title' => self::PREFIX . '申請'], $result);
        $this->assertSame(self::PREFIX . '申請', LinkModel::getSet($this->setId)['title']);
        $this->assertSame(self::PREFIX . '申請', $this->widgetLink($this->setId)['linklabel']);
        // 登録（linkid）はそのまま（各ユーザーのダッシュボードの配置が残る）
        $this->assertSame($linkId, LinkModel::getSet($this->setId)['linkid']);
    }

    public function test_TC_SET_003_ほかのウィジェットと同じ見出しは付けられない(): void
    {
        $other = $this->createSet('重なり');
        $otherTitle = LinkModel::getSet($other)['title'];

        foreach ([$otherTitle, mb_strtolower($otherTitle), 'Notebook', 'mini list'] as $title) {
            $result = $this->callApi('save_set', ['id' => (string) $this->setId, 'title' => $title]);
            $this->assertFalse($result['saved'], $title);
            $this->assertArrayHasKey('title', $result['errors']);
            $result = $this->callApi('save_set', ['title' => $title]);
            $this->assertFalse($result['saved'], "新規 $title");
        }
        // 自分の今の見出しのままなら保存できる
        $own = LinkModel::getSet($this->setId)['title'];
        $this->assertTrue($this->callApi('save_set', ['id' => (string) $this->setId, 'title' => $own])['saved']);
    }

    public function test_TC_SET_004_見出しが空や長すぎたり使えない文字なら保存しない(): void
    {
        $before = count(LinkModel::getSets());
        foreach (['', '   ', str_repeat('あ', 31), 'R&D', '<b>', 'a"b', "a'b"] as $title) {
            $result = $this->callApi('save_set', ['title' => $title]);
            $this->assertFalse($result['saved'], $title);
            $this->assertStringNotContainsString('LBL_', $result['errors']['title']);
        }
        $this->assertCount($before, LinkModel::getSets());
    }

    public function test_TC_SET_005_削除するとリンクと登録と配置が消え他のウィジェットは残る(): void
    {
        $id = $this->createLink([
            'visibility' => 'targets',
            'params' => [['name' => 'a', 'value_type' => 'fixed', 'value' => '1']],
            'targets' => [['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]],
        ]);
        $otherSet = $this->createSet('残る');
        $otherLink = LinkModel::saveLink(\Settings_SharedLinks_Rules_Model::normalize($this->linkInput()), $otherSet);
        $linkId = LinkModel::getSet($this->setId)['linkid'];
        $this->db->pquery(
            'INSERT INTO vtiger_module_dashboard_widgets (linkid, userid, filterid, title, data, dashboardtabid) VALUES (?, ?, 0, NULL, NULL, 1)',
            [$linkId, self::ADMIN_USER_ID]
        );

        $this->assertSame(['id' => $this->setId, 'deleted' => true], $this->callApi('delete_set', ['id' => (string) $this->setId]));

        $this->assertNull(LinkModel::getSet($this->setId));
        $this->assertNull($this->findLink($id));
        foreach (['vtiger_shared_link_params', 'vtiger_shared_link_targets'] as $table) {
            $this->assertSame(0, $this->db->num_rows($this->db->pquery("SELECT 1 FROM $table WHERE linkid = ?", [$id])), $table);
        }
        $this->assertSame(0, $this->db->num_rows($this->db->pquery('SELECT 1 FROM vtiger_links WHERE linkid = ?', [$linkId])));
        $this->assertSame(0, $this->db->num_rows($this->db->pquery(
            'SELECT 1 FROM vtiger_module_dashboard_widgets WHERE linkid = ?',
            [$linkId]
        )));
        $this->assertNotNull($this->findLink($otherLink));
    }

    public function test_TC_SET_007_存在しないウィジェットは404(): void
    {
        $cases = [
            ['save_set', ['id' => '999999', 'title' => self::PREFIX . 'x']],
            ['save_set', ['id' => 'abc', 'title' => self::PREFIX . 'x']],
            ['delete_set', ['id' => '999999']],
            ['delete_set', ['id' => '']],
            ['save', ['set_id' => '999999', 'link' => json_encode($this->linkInput())]],
            ['save', ['set_id' => '', 'link' => json_encode($this->linkInput())]],
        ];
        foreach ($cases as [$mode, $params]) {
            try {
                $this->callApi($mode, $params);
                $this->fail("$mode " . json_encode($params) . ' がエラーにならない');
            } catch (ApiNotFoundException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_TC_SET_008_リンクを別のウィジェットへ移せる(): void
    {
        $id = $this->createLink(['title' => self::PREFIX . '移す']);
        $otherSet = $this->createSet('移し先');

        $result = $this->callApi('save', [
            'id' => (string) $id,
            'set_id' => (string) $otherSet,
            'link' => json_encode($this->linkInput(['title' => self::PREFIX . '移す'])),
        ]);

        $this->assertTrue($result['saved']);
        $this->assertSame($otherSet, $this->findLink($id)['set_id']);
        $listed = array_values(array_filter($this->callApi('list')['links'], fn ($l) => $l['id'] === $id));
        $this->assertSame($otherSet, $listed[0]['set_id']);
    }

    public function test_TC_SET_009_ウィジェットにはそのウィジェットのリンクだけを出す(): void
    {
        $mine = $this->createLink(['title' => self::PREFIX . 'こちら']);
        $otherSet = $this->createSet('あちら');
        $theirs = LinkModel::saveLink(\Settings_SharedLinks_Rules_Model::normalize($this->linkInput()), $otherSet);

        $this->assertSame([$mine], array_column(LinkModel::getVisibleLinksForCurrentUser($this->setId), 'id'));
        $this->assertSame([$theirs], array_column(LinkModel::getVisibleLinksForCurrentUser($otherSet), 'id'));
        $this->assertSame([], LinkModel::getVisibleLinksForCurrentUser(999999));
    }

    // ==== TS-05 API: info =================================================

    public function test_TC_API_040_043_infoは選択肢と上限と文言を返す(): void
    {
        $info = $this->callApi('info');

        // TC-API-040
        $fieldNames = array_column($info['user_fields'], 'name');
        $this->assertSame(['id', 'full_name'], array_slice($fieldNames, 0, 2));
        $this->assertContains('email1', $fieldNames);
        foreach (Rules::EXCLUDED_USER_FIELDS as $excluded) {
            $this->assertNotContains($excluded, $fieldNames);
        }

        // TC-API-041: 親の後ろに子が並び、depth を持つ
        $position = array_flip(array_column($info['roles'], 'id'));
        $result = $this->db->pquery('SELECT roleid, parentrole, depth FROM vtiger_role', []);
        $this->assertCount($this->db->num_rows($result), $info['roles']);
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $chain = explode('::', (string) $this->db->query_result($result, $i, 'parentrole'));
            $roleId = (string) $this->db->query_result($result, $i, 'roleid');
            $this->assertSame((int) $this->db->query_result($result, $i, 'depth'), $info['roles'][$position[$roleId]]['depth']);
            if (count($chain) >= 2) {
                $this->assertLessThan($position[$roleId], $position[$chain[count($chain) - 2]], $roleId);
            }
        }

        // TC-API-042: 有効なユーザーだけ
        $active = $this->db->pquery("SELECT id FROM vtiger_users WHERE status = 'Active' AND deleted = 0", []);
        $expected = [];
        for ($i = 0; $i < $this->db->num_rows($active); $i++) {
            $expected[] = (string) $this->db->query_result($active, $i, 'id');
        }
        $actual = array_column($info['users'], 'id');
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);

        // TC-API-043
        $this->assertSame([
            'title_max' => Rules::TITLE_MAX,
            'url_max' => Rules::URL_MAX,
            'description_max' => Rules::DESCRIPTION_MAX,
            'params_max' => Rules::PARAMS_MAX,
            'param_name_max' => Rules::PARAM_NAME_MAX,
            'param_value_max' => Rules::PARAM_VALUE_MAX,
            'window_min' => Rules::WINDOW_MIN,
            'window_max' => Rules::WINDOW_MAX,
            'default_window_width' => Rules::DEFAULT_WINDOW_WIDTH,
            'default_window_height' => Rules::DEFAULT_WINDOW_HEIGHT,
            'widget_title_max' => Rules::WIDGET_TITLE_MAX,
        ], $info['limits']);
        $languageStrings = [];
        include dirname(__DIR__, 3) . '/languages/en_us/Settings/SharedLinks.php';
        $this->assertSame(array_keys($languageStrings), array_keys($info['labels']));
        foreach ($info['labels'] as $key => $label) {
            $this->assertNotSame($key, $label, "$key が翻訳されていない");
        }
    }

    // ==== TS-03 値の解決 ==================================================

    public function test_TC_RES_020_024_差し込める項目の一覧(): void
    {
        $fields = LinkModel::getInsertableUserFields();

        // TC-RES-024: 追加の項目が先頭で、ラベルは翻訳済み
        $this->assertSame(['id', 'full_name'], array_slice(array_keys($fields), 0, 2));
        $this->assertStringNotContainsString('LBL_', $fields['id'] . $fields['full_name']);
        // TC-RES-020
        $this->assertArrayHasKey('user_name', $fields);
        $this->assertArrayHasKey('email1', $fields);
        // TC-RES-021
        foreach (Rules::EXCLUDED_USER_FIELDS as $excluded) {
            $this->assertArrayNotHasKey($excluded, $fields);
        }
        // TC-RES-022
        $passwordFields = $this->db->pquery(
            'SELECT fieldname FROM vtiger_field WHERE tabid = ? AND uitype = 99',
            [getTabid('Users')]
        );
        for ($i = 0; $i < $this->db->num_rows($passwordFields); $i++) {
            $this->assertArrayNotHasKey($this->db->query_result($passwordFields, $i, 'fieldname'), $fields);
        }
    }

    public function test_TC_RES_023_非表示の項目は差し込めない(): void
    {
        $tabId = getTabid('Users');
        $result = $this->db->pquery("SELECT presence FROM vtiger_field WHERE tabid = ? AND fieldname = 'phone_fax'", [$tabId]);
        if ($this->db->num_rows($result) === 0) {
            $this->markTestSkipped('Users に phone_fax が無い');
        }
        $presence = $this->db->query_result($result, 0, 'presence');
        $this->db->pquery("UPDATE vtiger_field SET presence = 2 WHERE tabid = ? AND fieldname = 'phone_fax'", [$tabId]);
        try {
            $this->assertArrayHasKey('phone_fax', LinkModel::getInsertableUserFields());
            $this->db->pquery("UPDATE vtiger_field SET presence = 1 WHERE tabid = ? AND fieldname = 'phone_fax'", [$tabId]);
            $this->assertArrayNotHasKey('phone_fax', LinkModel::getInsertableUserFields());
        } finally {
            $this->db->pquery("UPDATE vtiger_field SET presence = ? WHERE tabid = ? AND fieldname = 'phone_fax'", [$presence, $tabId]);
        }
    }

    public function test_TC_RES_003_005_TC_RES_040_GETはユーザー情報を差し込んだURLを返す(): void
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
        $fullName = Rules::fullName(
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

    public function test_TC_RES_006_041_POSTはURLをそのままにしてパラメータを解決して返す(): void
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
            ['name' => 'gone', 'value' => ''],
        ], $link['params']);
    }

    // ==== TS-02 公開範囲（実際のユーザーの権限情報） ======================

    public function test_TC_VIS_025_無効のリンクは出ない(): void
    {
        $active = $this->createLink(['title' => self::PREFIX . 'on']);
        $inactive = $this->createLink(['title' => self::PREFIX . 'off', 'active' => false]);

        $this->assertNotNull($this->findVisible($active));
        $this->assertNull($this->findVisible($inactive));
    }

    public function test_TC_VIS_026_027_管理者の役割と本人と上位の役割(): void
    {
        $context = LinkModel::getUserContext(self::ADMIN_USER_ID);
        $roleId = $context['roleid'];
        $chain = explode('::', $context['parent_role_seq']);
        $this->assertNotSame('', $roleId, '管理者の役割が取れない');
        $this->assertGreaterThanOrEqual(2, count($chain), '管理者の役割に上位が無い');
        $parentRole = $chain[count($chain) - 2];

        $visible = fn (array $targets): bool => $this->findVisible(
            $this->createLink(['visibility' => 'targets', 'targets' => $targets])
        ) !== null;

        $this->assertTrue($visible([['type' => 'Roles', 'id' => $roleId]]), '自分の役割');
        $this->assertTrue($visible([['type' => 'Users', 'id' => (string) self::ADMIN_USER_ID]]), '本人');
        $this->assertFalse($visible([['type' => 'Roles', 'id' => $parentRole]]), '上位の役割（下位を含まない）');
        $this->assertTrue($visible([['type' => 'RoleAndSubordinates', 'id' => $parentRole]]), '上位の役割（下位を含む）');
    }

    /** テスト用のグループを作る（tearDown ではなく呼び出し側の finally で消す） */
    private function createGroup(string $name): string
    {
        $groupId = (string) $this->db->getUniqueID('vtiger_users');
        $this->db->pquery('INSERT INTO vtiger_groups (groupid, groupname, description) VALUES (?, ?, ?)', [
            $groupId, self::PREFIX . $name, '',
        ]);
        return $groupId;
    }

    private function dropGroups(string ...$groupIds): void
    {
        foreach ($groupIds as $groupId) {
            $this->db->pquery('DELETE FROM vtiger_users2group WHERE groupid = ?', [$groupId]);
            $this->db->pquery('DELETE FROM vtiger_group2grouprel WHERE groupid = ? OR containsgroupid = ?', [$groupId, $groupId]);
            $this->db->pquery('DELETE FROM vtiger_groups WHERE groupid = ?', [$groupId]);
        }
    }

    public function test_TC_VIS_029a_管理者もグループの所属で判定し入れ子のグループも含む(): void
    {
        // 外側のグループ ⊃ 内側のグループ ⊃ 管理者
        $outer = $this->createGroup('outer');
        $inner = $this->createGroup('inner');
        try {
            $this->db->pquery('INSERT INTO vtiger_group2grouprel (groupid, containsgroupid) VALUES (?, ?)', [$outer, $inner]);
            $toInner = $this->createLink(['visibility' => 'targets', 'targets' => [['type' => 'Groups', 'id' => $inner]]]);
            $toOuter = $this->createLink(['visibility' => 'targets', 'targets' => [['type' => 'Groups', 'id' => $outer]]]);

            $this->assertNull($this->findVisible($toInner), '所属していない');
            $this->assertNull($this->findVisible($toOuter), '所属していない（外側）');

            $this->db->pquery('INSERT INTO vtiger_users2group (groupid, userid) VALUES (?, ?)', [$inner, self::ADMIN_USER_ID]);
            $this->assertNotNull($this->findVisible($toInner), '直接所属');
            $this->assertNotNull($this->findVisible($toOuter), 'グループの中のグループ経由で所属');
        } finally {
            $this->dropGroups($outer, $inner);
        }
    }

    public function test_TC_VIS_029b_役割とグループはDBから求める(): void
    {
        $userId = $this->nonAdminUserId();
        $row = $this->db->query_result_rowdata($this->db->pquery(
            'SELECT r.roleid, r.parentrole FROM vtiger_user2role ur INNER JOIN vtiger_role r ON r.roleid = ur.roleid WHERE ur.userid = ?',
            [$userId]
        ), 0);

        $context = LinkModel::getUserContext($userId);

        $this->assertSame($userId, $context['id']);
        $this->assertSame((string) $row['roleid'], $context['roleid']);
        $this->assertSame((string) $row['parentrole'], $context['parent_role_seq']);
        $expectedGroups = array_map('strval', (array) explode(',', (string) fetchUserGroupids($userId)));
        $this->assertSame(array_values(array_filter($expectedGroups, 'strlen')), $context['groups']);
        // 管理者も権限ファイルに頼らず、役割の経路を持つ
        $this->assertNotSame('', LinkModel::getUserContext(self::ADMIN_USER_ID)['parent_role_seq']);
    }

    public function test_TC_VIS_028_管理者でも公開範囲外のリンクは出ない(): void
    {
        $this->assertTrue(Users_Record_Model::getCurrentUserModel()->isAdminUser());
        $id = $this->createLink(['visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => '999999']]]);

        $this->assertNull($this->findVisible($id));
    }
}
