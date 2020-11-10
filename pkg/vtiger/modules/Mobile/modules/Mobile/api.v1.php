<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Mobile_APIV1_Controller {

	static $opControllers = array(
		'login'						=> array('file' => '/api/ws/Login.php',						'class' => 'Mobile_WS_Login'),
		'loginAndFetchModules'		=> array('file' => '/api/ws/LoginAndFetchModules.php',		'class' => 'Mobile_WS_LoginAndFetchModules'),
		'fetchModuleFilters'		=> array('file' => '/api/ws/FetchModuleFilters.php'  ,		'class' => 'Mobile_WS_FetchModuleFilters'),
		'filterDetailsWithCount'	=> array('file' => '/api/ws/FilterDetailsWithCount.php',	'class' => 'Mobile_WS_FilterDetailsWithCount'),
		'fetchAllAlerts'			=> array('file' => '/api/ws/FetchAllAlerts.php',			'class' => 'Mobile_WS_FetchAllAlerts'),
		'alertDetailsWithMessage'	=> array('file' => '/api/ws/AlertDetailsWithMessage.php',	'class' => 'Mobile_WS_AlertDetailsWithMessage'),
		'listModuleRecords'			=> array('file' => '/api/ws/ListModuleRecords.php',			'class' => 'Mobile_WS_ListModuleRecords'),
		'fetchRecord'				=> array('file' => '/api/ws/FetchRecord.php',				'class' => 'Mobile_WS_FetchRecord'),
		'fetchRecordWithGrouping'	=> array('file' => '/api/ws/FetchRecordWithGrouping.php',	'class' => 'Mobile_WS_FetchRecordWithGrouping'),
		'fetchRecordsWithGrouping'	=> array('file' => '/api/ws/FetchRecordsWithGrouping.php',	'class' => 'Mobile_WS_FetchRecordsWithGrouping'),
		'fetchReferenceRecords'		=> array('file' => '/api/ws/FetchReferenceRecords.php',		'class' => 'Mobile_WS_FetchReferenceRecords'),
		'describe'					=> array('file' => '/api/ws/Describe.php',					'class' => 'Mobile_WS_Describe'),
		'saveRecord'				=> array('file' => '/api/ws/SaveRecord.php',				'class' => 'Mobile_WS_SaveRecord'),
		'syncModuleRecords'			=> array('file' => '/api/ws/SyncModuleRecords.php',			'class' => 'Mobile_WS_SyncModuleRecords'),
		'query'						=> array('file' => '/api/ws/Query.php',						'class' => 'Mobile_WS_Query'),
		'queryWithGrouping'			=> array('file' => '/api/ws/QueryWithGrouping.php',			'class' => 'Mobile_WS_QueryWithGrouping'),
		'relatedRecordsWithGrouping'=> array('file' => '/api/ws/RelatedRecordsWithGrouping.php','class' => 'Mobile_WS_RelatedRecordsWithGrouping'),
		'deleteRecords'				=> array('file' => '/api/ws/DeleteRecords.php',				'class' => 'Mobile_WS_DeleteRecords'),
		'logout'					=> array('file' => '/api/ws/Logout.php',					'class' => 'Mobile_WS_Logout'),
		'fetchModules'				=> array('file' => '/api/ws/FetchModules.php',				'class' => 'Mobile_WS_FetchModules'),
		'userInfo'					=> array('file' => '/api/ws/UserInfo.php',					'class' => 'Mobile_WS_UserInfo'),
		'addRecordComment'			=> array('file' => '/api/ws/AddRecordComment.php',			'class' => 'Mobile_WS_AddRecordComment'),
		'history'					=> array('file' => '/api/ws/History.php',					'class' => 'Mobile_WS_History'),
		'taxByType'					=> array('file' => '/api/ws/TaxByType.php',					'class' => 'Mobile_WS_TaxByType'),
		'fetchModuleOwners'			=> array('file' => '/api/ws/FetchModuleOwners.php',			'class' => 'Mobile_WS_FetchModuleOwners'),
	);

	protected function initSession(Mobile_API_Request $request) {
		$sessionid = $request->getSession();
		return Mobile_API_Session::init($sessionid);
	}

	protected function getController(Mobile_API_Request $request) {
		$operation = $request->getOperation();
		if(isset(self::$opControllers[$operation])) {
			$operationFile = self::$opControllers[$operation]['file'];
			$operationClass= self::$opControllers[$operation]['class'];

			include_once dirname(__FILE__) . $operationFile;
			$operationController = new $operationClass;
			return $operationController;
		}
	}


	function process(Mobile_API_Request $request) {
		$operation = $request->getOperation();

		$response = false;
		$operationController = $this->getController($request);
		if($operationController) {
			$operationSession = false;
			if($operationController->requireLogin()) {
				$operationSession = $this->initSession($request);
				if($operationController->hasActiveUser() === false) {
					$operationSession = false;
				}
				//Mobile_WS_Utils::initAppGlobals();
			} else {
				// By-pass login
				$operationSession = true;
			}

			if($operationSession === false) {
				$response = new Mobile_API_Response();
				$response->setError(1501, 'Login required');
			} else {

				try {
					$response = $operationController->process($request);
				} catch(Exception $e) {
					$response = new Mobile_API_Response();
					$response->setError($e->getCode(), $e->getMessage());
				}
			}

		} else {
			$response = new Mobile_API_Response();
			$response->setError(1404, 'Operation not found: ' . $operation);
		}

		if($response !== false) {
			echo $response->emitJSON();
		}
	}

	static function getInstance() {
		$instance = new static();
		return $instance;
	}
}
