<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\CredentialRecord;

/**
 * vtiger_user_credentials に保存した Passkey の読み書き
 *
 * 保存形式の変換は Users_PasskeyCredentialCodec_Model に任せる。セッションには依存しない。
 * credential ID は base64url で保存されている。過去にパディング付きで保存された行もあり得るため、
 * 検索はパディングあり・なしの両方で照合する。
 */
class Users_PublicKeyCredentialSourceRepository_Model
{
    private const ID_CONDITION = "type = 'passkey'"
        . " AND JSON_UNQUOTE(JSON_EXTRACT(passkey_credential, '$.publicKeyCredentialId')) IN (?, ?)";

    public function findOneByCredentialId(string $rawId): ?CredentialRecord
    {
        $row = $this->findRowByCredentialId($rawId);
        if ($row === null) {
            return null;
        }

        return $this->decodeOrNull($row['passkey_credential']);
    }

    public function findOwnerUserId(string $rawId): ?int
    {
        $row = $this->findRowByCredentialId($rawId);

        if ($row === null || !is_numeric($row['userid'])) {
            return null;
        }

        return (int) $row['userid'];
    }

    /**
     * @return list<CredentialRecord>
     */
    public function findAllForUserId(int $userId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT passkey_credential FROM vtiger_user_credentials WHERE userid = ? AND type = 'passkey'",
            [$userId]
        );
        $sources = [];
        while ($result !== false && ($row = $db->fetchByAssoc($result, -1, false))) {
            $source = $this->decodeOrNull($row['passkey_credential']);
            if ($source !== null) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /**
     * credential ID が一致する行の JSON を、保存形式（3 系形式）で書き換える
     */
    public function saveCredentialSource(CredentialRecord $source): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE vtiger_user_credentials SET passkey_credential = ? WHERE ' . self::ID_CONDITION,
            array_merge(
                [Users_PasskeyCredentialCodec_Model::encode($source)],
                $this->idCandidates($source->publicKeyCredentialId)
            )
        );
    }

    /**
     * @return array{userid: mixed, passkey_credential: mixed}|null
     */
    private function findRowByCredentialId(string $rawId): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT userid, passkey_credential FROM vtiger_user_credentials WHERE ' . self::ID_CONDITION . ' LIMIT 1',
            $this->idCandidates($rawId)
        );
        if ($result === false) {
            return null;
        }
        $row = $db->fetchByAssoc($result, -1, false);
        if (!is_array($row)) {
            return null;
        }

        return ['userid' => $row['userid'], 'passkey_credential' => $row['passkey_credential']];
    }

    /**
     * @return array{string, string} パディングなしとパディングありの base64url
     */
    private function idCandidates(string $rawId): array
    {
        $unpadded = Base64UrlSafe::encodeUnpadded($rawId);

        return [$unpadded, Base64UrlSafe::encode($rawId)];
    }

    private function decodeOrNull(mixed $json): ?CredentialRecord
    {
        try {
            return Users_PasskeyCredentialCodec_Model::decode(is_string($json) ? $json : '');
        } catch (InvalidArgumentException $e) {
            global $log;
            if ($log instanceof Logger) {
                $log->error('Invalid passkey credential data: ' . $e->getMessage());
            }

            return null;
        }
    }
}
