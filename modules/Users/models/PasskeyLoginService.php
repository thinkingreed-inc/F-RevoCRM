<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Passkey のみのログイン（ID/PW を使わない）の認証
 *
 * ID/PW ログイン（Users_Login_Action）と同じ扱いにする。
 * - 無効・ロック中のユーザーは拒否する。失敗回数は数えない
 * - 持ち主が分かったあとの検証失敗は失敗回数を数え、上限ならロックする
 * - 失敗はすべてログイン履歴に残す（持ち主が分からなければユーザー名は空）
 * ログイン状態（セッション）はここでは作らない。
 */
class Users_PasskeyLoginService_Model
{
    private const FLAG_KEY = 'ALLOW_PASSKEY_ONLY_LOGIN';

    private readonly Users_PasskeyVerifier_Model $verifier;

    public function __construct(?Users_PasskeyVerifier_Model $verifier = null)
    {
        $this->verifier = $verifier ?? new Users_PasskeyVerifier_Model();
    }

    /**
     * システム変数 ALLOW_PASSKEY_ONLY_LOGIN が "true" のときだけ有効
     */
    public static function isEnabled(): bool
    {
        return Settings_Parameters_Record_Model::getParameterValue(self::FLAG_KEY, 'false') === 'true';
    }

    /**
     * @param string       $credentialJson navigator.credentials.get() の結果の JSON
     * @param string|false $challenge      セッションから取り出したチャレンジ（生のバイト列）。無ければ false
     * @return int|null 成功ならユーザー ID、失敗なら null
     */
    public function authenticate(string $credentialJson, string|false $challenge, string $rpId): ?int
    {
        $result = $this->verifier->verifyAssertion($credentialJson, $challenge, $rpId, null);
        $userId = $result->userId;
        if ($userId === null) {
            $this->fail('', "Passkey-only login rejected: reason={$result->reason}");

            return null;
        }

        $user = $this->loadUser($userId);
        if ($user === null) {
            $this->fail('', "Passkey-only login rejected: owner not found, userid=$userId, reason={$result->reason}");

            return null;
        }
        $username = $this->stringValue($user->get('user_name'));

        // 検証が通っていても、無効・ロック中のユーザーは入れない。ID/PW のロック中と同じく失敗回数は数えない
        if ($this->stringValue($user->get('status')) !== 'Active') {
            $this->fail($username, "Passkey-only login rejected: inactive user, userid=$userId, reason={$result->reason}");

            return null;
        }
        if ($user->isLocked()) {
            $this->fail($username, "Passkey-only login rejected: locked user, userid=$userId, reason={$result->reason}");

            return null;
        }

        if (!$result->verified) {
            // 持ち主が分かったあとの失敗は ID/PW のパスワード誤りと同じ扱い（Users_Login_Action と同じ順）
            $user->countUpLoginAttempt();
            if ($user->isLoginAttemptLocked()) {
                $user->setLockTime();
                $this->log("Account locked due to too many failed passkey attempts: userid=$userId");
            }
            $this->fail($username, "Passkey-only login failed: userid=$userId, reason={$result->reason}");

            return null;
        }

        $user->resetLoginAttempt();
        $user->resetLockTime();

        return $userId;
    }

    private function loadUser(int $userId): ?Users_Record_Model
    {
        try {
            $user = Users_Record_Model::getInstanceById($userId, 'Users');
        } catch (Throwable $e) {
            $this->log('Failed to load passkey owner: ' . get_class($e) . ': ' . $e->getMessage());

            return null;
        }
        if (!$user instanceof Users_Record_Model || $this->stringValue($user->get('user_name')) === '') {
            return null;
        }

        return $user;
    }

    private function fail(string $username, string $message): void
    {
        $this->log($message);
        $moduleModel = Users_Module_Model::getInstance('Users');
        if ($moduleModel instanceof Users_Module_Model) {
            $moduleModel->savePasskeyLoginErrorHistory($username);
        }
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function log(string $message): void
    {
        global $log;
        if ($log instanceof Logger) {
            $log->info($message);
        }
    }
}
