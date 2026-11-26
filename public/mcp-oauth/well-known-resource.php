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
 * RFC 9728 Protected Resource Metadata
 * /mcp-oauth/.well-known/oauth-protected-resource（サブパス方式）
 *
 * MCPクライアント(claude.ai)が401受信後に最初にアクセスするエンドポイント
 * 認可サーバーのURL(issuer)を返す
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

mcp_oauth_send_json([
    'resource'                 => $baseUrl . '/mcp.php',
    // 認可サーバー(issuer)。設定 mcp_oauth_config.php の issuer、無ければ自ドメイン。
    // ドメインルートの /.well-known/ が使えない環境では、設定で .well-known が使える
    // 別ドメインを指定する（そのドメインに authorization server metadata を置く）。
    'authorization_servers'    => [mcp_oauth_get_issuer()],
    'bearer_methods_supported' => ['header'],
]);
