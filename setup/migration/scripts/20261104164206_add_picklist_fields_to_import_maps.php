<?php

/**
 * マイグレーション: add_picklist_fields_to_import_maps
 * 生成日時: 20261104164206
 *
 * 【チケット】#1906 インポート時、選択肢に存在しない値を選択肢に追加して取り込めるようにしたい
 * 【変更対象】vtiger_import_maps に add_picklist_fields（TEXT, NULL 可）を追加。保存したマッピングに「選択肢に無い値を追加」をチェックした項目名を持たせる
 * 【影響】既に列があれば何もしない。列追加前に保存したマッピングは NULL のまま（どの項目もチェックなしとして読み込む）
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261104164206_AddPicklistFieldsToImportMaps extends FRMigrationClass
{
    public function process(): void
    {
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        if ($this->db->num_rows($this->db->pquery($sql, ['vtiger_import_maps', 'add_picklist_fields']))) {
            $this->log("vtiger_import_maps.add_picklist_fields は既に存在します。スキップします");
            return;
        }
        $this->db->pquery("ALTER TABLE vtiger_import_maps ADD COLUMN add_picklist_fields TEXT NULL", []);
        $this->log("vtiger_import_maps に add_picklist_fields を追加しました");
    }
}
