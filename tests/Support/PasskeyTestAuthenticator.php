<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

declare(strict_types=1);

namespace Tests\Support;

use CBOR\ByteStringObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;

/**
 * テスト用の Passkey 認証器（P-256 / ES256）
 *
 * ブラウザと認証器の代わりに、保存用 JSON（web-auth/webauthn-lib 3 系の jsonSerialize() と同じ形）と、
 * navigator.credentials.get() / create() の結果を JSON.stringify したものと同じ形の JSON を作る。
 */
final class PasskeyTestAuthenticator
{
    private function __construct(
        private readonly OpenSSLAsymmetricKey $privateKey,
        private readonly string $credentialId,
        private readonly string $userHandle,
        private readonly string $coseKey,
    ) {
    }

    /**
     * @param string|null $credentialId 省略すると 32 バイトの乱数。重複した credential ID の検証用に指定できる
     */
    public static function create(string $userHandle, ?string $credentialId = null): self
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($key === false) {
            throw new RuntimeException('EC 鍵を生成できません');
        }
        $details = openssl_pkey_get_details($key);
        if ($details === false || !isset($details['ec']['x'], $details['ec']['y'])) {
            throw new RuntimeException('EC 公開鍵を取り出せません');
        }
        $x = str_pad((string) $details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad((string) $details['ec']['y'], 32, "\0", STR_PAD_LEFT);

        $coseKey = (string) MapObject::create([
            MapItem::create(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2)),
            MapItem::create(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7)),
            MapItem::create(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1)),
            MapItem::create(NegativeIntegerObject::create(-2), ByteStringObject::create($x)),
            MapItem::create(NegativeIntegerObject::create(-3), ByteStringObject::create($y)),
        ]);

        return new self($key, $credentialId ?? random_bytes(32), $userHandle, $coseKey);
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }

    public function userHandle(): string
    {
        return $this->userHandle;
    }

    public function coseKey(): string
    {
        return $this->coseKey;
    }

    /**
     * 3 系の PublicKeyCredentialSource::jsonSerialize() と同じ形の保存用 JSON
     */
    public function v3Json(int $counter = 0, string $aaguid = '00000000-0000-0000-0000-000000000000'): string
    {
        return (string) json_encode([
            'publicKeyCredentialId' => self::b64url($this->credentialId),
            'type' => 'public-key',
            'transports' => [],
            'attestationType' => 'none',
            'trustPath' => ['type' => 'Webauthn\\TrustPath\\EmptyTrustPath'],
            'aaguid' => $aaguid,
            'credentialPublicKey' => self::b64url($this->coseKey),
            'userHandle' => self::b64url($this->userHandle),
            'counter' => $counter,
            'otherUI' => null,
        ]);
    }

    /**
     * navigator.credentials.get() の結果を JSON.stringify(credential) したものと同じ形
     */
    public function assertionJson(string $challenge, string $rpId, string $origin, int $counter, ?string $userHandle = null): string
    {
        $authenticatorData = hash('sha256', $rpId, true) . chr(0x05) . pack('N', $counter);
        $clientDataJson = (string) json_encode([
            'type' => 'webauthn.get',
            'challenge' => self::b64url($challenge),
            'origin' => $origin,
        ], JSON_UNESCAPED_SLASHES);
        $signature = '';
        if (!openssl_sign($authenticatorData . hash('sha256', $clientDataJson, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('署名できません');
        }

        return (string) json_encode([
            'id' => self::b64url($this->credentialId),
            'rawId' => self::b64url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'authenticatorData' => self::b64url($authenticatorData),
                'clientDataJSON' => self::b64url($clientDataJson),
                'signature' => self::b64url((string) $signature),
                'userHandle' => self::b64url($userHandle ?? $this->userHandle),
            ],
            'clientExtensionResults' => new \stdClass(),
        ]);
    }

    /**
     * 登録画面の JS（MultiFactorAuthentication.js）が送る形の登録結果 JSON。
     * rawId・clientDataJSON・attestationObject は JS の btoa() と同じ標準 base64（パディングあり）。
     */
    public function registrationJson(
        string $challenge,
        string $rpId,
        string $origin,
        string $aaguid = '00000000-0000-0000-0000-000000000000'
    ): string {
        $authData = hash('sha256', $rpId, true)
            . chr(0x45) // UP | UV | AT
            . pack('N', 0)
            . (string) hex2bin(str_replace('-', '', $aaguid))
            . pack('n', strlen($this->credentialId))
            . $this->credentialId
            . $this->coseKey;
        $attestationObject = (string) MapObject::create([
            MapItem::create(TextStringObject::create('fmt'), TextStringObject::create('none')),
            MapItem::create(TextStringObject::create('attStmt'), MapObject::create()),
            MapItem::create(TextStringObject::create('authData'), ByteStringObject::create($authData)),
        ]);
        $clientDataJson = (string) json_encode([
            'type' => 'webauthn.create',
            'challenge' => self::b64url($challenge),
            'origin' => $origin,
        ], JSON_UNESCAPED_SLASHES);

        return (string) json_encode([
            'id' => self::b64url($this->credentialId),
            'type' => 'public-key',
            'rawId' => base64_encode($this->credentialId),
            'response' => [
                'clientDataJSON' => base64_encode($clientDataJson),
                'attestationObject' => base64_encode($attestationObject),
            ],
            'clientExtensionResults' => new \stdClass(),
        ]);
    }

    public static function b64url(string $data): string
    {
        return Base64UrlSafe::encodeUnpadded($data);
    }
}
