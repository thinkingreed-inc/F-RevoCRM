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
 * MCP OAuth 2.1 ヘルパー関数群
 *
 * URL構築・PKCE検証・トークン生成・レスポンス送信
 * F-revoブート不要（単体で利用可能）
 */

/**
 * リクエストからOAuthエンドポイントの公開ベースURLを取得（scheme + HTTP_HOST + basePath）。
 * basePath 無し固定だと docroot が public/ 直下でない環境で動的クライアント登録が 404 になる。
 */
function mcp_oauth_get_base_url(): string
{
    // $site_URL が設定されている場合はそれを使用し、Host ヘッダへの依存を避ける。
    // 未設定の場合は従来どおり URL を組み立てる。
    global $site_URL;
    if (!empty($site_URL)) {
        return rtrim($site_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . mcp_oauth_get_base_path();
}

/**
 * public/ ディレクトリの DocumentRoot からの相対パスを返す（realpath 比較、文字列一致はしない）。
 * @return string 先頭 '/' 付き・末尾 '/' 無し。判定不能なら ''
 */
function mcp_oauth_get_base_path(): string
{
    $publicDir = realpath(__DIR__ . '/../../public');
    $docRoot   = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($publicDir === false || $docRoot === false) {
        return '';
    }
    if (strpos($publicDir, $docRoot) !== 0) {
        return '';
    }
    $basePath = str_replace('\\', '/', substr($publicDir, strlen($docRoot)));
    return rtrim($basePath, '/');
}

/**
 * 設定ファイル(include/Mcp/mcp_oauth_config.php)を読み込む。
 * 無ければ空配列（＝すべて自動判定）。環境依存値(issuer等)はここに置く。
 */
function mcp_oauth_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $f = __DIR__ . '/mcp_oauth_config.php';
        $cfg = is_file($f) ? (array) (include $f) : [];
    }
    return $cfg;
}

/**
 * OAuth issuer URL を取得（= authorization server metadata を配信するベースURL）。
 *
 * 設定の issuer があればそれを使う。無ければ自ドメイン(base_url)。
 * - 標準環境: 空でよい → 自ドメインの /.well-known/oauth-authorization-server で配信。
 * - ドメインルートの /.well-known/ が使えない環境(例 一部共有ホスティングのサブドメイン)
 *   では、.well-known が使える別ドメインを issuer に設定し、そこにメタデータを置く。
 */
function mcp_oauth_get_issuer(): string
{
    $cfg = mcp_oauth_config();
    if (!empty($cfg['issuer'])) {
        return rtrim($cfg['issuer'], '/');
    }
    return mcp_oauth_get_base_url();
}

/**
 * ランダムトークン生成（64桁16進数 = 32バイト）
 */
function mcp_oauth_generate_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * ランダム client_id 生成（48桁16進数 = 24バイト）
 */
function mcp_oauth_generate_client_id(): string
{
    return bin2hex(random_bytes(24));
}

/**
 * PKCE S256 検証
 * base64url(sha256(code_verifier)) === code_challenge
 */
function mcp_oauth_pkce_verify(string $codeVerifier, string $codeChallenge): bool
{
    $hash = hash('sha256', $codeVerifier, true);
    $computed = mcp_oauth_base64url_encode($hash);
    return hash_equals($codeChallenge, $computed);
}

/**
 * base64url エンコード (RFC 7636 Appendix A)
 */
function mcp_oauth_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * OAuth エラーレスポンス送信 (JSON)
 */
function mcp_oauth_send_error(int $httpStatus, string $error, string $description = ''): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Pragma: no-cache');
    $body = ['error' => $error];
    if ($description !== '') {
        $body['error_description'] = $description;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * JSON レスポンス送信
 */
function mcp_oauth_send_json(array $data, int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Pragma: no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * CORS ヘッダー設定（well-known, register, token 用）
 * OPTIONSプリフライトも処理
 */
function mcp_oauth_cors_headers(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
