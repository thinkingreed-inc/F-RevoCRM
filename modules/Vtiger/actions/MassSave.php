<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_MassSave_Action extends Vtiger_Mass_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView');
		return $permissions;
	}
	
	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode',false));
			$moduleName = $request->getModule();
			$recordModels = $this->getRecordModelsFromRequest($request);
			$allRecordSave= true;
			foreach($recordModels as $recordId => $recordModel) {
				if(Users_Privileges_Model::isPermitted($moduleName, 'Save', $recordId)) {
					$recordModel->save();
				} else {
					$allRecordSave= false;
				}
			}
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);
			if($allRecordSave) {
				$response->setResult(true);
			} else {
			   $response->setResult(false);
			}
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Function to get the updated record models
	 * @param Vtiger_Request $request
	 * @return array of Vtiger_Record_Model
	 */
	protected function getRecordModelsFromRequest(Vtiger_Request $request) {
		$recordIds = $this->getRecordsListFromRequest($request);
		$recordModels = array();

		foreach($recordIds as $recordId) {
			$recordModels[$recordId] = $this->getUpdatedRecord($request, $recordId);
		}
		
		return $recordModels;
	}
	
	private function getUpdatedRecord(Vtiger_Request $request, $recordId) {
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
		$recordModel->set('mode', 'edit');
		$fieldModelList = $recordModel->getModule()->getFields();
		
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			if ($request->has($fieldName)) {
				$fieldValue = $request->get($fieldName, null);
				$fieldDataType = $fieldModel->getFieldDataType();
				if($fieldDataType == 'time'){
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				
				if (!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
