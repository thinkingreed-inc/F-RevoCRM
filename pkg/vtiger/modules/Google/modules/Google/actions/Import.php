<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Google_Import_Action extends Vtiger_BasicAjax_Action {

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	function process(Vtiger_Request $request) {
		$request->set('sourcemodule', 'Contacts');
		$sourceModule = $request->get('sourcemodule');
		$oauth2 = new Google_Oauth2_Connector($sourceModule);
		if (Google_Utils_Helper::checkSyncEnabled($sourceModule) && $oauth2->hasStoredToken()) {
			$this->saveSettings($request);
			return $this->import($request);
		}
	}

	function saveSettings($request) {
		$sourceModule = $request->get('sourcemodule');
		$fieldMapping = $request->get('fieldmapping');

		Google_Utils_Helper::saveSettings($request);
		if ($fieldMapping) {
			Google_Utils_Helper::saveFieldMappings($sourceModule, $fieldMapping);
		}
		$response = new Vtiger_Response;
		$result = array('settingssaved' => true);
		$response->setResult($result);
		$response->emit();
	}

	function import($request) {
		try {
			$records = $this->importContacts();
		} catch (Zend_Gdata_App_HttpException $e) {
			$errorCode = $e->getResponse()->getStatus();
			if ($errorCode == 401) {
				$this->removeSynchronization($request);
				$response = new Vtiger_Response();
				$response->setError(401);
				$response->emit();
				return false;
			}
		}
	}

	public function importContacts() {
		$user = Users_Record_Model::getCurrentUserModel();
		$controller = new Google_Contacts_Controller($user);
		if (Google_Utils_Helper::checkSyncEnabled('Contacts', $user)) {
			$records = $controller->synchronize(true, true, false);
		}
		$googleListView = new Google_List_View();
		$syncRecords = $googleListView->getSyncRecordsCount($records);
		$syncRecords['vtiger']['more'] = $controller->targetConnector->moreRecordsExits();
		$syncRecords['google']['more'] = $controller->sourceConnector->moreRecordsExits();

		return $syncRecords;
	}

	function removeSynchronization($request) {
		$sourceModule = $request->get('sourcemodule');
		$userModel = Users_Record_Model::getCurrentUserModel();
		Google_Module_Model::removeSync($sourceModule, $userModel->getId());
	}

}
