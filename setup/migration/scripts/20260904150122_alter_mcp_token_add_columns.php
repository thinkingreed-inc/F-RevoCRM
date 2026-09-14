<?php
/**
 * マイグレーション: alter_mcp_token_add_columns
 * 生成日時: 20260904150122
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260904150122_AlterMcpTokenAddColumns extends FRMigrationClass {

    /**
     * vtiger_mcp_token に接頭辞、有効期限、最終使用日時を追加
     */
    public function process() {
        $sql = "ALTER TABLE vtiger_mcp_token ADD COLUMN token_prefix varchar(20) NULL COMMENT '表示用の接頭辞'";
        $this->db->query($sql);

        $sql = "ALTER TABLE vtiger_mcp_token ADD COLUMN expires_at datetime NULL COMMENT '有効期限。NULL=無期限'";
        $this->db->query($sql);

        $sql = "ALTER TABLE vtiger_mcp_token ADD COLUMN last_used_at datetime NULL COMMENT '最終使用日時。NULL=未使用'";
        $this->db->query($sql);

        $this->log("マイグレーション alter_mcp_token_add_columns が正常に完了しました");
    }
}
