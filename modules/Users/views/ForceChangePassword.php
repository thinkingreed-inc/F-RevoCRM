<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Users_ForceChangePassword_View extends Vtiger_View_Controller {

    function loginRequired() {
        return false;
    }

    function checkPermission(Vtiger_Request $request) {
        return true;
    }
    //パスワード変更画面表示
    public function process(Vtiger_Request $request) {
        global $site_URL;
        if (
            empty($_SESSION['force_change_password_userid']) ||
            empty($_SESSION['force_change_password_username'])
        ) {
            header('Location: index.php?module=Users&view=Login');
            exit;
        }

        $userId   = $_SESSION['force_change_password_userid'];
        $userName = $_SESSION['force_change_password_username'];

        $viewer = $this->getViewer($request);
        $companyModel = Vtiger_CompanyDetails_Model::getInstanceById();
        $companyName  = $companyModel->get('organizationname');
        $logoDetails  = $companyModel->getLogo();
        $logoTitle    = $logoDetails->get('title');
        $logoName     = $logoDetails->get('imagename');

        $moduleName = 'Users';
        $viewer->assign('LOGOURL', $site_URL . '/logo/' . $logoName);
        $viewer->assign('TITLE', $logoTitle);
        $viewer->assign('COMPANYNAME', $companyName);
        $viewer->assign('USERID', $userId);
        $viewer->assign('USERNAME', $userName);
        $viewer->assign('MODULE', $moduleName);
		$viewer->assign('LANGUAGE_STRINGS', Vtiger_Language_Handler::export('Users', 'jsLanguageStrings'));
        $viewer->view('ForceChangePassword.tpl', $moduleName);
    }

	function postProcess(Vtiger_Request $request) {
    }
	function preProcess(Vtiger_Request $request, $display=true) {
    }
}