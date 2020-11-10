<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchRecords extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$customerId = $this->getActiveCustomer()->id;
			$contactWebserviceId = vtws_getWebserviceEntityId('Contacts', $customerId);
			$accountId = $this->getParent($contactWebserviceId);
			$mode = $request->get('mode');
			$module = $request->get('module');
			$moduleLabel = $request->get('moduleLabel');
			$fieldsArray = $request->get('fields');
			$orderBy = $request->get('orderBy');
			$order = $request->get('order');
			$activeFields = CustomerPortal_Utils::getActiveFields($module);

			if (empty($orderBy)) {
				$orderBy = 'modifiedtime';
			} else {
				if (!in_array($orderBy, $activeFields)) {
					throw new Exception("sort by $orderBy not allowed", 1412);
					exit;
				}
			}

			if (empty($order)) {
				$order = 'DESC';
			} else {
				if (!in_array(strtoupper($order), array("DESC", "ASC"))) {
					throw new Exception("Invalid sorting order", 1412);
					exit;
				}
			}
			$fieldsArray = Zend_Json::decode($fieldsArray);
			$groupConditionsBy = $request->get('groupConditions');
			$page = $request->get('page');
			if (empty($page))
				$page = 0;

			$pageLimit = $request->get('pageLimit');

			if (empty($pageLimit))
				$pageLimit = CustomerPortal_Config::$DEFAULT_PAGE_LIMIT;

			if (empty($groupConditionsBy))
				$groupConditionsBy = 'AND';

			if (!CustomerPortal_Utils::isModuleActive($module)) {
				throw new Exception("Module not accessible", 1412);
				exit;
			}

			if (empty($mode)) {
				$mode = CustomerPortal_Settings_Utils::getDefaultMode($module);
			}
			$count = null;

			if ($fieldsArray !== null) {
				foreach ($fieldsArray as $key => $value) {
					if (!in_array($key, $activeFields)) {
						throw new Exception($key." is not accessible.", 1412);
						exit;
					}
				}
			}
			$fields = implode(',', $activeFields);

			if ($module == 'Faq') {
				if (!empty($fieldsArray)) {
					$countSql = "SELECT COUNT(*) FROM Faq WHERE faqstatus='Published' AND ";
					$sql = sprintf('SELECT %s FROM Faq WHERE faqstatus=\'Published\' AND ', $fields);

					foreach ($fieldsArray as $key => $value) {
						$countSql.= $key.'=\''.$value."' ".$groupConditionsBy." ";
						$sql.= $key.'=\''.$value."' ".$groupConditionsBy." ";
					}
					$countSql = CustomerPortal_Utils::str_replace_last($groupConditionsBy, ';', $countSql);
					$sql = CustomerPortal_Utils::str_replace_last($groupConditionsBy, '', $sql);
				} else {
					$countSql = "SELECT COUNT(*) FROM Faq WHERE faqstatus='Published';";
					$sql = sprintf('SELECT %s FROM Faq WHERE faqstatus=\'Published\'', $fields);
				}
				$countResult = vtws_query($countSql, $current_user);
				$count = $countResult[0]['count'];

				$sql = sprintf('%s ORDER BY %s %s LIMIT %s,%s ;', $sql, $orderBy, $order, ($page * $pageLimit), $pageLimit);
				$result = vtws_query($sql, $current_user);
			} else if ($module == 'Contacts') {
				$result = vtws_query(sprintf("SELECT %s FROM %s WHERE id='%s';", $fields, $module, $contactWebserviceId), $current_user);
			} else if ($module == 'Accounts') {
				if (!empty($accountId))
					$result = vtws_query(sprintf("SELECT %s FROM %s WHERE id='%s';", $fields, $module, $accountId), $current_user);
			} else {
				$relatedId = null;
				$defaultMode = CustomerPortal_Settings_Utils::getDefaultMode($module);
				if (!empty($fieldsArray)) {
					$countSql = sprintf('SELECT count(*) FROM %s WHERE ', $module);
					$sql = sprintf('SELECT %s FROM %s WHERE ', $fields, $module);

					foreach ($fieldsArray as $key => $value) {
						$countSql.= $key.'=\''.$value."' ".$groupConditionsBy." ";
						$sql.= $key.'=\''.$value."' ".$groupConditionsBy." ";
					}

					$countSql = CustomerPortal_Utils::str_replace_last($groupConditionsBy, '', $countSql);
					$sql = CustomerPortal_Utils::str_replace_last($groupConditionsBy, '', $sql);
				} else {
					$countSql = sprintf('SELECT count(*) FROM %s', $module);
					$sql = sprintf('SELECT %s FROM %s', $fields, $module);
				}
				if ($mode == 'mine') {
					$relatedId = $contactWebserviceId;
					$countResult = vtws_query_related($countSql, $relatedId, $moduleLabel, $current_user);
					$count = $countResult[0]['count'];

					$limitClause = sprintf('ORDER BY %s %s LIMIT %s,%s', $orderBy, $order, ($page * $pageLimit), $pageLimit);
					$result = vtws_query_related($sql, $relatedId, $moduleLabel, $current_user, $limitClause);
				} else if ($mode == 'all') {
					if (in_array($module, array('Products', 'Services'))) {
						$countSql = sprintf('SELECT count(*) FROM %s;', $module);
						$sql = sprintf('SELECT %s FROM %s', $fields, $module);
						$limitClause = sprintf('ORDER BY %s %s LIMIT %s,%s;', $orderBy, $order, ($page * $pageLimit), $pageLimit);
						$sql = $sql.' '.$limitClause;
						$result = vtws_query($sql, $current_user);
						$countResult = vtws_query($countSql, $current_user);
						$count = $countResult[0]['count'];
					} else {
						if (!empty($accountId)) {
							if ($defaultMode == 'all')
								$relatedId = $accountId;
							else
								$relatedId = $contactWebserviceId;
						}
						else {
							$relatedId = $contactWebserviceId;
						}

						$countResult = vtws_query_related($countSql, $relatedId, $moduleLabel, $current_user);
						$count = $countResult[0]['count'];

						$limitClause = sprintf('ORDER BY %s %s LIMIT %s,%s', $orderBy, $order, ($page * $pageLimit), $pageLimit);
						$result = vtws_query_related($sql, $relatedId, $moduleLabel, $current_user, $limitClause);
					}
				}
			}

			foreach ($result as $key => $recordValues) {
				$result[$key] = CustomerPortal_Utils::resolveRecordValues($recordValues);
			}

			$response->setResult($result);
			$response->addToResult('count', $count);
			return $response;
		}
	}

}
