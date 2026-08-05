<?php
/**
 * 電子帳簿保存法対応: 監査ログ記録ユーティリティ
 *
 * ドキュメントの訂正・削除履歴を vtiger_notes_audit_log に記録する。
 * 電帳法の「訂正削除の履歴確保」要件を満たすための機能。
 */
class Documents_AuditLogger {

    /**
     * 変更履歴の追跡対象外カラム
     * システムが自動更新する項目（ハッシュ・適合状態・自動計算値など）はノイズになるため除外する。
     */
    private static $untrackedColumns = array(
        'modifiedtime', 'modifiedby', 'createdtime', 'smcreatorid', 'viewedtime',
        'note_no', 'filesize', 'filetype', 'filedownloadcount', 'fileversion',
        'file_hash', 'file_hash_algorithm',
        'input_deadline', 'input_deadline_status',
        'compliance_status', 'compliance_checked_at', 'compliance_notes',
        'scanned_by', 'scanned_at',
    );

    /** action_detail に保存する値の最大文字数 */
    const MAX_VALUE_LENGTH = 255;

    /** Documentsモジュールのフィールドメタ情報キャッシュ */
    private static $fieldMetaCache = null;

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
     * Documentsモジュールのフィールドメタ情報を取得する
     *
     * @return array フィールド名 => ['table' => ..., 'column' => ..., 'label' => ..., 'uitype' => int]
     */
    public static function getFieldMeta() {
        if (self::$fieldMetaCache !== null) {
            return self::$fieldMetaCache;
        }
        $meta = array();
        try {
            $moduleModel = Vtiger_Module_Model::getInstance('Documents');
            if ($moduleModel) {
                foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
                    $meta[$fieldName] = array(
                        'table'  => $fieldModel->get('table'),
                        'column' => $fieldModel->get('column'),
                        'label'  => $fieldModel->get('label'),
                        'uitype' => (int) $fieldModel->get('uitype'),
                    );
                }
            }
        } catch (Exception $e) {
            $meta = array();
        }
        self::$fieldMetaCache = $meta;
        return $meta;
    }

    /**
     * 変更履歴の追跡対象フィールドを取得する
     *
     * @return array フィールド名 => メタ情報
     */
    public static function getTrackedFields() {
        $tracked = array();
        foreach (self::getFieldMeta() as $fieldName => $meta) {
            if (!in_array($meta['table'], array('vtiger_notes', 'vtiger_crmentity'))) {
                continue;
            }
            if (in_array($meta['column'], self::$untrackedColumns)) {
                continue;
            }
            // 自動採番は追跡しない
            if ($meta['uitype'] === 4) {
                continue;
            }
            $tracked[$fieldName] = $meta;
        }
        return $tracked;
    }

    /**
     * 追跡対象フィールドの現在値スナップショットを取得する
     *
     * 保存処理の前後で呼び出し、差分を logFieldChanges() に渡して使用する。
     *
     * @param int $notesId ドキュメントID
     * @return array フィールド名 => 値
     */
    public static function snapshotFields($notesId) {
        $notesId = (int) $notesId;
        if (empty($notesId)) {
            return array();
        }
        $tracked = self::getTrackedFields();
        if (empty($tracked)) {
            return array();
        }

        $db = PearDatabase::getInstance();
        $rows = array();
        $tables = array('vtiger_notes' => 'notesid', 'vtiger_crmentity' => 'crmid');
        foreach ($tables as $table => $keyColumn) {
            $result = $db->pquery("SELECT * FROM $table WHERE $keyColumn = ?", array($notesId));
            if ($result !== false && $db->num_rows($result) > 0) {
                $rows[$table] = $db->query_result_rowdata($result, 0);
            }
        }

        $snapshot = array();
        foreach ($tracked as $fieldName => $meta) {
            $table = $meta['table'];
            if (!isset($rows[$table]) || !array_key_exists($meta['column'], $rows[$table])) {
                continue;
            }
            $value = $rows[$table][$meta['column']];
            $snapshot[$fieldName] = ($value === null) ? null : decode_html($value);
        }
        return $snapshot;
    }

    /**
     * スナップショットの差分を算出する
     *
     * @param array $before 変更前スナップショット
     * @param array $after 変更後スナップショット
     * @return array [['field' => ..., 'old_value' => ..., 'new_value' => ...], ...]
     */
    public static function diffSnapshots($before, $after) {
        $changes = array();
        foreach (array_keys(self::getTrackedFields()) as $fieldName) {
            if (!array_key_exists($fieldName, $before) || !array_key_exists($fieldName, $after)) {
                continue;
            }
            $oldValue = $before[$fieldName];
            $newValue = $after[$fieldName];
            if (self::normalizeValue($oldValue) === self::normalizeValue($newValue)) {
                continue;
            }
            $changes[] = array(
                'field' => $fieldName,
                'old_value' => self::truncateValue($oldValue),
                'new_value' => self::truncateValue($newValue),
            );
        }
        return $changes;
    }

    /**
     * 項目値の変更を監査ログに記録する
     *
     * @param int $notesId ドキュメントID
     * @param array $before 変更前スナップショット
     * @param array $after 変更後スナップショット
     * @param string|null $reason 変更理由
     * @return bool 記録した場合true（差分が無い場合はfalse）
     */
    public static function logFieldChanges($notesId, $before, $after, $reason = null) {
        $changes = self::diffSnapshots($before, $after);
        if (empty($changes)) {
            return false;
        }
        return self::logUpdate($notesId, $changes, $reason);
    }

    /**
     * 比較用に値を正規化する
     *
     * @param mixed $value
     * @return string
     */
    private static function normalizeValue($value) {
        if ($value === null) {
            return '';
        }
        $value = trim(str_replace(array("\r\n", "\r"), "\n", (string) $value));
        if ($value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }
        return $value;
    }

    /**
     * 監査ログに保存する値を最大長で切り詰める
     *
     * @param mixed $value
     * @return string
     */
    private static function truncateValue($value) {
        if ($value === null) {
            return '';
        }
        $value = (string) $value;
        if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > self::MAX_VALUE_LENGTH) {
            return mb_substr($value, 0, self::MAX_VALUE_LENGTH, 'UTF-8') . '…';
        }
        if (!function_exists('mb_strlen') && strlen($value) > self::MAX_VALUE_LENGTH) {
            return substr($value, 0, self::MAX_VALUE_LENGTH) . '...';
        }
        return $value;
    }

    /**
     * 変更内容に表示用のラベル・値を付与する
     *
     * @param array $changes
     * @return array
     */
    private static function decorateChanges($changes) {
        $fieldMeta = self::getFieldMeta();
        $decorated = array();
        foreach ($changes as $change) {
            if (!is_array($change) || !isset($change['field'])) {
                $decorated[] = $change;
                continue;
            }
            $fieldName = $change['field'];
            $meta = isset($fieldMeta[$fieldName]) ? $fieldMeta[$fieldName] : null;
            $change['label'] = $meta ? vtranslate($meta['label'], 'Documents') : $fieldName;
            $change['old_display'] = self::getDisplayValue(
                isset($change['old_value']) ? $change['old_value'] : null, $meta);
            $change['new_display'] = self::getDisplayValue(
                isset($change['new_value']) ? $change['new_value'] : null, $meta);
            $decorated[] = $change;
        }
        return $decorated;
    }

    /**
     * 項目値を表示用の文字列に変換する
     *
     * @param mixed $value
     * @param array|null $meta フィールドメタ情報
     * @return string
     */
    private static function getDisplayValue($value, $meta) {
        if ($value === null || $value === '' || self::normalizeValue($value) === '') {
            return '';
        }
        $uitype = ($meta === null) ? 0 : (int) $meta['uitype'];
        switch ($uitype) {
            case 15: // ピックリスト（ロール依存）
            case 16: // ピックリスト
            case 55: // 敬称
                return vtranslate($value, 'Documents');
            case 33: // 複数選択ピックリスト
                $labels = array();
                foreach (explode(' |##| ', $value) as $item) {
                    $labels[] = vtranslate($item, 'Documents');
                }
                return implode(', ', $labels);
            case 27: // ダウンロード種別（内部ファイル / 外部URL）
                return ($value === 'I')
                    ? vtranslate('LBL_INTERNAL', 'Documents')
                    : vtranslate('LBL_EXTERNAL', 'Documents');
            case 26: // フォルダ
                return self::getFolderName($value);
            case 52: // ユーザー
            case 53: // 担当者（ユーザー / グループ）
                // getOwnerName() は画面表示と同じ表示名（ユーザー名 / グループ名）を返す
                $ownerLabel = getOwnerName($value);
                return !empty($ownerLabel) ? decode_html($ownerLabel) : (string) $value;
            case 10: // 参照
                $recordLabel = Vtiger_Util_Helper::getRecordName((int) $value);
                return !empty($recordLabel) ? decode_html($recordLabel) : (string) $value;
            case 56: // チェックボックス
                return ((string) $value === '1')
                    ? vtranslate('LBL_YES', 'Vtiger')
                    : vtranslate('LBL_NO', 'Vtiger');
            default:
                return (string) $value;
        }
    }

    /**
     * フォルダ名を取得する
     *
     * @param int $folderId
     * @return string
     */
    private static function getFolderName($folderId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT foldername FROM vtiger_attachmentsfolder WHERE folderid = ?",
            array((int) $folderId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return (string) $folderId;
        }
        return decode_html($db->query_result($result, 0, 'foldername'));
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
            // 項目値の変更には表示用のラベル・値を付与する
            if (is_array($detail) && !empty($detail['changes']) && is_array($detail['changes'])) {
                $detail['changes'] = self::decorateChanges($detail['changes']);
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
