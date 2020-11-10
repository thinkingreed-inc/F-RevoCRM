<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Reports_DeleteAjax_Action extends Vtiger_DeleteAjax_Action {
    
	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		$permissions[] = array('module_parameter' => 'module', 'action' => 'Delete', 'record_parameter' => 'record');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$response = new Vtiger_Response();

		$recordModel = Reports_Record_Model::getInstanceById($recordId, $moduleName);

		if (!$recordModel->isDefault() && $recordModel->isEditable() && $recordModel->isEditableBySharing()) {
			$recordModel->delete();
			$response->setResult(array(vtranslate('LBL_REPORTS_DELETED_SUCCESSFULLY', $parentModule)));
		} else {
			$response->setError(vtranslate('LBL_REPORT_DELETE_DENIED', $moduleName));
		}
		$response->emit();
	}
}
