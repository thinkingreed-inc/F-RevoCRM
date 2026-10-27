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
use ReflectionProperty;
use Settings_Parameters_Record_Model;
use Tests\Support\PasskeyTestAuthenticator;
use Users_PasskeyLoginService_Model;

$projectRoot = dirname(__DIR__, 3);
require_once $projectRoot . '/modules/Users/models/PasskeyCredentialCodec.php';
require_once $projectRoot . '/modules/Users/models/PasskeyAssertionResult.php';
require_once $projectRoot . '/modules/Users/models/PasskeyCounterChecker.php';
require_once $projectRoot . '/modules/Users/models/PublicKeyCredentialSourceRepository.php';
require_once $projectRoot . '/modules/Users/models/PasskeyVerifier.php';
require_once $projectRoot . '/modules/Users/models/PasskeyLoginService.php';

/**
 * Passkey のみのログイン（Users_PasskeyLoginService_Model）
 *
 * テスト用 DB に最小限のユーザー行を作り、ID/PW ログインと同じ扱い
 * （無効・ロック中の拒否、失敗回数のカウントとロック、失敗履歴）になることを確かめる。
 */
final class PasskeyLoginServiceTest extends TestCase
{
    private const USER_ID = 990101;

    private const OTHER_ID = 990102;

    private const USER_NAME = 'passkey_only_test_user';

    private const OTHER_NAME = 'passkey_only_test_other';

    private const RP_ID = 'localhost';

    private const ORIGIN = 'http://localhost';

    private const PARAMETER_KEYS = ['USER_LOCK_COUNT', 'USER_LOCK_TIME', 'ALLOW_PASSKEY_ONLY_LOGIN'];

    private PearDatabase $db;

    /** @var array<string, string|null> テスト前の値。null は行がなかったことを表す */
    private array $originalParameters = [];

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
        // tests/bootstrap.php は Vtiger_Loader を外している。結合テストでは Users_Record_Model などを使うため戻す。
        if (class_exists('Vtiger_Loader', false)) {
            spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        }
        vimport('includes.runtime.BaseModel');
        vimport('includes.runtime.LanguageHandler');
    }

    protected function setUp(): void
    {
        global $adb;
        $this->db = PearDatabase::getInstance();
        $adb = $this->db;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        foreach (self::PARAMETER_KEYS as $key) {
            $this->originalParameters[$key] = $this->readParameter($key);
        }
        $this->cleanUp();
        $this->createUser(self::USER_ID, self::USER_NAME, 'Active');
        $this->createUser(self::OTHER_ID, self::OTHER_NAME, 'Active');
        $this->setParameter('USER_LOCK_COUNT', '2');
        $this->setParameter('USER_LOCK_TIME', '30');
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        foreach ($this->originalParameters as $key => $value) {
            if ($value === null) {
                $this->db->pquery('DELETE FROM vtiger_parameters WHERE `key` = ?', [$key]);
            } else {
                $this->db->pquery('UPDATE vtiger_parameters SET `value` = ? WHERE `key` = ?', [$value, $key]);
            }
        }
        $this->clearParameterCache();
    }

    public function test_成功するとユーザーIDを返し試行回数をリセット(): void
    {
        $authenticator = $this->register(self::USER_ID);
        $this->setLock(self::USER_ID, 1, null);
        $challenge = random_bytes(32);

        $userId = $this->service()->authenticate(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID
        );

        self::assertSame(self::USER_ID, $userId);
        self::assertSame(0, $this->loginAttempt(self::USER_ID));
        self::assertNull($this->lockTime(self::USER_ID));
        self::assertSame(0, $this->errorHistoryCount(self::USER_NAME));
    }

    public function test_無効ユーザーは拒否しカウントせず失敗履歴を残す(): void
    {
        $this->db->pquery('UPDATE vtiger_users SET status = ? WHERE id = ?', ['Inactive', self::USER_ID]);
        $authenticator = $this->register(self::USER_ID);
        $challenge = random_bytes(32);

        $userId = $this->service()->authenticate(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID
        );

        self::assertNull($userId);
        self::assertSame(0, $this->loginAttempt(self::USER_ID));
        self::assertSame(1, $this->errorHistoryCount(self::USER_NAME));
    }

    public function test_ロック中ユーザーは拒否しカウントしない(): void
    {
        $authenticator = $this->register(self::USER_ID);
        $this->setLock(self::USER_ID, 0, date('Y-m-d H:i:s'));
        $challenge = random_bytes(32);

        $userId = $this->service()->authenticate(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID
        );

        self::assertNull($userId);
        self::assertSame(0, $this->loginAttempt(self::USER_ID));
        self::assertSame(1, $this->errorHistoryCount(self::USER_NAME));
    }

    public function test_持ち主特定後の検証失敗はカウントし上限でロック(): void
    {
        $authenticator = $this->register(self::USER_ID);

        for ($i = 0; $i < 2; $i++) {
            $challenge = random_bytes(32);
            // userHandle を別ユーザーに偽装した要求
            $userId = $this->service()->authenticate(
                $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1, (string) self::OTHER_ID),
                $challenge,
                self::RP_ID
            );
            self::assertNull($userId);
        }

        self::assertSame(2, $this->loginAttempt(self::USER_ID));
        self::assertNotNull($this->lockTime(self::USER_ID));
        self::assertSame(2, $this->errorHistoryCount(self::USER_NAME));
        self::assertSame(0, $this->loginAttempt(self::OTHER_ID));
        self::assertSame(0, $this->errorHistoryCount(self::OTHER_NAME));

        // ロックされたあとは正しい要求でも入れない
        $challenge = random_bytes(32);
        self::assertNull($this->service()->authenticate(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 2),
            $challenge,
            self::RP_ID
        ));
        self::assertSame(2, $this->loginAttempt(self::USER_ID));
    }

    public function test_署名不正は持ち主の失敗回数に数える(): void
    {
        $authenticator = $this->register(self::USER_ID);
        $challenge = random_bytes(32);

        $userId = $this->service()->authenticate(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1, null, 0x05, true),
            $challenge,
            self::RP_ID
        );

        self::assertNull($userId);
        self::assertSame(1, $this->loginAttempt(self::USER_ID));
        self::assertSame(1, $this->errorHistoryCount(self::USER_NAME));
    }

    public function test_未知のcredentialはユーザー名空で失敗履歴(): void
    {
        $authenticator = PasskeyTestAuthenticator::create((string) self::USER_ID);
        $challenge = random_bytes(32);
        $before = $this->errorHistoryCount('');

        $userId = $this->service()->authenticate(
            $authenticator->assertionJson($challenge, self::RP_ID, self::ORIGIN, 1),
            $challenge,
            self::RP_ID
        );

        self::assertNull($userId);
        self::assertSame($before + 1, $this->errorHistoryCount(''));
        self::assertSame(0, $this->loginAttempt(self::USER_ID));
    }

    public function test_空や壊れたcredentialはnullで例外にならない(): void
    {
        $this->register(self::USER_ID);
        foreach (['', '{', '{"type":"public-key"}'] as $json) {
            self::assertNull($this->service()->authenticate($json, random_bytes(32), self::RP_ID), $json);
        }
        self::assertSame(0, $this->loginAttempt(self::USER_ID));
    }

    public function test_チャレンジがfalseなら拒否(): void
    {
        $authenticator = $this->register(self::USER_ID);
        $json = $authenticator->assertionJson(random_bytes(32), self::RP_ID, self::ORIGIN, 1);

        self::assertNull($this->service()->authenticate($json, false, self::RP_ID));
        self::assertSame(0, $this->loginAttempt(self::USER_ID));
    }

    public function test_フラグはtrueのときだけ有効(): void
    {
        $this->setParameter('ALLOW_PASSKEY_ONLY_LOGIN', 'false');
        self::assertFalse(Users_PasskeyLoginService_Model::isEnabled());

        $this->setParameter('ALLOW_PASSKEY_ONLY_LOGIN', 'true');
        self::assertTrue(Users_PasskeyLoginService_Model::isEnabled());

        $this->db->pquery('DELETE FROM vtiger_parameters WHERE `key` = ?', ['ALLOW_PASSKEY_ONLY_LOGIN']);
        $this->clearParameterCache();
        self::assertFalse(Users_PasskeyLoginService_Model::isEnabled());
    }

    private function service(): Users_PasskeyLoginService_Model
    {
        return new Users_PasskeyLoginService_Model();
    }

    private function register(int $userId): PasskeyTestAuthenticator
    {
        $authenticator = PasskeyTestAuthenticator::create((string) $userId);
        $this->db->pquery(
            'INSERT INTO vtiger_user_credentials (userid, type, device_name, passkey_credential) VALUES (?, ?, ?, ?)',
            [$userId, 'passkey', 'test device', $authenticator->v3Json()]
        );

        return $authenticator;
    }

    private function createUser(int $userId, string $userName, string $status): void
    {
        $this->db->pquery(
            'INSERT INTO vtiger_users (id, user_name, last_name, status, deleted, date_entered) VALUES (?, ?, ?, ?, 0, NOW())',
            [$userId, $userName, $userName, $status]
        );
    }

    private function setLock(int $userId, int $count, ?string $lockTime): void
    {
        $this->db->pquery(
            'INSERT INTO vtiger_user_lock (userid, signature_count, lock_time) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE signature_count = VALUES(signature_count), lock_time = VALUES(lock_time)',
            [$userId, $count, $lockTime]
        );
    }

    private function loginAttempt(int $userId): int
    {
        $result = $this->db->pquery('SELECT signature_count FROM vtiger_user_lock WHERE userid = ?', [$userId]);
        if ($this->db->num_rows($result) === 0) {
            return 0;
        }

        return (int) $this->db->query_result($result, 0, 'signature_count');
    }

    private function lockTime(int $userId): ?string
    {
        $result = $this->db->pquery('SELECT lock_time FROM vtiger_user_lock WHERE userid = ?', [$userId]);
        if ($this->db->num_rows($result) === 0) {
            return null;
        }
        $value = $this->db->query_result($result, 0, 'lock_time');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function errorHistoryCount(string $userName): int
    {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS cnt FROM vtiger_loginhistory
             WHERE user_name = ? AND status = 'Signed in error' AND login_type = 'passkey'",
            [$userName]
        );

        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    private function readParameter(string $key): ?string
    {
        $result = $this->db->pquery('SELECT `value` FROM vtiger_parameters WHERE `key` = ?', [$key]);
        if ($this->db->num_rows($result) === 0) {
            return null;
        }

        return (string) $this->db->query_result($result, 0, 'value');
    }

    private function setParameter(string $key, string $value): void
    {
        if ($this->readParameter($key) === null) {
            $this->db->pquery(
                'INSERT INTO vtiger_parameters (`key`, `value`, `description`) VALUES (?, ?, ?)',
                [$key, $value, 'test']
            );
        } else {
            $this->db->pquery('UPDATE vtiger_parameters SET `value` = ? WHERE `key` = ?', [$value, $key]);
        }
        $this->clearParameterCache();
    }

    private function clearParameterCache(): void
    {
        if (!class_exists(Settings_Parameters_Record_Model::class)) {
            return;
        }
        $cache = new ReflectionProperty(Settings_Parameters_Record_Model::class, 'cache');
        $cache->setValue(null, []);
    }

    private function cleanUp(): void
    {
        $ids = [self::USER_ID, self::OTHER_ID];
        $this->db->pquery('DELETE FROM vtiger_user_credentials WHERE userid IN (?, ?)', $ids);
        $this->db->pquery('DELETE FROM vtiger_user_lock WHERE userid IN (?, ?)', $ids);
        $this->db->pquery('DELETE FROM vtiger_users WHERE id IN (?, ?)', $ids);
        $this->db->pquery(
            "DELETE FROM vtiger_loginhistory WHERE user_name IN (?, ?, '') AND login_type = 'passkey'",
            [self::USER_NAME, self::OTHER_NAME]
        );
    }
}
