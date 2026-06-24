<?php
/**
 * 電子帳簿保存法対応: ファイルハッシュ計算・検証ユーティリティ
 *
 * ファイルの真正性を確保するためのSHA-256ハッシュ計算と検証機能を提供する。
 */
class Documents_FileHasher {

    const ALGORITHM = 'sha256';
    const ALGORITHM_LABEL = 'SHA-256';

    /**
     * ファイルパスからSHA-256ハッシュ値を計算する
     *
     * @param string $filePath ファイルの絶対パス
     * @return string|false ハッシュ値（64文字の16進文字列）、失敗時はfalse
     */
    public static function calculateHash($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }
        return hash_file(self::ALGORITHM, $filePath);
    }

    /**
     * ドキュメントIDからファイルのハッシュ値を計算する
     *
     * @param int $notesId ドキュメントID
     * @return string|false ハッシュ値、失敗時はfalse
     */
    public static function calculateHashForRecord($notesId) {
        $filePath = self::getFilePathForRecord($notesId);
        if ($filePath === false) {
            return false;
        }
        return self::calculateHash($filePath);
    }

    /**
     * ドキュメントIDに紐づくファイルのパスを取得する
     *
     * @param int $notesId ドキュメントID
     * @return string|false ファイルパス、取得失敗時はfalse
     */
    public static function getFilePathForRecord($notesId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_attachments.attachmentsid, vtiger_attachments.path, vtiger_attachments.storedname, vtiger_attachments.name
            FROM vtiger_attachments
            INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
            WHERE vtiger_seattachmentsrel.crmid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return false;
        }
        $row = $db->query_result_rowdata($result, 0);
        $storedName = !empty($row['storedname']) ? $row['storedname'] : $row['name'];
        $filePath = $row['path'] . $row['attachmentsid'] . '_' . $storedName;
        if (!file_exists($filePath)) {
            return false;
        }
        return $filePath;
    }

    /**
     * 保存済みハッシュ値と実ファイルのハッシュ値を照合する
     *
     * @param int $notesId ドキュメントID
     * @return array ['valid' => bool, 'stored_hash' => string|null, 'current_hash' => string|null, 'message' => string]
     */
    public static function verifyHash($notesId) {
        $db = PearDatabase::getInstance();

        // 保存済みハッシュ取得
        $result = $db->pquery(
            "SELECT file_hash FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return array('valid' => false, 'stored_hash' => null, 'current_hash' => null, 'message' => 'レコードが見つかりません');
        }
        $storedHash = $db->query_result($result, 0, 'file_hash');
        if (empty($storedHash)) {
            return array('valid' => false, 'stored_hash' => null, 'current_hash' => null, 'message' => 'ハッシュ値が未登録です');
        }

        // 現在のファイルハッシュ計算
        $currentHash = self::calculateHashForRecord($notesId);
        if ($currentHash === false) {
            return array('valid' => false, 'stored_hash' => $storedHash, 'current_hash' => null, 'message' => 'ファイルが見つかりません');
        }

        $isValid = ($storedHash === $currentHash);
        return array(
            'valid' => $isValid,
            'stored_hash' => $storedHash,
            'current_hash' => $currentHash,
            'message' => $isValid ? 'ハッシュ値が一致しました' : '改ざんの可能性: ハッシュ値が不一致です',
        );
    }

    /**
     * ドキュメントのハッシュ値をDBに保存する
     *
     * @param int $notesId ドキュメントID
     * @param string $hash ハッシュ値
     * @return bool 成功時true
     */
    public static function saveHash($notesId, $hash) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "UPDATE vtiger_notes SET file_hash = ?, file_hash_algorithm = ? WHERE notesid = ?",
            array($hash, self::ALGORITHM_LABEL, $notesId)
        );
        return $result !== false;
    }

    /**
     * ファイルアップロード時にハッシュを計算して保存する
     *
     * @param int $notesId ドキュメントID
     * @return string|false 計算されたハッシュ値、失敗時はfalse
     */
    public static function computeAndSave($notesId) {
        $hash = self::calculateHashForRecord($notesId);
        if ($hash === false) {
            return false;
        }
        self::saveHash($notesId, $hash);
        return $hash;
    }
}
