<?php
class Users_SaveMultiFactorAuthenticationAjax_Action extends Vtiger_SaveAjax_Action {
    function loginRequired() {
        return false;
    }

    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

    public function checkPermission(Vtiger_Request $request) {
    }

    public function process(Vtiger_Request $request) {

        if( isset($_SESSION['registration_userid']) )
        {
            $userId = $_SESSION['registration_userid'];
			$currentUser = Users_Record_Model::getInstanceById($userId, 'Users');
        }
        else
        {
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $userId = $currentUser->getId();
        }
        
        $type = $request->get('type');
        $username = $request->get('username');

        $device_name = $request->get('device_name');
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if( $type == "totp") {
            $totp_code = $request->get('totp_code');
            $totp_secret = $request->get('secret');
            $verifyKey = Users_MultiFactorAuthentication_Helper::totpVerifyKey($totp_secret, $totp_code);
            if( $verifyKey === false )
            {
                $response->setError(vtranslate("LBL_TOTP_CODE_INCORRECT", 'Users'));
            } else {
                $result = $currentUser->totpRegisterUserCredential($totp_secret, $device_name);
                if($result === false)
                {
                    $response->setError(vtranslate("LBL_ERROR_ADD_USER_CREDENTIAL", 'Users'));
                }
                else 
                {
                    if( $_SESSION['force_2fa_registration'] == true ) {
                        $response->setResult(array('success' => true, 'login' => 'true', 'link' => "index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step3&userid=" . $userId));
                    } else {
                        // 通常の登録処理
                        $response->setResult(array('success' => true));
                    }
                }
            }
        } else {
            $challenge = $request->get('challenge');
            $credential = $request->get('credential');
            $verifyKey = Users_MultiFactorAuthentication_Helper::passkeyRegisterVerifyKey($challenge,$credential,$userId,$username);
            if($verifyKey === false)
            {
                $response->setError(vtranslate("LBL_FAILED_TO_PASSKEY_VERIFYKEY"), 'Users');
            } else {
                $result = $currentUser->passkeyRegisterUserCredential($device_name, $verifyKey);
                if($result === false)
                {
                    $response->setError(vtranslate("LBL_FAILED_TO_REGISTER_USER_AUTHENTICATION"), 'Users');
                } else {
                    if( $_SESSION['force_2fa_registration'] == true ) {
                        // 初回ログイン時はログイン処理を行う
                        $response->setResult(array('success' => true, 'login' => 'true', 'link' => "index.php?module=Users&view=ForceAddMultiFactorAuthentication&step=step3&userid=" . $userId));
                    } else {
                        // 通常の登録処理
                        $response->setResult(array('success' => true));
                    }
                }
            }
        }

        $response->emit();
    }
}
