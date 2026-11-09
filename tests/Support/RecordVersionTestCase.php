<?php

declare(strict_types=1);

namespace Tests\Support;

use PearDatabase;
use PHPUnit\Framework\TestCase;
use RecordVersion_Config_Model;
use RecordVersion_Setup_Model;
use RecordVersion_Util_Helper;
use RecordVersion_Version_Model;
use Users;
use Vtiger_Record_Model;

/**
 * 版管理（#1964）の結合テストの共通基盤
 *
 * テスト用 DB の案件（Potentials）を使う。版管理の設定（はい／いいえ・版に含める関連）は
 * テストごとに退避・復元し、作ったレコード・過去の版・イベントハンドラは tearDown() でまとめて消す。
 */
abstract class RecordVersionTestCase extends TestCase
{
    public const ADMIN_USER_ID = 1;
    public const PREFIX = 'SPECTEST_RV_';
    public const MODULE = 'Potentials';
    public const HANDLER_PATH = 'tests/Support/recordversion/RecordVersionTestHandlers.php';

    private static bool $initialized = false;

    protected PearDatabase $db;
    protected int $tabId = 0;

    /** @var list<int> 作ったレコード */
    private array $createdRecordIds = [];

    /** @var list<array{0: string, 1: string}> 登録したイベントハンドラ（イベント名, クラス名） */
    private array $registeredHandlers = [];

    private bool $savedEnabled = false;

    /** @var array<int> */
    private array $savedRelationIds = [];

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
            $result = $db->pquery("SHOW TABLES LIKE 'vtiger_recordversion'", []);
            $exists = $result !== false && $db->num_rows($result) === 1;
        } catch (\Throwable $e) {
            $exists = false;
        }
        if (!$exists) {
            self::markTestSkipped(
                'テスト用DBに接続できないか、版管理のマイグレーション（20261109162036_setup_record_version）が未実行のため飛ばします'
            );
        }
    }

    /**
     * F-RevoCRM 本体をまるごと読み込む（tests/bootstrap.php が外したオートロードを戻す）
     */
    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 2);
        chdir($root);
        require_once $root . '/include/utils/utils.php';
        require_once $root . '/includes/Loader.php';
        spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        vimport('includes.runtime.EntryPoint');
        require_once $root . '/modules/Users/Users.php';
        require_once $root . '/include/events/include.inc';
        date_default_timezone_set('UTC');
        self::$initialized = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::ADMIN_USER_ID);
        $this->tabId = (int) getTabid(self::MODULE);
        RecordVersion_Config_Model::clearCache();
        $this->savedEnabled = RecordVersion_Config_Model::isEnabledTab($this->tabId);
        $this->savedRelationIds = RecordVersion_Config_Model::getIncludedRelationIds($this->tabId);
    }

    protected function tearDown(): void
    {
        foreach ($this->registeredHandlers as [$event, $class]) {
            $this->db->pquery('DELETE FROM vtiger_eventhandlers WHERE event_name = ? AND handler_class = ?', [$event, $class]);
        }
        // イベントのキャッシュは「全件読み込み済み」になると、あとから登録したイベントを読み直さないため全部消す
        \VTEventTrigger::clearCache();
        if (!empty($this->createdRecordIds)) {
            $ids = $this->createdRecordIds;
            $marks = generateQuestionMarks($ids);
            $this->db->pquery("DELETE FROM vtiger_recordversion WHERE crmid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_modtracker_detail WHERE id IN (SELECT id FROM vtiger_modtracker_basic WHERE crmid IN ($marks))", $ids);
            $this->db->pquery("DELETE FROM vtiger_modtracker_basic WHERE crmid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_potential WHERE potentialid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_crmentity WHERE crmid IN ($marks)", $ids);
        }
        $this->loginAs(self::ADMIN_USER_ID);
        if ($this->savedEnabled) {
            RecordVersion_Setup_Model::enable($this->tabId);
        } else {
            RecordVersion_Setup_Model::disable($this->tabId);
        }
        RecordVersion_Config_Model::saveIncludedRelationIds($this->tabId, $this->savedRelationIds);
        RecordVersion_Config_Model::clearCache();
        \Vtiger_Cache::$cacheEnable = true;
        $this->createdRecordIds = [];
        $this->registeredHandlers = [];
        parent::tearDown();
    }

    protected function loginAs(int $userId): void
    {
        $user = new Users();
        $user->retrieveCurrentUserInfoFromFile($userId);
        $GLOBALS['current_user'] = $user;
        \Users_Record_Model::$currentUserModels = [];
    }

    /**
     * 管理者でない、案件を編集できる利用者（シードの一般ユーザー）
     */
    protected function getNonAdminUserId(): int
    {
        $id = (int) $this->scalar("SELECT id FROM vtiger_users WHERE is_admin = 'off' AND status = 'Active' AND deleted = 0 ORDER BY id LIMIT 1");
        if ($id === 0) {
            self::markTestSkipped('管理者でないユーザーがテスト用DBにいないため飛ばします');
        }
        return $id;
    }

    protected function enableVersioning(): void
    {
        RecordVersion_Setup_Model::enable($this->tabId);
        RecordVersion_Config_Model::clearCache();
    }

    protected function disableVersioning(): void
    {
        RecordVersion_Setup_Model::disable($this->tabId);
        RecordVersion_Config_Model::clearCache();
    }

    /**
     * 案件を作る（通常の保存処理を通す）
     *
     * @param array<string, mixed> $values
     */
    protected function createPotential(string $name, array $values = []): int
    {
        $accountId = (int) $this->scalar("SELECT crmid FROM vtiger_crmentity WHERE setype = 'Accounts' AND deleted = 0 ORDER BY crmid LIMIT 1");
        $record = Vtiger_Record_Model::getCleanInstance(self::MODULE);
        $record->set('potentialname', self::PREFIX . $name);
        $record->set('related_to', $accountId);
        $record->set('closingdate', '2027-01-31');
        $record->set('sales_stage', 'Prospecting');
        $record->set('assigned_user_id', self::ADMIN_USER_ID);
        foreach ($values as $field => $value) {
            $record->set($field, $value);
        }
        $record->set('mode', '');
        $record->save();
        $id = (int) $record->getId();
        $this->createdRecordIds[] = $id;
        return $id;
    }

    /**
     * テストの間だけイベントハンドラを登録する
     */
    protected function registerTestHandler(string $event, string $class): void
    {
        $manager = new \VTEventsManager($this->db);
        $manager->registerHandler($event, self::HANDLER_PATH, $class);
        $this->registeredHandlers[] = [$event, $class];
        \VTEventTrigger::clearCache();
    }

    protected function versionCount(int $recordId): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM vtiger_recordversion WHERE crmid = ?', [$recordId]);
    }

    /**
     * 「更新」の更新履歴のうち、版の変更の件数
     */
    protected function versionChangeLogCount(int $recordId): int
    {
        return (int) $this->scalar(
            "SELECT COUNT(*) FROM vtiger_modtracker_basic b JOIN vtiger_modtracker_detail d ON d.id = b.id
             WHERE b.crmid = ? AND b.status = '0' AND d.fieldname = ?",
            [$recordId, RecordVersion_Config_Model::FIELD_NAME]
        );
    }

    /**
     * SQL の結果の最初の行の最初の列（無ければ空）
     *
     * @param array<int, mixed> $params
     */
    protected function scalar(string $sql, array $params = []): string
    {
        $rows = RecordVersion_Util_Helper::fetchRows($this->db->pquery($sql, $params));
        if (empty($rows)) {
            return '';
        }
        return RecordVersion_Util_Helper::toStr(reset($rows[0]));
    }

    /**
     * 過去の版に保存した項目（項目 ID => 内容）
     *
     * @return array<string, array<string, mixed>>
     */
    protected function snapshotFields(int $recordId, int $versionNo = 1): array
    {
        $fields = [];
        foreach (RecordVersion_Util_Helper::toArray($this->snapshot($recordId, $versionNo)['fields'] ?? []) as $id => $entry) {
            $fields[$id] = RecordVersion_Util_Helper::toArray($entry);
        }
        return $fields;
    }

    /**
     * 過去の版に保存した項目（項目名 => 内容）
     *
     * @return array<string, array<string, mixed>>
     */
    protected function snapshotFieldsByName(int $recordId, int $versionNo = 1): array
    {
        $fields = [];
        foreach ($this->snapshotFields($recordId, $versionNo) as $entry) {
            $fields[RecordVersion_Util_Helper::toStr($entry['name'] ?? '')] = $entry;
        }
        return $fields;
    }

    /**
     * 過去の版に保存した関連（何本目か）
     *
     * @return array<string, mixed>
     */
    protected function snapshotRelation(int $recordId, int $index, int $versionNo = 1): array
    {
        $relations = RecordVersion_Util_Helper::toList($this->snapshot($recordId, $versionNo)['relations'] ?? []);
        return RecordVersion_Util_Helper::toArray($relations[$index] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(int $recordId, int $versionNo): array
    {
        $version = RecordVersion_Version_Model::getByNumber($recordId, $versionNo);
        $this->assertNotNull($version, "版 {$versionNo} が保存されていない");
        return $version->getSnapshot();
    }

    protected function currentVersion(int $recordId): int
    {
        return RecordVersion_Config_Model::getCurrentVersionNo($recordId);
    }
}
