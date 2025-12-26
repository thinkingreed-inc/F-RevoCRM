<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
chdir(dirname(__FILE__)."/../../../");

include_once "include/utils/VtlibUtils.php";
include_once "include/utils/CommonUtils.php";
include_once "includes/Loader.php";
include_once 'includes/runtime/BaseModel.php';
include_once 'includes/runtime/Viewer.php';
include_once "includes/http/Request.php";
include_once "include/Webservices/Custom/ChangePassword.php";
include_once "include/Webservices/Utils.php";
include_once "includes/runtime/EntryPoint.php";

class Users_ForceChangePassword_Action extends Vtiger_Action_Controller {

    public function loginRequired() {
        return false;
    }

    public function checkPermission(Vtiger_Request $request) {
        return true;
    }

    public function process(Vtiger_Request $request) {

        $viewer = Vtiger_Viewer::getInstance();
        $userId   = Vtiger_Session::get('force_change_password_userid');
        $userName = Vtiger_Session::get('force_change_password_username');

        if (empty($userId) || empty($userName)) {
            header('Location: index.php?module=Users&view=Login');
            exit;
        }

        $newPassword     = $request->get('password');
        $confirmPassword = $request->get('confirmPassword');

        if (mb_strlen($newPassword) > 128 || mb_strlen($confirmPassword) > 128) {
            $viewer->assign('ERROR', true);
            $viewer->assign('USERNAME', $userName);
            $viewer->view('FPLogin.tpl', 'Users');
            return;
        }

        try {
            $adminUser = Users::getActiveAdminUser();
            $wsUserId  = vtws_getWebserviceEntityId('Users', $userId);

            vtws_changePassword(
                $wsUserId,
                '',
                $newPassword,
                $confirmPassword,
                $adminUser
            );

        } catch (Exception $e) {
            $viewer->assign('ERROR', true);
            $viewer->assign('USERNAME', $userName);
            $viewer->view('FPLogin.tpl', 'Users');
            return;
        }
        $viewer->assign('USERNAME', $userName);
        $viewer->assign('PASSWORD', $newPassword);
        Vtiger_Session::set('just_changed_password', true);
        $viewer->view('FPLogin.tpl', 'Users');
    }
}
