<?php
/**
 * マイグレーション: fix_duplicate_products_purchaseorder_relation
 * 生成日時: 20260601082359
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once dirname(__FILE__) . '/../../../include/utils/CommonUtils.php';

class Migration20260601082359_FixDuplicateProductsPurchaseorderRelation extends FRMigrationClass {
    public function process() {
        global $adb;

        // ---- Products と PurchaseOrder の tabid を取得 ----
        $productsTabId = $this->getTabId('Products');
        $poTabId       = $this->getTabId('PurchaseOrder');

        // モジュールが存在しない場合は処理を中止
        if (!$productsTabId || !$poTabId) {
            return;
        }

        // ---- Products → PurchaseOrder の N:N 関係のみ取得（重複検出用）----
        $sql = "SELECT relation_id FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? AND name = 'get_purchase_orders' AND relationfieldid IS NULL AND relationtype = 'N:N' ORDER BY relation_id ASC";
        $result = $adb->pquery($sql, [$productsTabId, $poTabId]);
        $count = $adb->num_rows($result);

        // 重複が1件以下なら処理不要
        if ($count < 1) {
            return;
        }

        // ---- N:N 側はすべて削除（正規の 1:N 行は別条件で残存） ----
        while ($row = $adb->fetch_array($result)) {
            $adb->pquery(
                "DELETE FROM vtiger_relatedlists WHERE relation_id = ?",
                [$row['relation_id']]
            );
        }

        $this->log("マイグレーション fix_duplicate_products_purchaseorder_relation が正常に完了しました");
    }

    /** モジュール名から tabid を取得する関数 */
    private function getTabId($moduleName) {
        global $adb;

        $result = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", [$moduleName]);

        if ($adb->num_rows($result) > 0) {
            $row = $adb->fetch_array($result);
            return $row['tabid'];
        }
        return false;
    }
}
