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
 * File-based MCP Rate Limiter
 *
 * Default: 20 requests per 10 seconds per key (IP or token).
 */
class Mcp_RateLimiter
{
    /** システム変数が未設定の場合に使用するレート制限の既定値 */
    public const DEFAULT_WINDOW_SEC   = 10;
    public const DEFAULT_MAX_REQUESTS = 20;

    private string $cacheDir;
    private int $windowSec;
    private int $maxRequests;

    public function __construct(
        string $cacheDir,
        int $windowSec = self::DEFAULT_WINDOW_SEC,
        int $maxRequests = self::DEFAULT_MAX_REQUESTS
    ) {
        $this->cacheDir    = rtrim($cacheDir, '/\\') . '/mcp_ratelimit';
        $this->windowSec   = $windowSec;
        $this->maxRequests = $maxRequests;
    }

    /**
     * システム変数の値を 1 以上の整数として解釈する。
     * 小数・指数表記・0 以下は切り捨てると意図しない上限になるため既定値を使用する。
     * @param mixed $value
     */
    public static function parseLimitValue($value, int $default): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $parsed === false ? $default : $parsed;
    }

    /**
     * @return bool true=allowed, false=rate exceeded
     */
    public function check(string $key): bool
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        $file   = $this->filePath($key);
        $now    = time();
        $cutoff = $now - $this->windowSec;

        // 読み取りから書き込みまでを同一ロック内で行い、並行リクエストによる競合を防ぐ
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return true;
        }
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return true;
        }
        $timestamps = $this->readTimestamps($fp, $cutoff);

        $allowed = count($timestamps) < $this->maxRequests;
        if ($allowed) {
            $timestamps[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($timestamps));
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return $allowed;
    }

    /**
     * 計数せずに上限到達かだけを判定する。
     * @return bool true=rate exceeded, false=allowed
     */
    public function isLimited(string $key): bool
    {
        $fp = @fopen($this->filePath($key), 'r');
        if ($fp === false) {
            return false;
        }
        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            return false;
        }
        $timestamps = $this->readTimestamps($fp, time() - $this->windowSec);
        flock($fp, LOCK_UN);
        fclose($fp);

        return count($timestamps) >= $this->maxRequests;
    }

    private function filePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.json';
    }

    /**
     * @param resource $fp
     * @return array<int, int>
     */
    private function readTimestamps($fp, int $cutoff): array
    {
        $data = stream_get_contents($fp);
        $timestamps = ($data !== false && $data !== '') ? (json_decode($data, true) ?: []) : [];

        // Keep only timestamps within the window
        return array_values(array_filter(
            $timestamps,
            function ($t) use ($cutoff) {
                return $t > $cutoff;
            }
        ));
    }

    /**
     * Clean up old rate limit files.
     */
    public function cleanup(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }
        $cutoff = time() - $this->windowSec * 10;
        foreach (glob($this->cacheDir . '/*.json') ?: [] as $f) {
            if (filemtime($f) < $cutoff) {
                @unlink($f);
            }
        }
    }
}
