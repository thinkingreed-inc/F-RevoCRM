<?php
/**
 * EGroupware WebAuthn
 *
 * @link https://www.egroupware.org
 * @author Ralf Becker <rb-At-egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class Users_PublicKeyCredentialSourceRepository_Model implements PublicKeyCredentialSourceRepositoryInterface
{

    public function __construct()
    {
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $encodedId = $this->cleanBase64Padding($publicKeyCredentialId);
        foreach($this->read() as $data){
            $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
            $decodedData = json_decode($data, true);
            if($decodedData['publicKeyCredentialId'] === $encodedId) {
                return PublicKeyCredentialSource::createFromArray($decodedData);
            }
        }
        return null;
    }

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sources = [];
        foreach($this->read() as $data)
        {
            // データが空でないかチェック
            if (empty($data)) {
                continue;
            }
            
            $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
            
            // JSONデコード
            $decodedData = json_decode($data, true);
        
            // デコード結果が配列かどうかチェック
            if (!is_array($decodedData)) {
                global $log;
                $log->error("Invalid JSON data in credential: " . $data);
                continue;
            }

            try {
                $source = PublicKeyCredentialSource::createFromArray($decodedData);
                if ($source->getUserHandle() === $publicKeyCredentialUserEntity->getId())
                {
                    $sources[] = $source;
                }
            } catch (Exception $e) {
                global $log;
                $log->error("Error creating credential source: " . $e->getMessage());
                continue;
            }
        }
        return $sources;
    }

    // 未使用
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
    }

    private function read()
    {
        if(isset($_SESSION['registration_userid'] ))
        {
            $userid = $_SESSION['registration_userid'];
        } elseif (isset($_SESSION['multi_factor_auth_userid'])) {
            $userid = $_SESSION['multi_factor_auth_userid'];
        } else {
            return array();
        }

        $currentUserModel = Users_Record_Model::getInstanceById($userid, 'Users');
        $userCredentials = $currentUserModel->getUserCredential();
        $userCredentialList = array();
        foreach ($userCredentials as $credential) {
            if(!isset($credential['passkey_credential'])) {
                continue;
            }
            $userCredentialList[] = $credential['passkey_credential'];
        }
        
        return $userCredentialList;
    }

    private function cleanBase64Padding($credential) {
        $cleanCredential = $credential;
        $cleanCredential = base64_encode($cleanCredential);
        $cleanCredential = str_replace(['+', '/'], ['-', '_'], $cleanCredential);
        $cleanCredential = rtrim($cleanCredential, '=');

        return $cleanCredential;
    }
}