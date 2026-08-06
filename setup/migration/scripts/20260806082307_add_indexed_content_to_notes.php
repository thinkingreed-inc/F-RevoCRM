<?php
/**
 * マイグレーション: add_indexed_content_to_notes
 * 生成日時: 20260806082307
 *
 * ファイル内テキストの全文検索用に vtiger_notes へ indexed_content カラムを追加する。
 * PDF / Word / Excel / PowerPoint / テキストから抽出した本文を保持し、
 * 一覧のキーワード検索（タイトル・ファイル名との OR 条件）で参照する。
 *
 * 既存ドキュメントの本文は登録済みのファイルには入らないため、
 * 必要に応じて次のスクリプトでインデックスを再作成する。
 *   php modules/Documents/schema/reindex_documents.php --execute
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260806082307_AddIndexedContentToNotes extends FRMigrationClass {

    public function process() {
        if ($this->columnExists('vtiger_notes', 'indexed_content')) {
            $this->log("カラム vtiger_notes.indexed_content は既に存在するためスキップします");
            return;
        }

        $this->db->pquery(
            "ALTER TABLE vtiger_notes ADD COLUMN indexed_content LONGTEXT AFTER notecontent",
            array()
        );
        $this->log("カラム vtiger_notes.indexed_content を追加しました");
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
