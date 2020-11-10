<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Google_Authenticate_View extends Vtiger_Index_View {

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		$recordPermission = Users_Privileges_Model::isPermitted($moduleName, 'index');
		if(!$recordPermission) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		return true;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$oauth2Connector = new Google_Oauth2_Connector($moduleName);
		$oauth2Connector->authorize();
	}

	public function validateRequest(Vtiger_Request $request) {
		/* Ignore check - as referer could be CRM or Google Accounts */
	}
}
