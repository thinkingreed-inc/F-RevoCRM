<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchAnnouncement extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		global $adb;
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$sql = "SELECT announcement FROM vtiger_customerportal_settings LIMIT 1";
			$result = $adb->pquery($sql, array());
			$announcement = $adb->query_result($result, 0, 'announcement');
			$response->setResult(array('announcement' => $announcement));
		}
		return $response;
	}

}
