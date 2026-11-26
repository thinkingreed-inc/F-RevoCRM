<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/
/**
 * マイグレーション: alter_mcp_oauth_token
 * 生成日時: 20260910190925
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260910190925_AlterMcpOauthToken extends FRMigrationClass
{
    /** 列を追加する対象テーブル */
    private const TOKEN_TABLE = 'vtiger_mcp_oauth_token';

    /** 追加する列名（固定トークン側 vtiger_mcp_token.enabled と同名同義） */
    private const ENABLED_COLUMN = 'enabled';

    /** 設定メニューに登録する項目の翻訳キー */
    private const SETTINGS_FIELD_NAME = 'LBL_MCP_OAUTH_APPS_ADMIN';

    /** 配置先の設定ブロック（既存の LBL_MCP_TOKENS と同じブロック） */
    private const SETTINGS_BLOCK_LABEL = 'LBL_OTHER_SETTINGS';

    /**
     * マイグレーションを実行する
     *
     * OAuth トークンに失効フラグ（enabled）を追加し、
     * 管理設定「MCP連携アプリケーション管理」の入口を設定メニューへ登録する。
     */
    public function process()
    {
        $this->addEnabledColumn();
        $this->registerSettingsField();
    }

    /**
     * vtiger_mcp_oauth_token に enabled 列を追加する
     *
     * 既存行は既定値の 1（有効）となり、現在の連携はそのまま維持される。
     *
     * 失効は行の物理削除ではなく UPDATE ... SET enabled = 0 で行う（軟削除）。
     * 軟削除で enabled=0 の行を残す方式と (client_id, userid) の一意制約は両立しないため、
     * 一意制約は張らない。重複の防止は認可時に既存の有効行を enabled=0 にしてから
     * INSERT する順序（Mcp_OAuthStorage::createToken()）でコード側が担保する。
     *
     * MySQL の DDL は暗黙コミットされ、再実行時の Duplicate column name で詰まるため、
     * ADD COLUMN の前に checkColumnExists() で存在を確認する。
     */
    private function addEnabledColumn()
    {
        if ($this->checkColumnExists(self::TOKEN_TABLE, self::ENABLED_COLUMN)) {
            $this->log(self::ENABLED_COLUMN . ' 列は既に存在するためスキップしました');
            return;
        }

        $alter = $this->db->query(
            'ALTER TABLE ' . self::TOKEN_TABLE . "
             ADD COLUMN " . self::ENABLED_COLUMN . " TINYINT NOT NULL DEFAULT 1 COMMENT '1=有効, 0=失効'"
        );
        if ($alter === false) {
            throw new Exception(self::ENABLED_COLUMN . ' 列の追加に失敗しました');
        }
        $this->log(self::TOKEN_TABLE . ' に ' . self::ENABLED_COLUMN . ' 列を追加しました');
    }

    /**
     * 設定メニューに「MCP連携アプリケーション管理」を登録する
     *
     * fieldid は本体と同じく getUniqueID() で採番する
     * （MAX(fieldid)+1 は同時実行で衝突しうるため使わない）。
     */
    private function registerSettingsField()
    {
        $exists = $this->db->pquery(
            'SELECT fieldid FROM vtiger_settings_field WHERE name = ?',
            [self::SETTINGS_FIELD_NAME]
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
            [$blockId]
        );
        if ($seqResult === false) {
            throw new Exception('sequence の取得に失敗しました');
        }
        $sequence = (int) $this->db->query_result($seqResult, 0, 'maxseq') + 1;

        // active=0 が「表示」を意味する（本体の既存レコードと同じ扱い）
        $insert = $this->db->pquery(
            'INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $fieldId,
                $blockId,
                self::SETTINGS_FIELD_NAME,
                '',
                'LBL_MCP_OAUTH_APPS_ADMIN_DESCRIPTION',
                'index.php?module=MCPOAuthApps&parent=Settings&view=List',
                $sequence,
                0,
                0,
            ]
        );
        if ($insert === false) {
            throw new Exception('vtiger_settings_field への登録に失敗しました');
        }
        $this->log(sprintf(
            '%s を登録しました (fieldid=%d, blockid=%d, sequence=%d)',
            self::SETTINGS_FIELD_NAME,
            $fieldId,
            $blockId,
            $sequence
        ));
    }

    /**
     * 配置先の設定ブロックIDを返す
     * 既存の LBL_MCP_TOKENS と同じ「他の設定」ブロックに並べる
     */
    private function resolveSettingsBlockId()
    {
        $result = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks WHERE label = ? LIMIT 1',
            [self::SETTINGS_BLOCK_LABEL]
        );
        if ($result === false) {
            throw new Exception('vtiger_settings_blocks の検索に失敗しました');
        }
        if ($this->db->num_rows($result) === 0) {
            throw new Exception(self::SETTINGS_BLOCK_LABEL . ' ブロックが見つかりません');
        }
        return (int) $this->db->query_result($result, 0, 'blockid');
    }
}
