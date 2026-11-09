<?php

/**
 * 版管理（#1964）の設定を読み書きする
 *
 * 版管理用の画面はモジュールとして vtiger_tab に登録しないため、
 * Vtiger_Module_Model ではなくこのクラスで設定を扱う。
 */
class RecordVersion_Config_Model
{
    public const MODULE_NAME = 'RecordVersion';
    public const MODULES_TABLE = 'vtiger_recordversion_modules';
    public const RELATIONS_TABLE = 'vtiger_recordversion_relations';
    public const VERSIONS_TABLE = 'vtiger_recordversion';

    /** 「版」項目の項目名・表示名（値は vtiger_crmentity.version に持つ） */
    public const FIELD_NAME = 'record_version';
    public const FIELD_LABEL = 'LBL_RECORD_VERSION';
    public const FIELD_TABLE = 'vtiger_crmentity';
    public const FIELD_COLUMN = 'version';

    /** 改版理由の最大文字数 */
    public const MAX_REASON_LENGTH = 1000;

    /**
     * 版管理の対象にできないモジュール（仕様 概要 1-3 No.1）
     * ドキュメントは独自のファイルバージョン管理を持つため対象外
     */
    public const EXCLUDED_MODULES = [
        'Emails', 'Webmails', 'ModComments', 'Calendar', 'Events', 'Documents',
        'Users', 'PBXManager', 'SMSNotifier',
    ];

    /** 版に含める関連の候補から外す関連先モジュール（仕様 概要 1-3 No.2） */
    public const EXCLUDED_RELATED_MODULES = [
        'Emails', 'Webmails', 'ModComments', 'Calendar', 'Events', 'Users', 'PBXManager', 'SMSNotifier',
    ];

    /** レコードの一覧を出さない関連（履歴・ガントチャートなど。仕様 概要 1-3 No.2） */
    public const NON_LIST_RELATION_FUNCTIONS = [
        'get_history', 'get_activities', 'get_emails', 'get_comments', 'get_users', 'get_gantt_chart',
        'get_stage_history', 'get_ticket_history', 'get_quotestagehistory', 'get_sostatushistory',
        'get_postatushistory', 'get_invoicestatushistory',
    ];

    /** @var array<int, bool>|null tabid => 版管理「はい」 */
    private static $enabledCache = null;

    /** @var array<int, bool> tabid => 「版」項目を登録済み */
    private static $fieldCache = [];

    public static function clearCache(): void
    {
        self::$enabledCache = null;
        self::$fieldCache = [];
    }

    /**
     * モジュールが版管理「はい」か
     */
    public static function isEnabled(string $moduleName): bool
    {
        $tabId = (int) getTabid($moduleName);
        if ($tabId <= 0) {
            return false;
        }
        return self::isEnabledTab($tabId);
    }

    public static function isEnabledTab(int $tabId): bool
    {
        $enabled = self::loadEnabled();
        return !empty($enabled[$tabId]);
    }

    /**
     * 版管理「はい」のモジュールの tabid
     *
     * @return int[]
     */
    public static function getEnabledTabIds(): array
    {
        return array_keys(array_filter(self::loadEnabled()));
    }

    /**
     * 版管理の設定を保存する（「版」項目・ボタンの登録は RecordVersion_Setup_Model が行う）
     */
    public static function saveEnabled(int $tabId, bool $enabled): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'INSERT INTO ' . self::MODULES_TABLE . ' (tabid, enabled) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)',
            [$tabId, $enabled ? 1 : 0]
        );
        self::$enabledCache = null;
    }

    /**
     * 版に含める関連（vtiger_relatedlists.relation_id）
     *
     * @return int[]
     */
    public static function getIncludedRelationIds(int $tabId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT relation_id FROM ' . self::RELATIONS_TABLE . ' WHERE tabid = ? ORDER BY relation_id',
            [$tabId]
        );
        $ids = [];
        while ($row = $db->fetch_array($result)) {
            $ids[] = (int) $row['relation_id'];
        }
        return $ids;
    }

    /**
     * 版に含める関連を保存する（すでにある過去の版は変えない）
     *
     * @param int[] $relationIds
     */
    public static function saveIncludedRelationIds(int $tabId, array $relationIds): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM ' . self::RELATIONS_TABLE . ' WHERE tabid = ?', [$tabId]);
        foreach (array_unique(array_map('intval', $relationIds)) as $relationId) {
            if ($relationId > 0) {
                $db->pquery(
                    'INSERT INTO ' . self::RELATIONS_TABLE . ' (tabid, relation_id) VALUES (?, ?)',
                    [$tabId, $relationId]
                );
            }
        }
    }

    /**
     * モジュールに「版」項目が登録済みか（一度でも版管理「はい」にしたモジュール）
     */
    public static function hasVersionField(int $tabId): bool
    {
        if (!array_key_exists($tabId, self::$fieldCache)) {
            self::$fieldCache[$tabId] = self::getVersionFieldId($tabId) > 0;
        }
        return self::$fieldCache[$tabId];
    }

    public static function getVersionFieldId(int $tabId): int
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?',
            [$tabId, self::FIELD_NAME]
        );
        if ($db->num_rows($result) === 0) {
            return 0;
        }
        return (int) $db->query_result($result, 0, 'fieldid');
    }

    /**
     * 版管理の対象にできるモジュールか（仕様 概要 1-3 No.1）
     */
    public static function isSupportedModule(string $moduleName): bool
    {
        return !in_array($moduleName, self::EXCLUDED_MODULES, true);
    }

    /**
     * レコードの今の版番号（0 は版管理していない）
     */
    public static function getCurrentVersionNo(int $recordId): int
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT ' . self::FIELD_COLUMN . ' FROM ' . self::FIELD_TABLE . ' WHERE crmid = ?',
            [$recordId]
        );
        if ($db->num_rows($result) === 0) {
            return 0;
        }
        return (int) $db->query_result($result, 0, self::FIELD_COLUMN);
    }

    /**
     * @return array<int, bool>
     */
    private static function loadEnabled(): array
    {
        if (self::$enabledCache === null) {
            self::$enabledCache = [];
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT tabid, enabled FROM ' . self::MODULES_TABLE, []);
            if ($result) {
                while ($row = $db->fetch_array($result)) {
                    self::$enabledCache[(int) $row['tabid']] = ((int) $row['enabled']) === 1;
                }
            }
        }
        return self::$enabledCache;
    }
}
