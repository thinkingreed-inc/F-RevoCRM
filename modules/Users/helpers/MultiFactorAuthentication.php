<?php
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialRpEntity;
use Symfony\Component\HttpFoundation\Request;
use Webauthn\PublicKeyCredentialDescriptor; 

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

    // セッションチャンレンジが成功するかどうか比較するメソッド
    public static function challengeCompare($challenge) {
        global $log;
        $session_challenge = $_SESSION['challenge'] ?? '';
        $decoded_challenge = self::decodeChallenge($challenge);
        $decoded_session_challenge = self::decodeChallenge($session_challenge);
        if (!$decoded_challenge || !$decoded_session_challenge) {
            $log->error("Challenge decode failed");
            return false;
        }

        // チャレンジトークンの検証
        if ($decoded_challenge !== $decoded_session_challenge) {
            $log->error("Challenge mismatch");
            return false;
        }
        return true;
    }

    public static function passkeyLoginVerifyKey($challenge, $credential, $userid, $username) {
        global $log, $adb;
        $sessionChallengeResult = self::challengeCompare($challenge);
        $userRecordModel = Users_Record_Model::getInstanceById($userid, 'Users');
        if( !$sessionChallengeResult ) {
            global $log;
            $log->error("Session challenge mismatch");
            return false;
        }

        try {
            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            
            $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
            $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
            $publicKeyCredential = $publicKeyCredentialLoader->load(json_encode($credential));

            $authenticatorAssertionResponse = $publicKeyCredential->getResponse();
            if (!$authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
                $log->error("Invalid credential response type");
                return false;
            }

            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory, // ServerRequestFactory
                $psr17Factory, // UriFactory
                $psr17Factory, // UploadedFileFactory
                $psr17Factory  // StreamFactory
            );

            $publicKeyCredentialSourceRepository = new Users_PublicKeyCredentialSourceRepository_Model();
            $userEntity = new PublicKeyCredentialUserEntity(
                $username,
                $userid,
                $username
            );

            $serverRequest = $creator->fromGlobals();

            $excludeCredentials  = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
            if (!is_array($excludeCredentials)) {
                $excludeCredentials = [];
            }

            // PublicKeyCredentialDescriptor配列に変換
            $excludeCredentialDescriptors = [];
            foreach ($excludeCredentials as $credential) {
                if ($credential instanceof PublicKeyCredentialSource) {
                    $excludeCredentialDescriptors[] = new PublicKeyCredentialDescriptor(
                        'public-key',
                        $credential->getPublicKeyCredentialId()
                    );
                }
            }

            $coseAlgorithmManager = new Manager();
            $coseAlgorithmManager->add(new ECDSA\ES256());
            $coseAlgorithmManager->add(new RSA\RS256());

            $publicKeyCredentialSourceRepository = new Users_PublicKeyCredentialSourceRepository_Model();
            $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
                $publicKeyCredentialSourceRepository,
                new TokenBindingNotSupportedHandler(),
                new ExtensionOutputCheckerHandler(),
                $coseAlgorithmManager,
                null,
                null
            );
            
            $publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
                self::decodeChallenge($challenge),
                60000,
                $_SERVER['SERVER_NAME'], 
                $excludeCredentialDescriptors,
                null,
                null
            );

            $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
                $publicKeyCredential->getRawId(),
                $authenticatorAssertionResponse,
                $publicKeyCredentialRequestOptions,
                $serverRequest,
                null,
                ['localhost']
            );
        } catch (Exception $e) {
            $errMsg = "User credential error: "
                        .$e->getMessage().":".$e->getTraceAsString();
            $log->error($errMsg);
            return false; // 検証失敗
        }
    }

    public static function passkeyRegisterVerifyKey($challenge,$credential,$userid,$username) {
        global $log;
        $sessionChallengeResult = self::challengeCompare($challenge);

        if( !$sessionChallengeResult ) {
            global $log;
            $log->error("Session challenge mismatch");
            return false;
        }

        try {
            // The manager will receive data to load and select the appropriate 
            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            
            $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
            $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
            $publicKeyCredential = $publicKeyCredentialLoader->load(json_encode($credential));

            $authenticatorAttestationResponse = $publicKeyCredential->getResponse();
            if (!$authenticatorAttestationResponse instanceof Webauthn\AuthenticatorAttestationResponse) {
                $log->error("Invalid credential response type");
                return false;
            }

            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory, // ServerRequestFactory
                $psr17Factory, // UriFactory
                $psr17Factory, // UploadedFileFactory
                $psr17Factory  // StreamFactory
            );

            $publicKeyCredentialSourceRepository = new Users_PublicKeyCredentialSourceRepository_Model();
            $userEntity = new PublicKeyCredentialUserEntity(
                $username,
                $userid,
                $username
            );

            $serverRequest = $creator->fromGlobals();
            $relyingrpEntityParty = new PublicKeyCredentialRpEntity(
                'F-RevoCRM', // The application name
                $_SERVER['SERVER_NAME']
            );
            $tokenBindingHandler = new TokenBindingNotSupportedHandler();
            $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();
            $pubKeyCredParams = 
            [
                new PublicKeyCredentialParameters("public-key", -7), // ES256
                new PublicKeyCredentialParameters("public-key", -8), // EdDSA
                new PublicKeyCredentialParameters("public-key", -257), // RS256
            ];
            $excludeCredentials  = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
            if (!is_array($excludeCredentials)) {
                $excludeCredentials = [];
            }

            // PublicKeyCredentialDescriptor配列に変換
            $excludeCredentialDescriptors = [];
            foreach ($excludeCredentials as $credential) {
                if ($credential instanceof PublicKeyCredentialSource) {
                    $excludeCredentialDescriptors[] = new PublicKeyCredentialDescriptor(
                        'public-key',
                        $credential->getPublicKeyCredentialId()
                    );
                }
            }
            $authenticatorSelection = new AuthenticatorSelectionCriteria(
                AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED
            );
                
            $publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
                $relyingrpEntityParty,
                $userEntity,
                self::decodeChallenge($challenge),
                $pubKeyCredParams,
                60000,
                $excludeCredentialDescriptors,
                $authenticatorSelection
            );

            $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
                $attestationStatementSupportManager,
                $publicKeyCredentialSourceRepository,
                $tokenBindingHandler,
                $extensionOutputCheckerHandler
            );

            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions,
                $serverRequest,
                ['localhost']
            );

            return $publicKeyCredentialSource; // 検証成功
        } catch (Exception $e) {
            global $log;
            $errMsg = "User credential error: "
                        .$e->getMessage().":".$e->getTraceAsString();
            $log->error($errMsg);
            return false; // 検証失敗
        }
    }

    private static function decodeChallenge($challenge) {
        try {
            if (empty($challenge)) {
                return false;
            }

            if (is_string($challenge)) {
                // カンマ区切りの数値文字列の場合
                if (strpos($challenge, ',') !== false) {
                    $numbers = explode(',', $challenge);
                    $binaryData = '';
                    foreach ($numbers as $num) {
                        $binaryData .= chr(intval(trim($num)));
                    }
                    return $binaryData;
                } else {
                    // Base64文字列の場合
                    return base64_decode($challenge);
                }
            } elseif (is_array($challenge)) {
                // 配列の場合（数値の配列）
                $binaryData = '';
                foreach ($challenge as $num) {
                    $binaryData .= chr(intval($num));
                }
                return $binaryData;
            } else {
                // その他の型の場合
                global $log;
                $log->error("Invalid challenge type: " . gettype($challenge));
                return false;
            }
        } catch (Exception $e) {
            global $log;
            $log->error("Challenge decode error: " . $e->getMessage());
            return false;
        }
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
