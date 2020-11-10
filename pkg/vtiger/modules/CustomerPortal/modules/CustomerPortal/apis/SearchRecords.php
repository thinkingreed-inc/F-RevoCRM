<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_SearchRecords extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();
		global $adb;

		if ($current_user) {
			$searchKey = $request->get('searchKey');
			$orderBy = 'modifiedtime';

			if (!empty($searchKey)) {
				$portalActiveModules = CustomerPortal_Utils::getActiveModules();

				if (!empty($portalActiveModules)) {
					$searchResult = array();

					foreach ($portalActiveModules as $key => $module) {
						$moduleModel = Vtiger_Module_Model::getInstance($module);
						//Restricting search to Contact related modules
						if (in_array($module, array("Faq", "ProjectTask", "ProjectMilestone"))) {
							// do nothing
						} else {
							$activeFields = CustomerPortal_Utils::getActiveFields($module);

							// unset date, time fields as search will fail on them
							foreach ($activeFields as $key => $field) {
								$field_model = Vtiger_Field_Model::getInstance($field, $moduleModel);

								if (in_array($field_model->getFieldDataType(), array('date', 'datetime', 'time', 'currency'))) {
									unset($activeFields[$key]);
								}
							}

							$describe = vtws_describe($module, $current_user);
							$labelFields = $describe['labelFields'];
							if ($module == 'Documents') {
								$labelFields = 'notes_title';
							}
							if ($module == 'HelpDesk') {
								$labelFields = 'ticket_title';
							}

							//generate query using Query Generator
							$queryGenerator = new QueryGenerator($module, $current_user);
							$labelFieldsArray = explode(',', $labelFields);
							$queryGenerator->setFields($labelFieldsArray);

							foreach ($activeFields as $fieldName) {
								$queryGenerator->addCondition($fieldName, $searchKey, 'c', 'OR');
							}
							$query = $queryGenerator->getQuery();

							$moduleLabel = CustomerPortal_Utils::getRelatedModuleLabel($module);
							$relatedRecordWSIds = $this->relatedRecordIds($module, $moduleLabel);
							$relatedRecordCRMIds = array();

							//extract crm ids from webservice ids
							if (!empty($relatedRecordWSIds)) {
								foreach ($relatedRecordWSIds as $wsId) {
									$idParts = explode('x', $wsId);
									$relatedRecordCRMIds[] = $idParts[1];
								}
							}

							$whereClause = "crmid IN ('".implode("','", $relatedRecordCRMIds)."')";
							if (stripos($query, 'WHERE') == false) {
								$query .= " WHERE ".$whereClause;
							} else {
								$queryParts = explode('WHERE', $query);
								// adding crmid into query select fields list 
								$subParts = explode('FROM', $queryParts[0]);
								$queryParts[0] = $subParts[0].",vtiger_crmentity.crmid FROM ".$subParts[1];
								$query = $queryParts[0]." WHERE ".$whereClause;
								$query .= " AND (".$queryParts[1].")";
							}
							$query = sprintf('%s ORDER BY %s %s', $query, $orderBy, 'DESC');
							$queryResult = $adb->pquery($query, array());
							$result = array();

							$num_rows = $adb->num_rows($queryResult);
							if ($num_rows > 0) {
								$result['uiLabel'] = decode_html(vtranslate($moduleLabel, $module));
								$result['labelField'] = $labelFields;
							}

							// Parse result and construct response
							while ($row = $adb->fetch_array($queryResult)) {
								$record = array();
								$crmId = $row['crmid'];
								$recordWSId = vtws_getWebserviceEntityId($module, $crmId);
								$record['id'] = $recordWSId;
								$label = '';
								foreach ($labelFieldsArray as $labelField) {
									$fieldModel = Vtiger_Field_Model::getInstance($labelField, $moduleModel);
									$label.= $row[$fieldModel->column]." ";
								}
								$record['label'] = decode_html($label);
								$result[] = $record;
							}

							$searchResult[$module] = $result;
						}
					}
				}
			} else {
				throw new Exception("Search key is empty", 1412);
			}
			$response->setResult($searchResult);
			return $response;
		}
	}

}
