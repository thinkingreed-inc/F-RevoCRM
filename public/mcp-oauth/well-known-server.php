<?php
/**
 * RFC 8414 Authorization Server Metadata
 * /mcp-oauth/.well-known/oauth-authorization-server（サブパス方式）
 *
 * MCPクライアントがauthorize/token/registerのURLと対応方式を取得する
 *
 * F-revoブート不要（静的JSONを返すのみ）
 */

require_once dirname(__DIR__, 2) . '/include/Mcp/OAuthHelper.php';

mcp_oauth_cors_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	mcp_oauth_send_error(405, 'invalid_request', 'GET only');
}

$baseUrl = mcp_oauth_get_base_url();
$issuer  = mcp_oauth_get_issuer();

mcp_oauth_send_json([
	'issuer'                                => $issuer,
	'authorization_endpoint'                => $baseUrl . '/mcp-oauth/authorize.php',
	'token_endpoint'                        => $baseUrl . '/mcp-oauth/token.php',
	'registration_endpoint'                 => $baseUrl . '/mcp-oauth/register.php',
	'scopes_supported'                      => ['mcp'],
	'response_types_supported'              => ['code'],
	'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
	'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post'],
	'code_challenge_methods_supported'      => ['S256'],
	'service_documentation'                 => 'https://spec.modelcontextprotocol.io/specification/2025-03-26/basic/authorization/',
]);
