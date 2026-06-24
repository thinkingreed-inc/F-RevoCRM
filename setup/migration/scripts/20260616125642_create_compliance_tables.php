<?php
/**
 * 電子帳簿保存法対応: 監査ログテーブル・ファイルバージョンテーブルを作成
 */
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260616125642_CreateComplianceTables extends FRMigrationClass {

    public function process() {
        // 訂正削除履歴テーブル
        $this->db->pquery("CREATE TABLE vtiger_notes_audit_log (
            audit_id BIGINT AUTO_INCREMENT,
            notesid INT NOT NULL,
            action_type VARCHAR(20) NOT NULL,
            action_detail TEXT DEFAULT NULL,
            file_hash_before VARCHAR(64) DEFAULT NULL,
            file_hash_after VARCHAR(64) DEFAULT NULL,
            performed_by INT NOT NULL,
            performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY (audit_id),
            INDEX idx_audit_notesid (notesid),
            INDEX idx_audit_action (action_type),
            INDEX idx_audit_performed_at (performed_at),
            INDEX idx_audit_user (performed_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log("vtiger_notes_audit_log テーブルを作成しました");

        // ファイルバージョン管理テーブル
        $this->db->pquery("CREATE TABLE vtiger_notes_file_versions (
            version_id BIGINT AUTO_INCREMENT,
            notesid INT NOT NULL,
            version_number INT NOT NULL DEFAULT 1,
            attachmentsid INT NOT NULL,
            file_hash VARCHAR(64) NOT NULL,
            file_size INT NOT NULL,
            change_reason TEXT DEFAULT NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_current TINYINT(1) DEFAULT 0,
            PRIMARY KEY (version_id),
            INDEX idx_version_notesid (notesid),
            INDEX idx_version_current (notesid, is_current),
            UNIQUE INDEX idx_version_unique (notesid, version_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log("vtiger_notes_file_versions テーブルを作成しました");
    }
}
