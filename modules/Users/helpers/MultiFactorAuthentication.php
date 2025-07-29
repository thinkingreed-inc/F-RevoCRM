<?php
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialSource;

use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Cose\Algorithm\Signature\ECDSA\ES256;

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

    public static function algorithmManager() {
        $algorithmManager = Manager::create()
            ->add(
                ES256::create(),
                ES256K::create(),
                ES384::create(),
                ES512::create(),

                RS256::create(),
                RS384::create(),
                RS512::create(),

                PS256::create(),
                PS384::create(),
                PS512::create(),

                Ed256::create(),
                Ed512::create(),
            )
        ;
        return $algorithmManager;
    }

    public static function passkeyLoginVerifyKey($challenge, $credential, $userid) {
        global $log, $adb;
        $sessionChallengeResult = self::challengeCompare($challenge);
        $userRecordModel = Users_Record_Model::getInstanceById($userid, 'Users');
        if( !$sessionChallengeResult ) {
            global $log;
            $log->error("Session challenge mismatch");
            return false;
        }

        try {
            $passkeyList = $userRecordModel->getPasskeyCredentialById();
            $passkey_credential_list = array();

            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            $serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();

            foreach ($passkeyList as $passkey_credential) {
                $passkey_credential = html_entity_decode($passkey_credential, ENT_QUOTES, 'UTF-8');
                $passkey_credential_list[] = $serializer->deserialize(
                    $passkey_credential,
                    PublicKeyCredentialSource::class,
                    'json');
            }

            $requestPublicKeyCredential = $serializer->deserialize(
                json_encode($credential), 
                PublicKeyCredential::class, 
                'json'
            );

            if (!$requestPublicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
                $log->error("Invalid credential response type for user: $userid");
                return false;
            }

            // localhostを許可オリジンとして設定
            // ここは実際の環境に合わせて変更する必要があります。
            // プルリクの時にコメントアウトされているので、必要に応じて変更してください。
            $csmFactory = new CeremonyStepManagerFactory();
            $csmFactory->setAlgorithmManager(self::algorithmManager());
            $csmFactory->setAllowedOrigins([
                'http://localhost',
            ]);
            $requestCSM = $csmFactory->requestCeremony();
            $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
                $requestCSM
            );
            
            $allowedCredentials = array_map(
                static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                    return $credential->getPublicKeyCredentialDescriptor();
                },
                $passkey_credential_list
            );

            $publicKeyCredentialRequestOptions =
                PublicKeyCredentialRequestOptions::create(
                    self::decodeChallenge($_SESSION['challenge']),
                    allowCredentials: $allowedCredentials
                );
            
            foreach ($passkey_credential_list as $credentialFromDb) {
                try {
                    $userHandle = property_exists($credentialFromDb, 'userHandle') ? $credentialFromDb->userHandle : null;
                    $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
                        $credentialFromDb,
                        $requestPublicKeyCredential->response,
                        $publicKeyCredentialRequestOptions,
                        $_SERVER['SERVER_NAME'],
                        $userHandle
                    );
                    if ($publicKeyCredentialSource) {
                        return $publicKeyCredentialSource;
                    }
                } catch (Exception $e) {
                    // passkeyは複数あるため、一致パターン以外は例外処理されてしまうため、例外を握りつぶす形で対応。
                }
            }
            // 全件失敗した場合のみfalseを返す
            return false;
        } catch (Exception $e) {
            $errMsg = "User credential error: "
                        .$e->getMessage().":".$e->getTraceAsString();
            $log->error($errMsg);
            return false; // 検証失敗
        }
    }

    public static function passkeyRegisterVerifyKey($challenge,$credential,$userid,$username) {
        $sessionChallengeResult = self::challengeCompare($challenge);
        if( !$sessionChallengeResult ) {
            global $log;
            $log->error("Session challenge mismatch");
            return false;
        }

        try {
            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            
            $serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();
            $cleanCredential = self::cleanBase64Padding($credential);
        
            // 受け取った認証情報をデシリアライズ
            $publicKeyCredential = $serializer->deserialize(
                json_encode($cleanCredential), 
                PublicKeyCredential::class, 
                'json'
            );

            if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
                global $log;
                $log->error("Invalid credential response type");
                return false;
            }

            $csmFactory = new CeremonyStepManagerFactory();
            $csmFactory->setAlgorithmManager(self::algorithmManager());
            // localhostを許可オリジンとして設定
            // ここは実際の環境に合わせて変更する必要があります。
            // プルリクの時にコメントアウトされているので、必要に応じて変更してください。
            $csmFactory->setAllowedOrigins([
                'http://localhost',
            ]);
            $creationCSM = $csmFactory->creationCeremony();
            $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
                $creationCSM
            );
            $rpEntity = new PublicKeyCredentialRpEntity($_SERVER['SERVER_NAME'], $_SERVER['SERVER_NAME']);
            $userEntity = new PublicKeyCredentialUserEntity(
                $username, 
                (string)$userid, 
                $username
            );
            $publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
                $rpEntity,
                $userEntity,
                self::decodeChallenge($_SESSION['challenge'])
            );

            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
                $publicKeyCredential->response,
                $publicKeyCredentialCreationOptions,
                $_SERVER['SERVER_NAME']
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

    private static function cleanBase64Padding($credential) {
        $cleanCredential = $credential;
        
        // rawIdのパディングを削除
        if (isset($cleanCredential['rawId'])) {
            $cleanCredential['rawId'] = rtrim($cleanCredential['rawId'], '=');
        }
        
        // responseのBase64データのパディングを削除
        if (isset($cleanCredential['response'])) {
            if (isset($cleanCredential['response']['clientDataJSON'])) {
                $cleanCredential['response']['clientDataJSON'] = rtrim($cleanCredential['response']['clientDataJSON'], '=');
            }
            if (isset($cleanCredential['response']['attestationObject'])) {
                $cleanCredential['response']['attestationObject'] = rtrim($cleanCredential['response']['attestationObject'], '=');
            }
        }
        
        return $cleanCredential;
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

    public static function LoginProcess($userid, $username){
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
        $moduleModel = Users_Module_Model::getInstance('Users');
        $moduleModel->saveLoginHistory($username);
        //End
                    
        header ('Location: index.php?module=Users&parent=Settings&view=SystemSetup');
        exit();
    }
}
