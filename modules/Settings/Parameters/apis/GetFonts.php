<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'vtlib/Vtiger/PDF/TCPDF.php';

/**
 * GetFonts API - PDF_DEFAULT_FONT に指定できるフォントの一覧を返す（thinkingreed-inc#1901）
 *
 * Response:
 *   { "fonts": ["cid0jp", "dejavusans", ...], "fallback": "ume-tgo4" }
 *   fonts は libraries/tcpdf/fonts/ にあるフォント（太字・斜体用のファイルは除く）
 *   fallback は PDF_DEFAULT_FONT が空のときに使うフォント
 */
class Settings_Parameters_GetFonts_Api extends Vtiger_Api_Controller {

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
        return $this->sendSuccess(array(
            'fonts' => Vtiger_PDF_TCPDF::getAvailableFonts(),
            'fallback' => Vtiger_PDF_TCPDF::FALLBACK_FONT,
        ));
    }
}
