<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Google_Sync_View extends Google_List_View {

	function process(Vtiger_Request $request) {
		$modules = array('Contacts', 'Calendar');
		$syncRecordList = array();
		foreach ($modules as $sourceModule) {
			$request->set('sourcemodule', $sourceModule);
			$oauth2 = new Google_Oauth2_Connector($sourceModule);

			if (Google_Utils_Helper::checkSyncEnabled($sourceModule) && $oauth2->hasStoredToken()) {
				$syncRecords = $this->sync($request, $sourceModule);
				$syncRecordList[$sourceModule] = $syncRecords;
			}
		}

		$response = new Vtiger_Response();
		$response->setResult($syncRecordList);
		$response->emit();
	}

	function sync(Vtiger_Request $request, $sourceModule) {
		try {
			$records = $this->invokeExposedMethod($sourceModule);
			return $records;
		} catch (Zend_Gdata_App_HttpException $e) {
			$errorCode = $e->getResponse()->getStatus();
			if($errorCode == 401) {
				$this->removeSynchronization($request);
				$response = new Vtiger_Response();
				$response->setError(401);
				$response->emit();
				return array();
			}
		}
	}
}
