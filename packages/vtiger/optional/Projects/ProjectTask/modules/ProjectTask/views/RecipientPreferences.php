<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProjectTask_RecipientPreferences_View extends Project_RecipientPreferences_View {

	public function process(Vtiger_Request $request) {
		$sourceModule = $request->getModule();
		$emailFieldsInfo = $this->getEmailFieldsInfo('Project');
		$viewer = $this->getViewer($request);
		$viewer->assign('EMAIL_FIELDS_LIST', $emailFieldsInfo);
		$viewer->assign('MODULE', $sourceModule);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		echo $viewer->view('RecipientPreferences.tpl', 'Project', true);
	}

	protected function getEmailFieldsInfo() {
		$emailFieldsInfo = array();
		$emailFieldsList = array();
		$recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance('ProjectTask');
		if ($recipientPrefModel) {
			$prefs = $recipientPrefModel->getPreferences();
		}

		//for project task module
		$sourceModuleModel = Vtiger_Module_Model::getInstance('ProjectTask');
		$emailFields = $sourceModuleModel->getFieldsByType('email');
		$emailFieldsPref = $prefs[$sourceModuleModel->getId()];

		foreach ($emailFields as $field) {
			if ($field->isViewable()) {
				if ($emailFieldsPref && in_array($field->getId(), $emailFieldsPref)) {
					$field->set('isPreferred', true);
				}
				$emailFieldsList[$field->getName()] = $field;
			}
		}

		if (!empty($emailFieldsList)) {
			$emailFieldsInfo[$sourceModuleModel->getId()] = $emailFieldsList;
		}

		//for parent module
		$emailFieldsList = array();
		$sourceModuleModel = Vtiger_Module_Model::getInstance('Project');
		$emailFields = $sourceModuleModel->getFieldsByType('email');
		$emailFieldsPref = $prefs[$sourceModuleModel->getId()];

		foreach ($emailFields as $field) {
			if ($field->isViewable()) {
				if ($emailFieldsPref && in_array($field->getId(), $emailFieldsPref)) {
					$field->set('isPreferred', true);
				}
				$emailFieldsList[$field->getName()] = $field;
			}
		}

		if (!empty($emailFieldsList)) {
			$emailFieldsInfo[$sourceModuleModel->getId()] = $emailFieldsList;
		}

		$referenceFields = $sourceModuleModel->getFieldsByType(array('reference', 'multireference'));
		foreach ($referenceFields as $fieldModel) {
			if ($fieldModel && $fieldModel->isViewable()) {
				$referenceList = $fieldModel->getReferenceList();
				if (in_array('Users', $referenceList))
					continue;
				foreach ($referenceList as $refModuleName) {
					$refModuleModel = Vtiger_Module_Model::getInstance($refModuleName);
					$refModuleEmailFields = $refModuleModel->getFieldsByType('email');
					if (empty($refModuleEmailFields))
						continue;

					$accessibleFields = array();
					$refModuleEmailFieldsPref = $prefs[$refModuleModel->getId()];

					//updating field model prefs
					foreach ($refModuleEmailFields as $fieldModel) {
						if (!$fieldModel->isViewable())
							continue;
						if ($refModuleEmailFieldsPref && in_array($fieldModel->getId(), $refModuleEmailFieldsPref)) {
							$fieldModel->set('isPreferred', true);
						}
						$accessibleFields[$fieldModel->getName()] = $fieldModel;
					}

					$refModuleEmailFields = $accessibleFields;
					if (!empty($refModuleEmailFields)) {
						$emailFieldsInfo[$refModuleModel->getId()] = $refModuleEmailFields;
					}
				}
			}
		}
		return $emailFieldsInfo;
	}

}
