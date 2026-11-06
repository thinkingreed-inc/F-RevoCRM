<?php
/**
 * マイグレーション: add_picklist_input_style
 * 生成日時: 20261106120000
 *
 * #1677 ピックリスト/複数選択の表示方式カスタマイズ。
 * vtiger_picklist（ピックリスト項目レジストリ。picklistid, name の2列のみ）に
 * 項目単位の表示設定3カラムを追加する。
 *
 *  - input_style : 表示方式。NULL/auto=従来挙動（multipicklist は閾値以下でチェック、
 *                  単一ピックリストは従来ドロップダウン）/ checkbox / radio / dropdown。
 *  - threshold   : 選択肢数がこれ以下でチェック/ラジオ、超で従来ドロップダウン。
 *                  NULL はコード側で既定100として扱う。
 *  - columns     : チェック/ラジオ表示の列数（1 or 2）。既定1。
 *                  ※ `columns` は紛れやすいので SQL では必ずバッククォートで囲む。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261106120000_AddPicklistInputStyle extends FRMigrationClass {

    public function process() {
        $this->addColumnIfMissing('input_style', "ALTER TABLE vtiger_picklist ADD COLUMN input_style VARCHAR(20) DEFAULT NULL");
        $this->addColumnIfMissing('threshold',   "ALTER TABLE vtiger_picklist ADD COLUMN threshold INT DEFAULT NULL");
        $this->addColumnIfMissing('columns',     "ALTER TABLE vtiger_picklist ADD COLUMN `columns` TINYINT DEFAULT 1");

        $this->log("vtiger_picklist に input_style/threshold/columns を追加しました");
    }

    /**
     * 既に存在するカラムは追加しない（再実行耐性）。
     */
    private function addColumnIfMissing($columnName, $alterSql) {
        $result = $this->db->pquery("SHOW COLUMNS FROM vtiger_picklist LIKE ?", array($columnName));
        if ($result && $this->db->num_rows($result) > 0) {
            $this->log("vtiger_picklist.$columnName は既に存在するためスキップ");
            return;
        }
        $this->db->query($alterSql);
        $this->log("vtiger_picklist.$columnName を追加");
    }
}
