<?php
/**
 * MCP Server - JSON-RPC 2.0 / Streamable HTTP / Stateless
 *
 * Handles: initialize, tools/list, tools/call, ping
 * Authentication:
 *   1. OAuth 2.1 access_token (vtiger_mcp_oauth_token) — claude.ai Web用
 *   2. Static Bearer token (vtiger_mcp_token)           — Desktop/Code用
 *   両方を順に検証し、いずれかで認証成功すればOK
 *   認証失敗時は 401 + WWW-Authenticate: Bearer resource_metadata=URL を返す
 */

require_once 'include/Mcp/TokenAuth.php';
require_once 'include/Mcp/AuditLogger.php';
require_once 'include/Mcp/RateLimiter.php';
require_once 'include/Mcp/CrmTools.php';
require_once 'include/Mcp/OAuthHelper.php';

class Mcp_McpServer
{
    private const PROTOCOL_VERSION = '2025-03-26';
    private const SERVER_NAME      = 'frevo-crm';
    private const SERVER_VERSION   = '1.0.0';

    /** @var Mcp_AuditLogger */
    private $audit;

    /** @var Mcp_RateLimiter */
    private $rateLimiter;

    /** @var bool */
    private $debug;

    /** @var string */
    private $rootDir;

    public function __construct(string $rootDir, bool $debug = false)
    {
        $this->rootDir     = rtrim($rootDir, '/\\');
        $this->debug       = $debug;
        $this->audit       = new Mcp_AuditLogger($this->rootDir . '/logs');
        $this->rateLimiter = new Mcp_RateLimiter($this->rootDir . '/cache');
    }

    /**
     * Handle the incoming HTTP request.
     */
    public function handle(): void
    {
        // Only accept POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendHttpError(405, 'Method Not Allowed');
            return;
        }

        header('Content-Type: application/json');

        $ip = $this->getClientIp();

        // ── Rate limiting (before auth) ──
        if (!$this->rateLimiter->check($ip)) {
            $this->audit->logAuth($ip, false, 'rate_limited');
            $this->sendJsonRpcError(null, -32002, 'Rate limit exceeded (max 20 requests per 10 seconds)', 429);
            return;
        }

        // ── Bearer token extraction ──
        $authHeader  = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        $bearerToken = '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            $bearerToken = substr($authHeader, 7);
        }

        // ── Authenticate (OAuth 2.1 + 既存Bearer 併存) ──
        $userId = 0;
        $authMethod = '';

        if ($bearerToken !== '') {
            // (a) OAuth access_token で認証試行
            $userId = $this->tryOAuthAuth($bearerToken);
            if ($userId > 0) {
                $authMethod = 'oauth';
            }

            // (b) OAuth失敗 → 既存 vtiger_mcp_token で認証試行
            if ($userId === 0) {
                try {
                    $userId = Mcp_TokenAuth::authenticate($bearerToken);
                    $authMethod = 'static_token';
                } catch (\Exception $e) {
                    // 両方失敗
                }
            }
        }

        // 認証済みユーザーが F-revo 上で有効(Active/未削除)か確認。
        // 無効化・削除されたユーザーのトークン(OAuth/固定とも)は拒否する。
        if ($userId > 0 && !$this->isActiveUser($userId)) {
            $userId = 0;
        }

        if ($userId === 0) {
            $reason = ($bearerToken === '') ? 'no_token' : 'invalid_token';
            $this->audit->logAuth($ip, false, $reason);
            $this->sendOAuthChallenge();
            return;
        }

        $this->audit->logAuth($ip, true, "user={$userId},method={$authMethod}");

        // ── Set current_user to the token's user ──
        global $current_user;
        $current_user = new Users();
        $current_user->retrieveCurrentUserInfoFromFile($userId);

        // ModTracker reads $_SESSION['authenticated_user_id'] for whodid
        if (session_status() === PHP_SESSION_NONE) {
            // Don't start a real session for stateless MCP, just set the superglobal
            $_SESSION = [];
        }
        $_SESSION['authenticated_user_id'] = $userId;

        // ── Parse JSON-RPC request ──
        $rawBody = file_get_contents('php://input');
        $body = json_decode($rawBody, true);
        if (!is_array($body) || empty($body['method'])) {
            $this->sendJsonRpcError($body['id'] ?? null, -32600, 'Invalid JSON-RPC request');
            return;
        }

        $rpcId  = $body['id'] ?? null;
        $method = $body['method'];
        $params = $body['params'] ?? [];

        // ── Dispatch ──
        try {
            $tools = new Mcp_CrmTools($current_user);

            switch ($method) {
                case 'initialize':
                    $result = $this->handleInitialize($params);
                    break;
                case 'tools/list':
                    $result = ['tools' => $tools->listTools()];
                    break;
                case 'tools/call':
                    $result = $this->handleToolsCall($tools, $params, $ip, $userId);
                    break;
                case 'ping':
                    $result = ['status' => 'pong'];
                    break;
                default:
                    $this->sendJsonRpcError($rpcId, -32601, "Unknown method: {$method}");
                    return;
            }

            $this->sendJsonRpcSuccess($rpcId, $result);
        } catch (\InvalidArgumentException $e) {
            $this->sendJsonRpcError($rpcId, -32602, $e->getMessage());
        } catch (WebServiceException $e) {
            // vtws_* errors - forward message but hide SQL details in production
            $msg = $this->debug
                ? $e->getMessage()
                : $this->sanitizeErrorMessage($e->getMessage());
            $this->sendJsonRpcError($rpcId, -32603, $msg);
        } catch (\Throwable $e) {
            $msg = $this->debug
                ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : 'Internal server error';
            error_log('[MCP] Unhandled: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendJsonRpcError($rpcId, -32603, $msg);
        }
    }

    // ================================================================
    //  Method handlers
    // ================================================================

    private function handleInitialize(array $params): array
    {
        $clientVersion = $params['protocolVersion'] ?? self::PROTOCOL_VERSION;

        return [
            'protocolVersion' => $clientVersion,
            'serverInfo'      => [
                'name'    => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'capabilities' => [
                'tools' => new \stdClass(),
            ],
            // クライアント(Claude)にサーバーの使い方を伝える。特にレコードの関連付け方法。
            'instructions' => $this->serverInstructions(),
        ];
    }

    /**
     * MCPクライアント(Claude)向けの使い方ガイド。
     * initialize 応答で返され、Claudeがツールの使い方・関連付け方法を理解するのに使う。
     */
    private function serverInstructions(): string
    {
        return implode("\n", [
            'これはF-revo CRM(vtigerベース)を操作するMCPサーバーです。操作は認証ユーザーの権限の範囲で行われます。',
            '',
            '基本フロー:',
            '1) crm_list_modules でアクセス可能なモジュール(Accounts/Contacts/Potentials/Project/Calendar等)を確認',
            '2) crm_describe(module) でフィールド定義・型・選択肢(picklist)・必須項目を確認してから作成/更新する',
            '3) crm_search / crm_get で取得、crm_create / crm_update で作成・更新、crm_delete でゴミ箱へ移動',
            '',
            'レコードの関連付け(リレーション)の方法:',
            '- 関連付けは「参照フィールド」に相手レコードの数値ID(crmid)を入れて行う。例: 連絡先や案件の取引先紐付けは account_id、担当者は assigned_user_id。',
            '- どのフィールドが参照型かは crm_describe の結果で type が "reference" または "owner" のフィールドを見る。"refersTo" にどのモジュールを参照できるかが載っている。',
            '- 値は crm_search/crm_get で得た相手レコードの crmid(数値) をそのまま渡せばよい(内部で vtiger形式に変換される)。',
            '- 担当者(assigned_user_id)の候補は crm_list_users で取得できる。',
            '',
            '注意:',
            '- IDはツール入出力では module名 + crmid(数値) を使う。',
            '- crm_delete は物理削除でなくゴミ箱(論理削除)。CRM画面から復元可能。',
            '- 作成・更新・削除は変更履歴(modtracker)に自動記録される。',
        ]);
    }

    private function handleToolsCall(Mcp_CrmTools $tools, array $params, string $ip, int $userId): array
    {
        $toolName = $params['name'] ?? '';
        $toolArgs = $params['arguments'] ?? [];

        if ($toolName === '') {
            throw new \InvalidArgumentException('tools/call requires "name" parameter');
        }

        $success  = false;
        $errorMsg = null;
        try {
            $result  = $tools->callTool($toolName, $toolArgs);
            $success = true;

            $text = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return [
                'content' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ];
        } catch (\Throwable $e) {
            $errorMsg = $this->debug
                ? $e->getMessage()
                : $this->sanitizeErrorMessage($e->getMessage());
            return [
                'content' => [
                    ['type' => 'text', 'text' => json_encode(
                        ['error' => $errorMsg],
                        JSON_UNESCAPED_UNICODE
                    )],
                ],
                'isError' => true,
            ];
        } finally {
            $this->audit->logToolCall($ip, $userId, $toolName, $toolArgs, $success, $errorMsg);
        }
    }

    // ================================================================
    //  OAuth authentication helpers
    // ================================================================

    /**
     * OAuth access_token で認証試行
     * vtiger_mcp_oauth_token テーブルから access_token_hash を照合
     *
     * @param string $bearerToken 生のaccess_token
     * @return int ユーザーID（認証失敗時は0）
     */
    private function tryOAuthAuth(string $bearerToken): int
    {
        try {
            require_once 'include/Mcp/OAuthStorage.php';
            $hash = hash('sha256', $bearerToken);
            $tokenRow = Mcp_OAuthStorage::getTokenByAccessHash($hash);
            if ($tokenRow !== null) {
                return (int) $tokenRow['userid'];
            }
        } catch (\Exception $e) {
            // テーブル未作成・DB接続エラー等は無視してフォールバック
            if ($this->debug) {
                error_log('[MCP] OAuth auth error: ' . $e->getMessage());
            }
        }
        return 0;
    }

    /**
     * 401 Unauthorized + WWW-Authenticate ヘッダー送信
     * MCP Authorization spec: resource_metadata パラメータで
     * Protected Resource Metadata のURLをクライアントに通知
     *
     * サブパス方式: /mcp-oauth/.well-known/oauth-protected-resource
     * （ドメインルート /.well-known/ はXserverが403で拒否するため）
     */
    private function sendOAuthChallenge(): void
    {
        $baseUrl = mcp_oauth_get_base_url();
        $resourceMetadataUrl = $baseUrl . '/mcp-oauth/.well-known/oauth-protected-resource';

        http_response_code(401);
        header('WWW-Authenticate: Bearer resource_metadata="' . $resourceMetadataUrl . '"');
        header('Content-Type: application/json');
        echo json_encode([
            'jsonrpc' => '2.0',
            'id'      => null,
            'error'   => [
                'code'    => -32001,
                'message' => 'Unauthorized: Bearer token required',
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // ================================================================
    //  Response helpers
    // ================================================================

    private function sendJsonRpcSuccess($id, array $result): void
    {
        echo json_encode([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function sendJsonRpcError($id, int $code, string $message, int $httpStatus = 200): void
    {
        if ($httpStatus !== 200) {
            http_response_code($httpStatus);
        }
        echo json_encode([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => ['code' => $code, 'message' => $message],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function sendHttpError(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }

    /**
     * 指定ユーザーが F-revo 上で有効(status=Active かつ deleted=0)か。
     * 無効化済みユーザーの古いトークンを失効させるために使う。
     */
    private function isActiveUser(int $userId): bool
    {
        try {
            $db = PearDatabase::getInstance();
            $r = $db->pquery("SELECT 1 FROM vtiger_users WHERE id = ? AND status = 'Active' AND deleted = 0", [$userId]);
            return $r && $db->num_rows($r) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getClientIp(): string
    {
        // X-Forwarded-For は信頼できるプロキシ前段がある場合のみ有効。共有ホスティングでは
        // クライアントが自由に偽装でき、レート制限のすり抜け・監査ログ汚染に悪用されるため
        // 既定では REMOTE_ADDR(=接続元)のみを使う。
        // 信頼プロキシ配下で運用する場合のみ MCP_TRUST_XFF を真にして XFF 先頭を採用する。
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && getenv('MCP_TRUST_XFF')) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Sanitize error message for production (remove SQL, file paths, etc.)
     */
    private function sanitizeErrorMessage(string $msg): string
    {
        // Keep known vtws error messages, sanitize unknown ones
        $knownPrefixes = [
            'Permission to',
            'Access denied',
            'ACCESSDENIED',
            'Record you are trying',
            'Invalid',
            'Cannot assign',
            'Unknown tool',
            'module',
            'Missing',
        ];
        foreach ($knownPrefixes as $prefix) {
            if (stripos($msg, $prefix) === 0 || stripos($msg, $prefix) !== false) {
                return $msg;
            }
        }
        // If message contains SQL-like patterns, generic-ize it
        if (preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|JOIN)\b/i', $msg)) {
            return 'Operation failed';
        }
        return $msg;
    }
}
