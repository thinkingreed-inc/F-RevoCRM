<?php
/**
 * マイグレーション: add_parent_folderid_to_attachmentsfolder
 * 生成日時: 20260806082306
 *
 * ドキュメントフォルダをサブフォルダ（階層構造）に対応させるため、
 * vtiger_attachmentsfolder に親フォルダIDのカラムを追加する。
 * 0 はルート（親なし）を表す。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260806082306_AddParentFolderidToAttachmentsfolder extends FRMigrationClass {

    public function process() {
        if ($this->columnExists('vtiger_attachmentsfolder', 'parent_folderid')) {
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
     * カラムの存在を確認する
     */
    private function columnExists($table, $column) {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            array($table, $column)
        );
        return $result !== false && (int) $this->db->query_result($result, 0, 'cnt') > 0;
    }
}
