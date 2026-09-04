<?php
/**
 * F-revo CRM MCP Server Endpoint
 *
 * JSON-RPC 2.0 / Streamable HTTP / Stateless
 * Authentication: Bearer token -> vtiger_mcp_token -> F-revo user
 *
 * Boot sequence follows vtigercron.php pattern:
 *   config.inc.php -> Loader -> EntryPoint -> Users
 *
 * URL:
 *   Local:  http://localhost/ct/public/mcp.php
 *   Prod:   https://your-crm.example.com/mcp.php (symlink or copy)
 */

// ── Disable output buffering for streaming ──
while (ob_get_level()) {
    ob_end_clean();
}

// ── Boot F-revo core ──
// Note: vendor/autoload.php already loads includes/Loader.php via composer autoload_files
chdir(dirname(__DIR__));  // Set working directory to ct/

require_once 'config.inc.php';
if (file_exists('config_override.php')) {
    include_once 'config_override.php';
}
require_once 'vendor/autoload.php';
require_once 'include/utils/CommonUtils.php';
vimport('includes.runtime.EntryPoint');

// Ensure Webservices are available
require_once 'include/Webservices/Utils.php';
// Relation.php conflicts with RelatedListView.php - conditionally include
if (!function_exists('GetRelatedList')) {
    include_once 'include/Webservices/Relation.php';
}
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/logging.php';

// ── Suppress HTML error output ──
ini_set('display_errors', '0');
ini_set('html_errors', '0');

// ── Debug flag: true for local, false for production ──
$isDebug = (strpos($site_URL, 'localhost') !== false || strpos($site_URL, '127.0.0.1') !== false);

// ── Launch MCP Server ──
require_once 'include/Mcp/McpServer.php';

$server = new Mcp_McpServer($root_directory, $isDebug);
$server->handle();
