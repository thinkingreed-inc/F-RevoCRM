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
        $userid = $_SESSION['multi_factor_auth_userid'];
        $username = $_SESSION['multi_factor_auth_username'];

        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUser = Users_Record_Model::getInstanceById($userid, 'Users');
        $viewer->assign('MODULE_MODEL',$moduleModel);

        $type = $request->get('type');
        $loginResult = false;
        // 認証種別ごとに認証処理
        if ($type == 'totp') {
            $totp_code = $request->get('totp_code');
            $secret = $currentUser->getTotpSecret();
            $loginResult = Users_MultiFactorAuthentication_Helper::totpVerifyKey($secret, $totp_code);
            $errorIncorrect = "LBL_TOTP_CODE_INCORRECT";
        } else {
            $credential = $request->get('credential');
            $challenge = $request->get('challenge');
            $loginResult = Users_MultiFactorAuthentication_Helper::passkeyLoginVerifyKey($challenge, $credential, $userid);
            $errorIncorrect = "LBL_PASSKEY_CODE_INCORRECT";
        }

		$lockResult = $currentUser->isLoginLockedByMFA();
        if ($loginResult !== false && !$lockResult) {
            // 試行回数のリセット
            $currentUser->resetSignatureCount();
            $currentUser->resetLockTime();
            // ログイン処理
            Users_MultiFactorAuthentication_Helper::LoginProcess($userid, $username);
            exit;
        } else {
            // 試行回数のカウントアップ
            $currentUser->countUpSignatureCount();
			$lockResult = $currentUser->isLoginLockedByMFA();
            // 試行回数の制限を超えた場合はエラー
            if ($lockResult) {
				$currentUser->setLockTime();
				$currentUser->resetSignatureCount();
                header('Location:index.php?module=Users&view=Login&error=userLocked');
                exit;
            }
			$companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
			$companyLogo = $companyDetails->getLogo();
			$viewer->assign('COMPANY_LOGO',$companyLogo);
			$viewer->assign("USERID", $userid);
			$viewer->assign("USERNAME", $username);
			$viewer->assign("TYPE", $type);
            $viewer->assign("ERROR", vtranslate($errorIncorrect, "Users"));
        	$viewer->view('MultiFactorAuth.tpl', $moduleName);
        }
    }

    function postProcess(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view('Footer.tpl', $moduleName);
    }
}