<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class CustomView_DeleteAjax_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'sourceModule', 'action' => 'DetailView');
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}
	
	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$customViewModel = CustomView_Record_Model::getInstanceById($request->get('record'));
		$customViewOwner = $customViewModel->getOwnerId();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ((!$currentUser->isAdminUser()) && ($customViewOwner != $currentUser->getId())) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$customViewModel->delete();
	}
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}
