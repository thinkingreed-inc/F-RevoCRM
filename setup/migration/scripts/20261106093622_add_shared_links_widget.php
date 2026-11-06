<?php
/**
 * マイグレーション: add_shared_links_widget
 * 生成日時: 20261106093622
 *
 * ダッシュボードのウィジェット「共有リンク」（#1963）を追加する。
 * - リンク・リクエストパラメータ・公開範囲の対象のテーブルを作る
 * - ホームのダッシュボードの「ウィジェットを追加」に出るよう vtiger_links に登録する
 * - 設定メニューの「システム構成」に管理画面を追加する
 *
 * 何度実行しても結果が変わらないようにしてある。
 * 仕様: docs/specs/shared_links_widget.md
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'vtlib/Vtiger/Link.php';

class Migration20261106093622_AddSharedLinksWidget extends FRMigrationClass {

    /** 設定メニューを追加するブロック（システム構成） */
    const SETTINGS_BLOCK = 'LBL_CONFIGURATION';

    /** 設定メニュー名 */
    const SETTINGS_FIELD = 'LBL_SHARED_LINKS';

    /** ウィジェットの名前（vtiger_links.linklabel） */
    const WIDGET_LABEL = 'Shared Links';

    const WIDGET_URL = 'index.php?module=Home&view=ShowWidget&name=SharedLinks';

    public function process() {
        $this->createLinksTable();
        $this->createParamsTable();
        $this->createTargetsTable();
        $this->registerWidget();
        $this->registerSettingsMenu();
    }

    private function createLinksTable() {
        if ($this->checkTableExists('vtiger_shared_links')) {
            $this->log('テーブル vtiger_shared_links は既に存在するためスキップします');
            return;
        }
        $this->db->pquery("CREATE TABLE vtiger_shared_links (
            id INT AUTO_INCREMENT,
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
            INDEX idx_shared_links_sequence (sequence)
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
     * ホーム（tabid=3）のダッシュボードウィジェットとして登録する
     *
     * Vtiger_DashBoard_Model::getSelectableDashboard() が「ウィジェットを追加」に出す。
     */
    private function registerWidget() {
        $existing = $this->db->pquery(
            "SELECT linkid FROM vtiger_links WHERE linktype = 'DASHBOARDWIDGET' AND linklabel = ?",
            array(self::WIDGET_LABEL)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log('ダッシュボードウィジェット ' . self::WIDGET_LABEL . ' は既に登録済みのためスキップします');
            return;
        }
        $this->syncLinksSequence();
        Vtiger_Link::addLink(getTabid('Home'), 'DASHBOARDWIDGET', self::WIDGET_LABEL, self::WIDGET_URL);
        $this->log('ダッシュボードウィジェット ' . self::WIDGET_LABEL . ' を登録しました');
    }

    /**
     * vtiger_links の採番（vtiger_links_seq）を、登録済みの最大 ID に追いつかせる
     *
     * Vtiger_Link::addLink は vtiger_links_seq から ID を採番する。過去のマイグレーション
     * （IFrame ウィジェットの追加）が MAX(linkid)+1 で直接 INSERT しており、採番が遅れている
     * 環境では ID が重複して INSERT に失敗するため、先にそろえる。
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
