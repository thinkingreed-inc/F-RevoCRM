<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Vtiger_AddIFrameWidget_View extends Vtiger_Index_View {

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		// Dashboard widget management is allowed for users with Home module access
		$request->set('custom_module', 'Home');
		$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'DetailView');
		
		return $permissions;
	}
	
	function process (Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		$viewer->assign('MODULE', $moduleName);
		
		$viewer->view('dashboards/AddIFrameWidget.tpl', $moduleName);
	}
}