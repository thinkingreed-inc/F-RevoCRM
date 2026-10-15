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
 * MCP OAuth 認可の共通処理（Users_McpOAuthAuthorize_View と Users_McpOAuthConsent_Action の双方から使う）
 */
class Users_McpOAuth_Helper
{
    /** スコープに対応する権限行の文言キー */
    private static $scopePermissionLabels = array(
        'mcp' => array(
            'LBL_MCP_OAUTH_SCOPE_MCP_READ',
            'LBL_MCP_OAUTH_SCOPE_MCP_WRITE',
            'LBL_MCP_OAUTH_SCOPE_MCP_LIMIT',
        ),
    );

    /**
     * OAuthパラメータを取得する
     *
     * redirect_uri の厳格一致と PKCE の検証は値をそのまま比較する必要があるため、
     * purify を通さない getRaw で受ける。
     *
     * @return array<string,string>
     */
    public static function collectOAuthParams(Vtiger_Request $request): array {
        $scope = (string) $request->getRaw('scope', '');
        return array(
            'response_type'         => (string) $request->getRaw('response_type', ''),
            'client_id'             => (string) $request->getRaw('client_id', ''),
            'redirect_uri'          => (string) $request->getRaw('redirect_uri', ''),
            'code_challenge'        => (string) $request->getRaw('code_challenge', ''),
            'code_challenge_method' => (string) $request->getRaw('code_challenge_method', ''),
            'state'                 => (string) $request->getRaw('state', ''),
            'scope'                 => ($scope === '') ? 'mcp' : $scope,
        );
    }

    /**
     * OAuthパラメータを検証する
     *
     * @param array<string,string> $params collectOAuthParams() の戻り値
     * @return array 検証結果
     *               成功        : array('client' => クライアント行)
     *               エラーページ: array('message_key' => 文言キー)
     *               リダイレクト: array('error' => OAuthエラー, 'error_description' => 説明)
     */
    public static function validateOAuthParams(array $params): array {
        require_once 'include/Mcp/OAuthStorage.php';

        // ── client_id 検証（失敗時はリダイレクトせずエラーページ表示） ──
        if ($params['client_id'] === '') {
            return array('message_key' => 'LBL_MCP_OAUTH_ERR_CLIENT_ID_MISSING');
        }
        $client = Mcp_OAuthStorage::getClient($params['client_id']);
        if ($client === null) {
            return array('message_key' => 'LBL_MCP_OAUTH_ERR_CLIENT_ID_INVALID');
        }

        // ── redirect_uri 厳格一致検証（失敗時はリダイレクトせずエラーページ表示） ──
        if ($params['redirect_uri'] === '') {
            return array('message_key' => 'LBL_MCP_OAUTH_ERR_REDIRECT_URI_MISSING');
        }
        if (!in_array($params['redirect_uri'], $client['redirect_uris_array'], true)) {
            return array('message_key' => 'LBL_MCP_OAUTH_ERR_REDIRECT_URI_MISMATCH');
        }

        // ── ここからはredirect_uriが検証済みなのでエラーはリダイレクトで返す ──

        // response_type 検証
        if ($params['response_type'] !== 'code') {
            return array(
                'error'             => 'unsupported_response_type',
                'error_description' => 'response_type must be "code"',
            );
        }

        // PKCE 必須検証（OAuth 2.1）
        if ($params['code_challenge'] === '') {
            return array(
                'error'             => 'invalid_request',
                'error_description' => 'code_challenge is required (PKCE S256)',
            );
        }
        if ($params['code_challenge_method'] !== 'S256') {
            return array(
                'error'             => 'invalid_request',
                'error_description' => 'code_challenge_method must be "S256"',
            );
        }

        return array('client' => $client);
    }

    /**
     * 同意画面に表示するクライアント名
     */
    public static function getClientDisplayName(array $client): string {
        $clientName = (string) $client['client_name'];
        return ($clientName === '') ? 'MCP Client' : $clientName;
    }

    /**
     * スコープから権限行の文言キーを組む
     *
     * @return array<int,string>
     */
    public static function getScopePermissionLabels(string $scope): array {
        $labels = array();
        foreach (preg_split('/\s+/', trim($scope), -1, PREG_SPLIT_NO_EMPTY) as $scopeName) {
            if (isset(self::$scopePermissionLabels[$scopeName])) {
                $labels = array_merge($labels, self::$scopePermissionLabels[$scopeName]);
            }
        }
        return array_unique($labels);
    }

    /**
     * redirect_uri にOAuthエラーリダイレクト
     */
    public static function redirectWithError(string $redirectUri, string $error, string $description, string $state): void {
        $params = array(
            'error'             => $error,
            'error_description' => $description,
        );
        if ($state !== '') {
            $params['state'] = $state;
        }
        $location = self::buildRedirectUrl($redirectUri, $params);
        while (ob_get_level()) { ob_end_clean(); }
        header('Location: ' . $location);
    }

    /**
     * redirect_uri にパラメータを付けたURLを組む
     *
     * @param array<string,string> $params
     */
    public static function buildRedirectUrl(string $redirectUri, array $params): string {
        $separator = (strpos($redirectUri, '?') === false) ? '?' : '&';
        return $redirectUri . $separator . http_build_query($params);
    }

    /**
     * エラーページ表示（redirect_uriが検証できない場合用）
     */
    public static function renderErrorPage(string $messageKey): void {
        http_response_code(400);
        $viewer = Vtiger_Viewer::getInstance();
        $viewer->assign('MCP_OAUTH_ERROR', true);
        $viewer->assign('MCP_OAUTH_ERROR_MESSAGE_KEY', $messageKey);
        $viewer->view('McpOAuthAuthorize.tpl', 'Users');
    }
}
