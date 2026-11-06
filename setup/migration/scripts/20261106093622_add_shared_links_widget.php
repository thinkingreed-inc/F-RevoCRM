<?php
/**
 * マイグレーション: add_shared_links_widget
 * 生成日時: 20261106093622
 *
 * ダッシュボードの共有リンク（#1963）を追加する。
 * - ウィジェット（リンクのまとまり）・リンク・リクエストパラメータ・公開範囲の対象のテーブルを作る
 * - 最初のウィジェット「共有リンク」を作り、ホームのダッシュボードの「ウィジェットの追加」に出す
 *   （ウィジェット 1 つにつき vtiger_links に DASHBOARDWIDGET を 1 行登録する。2 つ目以降は管理画面で作る）
 * - 設定メニューの「システム構成」に管理画面を追加する
 *
 * 何度実行しても結果が変わらないようにしてある。
 * 仕様: docs/specs/shared_links_widget.md
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261106093622_AddSharedLinksWidget extends FRMigrationClass {

    /** 設定メニューを追加するブロック（システム構成） */
    const SETTINGS_BLOCK = 'LBL_CONFIGURATION';

    /** 設定メニュー名 */
    const SETTINGS_FIELD = 'LBL_SHARED_LINKS';

    /** 最初のウィジェットの見出し */
    const FIRST_WIDGET_TITLE = '共有リンク';

    /** ウィジェットを表示する URL（どのウィジェットかは ShowWidget が付ける linkid で見分ける） */
    const WIDGET_URL = 'index.php?module=Home&view=ShowWidget&name=SharedLinks';

    public function process() {
        $this->createSetsTable();
        $this->createLinksTable();
        $this->createParamsTable();
        $this->createTargetsTable();
        $this->createFirstWidget();
        $this->registerSettingsMenu();
    }

    private function createSetsTable() {
        if ($this->checkTableExists('vtiger_shared_link_sets')) {
            $this->log('テーブル vtiger_shared_link_sets は既に存在するためスキップします');
            return;
        }
        $this->db->pquery("CREATE TABLE vtiger_shared_link_sets (
            id INT AUTO_INCREMENT,
            title VARCHAR(30) NOT NULL COMMENT 'ウィジェットの見出し（vtiger_links.linklabel と同じ値）',
            linkid INT NOT NULL COMMENT 'vtiger_links.linkid（DASHBOARDWIDGET）',
            sequence INT NOT NULL DEFAULT 0,
            createdtime DATETIME NOT NULL,
            modifiedtime DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX idx_shared_link_sets_linkid (linkid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", array());
        $this->log('テーブル vtiger_shared_link_sets を作成しました');
    }

    private function createLinksTable() {
        if ($this->checkTableExists('vtiger_shared_links')) {
            $this->log('テーブル vtiger_shared_links は既に存在するためスキップします');
            return;
        }
        $this->db->pquery("CREATE TABLE vtiger_shared_links (
            id INT AUTO_INCREMENT,
            setid INT NOT NULL COMMENT 'vtiger_shared_link_sets.id（どのウィジェットに出すか）',
            title VARCHAR(100) NOT NULL,
            url VARCHAR(2000) NOT NULL,
            description TEXT DEFAULT NULL,
            method VARCHAR(4) NOT NULL DEFAULT 'GET' COMMENT 'GET / POST',
            open_mode VARCHAR(10) NOT NULL DEFAULT 'tab' COMMENT 'tab: 別タブ / window: 別ウィンドウ',
            window_width INT DEFAULT NULL,
            window_height INT DEFAULT NULL,
            noreferrer TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: リファラーを送らない',
            active TINYINT(1) NOT NULL DEFAULT 1,
            visibility VARCHAR(10) NOT NULL DEFAULT 'all' COMMENT 'all: 全員 / targets: 指定した対象のみ',
            sequence INT NOT NULL DEFAULT 0,
            createdtime DATETIME NOT NULL,
            modifiedtime DATETIME NOT NULL,
            modifiedby INT NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_shared_links_setid (setid, sequence)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", array());
        $this->log('テーブル vtiger_shared_links を作成しました');
    }

    private function createParamsTable() {
        if ($this->checkTableExists('vtiger_shared_link_params')) {
            $this->log('テーブル vtiger_shared_link_params は既に存在するためスキップします');
            return;
        }
        $this->db->pquery("CREATE TABLE vtiger_shared_link_params (
            id INT AUTO_INCREMENT,
            linkid INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            value_type VARCHAR(10) NOT NULL DEFAULT 'fixed' COMMENT 'fixed: 固定値 / user: ユーザー情報',
            value VARCHAR(1000) NOT NULL DEFAULT '' COMMENT 'fixed は固定値、user はユーザーの項目名',
            sequence INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            INDEX idx_shared_link_params_linkid (linkid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", array());
        $this->log('テーブル vtiger_shared_link_params を作成しました');
    }

    private function createTargetsTable() {
        if ($this->checkTableExists('vtiger_shared_link_targets')) {
            $this->log('テーブル vtiger_shared_link_targets は既に存在するためスキップします');
            return;
        }
        $this->db->pquery("CREATE TABLE vtiger_shared_link_targets (
            id INT AUTO_INCREMENT,
            linkid INT NOT NULL,
            target_type VARCHAR(20) NOT NULL COMMENT 'Users / Groups / Roles / RoleAndSubordinates',
            target_id VARCHAR(30) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX idx_shared_link_targets_unique (linkid, target_type, target_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", array());
        $this->log('テーブル vtiger_shared_link_targets を作成しました');
    }

    /**
     * 最初のウィジェット「共有リンク」を作る（ウィジェットが 1 つも無いときだけ）
     */
    private function createFirstWidget() {
        $result = $this->db->pquery('SELECT 1 FROM vtiger_shared_link_sets LIMIT 1', array());
        if ($result !== false && $this->db->num_rows($result) > 0) {
            $this->log('共有リンクのウィジェットは既にあるためスキップします');
            return;
        }

        $this->syncLinksSequence();
        $linkId = (int) $this->db->getUniqueID('vtiger_links');
        $this->db->pquery(
            'INSERT INTO vtiger_links (linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence) VALUES (?, ?, ?, ?, ?, ?, ?)',
            array($linkId, getTabid('Home'), 'DASHBOARDWIDGET', self::FIRST_WIDGET_TITLE, self::WIDGET_URL, '', 0)
        );
        $now = date('Y-m-d H:i:s');
        $this->db->pquery(
            'INSERT INTO vtiger_shared_link_sets (title, linkid, sequence, createdtime, modifiedtime) VALUES (?, ?, 1, ?, ?)',
            array(self::FIRST_WIDGET_TITLE, $linkId, $now, $now)
        );
        $this->log('ダッシュボードウィジェット「' . self::FIRST_WIDGET_TITLE . "」を登録しました（linkid={$linkId}）");
    }

    /**
     * vtiger_links の採番（vtiger_links_seq）を、登録済みの最大 ID に追いつかせる
     *
     * 過去のマイグレーション（IFrame ウィジェットの追加）が MAX(linkid)+1 で直接 INSERT しており、
     * 採番が遅れている環境では ID が重複して INSERT に失敗するため、先にそろえる。
     */
    private function syncLinksSequence() {
        $result = $this->db->pquery(
            'SELECT (SELECT COALESCE(MAX(linkid), 0) FROM vtiger_links) AS max_id,'
            . ' (SELECT COALESCE(MAX(id), 0) FROM vtiger_links_seq) AS seq_id',
            array()
        );
        $maxId = (int) $this->db->query_result($result, 0, 'max_id');
        $seqId = (int) $this->db->query_result($result, 0, 'seq_id');
        if ($seqId >= $maxId) {
            return;
        }
        $this->db->pquery('UPDATE vtiger_links_seq SET id = ?', array($maxId));
        $this->log("vtiger_links_seq を {$seqId} から {$maxId} に進めました（登録済みの最大 ID に合わせるため）");
    }

    /**
     * 設定画面のメニュー（システム構成）に共有リンクを追加する
     */
    private function registerSettingsMenu() {
        $blockResult = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks WHERE label = ?',
            array(self::SETTINGS_BLOCK)
        );
        if ($blockResult === false || $this->db->num_rows($blockResult) === 0) {
            $this->log('ブロック ' . self::SETTINGS_BLOCK . ' が見つからないため設定メニューの登録をスキップします');
            return;
        }
        $blockId = (int) $this->db->query_result($blockResult, 0, 'blockid');

        $existing = $this->db->pquery(
            'SELECT fieldid FROM vtiger_settings_field WHERE name = ?',
            array(self::SETTINGS_FIELD)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' は既に登録済みのためスキップします');
            return;
        }

        $result = $this->db->pquery(
            'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_settings_field WHERE blockid = ?',
            array($blockId)
        );
        $sequence = (int) $this->db->query_result($result, 0, 'next_seq');
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');

        $this->db->pquery(
            'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
            array(
                $fieldId,
                $blockId,
                self::SETTINGS_FIELD,
                '',
                'LBL_SHARED_LINKS_DESCRIPTION',
                'index.php?module=SharedLinks&parent=Settings&view=List',
                $sequence,
            )
        );
        $this->log("設定メニューに共有リンクを追加しました（sequence={$sequence}）");
    }
}
