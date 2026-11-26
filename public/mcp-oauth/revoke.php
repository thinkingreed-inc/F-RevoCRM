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
 * OAuth 2.0 失効エンドポイント（RFC 7009）
 * POST /mcp-oauth/revoke.php
 *
 * token（access_token または refresh_token）の連携を失効させる。
 * トークンそのものが本人性の証明になるため、追加の認証は要求しない。
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

$token = $body['token'] ?? '';
if ($token === '') {
    mcp_oauth_send_error(400, 'invalid_request', 'Missing required parameter: token');
}

// 該当行が無くても 200 を返す（RFC 7009。存在の有無をクライアントに教えない）
Mcp_OAuthStorage::disableByTokenHash(hash('sha256', $token));

http_response_code(200);
header('Cache-Control: no-store');
header('Pragma: no-cache');
