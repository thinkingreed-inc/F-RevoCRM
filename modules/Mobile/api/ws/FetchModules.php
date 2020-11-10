<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once dirname(__FILE__) . '/../../api/ws/LoginAndFetchModules.php';

class Mobile_WS_FetchModules extends Mobile_WS_LoginAndFetchModules {

	function requireLogin() {
		return true;
	}
	
	function process(Mobile_API_Request $request) {
		$current_user = $this->getActiveUser();
		
		$response = new Mobile_API_Response();
		$result = array();
		$result['modules'] = $this->getListing($current_user);
		$response->setResult($result);
		return $response;
	}
}