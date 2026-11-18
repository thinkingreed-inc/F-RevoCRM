<?php

declare(strict_types=1);

namespace Tests\Support;

use CRMEntity;
use Migration20261106093622_AddSharedLinksWidget;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Settings_SharedLinks_Module_Model;
use Settings_SharedLinks_Rules_Model;
use Users_Record_Model;

/**
 * 共有リンクの結合テストの共通基盤
 *
 * テスト仕様書: docs/tests/SharedLinks/
 *
 * F-RevoCRM 本体の読み込み・テスト用 DB へのマイグレーション・実行ユーザーの切り替え・
 * テストデータの後始末を引き受ける。テストで作るリンクのタイトルには接頭辞 SPECTEST_ を付け、
 * tearDown() で消す。
 */
abstract class SharedLinksTestCase extends TestCase
{
    public const PREFIX = 'SPECTEST_';
    public const ADMIN_USER_ID = 1;

    private static bool $initialized = false;

    protected PearDatabase $db;

    /** テストで使うウィジェット（リンクのまとまり）の ID。setUp で作り、tearDown で消す */
    protected int $setId = 0;

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
        $root = dirname(__DIR__, 2);
        // tests/bootstrap.php が外したオートロードを戻す（結合テストは本体をまるごと使う）
        if (class_exists('Vtiger_Loader', false)) {
            spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        }
        require_once $root . '/include/utils/utils.php';
        require_once $root . '/include/database/PearDatabase.php';
        vimport('includes.runtime.Globals');
        vimport('includes.runtime.LanguageHandler');
        vimport('includes.runtime.BaseModel');
        vimport('includes.runtime.Controller');
        vimport('includes.runtime.Viewer');
        vimport('includes.runtime.Theme');
        vimport('includes.runtime.JavaScript');
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

    /** マイグレーションを実行する（ログの出力は捨てる） */
    protected static function runMigration(): void
    {
        ob_start();
        try {
            (new Migration20261106093622_AddSharedLinksWidget())->process();
        } finally {
            ob_end_clean();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::ADMIN_USER_ID);
        $this->cleanLinks();
        $this->setId = $this->createSet('set');
    }

    protected function tearDown(): void
    {
        $this->cleanLinks();
        $this->loginAs(self::ADMIN_USER_ID);
        parent::tearDown();
    }

    /**
     * テスト用のウィジェットを作る（見出しに接頭辞 SPECTEST_ を付ける。30 文字まで）
     */
    protected function createSet(string $name): int
    {
        return Settings_SharedLinks_Module_Model::saveSet(mb_substr(self::PREFIX . $name, 0, 30));
    }

    /**
     * テストで作ったウィジェット・リンクを消す
     *
     * ウィジェットを消すと、中のリンク・「ウィジェットの追加」の登録・ダッシュボードの配置も消える。
     */
    protected function cleanLinks(): void
    {
        foreach (Settings_SharedLinks_Module_Model::getSets() as $set) {
            if (str_starts_with($set['title'], self::PREFIX)) {
                Settings_SharedLinks_Module_Model::deleteSet($set['id']);
            }
        }
        $result = $this->db->pquery('SELECT id FROM vtiger_shared_links WHERE title LIKE ?', [self::PREFIX . '%']);
        $ids = [];
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $ids[] = (int) $this->db->query_result($result, $i, 'id');
        }
        foreach ($ids as $id) {
            Settings_SharedLinks_Module_Model::deleteLink($id);
        }
    }

    /** 指定ユーザーとして実行する */
    protected function loginAs(int $userId): Users_Record_Model
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
    protected function linkInput(array $overrides = []): array
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
    protected function createLink(array $overrides = []): int
    {
        return Settings_SharedLinks_Module_Model::saveLink(
            Settings_SharedLinks_Rules_Model::normalize($this->linkInput($overrides)),
            $this->setId
        );
    }

    /** 管理者でない有効なユーザーの ID。いなければテストを飛ばす */
    protected function nonAdminUserId(): int
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
}
