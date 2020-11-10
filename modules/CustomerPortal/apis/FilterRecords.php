<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FilterRecords extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$customerId = $this->getActiveCustomer()->id;
			$contactWebserviceId = vtws_getWebserviceEntityId('Contacts', $customerId);
			$module = $request->get('module');

			if (!CustomerPortal_Utils::isModuleActive($module)) {
				throw new Exception("Module not accessible", 1412);
				exit;
			}

			$moduleLabel = $request->get('moduleLabel');
			$mode = CustomerPortal_Settings_Utils::getDefaultMode($module);
			$orderByfield = $request->get('field');
			$order = $request->get('orderBy');
			$limit = $request->get('limit');

			if (empty($limit))
				$limit = CustomerPortal_Config::$DEFAULT_PAGE_LIMIT;

			$activeFields = CustomerPortal_Utils::getActiveFields($module);

			if (!empty($orderByfield) && !in_array($orderByfield, $activeFields)) {
				throw new Exception("filter by field not accessible", 1412);
				exit;
			}

			$fields = implode(',', $activeFields);
			$relatedId = $contactWebserviceId;

			if ($mode == 'all') {
				$accountId = $this->getParent($contactWebserviceId);
				if (!empty($accountId))
					$relatedId = $accountId;
			}

			$sql = sprintf("SELECT %s FROM %s", $fields, $module);
			$filterClause = null;

			if (!empty($orderByfield) && !empty($order)) {
				$filterClause.= ' ORDER BY '.$orderByfield." ".$order;
			}

			if (!empty($limit)) {
				$filterClause.= ' LIMIT '.$limit;
			}

			$result = vtws_query_related($sql, $relatedId, $moduleLabel, $this->getActiveUser(), $filterClause);

			foreach ($result as $key => $recordValues) {
				$result[$key] = CustomerPortal_Utils::resolveRecordValues($recordValues);
			}

			$response->setResult($result);
			return $response;
		}
	}

}
