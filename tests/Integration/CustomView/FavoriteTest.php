<?php

declare(strict_types=1);

namespace Tests\Integration\CustomView;

use ApiException;
use CRMEntity;
use CustomView_Favorite_Model;
use CustomView_GetFavorites_Api;
use CustomView_Record_Model;
use CustomView_SaveFavorite_Api;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Users_Record_Model;
use Vtiger_Request;

/**
 * 管理者は全リストが見えることがあるため、参照できない場合の確認には既存の一般ユーザーを使う。
 * （ユーザーを作ると権限ファイルが開発環境と共有の user_privileges/ に書き出される）
 */
final class FavoriteTest extends TestCase
{
    private const ADMIN_USER_ID = 1;

    private const MODULE = 'Potentials';

    private const PREFIX = 'SPECTEST_1716_';

    private static bool $initialized = false;

    private PearDatabase $db;

    /** @var list<int> */
    private array $customViewIds = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    private static function requireTestDatabase(): void
    {
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_tab', []);
            $count = ($result !== false && $db->num_rows($result) === 1)
                ? (int) $db->query_result($result, 0, 'cnt')
                : 0;
            $favorites = $db->pquery("SHOW TABLES LIKE 'vtiger_customview_favorites'", []);
            $hasTable = $favorites !== false && $db->num_rows($favorites) === 1;
        } catch (\Throwable $e) {
            $count = 0;
            $hasTable = false;
        }
        if ($count === 0) {
            self::markTestSkipped(
                'テスト用DBに接続できないか初期データがないため飛ばします（tests/README.md のテスト用DBを参照）'
            );
        }
        if (!$hasTable) {
            self::markTestSkipped(
                'vtiger_customview_favorites がないため飛ばします（setup/migration のマイグレーションを実行してください）'
            );
        }
    }

    /**
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
        require_once $root . '/modules/Users/models/Record.php';
        require_once $root . '/vtlib/Vtiger/Module.php';
        require_once $root . '/modules/CustomView/models/Record.php';
        require_once $root . '/modules/CustomView/models/Favorite.php';
        require_once $root . '/modules/CustomView/apis/GetFavorites.php';
        require_once $root . '/modules/CustomView/apis/SaveFavorite.php';

        date_default_timezone_set('Asia/Tokyo');
        self::$initialized = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::ADMIN_USER_ID);
    }

    protected function tearDown(): void
    {
        // お気に入りの行は FK（ON DELETE CASCADE）で一緒に消える
        foreach ($this->customViewIds as $cvId) {
            $this->db->pquery('DELETE FROM vtiger_customview WHERE cvid = ?', [$cvId]);
        }
        $this->customViewIds = [];
        parent::tearDown();
    }

    public function test_登録した順に並び_外すと一覧から消える(): void
    {
        $first = $this->createCustomView('先に登録', CustomView_Record_Model::CV_STATUS_PUBLIC);
        $second = $this->createCustomView('後に登録', CustomView_Record_Model::CV_STATUS_PUBLIC);

        CustomView_Favorite_Model::add($second);
        CustomView_Favorite_Model::add($first);
        // 同じ秒に登録すると ID 順になるため、登録日時をずらす
        $this->db->pquery(
            'UPDATE vtiger_customview_favorites SET createdtime = createdtime - INTERVAL 1 MINUTE WHERE userid = ? AND cvid = ?',
            [self::ADMIN_USER_ID, $second]
        );

        $this->assertSame([$second, $first], $this->favoriteIdsOf([$first, $second]));

        CustomView_Favorite_Model::remove($second);

        $this->assertSame([$first], $this->favoriteIdsOf([$first, $second]));
    }

    public function test_同じリストを2回登録しても1件のまま(): void
    {
        $cvId = $this->createCustomView('二重登録', CustomView_Record_Model::CV_STATUS_PUBLIC);

        CustomView_Favorite_Model::add($cvId);
        CustomView_Favorite_Model::add($cvId);

        $this->assertSame(1, $this->countFavoriteRows(self::ADMIN_USER_ID, $cvId));
    }

    public function test_お気に入りはユーザーごとに分かれる(): void
    {
        $userId = $this->generalUserId();
        $cvId = $this->createCustomView('ユーザーごと', CustomView_Record_Model::CV_STATUS_PUBLIC);

        CustomView_Favorite_Model::add($cvId);

        $this->loginAs($userId);
        $this->assertSame([], $this->favoriteIdsOf([$cvId]));
    }

    public function test_参照できない他人の個人リストは登録できない(): void
    {
        $userId = $this->generalUserId();
        $cvId = $this->createCustomView('管理者の個人リスト', CustomView_Record_Model::CV_STATUS_PRIVATE);

        $this->loginAs($userId);
        try {
            $this->saveFavorite($cvId, true);
            $this->fail('参照できないリストを登録できてしまった');
        } catch (ApiException $e) {
            $this->assertSame(403, $e->getHttpStatusCode());
        }
        $this->assertSame(0, $this->countFavoriteRows($userId, $cvId));
    }

    public function test_別モジュールのリストとしては登録できない(): void
    {
        $userId = $this->generalUserId();
        $cvId = $this->createCustomView('公開リスト', CustomView_Record_Model::CV_STATUS_PUBLIC);

        $this->loginAs($userId);
        try {
            $this->saveFavorite($cvId, true, 'Accounts');
            $this->fail('別モジュールのリストとして登録できてしまった');
        } catch (ApiException $e) {
            $this->assertSame(403, $e->getHttpStatusCode());
        }
        $this->assertSame(0, $this->countFavoriteRows($userId, $cvId));
    }

    public function test_共有を外されたリストは一覧に出ないが_外すことはできる(): void
    {
        $userId = $this->generalUserId();
        $cvId = $this->createCustomView('あとで非公開', CustomView_Record_Model::CV_STATUS_PUBLIC);

        $this->loginAs($userId);
        $this->saveFavorite($cvId, true);
        $this->assertSame([$cvId], $this->favoriteIdsOf([$cvId]));

        $this->db->pquery('UPDATE vtiger_customview SET status = ? WHERE cvid = ?', [CustomView_Record_Model::CV_STATUS_PRIVATE, $cvId]);

        $this->assertSame([], $this->favoriteIdsOf([$cvId]), '参照できなくなったリストは一覧に出さない');
        $this->assertSame(1, $this->countFavoriteRows($userId, $cvId), '行は残っている');

        $this->saveFavorite($cvId, false);
        $this->assertSame(0, $this->countFavoriteRows($userId, $cvId), '参照できなくなったリストでも外せる');
    }

    public function test_リストを削除するとお気に入りも消える(): void
    {
        $cvId = $this->createCustomView('削除する', CustomView_Record_Model::CV_STATUS_PRIVATE);
        CustomView_Favorite_Model::add($cvId);

        CustomView_Record_Model::getInstanceById($cvId)->delete();

        $this->assertSame(0, $this->countFavoriteRows(self::ADMIN_USER_ID, $cvId));
    }

    public function test_SaveFavorite_は変更後の一覧を返し_GetFavorites_と一致する(): void
    {
        $cvId = $this->createCustomView('API', CustomView_Record_Model::CV_STATUS_PUBLIC);

        $saved = $this->saveFavorite($cvId, true);

        $this->assertSame($cvId, $saved['record']);
        $this->assertTrue($saved['favorite']);
        $favorite = $this->findFavorite($saved['favorites'], $cvId);
        $this->assertNotNull($favorite);
        $this->assertSame(self::PREFIX . 'API', $favorite['name']);
        $this->assertStringContainsString('module=' . self::MODULE, $favorite['url']);
        $this->assertStringEndsWith('&viewname=' . $cvId, $favorite['url']);

        $api = new CustomView_GetFavorites_Api();
        $fetched = $this->responseResult($api->process($this->request(['sourceModule' => self::MODULE])));
        $this->assertSame($saved['favorites'], $fetched['favorites']);
    }

    public function test_サイドバー用は渡されたリストのうちお気に入りだけを返し_getFavoriteList_と一致する(): void
    {
        $first = $this->createCustomView('サイドバー1', CustomView_Record_Model::CV_STATUS_PUBLIC);
        $second = $this->createCustomView('サイドバー2', CustomView_Record_Model::CV_STATUS_PRIVATE);
        $notFavorite = $this->createCustomView('サイドバー対象外', CustomView_Record_Model::CV_STATUS_PUBLIC);
        CustomView_Favorite_Model::add($first);
        CustomView_Favorite_Model::add($second);

        $grouped = CustomView_Record_Model::getAllByGroup(self::MODULE);
        $sidebar = CustomView_Favorite_Model::getSidebarFavorites(self::MODULE, $grouped);

        $this->assertSame(CustomView_Favorite_Model::getFavoriteList(self::MODULE), $sidebar['list']);
        $this->assertSame(array_column($sidebar['list'], 'id'), $sidebar['ids']);
        $this->assertContains($first, $sidebar['ids']);
        $this->assertContains($second, $sidebar['ids']);
        $this->assertNotContains($notFavorite, $sidebar['ids']);
    }

    public function test_サイドバー用はサイドバーに出ていないリストを返さない(): void
    {
        $cvId = $this->createCustomView('サイドバーにない', CustomView_Record_Model::CV_STATUS_PUBLIC);
        CustomView_Favorite_Model::add($cvId);

        $grouped = CustomView_Record_Model::getAllByGroup(self::MODULE);
        // Mine / Shared 以外のグループ（Public など）はサイドバーに出ない
        $onlyOtherGroups = ['Public' => $grouped['Shared'] ?? []];

        $this->assertSame(['ids' => [], 'list' => []], CustomView_Favorite_Model::getSidebarFavorites(self::MODULE, $onlyOtherGroups));
        $this->assertSame(['ids' => [], 'list' => []], CustomView_Favorite_Model::getSidebarFavorites(self::MODULE, null));
    }

    public function test_SaveFavorite_はリストIDがなければエラー(): void
    {
        try {
            (new CustomView_SaveFavorite_Api())->process($this->request(['sourceModule' => self::MODULE]));
            $this->fail('リストIDなしで保存できてしまった');
        } catch (ApiException $e) {
            $this->assertSame(400, $e->getHttpStatusCode());
        }
    }

    public function test_どちらのAPIもモジュールの参照権限を求める(): void
    {
        $request = $this->request(['sourceModule' => self::MODULE]);
        $expected = [['module_parameter' => 'sourceModule', 'action' => 'DetailView']];

        $this->assertSame($expected, (new CustomView_GetFavorites_Api())->requiresPermission($request));
        $this->assertSame($expected, (new CustomView_SaveFavorite_Api())->requiresPermission($request));
    }

    private function createCustomView(string $name, int $status): int
    {
        $cvId = (int) $this->db->getUniqueID('vtiger_customview');
        $this->db->pquery(
            'INSERT INTO vtiger_customview (cvid, viewname, setdefault, setmetrics, entitytype, status, userid) VALUES (?, ?, 0, 0, ?, ?, ?)',
            [$cvId, self::PREFIX . $name, self::MODULE, $status, self::ADMIN_USER_ID]
        );
        $this->customViewIds[] = $cvId;
        return $cvId;
    }

    /**
     * テスト用DBには開発DBから複製したお気に入りも入っているため、テストで作ったものに絞る。
     *
     * @param list<int> $cvIds
     * @return list<int>
     */
    private function favoriteIdsOf(array $cvIds): array
    {
        $ids = array_column(CustomView_Favorite_Model::getFavoriteList(self::MODULE), 'id');
        return array_values(array_filter($ids, fn ($id) => in_array($id, $cvIds, true)));
    }

    private function countFavoriteRows(int $userId, int $cvId): int
    {
        $result = $this->db->pquery(
            'SELECT COUNT(*) AS cnt FROM vtiger_customview_favorites WHERE userid = ? AND cvid = ?',
            [$userId, $cvId]
        );
        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    /**
     * @return array{record: int, favorite: bool, favorites: list<array{id: int, name: string, url: string}>}
     */
    private function saveFavorite(int $cvId, bool $favorite, string $sourceModule = self::MODULE): array
    {
        $api = new CustomView_SaveFavorite_Api();
        $response = $api->process($this->request([
            'sourceModule' => $sourceModule,
            'record' => (string) $cvId,
            'favorite' => $favorite ? '1' : '0',
        ]));
        /** @var array{record: int, favorite: bool, favorites: list<array{id: int, name: string, url: string}>} */
        return $this->responseResult($response);
    }

    /**
     * @param list<array{id: int, name: string, url: string}> $favorites
     * @return array{id: int, name: string, url: string}|null
     */
    private function findFavorite(array $favorites, int $cvId): ?array
    {
        foreach ($favorites as $favorite) {
            if ($favorite['id'] === $cvId) {
                return $favorite;
            }
        }
        return null;
    }

    private function generalUserId(): int
    {
        $result = $this->db->pquery(
            "SELECT id FROM vtiger_users WHERE deleted = 0 AND status = 'Active' AND is_admin = 'off' ORDER BY id",
            []
        );
        $count = (int) $this->db->num_rows($result);
        $root = dirname(__DIR__, 3);
        for ($i = 0; $i < $count; $i++) {
            $userId = (int) $this->db->query_result($result, $i, 'id');
            if (is_file("$root/user_privileges/user_privileges_$userId.php")
                && is_file("$root/user_privileges/sharing_privileges_$userId.php")) {
                return $userId;
            }
        }
        $this->markTestSkipped('権限ファイルのある一般ユーザーがテスト用DBにいないため飛ばします');
    }

    private function loginAs(int $userId): void
    {
        $currentUser = CRMEntity::getInstance('Users');
        $currentUser->id = $userId;
        $currentUser->retrieve_entity_info($userId, 'Users');
        $currentUser->column_fields = (array) $currentUser->column_fields;
        $GLOBALS['current_user'] = $currentUser;
        // vglobal() の PHPDoc は引数の型が "type" と誤っている（includes/runtime/Globals.php）
        vglobal('current_user', $currentUser); // @phpstan-ignore argument.type
        unset(Users_Record_Model::$currentUserModels[$userId]);
    }

    /**
     * Vtiger_Request は raw 側の値も見るため両方に渡す
     *
     * @param array<string, string> $values
     */
    private function request(array $values): Vtiger_Request
    {
        return new Vtiger_Request($values, $values);
    }

    /**
     * @return array<string, mixed>
     */
    private function responseResult(object $response): array
    {
        $property = new \ReflectionProperty(get_class($response), 'result');
        $property->setAccessible(true);
        $result = $property->getValue($response);

        return is_array($result) ? $result : [];
    }
}
