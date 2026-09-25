<?php
/**
 * マイグレーション: setup_documents_folders
 * 生成日時: 20260617153210
 *
 * ドキュメントフォルダの階層化と権限設定に必要なスキーマを用意する。
 *
 *   1. vtiger_attachmentsfolder.parent_folderid（サブフォルダ。0 はルート）
 *   2. vtiger_folder_permissions（フォルダごとの参照権限・編集権限）
 *      付与先: 全員(everyone) / ユーザー(user) / 役割(role) / グループ(group)
 *
 * 権限テーブルが既にある環境では既定権限の再投入を行わない
 * （運用側で外した権限を復活させないため）。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260617153210_SetupDocumentsFolders extends FRMigrationClass {

    /** 権限テーブル名 */
    const PERMISSIONS_TABLE = 'vtiger_folder_permissions';

    public function process() {
        $this->addParentFolderIdColumn();
        $this->createPermissionsTable();
    }

    /**
     * フォルダを階層構造に対応させる
     */
    private function addParentFolderIdColumn() {
        if ($this->checkColumnExists('vtiger_attachmentsfolder', 'parent_folderid')) {
            $this->log("カラム vtiger_attachmentsfolder.parent_folderid は既に存在するためスキップします");
            return;
        }

        $this->db->pquery(
            "ALTER TABLE vtiger_attachmentsfolder
             ADD COLUMN parent_folderid INT DEFAULT 0 AFTER folderid",
            array()
        );
        $this->log("カラム vtiger_attachmentsfolder.parent_folderid を追加しました");

        // 既存フォルダはすべてルート扱いにする
        $result = $this->db->pquery(
            "UPDATE vtiger_attachmentsfolder SET parent_folderid = 0 WHERE parent_folderid IS NULL",
            array()
        );
        $affected = ($result === false) ? 0 : $this->db->getAffectedRowCount($result);
        $this->log("既存フォルダ {$affected} 件の parent_folderid を 0（ルート）に設定しました");
    }

    /**
     * フォルダ権限テーブルを作成し、既存フォルダに既定権限を付与する
     */
    private function createPermissionsTable() {
        if ($this->checkTableExists(self::PERMISSIONS_TABLE)) {
            $this->log('テーブル ' . self::PERMISSIONS_TABLE . ' は既に存在するためスキップします');
            return;
        }

        $this->db->pquery('CREATE TABLE ' . self::PERMISSIONS_TABLE . " (
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
        $this->log('テーブル ' . self::PERMISSIONS_TABLE . ' を作成しました');

        // 既存フォルダにデフォルト権限を設定（全員: 編集可能）
        // 編集権限があれば参照も可能なため、editのみで十分
        $result = $this->db->pquery("SELECT folderid FROM vtiger_attachmentsfolder", array());
        if ($result !== false) {
            $count = $this->db->num_rows($result);
            for ($i = 0; $i < $count; $i++) {
                $folderId = (int) $this->db->query_result($result, $i, 'folderid');
                $this->db->pquery(
                    'INSERT IGNORE INTO ' . self::PERMISSIONS_TABLE .
                    " (folderid, permission_type, target_type, target_id) VALUES (?, 'edit', 'everyone', NULL)",
                    array($folderId)
                );
            }
            $this->log("既存 {$count} フォルダにデフォルト権限（全員: 編集可能）を設定しました");
        }
    }
}
