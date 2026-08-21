<?php
/**
 * ドキュメントの変更履歴（ModTracker ＋ 電帳法の監査ログ の統合）
 *
 * ドキュメントの履歴は2か所に分かれて記録される。
 *
 *   vtiger_modtracker_basic  vtiger 標準の更新履歴。登録・項目変更・削除・復元
 *   vtiger_notes_audit_log   電帳法の監査ログ。ダウンロード・ハッシュ検証・
 *                            ファイル差替え・IPアドレス・電帳法項目の変更
 *
 * 標準の詳細画面は前者だけ、React の詳細モーダルは後者だけを見ていたため、
 * 同じ「履歴」でも中身が違っていた（復元は前者にしか無い、など）。
 * ここで1本の時系列にまとめる。
 *
 * 重複の扱い:
 *   登録・項目変更・削除は両方に記録され得るので、ModTracker 側を採用する。
 *   ただし ModTracker が持ち得ない情報を含む監査ログは必ず残す。
 *     - ダウンロード・ハッシュ検証（ModTracker に該当する種別が無い）
 *     - ファイル差替え・ファイルハッシュ（同上）
 *     - ComplianceAPI 経由の電帳法項目の変更
 *       （vtiger_notes を直接 UPDATE するため ModTracker に残らない）
 *
 * 使い方:
 *   require_once 'modules/Documents/utils/HistoryLog.php';
 *   Documents_HistoryLog::getHistory($notesId, 1, 20);
 */
require_once 'modules/Documents/utils/AuditLogger.php';

class Documents_HistoryLog {

    /** 記録元: vtiger 標準の更新履歴 */
    const SOURCE_MODTRACKER = 'modtracker';

    /** 記録元: 電帳法の監査ログ */
    const SOURCE_AUDIT = 'audit';

    /**
     * ModTracker の status と操作種別の対応
     *
     * 関連付け・関連解除（4 / 5）は項目の履歴ではないため扱わない。
     */
    private static $statusToAction = array(
        '0' => 'update',
        '1' => 'delete',
        '2' => 'create',
        '3' => 'restore',
    );

    /** 同じ操作と見なす時刻の差（秒）。両方に記録された同一操作をまとめるために使う */
    const DEDUP_WINDOW_SECONDS = 2;

    /** ModTracker と重複し得る操作種別（ModTracker が記録する種別と同じ） */
    private static $overlappingActions = array('create', 'update', 'delete', 'restore');

    /**
     * 統合前に読み込む監査ログの上限
     *
     * 1レコード分の履歴なので通常は数十件。取りこぼしたら件数が合わなくなるため、
     * 実運用で届かない大きさにしておく。
     */
    const AUDIT_FETCH_LIMIT = 10000;

    /**
     * 統合した変更履歴を返す
     *
     * @param int $notesId ドキュメントID
     * @param int $page ページ番号（1始まり）
     * @param int $limit 1ページの件数
     * @return array ['records' => array, 'total' => int]
     */
    public static function getHistory($notesId, $page = 1, $limit = 20) {
        $notesId = (int) $notesId;
        $page = ($page < 1) ? 1 : (int) $page;
        $limit = ($limit < 1) ? 20 : (int) $limit;

        $trackerEntries = self::fetchModTrackerEntries($notesId);
        $auditEntries = self::fetchAuditEntries($notesId);
        $merged = array_merge($trackerEntries, self::dropDuplicates($auditEntries, $trackerEntries));

        usort($merged, array(__CLASS__, 'compareByTimeDesc'));

        $total = count($merged);
        $offset = ($page - 1) * $limit;
        return array(
            'records' => array_slice($merged, $offset, $limit),
            'total' => $total,
        );
    }

    /**
     * 新しい順に並べる（同時刻なら記録IDの大きい順）
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private static function compareByTimeDesc($a, $b) {
        if ($a['performed_at'] === $b['performed_at']) {
            return ($b['entry_id'] < $a['entry_id']) ? -1 : (($b['entry_id'] > $a['entry_id']) ? 1 : 0);
        }
        return ($a['performed_at'] < $b['performed_at']) ? 1 : -1;
    }

    /**
     * ModTracker の履歴を取得する
     *
     * @param int $notesId
     * @return array
     */
    private static function fetchModTrackerEntries($notesId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT mb.id, mb.whodid, mb.changedon, mb.status,
                    CONCAT(u.last_name, ' ', u.first_name) AS performer_name
             FROM vtiger_modtracker_basic mb
             LEFT JOIN vtiger_users u ON u.id = mb.whodid
             WHERE mb.crmid = ? AND mb.module = ?
             ORDER BY mb.id DESC",
            array($notesId, 'Documents')
        );
        if ($result === false) {
            return array();
        }

        $entries = array();
        $ids = array();
        $numRows = $db->num_rows($result);
        for ($i = 0; $i < $numRows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $status = (string) $row['status'];
            if (!isset(self::$statusToAction[$status])) {
                continue;// 関連付け・関連解除などは対象外
            }
            $id = (int) $row['id'];
            $ids[] = $id;
            $entries[$id] = array(
                'entry_id' => $id,
                'source' => self::SOURCE_MODTRACKER,
                'action_type' => self::$statusToAction[$status],
                'action_detail' => null,
                'file_hash_before' => null,
                'file_hash_after' => null,
                'performed_by' => ($row['whodid'] === null) ? null : (int) $row['whodid'],
                'performer_name' => decode_html((string) $row['performer_name']),
                'performed_at' => $row['changedon'],
                // ModTracker はIPアドレスを持たない
                'ip_address' => null,
            );
        }

        if (!empty($ids)) {
            self::attachChanges($entries, $ids);
        }
        return array_values($entries);
    }

    /**
     * ModTracker の項目変更を各履歴へ付ける
     *
     * @param array $entries 参照で更新する（キーは vtiger_modtracker_basic.id）
     * @param array $ids
     */
    private static function attachChanges(&$entries, $ids) {
        $db = PearDatabase::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $result = $db->pquery(
            "SELECT id, fieldname, prevalue, postvalue FROM vtiger_modtracker_detail
             WHERE id IN ($placeholders)", $ids
        );
        if ($result === false) {
            return;
        }

        // 項目として存在しないもの（vtiger_crmentity.label などの内部カラム）は出さない
        $fieldMeta = Documents_AuditLogger::getFieldMeta();

        $changesById = array();
        $numRows = $db->num_rows($result);
        for ($i = 0; $i < $numRows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            if (!isset($fieldMeta[$row['fieldname']])) {
                continue;
            }
            $id = (int) $row['id'];
            if (!isset($changesById[$id])) {
                $changesById[$id] = array();
            }
            $changesById[$id][] = array(
                'field' => $row['fieldname'],
                'old_value' => decode_html((string) $row['prevalue']),
                'new_value' => decode_html((string) $row['postvalue']),
            );
        }

        foreach ($changesById as $id => $changes) {
            if (!isset($entries[$id])) {
                continue;
            }
            // ラベル・表示用の値は監査ログと同じ付け方に揃える
            $entries[$id]['action_detail'] = array(
                'changes' => Documents_AuditLogger::decorateChanges($changes),
            );
        }
    }

    /**
     * 監査ログの履歴を取得する
     *
     * @param int $notesId
     * @return array
     */
    private static function fetchAuditEntries($notesId) {
        // 件数は統合後に数えるため、監査ログは全件取る（1レコード分なので件数は限られる）
        $log = Documents_AuditLogger::getAuditLog($notesId, 1, self::AUDIT_FETCH_LIMIT);
        $entries = array();
        foreach ($log['records'] as $record) {
            $record['entry_id'] = $record['audit_id'];
            $record['source'] = self::SOURCE_AUDIT;
            unset($record['audit_id']);
            $entries[] = $record;
        }
        return $entries;
    }

    /**
     * ModTracker と重複する監査ログを落とす
     *
     * ModTracker が持ち得ない情報を含むものは、種別が重なっていても残す。
     *
     * @param array $auditEntries
     * @param array $trackerEntries
     * @return array
     */
    private static function dropDuplicates($auditEntries, $trackerEntries) {
        $kept = array();
        foreach ($auditEntries as $entry) {
            if (self::isUniqueToAuditLog($entry)
                || !self::hasMatchingTrackerEntry($entry, $trackerEntries)) {
                $kept[] = $entry;
            }
        }
        return $kept;
    }

    /**
     * ModTracker では表せない記録かどうか
     *
     * @param array $entry
     * @return bool
     */
    private static function isUniqueToAuditLog($entry) {
        if (!in_array($entry['action_type'], self::$overlappingActions, true)) {
            // ダウンロード・ハッシュ検証
            return true;
        }
        // ファイル差替え。ModTracker は差替えという概念を持たない
        // （登録時のハッシュだけを見ると登録が重複するので、差替えの印で判断する）
        $detail = $entry['action_detail'];
        return is_array($detail) && !empty($detail['file_replaced']);
    }

    /**
     * 同じ操作を指す ModTracker の履歴があるか
     *
     * @param array $entry
     * @param array $trackerEntries
     * @return bool
     */
    private static function hasMatchingTrackerEntry($entry, $trackerEntries) {
        $at = strtotime($entry['performed_at']);
        if ($at === false) {
            return false;
        }
        foreach ($trackerEntries as $tracker) {
            if ($tracker['action_type'] !== $entry['action_type']) {
                continue;
            }
            $trackerAt = strtotime($tracker['performed_at']);
            if ($trackerAt !== false
                && abs($trackerAt - $at) <= self::DEDUP_WINDOW_SECONDS) {
                return true;
            }
        }
        return false;
    }

}
