<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProjectTask_MassActionAjax_View extends Project_MassActionAjax_View {

	protected function getEmailFieldsInfo(Vtiger_Request $request) {
		$sourceModule = $request->getModule();
		$emailFieldsInfo = parent::getEmailFieldsInfo($request);
		//get parent email fields and their reference email fields
		$moduleModel = Vtiger_Module_Model::getInstance('Project');
		$recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($sourceModule);

		if ($recipientPrefModel)
			$recipientPrefs = $recipientPrefModel->getPreferences();
		$moduleEmailPrefs = $recipientPrefs[$moduleModel->getId()];
		$emailAndRefFields = $moduleModel->getFieldsByType(array('email', 'reference'));
		$accesibleFields = array();
		$referenceFieldValues = array();

		foreach ($emailAndRefFields as $field) {
			$fieldName = $field->getName();
			if ($field->isViewable()) {
				if ($moduleEmailPrefs && in_array($field->getId(), $moduleEmailPrefs)) {
					$field->set('isPreferred', true);
				}
				$accesibleFields[$fieldName] = $field;
			}
		}

		$allEmailFields = array();
		$moduleEmailFields = $moduleModel->getFieldsByType(array('email'));
		foreach ($moduleEmailFields as $moduleEmailField) {
			if ($moduleEmailField->isViewable()) {
				if ($moduleEmailPrefs && in_array($moduleEmailField->getId(), $moduleEmailPrefs)) {
					$moduleEmailField->set('isPreferred', true);
				}
				$allEmailFields[$sourceModule][$moduleEmailField->getFieldName()] = $moduleEmailField;
			}
		}

		$referenceFields = $moduleModel->getFieldsByType(array('reference'));
		foreach ($referenceFields as $referenceField) {
			$referenceModules = $referenceField->getReferenceList();
			$refModuleName = $referenceModules[0];
			if (empty($refModuleName) || $refModuleName == 'Users') {
				continue;
			}
			$refModule = Vtiger_Module_Model::getInstance($refModuleName);
			if ($refModule) {
				$refModuleEmailFields = $refModule->getFieldsByType(array('email'));
				if (empty($refModuleEmailFields)) {
					continue;
				}
				$refModuleEmailPrefs = $recipientPrefs[$refModule->getId()];
				foreach ($refModuleEmailFields as $refModuleEmailField) {
					if ($refModuleEmailField->isViewable()) {
						$refModuleEmailField->set('baseRefField', $referenceField->getFieldName());
						if ($refModuleEmailPrefs && in_array($refModuleEmailField->getId(), $refModuleEmailPrefs)) {
							$refModuleEmailField->set('isPreferred', true);
						}
						$allEmailFields[$refModuleName][$refModuleEmailField->getFieldName()] = $refModuleEmailField;
					}
				}
			}
		}

		if (count($accesibleFields) > 0) {
			$projectTaskIds = $this->getRecordsListFromRequest($request);
			//get parent project records
			$projectIds = $this->getProjectIds($projectTaskIds);

			global $current_user;
			$queryGen = new QueryGenerator($moduleModel->getName(), $current_user);
			$selectFields = array_keys($accesibleFields);
			$queryGen->setFields($selectFields);
			$query = $queryGen->getQuery();
			$query = $query.' AND crmid IN ('.generateQuestionMarks($projectIds).')';
			$emailOptout = $moduleModel->getField('emailoptout');
			if ($emailOptout) {
				$query .= ' AND '.$emailOptout->get('column').' = 0';
			}

			$db = PearDatabase::getInstance();
			$result = $db->pquery($query, $projectIds);
			$num_rows = $db->num_rows($result);

			if ($num_rows > 0) {
				for ($i = 0; $i < $num_rows; $i++) {
					$emailFieldsList = array();
					foreach ($accesibleFields as $field) {
						$fieldValue = $db->query_result($result, $i, $field->get('column'));
						if (!empty($fieldValue)) {
							if (in_array($field->getFieldDataType(), array('reference'))) {
								$referenceFieldValues[$projectTaskIds[$i]][] = $fieldValue;
							} else {
								$emailFieldsList[$fieldValue] = $field;
							}
						}
					}
					if (!empty($emailFieldsList)) {
						$emailFieldsInfo[$projectTaskIds[$i]][$moduleModel->getName()] = $emailFieldsList;
					}
				}
			}
		}

		if (!empty($referenceFieldValues)) {
			foreach ($referenceFieldValues as $recordId => $refRecordIds) {
				foreach ($refRecordIds as $refRecordId) {
					$refModuleName = Vtiger_Functions::getCRMRecordType($refRecordId);
					if (empty($refModuleName) || $refModuleName == 'Users')
						continue;
					$refModuleModel = Vtiger_Module_Model::getInstance($refModuleName);
					if (!$refModuleModel || !$refModuleModel->isActive() || !Users_Privileges_Model::isPermitted($refModuleModel->getName(), 'DetailView'))
						continue;
					$refModuleEmailPrefs = $recipientPrefs[$refModuleModel->getId()];
					$refModuleEmailFields = $refModuleModel->getFieldsByType('email');
					if (empty($refModuleEmailFields))
						continue;

					$accesibleFields = array();
					foreach ($refModuleEmailFields as $fieldModel) {
						if (!$fieldModel->isViewable())
							continue;
						if ($refModuleEmailPrefs && in_array($fieldModel->getId(), $refModuleEmailPrefs)) {
							$fieldModel->set('isPreferred', true);
						}
						$accesibleFields[$fieldModel->getName()] = $fieldModel;
					}
					$refModuleEmailFields = $accesibleFields;
					$qGen = new QueryGenerator($refModuleName, $current_user);
					$qGen->setFields(array_keys($refModuleEmailFields));
					$query = $qGen->getQuery();
					$query .= " AND crmid = $refRecordId";

					$params = array();
					if ($refModuleModel->getField('emailoptout')) {
						$query .= ' AND '.$refModuleModel->basetable.'.emailoptout = ?';
						$params[] = 0;
					}
					$result = $db->pquery($query, $params);
					$numRows = $db->num_rows($result);
					$emailFieldList = array();
					if ($numRows > 0) {
						foreach ($refModuleEmailFields as $emailFieldName => $emailField) {
							$emailValue = $db->query_result($result, 0, $emailFieldName);
							if (!empty($emailValue)) {
								$emailFieldList[$emailValue] = $emailField;
							}
						}
					}
					if (!empty($emailFieldList)) {
						$emailFieldsInfo[$recordId][$refModuleName] = $emailFieldList;
					}
				}
			}
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORDS_COUNT', count($projectTaskIds));
		if ($recipientPrefModel && !empty($recipientPrefs)) {
			$viewer->assign('RECIPIENT_PREF_ENABLED', true);
		}
		$viewer->assign('EMAIL_FIELDS', $allEmailFields);
		return $emailFieldsInfo;
	}

	protected function getProjectIds($taskIds = array()) {
		$projectIds = array();
		if (!empty($taskIds)) {
			$db = PearDatabase::getInstance();
			$sql = 'SELECT projectid FROM vtiger_projecttask INNER JOIN vtiger_crmentity  ON vtiger_projecttask.projecttaskid = vtiger_crmentity.crmid
						WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentity.crmid IN ('.generateQuestionMarks($taskIds).')';

			$params = $taskIds;
			$result = $db->pquery($sql, $params);
			$numRows = $db->num_rows($result);
			if ($numRows > 0) {
				for ($i = 0; $i < $numRows; $i++) {
					$projectIds[] = $db->query_result($result, $i, 'projectid');
				}
			}
		}
		return $projectIds;
	}

	protected function isPreferencesNeedToBeUpdated(Vtiger_Request $request) {
		$parentStatus = parent::isPreferencesNeedToBeUpdated($request);
		if (!$parentStatus) {
			$recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($request->getModule());
			if (!$recipientPrefModel)
				return $parentStatus;
			$prefs = $recipientPrefModel->getPreferences();
			$moduleModel = Vtiger_Module_Model::getInstance('Project');
			$refFields = $moduleModel->getFieldsByType(array('reference'));
			foreach ($refFields as $refField) {
				if ($refField && $refField->isViewable()) {
					$referenceList = $refField->getReferenceList();
					foreach ($referenceList as $moduleName) {
						if ($moduleName !== 'Users') {
							$refModuleModel = Vtiger_Module_Model::getInstance($moduleName);
							if (!$prefs[$refModuleModel->getId()])
								continue;
							$moduleEmailPrefs = $prefs[$refModuleModel->getId()];
							foreach ($moduleEmailPrefs as $fieldId) {
								$field = Vtiger_Field_Model::getInstance($fieldId, $refModuleModel);
								if ($field) {
									if (!$field->isActiveField()) {
										$parentStatus = true;
									}
								} else {
									$parentStatus = true;
								}
							}
						}
					}
				}
			}
		}
		return $parentStatus;
	}

}
