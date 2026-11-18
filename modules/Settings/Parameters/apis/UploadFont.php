<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'vtlib/Vtiger/PDF/FontInstaller.php';

/**
 * UploadFont API - TrueType フォントをアップロードして PDF で使えるようにする（thinkingreed-inc#1901）
 *
 * Parameters（multipart/form-data の POST）:
 *   - font: TrueType フォント（.ttf）
 *
 * Response:
 *   { "font": "lineseedjp" }  追加したフォント名（PDF_DEFAULT_FONT に指定する値）
 *   エラー時は Vtiger_Response が { "success": false, "error": {...} } を返す
 *
 * Note:
 *   - 変換は Vtiger_PDF_FontInstaller が行い、libraries/tcpdf/fonts/ に置く
 *   - 同名のフォントがある場合は断る（既存のフォントを置き換えられないようにするため）
 *   - アップロードされたファイルは変換にだけ使い、公開ディレクトリには残さない
 */
class Settings_Parameters_UploadFont_Api extends Vtiger_Api_Controller {

    function loginRequired() {
        return true;
    }

    /**
     * 管理者のみ
     */
    function checkPermission(Vtiger_Request $request) {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request) {
        // post_max_size を超えると PHP が POST の中身（CSRF トークンを含む）を捨てるため、
        // そのままでは CSRF の確認で失敗し、利用者には原因がわからない。先にサイズで判定して理由を返す。
        if ($this->exceedsPostMaxSize()) {
            throw new ApiBadRequestException($this->translate('LBL_FONT_UPLOAD_TOO_LARGE_SERVER'));
        }

        // CSRFトークン検証
        $request->validateWriteAccess();

        $file = $this->getUploadedFile();
        $error = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            // PHP の upload_max_filesize / post_max_size を超えている
            throw new ApiBadRequestException($this->translate('LBL_FONT_UPLOAD_TOO_LARGE_SERVER'));
        }
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new ApiBadRequestException($this->translate('LBL_FONT_UPLOAD_NO_FILE'));
        }
        if ($error !== UPLOAD_ERR_OK || !$this->isUploadedFile($file['tmp_name'])) {
            throw new ApiBadRequestException($this->translate('LBL_FONT_UPLOAD_FAILED'));
        }

        try {
            $fontName = $this->createInstaller()->install($file['tmp_name'], (string)$file['name']);
        } catch (Vtiger_PDF_FontInstallException $e) {
            throw new ApiBadRequestException($this->translate($e->getMessage()));
        }

        return $this->sendSuccess(array('font' => $fontName));
    }

    /**
     * アップロードされたファイルの情報（$_FILES['font']）
     * 複数ファイルの形式（name[]）で送られた場合は受け付けない
     * @return array
     */
    protected function getUploadedFile() {
        $file = isset($_FILES['font']) ? $_FILES['font'] : array();
        if (!is_array($file) || (isset($file['name']) && !is_string($file['name']))) {
            return array('error' => UPLOAD_ERR_NO_FILE);
        }
        return $file;
    }

    /**
     * リクエストの大きさが post_max_size を超えているか
     * @return boolean
     */
    protected function exceedsPostMaxSize() {
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        $limit = self::toBytes((string)ini_get('post_max_size'));
        return $limit > 0 && $contentLength > $limit;
    }

    /**
     * php.ini のサイズ表記（例: 8M）をバイト数にする
     * @param string $value
     * @return int
     */
    public static function toBytes($value) {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $number = (int)$value;
        switch (strtolower(substr($value, -1))) {
            case 'g':
                $number *= 1024;
                // no break
            case 'm':
                $number *= 1024;
                // no break
            case 'k':
                $number *= 1024;
        }
        return $number;
    }

    /**
     * HTTP でアップロードされたファイルか
     * @param string $path
     * @return boolean
     */
    protected function isUploadedFile($path) {
        return is_string($path) && is_uploaded_file($path);
    }

    /**
     * @return Vtiger_PDF_FontInstaller
     */
    protected function createInstaller() {
        return new Vtiger_PDF_FontInstaller();
    }

    /**
     * @param string $key
     * @return string
     */
    private function translate($key) {
        return vtranslate($key, 'Settings:Parameters');
    }
}
