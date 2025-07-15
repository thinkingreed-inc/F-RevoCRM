<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_MultiFactorAuthLogin_View extends Vtiger_View_Controller {

    function loginRequired() {
        return false;
    }

    function checkPermission(Vtiger_Request $request) {
        return true;
    }

    function preProcess(Vtiger_Request $request, $display = true) {
        $viewer = $this->getViewer($request);
        $viewer->assign('PAGETITLE', $this->getPageTitle($request));
        $viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
        $viewer->assign('STYLES', $this->getHeaderCss($request));
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('VIEW', $request->get('view'));
        $viewer->assign('LANGUAGE_STRINGS', array());
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule(false);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUser = Users_Record_Model::getCurrentUserModel();

        $viewer->assign('userid',$_SESSION['registration_userid']);
        $viewer->assign('MODULE_MODEL',$moduleModel);

        $userid = $_SESSION['registration_userid'];
        $username = $_SESSION['registration_username'];
        $type = $request->get('type');
        $userCredentialHelper = new Users_MultiFactorAuthentication_Helper();
        $loginResult = false;
        // 認証種別ごとに認証処理
        if ($type == 'totp') {
            $totp_code = $request->get('totp_code');
            $secret = $userCredentialHelper->getTotpSecret($userid);
            $loginResult = $userCredentialHelper->totpVerifyKey($secret, $totp_code);
            $errorTryLimit = "LBL_TOTP_CODE_TRY_LIMIT_EXCEEDED";
            $errorIncorrect = "LBL_TOTP_CODE_INCORRECT";
        } else {
            $credential = $request->get('credential');
            $challenge = $request->get('challenge');
            $loginResult = $userCredentialHelper->passkeyLoginVerifyKey($challenge, $credential, $userid, $username);
            $errorTryLimit = "LBL_PASSKEY_TRY_LIMIT_EXCEEDED";
            $errorIncorrect = "LBL_PASSKEY_CODE_INCORRECT";
        }

        if ($loginResult !== false) {
            // 試行回数のリセット
            $currentUser->resetSignatureCount();
            // ログイン処理
            $userCredentialHelper->LoginProcess($userid, $username);
            exit;
        } else {
            // 試行回数のカウントアップ
            $currentUser->countUpSignatureCount($type);
            // 試行回数の制限を超えた場合はエラー
            if ($currentUser->isMultiFactorLoginLimitExceeded($type)) {
                $viewer->assign("ERROR", $errorTryLimit);
                $_SESSION['login_locked'] = true;
                header('Location:index.php?module=Users&view=Login');
                exit;
            }
            $viewer->assign("ERROR", $errorIncorrect);
            header('Location:index.php?module=Users&view=Login');
            exit;
        }

        if(isset($_SESSION['return_params'])) {
            $return_params = urldecode($_SESSION['return_params']);
            header("Location: index.php?$return_params");
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    }

    function postProcess(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view('Footer.tpl', $moduleName);
    }
}