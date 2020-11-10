<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchShortcuts extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		global $adb;
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$shortcuts = array();
			$sql = "SELECT shortcuts FROM vtiger_customerportal_settings LIMIT 1";
			$result = $adb->pquery($sql, array());
			$shortcutsJSON = $adb->query_result($result, 0, 'shortcuts');
			$data = Zend_Json::decode(decode_html($shortcutsJSON));

			foreach ($data as $module => $value) {
				$operations = array();
				if (is_array($value)) {
					foreach ($value as $key1 => $value1) {
						if ($value1 != 0)
							$operations[] = $key1;
					}

					if (!empty($operations) && CustomerPortal_Utils::isModuleActive($module)) {
						$shortcuts[] = array($module => $operations);
					}
				}
			}
			$isHelpDeskRecordCreatable = CustomerPortal_Utils::isModuleRecordCreatable('HelpDesk');
			foreach ($shortcuts as $shortcutArray => $shortcutValues) {
				foreach ($shortcutValues as $module => $values) {
					if ($module == 'HelpDesk' && !$isHelpDeskRecordCreatable) {
						$createShortCutKey = array_search('LBL_CREATE_TICKET', $values);
						unset($values[$createShortCutKey]);
						$values = array_values($values);
						$shortcutValues['HelpDesk'] = $values;
						$shortcuts[$shortcutArray] = $shortcutValues;
					}
				}
			}
			$response->setResult(array('shortcuts' => $shortcuts));
		}
		return $response;
	}

}
