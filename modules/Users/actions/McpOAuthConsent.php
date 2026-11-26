<?php
/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * OAuth 2.1 同意処理
 * POST index.php?module=Users&action=McpOAuthConsent
 *
 *   decision=allow: 認可コード発行 → redirect_uri にリダイレクト
 *   decision=deny : redirect_uri に access_denied でリダイレクト
 *
 * 認可コードは現在ログイン中の利用者に対して発行する。
 * 認可専用のセッション変数は持たない（本体のログインセッションのみに依存する）。
 */
class Users_McpOAuthConsent_Action extends Vtiger_Action_Controller {

    /** 認可コードの有効期限（秒）。短命でよい（トークン交換で即座に消費される） */
    const AUTH_CODE_TTL_SECONDS = 600;

    /**
     * 権限チェックは requiresPermission/checkPermission を無効化する（権限行=空配列）。
     * ログイン済みユーザーは誰でも「自分のアカウント」として認可できればよく、
     * 追加の権限は必要ないため。
     */
    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

    /**
     * CSRF 対策: 書き込みアクセスを検証する（本体の csrf-magic に委ねる）
     */
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }

    /**
     * フォーム送信直後の真の302リダイレクトはブラウザCSPの
     * "form-action 'self'" に阻まれるため使わない。
     * 200応答のHTMLページを返し、ページ自身のmeta-refresh/JSで
     * redirect_uri（外部）へ遷移させる。
     */
    private static function redirectViaHtml(string $location): void {
        while (ob_get_level()) { ob_end_clean(); }
        $locEsc = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<meta http-equiv="refresh" content="0;url=' . $locEsc . '">'
            . '<script>location.replace(' . json_encode($location, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');</script></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:40px">'
            . vtranslate('LBL_MCP_OAUTH_REDIRECTING', 'Users') . '<br>'
            . '<a href="' . $locEsc . '">' . vtranslate('LBL_MCP_OAUTH_REDIRECT_MANUAL', 'Users') . '</a>'
            . '</body></html>';
    }

    /**
     * error/error_description(/state) を付けた redirect_uri へ、
     * 200+meta-refresh/JS で遷移させる（redirectViaHtml と同じ理由）。
     */
    private static function redirectWithErrorViaHtml(string $redirectUri, string $error, string $description, string $state): void {
        $params = array(
            'error'             => $error,
            'error_description' => $description,
        );
        if ($state !== '') {
            $params['state'] = $state;
        }
        $location = Users_McpOAuth_Helper::buildRedirectUrl($redirectUri, $params);
        self::redirectViaHtml($location);
    }

    /**
     * 流れ制御のみ: パラメータ検証 → decision（allow/deny/不正値）で振り分ける。
     * 各分岐の中身（コード発行・リダイレクト生成）は専用メソッドに持たせる。
     */
    public function process(Vtiger_Request $request) {
        require_once 'include/Mcp/OAuthHelper.php';
        require_once 'include/Mcp/OAuthStorage.php';

        // 同意フォームの hidden は利用者が書き換えられるため、認可画面と同じ検証をやり直す
        $params = Users_McpOAuth_Helper::collectOAuthParams($request);
        $validation = Users_McpOAuth_Helper::validateOAuthParams($params);

        if (isset($validation['message_key'])) {
            Users_McpOAuth_Helper::renderErrorPage($validation['message_key']);
            return;
        }
        if (isset($validation['error'])) {
            self::redirectWithErrorViaHtml($params['redirect_uri'], $validation['error'], $validation['error_description'], $params['state']);
            return;
        }

        $decision = $request->get('decision');

        if ($decision === 'deny') {
            $this->handleDeny($params);
            return;
        }

        if ($decision === 'allow') {
            $this->handleAllow($params);
            return;
        }

        Users_McpOAuth_Helper::renderErrorPage('LBL_MCP_OAUTH_ERR_INVALID_DECISION');
    }

    /**
     * 拒否: redirect_uri にエラーリダイレクト。
     * @param array $params collectOAuthParams() の戻り値
     */
    private function handleDeny(array $params): void {
        self::redirectWithErrorViaHtml($params['redirect_uri'], 'access_denied', 'The user denied the authorization request', $params['state']);
    }

    /**
     * 許可: 認可コードを DB へ発行し、redirect_uri へリダイレクトする。
     * @param array $params collectOAuthParams() の戻り値
     */
    private function handleAllow(array $params): void {
        $code = $this->issueAuthCode($params);
        if ($code === null) {
            Users_McpOAuth_Helper::renderErrorPage('LBL_MCP_OAUTH_ERR_CODE_ISSUE_FAILED');
            return;
        }

        $redirectParams = array('code' => $code);
        if ($params['state'] !== '') {
            $redirectParams['state'] = $params['state'];
        }
        $location = Users_McpOAuth_Helper::buildRedirectUrl($params['redirect_uri'], $redirectParams);
        self::redirectViaHtml($location);
    }

    /**
     * 認可コードを生成し DB へ保存する（DB アクセスのみを担う）。
     * @param array $params collectOAuthParams() の戻り値
     * @return string|null 生コード。DB エラー時は null
     */
    private function issueAuthCode(array $params): ?string {
        $userId    = (int) Users_Record_Model::getCurrentUserModel()->getId();
        $code      = mcp_oauth_generate_token();
        $expiresAt = date('Y-m-d H:i:s', time() + self::AUTH_CODE_TTL_SECONDS); // 10分後

        try {
            Mcp_OAuthStorage::createAuthCode(
                hash('sha256', $code),
                $params['client_id'],
                $userId,
                $params['code_challenge'],
                $params['redirect_uri'],
                $params['scope'],
                $expiresAt
            );
        } catch (\Exception $e) {
            error_log('[MCP OAuth Authorize] DB error: ' . $e->getMessage());
            return null;
        }

        return $code;
    }
}
