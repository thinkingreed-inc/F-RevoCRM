<?php
/**
 * File-based MCP Rate Limiter
 *
 * Default: 20 requests per 10 seconds per key (IP or token).
 */
class Mcp_RateLimiter
{
    private string $cacheDir;
    private int $windowSec;
    private int $maxRequests;

    public function __construct(
        string $cacheDir,
        int $windowSec = 10,
        int $maxRequests = 20
    ) {
        $this->cacheDir    = rtrim($cacheDir, '/\\') . '/mcp_ratelimit';
        $this->windowSec   = $windowSec;
        $this->maxRequests = $maxRequests;
    }

    /**
     * @return bool true=allowed, false=rate exceeded
     */
    public function check(string $key): bool
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        $file   = $this->cacheDir . '/' . md5($key) . '.json';
        $now    = time();
        $cutoff = $now - $this->windowSec;

        $timestamps = [];
        if (is_readable($file)) {
            $data = @file_get_contents($file);
            if ($data !== false) {
                $timestamps = json_decode($data, true) ?: [];
            }
        }

        // Keep only timestamps within the window
        $timestamps = array_values(array_filter(
            $timestamps,
            function ($t) use ($cutoff) { return $t > $cutoff; }
        ));

        if (count($timestamps) >= $this->maxRequests) {
            return false;
        }

        $timestamps[] = $now;
        @file_put_contents($file, json_encode($timestamps), LOCK_EX);

        return true;
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
        foreach (glob($this->cacheDir . '/*.json') as $f) {
            if (filemtime($f) < $cutoff) {
                @unlink($f);
            }
        }
    }
}
