<?php

/**
 * 自動採番項目の振り直しで変わった番号（旧 → 新）を CSV で出す（管理者のみ）
 *
 * ?module=Vtiger&parent=Settings&action=AutoNumberRenumberLog&jobid=12
 * CSV は JSON ではないため、apis ではなく設定の Action に置く。
 */
class Settings_Vtiger_AutoNumberRenumberLog_Action extends Settings_Vtiger_Index_Action
{
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateReadAccess();
    }

    public function process(Vtiger_Request $request): void
    {
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        require_once 'modules/Vtiger/utils/AutoNumberBackfill.php';
        require_once 'modules/Vtiger/utils/AutoNumberValue.php';

        $jobId = (int) $request->get('jobid');
        $job = Vtiger_AutoNumberBackfill::getJob($jobId);
        if ($job === null || $job['mode'] !== Vtiger_AutoNumberBackfill::MODE_RENUMBER) {
            throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="autonumber_renumber_' . $jobId . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        self::writeCsv($out, $jobId);
        fclose($out);
    }

    /**
     * 振り直しの記録を CSV にして書き出す
     *
     * @param resource $out 書き出し先
     */
    public static function writeCsv($out, int $jobId): void
    {
        require_once 'modules/Vtiger/utils/AutoNumberValue.php';
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT l.crmid, e.label, l.old_number, l.new_number FROM ' . Vtiger_AutoNumber::JOB_LOG_TABLE . ' l
             LEFT JOIN vtiger_crmentity e ON e.crmid = l.crmid WHERE l.jobid = ? ORDER BY l.new_number, l.crmid',
            [$jobId]
        );
        fwrite($out, "\xEF\xBB\xBF"); // Excel で文字化けしないように BOM を付ける
        fputcsv($out, [
            vtranslate('LBL_AUTONUMBER_RENUMBER_LOG_RECORD_ID', 'Vtiger'),
            vtranslate('LBL_AUTONUMBER_RENUMBER_LOG_LABEL', 'Vtiger'),
            vtranslate('LBL_AUTONUMBER_RENUMBER_LOG_OLD', 'Vtiger'),
            vtranslate('LBL_AUTONUMBER_RENUMBER_LOG_NEW', 'Vtiger'),
        ], ',', '"', '\\');
        while ($result !== false && is_array($row = $db->fetchByAssoc($result, -1, false))) {
            fputcsv($out, [
                Vtiger_AutoNumberValue::string($row['crmid']),
                self::safeCell(Vtiger_AutoNumberValue::string($row['label'] ?? '')),
                self::safeCell(Vtiger_AutoNumberValue::string($row['old_number'])),
                self::safeCell(Vtiger_AutoNumberValue::string($row['new_number'])),
            ], ',', '"', '\\');
        }
    }

    /**
     * 表計算ソフトで式として実行されないよう、先頭が式の記号のセルは引用符を付ける
     */
    private static function safeCell(string $value): string
    {
        return $value !== '' && strpbrk($value[0], "=+-@\t\r") !== false ? "'" . $value : $value;
    }
}
