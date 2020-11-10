<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_ExportRecords extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();
		$db = PearDatabase::getInstance();
		if ($current_user) {
			$customerId = $this->getActiveCustomer()->id;
			$contactWebserviceId = vtws_getWebserviceEntityId('Contacts', $customerId);
			$accountId = $this->getParent($contactWebserviceId);
			$mode = $request->get('mode');
			$module = $request->get('module');
			$fieldsArray = $request->get('fields');
			$fieldsArray = Zend_Json::decode($fieldsArray);

			//validate module with portal settings
			if (!CustomerPortal_Utils::isModuleActive($module)) {
				throw new Exception("Module not accessible", 1412);
				exit;
			}

			//validate filter fields with portal settings
			$activeFields = CustomerPortal_Utils::getActiveFields($module);
			if ($fieldsArray !== null) {
				foreach ($fieldsArray as $key => $value) {
					if (!in_array($key, $activeFields)) {
						throw new Exception($key." is not accessible.", 1412);
						exit;
					}
				}
			}

			$fields = $fields = implode(',', $activeFields);
			if (empty($mode)) {
				$mode = CustomerPortal_Settings_Utils::getDefaultMode($module);
			}
			if ($mode == 'all' && in_array($module, array('Products', 'Services'))) {
				$countSql = sprintf('SELECT count(*) FROM %s;', $module);
				$countResult = vtws_query($countSql, $current_user);
				$count = $countResult[0]['count'];
			} else {
				//setting parentId based on mode
				$parentId = null;
				if ($mode == 'mine') {
					$parentId = $contactWebserviceId;
				} else if ($mode == 'all') {
					if (!empty($accountId)) {
						if (CustomerPortal_Settings_Utils::getDefaultMode($module) == 'all')
							$parentId = $accountId;
						else
							$parentId = $contactWebserviceId;
					}
					else {
						$parentId = $contactWebserviceId;
					}
				}
				$groupConditionsBy = $request->get('groupConditions');
				if (empty($groupConditionsBy))
					$groupConditionsBy = 'AND';
				$countSql = sprintf('SELECT count(*) FROM %s', $module);

				if (!empty($fieldsArray)) {
					$countSql = sprintf('SELECT count(*) FROM %s WHERE ', $module);
					foreach ($fieldsArray as $key => $value) {
						$countSql.= $key.'=\''.$value."' ".$groupConditionsBy." ";
					}
					$countSql = CustomerPortal_Utils::str_replace_last($groupConditionsBy, '', $countSql);
				}
				$moduleLabel = CustomerPortal_Utils::getRelatedModuleLabel($module);
				$countResult = vtws_query_related($countSql, $parentId, $moduleLabel, $current_user);
				$count = $countResult[0]['count'];
			}
			//vtws_query gives max of 100 records per request.loop for records if more than 100
			$pageLimit = 100;
			$loopCount = $count / $pageLimit;
			$records = array();

			for ($i = 0; $i < $loopCount; $i++) {
				if (!empty($fieldsArray)) {
					$sql = sprintf('SELECT %s FROM %s WHERE ', $fields, $module);
					foreach ($fieldsArray as $key => $value) {
						$sql.= $key.'=\''.$value."' ".$groupConditionsBy." ";
					}
					$sql = CustomerPortal_Utils::str_replace_last($groupConditionsBy, '', $sql);
				} else {
					$sql = sprintf('SELECT %s FROM %s', $fields, $module);
				}
				$filterClause = sprintf(" LIMIT %s,%s", $i * $pageLimit, $pageLimit);
				if ($mode == 'all' && in_array($module, array('Products', 'Services'))) {
					$result = vtws_query($sql.' '.$filterClause.';', $current_user);
				} else {
					$result = vtws_query_related($sql, $parentId, $moduleLabel, $current_user, $filterClause);
				}
				// process result
				foreach ($result as $key => $recordValues) {
					$records[] = CustomerPortal_Utils::resolveRecordValues($recordValues);
				}
			}
			$response->setResult($records);
			return $response;
		}
	}

}
