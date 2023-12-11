<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Leads_Mapping_Model extends Settings_Vtiger_Module_Model {

	var $name = 'Leads';

	/**
	 * Function to get detail view url of this model
	 * @return <String> url
	 */
	public function getDetailViewUrl() {
		return 'index.php?parent='. $this->getParentName() .'&module='. $this->getName() .'&view=MappingDetail';
	}

	/**
	 * Function to get edit view url of this model
	 * @return <String> url
	 */
	public function getEditViewUrl() {
		return 'index.php?parent='. $this->getParentName() .'&module='. $this->getName() .'&view=MappingEdit';
	}

	/**
	 * Function to get delete url of this mapping model
	 * @return <String> url
	 */
	public function getMappingDeleteUrl() {
		return 'index.php?parent='. $this->getParentName() .'&module='. $this->getName() .'&action=MappingDelete';
	}

	/**
	 * Function to get headers for detail view
	 * @return <Array> headers list
	 */
	public function getHeaders() {
		return array('Leads' => 'Leads', 'Type' => 'Type', 'Accounts' => 'Accounts', 'Contacts' => 'Contacts', 'Potentails' => 'Potentials');
	}

	/**
	 * Function to get list of detail view link models
	 * @return <Array> list of detail view link models <Vtiger_Link_Model>
	 */
	public function getDetailViewLinks() {
		return array(Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_EDIT',
				'linkurl' => 'javascript:Settings_LeadMapping_Js.triggerEdit("'. $this->getEditViewUrl() .'")',
				'linkicon' => ''
				)));
	}

	/**
	 * Function to get list of mapping link models
	 * @return <Array> list of mapping link models <Vtiger_Link_Model>
	 */
	public function getMappingLinks() {
		return array(Vtiger_Link_Model::getInstanceFromValues(array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => 'javascript:Settings_LeadMapping_Js.triggerDelete(event,"'. $this->getMappingDeleteUrl() .'")',
				'linkicon' => ''
				)));
	}

	/**
	 * Function to get mapping details
	 * @return <Array> list of mapping details
	 */
	public function getMapping($editable = false) {
		if (!$this->mapping) {
			$db = PearDatabase::getInstance();
			$query = 'SELECT * FROM vtiger_convertleadmapping';
			if ($editable) {
				$query .= ' WHERE editable = 1';
			}

			$result = $db->pquery($query, array());
			$numOfRows = $db->num_rows($result);
            $mapping = array();
			for ($i=0; $i<$numOfRows; $i++) {
				$rowData = $db->query_result_rowdata($result, $i);
				$mapping[$rowData['cfmid']] = $rowData;
			}

			$finalMapping = $fieldIdsList = array();
			foreach ($mapping as $mappingDetails) {
				array_push($fieldIdsList, $mappingDetails['leadfid'], $mappingDetails['accountfid'], $mappingDetails['contactfid'], $mappingDetails['potentialfid']);
			}
            $fieldLabelsList = array();
            if(!empty($fieldIdsList)){
                $fieldLabelsList = $this->getFieldsInfo(array_unique($fieldIdsList));
            }
			foreach ($mapping as $mappingId => $mappingDetails) {
				$finalMapping[$mappingId] = array(
						'editable'	=> $mappingDetails['editable'],
						'Leads'		=> $fieldLabelsList[$mappingDetails['leadfid']],
						'Accounts'	=> $fieldLabelsList[$mappingDetails['accountfid']],
						'Contacts'	=> $fieldLabelsList[$mappingDetails['contactfid']],
						'Potentials'=> $fieldLabelsList[$mappingDetails['potentialfid']]
				);
			}

			$this->mapping = $finalMapping;
		}
		return $this->mapping;
	}

	/**
	 * Function to get fields info
	 * @param <Array> list of field ids
	 * @return <Array> list of field info
	 */
	public function getFieldsInfo($fieldIdsList) {
		$leadModel = Vtiger_Module_Model::getInstance($this->getName());
		$leadId = $leadModel->getId();

		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT fieldid, fieldlabel, uitype, typeofdata, fieldname, tablename, tabid FROM vtiger_field WHERE fieldid IN ('. generateQuestionMarks($fieldIdsList). ')', $fieldIdsList);
		$numOfRows = $db->num_rows($result);

		$fieldLabelsList = array();
		for ($i=0; $i<$numOfRows; $i++) {
			$rowData = $db->query_result_rowdata($result, $i);

			$fieldInfo = array('id' => $rowData['fieldid'], 'label' => $rowData['fieldlabel']);
			if ($rowData['tabid'] === $leadId) {
				$fieldModel = Settings_Leads_Field_Model::getCleanInstance();
				$fieldModel->set('uitype', $rowData['uitype']);
				$fieldModel->set('typeofdata', $rowData['typeofdata']);
				$fieldModel->set('name', $rowData['fieldname']);
				$fieldModel->set('table', $rowData['tablename']);

				$fieldInfo['fieldDataType'] = $fieldModel->getFieldDataType();
			}

			$fieldLabelsList[$rowData['fieldid']] = $fieldInfo;
		}
		return $fieldLabelsList;
	}

	/**
	 * Function to save the mapping info
	 * @param <Array> $mapping info
	 */
	public function save($mapping) {
		$db = PearDatabase::getInstance();
		$deleteMappingsList = $updateMappingsList = $createMappingsList = array();
		foreach ($mapping as $mappingDetails) {
			$mappingId = $mappingDetails['mappingId'];
			if ($mappingDetails['lead']) {
				if ($mappingId) {
					if ((array_key_exists('deletable', $mappingDetails)) || (!$mappingDetails['account'] && !$mappingDetails['contact'] && !$mappingDetails['potential'])) {
						$deleteMappingsList[] = $mappingId;
					} else {
						if ($mappingDetails['account'] || $mappingDetails['contact'] || $mappingDetails['potential']) {
							$updateMappingsList[] = $mappingDetails;
						}
					}
				} else {
					if ($mappingDetails['account'] || $mappingDetails['contact'] || $mappingDetails['potential']) {
						$createMappingsList[] = $mappingDetails;
					}
				}
			}
		}

		if ($deleteMappingsList) {
			$db->pquery('DELETE FROM vtiger_convertleadmapping WHERE editable = 1 AND cfmid IN ('. generateQuestionMarks($deleteMappingsList) .')', $deleteMappingsList);
		}

		if ($createMappingsList) {
			$insertQuery = 'INSERT INTO vtiger_convertleadmapping(leadfid, accountfid, contactfid, potentialfid) VALUES ';

			$count = php7_count($createMappingsList);
            $params = array();
			for ($i=0; $i<$count; $i++) {
				$mappingDetails = $createMappingsList[$i];
				$insertQuery .= '(?, ?, ?, ?)';
                array_push($params, $mappingDetails['lead'], $mappingDetails['account'], $mappingDetails['contact'], $mappingDetails['potential']);
				if ($i !== $count-1) {
					$insertQuery .= ', ';
				}
			}
			$db->pquery($insertQuery, $params);
		}

		if ($updateMappingsList) {
			$leadQuery		= ' SET leadfid = CASE ';
			$accountQuery	= ' accountfid = CASE ';
			$contactQuery	= ' contactfid = CASE ';
			$potentialQuery	= ' potentialfid = CASE ';
			$paramArray = $leadParamArray = $accountParamArray = $contactParamArray = $potentialParamArray = array();
			foreach ($updateMappingsList as $mappingDetails) {
				$mappingId = $mappingDetails['mappingId'];
				$leadQuery		.= ' WHEN cfmid = ? THEN ? ';
				array_push($leadParamArray, $mappingId);
				array_push($leadParamArray, $mappingDetails['lead']);
				$accountQuery	.= ' WHEN cfmid = ? THEN ? ';
				array_push($accountParamArray, $mappingId);
				array_push($accountParamArray, $mappingDetails['account']);
				$contactQuery	.= ' WHEN cfmid = ? THEN ? ';
				array_push($contactParamArray, $mappingId);
				array_push($contactParamArray, $mappingDetails['contact']);
				$potentialQuery	.= ' WHEN cfmid = ? THEN ? ';
				array_push($potentialParamArray, $mappingId);
				array_push($potentialParamArray, $mappingDetails['potential']);
			}
			$leadQuery		.= ' ELSE leadfid END ';
			$accountQuery	.= ' ELSE accountfid END ';
			$contactQuery	.= ' ELSE contactfid END ';
			$potentialQuery .= ' ELSE potentialfid END ';
			$paramArray = array_merge($paramArray, $leadParamArray);
			$paramArray = array_merge($paramArray, $accountParamArray);
			$paramArray = array_merge($paramArray, $contactParamArray);
			$paramArray = array_merge($paramArray, $potentialParamArray);
			array_push($paramArray, 1);
			$db->pquery("UPDATE vtiger_convertleadmapping $leadQuery, $accountQuery, $contactQuery, $potentialQuery WHERE editable = ?", $paramArray);
		}
	}

	/**
	 * Function to get restricted field ids list
	 * @return <Array> list of field ids
	 */
	public static function getRestrictedFieldIdsList() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_convertleadmapping WHERE editable = ?', array(0));
		$numOfRows = $db->num_rows($result);

		$restrictedIdsList = array();
		for ($i=0; $i<$numOfRows; $i++) {
			$rowData = $db->query_result_rowdata($result, $i);
			if ($rowData['accountfid']) {
				$restrictedIdsList[] = $rowData['accountfid'];
			}
			if ($rowData['contactfid']) {
				$restrictedIdsList[] = $rowData['contactfid'];
			}
			if ($rowData['potentialfid']) {
				$restrictedIdsList[] = $rowData['potentialfid'];
			}
		}
		return $restrictedIdsList;
	}

	/**
	 * Function to get mapping supported modules list
	 * @return <Array>
	 */
	public static function getSupportedModulesList() {
		return array('Accounts', 'Contacts', 'Potentials');
	}

	/**
	 * Function to get instance
	 * @param <Boolean> true/false
	 * @return <Settings_Leads_Mapping_Model>
	 */
	public static function getInstance($editable = false) {
		$instance = new self();
		$instance->getMapping($editable);
		return $instance;
	}

	/**
	 * Function to get instance
	 * @return <Settings_Leads_Mapping_Model>
	 */
	public static function getCleanInstance() {
		return new self();
	}

	/**
	 * Function to delate the mapping
	 * @param <Array> $mappingIdsList
	 */
	public static function deleteMapping($mappingIdsList) {
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_convertleadmapping WHERE cfmid IN ('. generateQuestionMarks($mappingIdsList). ')', $mappingIdsList);
	}
}