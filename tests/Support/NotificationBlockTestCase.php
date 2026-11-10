<?php

declare(strict_types=1);

namespace Tests\Support;

use Migration20261110120556_SetupNotificationBlock;
use NotificationBlock_Config_Model;
use NotificationBlock_Notifier_Model;
use NotificationBlock_Util_Helper;
use NotificationBlockHandler;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Users;
use Vtiger_Record_Model;

/**
 * 通知ブロック（#1965）の結合テストの共通基盤
 *
 * テスト用 DB の案件（Potentials）に通知ブロックを置いて使う。メールは実際には送らず、
 * NotificationBlockFakeMailSender に記録する。作ったレコード・ユーザー・役割・グループ・通知ブロックは
 * tearDown() でまとめて消し、通知ブロックの設定とメール文面はテストごとに退避・復元する。
 */
abstract class NotificationBlockTestCase extends TestCase
{
    public const ADMIN_USER_ID = 1;
    public const PREFIX = 'SPECTEST_NB_';
    public const MODULE = 'Potentials';

    private static bool $initialized = false;

    /** @var list<int> テストの間だけ止めた、ファイルの無いイベントハンドラ */
    private static array $disabledHandlerIds = [];

    protected PearDatabase $db;
    protected int $tabId = 0;
    protected int $blockId = 0;
    protected NotificationBlockFakeMailSender $mailSender;

    /** @var list<int> */
    private array $createdRecordIds = [];
    /** @var list<int> */
    private array $createdUserIds = [];
    /** @var list<string> */
    private array $createdRoleIds = [];
    /** @var list<int> */
    private array $createdGroupIds = [];
    /** @var list<int> */
    private array $createdBlockIds = [];

    /** @var array<int, array<string, mixed>> */
    private array $savedSettings = [];
    /** @var array<int, array<string, mixed>> */
    private array $savedTemplates = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
        self::prepareDatabase();
    }

    public static function tearDownAfterClass(): void
    {
        if (!empty(self::$disabledHandlerIds)) {
            $db = PearDatabase::getInstance();
            $db->pquery(
                'UPDATE vtiger_eventhandlers SET is_active = 1 WHERE eventhandler_id IN (' . generateQuestionMarks(self::$disabledHandlerIds) . ')',
                self::$disabledHandlerIds
            );
            self::$disabledHandlerIds = [];
            \VTEventTrigger::clearCache();
        }
        parent::tearDownAfterClass();
    }

    private static function requireTestDatabase(): void
    {
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT DATABASE() AS name', []);
            $connected = $result !== false;
        } catch (\Throwable) {
            $connected = false;
        }
        if (!$connected) {
            self::markTestSkipped('テスト用DBに接続できないため飛ばします');
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
        require_once $root . '/setup/migration/scripts/20261110120556_setup_notification_block.php';
        require_once $root . '/modules/NotificationBlock/handlers/NotificationBlockHandler.php';
        require_once __DIR__ . '/NotificationBlockFakeMailSender.php';
        date_default_timezone_set('UTC');
        self::$initialized = true;
    }

    /**
     * テスト用 DB に通知ブロックのテーブル・ハンドラを用意する（マイグレーションは何度流しても同じ）
     *
     * テスト用 DB をほかのブランチと共用していると、このブランチに無いファイルのイベントハンドラが
     * 登録されていて、レコードの保存で落ちる。そうしたハンドラはこのクラスのテストの間だけ止める。
     */
    private static function prepareDatabase(): void
    {
        ob_start();
        try {
            (new Migration20261110120556_SetupNotificationBlock())->process();
        } finally {
            ob_end_clean();
        }
        $db = PearDatabase::getInstance();
        $rows = NotificationBlock_Util_Helper::fetchRawRows($db->pquery(
            'SELECT eventhandler_id, handler_path FROM vtiger_eventhandlers WHERE is_active = 1',
            []
        ));
        foreach ($rows as $row) {
            if (!file_exists(NotificationBlock_Util_Helper::toStr($row['handler_path'] ?? ''))) {
                self::$disabledHandlerIds[] = NotificationBlock_Util_Helper::toInt($row['eventhandler_id'] ?? 0);
            }
        }
        if (!empty(self::$disabledHandlerIds)) {
            $db->pquery(
                'UPDATE vtiger_eventhandlers SET is_active = 0 WHERE eventhandler_id IN (' . generateQuestionMarks(self::$disabledHandlerIds) . ')',
                self::$disabledHandlerIds
            );
        }
        \VTEventTrigger::clearCache();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $_SESSION = [];
        $_REQUEST = [];
        $this->loginAs(self::ADMIN_USER_ID);
        $this->tabId = (int) getTabid(self::MODULE);

        $this->savedSettings = NotificationBlock_Util_Helper::fetchRawRows($this->db->pquery(
            'SELECT * FROM ' . NotificationBlock_Config_Model::SETTINGS_TABLE . ' WHERE tabid = ?',
            [$this->tabId]
        ));
        $this->savedTemplates = NotificationBlock_Util_Helper::fetchRawRows($this->db->pquery(
            'SELECT * FROM ' . NotificationBlock_Config_Model::TEMPLATES_TABLE . ' WHERE tabid = ?',
            [$this->tabId]
        ));
        $this->db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::TEMPLATES_TABLE . ' WHERE tabid = ?', [$this->tabId]);
        $this->blockId = $this->addNotificationBlock(false);

        $this->mailSender = new NotificationBlockFakeMailSender();
        NotificationBlock_Notifier_Model::setMailSender($this->mailSender);
        NotificationBlockHandler::resetForTest();
    }

    protected function tearDown(): void
    {
        NotificationBlock_Notifier_Model::setMailSender(null);
        NotificationBlockHandler::resetForTest();
        $_REQUEST = [];
        $_SESSION = [];

        if (!empty($this->createdRecordIds)) {
            $ids = $this->createdRecordIds;
            $marks = generateQuestionMarks($ids);
            $this->db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::TARGETS_TABLE . " WHERE crmid IN ($marks)", $ids);
            $this->db->pquery(
                'DELETE r FROM ' . NotificationBlock_Config_Model::RECIPIENTS_TABLE . ' r INNER JOIN '
                . NotificationBlock_Config_Model::HISTORY_TABLE . " h ON h.historyid = r.historyid WHERE h.crmid IN ($marks)",
                $ids
            );
            $this->db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::HISTORY_TABLE . " WHERE crmid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_modtracker_detail WHERE id IN (SELECT id FROM vtiger_modtracker_basic WHERE crmid IN ($marks))", $ids);
            $this->db->pquery("DELETE FROM vtiger_modtracker_basic WHERE crmid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_potentialscf WHERE potentialid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_potential WHERE potentialid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_crmentity WHERE crmid IN ($marks)", $ids);
        }
        if (!empty($this->createdGroupIds)) {
            $ids = $this->createdGroupIds;
            $marks = generateQuestionMarks($ids);
            $this->db->pquery("DELETE FROM vtiger_users2group WHERE groupid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_group2role WHERE groupid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_group2rs WHERE groupid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_group2grouprel WHERE groupid IN ($marks) OR containsgroupid IN ($marks)", array_merge($ids, $ids));
            $this->db->pquery("DELETE FROM vtiger_groups WHERE groupid IN ($marks)", $ids);
        }
        if (!empty($this->createdUserIds)) {
            $ids = $this->createdUserIds;
            $marks = generateQuestionMarks($ids);
            $this->db->pquery("DELETE FROM vtiger_user2role WHERE userid IN ($marks)", $ids);
            $this->db->pquery("DELETE FROM vtiger_users WHERE id IN ($marks)", $ids);
        }
        if (!empty($this->createdRoleIds)) {
            $ids = $this->createdRoleIds;
            $this->db->pquery('DELETE FROM vtiger_role WHERE roleid IN (' . generateQuestionMarks($ids) . ')', $ids);
        }

        // 通知ブロックとメール文面を元に戻す
        if (!empty($this->createdBlockIds)) {
            $this->db->pquery(
                'DELETE FROM vtiger_blocks WHERE blockid IN (' . generateQuestionMarks($this->createdBlockIds) . ')',
                $this->createdBlockIds
            );
        }
        $this->db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::SETTINGS_TABLE . ' WHERE tabid = ?', [$this->tabId]);
        foreach ($this->savedSettings as $row) {
            $this->db->pquery(
                'INSERT INTO ' . NotificationBlock_Config_Model::SETTINGS_TABLE . ' (tabid, blockid, send_on_create) VALUES (?, ?, ?)',
                [$row['tabid'], $row['blockid'], $row['send_on_create']]
            );
        }
        $this->db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::TEMPLATES_TABLE . ' WHERE tabid = ?', [$this->tabId]);
        foreach ($this->savedTemplates as $row) {
            $this->db->pquery(
                'INSERT INTO ' . NotificationBlock_Config_Model::TEMPLATES_TABLE . ' (tabid, language, subject, body) VALUES (?, ?, ?, ?)',
                [$row['tabid'], $row['language'], $row['subject'], $row['body']]
            );
        }
        NotificationBlock_Config_Model::clearCache();
        \Vtiger_Cache::$cacheEnable = true;
        $this->loginAs(self::ADMIN_USER_ID);
        $this->createdRecordIds = [];
        $this->createdUserIds = [];
        $this->createdRoleIds = [];
        $this->createdGroupIds = [];
        $this->createdBlockIds = [];
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
     * 案件に通知ブロックを置く（項目設定の「ブロックの追加」と同じく、項目の無いブロックを足して印を付ける）
     */
    protected function addNotificationBlock(bool $sendOnCreate): int
    {
        $this->db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::SETTINGS_TABLE . ' WHERE tabid = ?', [$this->tabId]);
        NotificationBlock_Config_Model::clearCache();
        $module = \Vtiger_Module::getInstance(self::MODULE);
        $block = new \Vtiger_Block();
        $block->label = NotificationBlock_Config_Model::BLOCK_LABEL;
        $module->addBlock($block);
        $blockId = (int) $block->id;
        $this->createdBlockIds[] = $blockId;
        NotificationBlock_Config_Model::register($this->tabId, $blockId, $sendOnCreate);
        return $blockId;
    }

    protected function setSendOnCreate(bool $sendOnCreate): void
    {
        NotificationBlock_Config_Model::saveSendOnCreate($this->tabId, $sendOnCreate);
    }

    /**
     * 案件を作る（通常の保存処理を通す）
     */
    protected function createPotential(string $name): int
    {
        $accountId = (int) $this->scalar("SELECT crmid FROM vtiger_crmentity WHERE setype = 'Accounts' AND deleted = 0 ORDER BY crmid LIMIT 1");
        $record = Vtiger_Record_Model::getCleanInstance(self::MODULE);
        $record->set('potentialname', self::PREFIX . $name);
        $record->set('related_to', $accountId);
        $record->set('closingdate', '2027-01-31');
        $record->set('sales_stage', 'Prospecting');
        $record->set('assigned_user_id', self::ADMIN_USER_ID);
        $record->set('mode', '');
        $record->save();
        $id = (int) $record->getId();
        $this->createdRecordIds[] = $id;
        return $id;
    }

    /**
     * 作ったレコードとして後始末の対象にする（テストの中でほかの方法で作ったとき）
     */
    protected function trackRecord(int $recordId): void
    {
        $this->createdRecordIds[] = $recordId;
    }

    /**
     * テスト用のユーザーを作る（ログインはしない。宛先の展開・メールの言語を確かめる用）
     */
    protected function createUser(string $name, string $email, string $roleId = 'H2', string $status = 'Active', int $deleted = 0, string $language = 'ja_jp'): int
    {
        $userId = (int) $this->db->getUniqueID('vtiger_users');
        $this->db->pquery(
            "INSERT INTO vtiger_users (id, user_name, first_name, last_name, email1, status, deleted, is_admin, language,
             date_entered, time_zone, date_format, hour_format)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'off', ?, ?, 'UTC', 'yyyy-mm-dd', '24')",
            [$userId, strtolower(self::PREFIX) . $userId, '', self::PREFIX . $name, $email, $status, $deleted, $language, date('Y-m-d H:i:s')]
        );
        $this->db->pquery('INSERT INTO vtiger_user2role (userid, roleid) VALUES (?, ?)', [$userId, $roleId]);
        $this->createdUserIds[] = $userId;
        return $userId;
    }

    /**
     * テスト用の役割を作る
     */
    protected function createRole(string $name, string $parentRoleId = 'H1'): string
    {
        $parentRole = $this->scalar('SELECT parentrole FROM vtiger_role WHERE roleid = ?', [$parentRoleId]);
        $depth = (int) $this->scalar('SELECT depth FROM vtiger_role WHERE roleid = ?', [$parentRoleId]) + 1;
        $roleId = 'H' . $this->db->getUniqueID('vtiger_role');
        $this->db->pquery(
            'INSERT INTO vtiger_role (roleid, rolename, parentrole, depth, allowassignedrecordsto) VALUES (?, ?, ?, ?, 1)',
            [$roleId, self::PREFIX . $name, $parentRole . '::' . $roleId, $depth]
        );
        $this->createdRoleIds[] = $roleId;
        return $roleId;
    }

    /**
     * テスト用のグループを作る
     *
     * @param list<int> $userIds
     * @param list<string> $roleIds 役割（下位は含まない）
     * @param list<string> $roleAndSubIds 役割と下位の役割
     * @param list<int> $groupIds 入れ子にするグループ
     */
    protected function createGroup(string $name, array $userIds = [], array $roleIds = [], array $roleAndSubIds = [], array $groupIds = []): int
    {
        $groupId = (int) $this->db->getUniqueID('vtiger_users');
        $this->db->pquery('INSERT INTO vtiger_groups (groupid, groupname, description) VALUES (?, ?, ?)', [$groupId, self::PREFIX . $name, '']);
        foreach ($userIds as $userId) {
            $this->db->pquery('INSERT INTO vtiger_users2group (groupid, userid) VALUES (?, ?)', [$groupId, $userId]);
        }
        foreach ($roleIds as $roleId) {
            $this->db->pquery('INSERT INTO vtiger_group2role (groupid, roleid) VALUES (?, ?)', [$groupId, $roleId]);
        }
        foreach ($roleAndSubIds as $roleId) {
            $this->db->pquery('INSERT INTO vtiger_group2rs (groupid, roleandsubid) VALUES (?, ?)', [$groupId, $roleId]);
        }
        foreach ($groupIds as $containsGroupId) {
            $this->addGroupToGroup($groupId, $containsGroupId);
        }
        $this->createdGroupIds[] = $groupId;
        return $groupId;
    }

    protected function addGroupToGroup(int $groupId, int $containsGroupId): void
    {
        $this->db->pquery('INSERT INTO vtiger_group2grouprel (groupid, containsgroupid) VALUES (?, ?)', [$groupId, $containsGroupId]);
    }

    /**
     * SQL の結果の最初の行の最初の列（無ければ空）
     *
     * @param array<int, mixed> $params
     */
    protected function scalar(string $sql, array $params = []): string
    {
        $rows = NotificationBlock_Util_Helper::fetchRawRows($this->db->pquery($sql, $params));
        if (empty($rows)) {
            return '';
        }
        return NotificationBlock_Util_Helper::toStr(reset($rows[0]));
    }
}
