<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchHistory extends CustomerPortal_FetchRecord {

	function process(CustomerPortal_API_Request $request) {
		global $adb;
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();
		$pageLimit = (int) $request->get('pageLimit');

		if (empty($pageLimit))
			$pageLimit = CustomerPortal_Config::$DEFAULT_PAGE_LIMIT;

		if ($current_user) {
			$module = $request->get('module');
			$recordId = $request->get('record');

			if (!CustomerPortal_Utils::isModuleActive($module)) {
				throw new Exception("History not available for this module", 1412);
				exit;
			}

			if (!ModTracker::isTrackingEnabledForModule($module)) {
				throw new Exception("Module not tracked for changes.", 1412);
				exit;
			}

			//Incase of ProjectTask and Milestones parent will be Project
			$parentId = $request->get('parentId');
			if (!empty($parentId)) {
				if (!$this->isRecordAccessible($parentId)) {
					throw new Exception("Parent record not accessible", 1412);
					exit;
				} else {
					$relatedIds = $this->relatedRecordIds($module, CustomerPortal_Utils::getRelatedModuleLabel($module), $parentId);
				}
			} else {
				$relatedIds = $this->relatedRecordIds($module, CustomerPortal_Utils::getRelatedModuleLabel($module));
			}
			if (empty($relatedIds)) {
				throw new Exception("No records found", 1412);
				exit;
			}
			$recordIds = array();

			if (!empty($recordId)) {
				if (!in_array($recordId, $relatedIds)) {
					throw new Exception("Record not accessible", 1412);
					exit;
				}

				$idComponents = explode("x", $recordId);
				$recordIds[] = $idComponents[1];
			} else {
				foreach ($relatedIds as $id) {
					$idComponents = explode("x", $id);
					$recordIds[] = $idComponents[1];
				}
			}

			$sql = 'SELECT vtiger_modtracker_basic.* FROM vtiger_modtracker_basic
		            INNER JOIN vtiger_crmentity ON vtiger_modtracker_basic.crmid = vtiger_crmentity.crmid WHERE 
                    vtiger_modtracker_basic.module = ? AND vtiger_crmentity.deleted = ? AND vtiger_modtracker_basic.crmid IN ('.generateQuestionMarks($recordIds).')
                    ORDER BY changedon DESC';

			$params = array();
			$params[] = $module;
			$params[] = '0';

			foreach ($recordIds as $id) {
				$params[] = $id;
			}

			$result = $adb->pquery($sql, $params);
			$recordValuesMap = array();
			$orderedIds = array();

			while ($row = $adb->fetch_array($result)) {
				$orderedIds[] = $row['id'];
				$whodid = vtws_history_entityIdHelper('Users', $row['whodid']);
				$crmid = vtws_history_entityIdHelper($module, $row['crmid']);
				$status = $row['status'];

				switch ($status) {
					case ModTracker::$UPDATED: $statuslabel = 'updated';
						break;
					case ModTracker::$DELETED: $statuslabel = 'deleted';
						break;
					case ModTracker::$CREATED: $statuslabel = 'created';
						break;
					case ModTracker::$RESTORED: $statuslabel = 'restored';
						break;
					case ModTracker::$LINK: $statuslabel = 'link';
						break;
					case ModTracker::$UNLINK: $statuslabel = 'unlink';
						break;
				}

				$item['modifieduser'] = $whodid;
				$item['id'] = $crmid;
				$item['modifiedtime'] = $row['changedon'];
				$item['values'] = array();
				$item['status'] = $statuslabel;

				$recordValuesMap[$row['id']] = $item;
			}

			$historyItems = array();

			if (!empty($orderedIds)) {
				$activeFields = CustomerPortal_Utils::getActiveFields($module);
				$sql = 'SELECT vtiger_modtracker_detail.* FROM vtiger_modtracker_detail';
				$sql .= ' WHERE id IN ('.generateQuestionMarks($orderedIds).') AND 
                          fieldname IN('.generateQuestionMarks($activeFields).') ORDER BY id DESC LIMIT ?,?';

				$params = $orderedIds;
				foreach ($activeFields as $field) {
					$params[] = $field;
				}
				$page = $request->get('page');

				if (empty($page)) {
					$params[] = 0;
				} else {
					$params[] = $page * $pageLimit;
				}
				$params[] = $pageLimit;

				$result = $adb->pquery($sql, $params);

				while ($row = $adb->fetch_array($result)) {
					$item = $recordValuesMap[$row['id']];

					// NOTE: For reference field values transform them to webservice id.
					$item['values'][$row['fieldname']] = array(
						'previous' => decode_html($row['prevalue']),
						'current' => decode_html($row['postvalue'])
					);


					$recordValuesMap[$row['id']] = $item;
				}

				// Group the values per basic-transaction
				foreach ($orderedIds as $id) {
					if (count($recordValuesMap[$id]['values']) > 0)
						$historyItems[] = $recordValuesMap[$id];
				}
			}

			if (!empty($historyItems))
				$this->resolveReferences($historyItems, $module, $current_user);
			$response->setResult(array('history' => $historyItems));
		} else {
			$response->setError(1404, "No permission to perform this operation.");
		}
		return $response;
	}

	protected function resolveReferences(&$items, $module, $user) {
		$ids = array();

		foreach ($items as $item) {
			$ids[] = $item['id'];
		}
		$labels = Vtiger_Util_Helper::fetchRecordLabelsForIds($ids);
		$describe = vtws_describe($module, $user);

		foreach ($items as &$item) {
			$modifiedUser = $this->fetchLabelForUserId($item['modifieduser'], $user);
			$modifiedUser['label'] = decode_html($modifiedUser['label']);
			$item['modifieduser'] = $modifiedUser;
			$item['label'] = decode_html($labels[$item['id']]);
			$values = $item['values'];



			foreach ($values as $field => $value) {
				if (CustomerPortal_Utils::isOwnerType($field, $describe)) {
					$previous = $value['previous'];
					$current = $value['current'];

					if (!empty($previous)) {
						$previousOwnerType = vtws_getOwnerType($previous);
						$previousWSId = vtws_getWebserviceEntityId($previousOwnerType, $previous);
						$value['previous'] = trim(vtws_getName($previousWSId, $user));
					}

					$currentOwnerType = vtws_getOwnerType($current);
					$currentWSId = vtws_getWebserviceEntityId($currentOwnerType, $current);
					$value['current'] = trim(vtws_getName($currentWSId, $user));
				}

				if (CustomerPortal_Utils::isReferenceType($field, $describe)) {
					$previous = $value['previous'];
					$current = $value['current'];

					if (!empty($previous)) {
						$value['previous'] = Vtiger_Util_Helper::getRecordName($previous, true);
					}
					$value['current'] = Vtiger_Util_Helper::getRecordName($current, true);
				}

				$value['previous'] = decode_html($value['previous']);
				$value['current'] = decode_html($value['current']);
				$values[$field] = $value;
			}
			$item['values'] = $values;
			unset($item);
		}
	}

	protected function fetchLabelForUserId($id, $user) {
		$label = trim(vtws_getName($id, $user));
		return array('value' => $id, 'label' => $label);
	}

}
