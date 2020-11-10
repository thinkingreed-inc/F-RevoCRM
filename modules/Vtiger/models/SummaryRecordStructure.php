<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger Summary View Record Structure Model
 */
class Vtiger_SummaryRecordStructure_Model extends Vtiger_DetailRecordStructure_Model {

	private $picklistValueMap = array(); 
	private $picklistRoleMap = array();

	/**
	 * Function to get the values in stuctured format
	 * @return <array> - values in structure array('block'=>array(fieldinfo));
	 */
	public function getStructure() {
		$currentUsersModel = Users_Record_Model::getCurrentUserModel();
		$summaryFieldsList = $this->getModule()->getSummaryViewFieldsList();

		//For Calendar module getSummaryViewFieldsList() returns empty array. On changing that API Calendar related tab header
		//field changes. In related tab if summary fields are empty, it is depending of getRelatedListFields(). So added same here.
		if(empty($summaryFieldsList)) {
			$fieldModuleModel = $this->getModule();
			if($fieldModuleModel->getName() == 'Events') {
				$fieldModuleModel = Vtiger_Module_Model::getInstance('Calendar');
			}
			$summaryFieldsListNames = $fieldModuleModel->getRelatedListFields();
			foreach($summaryFieldsListNames as $summaryFieldsListName) {
				$summaryFieldsList[$summaryFieldsListName] = $fieldModuleModel->getField($summaryFieldsListName);
			}
		}

		$recordModel = $this->getRecord();
		$blockSeqSortSummaryFields = array();
		if ($summaryFieldsList) {
			foreach ($summaryFieldsList as $fieldName => $fieldModel) {
				if($fieldModel->isViewableInDetailView()) {
					$fieldModel->set('fieldvalue', $recordModel->get($fieldName));
					$blockSequence = $fieldModel->block->sequence;
					if(!$currentUsersModel->isAdminUser() && ($fieldModel->getFieldDataType() == 'picklist' || $fieldModel->getFieldDataType() == 'multipicklist')) {
						$this->setupAccessiblePicklistValueList($fieldName);
					}
					$blockSeqSortSummaryFields[$blockSequence]['SUMMARY_FIELDS'][$fieldName] = $fieldModel;
				}
			}
		}
		$summaryFieldModelsList = array();
		ksort($blockSeqSortSummaryFields);
		foreach($blockSeqSortSummaryFields as $blockSequence => $summaryFields){
			$summaryFieldModelsList = array_merge_recursive($summaryFieldModelsList , $summaryFields);
		}
		return $summaryFieldModelsList;
	}

	public function setupAccessiblePicklistValueList($name) {
			$db = PearDatabase::getInstance();
			$currentUsersModel = Users_Record_Model::getCurrentUserModel();
			$roleId = $currentUsersModel->getRole();
			$isRoleBased = vtws_isRoleBasedPicklist($name);
			$this->picklistRoleMap[$name] = $isRoleBased;
			if ($this->picklistRoleMap[$name]) {
				$this->picklistValueMap[$name] = getAssignedPicklistValues($name,$roleId, $db);
			}
	}
}