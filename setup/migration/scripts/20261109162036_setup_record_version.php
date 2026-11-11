<?php

/**
 * マイグレーション: setup_record_version
 * 生成日時: 20261109162036
 *
 * レコードの版管理（#1964）の土台を用意する。
 * - 設定テーブル（モジュールごとの「版管理」と、版に含める関連）
 * - 過去の版のテーブル（1 版を 1 行、内容は JSON で持つ）
 * - システム設定 →「モジュール管理」の「版管理」メニュー
 * - 保存・完全削除のイベントハンドラ
 *
 * 版管理用の画面はモジュールとして vtiger_tab に登録しない（プロファイル・モジュール管理に出さないため）。
 * 「版」項目と詳細画面のボタンは、システム設定でモジュールを「版管理：はい」にしたときに登録する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/events/include.inc';

class Migration20261109162036_SetupRecordVersion extends FRMigrationClass
{
    private const MODULES_TABLE = 'vtiger_recordversion_modules';
    private const RELATIONS_TABLE = 'vtiger_recordversion_relations';
    private const VERSIONS_TABLE = 'vtiger_recordversion';

    private const SETTINGS_BLOCK = 'LBL_MODULE_MANAGER';
    private const SETTINGS_FIELD = 'LBL_RECORD_VERSION_SETTINGS';
    private const SETTINGS_URL = 'index.php?module=RecordVersion&parent=Settings&view=Index';

    private const HANDLER_PATH = 'modules/RecordVersion/handlers/RecordVersionHandler.php';
    private const HANDLER_CLASS = 'RecordVersionHandler';
    private const PERMANENT_DELETE_EVENT = 'vtiger.batchevent.afterpermanentdelete';

    public function process(): void
    {
        $this->createModulesTable();
        $this->createRelationsTable();
        $this->createVersionsTable();
        $this->upgradeVersionsTable();
        $this->registerSettingsMenu();
        $this->registerEventHandlers();
        $this->log('マイグレーション setup_record_version が正常に完了しました');
    }

    private function createModulesTable(): void
    {
        if ($this->checkTableExists(self::MODULES_TABLE)) {
            $this->log('テーブル ' . self::MODULES_TABLE . ' は既に存在するためスキップします');
            return;
        }
        $this->db->pquery('CREATE TABLE ' . self::MODULES_TABLE . ' (
            tabid INT NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tabid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci', []);
        $this->log('テーブル ' . self::MODULES_TABLE . ' を作成しました');
    }

    private function createRelationsTable(): void
    {
        if ($this->checkTableExists(self::RELATIONS_TABLE)) {
            $this->log('テーブル ' . self::RELATIONS_TABLE . ' は既に存在するためスキップします');
            return;
        }
        $this->db->pquery('CREATE TABLE ' . self::RELATIONS_TABLE . ' (
            tabid INT NOT NULL,
            relation_id INT NOT NULL,
            PRIMARY KEY (tabid, relation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci', []);
        $this->log('テーブル ' . self::RELATIONS_TABLE . ' を作成しました');
    }

    private function createVersionsTable(): void
    {
        if ($this->checkTableExists(self::VERSIONS_TABLE)) {
            $this->log('テーブル ' . self::VERSIONS_TABLE . ' は既に存在するためスキップします');
            return;
        }
        // 同じレコードの同じ版番号は 1 件だけ（同時に改版したときの二重登録を DB でも防ぐ）
        $this->db->pquery('CREATE TABLE ' . self::VERSIONS_TABLE . ' (
            versionid INT NOT NULL AUTO_INCREMENT,
            crmid INT NOT NULL,
            setype VARCHAR(100) NOT NULL,
            version_no INT NOT NULL,
            reason TEXT NOT NULL,
            revised_by INT NOT NULL,
            valid_from DATETIME NOT NULL,
            revised_at DATETIME NOT NULL,
            snapshot LONGTEXT NOT NULL,
            PRIMARY KEY (versionid),
            UNIQUE KEY uk_recordversion_crmid_version (crmid, version_no),
            KEY idx_recordversion_setype_revised (setype, revised_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci', []);
        $this->log('テーブル ' . self::VERSIONS_TABLE . ' を作成しました');
    }

    /**
     * 先に作ったテーブル（valid_from が無い形）を今の形に合わせる
     *
     * 列の追加・既存の行の valid_from の補完・NOT NULL への変更・インデックスの付け替えを、
     * それぞれ「まだなら行う」形にしているので、途中で止まっても再実行で続きから合わせられる
     * （ALTER は暗黙にコミットされ、失敗しても巻き戻らないため）。
     */
    private function upgradeVersionsTable(): void
    {
        $table = self::VERSIONS_TABLE;
        if (!$this->checkColumnExists($table, 'valid_from')) {
            $this->db->pquery("ALTER TABLE {$table} ADD COLUMN valid_from DATETIME NULL AFTER revised_by", []);
            $this->log("テーブル {$table} に valid_from を追加しました");
        }
        // 版 1 はレコードの作成日時、版 N は版 N-1 を改版した日時（レコードが無ければ改版日時で代用）
        $this->db->pquery(
            "UPDATE {$table} rv
             JOIN (
                SELECT v.versionid,
                       COALESCE(
                           LAG(v.revised_at) OVER (PARTITION BY v.crmid ORDER BY v.version_no),
                           ce.createdtime,
                           v.revised_at
                       ) AS valid_from
                FROM {$table} v
                LEFT JOIN vtiger_crmentity ce ON ce.crmid = v.crmid
             ) calc ON calc.versionid = rv.versionid
             SET rv.valid_from = calc.valid_from
             WHERE rv.valid_from IS NULL",
            []
        );
        if ($this->isColumnNullable($table, 'valid_from')) {
            $this->db->pquery("ALTER TABLE {$table} MODIFY COLUMN valid_from DATETIME NOT NULL", []);
            $this->log("テーブル {$table} の valid_from を NOT NULL にしました");
        }
        if (!$this->checkIndexExists($table, 'idx_recordversion_setype_revised')) {
            $this->db->pquery("ALTER TABLE {$table} ADD INDEX idx_recordversion_setype_revised (setype, revised_at)", []);
            $this->log("テーブル {$table} にインデックス idx_recordversion_setype_revised を追加しました");
        }
        if ($this->checkIndexExists($table, 'idx_recordversion_setype')) {
            $this->db->pquery("ALTER TABLE {$table} DROP INDEX idx_recordversion_setype", []);
            $this->log("テーブル {$table} のインデックス idx_recordversion_setype を削除しました");
        }
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        $result = $this->db->pquery(
            'SELECT IS_NULLABLE AS nullable FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        return $result !== false && $this->db->num_rows($result) > 0 && $this->db->query_result($result, 0, 'nullable') === 'YES';
    }

    private function checkIndexExists(string $table, string $index): bool
    {
        $result = $this->db->pquery(
            'SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
        return $result !== false && (int) $this->db->query_result($result, 0, 'cnt') > 0;
    }

    private function registerSettingsMenu(): void
    {
        $existing = $this->db->pquery('SELECT fieldid FROM vtiger_settings_field WHERE name = ?', [self::SETTINGS_FIELD]);
        if ($this->db->num_rows($existing) > 0) {
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' は既に登録済みのためスキップします');
            return;
        }
        $blockResult = $this->db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label = ?', [self::SETTINGS_BLOCK]);
        if ($this->db->num_rows($blockResult) === 0) {
            throw new Exception('設定メニューのブロック ' . self::SETTINGS_BLOCK . ' が見つかりません');
        }
        $blockId = (int) $this->db->query_result($blockResult, 0, 'blockid');
        $seqResult = $this->db->pquery('SELECT MAX(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?', [$blockId]);
        $sequence = (int) $this->db->query_result($seqResult, 0, 'max_seq') + 1;
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');
        $this->db->pquery(
            'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)',
            [$fieldId, $blockId, self::SETTINGS_FIELD, '', 'LBL_RECORD_VERSION_DESCRIPTION', self::SETTINGS_URL, $sequence]
        );
        $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' を登録しました');
    }

    private function registerEventHandlers(): void
    {
        $manager = new VTEventsManager($this->db);
        $events = ['vtiger.entity.beforesave', 'vtiger.entity.aftersave', self::PERMANENT_DELETE_EVENT];
        foreach ($events as $event) {
            // registerHandler() は同じ組み合わせが登録済みなら何もしない
            $manager->registerHandler($event, self::HANDLER_PATH, self::HANDLER_CLASS);
            $this->log("イベント {$event} に " . self::HANDLER_CLASS . ' を登録しました（登録済みならそのまま）');
        }
    }
}
