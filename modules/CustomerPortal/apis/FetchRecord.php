<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchRecord extends CustomerPortal_API_Abstract {

	protected function processRetrieve(CustomerPortal_API_Request $request) {
		global $adb;
		$parentId = $request->get('parentId');
		$recordId = $request->get('recordId');
		$module = VtigerWebserviceObject::fromId($adb, $recordId)->getEntityName();

		if (!CustomerPortal_Utils::isModuleActive($module)) {
			throw new Exception("Records not Accessible for this module", 1412);
			exit;
		}

		if (!empty($parentId)) {
			if (!$this->isRecordAccessible($parentId)) {
				throw new Exception("Parent record not Accessible", 1412);
				exit;
			}
			$relatedRecordIds = $this->relatedRecordIds($module, CustomerPortal_Utils::getRelatedModuleLabel($module), $parentId);

			if (!in_array($recordId, $relatedRecordIds)) {
				throw new Exception("Record not Accessible", 1412);
				exit;
			}
		} else {
			if (!$this->isRecordAccessible($recordId, $module)) {
				throw new Exception("Record not Accessible", 1412);
				exit;
			}
		}

		$fields = implode(',', CustomerPortal_Utils::getActiveFields($module));
		$sql = sprintf('SELECT %s FROM %s WHERE id=\'%s\';', $fields, $module, $recordId);
		$result = vtws_query($sql, $this->getActiveUser());
		return $result[0];
	}

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$record = $this->processRetrieve($request);

			$record = CustomerPortal_Utils::resolveRecordValues($record);
			$response->setResult(array('record' => $record));
		}
		return $response;
	}

}
