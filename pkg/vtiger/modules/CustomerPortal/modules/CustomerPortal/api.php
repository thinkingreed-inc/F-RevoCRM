<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

include_once 'include.inc';

class CustomerPortal_API_EntryPoint {

	protected static function authenticate(CustomerPortal_API_Abstract $controller, CustomerPortal_API_Request $request) {
		// Fix: https://bugs.php.net/bug.php?id=35752
		if (!isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
			if (preg_match('/Basic\s+(.*)$/i', $_SERVER['Authorization'], $matches)) {
				list($name, $password) = explode(':', base64_decode($matches[1]));
				$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
				$_SERVER['PHP_AUTH_PW']    = strip_tags($password);
			}
		}

		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="Customer Portal"');
			header('HTTP/1.0 401 Unauthorized');
			throw new Exception("Login Required", 1412);
			exit;
		} else {
			// Handling the case Contacts module is disabled 
			if (!vtlib_isModuleActive("Contacts")) {
				throw new Exception("Contacts module is disabled", 1412);
			}

			$ok = $controller->authenticatePortalUser($request->get('username'), $request->get('password'));
			if (!$ok) {
				throw new Exception("Login failed", 1412);
			}
		}
	}

	static function process(CustomerPortal_API_Request $request) {
		$operation = $request->getOperation();
		$response = false;
		if (!preg_match("/[0-9a-zA-z]*/", $operation, $match)) {
			throw new Exception("Invalid entry", 1412);
		}

		if ($operation == $match[0]) {
			$operationFile = sprintf('/apis/%s.php', $operation);
			$operationClass = sprintf("CustomerPortal_%s", $operation);
			include_once dirname(__FILE__).$operationFile;
			$operationController = new $operationClass;

			try {
				self::authenticate($operationController, $request);

				//setting active user language as Portal user language 
				$current_user = $operationController->getActiveUser();
				$portal_language = $request->getLanguage();
				$current_user->column_fields["language"] = $portal_language;
				$current_user->language = $portal_language;

				$response = $operationController->process($request);
			} catch (Exception $e) {
				$response = new CustomerPortal_API_Response();
				$response->setError($e->getCode(), $e->getMessage());
			}
		} else {
			$response = new CustomerPortal_API_Response();
			$response->setError(1404, 'Operation not found: '.$operation);
		}

		if ($response !== false) {
			echo $response->emitJSON();
		}
	}

}

/** Take care of stripping the slashes */
function stripslashes_recursive($value) {
	$value = is_array($value) ? array_map('stripslashes_recursive', $value) : stripslashes($value);
	return $value;
}

$clientRequestValues = $_POST;
if (get_magic_quotes_gpc()) {
	$clientRequestValues = stripslashes_recursive($clientRequestValues);
}

$clientRequestValuesRaw = array();
CustomerPortal_API_EntryPoint::process(new CustomerPortal_API_Request($clientRequestValues, $clientRequestValuesRaw));

