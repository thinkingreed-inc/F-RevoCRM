<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Settings_LoginHistory_ExportData_Action extends Vtiger_ExportData_Action {
    
    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
    public function checkPermission(Vtiger_Request $request){
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if(!$currentUserModel->isAdminUser()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
	}

	var $exportableFields = array(	'login_id'		=> 'login_id',
									'user_name'=> 'LBL_USER_NAME',
									'user_ip'=> 'LBL_USER_IP_ADDRESS', 
									'login_time' => 'LBL_LOGIN_TIME',
									'logout_time' => 'LBL_LOGGED_OUT_TIME', 
									'status' => 'LBL_STATUS',
									'is_portal'=> 'Portal',
	);

	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	function ExportData(Vtiger_Request $request) {
		$this->moduleCall = true;
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');
		if ($moduleName) {
			$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
//			$this->moduleFieldInstances = $this->moduleInstance->getFields();
//			$this->focus = CRMEntity::getInstance($moduleName);

			$query = $this->getExportQuery($request);

			$result = $db->pquery($query, array());

			$headers = $this->exportableFields;
			foreach ($headers as $header) {
				$translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), 'Settings:LoginHistory');
			}

			$entries = array();
			for ($i=0; $i<$db->num_rows($result); $i++) {
				$record = $db->fetchByAssoc($result, $i);
				if($record['status'] != 'Signed off') {
					$record['logout_time'] = '';
				}
				$record['status'] = vtranslate($record['status'], 'Settings:LoginHistory');
				$entries[] = $record;
			}

			return $this->output($request, $translatedHeaders, $entries);
		}
	}

	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return <String> export query
	 */
	function getExportQuery(Vtiger_Request $request) {
		$listview = Settings_LoginHistory_ListView_Model::getInstance('Settings:LoginHistory');
		$listview->set('search_key', $request->get('search_key'));
		$listview->set('search_value', $request->get('search_value'));

		return $listview->getBasicListQuery();
	}
	
	public function validateRequest(Vtiger_Request $request) {
        $request->validateReadAccess();
    }
}
