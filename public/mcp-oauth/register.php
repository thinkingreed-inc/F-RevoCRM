<?php
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

// redirect_uri のバリデーション（HTTPS必須。loopbackのみHTTP許可。fragment/userinfo禁止）
foreach ($redirectUris as $uri) {
	if (!is_string($uri) || $uri === '') {
		mcp_oauth_send_error(400, 'invalid_redirect_uri', 'Each redirect_uri must be a non-empty string');
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
