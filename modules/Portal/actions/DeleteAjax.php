<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Portal_DeleteAjax_Action extends Vtiger_DeleteAjax_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}
	
    public function process(Vtiger_Request $request) {
        $recordId = $request->get('record');
        $module = $request->getModule();
        Portal_Module_Model::deletePortalRecord($recordId);
        
        $response = new Vtiger_Response();
		$response->setResult(array('message'=>  vtranslate('LBL_RECORD_DELETED_SUCCESSFULLY', $module)));
		$response->emit();
    }
}