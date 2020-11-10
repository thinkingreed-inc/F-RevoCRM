<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_DeleteAjax_Action extends Vtiger_DeleteAjax_Action {
	
	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('sourceModule');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'sourceModule';
		}
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'Delete', 'record_parameter'=>'record');
		return $permissions;
	}
	
	function checkPermission(Vtiger_Request $request) {
		$sourceModule = $request->get('sourceModule');
		$record = $request->get('record');
		parent::checkPermission($request);
		
		if ($record) {
			$activityModulesList = array('Calendar', 'Events');
			$recordEntityName = getSalesEntityType($record);

		if (!in_array($recordEntityName, $activityModulesList)  || (!empty($sourceModule) && !in_array($sourceModule, $activityModulesList))) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		}
		return true;
	}
	
	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$recurringEditMode = $request->get('recurringEditMode');
		
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$recordModel->set('recurringEditMode', $recurringEditMode);
		$deletedRecords = $recordModel->delete();

		$cvId = $request->get('viewname');
		deleteRecordFromDetailViewNavigationRecords($recordId, $cvId, $moduleName);
		$response = new Vtiger_Response();
		$response->setResult(array('viewname' => $cvId, 'module' => $moduleName, 'deletedRecords' => $deletedRecords));
		$response->emit();
	}

}
