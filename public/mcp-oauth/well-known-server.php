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
 * RFC 8414 Authorization Server Metadata
 * /mcp-oauth/.well-known/oauth-authorization-server（サブパス方式）
 *
 * MCPクライアントがauthorize/token/registerのURLと対応方式を取得する
 *
 * F-revoブート不要（静的JSONを返すのみ）
 */

chdir(dirname(__DIR__, 2));
require_once 'config.inc.php';
if (file_exists('config_override.php')) {
    include_once 'config_override.php';
}
require_once 'include/Mcp/OAuthHelper.php';

mcp_oauth_cors_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mcp_oauth_send_error(405, 'invalid_request', 'GET only');
}

$baseUrl = mcp_oauth_get_base_url();
$issuer  = mcp_oauth_get_issuer();

mcp_oauth_send_json([
    'issuer'                                => $issuer,
    'authorization_endpoint'                => $baseUrl . '/index.php?module=Users&view=McpOAuthAuthorize',
    'token_endpoint'                        => $baseUrl . '/mcp-oauth/token.php',
    'registration_endpoint'                 => $baseUrl . '/mcp-oauth/register.php',
    'revocation_endpoint'                   => $baseUrl . '/mcp-oauth/revoke.php',
    'scopes_supported'                      => ['mcp'],
    'response_types_supported'              => ['code'],
    'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
    'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post'],
    'code_challenge_methods_supported'      => ['S256'],
    'service_documentation'                 => 'https://spec.modelcontextprotocol.io/specification/2025-03-26/basic/authorization/',
]);
