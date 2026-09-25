<?php
/**
 * マイグレーション: change_indexed_content_to_longtext
 * 生成日時: 20260824082627
 *
 * 全文検索用のテキスト（vtiger_notes.indexed_content）を LONGTEXT に揃える。
 *
 * カラムを追加するマイグレーションは LONGTEXT で作っているが、追加より前から
 * カラムがある環境では「存在すればスキップ」するため、古い型（MEDIUMTEXT など）が
 * 残っていることがある。MEDIUMTEXT は約16MBで、日本語なら約558万文字で頭打ちに
 * なるため、抽出の上限を伸ばすと入り切らなくなる。型を明示的に合わせる。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260824082627_ChangeIndexedContentToLongtext extends FRMigrationClass {

    /** 目的の型 */
    const TARGET_TYPE = 'longtext';

    public function process() {
        if (!$this->checkColumnExists('vtiger_notes', 'indexed_content')) {
            $this->log('vtiger_notes.indexed_content が無いためスキップします'
                . '（カラム追加のマイグレーションが未適用）');
            return;
        }

        $currentType = $this->getColumnType();
        if ($currentType === self::TARGET_TYPE) {
            $this->log('既に LONGTEXT のためスキップします');
            return;
        }

        // 桁を広げる方向のみ。NULL 許可・既定値は変えない
        $this->db->pquery(
            'ALTER TABLE vtiger_notes MODIFY COLUMN indexed_content LONGTEXT DEFAULT NULL', array());
        $this->log("indexed_content の型を {$currentType} から LONGTEXT に変更しました");
    }

    /**
     * indexed_content の現在の型を返す
     *
     * @return string 小文字の型名（取得できない場合は空文字）
     */
    private function getColumnType() {
        // PearDatabase は列名を小文字で扱うため、別名も小文字で付ける
        $result = $this->db->pquery(
            'SELECT DATA_TYPE AS data_type FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            array('vtiger_notes', 'indexed_content'));
        if ($result === false || $this->db->num_rows($result) === 0) {
            return '';
        }
        return strtolower((string) $this->db->query_result($result, 0, 'data_type'));
    }

}
