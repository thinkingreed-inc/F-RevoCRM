<?php
/*+***********************************************************************************
 * HealthCheck endpoint for F-RevoCRM (GitHub/OSS edition)
 * See: https://github.com/thinkingreed-inc/F-RevoCRM/issues/1640
 *
 * Checks : web (this process responded) + db (MySQL "SELECT 1")
 * Output : minimal JSON, HTTP 200 (healthy) / 503 (unhealthy). No auth.
 *
 * Design : docs/superpowers/specs/2026-06-11-healthcheck-endpoint-design.md
 *************************************************************************************/

// Never leak PHP warnings/notices into the JSON body.
ini_set('display_errors', '0');
error_reporting(0);

// Buffer everything so any stray bootstrap output can't corrupt the JSON.
ob_start();

/**
 * DB reachability check. Returns true only when "SELECT 1" succeeds.
 * Never throws: any failure is reported as a false result.
 */
function frevo_healthcheck_db() {
    try {
        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT 1 AS healthcheck', array());
        return ($result !== false) && ($adb->num_rows($result) >= 1);
    } catch (\Throwable $e) {
        return false;
    }
}

// Reaching this line means the web layer (PHP) is responding.
$checks = array('web' => 'ok');

try {
    chdir(dirname(__DIR__));
    require_once 'vendor/autoload.php';
    include_once 'config.php';                        // populates $dbconfig / $dbconfigoption
    require_once 'include/database/PearDatabase.php'; // pulls logging + adodb, defines PearDatabase

    $checks['db'] = frevo_healthcheck_db() ? 'ok' : 'error';
} catch (\Throwable $e) {
    // Bootstrap failed before/while checking DB: report error but stay alive.
    if (!isset($checks['db'])) {
        $checks['db'] = 'error';
    }
}

$healthy = !in_array('error', $checks, true);

// Discard any bootstrap noise, then emit a clean JSON document.
ob_end_clean();

header('Content-Type: application/json');
header('Cache-Control: no-store');
http_response_code($healthy ? 200 : 503);

echo json_encode(array(
    'status' => $healthy ? 'ok' : 'error',
    'checks' => $checks,
));
