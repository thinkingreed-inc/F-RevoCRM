<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once('modules/com_vtiger_workflow/VTEntityCache.inc');
require_once('modules/com_vtiger_workflow/VTWorkflowUtils.php');
require_once('modules/com_vtiger_workflow/VTSimpleTemplate.inc');

class VTCurlTask extends VTTask {
	// Curl requests are typically short, execute immediately
	public $executeImmediately = true;

	public function getFieldNames() {
		return array("url", "method", "headers", "body", "timeout");
	}

	public function doTask($entity) {
		global $adb, $log;

		$util = new VTWorkflowUtils();
		$admin = $util->adminUser();

		$entityCache = new VTEntityCache($admin);
		$entityId = $entity->getId();

		// Render URL with field values
		$urlTemplate = new VTSimpleTemplate($this->url);
		$renderedUrl = $urlTemplate->render($entityCache, $entityId);

		// Validate URL and check for SSRF
		if (!$this->isValidUrl($renderedUrl)) {
			$log->error("VTCurlTask: Invalid or unsafe URL: " . $renderedUrl);
			$util->revertUser();
			return false;
		}

		// Render headers with field values
		$headersTemplate = new VTSimpleTemplate($this->headers);
		$renderedHeaders = $headersTemplate->render($entityCache, $entityId);

		// Render body with field values
		$bodyTemplate = new VTSimpleTemplate($this->body);
		$renderedBody = $bodyTemplate->render($entityCache, $entityId);

		// Set default method if not specified
		$method = !empty($this->method) ? strtoupper($this->method) : 'POST';

		// Set default timeout if not specified
		$timeout = !empty($this->timeout) ? intval($this->timeout) : 30;

		// Limit timeout to maximum 60 seconds for security
		if ($timeout > 60) {
			$timeout = 60;
		}

		// Execute curl request
		try {
			$result = $this->executeCurlRequest($renderedUrl, $method, $renderedHeaders, $renderedBody, $timeout);

			if ($result['success']) {
				$log->info("VTCurlTask: Successfully executed curl request to " . $renderedUrl);
			} else {
				$log->error("VTCurlTask: Failed to execute curl request: " . $result['error']);
			}
		} catch (Exception $e) {
			$log->error("VTCurlTask: Exception during curl execution: " . $e->getMessage());
		}

		$util->revertUser();
	}

	/**
	 * Validate URL and check for SSRF attacks
	 * @param string $url
	 * @return bool
	 */
	private function isValidUrl($url) {
		// Check if URL is valid
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		}

		// Parse URL
		$parsedUrl = parse_url($url);
		if (!$parsedUrl || !isset($parsedUrl['host'])) {
			return false;
		}

		$host = $parsedUrl['host'];

		// Check for localhost
		if (in_array(strtolower($host), array('localhost', '127.0.0.1', '::1'))) {
			return false;
		}

		// Resolve hostname to IP
		$ip = gethostbyname($host);
		if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
			// Could not resolve hostname
			return true; // Allow external domains
		}

		// Check if IP is private
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			// Check for private IPv4 ranges
			$private_ranges = array(
				array('10.0.0.0', '10.255.255.255'),
				array('172.16.0.0', '172.31.255.255'),
				array('192.168.0.0', '192.168.255.255'),
				array('127.0.0.0', '127.255.255.255'),
			);

			$ip_long = ip2long($ip);
			foreach ($private_ranges as $range) {
				if ($ip_long >= ip2long($range[0]) && $ip_long <= ip2long($range[1])) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Execute curl request
	 * @param string $url
	 * @param string $method
	 * @param string $headers
	 * @param string $body
	 * @param int $timeout
	 * @return array
	 */
	private function executeCurlRequest($url, $method, $headers, $body, $timeout) {
		$ch = curl_init();

		// Set URL
		curl_setopt($ch, CURLOPT_URL, $url);

		// Set method
		switch ($method) {
			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				if (!empty($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			case 'PUT':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				if (!empty($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			case 'PATCH':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
				if (!empty($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			default:
				curl_setopt($ch, CURLOPT_POST, true);
		}

		// Parse and set headers
		if (!empty($headers)) {
			$headerLines = explode("\n", $headers);
			$curlHeaders = array();
			foreach ($headerLines as $headerLine) {
				$headerLine = trim($headerLine);
				if (!empty($headerLine)) {
					$curlHeaders[] = $headerLine;
				}
			}
			if (!empty($curlHeaders)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
			}
		}

		// Set timeout
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($timeout, 10));

		// Return response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// SSL verification (enabled for security)
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		// Disable redirect following for security
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

		// Execute request
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		$errno = curl_errno($ch);

		curl_close($ch);

		if ($errno) {
			return array(
				'success' => false,
				'error' => "Curl error ($errno): $error"
			);
		}

		global $log;
		$log->debug("VTCurlTask: Response: " . $response);

		return array(
			'success' => true,
			'http_code' => $httpCode,
			'response' => $response
		);
	}
}
