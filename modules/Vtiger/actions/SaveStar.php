<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Vtiger_SaveStar_Action extends Vtiger_Mass_Action {
	var $followRecordIds = Array();
	
	public function requiresPermission(\Vtiger_Request $request) {
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}
	function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		if ($request->has('selected_ids')) {
			$recordIds = $this->getRecordsListFromRequest($request);
			foreach ($recordIds as $recordId) {
				$moduleName = getSalesEntityType($recordId);
				$permissionStatus  = Users_Privileges_Model::isPermitted($moduleName,  'DetailView', $recordId);
				if($permissionStatus){
					$this->followRecordIds[] = $recordId;
				}
				if(empty($this->followRecordIds)){
					throw new AppException(vtranslate('LBL_RECORD_PERMISSION_DENIED'));
				}
			}
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$module = $request->get('module');
		if ($request->has('selected_ids')) {
			$recordIds = $this->followRecordIds;
		} else {
			$recordIds = array($request->get('record'));
		}

		$moduleUserSpecificTableName = Vtiger_Functions::getUserSpecificTableName($module);
		//TODO : Currently we are not doing retrieve_entity_info before doing save since we have only one user specific field(starred)
		// if we add more user specific field then we need to peform retrieve_entity_info 
		foreach ($recordIds as $recordId) {
			$focus = CRMEntity::getInstance($module);
			$focus->mode = "edit";
			$focus->id = $recordId;
			$focus->column_fields->startTracking();
			$focus->column_fields['starred'] = $request->get('value');
			$focus->insertIntoEntityTable($moduleUserSpecificTableName, $module);
		}

		$response = new Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

}
