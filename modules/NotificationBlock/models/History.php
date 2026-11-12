<?php

/**
 * 送信履歴と、宛先ごとの結果
 *
 * 決まり：
 * - 送信履歴は送る前に作り（結果「送信中」）、1 人送るごとに結果を記録する
 * - 途中で止まったときは、送れなかった宛先を「未送信（中断）」とし、全体を「一部失敗」（1 人も送れていなければ「失敗」）とする
 * - 最後に 1 人分を記録してから 10 分を過ぎても「送信中」のままの行は、表示するときに中断とみなす
 * - 送信者・宛先は、送った時点の氏名・メールアドレスで残す
 */
class NotificationBlock_History_Model
{
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    public const RECIPIENT_PENDING = 'pending';
    public const RECIPIENT_SENT = 'sent';
    public const RECIPIENT_FAILED = 'failed';
    public const RECIPIENT_ABORTED = 'aborted';

    public const METHOD_MANUAL = 'manual';
    public const METHOD_CREATE = 'create';
    public const METHOD_EXTERNAL = 'external';

    /** 送信が止まったとみなすまでの時間（秒）。最後に 1 人分を記録した時点から数える */
    public const STALE_SECONDS = 600;

    /** 理由（言語ファイル NotificationBlock.php のキー） */
    public const REASON_NO_SERVER = 'LBL_REASON_NO_SERVER';
    public const REASON_ABORTED = 'LBL_REASON_ABORTED';

    /**
     * 送信履歴を「送信中」で作り、宛先を「未送信」で登録する
     *
     * @param array{crmid: int, setype: string, sent_by: int, sender_name: string, method: string, method_label: string, method_module: string, comment: string} $info
     * @param array<int, array{userid: int, name: string, email: string}> $recipients
     */
    public static function create(array $info, array $recipients, string $status = self::STATUS_SENDING, string $failureReason = ''): int
    {
        $db = PearDatabase::getInstance();
        $now = NotificationBlock_Util_Helper::now();
        $db->pquery(
            'INSERT INTO ' . NotificationBlock_Config_Model::HISTORY_TABLE . ' (crmid, setype, sent_by, sender_name, method,
             method_label, method_module, comment, status, recipient_count, failure_reason, started_at, last_progress_at, finished_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $info['crmid'], $info['setype'], $info['sent_by'], $info['sender_name'], $info['method'],
                $info['method_label'], $info['method_module'], $info['comment'], $status, count($recipients),
                $failureReason, $now, $now, $status === self::STATUS_SENDING ? null : $now,
            ]
        );
        $historyId = NotificationBlock_Util_Helper::toInt($db->getLastInsertID());
        $recipientStatus = $status === self::STATUS_SENDING ? self::RECIPIENT_PENDING : self::RECIPIENT_FAILED;
        foreach (array_values($recipients) as $sequence => $recipient) {
            $db->pquery(
                'INSERT INTO ' . NotificationBlock_Config_Model::RECIPIENTS_TABLE
                . ' (historyid, userid, user_name, email, status, reason, sequence) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$historyId, $recipient['userid'], $recipient['name'], $recipient['email'], $recipientStatus, $failureReason, $sequence]
            );
        }
        return $historyId;
    }

    /**
     * 宛先 1 人分の結果を記録する（送信履歴の「最後に記録した日時」も進める）
     */
    public static function markRecipient(int $historyId, int $userId, bool $sent, string $reason = ''): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE ' . NotificationBlock_Config_Model::RECIPIENTS_TABLE . ' SET status = ?, reason = ? WHERE historyid = ? AND userid = ?',
            [$sent ? self::RECIPIENT_SENT : self::RECIPIENT_FAILED, mb_substr($reason, 0, 500), $historyId, $userId]
        );
        $db->pquery(
            'UPDATE ' . NotificationBlock_Config_Model::HISTORY_TABLE . ' SET last_progress_at = ? WHERE historyid = ?',
            [NotificationBlock_Util_Helper::now(), $historyId]
        );
    }

    /**
     * 送り終えた（または途中で止まった）送信履歴の結果を決める
     *
     * まだ送っていない宛先は「未送信（中断）」にする。
     *
     * @return array{status: string, total: int, sent: int, failed: int}
     */
    public static function finish(int $historyId): array
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE ' . NotificationBlock_Config_Model::RECIPIENTS_TABLE . ' SET status = ?, reason = ? WHERE historyid = ? AND status = ?',
            [self::RECIPIENT_ABORTED, self::REASON_ABORTED, $historyId, self::RECIPIENT_PENDING]
        );
        $counts = self::countRecipients($historyId);
        $status = self::decideStatus($counts['sent'], $counts['total']);
        $now = NotificationBlock_Util_Helper::now();
        $db->pquery(
            'UPDATE ' . NotificationBlock_Config_Model::HISTORY_TABLE
            . ' SET status = ?, last_progress_at = ?, finished_at = ? WHERE historyid = ? AND status = ?',
            [$status, $now, $now, $historyId, self::STATUS_SENDING]
        );
        return ['status' => $status, 'total' => $counts['total'], 'sent' => $counts['sent'], 'failed' => $counts['total'] - $counts['sent']];
    }

    /**
     * そのレコードに、まだ送っている最中の送信履歴があるか（10 分止まったものは数えない）
     */
    public static function isSending(int $crmId): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT 1 FROM ' . NotificationBlock_Config_Model::HISTORY_TABLE
            . ' WHERE crmid = ? AND status = ? AND last_progress_at >= ? LIMIT 1',
            [$crmId, self::STATUS_SENDING, self::staleThreshold()]
        );
        return $db->num_rows($result) > 0;
    }

    /**
     * そのレコードの送信履歴の件数（通知ブロックの「送信履歴（N 件）」に出す）
     */
    public static function countByRecord(int $crmId): int
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT COUNT(*) AS cnt FROM ' . NotificationBlock_Config_Model::HISTORY_TABLE . ' WHERE crmid = ?', [$crmId]);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        return NotificationBlock_Util_Helper::toInt($rows[0]['cnt'] ?? 0);
    }

    /**
     * そのレコードのすべての送信履歴（新しい順）
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(int $crmId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT * FROM ' . NotificationBlock_Config_Model::HISTORY_TABLE . ' WHERE crmid = ? ORDER BY historyid DESC',
            [$crmId]
        );
        $histories = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $histories[] = self::present($row);
        }
        return $histories;
    }

    /**
     * 送信履歴 1 件の宛先の一覧（送った時点の氏名・メールアドレス）
     *
     * @return array<int, array{name: string, email: string, status: string, status_label: string, reason: string}>
     */
    public static function getRecipients(int $historyId): array
    {
        $history = self::getById($historyId);
        if ($history === null) {
            return [];
        }
        $stale = $history['status'] === self::STATUS_SENDING && $history['is_stale'];
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT userid, user_name, email, status, reason FROM ' . NotificationBlock_Config_Model::RECIPIENTS_TABLE
            . ' WHERE historyid = ? ORDER BY sequence',
            [$historyId]
        );
        $recipients = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $status = NotificationBlock_Util_Helper::toStr($row['status'] ?? '');
            $reason = NotificationBlock_Util_Helper::toStr($row['reason'] ?? '');
            if ($stale && $status === self::RECIPIENT_PENDING) {
                $status = self::RECIPIENT_ABORTED;
                $reason = self::REASON_ABORTED;
            }
            $recipients[] = [
                'name' => NotificationBlock_Util_Helper::toStr($row['user_name'] ?? ''),
                'email' => NotificationBlock_Util_Helper::toStr($row['email'] ?? ''),
                'status' => $status,
                'status_label' => self::translate('LBL_RECIPIENT_STATUS_' . strtoupper($status)),
                'reason' => self::translateReason($reason),
            ];
        }
        return $recipients;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getById(int $historyId): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM ' . NotificationBlock_Config_Model::HISTORY_TABLE . ' WHERE historyid = ?', [$historyId]);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        if (empty($rows)) {
            return null;
        }
        return self::present($rows[0]);
    }

    /**
     * 完全に削除したレコードの送信履歴を消す
     *
     * @param array<int, int> $crmIds
     */
    public static function deleteByRecordIds(array $crmIds): void
    {
        if (empty($crmIds)) {
            return;
        }
        $db = PearDatabase::getInstance();
        $db->pquery(
            'DELETE r FROM ' . NotificationBlock_Config_Model::RECIPIENTS_TABLE . ' r INNER JOIN '
            . NotificationBlock_Config_Model::HISTORY_TABLE . ' h ON h.historyid = r.historyid
             WHERE h.crmid IN (' . generateQuestionMarks($crmIds) . ')',
            $crmIds
        );
        $db->pquery(
            'DELETE FROM ' . NotificationBlock_Config_Model::HISTORY_TABLE . ' WHERE crmid IN (' . generateQuestionMarks($crmIds) . ')',
            $crmIds
        );
    }

    public static function decideStatus(int $sent, int $total): string
    {
        if ($total > 0 && $sent === $total) {
            return self::STATUS_SENT;
        }
        return $sent > 0 ? self::STATUS_PARTIAL : self::STATUS_FAILED;
    }

    /**
     * 理由を見る人の言語にする（言語ファイルのキーならその訳、メールサーバーの返したエラーならそのまま）
     */
    public static function translateReason(string $reason): string
    {
        if (str_starts_with($reason, 'LBL_')) {
            return self::translate($reason);
        }
        return $reason;
    }

    /**
     * 画面に出す形にする（10 分止まった「送信中」は、中断とみなした結果にする）
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function present(array $row): array
    {
        $historyId = NotificationBlock_Util_Helper::toInt($row['historyid'] ?? 0);
        $status = NotificationBlock_Util_Helper::toStr($row['status'] ?? '');
        $lastProgress = NotificationBlock_Util_Helper::toStr($row['last_progress_at'] ?? '');
        $isStale = $status === self::STATUS_SENDING && $lastProgress < self::staleThreshold();
        $total = NotificationBlock_Util_Helper::toInt($row['recipient_count'] ?? 0);
        $displayStatus = $status;
        $sentCount = null;
        if ($isStale) {
            $sentCount = self::countRecipients($historyId)['sent'];
            $displayStatus = self::decideStatus($sentCount, $total);
        }
        $method = NotificationBlock_Util_Helper::toStr($row['method'] ?? '');
        $methodLabel = NotificationBlock_Util_Helper::toStr($row['method_label'] ?? '');
        $methodModule = NotificationBlock_Util_Helper::toStr($row['method_module'] ?? '');
        if ($method === self::METHOD_EXTERNAL && $methodLabel !== '') {
            $methodText = NotificationBlock_Util_Helper::toStr(vtranslate($methodLabel, $methodModule !== '' ? $methodModule : 'Vtiger'));
        } else {
            $methodText = self::translate('LBL_METHOD_' . strtoupper($method));
        }
        return [
            'historyid' => $historyId,
            'crmid' => NotificationBlock_Util_Helper::toInt($row['crmid'] ?? 0),
            'status' => $status,
            'is_stale' => $isStale,
            'display_status' => $displayStatus,
            'status_label' => self::translate('LBL_STATUS_' . strtoupper($displayStatus)),
            'sender_name' => NotificationBlock_Util_Helper::toStr($row['sender_name'] ?? ''),
            'method' => $method,
            'method_text' => $methodText,
            'comment' => NotificationBlock_Util_Helper::toStr($row['comment'] ?? ''),
            'recipient_count' => $total,
            'failure_reason' => self::translateReason(NotificationBlock_Util_Helper::toStr($row['failure_reason'] ?? '')),
            'started_at' => NotificationBlock_Util_Helper::toStr($row['started_at'] ?? ''),
            'started_at_display' => NotificationBlock_Util_Helper::formatDateTime(NotificationBlock_Util_Helper::toStr($row['started_at'] ?? '')),
        ];
    }

    /**
     * @return array{total: int, sent: int}
     */
    private static function countRecipients(int $historyId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT COUNT(*) AS total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS sent FROM '
            . NotificationBlock_Config_Model::RECIPIENTS_TABLE . ' WHERE historyid = ?',
            [self::RECIPIENT_SENT, $historyId]
        );
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        return [
            'total' => NotificationBlock_Util_Helper::toInt($rows[0]['total'] ?? 0),
            'sent' => NotificationBlock_Util_Helper::toInt($rows[0]['sent'] ?? 0),
        ];
    }

    private static function staleThreshold(): string
    {
        return date('Y-m-d H:i:s', time() - self::STALE_SECONDS);
    }

    private static function translate(string $key): string
    {
        return NotificationBlock_Util_Helper::toStr(vtranslate($key, NotificationBlock_Config_Model::MODULE_NAME));
    }
}
