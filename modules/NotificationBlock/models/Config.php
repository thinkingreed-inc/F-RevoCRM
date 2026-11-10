<?php

/**
 * 通知ブロック（#1965）の設定（モジュールごとの通知ブロックと「作成時に送信」）
 *
 * 通知ブロックは、項目設定で追加した項目の無いブロック（vtiger_blocks）に、
 * 「通知ブロックである」という印をこのテーブルで付けて表す（vtiger_blocks の形は変えない）。
 * 1 モジュールに 1 つだけ置ける。
 */
class NotificationBlock_Config_Model
{
    public const MODULE_NAME = 'NotificationBlock';
    public const SETTINGS_MODULE = 'Settings:NotificationBlock';

    public const SETTINGS_TABLE = 'vtiger_notificationblock_settings';
    public const TEMPLATES_TABLE = 'vtiger_notificationblock_templates';
    public const TARGETS_TABLE = 'vtiger_notificationblock_targets';
    public const HISTORY_TABLE = 'vtiger_notificationblock_history';
    public const RECIPIENTS_TABLE = 'vtiger_notificationblock_recipients';

    /** 通知ブロックの名前（共通の言語ファイル languages/<言語>/Vtiger.php で「通知」「Notification」に訳す） */
    public const BLOCK_LABEL = 'LBL_NOTIFICATION_BLOCK';

    /**
     * 通知ブロックを置けないモジュール（仕様 概要 1-3 No.1）
     */
    public const EXCLUDED_MODULES = [
        'Emails', 'Webmails', 'ModComments', 'Calendar', 'Events', 'Users', 'PBXManager', 'SMSNotifier',
    ];

    /** @var array<int, array{blockid: int, send_on_create: bool}>|null */
    private static ?array $settings = null;

    /**
     * 通知ブロックを置けるモジュールか（エンティティ・有効・対象外でない）
     */
    public static function isSupportedModule(string $moduleName): bool
    {
        if ($moduleName === '' || in_array($moduleName, self::EXCLUDED_MODULES, true)) {
            return false;
        }
        $db = PearDatabase::getInstance();
        // presence：0 と 2 は有効、1 は「モジュール管理」で無効にしたもの
        $result = $db->pquery(
            'SELECT 1 FROM vtiger_tab WHERE name = ? AND isentitytype = 1 AND presence IN (0, 2)',
            [$moduleName]
        );
        return $db->num_rows($result) > 0;
    }

    /**
     * モジュールの通知ブロックの設定（通知ブロックが無ければ null）
     *
     * 印が残っていても、項目設定で消したブロック（vtiger_blocks に無い）は「無い」とみなす。
     * 無効にしたモジュール・対象外のモジュールも「無い」とみなす。
     *
     * @return array{blockid: int, send_on_create: bool}|null
     */
    public static function getSetting(string $moduleName): ?array
    {
        if (!self::isSupportedModule($moduleName)) {
            return null;
        }
        $tabId = NotificationBlock_Util_Helper::toInt(getTabid($moduleName));
        $settings = self::loadSettings();
        return $settings[$tabId] ?? null;
    }

    public static function hasBlock(string $moduleName): bool
    {
        return self::getSetting($moduleName) !== null;
    }

    public static function getBlockId(string $moduleName): int
    {
        $setting = self::getSetting($moduleName);
        return $setting === null ? 0 : $setting['blockid'];
    }

    public static function isSendOnCreate(string $moduleName): bool
    {
        $setting = self::getSetting($moduleName);
        return $setting !== null && $setting['send_on_create'];
    }

    /**
     * このブロックが通知ブロックか
     */
    public static function isNotificationBlock(int $blockId): bool
    {
        if ($blockId <= 0) {
            return false;
        }
        foreach (self::loadSettings() as $setting) {
            if ($setting['blockid'] === $blockId) {
                return true;
            }
        }
        return false;
    }

    /**
     * 追加したブロックを、そのモジュールの通知ブロックにする
     */
    public static function register(int $tabId, int $blockId, bool $sendOnCreate): void
    {
        $db = PearDatabase::getInstance();
        // 消したブロックの印が残っていれば置き換える（1 モジュールに 1 つ）
        $db->pquery('DELETE FROM ' . self::SETTINGS_TABLE . ' WHERE tabid = ?', [$tabId]);
        $db->pquery(
            'INSERT INTO ' . self::SETTINGS_TABLE . ' (tabid, blockid, send_on_create) VALUES (?, ?, ?)',
            [$tabId, $blockId, $sendOnCreate ? 1 : 0]
        );
        self::clearCache();
    }

    /**
     * 通知ブロックを削除したときに印を外す（通知先・送信履歴・メール文面は残す）
     */
    public static function unregisterBlock(int $blockId): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM ' . self::SETTINGS_TABLE . ' WHERE blockid = ?', [$blockId]);
        self::clearCache();
    }

    public static function saveSendOnCreate(int $tabId, bool $sendOnCreate): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE ' . self::SETTINGS_TABLE . ' SET send_on_create = ? WHERE tabid = ?',
            [$sendOnCreate ? 1 : 0, $tabId]
        );
        self::clearCache();
    }

    public static function clearCache(): void
    {
        self::$settings = null;
    }

    /**
     * @return array<int, array{blockid: int, send_on_create: bool}> tabid => 設定
     */
    private static function loadSettings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT s.tabid, s.blockid, s.send_on_create FROM ' . self::SETTINGS_TABLE . ' s
             INNER JOIN vtiger_blocks b ON b.blockid = s.blockid AND b.tabid = s.tabid',
            []
        );
        $settings = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $settings[NotificationBlock_Util_Helper::toInt($row['tabid'] ?? 0)] = [
                'blockid' => NotificationBlock_Util_Helper::toInt($row['blockid'] ?? 0),
                'send_on_create' => NotificationBlock_Util_Helper::toInt($row['send_on_create'] ?? 0) === 1,
            ];
        }
        self::$settings = $settings;
        return $settings;
    }
}
