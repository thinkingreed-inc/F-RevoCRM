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

namespace Tests\Unit\Users\Models;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\PasskeyTestAuthenticator;
use Users_PasskeyCredentialCodec_Model;

require_once dirname(__DIR__, 4) . '/modules/Users/models/PasskeyCredentialCodec.php';

/**
 * Passkey の保存形式（web-auth/webauthn-lib 3 系の jsonSerialize() と同じ形）と
 * 5 系の PublicKeyCredentialSource の相互変換
 */
final class PasskeyCredentialCodecTest extends TestCase
{
    private const V3_KEYS = [
        'publicKeyCredentialId', 'type', 'transports', 'attestationType', 'trustPath',
        'aaguid', 'credentialPublicKey', 'userHandle', 'counter', 'otherUI',
    ];

    public function test_3系の保存JSONを読み込める(): void
    {
        $authenticator = PasskeyTestAuthenticator::create('5');

        $source = Users_PasskeyCredentialCodec_Model::decode($authenticator->v3Json(7));

        self::assertSame($authenticator->credentialId(), $source->publicKeyCredentialId);
        self::assertSame('5', $source->userHandle);
        self::assertSame(7, $source->counter);
        self::assertSame($authenticator->coseKey(), $source->credentialPublicKey);
        self::assertSame('00000000-0000-0000-0000-000000000000', $source->aaguid->toRfc4122());
    }

    public function test_パディング付きのbase64も読み込める(): void
    {
        $authenticator = PasskeyTestAuthenticator::create('12');
        $data = json_decode($authenticator->v3Json(), true);
        self::assertIsArray($data);
        $data['userHandle'] = 'MTI='; // "12"

        $source = Users_PasskeyCredentialCodec_Model::decode((string) json_encode($data));

        self::assertSame('12', $source->userHandle);
    }

    public function test_encodeは3系と同じキー構成を出す(): void
    {
        $authenticator = PasskeyTestAuthenticator::create('5');

        $encoded = json_decode(Users_PasskeyCredentialCodec_Model::encode(
            Users_PasskeyCredentialCodec_Model::decode($authenticator->v3Json())
        ), true);

        self::assertIsArray($encoded);
        foreach (self::V3_KEYS as $key) {
            self::assertArrayHasKey($key, $encoded);
        }
        self::assertSame(['type' => 'Webauthn\\TrustPath\\EmptyTrustPath'], $encoded['trustPath']);
        self::assertSame('00000000-0000-0000-0000-000000000000', $encoded['aaguid']);
        self::assertStringNotContainsString('=', $encoded['publicKeyCredentialId']);
        self::assertSame(PasskeyTestAuthenticator::b64url($authenticator->credentialId()), $encoded['publicKeyCredentialId']);
    }

    public function test_decodeとencodeを往復しても値が変わらない(): void
    {
        $v3 = PasskeyTestAuthenticator::create('42')->v3Json(3);

        $roundTripped = json_decode(Users_PasskeyCredentialCodec_Model::encode(
            Users_PasskeyCredentialCodec_Model::decode(
                Users_PasskeyCredentialCodec_Model::encode(Users_PasskeyCredentialCodec_Model::decode($v3))
            )
        ), true);
        $original = json_decode($v3, true);

        self::assertIsArray($roundTripped);
        self::assertIsArray($original);
        foreach (self::V3_KEYS as $key) {
            self::assertSame($original[$key], $roundTripped[$key], $key);
        }
    }

    public function test_壊れたJSONは例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Users_PasskeyCredentialCodec_Model::decode('{');
    }

    public function test_必須キーの欠落は例外(): void
    {
        $data = json_decode(PasskeyTestAuthenticator::create('5')->v3Json(), true);
        self::assertIsArray($data);
        unset($data['credentialPublicKey']);

        $this->expectException(InvalidArgumentException::class);
        Users_PasskeyCredentialCodec_Model::decode((string) json_encode($data));
    }
}
