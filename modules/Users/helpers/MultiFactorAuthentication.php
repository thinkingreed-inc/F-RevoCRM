<?php
use PragmaRX\Google2FA\Google2FA;

class Users_MultiFactorAuthentication_Helper {
    // ここのCompany name or identifierを変更すると、google authenticatorの登録名が変わります。
    // 変更しない場合は、'F-RevoCRM:「ユーザー名」
    public static function getQRcodeUrl($username, $totp_secret) {
        $google2fa = new Google2FA;
        return $google2fa->getQRCodeUrl(
                'F-RevoCRM', // Company name or identifier
                $username,                   // User's email or username
                $totp_secret
            );
    }

    public static function getSecret($type){
        try {
            if( $type == "totp" )
            {
                $google2fa = new Google2FA;
                $totp_secret = $google2fa->generateSecretKey();
                return $totp_secret;
            } 
        } catch(Exception $e) {
            global $log;
            $errMsg = "User credential error: "
                        .$e->getMessage().":".$e->getTraceAsString();
            $log->error($errMsg);
            return false;
        }
        
    }

    public static function totpVerifyKey($totp_secret, $totp_code)
    {
        $google2fa = new Google2FA;
        try {
            return $google2fa->verifyKey($totp_secret, $totp_code);
        } catch (Exception $e) {
            global $log;
            $errMsg = "TOTP verification error: "
                        .$e->getMessage().":".$e->getTraceAsString();
            $log->error($errMsg);
            return false;
        }
    }

    /**
     * ChallengePasskeyAjax が発行したチャレンジを取り出す。使い回せないよう、セッションからは消す。
     *
     * @return string|false チャレンジ（生のバイト列）。無ければ false
     */
    public static function consumeChallenge() {
        $challenge = $_SESSION['challenge'] ?? '';
        unset($_SESSION['challenge']);
        if (!is_string($challenge) || $challenge === '') {
            return false;
        }
        $decoded = base64_decode($challenge, true);
        return ($decoded === false || $decoded === '') ? false : $decoded;
    }

    // $type: totp or passkey
    public static function LoginProcess($userid, $username, $type){
        unset($_SESSION['force_2fa_registration']);
        unset($_SESSION['registration_userid']);

        session_regenerate_id(true); // to overcome session id reuse.

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
        $loginType = $type == 'totp' ? 'mfa_totp' : 'mfa_passkey';
        $moduleModel = Users_Module_Model::getInstance('Users');
        $moduleModel->saveLoginHistory($username, false, $loginType);
        //End
                    
        header ('Location: index.php?module=Users&parent=Settings&view=SystemSetup');
        exit();
    }
}
