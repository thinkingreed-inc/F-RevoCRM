<?php
/**
 * MCP Audit Logger
 *
 * Logs all MCP tool calls and authentication events to ct/logs/mcp_audit.log
 */
class Mcp_AuditLogger
{
    private string $logFile;

    public function __construct(string $logDir)
    {
        $this->logFile = rtrim($logDir, '/\\') . '/mcp_audit.log';
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Log a tool call.
     */
    public function logToolCall(
        string $ip,
        int $userId,
        string $tool,
        array $args,
        bool $success,
        ?string $error = null
    ): void {
        $argsJson = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($argsJson) > 500) {
            $argsJson = mb_substr($argsJson, 0, 497) . '...';
        }
        $status = $success ? 'ok' : ('error: ' . ($error ?? 'unknown'));
        $line = sprintf(
            "[%s] IP=%s user=%d tool=%s args=%s result=%s\n",
            date('Y-m-d H:i:s'), $ip, $userId, $tool, $argsJson, $status
        );
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log authentication event.
     */
    public function logAuth(string $ip, bool $success, string $detail = ''): void
    {
        $status = $success ? 'auth_ok' : 'auth_fail';
        $line = sprintf(
            "[%s] IP=%s %s %s\n",
            date('Y-m-d H:i:s'), $ip, $status, $detail
        );
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
