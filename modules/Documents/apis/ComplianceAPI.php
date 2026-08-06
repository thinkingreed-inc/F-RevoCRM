<?php
/**
 * 電子帳簿保存法対応 API
 *
 * 電帳法メタデータの保存、関連チェック、ハッシュ検証、監査ログ取得を提供する。
 */
require_once 'modules/Documents/utils/FileHasher.php';
require_once 'modules/Documents/utils/AuditLogger.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';

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

        $existsResult = $db->pquery("SELECT notesid FROM vtiger_notes WHERE notesid = ?", array($notesId));
        if ($existsResult === false || $db->num_rows($existsResult) === 0) {
            throw new Exception('Document not found');
        }

        // 変更前の値を取得（監査ログ用）
        $beforeSnapshot = Documents_AuditLogger::snapshotFields($notesId);

        // 更新カラム組み立て
        $updates = array();
        $params = array();

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
            }
        }

        if (!empty($updates)) {
            $params[] = $notesId;
            $result = $db->pquery(
                "UPDATE vtiger_notes SET " . implode(', ', $updates) . " WHERE notesid = ?",
                $params
            );
            if ($result === false) {
                throw new Exception(vtranslate('LBL_COMPLIANCE_SAVE_FAILED', 'Documents'));
            }
        }

        // 入力期限の自動計算（スキャナ保存で受領日がある場合。対象外になったら値を消す）
        $deadline = Documents_DeadlineCalculator::recalculate($notesId);

        // 監査ログ記録（項目値が実際に変わった場合のみ）
        $afterSnapshot = Documents_AuditLogger::snapshotFields($notesId);
        Documents_AuditLogger::logFieldChanges($notesId, $beforeSnapshot, $afterSnapshot);

        // 適合チェック実行
        $complianceResult = Documents_ComplianceChecker::check($notesId);

        return array(
            'success' => true,
            'notesid' => $notesId,
            'compliance_status' => $complianceResult['status'],
            'issues' => $complianceResult['issues'],
            'input_deadline' => $deadline['input_deadline'],
            'input_deadline_status' => $deadline['input_deadline_status'],
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
            throw new Exception(vtranslate('LBL_REPORT_GENERATION_FAILED', 'Documents'));
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
