<?php

declare(strict_types=1);

namespace Tests\Support;

use CRMEntity;
use PearDatabase;
use Users_Record_Model;
use Vtiger_Request;

/**
 * インポート結果画面の「最新のインポートを取り消す」（#1967）の結合テストで使う共通処理
 *
 * インポート完了直後の状態（キュー status=4、一時テーブル vtiger_import_{userid}_{importid}）を
 * 直接作り、取り消し処理を結果画面と同じリクエストで実行する。
 */
trait ImportUndoTestSupport
{
    /** 実行ユーザー（管理者）ID */
    private static int $importUserId = 1;

    /** F-RevoCRM の初期化を1度だけ行うためのフラグ */
    private static bool $importUndoInitialized = false;

    private PearDatabase $db;

    private int $importId = 0;

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
        if (self::$importUndoInitialized) {
            return;
        }
        $root = dirname(__DIR__, 2);

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
        self::$importUndoInitialized = true;
    }

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

    /**
     * インポート完了直後の状態を作る
     *
     * キューは完了（status=4）、一時テーブルには新規作成（status=1）として recordid を記録する。
     *
     * @param list<int> $recordIds
     */
    private function createCompletedImport(string $moduleName, array $recordIds): void
    {
        $result = $this->db->pquery('SELECT COALESCE(MAX(importid), 0) + 1 AS nextid FROM vtiger_import_queue', []);
        $this->importId = (int) $this->db->query_result($result, 0, 'nextid');

        $this->db->pquery(
            'INSERT INTO vtiger_import_queue (importid, userid, tabid, field_mapping, default_values, merge_type, merge_fields, status)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->importId, self::$importUserId, getTabid($moduleName), '{}', '[]', 0, '""', 4]
        );

        $table = $this->importTableName();
        $this->db->pquery("CREATE TABLE $table (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, status INT DEFAULT 0, recordid INT)", []);
        foreach ($recordIds as $recordId) {
            $this->db->pquery("INSERT INTO $table (status, recordid) VALUES (?, ?)", [1, $recordId]);
        }
    }

    /** createCompletedImport() で作ったキューと一時テーブルを消す */
    private function dropCompletedImport(): void
    {
        if ($this->importId > 0) {
            $this->db->pquery('DROP TABLE IF EXISTS ' . $this->importTableName(), []);
            $this->db->pquery('DELETE FROM vtiger_import_queue WHERE importid = ?', [$this->importId]);
        }
    }

    private function importTableName(): string
    {
        return 'vtiger_import_' . self::$importUserId . '_' . $this->importId;
    }

    /**
     * 結果画面の「最新のインポートを取り消す」と同じリクエストで取り消しを実行する
     *
     * @param callable(Vtiger_Request): void $handler 取り消しを実行する処理
     * @return string 描画された削除結果
     */
    private function runUndoImport(string $moduleName, callable $handler): string
    {
        $values = [
            'module' => $moduleName,
            'view' => 'Import',
            'mode' => 'undoImport',
            'foruser' => (string) self::$importUserId,
            'importid' => (string) $this->importId,
        ];

        ob_start();
        try {
            $handler(new Vtiger_Request($values, $values));
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    /** 削除結果に表示された「削除されたレコード数」 */
    private function deletedCount(string $output): ?int
    {
        if (preg_match('/<td width="60%">\s*(\d+)\s*<\/td>/', $output, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
