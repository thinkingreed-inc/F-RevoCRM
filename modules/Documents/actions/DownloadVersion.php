<?php
/**
 * 電子帳簿保存法対応: 過去バージョンファイルのダウンロード
 *
 * vtiger_notes_file_versions テーブルの attachmentsid を使って
 * 指定バージョンのファイルをダウンロードする。
 */
class Documents_DownloadVersion_Action extends Vtiger_Action_Controller {

    public function requiresPermission(\Vtiger_Request $request) {
        $permissions = parent::requiresPermission($request);
        $permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
        return $permissions;
    }

    public function checkPermission(Vtiger_Request $request) {
        return parent::checkPermission($request);
    }

    public function process(Vtiger_Request $request) {
        $recordId = (int) $request->get('record');
        $versionNumber = (int) $request->get('version');

        if (empty($recordId) || empty($versionNumber)) {
            throw new Exception('record and version are required');
        }

        $db = PearDatabase::getInstance();

        // file_versions テーブルから対象バージョンの attachmentsid を取得
        $result = $db->pquery(
            "SELECT fv.attachmentsid, fv.notesid
            FROM vtiger_notes_file_versions fv
            WHERE fv.notesid = ? AND fv.version_number = ?",
            array($recordId, $versionNumber)
        );

        if ($result === false || $db->num_rows($result) === 0) {
            throw new Exception('Version not found');
        }

        $attachmentsId = (int) $db->query_result($result, 0, 'attachmentsid');
        if ($attachmentsId <= 0) {
            throw new Exception('Attachment not found for this version');
        }

        // vtiger_attachments からファイル情報を取得
        $attResult = $db->pquery(
            "SELECT * FROM vtiger_attachments WHERE attachmentsid = ?",
            array($attachmentsId)
        );

        if ($attResult === false || $db->num_rows($attResult) === 0) {
            throw new Exception('Attachment record not found');
        }

        $fileDetails = $db->query_result_rowdata($attResult, 0);
        $filePath = $fileDetails['path'];
        $fileName = html_entity_decode(decode_html($fileDetails['name']), ENT_QUOTES, vglobal('default_charset'));
        $storedName = $fileDetails['storedname'];
        $fileType = $fileDetails['type'];

        if (!empty($storedName)) {
            $savedFile = $attachmentsId . '_' . $storedName;
        } else {
            $savedFile = $attachmentsId . '_' . $fileDetails['name'];
        }

        $fullPath = $filePath . $savedFile;
        if (!file_exists($fullPath)) {
            throw new Exception('File not found on disk');
        }

        // 監査ログ記録
        require_once 'modules/Documents/utils/AuditLogger.php';
        Documents_AuditLogger::logDownload($recordId);

        // ファイル送出
        while (ob_get_level()) {
            ob_end_clean();
        }

        $fileSize = filesize($fullPath);
        $fileSize = $fileSize + ($fileSize % 1024);

        header("Content-type: " . $fileType);
        header("Pragma: public");
        header("Cache-Control: private");
        header("Content-Disposition: attachment; filename=\"" . $fileName . "\"");
        header("Content-Description: PHP Generated Data");
        header("Content-Encoding: none");

        echo fread(fopen($fullPath, "r"), $fileSize);
    }
}
