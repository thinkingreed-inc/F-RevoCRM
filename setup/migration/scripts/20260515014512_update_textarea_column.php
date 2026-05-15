<?php
/**
 * マイグレーション: update_account_address
 * 生成日時: 20260515014512
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260515014512_UpdateTextareaColumn extends FRMigrationClass {

    /**
     * 住所系 textarea カラム (uitype=21/24) と、メタデータ上 textarea (uitype=19)
     * だが DB が varchar 固定長の項目を TEXT に拡張する。
     * varchar(N) のままだと N 文字以上の入力がサイレントに切り捨てられるため。
     * Issue: thinkingreed-inc/F-RevoCRM#1610
     */
    public function process() {
        $db = PearDatabase::getInstance();
        $this->log("マイグレーション update_textarea_column を開始しました");

        $alterations = array(
            // 顧客企業 (Accounts)
            array('vtiger_accountbillads',  'bill_street'),
            array('vtiger_accountshipads',  'ship_street'),
            // 顧客担当者 (Contacts)
            array('vtiger_contactaddress',  'mailingstreet'),
            array('vtiger_contactaddress',  'otherstreet'),
            // リード (Leads)
            array('vtiger_leadaddress',     'lane'),
            // 見積 (Quotes)
            array('vtiger_quotesbillads',   'bill_street'),
            array('vtiger_quotesshipads',   'ship_street'),
            // 受注 (SalesOrder)
            array('vtiger_sobillads',       'bill_street'),
            array('vtiger_soshipads',       'ship_street'),
            // 仕入注文 (PurchaseOrder)
            array('vtiger_pobillads',       'bill_street'),
            array('vtiger_poshipads',       'ship_street'),
            // 請求書 (Invoice)
            array('vtiger_invoicebillads',  'bill_street'),
            array('vtiger_invoiceshipads',  'ship_street'),
            // ユーザ (Users)
            array('vtiger_users',           'address_street'),
            // ModComments (コメント編集理由)
            array('vtiger_modcomments',     'reasontoedit'),
        );

        foreach ($alterations as $target) {
            list($table, $column) = $target;
            $sql = "ALTER TABLE {$table} MODIFY COLUMN {$column} TEXT DEFAULT NULL";
            $result = $db->pquery($sql, array());
            if ($result === false) {
                $this->log("[ERROR] {$table}.{$column} の TEXT 拡張に失敗しました: {$sql}");
                continue;
            }
            $this->log("{$table}.{$column} を TEXT に拡張しました");
        }

        $this->log("マイグレーション update_textarea_column が正常に完了しました");
    }
}