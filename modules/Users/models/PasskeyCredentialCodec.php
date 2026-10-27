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
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\TrustPath\TrustPath;

/**
 * vtiger_user_credentials.passkey_credential の JSON と CredentialRecord（3 系の PublicKeyCredentialSource 相当）の相互変換
 *
 * 保存形式は web-auth/webauthn-lib 3 系の PublicKeyCredentialSource::jsonSerialize() と同じ形にそろえる。
 * DB の中身を 1 種類の形式に保ち、3 系に戻しても既存の Passkey が使えるようにするため。
 * 5 系で増えた backupEligible / backupStatus / uvInitialized は追加のキーとして持つ（3 系は読み飛ばす）。
 */
class Users_PasskeyCredentialCodec_Model
{
    private const REQUIRED_KEYS = [
        'publicKeyCredentialId', 'type', 'transports', 'attestationType', 'trustPath',
        'aaguid', 'credentialPublicKey', 'userHandle', 'counter',
    ];

    private const EMPTY_TRUST_PATH = 'Webauthn\\TrustPath\\EmptyTrustPath';

    private const CERTIFICATE_TRUST_PATH = 'Webauthn\\TrustPath\\CertificateTrustPath';

    /**
     * @throws InvalidArgumentException 壊れた JSON、必須キーの欠落、値の型が合わないとき
     */
    public static function decode(string $json): CredentialRecord
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Passkey の保存データが JSON ではありません');
        }
        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Passkey の保存データに {$key} がありません");
            }
        }

        $transports = $data['transports'];
        if (!is_array($transports)) {
            throw new InvalidArgumentException('transports が配列ではありません');
        }
        $otherUI = $data['otherUI'] ?? null;
        if ($otherUI !== null && !is_array($otherUI)) {
            throw new InvalidArgumentException('otherUI が配列ではありません');
        }
        if (!is_int($data['counter'])) {
            throw new InvalidArgumentException('counter が整数ではありません');
        }
        $aaguid = self::string($data, 'aaguid');
        if (!Uuid::isValid($aaguid)) {
            throw new InvalidArgumentException('aaguid が UUID ではありません');
        }

        /** @var list<string> $transports */
        /** @var array<string, mixed>|null $otherUI */
        return new CredentialRecord(
            self::decodeBase64Url(self::string($data, 'publicKeyCredentialId')),
            self::string($data, 'type'),
            $transports,
            self::string($data, 'attestationType'),
            self::decodeTrustPath($data['trustPath']),
            Uuid::fromString($aaguid),
            self::decodeBase64Url(self::string($data, 'credentialPublicKey')),
            self::decodeBase64Url(self::string($data, 'userHandle')),
            $data['counter'],
            $otherUI,
            self::nullableBool($data, 'backupEligible'),
            self::nullableBool($data, 'backupStatus'),
            self::nullableBool($data, 'uvInitialized'),
        );
    }

    public static function encode(CredentialRecord $source): string
    {
        $data = [
            'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId),
            'type' => $source->type,
            'transports' => $source->transports,
            'attestationType' => $source->attestationType,
            'trustPath' => self::encodeTrustPath($source->trustPath),
            'aaguid' => $source->aaguid->toRfc4122(),
            'credentialPublicKey' => Base64UrlSafe::encodeUnpadded($source->credentialPublicKey),
            'userHandle' => Base64UrlSafe::encodeUnpadded($source->userHandle),
            'counter' => $source->counter,
            'otherUI' => $source->otherUI,
            'backupEligible' => $source->backupEligible,
            'backupStatus' => $source->backupStatus,
            'uvInitialized' => $source->uvInitialized,
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * base64url（パディングなし）を基本に、パディング付き・標準 base64 の揺れも受け入れる
     */
    public static function decodeBase64Url(string $value): string
    {
        $normalized = rtrim(strtr($value, '+/', '-_'), '=');
        try {
            return Base64UrlSafe::decodeNoPadding($normalized);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('base64url として読めない値です', 0, $e);
        }
    }

    private static function decodeTrustPath(mixed $trustPath): TrustPath
    {
        if (!is_array($trustPath)) {
            throw new InvalidArgumentException('trustPath が配列ではありません');
        }
        $type = $trustPath['type'] ?? null;
        if ($type === self::EMPTY_TRUST_PATH) {
            return EmptyTrustPath::create();
        }
        if ($type === self::CERTIFICATE_TRUST_PATH) {
            $x5c = $trustPath['x5c'] ?? null;
            if (!is_array($x5c)) {
                throw new InvalidArgumentException('trustPath.x5c が配列ではありません');
            }
            /** @var list<string> $x5c */
            return CertificateTrustPath::create($x5c);
        }

        throw new InvalidArgumentException('未対応の trustPath です');
    }

    /**
     * @return array<string, mixed>
     */
    private static function encodeTrustPath(TrustPath $trustPath): array
    {
        if ($trustPath instanceof CertificateTrustPath) {
            return ['type' => self::CERTIFICATE_TRUST_PATH, 'x5c' => $trustPath->certificates];
        }
        if ($trustPath instanceof EmptyTrustPath) {
            return ['type' => self::EMPTY_TRUST_PATH];
        }

        throw new InvalidArgumentException('未対応の trustPath です: ' . get_class($trustPath));
    }

    /**
     * @param array<mixed> $data
     */
    private static function string(array $data, string $key): string
    {
        if (!is_string($data[$key])) {
            throw new InvalidArgumentException("{$key} が文字列ではありません");
        }

        return $data[$key];
    }

    /**
     * @param array<mixed> $data
     */
    private static function nullableBool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }
}
