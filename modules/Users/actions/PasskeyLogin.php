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
 * Passkey のみのログイン（ログイン画面の「パスキーでログイン」から POST される）
 *
 * 受け取るもの: credential（navigator.credentials.get() の結果を JSON.stringify したもの）。
 * チャレンジは ChallengePasskeyAjax がセッションに置いたものを使い、一度で消す。
 * 失敗の理由は画面に出さず、ID/PW の失敗と同じ error=login へ戻す。
 */
class Users_PasskeyLogin_Action extends Vtiger_Action_Controller
{
    private const ERROR_URL = 'index.php?module=Users&parent=Settings&view=Login&error=login';

    /** resolveRedirect() で認証できたユーザー */
    protected ?int $authenticatedUserId = null;

    public function loginRequired(): bool
    {
        return false;
    }

    /**
     * @return array<mixed>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        return true;
    }

    public function process(Vtiger_Request $request): void
    {
        $redirect = $this->resolveRedirect($request);
        $userId = $this->authenticatedUserId;
        if ($redirect !== null || $userId === null) {
            header('Location: ' . ($redirect ?? self::ERROR_URL));
            exit;
        }

        $user = Users_Record_Model::getInstanceById($userId, 'Users');
        $username = self::stringValue($user->get('user_name'));
        // セッション ID の作り直し・ログイン状態の作成・ログイン履歴（login_type=passkey）・遷移まで行い、exit する
        Users_MultiFactorAuthentication_Helper::LoginProcess($userId, $username, 'passkey_only');
    }

    /**
     * 認証して、失敗ならリダイレクト先を返す。成功なら null を返し、authenticatedUserId に ID を入れる。
     */
    protected function resolveRedirect(Vtiger_Request $request): ?string
    {
        $this->authenticatedUserId = null;
        // フラグ OFF や credential なしでも、チャレンジは使い捨てにする
        $challenge = Users_MultiFactorAuthentication_Helper::consumeChallenge();

        if (!$this->isPasskeyOnlyLoginEnabled()) {
            $this->log('Passkey-only login rejected: ALLOW_PASSKEY_ONLY_LOGIN is disabled');

            return self::ERROR_URL;
        }
        // get() は JSON を配列に読み替えたうえで値を加工するため、生の文字列をそのまま検証に渡す
        $credential = $request->getRaw('credential');
        if (!is_string($credential) || $credential === '') {
            $this->log('Passkey-only login rejected: credential is missing');

            return self::ERROR_URL;
        }

        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $userId = $this->createService()->authenticate(
            $credential,
            $challenge,
            is_string($serverName) ? $serverName : ''
        );
        if ($userId === null) {
            return self::ERROR_URL;
        }
        $this->authenticatedUserId = $userId;

        return null;
    }

    protected function isPasskeyOnlyLoginEnabled(): bool
    {
        return Users_PasskeyLoginService_Model::isEnabled();
    }

    protected function createService(): Users_PasskeyLoginService_Model
    {
        return new Users_PasskeyLoginService_Model();
    }

    private static function stringValue(mixed $value): string
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
