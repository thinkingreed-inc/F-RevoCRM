<?php

namespace Tests\Support;

use CRMEntity;
use Documents_ComplianceChecker;
use Documents_DeadlineCalculator;
use Documents_FolderPermission;
use FR_BusinessDay;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Users_Record_Model;
use Vtiger_Record_Model;
use Vtiger_Request;

/**
 * ドキュメント関連の結合テストの共通基盤
 *
 * 対応する仕様書: docs/tests/Documents/
 *
 * tests/bootstrap.php は Vtiger_Loader のオートロードを外すため、
 * 必要なクラスはここでまとめて require する。
 * 実行ユーザー・テストデータの後始末もここに集約する。
 */
abstract class DocumentsTestCase extends TestCase
{
    /** テストデータの目印（後始末で使う） */
    public const PREFIX = 'SPECTEST_';

    /** テストで使う年（初期データと衝突しない年を選ぶ） */
    public const YEAR = 2035;

    /** 管理者ユーザーID */
    public const ADMIN_USER_ID = 1;

    /** F-RevoCRM の初期化を1度だけ行うためのフラグ */
    private static bool $initialized = false;

    /** 退避したドキュメント設定 */
    private array $savedSettings = [];

    /** 退避した週休の曜日 */
    private array $savedWeeklyHolidays = [];

    protected PearDatabase $db;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    /**
     * テスト用 DB につながらなければ、このクラスのテストをまるごと飛ばす
     *
     * CI（.github/workflows/phpunit.yml）は DB を立てずに composer test を走らせる。
     * つながらないまま本体を初期化すると原因の分かりにくいエラーになるため、
     * 理由を添えて skip する。ローカルで動かす手順は tests/README.md を参照。
     */
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::ADMIN_USER_ID);

        $this->savedSettings = $this->backupDocumentsSettings();
        $this->savedWeeklyHolidays = FR_BusinessDay::getWeeklyHolidays();

        $this->cleanDocuments();
        $this->cleanHolidays();
        $this->setWeeklyHolidays([0, 6]);
    }

    protected function tearDown(): void
    {
        $this->cleanDocuments();
        $this->cleanHolidays();
        FR_BusinessDay::setWeeklyHolidays($this->savedWeeklyHolidays);
        $this->restoreDocumentsSettings($this->savedSettings);
        $this->clearCaches();
        $this->loginAs(self::ADMIN_USER_ID);
        parent::tearDown();
    }

    /**
     * F-RevoCRM 本体を読み込む
     *
     * bootstrap.php が Vtiger_Loader のオートロードを外しているため、
     * テストで触るクラスは明示的に読み込む必要がある。
     */
    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 2);

        // tests/bootstrap.php は PHPUnit のクラス探索で Vtiger 側の require が
        // 走らないようオートロードを外している。結合テストは本体をまるごと使うため、
        // 探索が済んだこの時点で戻す（見つからないクラスは false を返すだけなので
        // PHPUnit 自身のクラス解決には影響しない）。
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
        require_once $root . '/modules/Users/models/Module.php';
        require_once $root . '/modules/Documents/Documents.php';
        require_once $root . '/modules/Documents/models/Record.php';
        require_once $root . '/modules/Documents/models/Module.php';
        require_once $root . '/modules/Settings/Vtiger/models/Module.php';
        require_once $root . '/modules/Settings/Vtiger/models/Record.php';
        require_once $root . '/vtlib/Vtiger/Module.php';
        require_once $root . '/include/Webservices/Utils.php';
        require_once $root . '/include/utils/BusinessDay.php';

        date_default_timezone_set('Asia/Tokyo');
        self::$initialized = true;
    }

    // ---- 実行ユーザー ---------------------------------------------------

    /**
     * 指定ユーザーとして実行する
     *
     * @param int $userId ユーザーID
     */
    protected function loginAs(int $userId): Users_Record_Model
    {
        $currentUser = CRMEntity::getInstance('Users');
        $currentUser->id = $userId;
        $currentUser->retrieve_entity_info($userId, 'Users');
        $currentUser->column_fields = (array) $currentUser->column_fields;
        $GLOBALS['current_user'] = $currentUser;
        vglobal('current_user', $currentUser);

        $model = new Users_Record_Model();
        $model->setData($currentUser->column_fields);
        $model->setModule('Users');
        $model->setEntity($currentUser);
        foreach (get_object_vars($currentUser) as $key => $value) {
            if (!is_object($value)) {
                $model->$key = $value;
            }
        }
        Users_Record_Model::$currentUserModels[$userId] = $model;
        $this->clearCaches();

        return $model;
    }

    /** 判定結果のキャッシュをまとめて捨てる */
    protected function clearCaches(): void
    {
        if (class_exists('Documents_FolderPermission', false)) {
            Documents_FolderPermission::clearCache();
        }
        if (class_exists('Documents_DeadlineCalculator', false)) {
            Documents_DeadlineCalculator::clearCache();
        }
        if (class_exists('Documents_ComplianceChecker', false)) {
            Documents_ComplianceChecker::clearCache();
        }
        FR_BusinessDay::clearCache();
    }

    // ---- リクエスト -----------------------------------------------------

    /**
     * API に渡すリクエストを組み立てる
     *
     * Vtiger_Request は raw 側の値も見るため、両方に同じ配列を渡す。
     *
     * @param array<string,mixed> $values
     */
    protected function request(array $values): Vtiger_Request
    {
        return new Vtiger_Request($values, $values);
    }

    /**
     * API の応答（Vtiger_Response）から result を取り出す
     *
     * @return array<string,mixed>
     */
    protected function responseResult(object $response): array
    {
        $property = new \ReflectionProperty(get_class($response), 'result');
        $property->setAccessible(true);
        $result = $property->getValue($response);

        return is_array($result) ? $result : [];
    }

    // ---- ドキュメント ---------------------------------------------------

    /**
     * テスト用のドキュメントを作る（外部URL。ファイル操作を伴わない）
     *
     * @param string $suffix タイトルの接尾辞
     * @param array<string,mixed> $fields 追加で設定する項目
     * @return int 作成したドキュメントID
     */
    protected function createDocument(string $suffix, array $fields = []): int
    {
        $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
        $recordModel->set('mode', '');
        $recordModel->set('notes_title', self::PREFIX . $suffix);
        $recordModel->set('filelocationtype', 'E');
        $recordModel->set('filename', 'http://example.com/spec-test');
        $recordModel->set('filestatus', 1);
        $recordModel->set('folderid', 1);
        $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        foreach ($fields as $key => $value) {
            $recordModel->set($key, $value);
        }
        $recordModel->save();

        return (int) $recordModel->getId();
    }

    /**
     * ドキュメントのカラムを直接更新する（画面を経由しない前提条件を作る）
     *
     * @param array<string,mixed> $columns
     */
    protected function updateNotes(int $notesId, array $columns): void
    {
        $sets = [];
        $params = [];
        foreach ($columns as $column => $value) {
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }
        $params[] = $notesId;
        $this->db->pquery(
            'UPDATE vtiger_notes SET ' . implode(', ', $sets) . ' WHERE notesid = ?',
            $params
        );
    }

    /** ドキュメントの1カラムを読む */
    protected function notesColumn(int $notesId, string $column): mixed
    {
        $result = $this->db->pquery(
            "SELECT {$column} FROM vtiger_notes WHERE notesid = ?",
            [$notesId]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }

        return $this->db->query_result($result, 0, $column);
    }

    /** テストで作ったドキュメントを消す */
    protected function cleanDocuments(): void
    {
        $result = $this->db->pquery(
            'SELECT notesid FROM vtiger_notes WHERE title LIKE ?',
            [self::PREFIX . '%']
        );
        if ($result === false) {
            return;
        }
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $notesId = (int) $this->db->query_result($result, $i, 'notesid');
            $this->db->pquery('DELETE FROM vtiger_notes_audit_log WHERE notesid = ?', [$notesId]);
            $this->db->pquery('DELETE FROM vtiger_notes_file_versions WHERE notesid = ?', [$notesId]);
            $this->db->pquery('DELETE FROM vtiger_senotesrel WHERE notesid = ?', [$notesId]);
            $this->db->pquery(
                'DELETE FROM vtiger_modtracker_detail WHERE id IN
                 (SELECT id FROM vtiger_modtracker_basic WHERE crmid = ?)',
                [$notesId]
            );
            $this->db->pquery('DELETE FROM vtiger_modtracker_basic WHERE crmid = ?', [$notesId]);
            $this->db->pquery('DELETE FROM vtiger_notes WHERE notesid = ?', [$notesId]);
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$notesId]);
        }
    }

    // ---- フォルダ -------------------------------------------------------

    /**
     * テスト用のフォルダを直接作る
     *
     * @return int 作成したフォルダID
     */
    protected function createFolder(string $name, int $parentId = 0): int
    {
        $this->db->pquery(
            'INSERT INTO vtiger_attachmentsfolder
                (foldername, description, createdby, sequence, parent_folderid)
             VALUES (?, ?, ?, 0, ?)',
            [self::PREFIX . $name, '', self::ADMIN_USER_ID, $parentId]
        );

        return (int) $this->db->getLastInsertID();
    }

    /**
     * フォルダの権限をまとめて入れ替える
     *
     * @param array<int,array{0:string,1:string,2:string|int|null}> $rows [種別, 付与先, 付与先ID]
     */
    protected function setFolderPermissions(int $folderId, array $rows): void
    {
        $this->db->pquery('DELETE FROM vtiger_folder_permissions WHERE folderid = ?', [$folderId]);
        foreach ($rows as $row) {
            $this->db->pquery(
                'INSERT INTO vtiger_folder_permissions
                    (folderid, permission_type, target_type, target_id) VALUES (?, ?, ?, ?)',
                [$folderId, $row[0], $row[1], $row[2]]
            );
        }
        $this->clearCaches();
    }

    /**
     * フォルダの権限行を「種別/付与先/付与先ID」の形で読む
     *
     * @return array<int,string>
     */
    protected function folderPermissions(int $folderId): array
    {
        $result = $this->db->pquery(
            'SELECT permission_type, target_type, target_id FROM vtiger_folder_permissions
             WHERE folderid = ? ORDER BY permission_type, target_type, target_id',
            [$folderId]
        );
        $rows = [];
        if ($result !== false) {
            for ($i = 0; $i < $this->db->num_rows($result); $i++) {
                $row = $this->db->query_result_rowdata($result, $i);
                $rows[] = $row['permission_type'] . '/' . $row['target_type'] . '/'
                    . ($row['target_id'] === null ? '-' : $row['target_id']);
            }
        }

        return $rows;
    }

    /** テストで作ったフォルダと、その権限行を消す */
    protected function cleanFolders(): void
    {
        $result = $this->db->pquery(
            'SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername LIKE ?',
            [self::PREFIX . '%']
        );
        if ($result !== false) {
            for ($i = 0; $i < $this->db->num_rows($result); $i++) {
                $folderId = (int) $this->db->query_result($result, $i, 'folderid');
                $this->db->pquery(
                    'DELETE FROM vtiger_folder_permissions WHERE folderid = ?',
                    [$folderId]
                );
            }
        }
        $this->db->pquery(
            'DELETE FROM vtiger_attachmentsfolder WHERE foldername LIKE ?',
            [self::PREFIX . '%']
        );

        // フォルダIDは max(folderid)+1 で再利用されるため、宛先の無い権限行が
        // 残っていると新しいフォルダが以前の権限を引き継いでしまう
        $this->db->pquery(
            'DELETE fp FROM vtiger_folder_permissions fp
             LEFT JOIN vtiger_attachmentsfolder f ON f.folderid = fp.folderid
             WHERE f.folderid IS NULL',
            []
        );
        $this->clearCaches();
    }

    // ---- 休祝日 ---------------------------------------------------------

    /** テスト用の休日を1件登録する */
    protected function addHoliday(string $date, string $dayType = 'holiday', ?string $name = null): void
    {
        $this->db->pquery(
            "INSERT INTO vtiger_holidays (holiday_date, holiday_name, day_type, holiday_type)
             VALUES (?, ?, ?, 'company')",
            [$date, $name ?? self::PREFIX . $date, $dayType]
        );
        FR_BusinessDay::clearCache();
    }

    /** テスト用の休祝日を消す */
    protected function cleanHolidays(): void
    {
        $this->db->pquery('DELETE FROM vtiger_holidays WHERE YEAR(holiday_date) = ?', [self::YEAR]);
        $this->db->pquery('DELETE FROM vtiger_holidays WHERE holiday_name LIKE ?', [self::PREFIX . '%']);
        FR_BusinessDay::clearCache();
    }

    /**
     * 週休の曜日を設定する
     *
     * @param array<int,int> $weekdays 0=日曜 〜 6=土曜
     */
    protected function setWeeklyHolidays(array $weekdays): void
    {
        FR_BusinessDay::setWeeklyHolidays($weekdays);
        FR_BusinessDay::clearCache();
    }

    // ---- ドキュメント設定 -----------------------------------------------

    /**
     * ドキュメント設定を退避する
     *
     * @return array<string,string>
     */
    protected function backupDocumentsSettings(): array
    {
        $result = $this->db->pquery('SELECT name, value FROM vtiger_documents_settings', []);
        $settings = [];
        if ($result !== false) {
            for ($i = 0; $i < $this->db->num_rows($result); $i++) {
                $row = $this->db->query_result_rowdata($result, $i);
                $settings[$row['name']] = $row['value'];
            }
        }

        return $settings;
    }

    /**
     * 退避したドキュメント設定を書き戻す
     *
     * @param array<string,string> $settings
     */
    protected function restoreDocumentsSettings(array $settings): void
    {
        $this->db->pquery('DELETE FROM vtiger_documents_settings', []);
        foreach ($settings as $name => $value) {
            $this->db->pquery(
                'INSERT INTO vtiger_documents_settings (name, value) VALUES (?, ?)',
                [$name, $value]
            );
        }
        $this->clearCaches();
    }

    /** ドキュメント設定を1件書き込む */
    protected function setDocumentsSetting(string $name, string $value): void
    {
        $exists = $this->db->pquery(
            'SELECT name FROM vtiger_documents_settings WHERE name = ?',
            [$name]
        );
        if ($exists !== false && $this->db->num_rows($exists) > 0) {
            $this->db->pquery(
                'UPDATE vtiger_documents_settings SET value = ? WHERE name = ?',
                [$value, $name]
            );
        } else {
            $this->db->pquery(
                'INSERT INTO vtiger_documents_settings (name, value) VALUES (?, ?)',
                [$name, $value]
            );
        }
        $this->clearCaches();
    }
}
