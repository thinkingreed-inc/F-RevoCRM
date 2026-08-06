<?php
/**
 * 電子帳簿保存法対応: 適合チェックロジック
 *
 * ドキュメントが電帳法の要件を満たしているかを検証し、compliance_status を更新する。
 */
class Documents_ComplianceChecker {

    /** 電帳法検索要件を満たすために関連付けが必要なモジュール */
    const TRANSACTION_MODULES = array(
        'SalesOrder', 'Invoice', 'PurchaseOrder', 'Quotes',
        'Accounts', 'Vendors',
    );

    /** 書類区分の有効値 */
    const VALID_CATEGORIES = array(
        'invoice', 'receipt', 'contract', 'estimate', 'order', 'delivery', 'other',
    );

    /** 保存区分の有効値 */
    const VALID_PRESERVATION_TYPES = array(
        'electronic_transaction', 'scanner',
    );

    /**
     * ドキュメントが電帳法対象かどうかを判定する
     *
     * @param int $notesId ドキュメントID
     * @return bool
     */
    public static function isComplianceTarget($notesId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT document_category FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return false;
        }
        $category = $db->query_result($result, 0, 'document_category');
        return !empty($category);
    }

    /**
     * 関連レコードの有無をチェックする
     *
     * @param int $notesId ドキュメントID
     * @return array ['has_related' => bool, 'related_records' => array]
     */
    public static function checkRelatedRecords($notesId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_senotesrel.crmid, vtiger_crmentity.setype, vtiger_crmentity.label
            FROM vtiger_senotesrel
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_senotesrel.crmid
            WHERE vtiger_senotesrel.notesid = ? AND vtiger_crmentity.deleted = 0
            AND vtiger_crmentity.setype IN (" . generateQuestionMarks(self::TRANSACTION_MODULES) . ")",
            array_merge(array($notesId), self::TRANSACTION_MODULES)
        );

        $relatedRecords = array();
        if ($result !== false) {
            $numRows = $db->num_rows($result);
            for ($i = 0; $i < $numRows; $i++) {
                $row = $db->query_result_rowdata($result, $i);
                $relatedRecords[] = array(
                    'id' => (int) $row['crmid'],
                    'module' => $row['setype'],
                    'label' => decode_html($row['label']),
                );
            }
        }

        return array(
            'has_related' => count($relatedRecords) > 0,
            'related_records' => $relatedRecords,
        );
    }

    /**
     * ドキュメントの適合チェックを実行し、ステータスを更新する
     *
     * @param int $notesId ドキュメントID
     * @return array ['status' => string, 'issues' => array]
     */
    public static function check($notesId) {
        $db = PearDatabase::getInstance();
        // 不適合の理由は言語非依存の翻訳キーで保持し、表示時に翻訳する
        // （compliance_notes に翻訳済みの文字列を保存すると保存時の言語で固定されてしまう）
        $issueKeys = array();

        // 電帳法対象でなければスキップ
        if (!self::isComplianceTarget($notesId)) {
            return array('status' => null, 'issues' => array());
        }

        // ドキュメント情報取得
        $result = $db->pquery(
            "SELECT document_category, preservation_type, file_hash,
                    filelocationtype, scan_resolution_dpi, scan_color_type
            FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return array(
                'status' => 'non_compliant',
                'issues' => array(vtranslate('LBL_ISSUE_RECORD_NOT_FOUND', 'Documents')),
            );
        }
        $row = $db->query_result_rowdata($result, 0);

        // 1. 関連レコードチェック
        $relCheck = self::checkRelatedRecords($notesId);
        if (!$relCheck['has_related']) {
            $issueKeys[] = 'LBL_NO_RELATED_RECORD';
        }

        // 2. ファイルハッシュチェック（内部ファイルのみ）
        if ($row['filelocationtype'] === 'I' && empty($row['file_hash'])) {
            $issueKeys[] = 'LBL_ISSUE_NO_FILE_HASH';
        }

        // 3. 保存区分チェック
        if (empty($row['preservation_type'])) {
            $issueKeys[] = 'LBL_ISSUE_NO_PRESERVATION_TYPE';
        }

        // 4. スキャナ保存固有チェック
        if ($row['preservation_type'] === 'scanner') {
            if (!empty($row['scan_resolution_dpi']) && (int) $row['scan_resolution_dpi'] < 200) {
                $issueKeys[] = 'LBL_ISSUE_LOW_SCAN_RESOLUTION';
            }
        }

        // ステータス判定
        $status = empty($issueKeys) ? 'compliant' : 'non_compliant';

        // DBを更新（翻訳キーのまま保存する）
        $db->pquery(
            "UPDATE vtiger_notes SET compliance_status = ?, compliance_checked_at = NOW(),
             compliance_notes = ? WHERE notesid = ?",
            array($status, implode('; ', $issueKeys), $notesId)
        );

        return array('status' => $status, 'issues' => self::translateNotes($issueKeys));
    }

    /**
     * compliance_notes を表示用の文字列に変換する
     *
     * 翻訳キーで保存された不適合理由を現在の言語に翻訳する。
     * 過去に日本語のまま保存された値はキーに一致しないため、そのまま返る。
     *
     * @param string|array $notes 「; 」区切りの文字列またはキーの配列
     * @param bool $asArray true なら配列で返す
     * @return string|array
     */
    public static function translateNotes($notes, $asArray = true) {
        if ($notes === null || $notes === '') {
            return $asArray ? array() : '';
        }
        $keys = is_array($notes) ? $notes : explode('; ', $notes);
        $labels = array();
        foreach ($keys as $key) {
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            $labels[] = vtranslate($key, 'Documents');
        }
        return $asArray ? $labels : implode('; ', $labels);
    }

    /**
     * 電帳法対象ドキュメントの一括適合チェック
     *
     * @return array ['checked' => int, 'compliant' => int, 'non_compliant' => int]
     */
    public static function batchCheck() {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.notesid FROM vtiger_notes
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
            WHERE vtiger_notes.document_category IS NOT NULL AND vtiger_crmentity.deleted = 0",
            array()
        );

        if ($result === false) {
            throw new Exception(vtranslate('LBL_BATCH_CHECK_FAILED', 'Documents'));
        }

        $checked = 0;
        $compliant = 0;
        $nonCompliant = 0;

        $numRows = $db->num_rows($result);
        for ($i = 0; $i < $numRows; $i++) {
            $notesId = $db->query_result($result, $i, 'notesid');
            $checkResult = self::check($notesId);
            $checked++;
            if ($checkResult['status'] === 'compliant') {
                $compliant++;
            } else {
                $nonCompliant++;
            }
        }

        return array('checked' => $checked, 'compliant' => $compliant, 'non_compliant' => $nonCompliant);
    }
}
