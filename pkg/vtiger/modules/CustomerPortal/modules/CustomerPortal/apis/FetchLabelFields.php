<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchLabelFields extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		$current_user = $this->getActiveUser();
		$response = new CustomerPortal_API_Response();
		global $adb;

		if ($current_user) {
			$sql = "SELECT tabid FROM vtiger_customerportal_tabs WHERE visible=? ";
			$sqlResult = $adb->pquery($sql, array(1));
			$num_rows = $adb->num_rows($sqlResult);
			$result = array();

			for ($i = 0; $i < $num_rows; $i++) {
				$moduleId = $adb->query_result($sqlResult, $i, 'tabid');
				$module = Vtiger_Functions::getModuleName($moduleId);
				$describe = vtws_describe($module, $current_user);
				$labelFields = explode(',', $describe['labelFields']);
				$result[] = array($module => $labelFields);
			}
		}
		$response->setResult($result);
		return $response;
	}

}
