<?php
/**
 * OAuth 2.1 トークンエンドポイント
 * POST /mcp-oauth/token.php
 *
 * grant_type=authorization_code:
 *   認可コード + PKCE code_verifier → access_token + refresh_token
 *
 * grant_type=refresh_token:
 *   refresh_token → 新 access_token + 新 refresh_token（ローテーション）
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

// リクエストボディ解析（application/x-www-form-urlencoded or application/json）
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
	$body = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
	// application/x-www-form-urlencoded（OAuth標準）
	$body = $_POST;
}

$grantType = $body['grant_type'] ?? '';

// ================================================================
//  grant_type=authorization_code
// ================================================================
if ($grantType === 'authorization_code') {
	$code         = $body['code'] ?? '';
	$codeVerifier = $body['code_verifier'] ?? '';
	$redirectUri  = $body['redirect_uri'] ?? '';
	$clientId     = $body['client_id'] ?? '';

	// 必須パラメータ検証
	if ($code === '') {
		mcp_oauth_send_error(400, 'invalid_request', 'Missing required parameter: code');
	}
	if ($codeVerifier === '') {
		mcp_oauth_send_error(400, 'invalid_request', 'Missing required parameter: code_verifier');
	}
	if ($redirectUri === '') {
		mcp_oauth_send_error(400, 'invalid_request', 'Missing required parameter: redirect_uri');
	}
	if ($clientId === '') {
		mcp_oauth_send_error(400, 'invalid_request', 'Missing required parameter: client_id');
	}

	// 認可コード検索＋消費（1回使い捨て）
	$codeHash = hash('sha256', $code);
	$codeRow = Mcp_OAuthStorage::consumeAuthCode($codeHash);

	if ($codeRow === null) {
		mcp_oauth_send_error(400, 'invalid_grant', 'Invalid or already used authorization code');
	}

	// 有効期限チェック
	if (strtotime($codeRow['expires_at']) < time()) {
		mcp_oauth_send_error(400, 'invalid_grant', 'Authorization code has expired');
	}

	// client_id 一致検証
	if ($codeRow['client_id'] !== $clientId) {
		mcp_oauth_send_error(400, 'invalid_grant', 'client_id mismatch');
	}

	// redirect_uri 厳格一致検証
	if ($codeRow['redirect_uri'] !== $redirectUri) {
		mcp_oauth_send_error(400, 'invalid_grant', 'redirect_uri mismatch');
	}

	// PKCE S256 検証
	if (!mcp_oauth_pkce_verify($codeVerifier, $codeRow['code_challenge'])) {
		mcp_oauth_send_error(400, 'invalid_grant', 'PKCE verification failed');
	}

	// client_secret 検証（confidentialクライアントの場合）
	$client = Mcp_OAuthStorage::getClient($clientId);
	if ($client !== null && $client['client_secret_hash'] !== null && $client['client_secret_hash'] !== '') {
		$clientSecret = $body['client_secret'] ?? '';
		if ($clientSecret === '' || hash('sha256', $clientSecret) !== $client['client_secret_hash']) {
			mcp_oauth_send_error(401, 'invalid_client', 'Invalid client_secret');
		}
	}

	// トークンペア生成
	$accessToken  = mcp_oauth_generate_token();
	$refreshToken = mcp_oauth_generate_token();
	$accessHash   = hash('sha256', $accessToken);
	$refreshHash  = hash('sha256', $refreshToken);
	$expiresAt    = date('Y-m-d H:i:s', time() + 3600); // 1時間

	$userId = (int) $codeRow['userid'];
	$scope  = $codeRow['scope'];

	// DB保存
	try {
		Mcp_OAuthStorage::createToken(
			$accessHash,
			$refreshHash,
			$clientId,
			$userId,
			$scope,
			$expiresAt
		);
	} catch (\Exception $e) {
		error_log('[MCP OAuth Token] DB error: ' . $e->getMessage());
		mcp_oauth_send_error(500, 'server_error', 'Failed to create token');
	}

	// 期限切れデータのクリーンアップ（低頻度で実行）
	if (mt_rand(1, 20) === 1) {
		try {
			Mcp_OAuthStorage::cleanup();
		} catch (\Exception $e) {
			// クリーンアップ失敗は無視
		}
	}

	// 成功レスポンス
	mcp_oauth_send_json([
		'access_token'  => $accessToken,
		'token_type'    => 'Bearer',
		'expires_in'    => 3600,
		'refresh_token' => $refreshToken,
		'scope'         => $scope,
	]);
}

// ================================================================
//  grant_type=refresh_token
// ================================================================
if ($grantType === 'refresh_token') {
	$refreshToken = $body['refresh_token'] ?? '';
	$clientId     = $body['client_id'] ?? '';

	if ($refreshToken === '') {
		mcp_oauth_send_error(400, 'invalid_request', 'Missing required parameter: refresh_token');
	}

	// リフレッシュトークン検索
	$refreshHash = hash('sha256', $refreshToken);
	$tokenRow = Mcp_OAuthStorage::getTokenByRefreshHash($refreshHash);

	if ($tokenRow === null) {
		mcp_oauth_send_error(400, 'invalid_grant', 'Invalid refresh_token');
	}

	// クライアント認証: confidential クライアント(client_secret_hash 設定済み)は
	// refresh_token グラントでも client_secret を必須検証する(OAuth 2.1: 全グラントで認証必須)。
	$tokenClientId = $tokenRow['client_id'];
	if ($clientId !== '' && $clientId !== $tokenClientId) {
		mcp_oauth_send_error(400, 'invalid_grant', 'client_id mismatch');
	}
	$client = Mcp_OAuthStorage::getClient($tokenClientId);
	if ($client !== null && !empty($client['client_secret_hash'])) {
		$clientSecret = $body['client_secret'] ?? '';
		if ($clientSecret === '' || !hash_equals($client['client_secret_hash'], hash('sha256', $clientSecret))) {
			mcp_oauth_send_error(401, 'invalid_client', 'Invalid or missing client_secret');
		}
	}

	// 新トークンペア生成（ローテーション）
	$newAccessToken  = mcp_oauth_generate_token();
	$newRefreshToken = mcp_oauth_generate_token();
	$newAccessHash   = hash('sha256', $newAccessToken);
	$newRefreshHash  = hash('sha256', $newRefreshToken);
	$newExpiresAt    = date('Y-m-d H:i:s', time() + 3600); // 1時間

	// DB更新（旧トークンは無効化される）
	try {
		Mcp_OAuthStorage::rotateToken(
			(int) $tokenRow['id'],
			$newAccessHash,
			$newRefreshHash,
			$newExpiresAt
		);
	} catch (\Exception $e) {
		error_log('[MCP OAuth Token Refresh] DB error: ' . $e->getMessage());
		mcp_oauth_send_error(500, 'server_error', 'Failed to refresh token');
	}

	// 成功レスポンス
	mcp_oauth_send_json([
		'access_token'  => $newAccessToken,
		'token_type'    => 'Bearer',
		'expires_in'    => 3600,
		'refresh_token' => $newRefreshToken,
		'scope'         => $tokenRow['scope'],
	]);
}

// ================================================================
//  未対応の grant_type
// ================================================================
mcp_oauth_send_error(400, 'unsupported_grant_type', 'Supported: authorization_code, refresh_token');
