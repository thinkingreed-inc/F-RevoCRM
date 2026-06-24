<?php
/**
 * マイグレーション: change_mailscanner_uniqueid_format
 * 生成日時: 20260615103558
 *
 * メールスキャナーの重複チェックキーを「Message-ID」から「Message-ID|udate」形式に変更。
 * 同一Message-IDでも配送経路（To/CC）が異なり複数通届くケースに対応するため。
 *
 * 既存レコードには正確なudate情報がないため、sentinel値「|0」を付与する。
 * isMessageScanned() で「Message-ID|0」を検出した場合、正しい「Message-ID|udate」に
 * UPDATEする自己修復ロジックにより、初回スキャン時に正しい値に移行される。
 * これにより lastscan 変更不要で、取りこぼし・再取り込みの両方を防止する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260615103558_ChangeMailscannerUniqueidFormat extends FRMigrationClass {

    /**
     * マイグレーションを実行する
     */
    public function process() {
        // 旧フォーマット(Message-IDのみ)のレコードに sentinel値「|0」を付与
        $sql = "UPDATE vtiger_mailscanner_ids
                SET messageid = CONCAT(messageid, '|0')
                WHERE messageid NOT LIKE '%|%'";
        $result = $this->db->pquery($sql, []);
        $updatedCount = $this->db->getAffectedRowCount($result);
        $this->log("旧フォーマットのレコードにsentinel値を付与: {$updatedCount}件");

        $this->log("マイグレーション完了: 既存レコードは初回スキャン時に自動的に正しい値に移行されます");
    }
}
