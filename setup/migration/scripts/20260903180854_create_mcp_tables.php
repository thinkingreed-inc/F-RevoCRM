<?php
/**
 * マイグレーション: create_mcp_tables
 * 生成日時: 20260903180854
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260903180854_CreateMcpTables extends FRMigrationClass {

    /** 設定メニューに登録する項目の翻訳キー */
    private const SETTINGS_FIELD_NAME = 'LBL_MCP_TOKENS';

    /**
     * マイグレーションを実行する
     *
     * MCP モジュールが使う 4 テーブルと、設定メニュー項目を作成する。
     * 従来は modules/Settings/MCPTokens/install.php と
     * public/mcp-oauth/install.php で個別に作成していたが、
     * run_migration.php で一括実行できるようにし、
     * public 配下にインストーラを露出させないため移設した。
     */
    public function process() {
        $this->createTokenTable();
        $this->createOAuthClientTable();
        $this->createOAuthCodeTable();
        $this->createOAuthTokenTable();
        $this->registerSettingsField();
    }

    /**
     * 固定 Bearer トークン表
     * トークンは平文で保存せず SHA-256 ハッシュのみを持つ
     */
    private function createTokenTable() {
        if ($this->checkTableExists('vtiger_mcp_token')) {
            $this->log('vtiger_mcp_token は既に存在するためスキップしました');
            return;
        }
        $this->db->query("CREATE TABLE `vtiger_mcp_token` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `token_hash` CHAR(64) NOT NULL COMMENT 'Bearerトークンの SHA-256',
            `userid` INT NOT NULL COMMENT 'vtiger_users.id',
            `label` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '識別用ラベル',
            `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=有効, 0=無効',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_token_hash` (`token_hash`),
            KEY `idx_userid` (`userid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP Bearerトークンとユーザーの対応'");
        $this->log('vtiger_mcp_token テーブルを作成しました');
    }

    /**
     * OAuth 動的クライアント登録(DCR)で登録されたクライアント
     */
    private function createOAuthClientTable() {
        if ($this->checkTableExists('vtiger_mcp_oauth_client')) {
            $this->log('vtiger_mcp_oauth_client は既に存在するためスキップしました');
            return;
        }
        $this->db->query("CREATE TABLE `vtiger_mcp_oauth_client` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `client_id` VARCHAR(128) NOT NULL COMMENT 'DCR発行のランダムID',
            `client_secret_hash` VARCHAR(64) NULL COMMENT 'client_secretの SHA-256（publicクライアントはNULL）',
            `client_name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'クライアント表示名',
            `redirect_uris` TEXT NOT NULL COMMENT 'JSON配列: 登録済みredirect_uri',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_client_id` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP OAuth DCR登録クライアント'");
        $this->log('vtiger_mcp_oauth_client テーブルを作成しました');
    }

    /**
     * OAuth 認可コード（短命・使い捨て）
     */
    private function createOAuthCodeTable() {
        if ($this->checkTableExists('vtiger_mcp_oauth_code')) {
            $this->log('vtiger_mcp_oauth_code は既に存在するためスキップしました');
            return;
        }
        $this->db->query("CREATE TABLE `vtiger_mcp_oauth_code` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `code_hash` CHAR(64) NOT NULL COMMENT '認可コードの SHA-256',
            `client_id` VARCHAR(128) NOT NULL COMMENT '発行先client_id',
            `userid` INT NOT NULL COMMENT 'vtiger_users.id',
            `code_challenge` VARCHAR(128) NOT NULL COMMENT 'PKCE code_challenge (S256)',
            `redirect_uri` TEXT NOT NULL COMMENT '認可時のredirect_uri',
            `scope` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '認可スコープ',
            `expires_at` DATETIME NOT NULL COMMENT '有効期限（発行後10分）',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_code_hash` (`code_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP OAuth 認可コード'");
        $this->log('vtiger_mcp_oauth_code テーブルを作成しました');
    }

    /**
     * OAuth アクセス/リフレッシュトークン
     */
    private function createOAuthTokenTable() {
        if ($this->checkTableExists('vtiger_mcp_oauth_token')) {
            $this->log('vtiger_mcp_oauth_token は既に存在するためスキップしました');
            return;
        }
        $this->db->query("CREATE TABLE `vtiger_mcp_oauth_token` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `access_token_hash` CHAR(64) NOT NULL COMMENT 'access_tokenの SHA-256',
            `refresh_token_hash` CHAR(64) NOT NULL COMMENT 'refresh_tokenの SHA-256',
            `client_id` VARCHAR(128) NOT NULL COMMENT '発行先client_id',
            `userid` INT NOT NULL COMMENT 'vtiger_users.id',
            `scope` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'トークンスコープ',
            `expires_at` DATETIME NOT NULL COMMENT 'access_token有効期限（1時間）',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_access_hash` (`access_token_hash`),
            KEY `idx_refresh_hash` (`refresh_token_hash`),
            KEY `idx_userid` (`userid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MCP OAuth アクセス/リフレッシュトークン'");
        $this->log('vtiger_mcp_oauth_token テーブルを作成しました');
    }

    /**
     * 設定メニューに「MCPトークン管理」を登録する
     *
     * fieldid は本体と同じく getUniqueID() で採番する
     * （MAX(fieldid)+1 は同時実行で衝突しうるため使わない）。
     */
    private function registerSettingsField() {
        $exists = $this->db->pquery(
            'SELECT fieldid FROM vtiger_settings_field WHERE name = ?',
            array(self::SETTINGS_FIELD_NAME)
        );
        if ($exists === false) {
            throw new Exception('vtiger_settings_field の存在確認に失敗しました');
        }
        if ($this->db->num_rows($exists) > 0) {
            $this->log(self::SETTINGS_FIELD_NAME . ' は既に登録済みのためスキップしました');
            return;
        }

        $blockId = $this->resolveSettingsBlockId();
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');

        $seqResult = $this->db->pquery(
            'SELECT COALESCE(MAX(sequence), 0) AS maxseq FROM vtiger_settings_field WHERE blockid = ?',
            array($blockId)
        );
        if ($seqResult === false) {
            throw new Exception('sequence の取得に失敗しました');
        }
        $sequence = (int) $this->db->query_result($seqResult, 0, 'maxseq') + 1;

        // active=0 が「表示」を意味する（本体の既存レコードと同じ扱い）
        $insert = $this->db->pquery(
            'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array(
                $fieldId,
                $blockId,
                self::SETTINGS_FIELD_NAME,
                '',
                'MCPトークンの発行・一覧・無効化を管理します',
                'index.php?module=MCPTokens&parent=Settings&view=List',
                $sequence,
                0,
                0,
            )
        );
        if ($insert === false) {
            throw new Exception('vtiger_settings_field への登録に失敗しました');
        }
        $this->log(sprintf(
            '%s を登録しました (fieldid=%d, blockid=%d, sequence=%d)',
            self::SETTINGS_FIELD_NAME, $fieldId, $blockId, $sequence
        ));
    }

    /**
     * 配置先の設定ブロックIDを返す
     * セキュリティ管理ブロックが無い環境では最後のブロックにフォールバックする
     */
    private function resolveSettingsBlockId() {
        $result = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks WHERE label = ? LIMIT 1',
            array('LBL_SECURITY_MANAGEMENT')
        );
        if ($result !== false && $this->db->num_rows($result) > 0) {
            return (int) $this->db->query_result($result, 0, 'blockid');
        }

        $fallback = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks ORDER BY sequence DESC LIMIT 1',
            array()
        );
        if ($fallback === false || $this->db->num_rows($fallback) === 0) {
            throw new Exception('vtiger_settings_blocks に配置先ブロックが見つかりません');
        }
        $blockId = (int) $this->db->query_result($fallback, 0, 'blockid');
        $this->log('LBL_SECURITY_MANAGEMENT が無いため blockid=' . $blockId . ' を使用します');
        return $blockId;
    }
}
