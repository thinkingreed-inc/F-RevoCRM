<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

	require_once("config.php");
    /**
    * URL Verfication - Required to overcome Apache mis-configuration and leading to shared setup mode.
    */
    if (file_exists('config_override.php')) {
        include_once 'config_override.php';
    }

	require_once "vendor/autoload.php";

	//Overrides GetRelatedList : used to get related query
	//TODO : Eliminate below hacking solution
	include_once 'include/Webservices/Relation.php';

	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';

	require_once("libraries/HTTP_Session2/HTTP/Session2.php");
	require_once 'include/Webservices/Utils.php';
	require_once("include/Webservices/State.php");
	require_once("include/Webservices/OperationManager.php");
	require_once("include/Webservices/SessionManager.php");
	require_once("include/Zend/Json.php");
	require_once('include/logging.php');

	$API_VERSION = "0.22";

	global $seclog,$log;
	$seclog =Logger::getLogger('SECURITY');
	$log = Logger::getLogger('webservice');

	function getRequestParamsArrayForOperation($operation){
		global $operationInput;
		return $operationInput[$operation];
	}

	function setResponseHeaders() {
		header('Content-type: application/json');
	}

	function writeErrorOutput($operationManager, $error){

		setResponseHeaders();
		$state = new State();
		$state->success = false;
		$state->error = $error;
		unset($state->result);
		$output = $operationManager->encode($state);
		echo $output;

	}

	function writeOutput($operationManager, $data){

		setResponseHeaders();
		$state = new State();
		$state->success = true;
		$state->result = $data;
		unset($state->error);
		$output = $operationManager->encode($state);
		echo $output;

	}

	$operation = vtws_getParameter($_REQUEST, "operation");
	$operation = strtolower($operation);
	$format = vtws_getParameter($_REQUEST, "format","json");
	$sessionId = vtws_getParameter($_REQUEST,"sessionName");

	$sessionManager = new SessionManager();
	$operationManager = new OperationManager($adb,$operation,$format,$sessionManager);

	try{
		if(!$sessionId || strcasecmp($sessionId,"null")===0){
			$sessionId = null;
		}

		$input = $operationManager->getOperationInput();
		$adoptSession = false;
		if(strcasecmp($operation,"extendsession")===0){
			if(isset($input['operation'])){
				// Workaround fix for PHP 5.3.x: $_REQUEST doesn't have PHPSESSID
				if(isset($_REQUEST['PHPSESSID'])) {
					$sessionId = vtws_getParameter($_REQUEST,"PHPSESSID");
				} else {
					// NOTE: Need to evaluate for possible security issues
					$sessionId = vtws_getParameter($_COOKIE,'PHPSESSID');
				}
				// END
				$adoptSession = true;
			}else{
				writeErrorOutput($operationManager,new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,"Authencation required"));
				return;
			}
		}
		$sid = $sessionManager->startSession($sessionId,$adoptSession);

		if(!$sessionId && !$operationManager->isPreLoginOperation()){
			writeErrorOutput($operationManager,new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,"Authencation required"));
			return;
		}

		if(!$sid){
			writeErrorOutput($operationManager, $sessionManager->getError());
			return;
		}

		$userid = $sessionManager->get("authenticatedUserId");

		if($userid){

			$seed_user = new Users();
			$current_user = $seed_user->retrieveCurrentUserInfoFromFile($userid);

		}else{
			$current_user = null;
		}

		$operationInput = $operationManager->sanitizeOperation($input);
		$includes = $operationManager->getOperationIncludes();

		foreach($includes as $ind=>$path){
			checkFileAccessForInclusion($path);
			require_once($path);
		}
		$rawOutput = $operationManager->runOperation($operationInput,$current_user);
		writeOutput($operationManager, $rawOutput);
	} catch (DuplicateException $e) {
        writeErrorOutput($operationManager,new WebServiceException($e->getCode(), $e->getMessage()));
	}catch(WebServiceException $e){
		writeErrorOutput($operationManager,$e);
	}catch(Exception $e){
		writeErrorOutput($operationManager,
			new WebServiceException(WebServiceErrorCode::$INTERNALERROR,"Unknown Error while processing request"));
	}
?>
