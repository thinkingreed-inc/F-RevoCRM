<?php
/**
 * 電子帳簿保存法対応 API
 *
 * 電帳法メタデータの保存、関連チェック、ハッシュ検証、監査ログ取得を提供する。
 */
require_once 'modules/Documents/utils/FileHasher.php';
require_once 'modules/Documents/utils/AuditLogger.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';

class Documents_ComplianceAPI_Api extends Vtiger_Api_Controller {

    public function requiresPermission(Vtiger_Request $request) {
        $permissions = parent::requiresPermission($request);
        $mode = $request->get('mode');
        if (in_array($mode, array('batch_verify_hash', 'compliance_report'))) {
            $permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'is_admin' => true);
        } else {
            $permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
        }
        return $permissions;
    }

    protected function processApi(Vtiger_Request $request) {
        $mode = $request->get('mode');
        switch ($mode) {
            case 'save_compliance':
                return $this->sendSuccess($this->saveCompliance($request));
            case 'check_related':
                return $this->sendSuccess($this->checkRelated($request));
            case 'verify_hash':
                return $this->sendSuccess($this->verifyHash($request));
            case 'batch_verify_hash':
                return $this->sendSuccess($this->batchVerifyHash($request));
            case 'get_audit_log':
                return $this->sendSuccess($this->getAuditLog($request));
            case 'compliance_report':
                return $this->sendSuccess($this->complianceReport($request));
            case 'check_compliance':
                return $this->sendSuccess($this->checkCompliance($request));
            default:
                $this->sendError('Invalid mode: ' . $mode, 400);
        }
    }

    /**
     * 電帳法メタデータの保存
     */
    private function saveCompliance(Vtiger_Request $request) {
        $notesId = (int) $request->get('notesid');
        if (empty($notesId)) {
            throw new Exception('notesid is required');
        }

        $db = PearDatabase::getInstance();

        // 変更前の値を取得（監査ログ用）
        $beforeResult = $db->pquery(
            "SELECT document_category, preservation_type, receipt_date,
                    scan_resolution_dpi, scan_color_type, original_paper_size
            FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($beforeResult === false || $db->num_rows($beforeResult) === 0) {
            throw new Exception('Document not found');
        }
        $beforeData = $db->query_result_rowdata($beforeResult, 0);

        // 更新カラム組み立て
        $updates = array();
        $params = array();
        $changes = array();

        $fields = array(
            'document_category' => $request->get('document_category'),
            'preservation_type' => $request->get('preservation_type'),
            'receipt_date' => $request->get('receipt_date'),
            'scan_resolution_dpi' => $request->get('scan_resolution_dpi'),
            'scan_color_type' => $request->get('scan_color_type'),
            'original_paper_size' => $request->get('original_paper_size'),
        );

        foreach ($fields as $field => $value) {
            if ($value !== null && $value !== '') {
                $updates[] = "$field = ?";
                $params[] = $value;
                if (isset($beforeData[$field]) && $beforeData[$field] !== $value) {
                    $changes[] = array(
                        'field' => $field,
                        'old_value' => $beforeData[$field],
                        'new_value' => $value,
                    );
                }
            }
        }

        // 入力期限自動計算（スキャナ保存で受領日がある場合）
        $receiptDate = $request->get('receipt_date');
        $preservationType = $request->get('preservation_type');
        if ($preservationType === 'scanner' && !empty($receiptDate)) {
            require_once 'modules/Documents/utils/DeadlineCalculator.php';
            if (class_exists('Documents_DeadlineCalculator')) {
                $deadline = Documents_DeadlineCalculator::calculate($receiptDate);
                if ($deadline) {
                    $updates[] = "input_deadline = ?";
                    $params[] = $deadline;
                }
            }
        }

        if (!empty($updates)) {
            $params[] = $notesId;
            $result = $db->pquery(
                "UPDATE vtiger_notes SET " . implode(', ', $updates) . " WHERE notesid = ?",
                $params
            );
            if ($result === false) {
                throw new Exception('電帳法メタデータの保存に失敗しました');
            }
        }

        // 監査ログ記録
        if (!empty($changes)) {
            Documents_AuditLogger::logUpdate($notesId, $changes);
        }

        // 適合チェック実行
        $complianceResult = Documents_ComplianceChecker::check($notesId);

        return array(
            'success' => true,
            'notesid' => $notesId,
            'compliance_status' => $complianceResult['status'],
            'issues' => $complianceResult['issues'],
        );
    }

    /**
     * 関連レコードチェック
     */
    private function checkRelated(Vtiger_Request $request) {
        $notesId = (int) $request->get('notesid');
        if (empty($notesId)) {
            throw new Exception('notesid is required');
        }
        return Documents_ComplianceChecker::checkRelatedRecords($notesId);
    }

    /**
     * ハッシュ検証
     */
    private function verifyHash(Vtiger_Request $request) {
        $notesId = (int) $request->get('notesid');
        if (empty($notesId)) {
            throw new Exception('notesid is required');
        }

        $result = Documents_FileHasher::verifyHash($notesId);

        // 監査ログ記録
        Documents_AuditLogger::logVerify($notesId, $result['valid'], $result['message']);

        return $result;
    }

    /**
     * 一括ハッシュ検証
     */
    private function batchVerifyHash(Vtiger_Request $request) {
        $notesIds = $request->get('notesids');
        if (empty($notesIds) || !is_array($notesIds)) {
            throw new Exception('notesids array is required');
        }

        $results = array();
        $valid = 0;
        $invalid = 0;
        $errors = 0;

        foreach ($notesIds as $notesId) {
            $notesId = (int) $notesId;
            $result = Documents_FileHasher::verifyHash($notesId);
            Documents_AuditLogger::logVerify($notesId, $result['valid'], $result['message']);

            if ($result['valid']) {
                $valid++;
            } elseif ($result['current_hash'] === null) {
                $errors++;
            } else {
                $invalid++;
            }
            $results[] = array_merge(array('notesid' => $notesId), $result);
        }

        return array(
            'total' => count($notesIds),
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
            'results' => $results,
        );
    }

    /**
     * 監査ログ取得
     */
    private function getAuditLog(Vtiger_Request $request) {
        $notesId = (int) $request->get('notesid');
        if (empty($notesId)) {
            throw new Exception('notesid is required');
        }
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));

        return Documents_AuditLogger::getAuditLog($notesId, $page, $limit);
    }

    /**
     * 適合状態レポート
     */
    private function complianceReport(Vtiger_Request $request) {
        $db = PearDatabase::getInstance();

        // 統計情報
        $result = $db->pquery(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN compliance_status = 'compliant' THEN 1 ELSE 0 END) AS compliant_count,
                SUM(CASE WHEN compliance_status = 'non_compliant' THEN 1 ELSE 0 END) AS non_compliant_count,
                SUM(CASE WHEN input_deadline_status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count
            FROM vtiger_notes
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
            WHERE vtiger_notes.document_category IS NOT NULL AND vtiger_crmentity.deleted = 0",
            array()
        );

        if ($result === false) {
            throw new Exception('レポート生成に失敗しました');
        }

        $row = $db->query_result_rowdata($result, 0);
        return array(
            'total' => (int) $row['total'],
            'compliant' => (int) $row['compliant_count'],
            'non_compliant' => (int) $row['non_compliant_count'],
            'overdue' => (int) $row['overdue_count'],
        );
    }

    /**
     * 適合チェック実行
     */
    private function checkCompliance(Vtiger_Request $request) {
        $notesId = (int) $request->get('notesid');
        if (empty($notesId)) {
            throw new Exception('notesid is required');
        }
        return Documents_ComplianceChecker::check($notesId);
    }
}
