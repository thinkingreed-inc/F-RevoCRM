<?php
/**
 * マイグレーション: sync_deleted_flag_for_cascaded_quotes_and_potentials
 * 生成日時: 20260422174357
 *
 * Account / Contact の削除時にカスケード削除された Quote / Potential について、
 * updateBasicInformation の呼び出し順序バグにより vtiger_crmentity.deleted=1 だが
 * vtiger_quotes.deleted / vtiger_potential.deleted が 0 のままになっているレコードを
 * vtiger_crmentity 側の値で上書きして整合性を回復する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260422174357_SyncDeletedFlagForCascadedQuotesAndPotentials extends FRMigrationClass {

    public function process() {
        $this->syncDeletedFlag('vtiger_quotes',    'quoteid',     'Quotes');
        $this->syncDeletedFlag('vtiger_potential', 'potentialid', 'Potentials');
    }

    /**
     * 指定モジュールの基本テーブルについて vtiger_crmentity.deleted と食い違っている
     * レコードを crmentity 側の値で同期する。
     *
     * @param string $baseTable    基本テーブル名 (vtiger_quotes など)
     * @param string $baseTableKey 基本テーブルの主キーカラム名 (quoteid など)
     * @param string $moduleLabel  ログ用のモジュール名
     */
    private function syncDeletedFlag($baseTable, $baseTableKey, $moduleLabel) {
        $countSql = "SELECT COUNT(*) AS cnt
                     FROM {$baseTable} b
                     INNER JOIN vtiger_crmentity e ON b.{$baseTableKey} = e.crmid
                     WHERE b.deleted <> e.deleted";
        $result = $this->db->pquery($countSql, array());
        $target = (int) $this->db->query_result($result, 0, 'cnt');

        if ($target === 0) {
            $this->log("{$moduleLabel}: 不整合レコードなし。スキップします。");
            return;
        }

        $this->log("{$moduleLabel}: 不整合レコード {$target} 件を同期します。");

        $updateSql = "UPDATE {$baseTable} b
                      INNER JOIN vtiger_crmentity e ON b.{$baseTableKey} = e.crmid
                      SET b.deleted = e.deleted
                      WHERE b.deleted <> e.deleted";
        $this->db->pquery($updateSql, array());

        $this->log("{$moduleLabel}: 同期完了。");
    }
}
