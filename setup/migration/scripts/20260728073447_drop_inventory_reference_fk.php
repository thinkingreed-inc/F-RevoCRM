<?php
/**
 * マイグレーション: drop_inventory_reference_fk
 * 生成日時: 20260728073447
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260728073447_DropInventoryReferenceFk extends FRMigrationClass {

    /**
     * Inventory系テーブルの reference field FK 制約を削除する。
     *
     * 背景:
     * レコードタイプ機能で必須項目 (vendorid 等) を非表示化して新規保存すると、
     * POST に該当 field 値が乗らず Save は空文字 '' で INSERT する。
     * MySQL は '' を 0 に暗黙変換 → 参照先に該当 id が存在せず FK 違反 →
     * pquery が silent に失敗 → 子table 行が作られず「主要項目 空の詳細画面」が
     * 表示される不具合が発生する。
     *
     * 基本モジュール (Accounts / Contacts 等) の reference field には元々 FK が
     * 付与されておらず、空値保存が通る。Inventory 系4テーブルだけ歴史的経緯で
     * FK が張られており、動作の非対称と本不具合の直接原因になっている。
     * F-RevoCRM では削除は論理削除 (vtiger_crmentity.deleted=1) 運用のため、
     * ON DELETE CASCADE は事実上発火せず、依存コードも存在しない。
     *
     * 本 migration で該当 FK 制約を削除し、基本モジュールと動作を揃える。
     */
    public function process() {
        $targets = array(
            'vtiger_purchaseorder' => 'fk_4_vtiger_purchaseorder', // vendorid → vtiger_vendor
            'vtiger_salesorder'    => 'fk_3_vtiger_salesorder',    // vendorid → vtiger_vendor
            'vtiger_quotes'        => 'fk_3_vtiger_quotes',        // potentialid → vtiger_potential
            'vtiger_invoice'       => 'fk_2_vtiger_invoice',       // salesorderid → vtiger_salesorder
        );

        foreach ($targets as $table => $constraint) {
            if (!$this->foreignKeyExists($table, $constraint)) {
                $this->log("FK制約 {$table}.{$constraint} は存在せず。スキップ");
                continue;
            }
            $sql = "ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}";
            $this->db->pquery($sql, array());
            $this->log("FK制約 {$table}.{$constraint} を削除");
        }
    }

    private function foreignKeyExists($table, $constraint) {
        $sql = "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_NAME = ?
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
        $result = $this->db->pquery($sql, array($table, $constraint));
        return $result && $this->db->num_rows($result) > 0;
    }
}
