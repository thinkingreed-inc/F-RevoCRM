<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchModules extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		$current_user = $this->getActiveUser();
		$response = new CustomerPortal_API_Response();
		global $adb;

		if ($current_user) {
			$result = array();
			$customerId = vtws_getWebserviceEntityId('Contacts', $this->getActiveCustomer()->id);
			$accountId = $this->getParent($customerId);
			$user_id = CustomerPortal_Settings_Utils::getDefaultAssignee();
			$result['contact_id'] = array('value' => $customerId, 'label' => Vtiger_Util_Helper::fetchRecordLabelForId($customerId));

			if (!empty($accountId)) {
				$result['account_id'] = array('value' => $accountId, 'label' => Vtiger_Util_Helper::fetchRecordLabelForId($accountId));
			}

			$result['user_id'] = array('value' => $user_id, 'label' => decode_html(trim(vtws_getName($user_id, $current_user))));
			$sql = "SELECT vtiger_relatedlists.label, vtiger_customerportal_tabs.tabid, vtiger_customerportal_tabs.sequence,
                    vtiger_customerportal_tabs.createrecord,vtiger_customerportal_tabs.editrecord,vtiger_customerportal_fields.records_visible 
                    FROM vtiger_customerportal_tabs INNER JOIN vtiger_tab on vtiger_tab.tabid = vtiger_customerportal_tabs.tabid 
                    and vtiger_tab.presence=? INNER JOIN vtiger_relatedlists ON vtiger_customerportal_tabs.tabid =vtiger_relatedlists.related_tabid 
                    INNER JOIN vtiger_customerportal_fields ON vtiger_customerportal_fields.tabid = vtiger_customerportal_tabs.tabid WHERE   
                    vtiger_customerportal_tabs.visible =? GROUP BY vtiger_customerportal_tabs.tabid ORDER BY vtiger_customerportal_tabs.sequence ASC;";
			$sqlResult = $adb->pquery($sql, array(0, 1));
			$num_rows = $adb->num_rows($sqlResult);

			$modules = array('types' => array(), 'information' => array());
			for ($i = 0; $i < $num_rows; $i++) {
				$moduleId = $adb->query_result($sqlResult, $i, 'tabid');
				$moduleName = Vtiger_Functions::getModuleName($moduleId);
				if (!Vtiger_Runtime::isRestricted('modules', $moduleName)) {
					$modules['types'][] = $moduleName;
					$modules['information'][$moduleName] = array(
						'name' => $moduleName,
						'label' => $adb->query_result($sqlResult, $i, 'label'),
						'uiLabel' => decode_html(vtranslate($moduleName, $moduleName)),
						'order' => $adb->query_result($sqlResult, $i, 'sequence'),
						'create' => $adb->query_result($sqlResult, $i, 'createrecord'),
						'edit' => $adb->query_result($sqlResult, $i, 'editrecord'),
						'recordvisibility' => $adb->query_result($sqlResult, $i, 'records_visible')
					);
				}
			}

			$result['modules'] = $modules;
			$response->setResult($result);
			return $response;
		}
	}

}
