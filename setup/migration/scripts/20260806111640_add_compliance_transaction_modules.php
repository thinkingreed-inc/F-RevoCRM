<?php
/**
 * マイグレーション: add_compliance_transaction_modules
 * 生成日時: 20260806111640
 *
 * 電帳法の適合判定で「取引レコードに関連付けされているか」を確認する対象を
 * 書類区分ごとに設定できるようにする。既定値を登録する。
 *
 * 例）契約書は「契約」に紐づいていれば適合、請求書は「請求」「受注」「発注」
 *     「顧客企業」「仕入先」のいずれかに紐づいていれば適合。
 *
 * これまでは書類区分に関係なく受注・請求・発注・見積・顧客企業・仕入先のみを
 * 対象としていたため、契約書を契約に紐づけても不適合と判定されていた。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';

class Migration20260806111640_AddComplianceTransactionModules extends FRMigrationClass {

    /** 設定テーブル名 */
    const TABLE_NAME = 'vtiger_documents_settings';

    public function process() {
        if (!$this->checkTableExists(self::TABLE_NAME)) {
            $this->log('テーブル ' . self::TABLE_NAME . ' が存在しないためスキップします'
                . '（20260806102225_add_documents_deadline_settings を先に実行してください）');
            return;
        }

        $settingName = Documents_ComplianceChecker::SETTING_CATEGORY_MODULES;
        $existing = $this->db->pquery(
            'SELECT name FROM ' . self::TABLE_NAME . ' WHERE name = ?',
            array($settingName)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log("設定 {$settingName} は既に登録済みのためスキップします");
            return;
        }

        $defaults = Documents_ComplianceChecker::DEFAULT_CATEGORY_TRANSACTION_MODULES;
        $this->db->pquery(
            'INSERT INTO ' . self::TABLE_NAME . ' (name, value) VALUES (?, ?)',
            array($settingName, json_encode($defaults, JSON_UNESCAPED_UNICODE))
        );
        foreach ($defaults as $category => $modules) {
            $this->log("  {$category}: " . implode(', ', $modules));
        }
        $this->log("設定 {$settingName} を登録しました");

        $this->recheckCompliance();
    }

    /**
     * 既存ドキュメントの適合状態を新しい判定基準で再判定する
     */
    private function recheckCompliance() {
        Documents_ComplianceChecker::clearCache();
        $result = Documents_ComplianceChecker::batchCheck();
        $this->log("適合状態を再判定しました（対象 {$result['checked']}件 / "
            . "適合 {$result['compliant']}件 / 不適合 {$result['non_compliant']}件）");
    }
}
