<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_DownloadAttachment_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), $moduleName);
		$recordModel->downloadFile($request->get('attachmentid'));
	}

}
