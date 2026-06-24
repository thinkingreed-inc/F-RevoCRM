<?php
/**
 * 電子帳簿保存法対応: 監査ログ記録ユーティリティ
 *
 * ドキュメントの訂正・削除履歴を vtiger_notes_audit_log に記録する。
 * 電帳法の「訂正削除の履歴確保」要件を満たすための機能。
 */
class Documents_AuditLogger {

    /**
     * 監査ログを記録する
     *
     * @param int $notesId ドキュメントID
     * @param string $actionType 操作種別 (create, update, delete, restore, download, verify)
     * @param array $options オプション情報
     *   - action_detail: string|array 操作詳細（配列の場合はJSON化）
     *   - file_hash_before: string 変更前ハッシュ
     *   - file_hash_after: string 変更後ハッシュ
     *   - performed_by: int 操作ユーザーID（省略時は現在のユーザー）
     * @return bool 成功時true
     */
    public static function log($notesId, $actionType, $options = array()) {
        $db = PearDatabase::getInstance();

        // 操作ユーザー
        $performedBy = isset($options['performed_by']) ? $options['performed_by'] : self::getCurrentUserId();

        // 操作詳細
        $actionDetail = null;
        if (isset($options['action_detail'])) {
            $actionDetail = is_array($options['action_detail'])
                ? json_encode($options['action_detail'], JSON_UNESCAPED_UNICODE)
                : $options['action_detail'];
        }

        $fileHashBefore = isset($options['file_hash_before']) ? $options['file_hash_before'] : null;
        $fileHashAfter = isset($options['file_hash_after']) ? $options['file_hash_after'] : null;

        // IPアドレスとUser-Agent
        $ipAddress = self::getClientIp();
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

        $result = $db->pquery(
            "INSERT INTO vtiger_notes_audit_log
                (notesid, action_type, action_detail, file_hash_before, file_hash_after, performed_by, performed_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)",
            array($notesId, $actionType, $actionDetail, $fileHashBefore, $fileHashAfter, $performedBy, $ipAddress, $userAgent)
        );

        return $result !== false;
    }

    /**
     * ドキュメント新規登録ログ
     *
     * @param int $notesId ドキュメントID
     * @param array $initialData 初期データ
     * @param string|null $fileHash ファイルハッシュ
     * @return bool
     */
    public static function logCreate($notesId, $initialData = array(), $fileHash = null) {
        return self::log($notesId, 'create', array(
            'action_detail' => $initialData,
            'file_hash_after' => $fileHash,
        ));
    }

    /**
     * フィールド変更ログ
     *
     * @param int $notesId ドキュメントID
     * @param array $changes 変更内容 [['field' => '...', 'old_value' => '...', 'new_value' => '...'], ...]
     * @param string|null $reason 変更理由
     * @return bool
     */
    public static function logUpdate($notesId, $changes, $reason = null) {
        $detail = array('changes' => $changes);
        if ($reason !== null) {
            $detail['reason'] = $reason;
        }
        return self::log($notesId, 'update', array(
            'action_detail' => $detail,
        ));
    }

    /**
     * ファイル差替えログ
     *
     * @param int $notesId ドキュメントID
     * @param string|null $hashBefore 変更前ハッシュ
     * @param string|null $hashAfter 変更後ハッシュ
     * @param string|null $reason 変更理由
     * @return bool
     */
    public static function logFileReplace($notesId, $hashBefore, $hashAfter, $reason = null) {
        $detail = array('file_replaced' => true);
        if ($reason !== null) {
            $detail['reason'] = $reason;
        }
        return self::log($notesId, 'update', array(
            'action_detail' => $detail,
            'file_hash_before' => $hashBefore,
            'file_hash_after' => $hashAfter,
        ));
    }

    /**
     * 削除ログ
     *
     * @param int $notesId ドキュメントID
     * @param array $recordData 削除時点のデータ
     * @return bool
     */
    public static function logDelete($notesId, $recordData = array()) {
        return self::log($notesId, 'delete', array(
            'action_detail' => $recordData,
        ));
    }

    /**
     * ダウンロードログ
     *
     * @param int $notesId ドキュメントID
     * @return bool
     */
    public static function logDownload($notesId) {
        return self::log($notesId, 'download');
    }

    /**
     * ハッシュ検証ログ
     *
     * @param int $notesId ドキュメントID
     * @param bool $isValid 検証結果
     * @param string $message 検証メッセージ
     * @return bool
     */
    public static function logVerify($notesId, $isValid, $message = '') {
        return self::log($notesId, 'verify', array(
            'action_detail' => array(
                'result' => $isValid ? 'success' : 'failure',
                'message' => $message,
            ),
        ));
    }

    /**
     * 監査ログを取得する
     *
     * @param int $notesId ドキュメントID
     * @param int $page ページ番号
     * @param int $limit 1ページあたりの件数
     * @return array ['records' => [...], 'total' => int]
     */
    public static function getAuditLog($notesId, $page = 1, $limit = 20) {
        $db = PearDatabase::getInstance();

        // 件数取得
        $countResult = $db->pquery(
            "SELECT COUNT(*) AS total FROM vtiger_notes_audit_log WHERE notesid = ?",
            array($notesId)
        );
        if ($countResult === false) {
            throw new Exception('監査ログの件数取得に失敗しました');
        }
        $total = (int) $db->query_result($countResult, 0, 'total');

        // データ取得
        $offset = ($page - 1) * $limit;
        $result = $db->pquery(
            "SELECT al.*, CONCAT(u.last_name, ' ', u.first_name) AS performer_name
            FROM vtiger_notes_audit_log al
            LEFT JOIN vtiger_users u ON u.id = al.performed_by
            WHERE al.notesid = ?
            ORDER BY al.audit_id DESC
            LIMIT ?, ?",
            array($notesId, $offset, $limit)
        );

        if ($result === false) {
            throw new Exception('監査ログの取得に失敗しました');
        }

        $records = array();
        $numRows = $db->num_rows($result);
        for ($i = 0; $i < $numRows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $detail = $row['action_detail'];
            if (!empty($detail)) {
                // query_result_rowdataがHTMLエンコードする場合があるのでデコードする
                $rawDetail = decode_html($detail);
                $decoded = json_decode($rawDetail, true);
                if ($decoded !== null) {
                    $detail = $decoded;
                }
            }
            $records[] = array(
                'audit_id' => (int) $row['audit_id'],
                'action_type' => $row['action_type'],
                'action_detail' => $detail,
                'file_hash_before' => $row['file_hash_before'],
                'file_hash_after' => $row['file_hash_after'],
                'performed_by' => (int) $row['performed_by'],
                'performer_name' => decode_html($row['performer_name']),
                'performed_at' => $row['performed_at'],
                'ip_address' => $row['ip_address'],
            );
        }

        return array('records' => $records, 'total' => $total);
    }

    /**
     * 現在のユーザーIDを取得する
     *
     * @return int
     */
    private static function getCurrentUserId() {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        return $currentUser ? $currentUser->getId() : 0;
    }

    /**
     * クライアントIPアドレスを取得する
     *
     * @return string
     */
    private static function getClientIp() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}
