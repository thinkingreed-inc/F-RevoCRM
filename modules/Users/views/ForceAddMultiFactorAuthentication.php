<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_ForceAddMultiFactorAuthentication_View extends Vtiger_View_Controller {

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
        $type = $request->get('type');
        $moduleName = $request->getModule(false);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $username = $_SESSION['registration_username'];
        $userid = $_SESSION['registration_userid'];
        $viewer->assign('USERID', $userid);
        $viewer->assign('USERNAME', $username);
        $viewer->assign('HOSTNAME', $_SERVER['SERVER_NAME']);

        $companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
        $companyLogo = $companyDetails->getLogo();
        $viewer->assign('COMPANY_LOGO',$companyLogo);

        $viewer->assign('MODULE_MODEL',$moduleModel);
        $viewer->assign('LANGUAGE_STRINGS', Vtiger_Language_Handler::export('Users', 'jsLanguageStrings'));
        $step = $request->get('step');
         if ($step == 'step1') {
            $passkeyUrl = "window.location.href='index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step2&type=passkey'";
            $totpUrl = "window.location.href='index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step2&type=totp'";
            $viewer->assign('PASSKEY_URL', $passkeyUrl);
            $viewer->assign('TOTP_URL', $totpUrl);
            $viewer->view('ForceMultiFactorAuthenticationStep1.tpl', $moduleName);
        } elseif ($step == 'step2') {
            if( $type == "totp") { 
                $secret = Users_MultiFactorAuthentication_Helper::getSecret($type);
                $viewer->assign('SECRET', $secret);
                $viewer->assign('QRCODEURL', Users_MultiFactorAuthentication_Helper::getQRcodeUrl($username, $secret));
                $viewer->assign('BACK_URL', 'index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step1&type=totp');
            } else {
                $viewer->assign('BACK_URL', 'index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step1&type=passkey');
            }
            $viewer->assign('VIEW', 'ForceAddMultiFactorAuthentication');
            $viewer->assign('TYPE',$type);
            $viewer->view('ForceMultiFactorAuthenticationStep2.tpl', $moduleName);
        } elseif($step == 'step3') {
            $viewer->view('ForceMultiFactorAuthenticationStep3.tpl', $moduleName);
        } else {
            // 不正なステップの場合はエラーを表示
            $viewer->assign("ERROR", "不正なステップが指定されました。");
        }
    }

    function postProcess(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view('Footer.tpl', $moduleName);
    }
}