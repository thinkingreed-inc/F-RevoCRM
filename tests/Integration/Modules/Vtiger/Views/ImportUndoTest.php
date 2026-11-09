<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Vtiger\Views;

use CRMEntity;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Users_Record_Model;
use Vtiger_Import_View;
use Vtiger_Record_Model;
use Vtiger_Request;

/**
 * インポート結果画面の「最新のインポートを取り消す」（#1967）
 *
 * Vtiger_Import_View::undoImport() が、完了済み（キュー status=4）のインポートでも
 * URL の importid から一時テーブル（vtiger_import_{userid}_{importid}）を特定し、
 * 取り込んだレコードを削除できることを確認する。
 */
final class ImportUndoTest extends TestCase
{
    /** テストデータの目印（後始末で使う） */
    private const PREFIX = 'SPECTEST_UNDO_';

    /** 管理者ユーザーID */
    private const ADMIN_USER_ID = 1;

    /** F-RevoCRM の初期化を1度だけ行うためのフラグ */
    private static bool $initialized = false;

    private PearDatabase $db;

    private int $importId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    /** テスト用 DB につながらなければ、このクラスのテストをまるごと飛ばす */
    private static function requireTestDatabase(): void
    {
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT DATABASE() AS dbname', []);
            $connected = ($result !== false && $db->num_rows($result) === 1)
                ? (string) $db->query_result($result, 0, 'dbname')
                : '';
        } catch (\Throwable $e) {
            $connected = '';
        }
        if ($connected === '') {
            self::markTestSkipped(
                'テスト用DBに接続できないため飛ばします（tests/README.md のテスト用DBを参照）'
            );
        }
    }

    /** F-RevoCRM 本体を読み込む（bootstrap.php が外したオートロードを戻す） */
    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 5);

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
        vimport('includes.http.Request');
        vimport('includes.http.Response');
        vimport('includes.exceptions.AppException');
        require_once $root . '/include/events/include.inc';

        require_once $root . '/modules/Users/Users.php';
        require_once $root . '/modules/Users/models/Record.php';
        require_once $root . '/modules/Vtiger/views/Import.php';

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
        if ($this->importId > 0) {
            $this->db->pquery('DROP TABLE IF EXISTS ' . $this->importTableName(), []);
            $this->db->pquery('DELETE FROM vtiger_import_queue WHERE importid = ?', [$this->importId]);
        }
        $result = $this->db->pquery(
            'SELECT accountid FROM vtiger_account WHERE accountname LIKE ?',
            [self::PREFIX . '%']
        );
        while ($row = $this->db->fetchByAssoc($result)) {
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$row['accountid']]);
            $this->db->pquery('DELETE FROM vtiger_account WHERE accountid = ?', [$row['accountid']]);
        }
        parent::tearDown();
    }

    public function test_完了済みインポートを取り消すと取り込んだレコードが削除される(): void
    {
        $recordId = $this->createAccount('1');
        $this->createCompletedImport([$recordId]);

        $output = $this->undoImport();

        $this->assertFalse(isRecordExists($recordId), '取り込んだレコードが削除されていない');
        $this->assertMatchesRegularExpression('/<td width="60%">\s*1\s*<\/td>/', $output, '削除件数が 1 と表示されていない');
    }

    // ---- 前提データ -----------------------------------------------------

    private function loginAs(int $userId): void
    {
        $currentUser = CRMEntity::getInstance('Users');
        $currentUser->id = $userId;
        $currentUser->retrieve_entity_info($userId, 'Users');
        $GLOBALS['current_user'] = $currentUser;

        $model = new Users_Record_Model();
        $model->setData((array) $currentUser->column_fields);
        $model->setModule('Users');
        $model->setEntity($currentUser);
        foreach (get_object_vars($currentUser) as $key => $value) {
            if (!is_object($value)) {
                $model->$key = $value;
            }
        }
        Users_Record_Model::$currentUserModels[$userId] = $model;
    }

    private function createAccount(string $suffix): int
    {
        $recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
        $recordModel->set('mode', '');
        $recordModel->set('accountname', self::PREFIX . $suffix);
        $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        $recordModel->save();

        return (int) $recordModel->getId();
    }

    /**
     * インポート完了直後の状態を作る
     *
     * キューは完了（status=4）、一時テーブルには新規作成（status=1）として recordid を記録する。
     *
     * @param list<int> $recordIds
     */
    private function createCompletedImport(array $recordIds): void
    {
        $result = $this->db->pquery('SELECT COALESCE(MAX(importid), 0) + 1 AS nextid FROM vtiger_import_queue', []);
        $this->importId = (int) $this->db->query_result($result, 0, 'nextid');

        $this->db->pquery(
            'INSERT INTO vtiger_import_queue (importid, userid, tabid, field_mapping, default_values, merge_type, merge_fields, status)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->importId, self::ADMIN_USER_ID, getTabid('Accounts'), '{"accountname":"0"}', '[]', 0, '""', 4]
        );

        $table = $this->importTableName();
        $this->db->pquery("CREATE TABLE $table (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, status INT DEFAULT 0, recordid INT)", []);
        foreach ($recordIds as $recordId) {
            $this->db->pquery("INSERT INTO $table (status, recordid) VALUES (?, ?)", [1, $recordId]);
        }
    }

    private function importTableName(): string
    {
        return 'vtiger_import_' . self::ADMIN_USER_ID . '_' . $this->importId;
    }

    /** 結果画面の「最新のインポートを取り消す」と同じリクエストで取り消しを実行する */
    private function undoImport(): string
    {
        $values = [
            'module' => 'Accounts',
            'view' => 'Import',
            'mode' => 'undoImport',
            'foruser' => (string) self::ADMIN_USER_ID,
            'importid' => (string) $this->importId,
        ];
        $view = new Vtiger_Import_View();

        ob_start();
        try {
            $view->undoImport(new Vtiger_Request($values, $values));
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
