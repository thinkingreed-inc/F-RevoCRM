<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Users_Login_Action extends Vtiger_Action_Controller {

	function loginRequired() {
		return false;
	}

	function checkPermission(Vtiger_Request $request) {
		return true;
	} 

	function process(Vtiger_Request $request) {
		$username = $request->get('username');
		$password = $request->getRaw('password');

		$user = CRMEntity::getInstance('Users');
		$user->column_fields['user_name'] = $username;

		if ($user->doLogin($password)) {
            $userid = $user->retrieve_user_id($username);
            $currentUser = Users_Record_Model::getInstanceById($userid, 'Users');

            if ($currentUser->isLocked()) {
                header ('Location: index.php?module=Users&parent=Settings&view=Login&error=userLocked');
                exit;
            }
            //メールサーバー設定確認
            $moduleModel = Users_Module_Model::getInstance('Users');
            $hidePasswordFields = $moduleModel->hasMailServerConfigured();

            if (Vtiger_Session::get('just_changed_password')) {
                Vtiger_Session::set('just_changed_password', false);
            } elseif ($currentUser->isNeverLogged()) {
                if ($hidePasswordFields) {
                   header('Location: index.php?module=Users&parent=Settings&view=Login&error=initialLoginNotAllowed');
                   exit;
                }
                Vtiger_Session::set('force_change_password_userid', $userid);
                Vtiger_Session::set('force_change_password_username', $username);
                header('Location: index.php?module=Users&view=ForceChangePassword');
                exit;
            }
            session_regenerate_id(true);
            $userCredentialsData = $currentUser->getUserCredential();

            $forceMultiFactorAuth = Settings_Parameters_Record_Model::getParameterValue("FORCE_MULTI_FACTOR_AUTH", "false");
            if( $forceMultiFactorAuth === "true" && empty($userCredentialsData))
            {
                Vtiger_Session::set('force_2fa_registration', true);
                Vtiger_Session::set('registration_userid', $userid);
                Vtiger_Session::set('registration_username', $username);
                // 2要素認証の設定ページへリダイレクト
                header ('Location: index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step1');
            } else if( !empty($userCredentialsData) ) {
				Vtiger_Session::set('multi_factor_auth_userid', $userid);
				Vtiger_Session::set('multi_factor_auth_username', $username);
                // 2要素認証の認証ページへリダイレクト
                header ('Location: index.php?module=Users&view=MultiFactorAuth');
            } else {
                Vtiger_Session::set('AUTHUSERID', $userid);

                // For Backward compatability
                // TODO Remove when switch-to-old look is not needed
                $_SESSION['authenticated_user_id'] = $userid;
                $_SESSION['app_unique_key'] = vglobal('application_unique_key');
                $_SESSION['authenticated_user_language'] = vglobal('default_language');

                //Enabled session variable for KCFINDER 
                $_SESSION['KCFINDER'] = array(); 
                $_SESSION['KCFINDER']['disabled'] = false; 
                $_SESSION['KCFINDER']['uploadURL'] = "test/upload"; 
                $_SESSION['KCFINDER']['uploadDir'] = "../test/upload";
                $deniedExts = implode(" ", vglobal('upload_badext'));
                $_SESSION['KCFINDER']['deniedExts'] = $deniedExts;
                // End

                //Track the login History
                $moduleModel = Users_Module_Model::getInstance('Users');
                $moduleModel->saveLoginHistory($user->column_fields['user_name']);
                //End
                $moduleModel->updateHasInitialLogin($userid);
                header ('Location: index.php?module=Users&parent=Settings&view=SystemSetup');
            }
			exit();
		} else {
			//Track the login History
			$moduleModel = Users_Module_Model::getInstance('Users');
			$moduleModel->saveLoginErrorHistory($username);
			//End
			header ('Location: index.php?module=Users&parent=Settings&view=Login&error=login');
			exit;
		}
	}

}
