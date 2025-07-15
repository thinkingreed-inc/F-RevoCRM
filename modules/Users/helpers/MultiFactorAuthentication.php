<?php
require_once 'include/database/PearDatabase.php';

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
    protected $userid = null;
    protected $type = null;
    protected $device_name = null;
    protected $totp_secret = null;
    protected $passkey_credential_id = null;
    protected $signature_count = 0;
    protected $created_at = null;

    // Table name

    protected $table_name = 'vtiger_user_credentials';
    // ここのCompany name or identifierを変更すると、google authenticatorの登録名が変わります。
    // 変更しない場合は、F-revocrm:「ユーザー名」
    public function getQRcodeUrl($username, $totp_secret) {
        $google2fa = new Google2FA;
        return $google2fa->getQRCodeUrl(
                'F-revocrm', // Company name or identifier
                $username,                   // User's email or username
                $totp_secret
            );
    }

    public function getSecret($type){
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

    public function totpVerifyKey($totp_secret, $totp_code)
    {
        $google2fa = new Google2FA;
        return $google2fa->verifyKey($totp_secret, $totp_code);
    }

    // セッションチャンレンジが成功するかどうか比較するメソッド
    public function challengeCompare($challenge) {
        $session_challenge = $_SESSION['challenge'] ?? '';
        $decoded_challenge = $this->decodeChallenge($challenge);
        $decoded_session_challenge = $this->decodeChallenge($session_challenge);
        if (!$decoded_challenge || !$decoded_session_challenge) {
            global $log;
            $log->error("Challenge decode failed");
            return false;
        }

        // チャレンジトークンの検証
        if ($decoded_challenge !== $decoded_session_challenge) {
            global $log;
            $log->error("Challenge mismatch");
            return false;
        }
        return true;
    }

    public function algorithmManager() {
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

    public function passkeyLoginVerifyKey($challenge, $credential, $userid, $username) {
        $sessionChallengeResult = $this->challengeCompare($challenge);
        if( !$sessionChallengeResult ) {
            global $log;
            $log->error("Session challenge mismatch");
            return false;
        }

        try {
            $db = PearDatabase::getInstance();
            $query = "SELECT `passkey_credential`,`signature_count` FROM `vtiger_user_credentials` WHERE `userid` = ? AND `type` = 'passkey' AND `signature_count` < 5";
            $result = $db->pquery($query, array($userid));
            if ($db->num_rows($result) === 0) {
                global $log;
                $log->error("No passkey credential found for user: $userid");
                return false;
            }
            $passkey_credential_list = array();

            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            $serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();

            for($i=0;$i< $db->num_rows($result);$i++)
            {
                $row = $db->fetch_array($result, $i);
                $passkey_credential = html_entity_decode($row['passkey_credential']);
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
                global $log;
                $log->error("Invalid credential response type for user: $userid");
                return false;
            }

            // localhostを許可オリジンとして設定
            // ここは実際の環境に合わせて変更する必要があります。
            // プルリクの時にコメントアウトされているので、必要に応じて変更してください。
            $csmFactory = new CeremonyStepManagerFactory();
            $csmFactory->setAlgorithmManager($this->algorithmManager());
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
                    $this->decodeChallenge($_SESSION['challenge']),
                    allowCredentials: $allowedCredentials
                );
            
            foreach ($passkey_credential_list as $credentialFromDb) {
                $userHandle = property_exists($credentialFromDb, 'userHandle') ? $credentialFromDb->userHandle : null;
                $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
                    $credentialFromDb,
                    $requestPublicKeyCredential->response,
                    $publicKeyCredentialRequestOptions,
                    $_SERVER['SERVER_NAME'],
                    $userHandle
                );
                
                if( $publicKeyCredentialSource ) {
                    return $publicKeyCredentialSource; 
                }
            }

            return false;
        } catch (Exception $e) {
            global $log;
            $errMsg = "User credential error: "
                        .$e->getMessage().":".$e->getTraceAsString();
            $log->error($errMsg);
            return false; // 検証失敗
        }
    }

    public function passkeyRegisterVerifyKey($challenge,$credential,$userid,$username) {
        $sessionChallengeResult = $this->challengeCompare($challenge);
        if( !$sessionChallengeResult ) {
            global $log;
            $log->error("Session challenge mismatch");
            return false;
        }

        try {
            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            
            $serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();
            $cleanCredential = $this->cleanBase64Padding($credential);
        
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
            $csmFactory->setAlgorithmManager($this->algorithmManager());
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
                $this->decodeChallenge($_SESSION['challenge'])
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
            var_dump($errMsg);
            exit;
            return false; // 検証失敗
        }
    }

    private function cleanBase64Padding($credential) {
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

    private function decodeChallenge($challenge) {
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

    public function LoginProcess($userid, $username){
        unset($_SESSION['first_login']);
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
        $moduleModel->saveLoginHistory($user->column_fields['user_name']);
        //End
                    
        if(isset($_SESSION['return_params'])){
            $return_params = $_SESSION['return_params'];
        }

        header ('Location: index.php?module=Users&parent=Settings&view=SystemSetup');
        exit();
    }
}
