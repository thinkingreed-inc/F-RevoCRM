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
     * 項目設定の「ブロックの追加」で、通知ブロックを追加する（1 モジュールに 1 つ）
     *
     * 「すでにあるか確かめる」から「ブロックを作って印を付ける」までを、モジュールごとの DB のロックで
     * 1 つずつ通す。ロックが無いと、別々の利用者が同時に追加したときに、どれも「まだ無い」と判断して
     * ブロックを作り、印の付かない空のブロック（名前は「通知」）が残る。
     *
     * @param Vtiger_Module_Model|false|null $moduleModel
     * @return array{id: int, error: string} error は言語ファイル（Settings:NotificationBlock）のキー。成功なら空
     */
    public static function addBlock(mixed $moduleModel, int $beforeBlockId, bool $sendOnCreate): array
    {
        $moduleName = $moduleModel instanceof Vtiger_Module_Model ? NotificationBlock_Util_Helper::toStr($moduleModel->getName()) : '';
        if (!($moduleModel instanceof Vtiger_Module_Model) || !self::isSupportedModule($moduleName)) {
            return ['id' => 0, 'error' => 'LBL_NOTIFICATION_BLOCK_NOT_SUPPORTED'];
        }
        $tabId = NotificationBlock_Util_Helper::toInt($moduleModel->getId());
        $db = PearDatabase::getInstance();
        $lockName = 'notificationblock_layout_' . $tabId;
        $locked = NotificationBlock_Util_Helper::fetchRawRows($db->pquery('SELECT GET_LOCK(?, 10) AS locked', [$lockName]));
        if (NotificationBlock_Util_Helper::toInt($locked[0]['locked'] ?? 0) !== 1) {
            return ['id' => 0, 'error' => 'LBL_NOTIFICATION_BLOCK_EXISTS'];
        }
        try {
            // ほかの利用者がロックの前に追加していないかを、読み直して確かめる
            self::clearCache();
            if (self::hasBlock($moduleName)) {
                return ['id' => 0, 'error' => 'LBL_NOTIFICATION_BLOCK_EXISTS'];
            }
            $block = new Settings_LayoutEditor_Block_Model();
            $block->set('label', self::BLOCK_LABEL);
            $block->set('iscustom', '1');
            if ($beforeBlockId > 0) {
                $beforeBlock = Vtiger_Block_Model::getInstance($beforeBlockId);
                $beforeSequence = NotificationBlock_Util_Helper::toInt($beforeBlock->get('sequence'));
                $block->set('sequence', $beforeSequence + 1);
                // 後ろのブロックを 1 つずつ下げる（Vtiger_Block_Model::pushDown() と同じ SQL。pushDown() は引数の型の
                // 注釈が誤っている（@param type）ため静的解析を通らない。pushDown() が行うブロックのキャッシュの削除は、
                // キャッシュが 1 回のリクエストの中だけのもので、このリクエストは追加したら終わるため行わない）
                $db->pquery('UPDATE vtiger_blocks SET sequence = sequence + 1 WHERE sequence > ? AND tabid = ?', [$beforeSequence, $tabId]);
            }
            $blockId = NotificationBlock_Util_Helper::toInt($block->save($moduleModel));
            self::register($tabId, $blockId, $sendOnCreate);
            return ['id' => $blockId, 'error' => ''];
        } finally {
            $db->pquery('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
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
