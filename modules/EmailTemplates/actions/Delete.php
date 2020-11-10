<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class EmailTemplates_Delete_Action extends Vtiger_Delete_Action {
	
	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
    public function checkPermission($request) {
        $moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if(!$moduleModel->isActive()){
            return false;
        }
        return true;
    }

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$ajaxDelete = $request->get('ajaxDelete');
		
		$recordModel = EmailTemplates_Record_Model::getInstanceById($recordId);
		$moduleModel = $recordModel->getModule();

		$recordModel->delete($recordId);

		$listViewUrl = $moduleModel->getListViewUrl();
		$response = new Vtiger_Response();
		if($recordModel->isSystemTemplate()) {
			$response->setError('502', vtranslate('LBL_NO_PERMISSIONS_TO_DELETE_SYSTEM_TEMPLATE', $moduleName));
		} else if($ajaxDelete) {
			$response->setResult($listViewUrl);
		} else {
			header("Location: $listViewUrl");
		}
		return $response;
	}
}
