<?php

/**
 * マイグレーション: setup_notification_block
 * 生成日時: 20261110120556
 *
 * 通知ブロック（#1965）の土台を用意する。
 * - 通知ブロックの設定（モジュールごとのブロックと「作成時に送信」）
 * - メール文面（モジュール・言語ごとの件名・本文）
 * - 通知先（レコードごとの役割・グループ・ユーザー）
 * - 送信履歴と宛先ごとの結果
 * - 保存・完全削除のイベントハンドラ
 * - 全画面で読み込む JavaScript（保存したあとに移った先の画面で、送信の結果を出す）
 *
 * 通知ブロック用の画面はモジュールとして vtiger_tab に登録しない（プロファイル・モジュール管理に出さないため）。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/events/include.inc';

class Migration20261110120556_SetupNotificationBlock extends FRMigrationClass
{
    private const SETTINGS_TABLE = 'vtiger_notificationblock_settings';
    private const TEMPLATES_TABLE = 'vtiger_notificationblock_templates';
    private const TARGETS_TABLE = 'vtiger_notificationblock_targets';
    private const HISTORY_TABLE = 'vtiger_notificationblock_history';
    private const RECIPIENTS_TABLE = 'vtiger_notificationblock_recipients';

    private const HANDLER_PATH = 'modules/NotificationBlock/handlers/NotificationBlockHandler.php';
    private const HANDLER_CLASS = 'NotificationBlockHandler';
    private const PERMANENT_DELETE_EVENT = 'vtiger.batchevent.afterpermanentdelete';

    private const SCRIPT_LABEL = 'NotificationBlockScript';
    private const SCRIPT_URL = 'public/layouts/v7/modules/NotificationBlock/resources/NotificationBlock.js';

    public function process(): void
    {
        $this->createSettingsTable();
        $this->createTemplatesTable();
        $this->createTargetsTable();
        $this->createHistoryTable();
        $this->createRecipientsTable();
        $this->registerEventHandlers();
        $this->registerHeaderScript();
        $this->log('マイグレーション setup_notification_block が正常に完了しました');
    }

    private function createSettingsTable(): void
    {
        $this->createTable(self::SETTINGS_TABLE, '(
            tabid INT NOT NULL,
            blockid INT NOT NULL,
            send_on_create TINYINT(1) NOT NULL DEFAULT 0,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tabid),
            UNIQUE KEY uk_notificationblock_settings_blockid (blockid)
        )');
    }

    private function createTemplatesTable(): void
    {
        // 通知ブロックを削除しても残す（再び追加するとそのまま使う）ため、ブロックではなくモジュールに持つ
        $this->createTable(self::TEMPLATES_TABLE, '(
            tabid INT NOT NULL,
            language VARCHAR(10) NOT NULL,
            subject VARCHAR(255) NOT NULL DEFAULT \'\',
            body MEDIUMTEXT NOT NULL,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tabid, language)
        )');
    }

    private function createTargetsTable(): void
    {
        // target_id は役割なら H2 のような文字列、グループ・ユーザーなら数値の ID
        $this->createTable(self::TARGETS_TABLE, '(
            crmid INT NOT NULL,
            target_type VARCHAR(10) NOT NULL,
            target_id VARCHAR(50) NOT NULL,
            sequence INT NOT NULL DEFAULT 0,
            PRIMARY KEY (crmid, target_type, target_id),
            KEY idx_notificationblock_targets_target (target_type, target_id)
        )');
    }

    private function createHistoryTable(): void
    {
        $this->createTable(self::HISTORY_TABLE, '(
            historyid INT NOT NULL AUTO_INCREMENT,
            crmid INT NOT NULL,
            setype VARCHAR(100) NOT NULL,
            sent_by INT NOT NULL,
            sender_name VARCHAR(255) NOT NULL DEFAULT \'\',
            method VARCHAR(20) NOT NULL,
            method_label VARCHAR(255) NOT NULL DEFAULT \'\',
            method_module VARCHAR(100) NOT NULL DEFAULT \'\',
            comment TEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            recipient_count INT NOT NULL DEFAULT 0,
            failure_reason VARCHAR(255) NOT NULL DEFAULT \'\',
            started_at DATETIME NOT NULL,
            last_progress_at DATETIME NOT NULL,
            finished_at DATETIME NULL,
            PRIMARY KEY (historyid),
            KEY idx_notificationblock_history_crmid (crmid, historyid),
            KEY idx_notificationblock_history_status (crmid, status)
        )');
    }

    private function createRecipientsTable(): void
    {
        $this->createTable(self::RECIPIENTS_TABLE, '(
            historyid INT NOT NULL,
            userid INT NOT NULL,
            user_name VARCHAR(255) NOT NULL DEFAULT \'\',
            email VARCHAR(255) NOT NULL DEFAULT \'\',
            status VARCHAR(20) NOT NULL,
            reason VARCHAR(500) NOT NULL DEFAULT \'\',
            sequence INT NOT NULL DEFAULT 0,
            PRIMARY KEY (historyid, userid)
        )');
    }

    private function createTable(string $table, string $definition): void
    {
        if ($this->checkTableExists($table)) {
            $this->log("テーブル {$table} は既に存在するためスキップします");
            return;
        }
        $this->db->pquery(
            "CREATE TABLE {$table} {$definition} ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            []
        );
        $this->log("テーブル {$table} を作成しました");
    }

    private function registerEventHandlers(): void
    {
        $manager = new VTEventsManager($this->db);
        foreach (['vtiger.entity.aftersave', self::PERMANENT_DELETE_EVENT] as $event) {
            // registerHandler() は同じ組み合わせが登録済みなら何もしない
            $manager->registerHandler($event, self::HANDLER_PATH, self::HANDLER_CLASS);
            $this->log("イベント {$event} に " . self::HANDLER_CLASS . ' を登録しました（登録済みならそのまま）');
        }
    }

    /**
     * 全画面（tabid=0）で読み込む JavaScript を登録する
     *
     * 画面のヘッダーは、登録した URL のファイルがあるかを、製品のフォルダからの相対パスで確かめてから読み込む
     * （modules/Vtiger/views/Header.php の getHeaderScripts()）。JavaScript の実体は public/ の下にあるので
     * public/ から書く（ブラウザーからも、製品のフォルダからの相対パスで開ける）。
     */
    private function registerHeaderScript(): void
    {
        $existing = $this->db->pquery(
            'SELECT linkid FROM vtiger_links WHERE tabid = 0 AND linktype = ? AND linklabel = ?',
            ['HEADERSCRIPT', self::SCRIPT_LABEL]
        );
        if ($this->db->num_rows($existing) > 0) {
            $this->log('全画面の JavaScript ' . self::SCRIPT_LABEL . ' は既に登録済みのためスキップします');
            return;
        }
        // vtiger_links_seq が実際の最大 ID より遅れている環境があるため、最大 ID の次を使い、連番も追いつかせる
        $result = $this->db->pquery('SELECT MAX(linkid) AS max_id FROM vtiger_links', []);
        $linkId = (int) $this->db->query_result($result, 0, 'max_id') + 1;
        $this->db->pquery('UPDATE vtiger_links_seq SET id = GREATEST(id, ?)', [$linkId]);
        $this->db->pquery(
            'INSERT INTO vtiger_links (linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence)
             VALUES (?, 0, ?, ?, ?, ?, 0)',
            [$linkId, 'HEADERSCRIPT', self::SCRIPT_LABEL, self::SCRIPT_URL, '']
        );
        $this->log('全画面の JavaScript ' . self::SCRIPT_LABEL . ' を登録しました');
    }
}
