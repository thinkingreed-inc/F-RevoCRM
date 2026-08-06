<?php
/**
 * マイグレーション: widen_document_filesize_columns
 * 生成日時: 20260806055744
 *
 * ファイルサイズのカラムを BIGINT に拡張する。
 * signed INT は上限が 2,147,483,647（2GB-1）のため、2GB のファイルで桁あふれする。
 * 分割アップロードで大容量ファイルを扱えるようにするための前提となる変更。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260806055744_WidenDocumentFilesizeColumns extends FRMigrationClass {

    /** 対象カラム: テーブル => array(カラム => 定義) */
    private $targets = array(
        'vtiger_notes' => array(
            'filesize' => 'BIGINT DEFAULT NULL',
        ),
        'vtiger_notes_file_versions' => array(
            'file_size' => 'BIGINT NOT NULL DEFAULT 0',
        ),
    );

    public function process() {
        foreach ($this->targets as $table => $columns) {
            if (!$this->tableExists($table)) {
                $this->log("テーブル {$table} が存在しないためスキップします");
                continue;
            }
            foreach ($columns as $column => $definition) {
                $currentType = $this->getColumnType($table, $column);
                if ($currentType === null) {
                    $this->log("カラム {$table}.{$column} が存在しないためスキップします");
                    continue;
                }
                if (stripos($currentType, 'bigint') !== false) {
                    $this->log("カラム {$table}.{$column} は既に BIGINT のためスキップします");
                    continue;
                }
                $this->db->pquery("ALTER TABLE {$table} MODIFY {$column} {$definition}", array());
                $this->log("カラム {$table}.{$column} を {$currentType} から BIGINT に変更しました");
            }
        }
    }

    /**
     * テーブルの存在を確認する
     */
    private function tableExists($table) {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            array($table)
        );
        return $result !== false && (int) $this->db->query_result($result, 0, 'cnt') > 0;
    }

    /**
     * カラムの現在の型を取得する（存在しない場合は null）
     */
    private function getColumnType($table, $column) {
        $result = $this->db->pquery(
            "SELECT COLUMN_TYPE AS ctype FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            array($table, $column)
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return $this->db->query_result($result, 0, 'ctype');
    }
}
