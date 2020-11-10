<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
header('Content-Type: text/json');

chdir (dirname(__FILE__) . '/../../');

/**
 * URL Verfication - Required to overcome Apache mis-configuration and leading to shared setup mode.
 */
require_once 'config.php';
if (file_exists('config_override.php')) {
    include_once 'config_override.php';
}

// Define GetRelatedList API before including the core files
// NOTE: Make sure GetRelatedList function_exists check is made in include/utils/RelatedListView.php
include_once dirname(__FILE__) . '/api/Relation.php';

include_once dirname(__FILE__) . '/api/Request.php';
include_once dirname(__FILE__) . '/api/Response.php';
include_once dirname(__FILE__) . '/api/Session.php';

include_once dirname(__FILE__) . '/api/ws/Controller.php';
require_once 'includes/main/WebUI.php';

/** Take care of stripping the slashes */
function stripslashes_recursive($value) {
       $value = is_array($value) ? array_map('stripslashes_recursive', $value) : stripslashes($value);
       return $value;
}
/** END **/

if(!defined('MOBILE_API_CONTROLLER_AVOID_TRIGGER')) {

	$clientRequestValues = null;
	if(stripos($_SERVER['CONTENT_TYPE'], 'application/json')!==false) {
		$clientRequestValues = json_decode(file_get_contents("php://input"), true);
	} else {
		$clientRequestValues = $_POST;
	}

	$clientRequestValuesRaw = array();

	if (get_magic_quotes_gpc()) {
	    $clientRequestValues = stripslashes_recursive($clientRequestValues);
	}

	require_once dirname(__FILE__) . '/api.v1.php';
	$targetController = Mobile_APIV1_Controller::getInstance();
	$targetController->process(new Mobile_API_Request($clientRequestValues, $clientRequestValuesRaw));
}
