<?php
/**
 * マイグレーション: fix_duplicate_helpdesk_assets_relation
 * 生成日時: 20251127120006
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once dirname(__FILE__) . '/../../../include/utils/CommonUtils.php';

class Migration20251127120006_FixDuplicateHelpdeskAssetsRelation extends FRMigrationClass {
    public function process() {
        global $adb;

        // ---- HelpDesk と Assets の tabid を取得 ----
        $helpdeskTabId = $this->getTabId('HelpDesk');
        $assetsTabId   = $this->getTabId('Assets');

        // モジュールが存在しない場合は処理を中止
        if (!$helpdeskTabId || !$assetsTabId) {
            return;
        }

        // ---- HelpDesk → Assets の N:N 関係のみ取得（重複検出用）----
        $sql = "SELECT relation_id FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? AND name = 'get_related_list'AND relationfieldid IS NULL AND relationtype = 'N:N' ORDER BY relation_id ASC";
        $result = $adb->pquery($sql, [$helpdeskTabId, $assetsTabId]);
        $count = $adb->num_rows($result);

        // 重複が1件以下存在する場合スキップ
        if ($count <= 1) {
            return; 
        }

        // 最初の1件（最も古い関係）は削除せず保持するためのフラグ
        $firstRow = true;   

        // 取得した relation_id を1件ずつ処理
        while ($row = $adb->fetch_array($result)) {

            // ---- 最初の1件は削除せずスキップ ----
            if ($firstRow) {    // first row → keep
                $firstRow = false;
                continue;
            }

            // ---- 2件目以降の重複データを削除 ----
            $adb->pquery(
                "DELETE FROM vtiger_relatedlists WHERE relation_id = ?",
                [$row['relation_id']]
            );
        }
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