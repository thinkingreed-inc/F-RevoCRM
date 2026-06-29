<?php
/**
 * ドキュメントフォルダ権限テーブルの作成
 *
 * フォルダごとに参照権限・編集権限を設定できるようにする。
 * 付与先: 全員(everyone) / ユーザー(user) / 役割(role) / グループ(group)
 */
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260617153210_CreateFolderPermissions extends FRMigrationClass {

    public function process() {
        // フォルダ権限テーブル
        $this->db->pquery("CREATE TABLE vtiger_folder_permissions (
            permission_id INT AUTO_INCREMENT,
            folderid INT NOT NULL,
            permission_type VARCHAR(10) NOT NULL COMMENT 'view or edit',
            target_type VARCHAR(10) NOT NULL COMMENT 'everyone, user, role, group',
            target_id VARCHAR(50) DEFAULT NULL COMMENT 'NULL for everyone, otherwise user/role/group ID',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (permission_id),
            INDEX idx_fp_folderid (folderid),
            INDEX idx_fp_target (target_type, target_id),
            UNIQUE INDEX idx_fp_unique (folderid, permission_type, target_type, target_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log("vtiger_folder_permissions テーブルを作成しました");

        // 既存フォルダにデフォルト権限を設定（全員: 編集可能）
        // 編集権限があれば参照も可能なため、editのみで十分
        $result = $this->db->pquery("SELECT folderid FROM vtiger_attachmentsfolder", array());
        if ($result !== false) {
            $count = $this->db->num_rows($result);
            for ($i = 0; $i < $count; $i++) {
                $folderId = (int) $this->db->query_result($result, $i, 'folderid');
                $this->db->pquery(
                    "INSERT IGNORE INTO vtiger_folder_permissions (folderid, permission_type, target_type, target_id) VALUES (?, 'edit', 'everyone', NULL)",
                    array($folderId)
                );
            }
            $this->log("既存 {$count} フォルダにデフォルト権限（全員: 編集可能）を設定しました");
        }
    }
}
