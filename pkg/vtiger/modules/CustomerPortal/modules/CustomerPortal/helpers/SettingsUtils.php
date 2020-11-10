<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_Settings_Utils {

	static function getDefaultMode($module) {
		global $adb;
		$sql = "SELECT vtiger_customerportal_fields.records_visible FROM vtiger_customerportal_fields
				INNER JOIN vtiger_tab ON vtiger_customerportal_fields.tabid= vtiger_tab.tabid WHERE vtiger_tab.name= ?";
		$result = $adb->pquery($sql, array($module));
		$IsVisible = $adb->query_result($result, 0, 'records_visible');

		if ($IsVisible) {
			return 'all';
		} else {
			return 'mine';
		}
	}

	static function getDefaultAssignee() {
		global $adb;
		$sql = "SELECT default_assignee FROM vtiger_customerportal_settings LIMIT 1";
		$result = $adb->pquery($sql);
		$default_assignee = $adb->query_result($result, 0, 'default_assignee');

		if (!empty($default_assignee)) {
			$userId = vtws_getWebserviceEntityId('Users', $default_assignee);
			return $userId;
		}
	}

}
