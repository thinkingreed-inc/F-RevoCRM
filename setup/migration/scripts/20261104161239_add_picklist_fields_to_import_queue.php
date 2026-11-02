<?php

/**
 * マイグレーション: add_picklist_fields_to_import_queue
 * 生成日時: 20261104161239
 *
 * 【チケット】#1906 インポート時、選択肢に存在しない値を選択肢に追加して取り込めるようにしたい
 * 【変更対象】vtiger_import_queue に add_picklist_fields（TEXT, NULL 可）を追加
 * 【影響】既に列があれば何もしない。テーブルが未作成の環境は Import_Queue_Action::add() の CreateTable が列を含めて作る
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261104161239_AddPicklistFieldsToImportQueue extends FRMigrationClass
{
    public function process(): void
    {
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        if ($this->db->num_rows($this->db->pquery($sql, ['vtiger_import_queue', 'add_picklist_fields']))) {
            $this->log("vtiger_import_queue.add_picklist_fields は既に存在します。スキップします");
            return;
        }
        $tableExists = $this->db->pquery("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", ['vtiger_import_queue']);
        if (!$this->db->num_rows($tableExists)) {
            $this->log("vtiger_import_queue が未作成のためスキップします");
            return;
        }
        $this->db->pquery("ALTER TABLE vtiger_import_queue ADD COLUMN add_picklist_fields TEXT NULL", []);
        $this->log("vtiger_import_queue に add_picklist_fields を追加しました");
    }
}
