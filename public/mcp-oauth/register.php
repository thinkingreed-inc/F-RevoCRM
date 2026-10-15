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
 * RFC 7591 動的クライアント登録 (DCR) エンドポイント
 * POST /mcp-oauth/register.php
 *
 * claude.aiが自動でclient_idを取得するために呼び出す
 * リクエスト: POST JSON {redirect_uris, client_name, ...}
 * レスポンス: JSON {client_id, redirect_uris, client_name, ...}
 */

// F-revoブート
chdir(dirname(__DIR__, 2));
require_once 'config.inc.php';
if (file_exists('config_override.php')) {
    include_once 'config_override.php';
}
require_once 'vendor/autoload.php';
require_once 'include/utils/CommonUtils.php';
vimport('includes.runtime.EntryPoint');
require_once 'include/Mcp/RateLimiter.php';

require_once 'include/Mcp/OAuthHelper.php';
require_once 'include/Mcp/OAuthStorage.php';

// エラー表示抑制
ini_set('display_errors', '0');
ini_set('html_errors', '0');

mcp_oauth_cors_headers();

// POSTのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mcp_oauth_send_error(405, 'invalid_request', 'POST only');
}

// 未認証で呼び出せるため、接続元 IP 単位でレート制限する。
// 閾値は mcp.php と同じシステム変数から取得する。
$rateWindow = Mcp_RateLimiter::parseLimitValue(Settings_Parameters_Record_Model::getParameterValue('MCP_RATE_LIMIT_WINDOW'), Mcp_RateLimiter::DEFAULT_WINDOW_SEC);
$rateMax = Mcp_RateLimiter::parseLimitValue(Settings_Parameters_Record_Model::getParameterValue('MCP_RATE_LIMIT_MAX'), Mcp_RateLimiter::DEFAULT_MAX_REQUESTS);
$rateLimiter = new Mcp_RateLimiter($root_directory . 'cache', $rateWindow, $rateMax);
if (!$rateLimiter->check('dcr:' . ($_SERVER['REMOTE_ADDR'] ?? '-'))) {
    mcp_oauth_send_error(429, 'invalid_request', 'Rate limit exceeded');
}

// Content-Type チェック
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    mcp_oauth_send_error(400, 'invalid_request', 'Content-Type must be application/json');
}

// リクエストボディ解析
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true);
if (!is_array($body)) {
    mcp_oauth_send_error(400, 'invalid_request', 'Invalid JSON body');
}

// redirect_uris は必須（配列、最低1つ）
$redirectUris = $body['redirect_uris'] ?? [];
if (!is_array($redirectUris) || count($redirectUris) === 0) {
    mcp_oauth_send_error(400, 'invalid_client_metadata', 'redirect_uris is required and must be a non-empty array');
}
if (count($redirectUris) > 10) {
    mcp_oauth_send_error(400, 'invalid_client_metadata', 'Too many redirect_uris (max 10)');
}

// redirect_uri のバリデーション（HTTPS必須。loopbackのみHTTP許可。fragment/userinfo禁止）
foreach ($redirectUris as $uri) {
    if (!is_string($uri) || $uri === '') {
        mcp_oauth_send_error(400, 'invalid_redirect_uri', 'Each redirect_uri must be a non-empty string');
    }
    if (strlen($uri) > 2048) {
        mcp_oauth_send_error(400, 'invalid_redirect_uri', 'redirect_uri is too long (max 2048 bytes)');
    }
    $parsed = parse_url($uri);
    if ($parsed === false || empty($parsed['host'])) {
        mcp_oauth_send_error(400, 'invalid_redirect_uri', 'Malformed redirect_uri (must be an absolute http(s) URL with a host)');
    }
    if (isset($parsed['fragment'])) {
        mcp_oauth_send_error(400, 'invalid_redirect_uri', 'redirect_uri must not contain a fragment');
    }
    if (isset($parsed['user']) || isset($parsed['pass'])) {
        mcp_oauth_send_error(400, 'invalid_redirect_uri', 'redirect_uri must not contain userinfo');
    }
    $scheme = strtolower($parsed['scheme'] ?? '');
    $host = strtolower($parsed['host'] ?? '');
    $isLoopbackHttp = ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true));
    if ($scheme !== 'https' && !$isLoopbackHttp) {
        mcp_oauth_send_error(400, 'invalid_redirect_uri', 'redirect_uri must use https (http is allowed only for loopback)');
    }
}

// client_name（任意、デフォルト空文字）
$clientName = $body['client_name'] ?? '';
if (!is_string($clientName)) {
    $clientName = '';
}
if (mb_strlen($clientName) > 255) {
    mcp_oauth_send_error(400, 'invalid_client_metadata', 'client_name is too long (max 255 characters)');
}

// client_secret（任意: publicクライアントは送らない）
$clientSecret = $body['client_secret'] ?? null;
$clientSecretHash = null;
if (is_string($clientSecret) && $clientSecret !== '') {
    $clientSecretHash = hash('sha256', $clientSecret);
}

// client_id 生成
$clientId = mcp_oauth_generate_client_id();

// DB保存
try {
    Mcp_OAuthStorage::createClient($clientId, $clientSecretHash, $clientName, $redirectUris);
} catch (\Exception $e) {
    error_log('[MCP OAuth DCR] DB error: ' . $e->getMessage());
    mcp_oauth_send_error(500, 'server_error', 'Failed to register client');
}

// RFC 7591 レスポンス (201 Created)
$response = [
    'client_id'                  => $clientId,
    'client_name'                => $clientName,
    'redirect_uris'              => $redirectUris,
    'grant_types'                => ['authorization_code', 'refresh_token'],
    'response_types'             => ['code'],
    'token_endpoint_auth_method' => ($clientSecretHash !== null) ? 'client_secret_post' : 'none',
];

// client_secretを返す（生成した場合のみ）
if ($clientSecret !== null) {
    $response['client_secret'] = $clientSecret;
}

mcp_oauth_send_json($response, 201);
