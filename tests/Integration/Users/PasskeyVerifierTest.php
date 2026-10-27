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

namespace Tests\Integration\Users;

use PearDatabase;
use PHPUnit\Framework\TestCase;
use Tests\Support\PasskeyTestAuthenticator;
use Users_MultiFactorAuthentication_Helper;
use Users_PasskeyVerifier_Model;
use Webauthn\CredentialRecord;

$projectRoot = dirname(__DIR__, 3);
require_once $projectRoot . '/modules/Users/models/PasskeyCredentialCodec.php';
require_once $projectRoot . '/modules/Users/models/PasskeyAssertionResult.php';
require_once $projectRoot . '/modules/Users/models/PasskeyCounterChecker.php';
require_once $projectRoot . '/modules/Users/models/PublicKeyCredentialSourceRepository.php';
require_once $projectRoot . '/modules/Users/models/PasskeyVerifier.php';
require_once $projectRoot . '/modules/Users/helpers/MultiFactorAuthentication.php';

/**
 * Passkey の検証（Users_PasskeyVerifier_Model）
 *
 * vtiger_user_credentials に 3 系形式の Passkey を置き、テスト用の認証器で作った assertion を検証する。
 * テストで使うユーザー ID は実在しない値（OWNER_ID / OTHER_ID）にし、行は setUp / tearDown で消す。
 */
final class PasskeyVerifierTest extends TestCase
{
    private const OWNER_ID = 990001;

    private const OTHER_ID = 990002;

    private const RP_ID = 'localhost';

    private const ORIGIN = 'http://localhost';

    private PearDatabase $db;

    private Users_PasskeyVerifier_Model $verifier;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery("SHOW TABLES LIKE 'vtiger_user_credentials'", []);
            $exists = $result !== false && $db->num_rows($result) === 1;
        } catch (\Throwable $e) {
            $exists = false;
        }
        if (!$exists) {
            self::markTestSkipped('テスト用DBに接続できないか vtiger_user_credentials がないため飛ばします');
        }
    }

    protected function setUp(): void
    {
        $this->db = PearDatabase::getInstance();
        $this->deleteRows();
        $this->verifier = new Users_PasskeyVerifier_Model();
    }

    protected function tearDown(): void
    {
        $this->deleteRows();
        unset($_SESSION['challenge']);
    }

    public function test_3系形式で保存したPasskeyで2段階目が通る(): void
    {
        $authenticator = $this->register(self::OWNER_ID);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID,
            self::OWNER_ID
        );

        self::assertTrue($result->verified, $result->reason);
        self::assertSame(self::OWNER_ID, $result->userId);
    }

    public function test_ユーザー指定なしで全ユーザーから持ち主を特定できる(): void
    {
        $this->register(self::OTHER_ID);
        $authenticator = $this->register(self::OWNER_ID);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID,
            null
        );

        self::assertTrue($result->verified, $result->reason);
        self::assertSame(self::OWNER_ID, $result->userId);
    }

    public function test_別ユーザーのPasskeyは2段階目で拒否(): void
    {
        $authenticator = $this->register(self::OTHER_ID);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID,
            self::OWNER_ID
        );

        self::assertFalse($result->verified);
    }

    public function test_userHandleが持ち主と違えば拒否し持ち主を返す(): void
    {
        $authenticator = $this->register(self::OWNER_ID);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1, (string) self::OTHER_ID),
            $challenge,
            self::RP_ID,
            null
        );

        self::assertFalse($result->verified);
        self::assertSame(self::OWNER_ID, $result->userId);
    }

    public function test_同じcredentialIDが別ユーザーにもあれば拒否し持ち主を特定しない(): void
    {
        $victim = $this->register(self::OWNER_ID);
        // 攻撃者が被害者と同じ credential ID・自分の鍵で登録した行（過去データの重複を想定）
        $attacker = PasskeyTestAuthenticator::create((string) self::OTHER_ID, $victim->credentialId());
        $this->insert(self::OTHER_ID, $attacker->v3Json());
        $challenge = random_bytes(32);
        $json = $attacker->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1);

        $passkeyOnly = $this->verifier->verifyAssertion($json, $challenge, self::RP_ID, null);
        self::assertFalse($passkeyOnly->verified);
        self::assertNull($passkeyOnly->userId);
        self::assertSame('duplicate_credential', $passkeyOnly->reason);

        self::assertFalse($this->verifier->verifyAssertion($json, $challenge, self::RP_ID, self::OTHER_ID)->verified);
        $victimJson = $victim->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1);
        self::assertFalse($this->verifier->verifyAssertion($victimJson, $challenge, self::RP_ID, self::OWNER_ID)->verified);
    }

    public function test_保存されたuserHandleが行の持ち主と違えば拒否(): void
    {
        // 行の持ち主は OWNER_ID だが、保存された credential の userHandle は OTHER_ID
        $authenticator = PasskeyTestAuthenticator::create((string) self::OTHER_ID);
        $this->insert(self::OWNER_ID, $authenticator->v3Json());
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID,
            null
        );

        self::assertFalse($result->verified);
        self::assertSame(self::OWNER_ID, $result->userId);
        self::assertSame('owner_mismatch', $result->reason);
    }

    public function test_チャレンジ不一致とfalseは拒否(): void
    {
        $authenticator = $this->register(self::OWNER_ID);
        $json = $authenticator->assertionJson(random_bytes(32), self::RP_ID, self::ORIGIN, 1);

        self::assertFalse($this->verifier->verifyAssertion($json, random_bytes(32), self::RP_ID, self::OWNER_ID)->verified);
        self::assertFalse($this->verifier->verifyAssertion($json, false, self::RP_ID, self::OWNER_ID)->verified);
    }

    public function test_未知のcredentialは持ち主なし(): void
    {
        $authenticator = PasskeyTestAuthenticator::create((string) self::OWNER_ID);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID,
            null
        );

        self::assertFalse($result->verified);
        self::assertNull($result->userId);
    }

    public function test_壊れたcredentialは例外にせず拒否(): void
    {
        $result = $this->verifier->verifyAssertion('{"id":', random_bytes(32), self::RP_ID, null);

        self::assertFalse($result->verified);
        self::assertNull($result->userId);
    }

    public function test_成功するとカウンタがDBに保存される(): void
    {
        $authenticator = $this->register(self::OWNER_ID);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 5),
            $challenge,
            self::RP_ID,
            self::OWNER_ID
        );

        self::assertTrue($result->verified, $result->reason);
        $stored = $this->storedCredential(self::OWNER_ID);
        self::assertSame(5, $stored['counter']);
        foreach (
            ['publicKeyCredentialId', 'type', 'transports', 'attestationType', 'trustPath',
                'aaguid', 'credentialPublicKey', 'userHandle', 'counter', 'otherUI'] as $key
        ) {
            self::assertArrayHasKey($key, $stored);
        }
        $original = json_decode($authenticator->v3Json(), true);
        self::assertIsArray($original);
        self::assertSame($original['publicKeyCredentialId'], $stored['publicKeyCredentialId']);
        self::assertSame($original['credentialPublicKey'], $stored['credentialPublicKey']);
        self::assertSame($original['userHandle'], $stored['userHandle']);
    }

    public function test_カウンタが巻き戻っても通る(): void
    {
        $authenticator = $this->register(self::OWNER_ID, 10);
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyAssertion(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 3),
            $challenge,
            self::RP_ID,
            self::OWNER_ID
        );

        self::assertTrue($result->verified, $result->reason);
    }

    public function test_登録画面のJSが送る形の登録結果を検証できる(): void
    {
        $authenticator = PasskeyTestAuthenticator::create((string) self::OWNER_ID);
        $challenge = random_bytes(32);

        $source = $this->verifier->verifyRegistration(
            $authenticator->registrationJson($challenge, self::RP_ID, self::ORIGIN),
            $challenge,
            self::RP_ID,
            self::OWNER_ID,
            'passkey_test_user'
        );

        self::assertInstanceOf(CredentialRecord::class, $source);
        self::assertSame($authenticator->credentialId(), $source->publicKeyCredentialId);
        self::assertSame((string) self::OWNER_ID, $source->userHandle);
        self::assertSame($authenticator->coseKey(), $source->credentialPublicKey);
    }

    public function test_他のユーザーが登録済みのcredentialIDは登録できない(): void
    {
        $existing = $this->register(self::OTHER_ID);
        $authenticator = PasskeyTestAuthenticator::create((string) self::OWNER_ID, $existing->credentialId());
        $challenge = random_bytes(32);

        $result = $this->verifier->verifyRegistration(
            $authenticator->registrationJson($challenge, self::RP_ID, self::ORIGIN),
            $challenge,
            self::RP_ID,
            self::OWNER_ID,
            'passkey_test_user'
        );

        self::assertFalse($result);
    }

    public function test_登録もチャレンジ不一致とfalseは拒否(): void
    {
        $authenticator = PasskeyTestAuthenticator::create((string) self::OWNER_ID);
        $json = $authenticator->registrationJson(random_bytes(32), self::RP_ID, self::ORIGIN);

        self::assertFalse($this->verifier->verifyRegistration($json, random_bytes(32), self::RP_ID, self::OWNER_ID, 'u'));
        self::assertFalse($this->verifier->verifyRegistration($json, false, self::RP_ID, self::OWNER_ID, 'u'));
    }

    public function test_consumeChallengeは一度しか使えない(): void
    {
        $_SESSION['challenge'] = base64_encode('challenge-bytes');

        self::assertSame('challenge-bytes', Users_MultiFactorAuthentication_Helper::consumeChallenge());
        self::assertFalse(Users_MultiFactorAuthentication_Helper::consumeChallenge());
    }

    private function register(int $userId, int $counter = 0): PasskeyTestAuthenticator
    {
        $authenticator = PasskeyTestAuthenticator::create((string) $userId);
        $this->insert($userId, $authenticator->v3Json($counter));

        return $authenticator;
    }

    private function insert(int $userId, string $credentialJson): void
    {
        $this->db->pquery(
            'INSERT INTO vtiger_user_credentials (userid, type, device_name, passkey_credential) VALUES (?, ?, ?, ?)',
            [$userId, 'passkey', 'test device', $credentialJson]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function storedCredential(int $userId): array
    {
        $result = $this->db->pquery(
            'SELECT passkey_credential FROM vtiger_user_credentials WHERE userid = ?',
            [$userId]
        );
        $row = $this->db->fetchByAssoc($result, -1, false);
        self::assertIsArray($row);
        $data = json_decode((string) $row['passkey_credential'], true);
        self::assertIsArray($data);

        return $data;
    }

    private function deleteRows(): void
    {
        $this->db->pquery(
            'DELETE FROM vtiger_user_credentials WHERE userid IN (?, ?)',
            [self::OWNER_ID, self::OTHER_ID]
        );
    }
}
