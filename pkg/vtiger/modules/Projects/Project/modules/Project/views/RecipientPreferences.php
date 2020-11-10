<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Project_RecipientPreferences_View extends Vtiger_RecipientPreferences_View {

	protected function getEmailFieldsInfo($moduleName) {
		$parentEmailFieldsInfo = parent::getEmailFieldsInfo($moduleName);
		$recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($moduleName);
		if ($recipientPrefModel) {
			$prefs = $recipientPrefModel->getPreferences();
		}
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$referenceFields = $moduleModel->getFieldsByType(array('reference', 'multireference'));
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
					$accesibleFields = array();
					$refModuleEmailFieldsPref = $prefs[$refModuleModel->getId()];

					//updating field model prefs
					foreach ($refModuleEmailFields as $fieldModel) {
						if (!$fieldModel->isViewable())
							continue;
						if ($refModuleEmailFieldsPref && in_array($fieldModel->getId(), $refModuleEmailFieldsPref)) {
							$fieldModel->set('isPreferred', true);
						}
						$accesibleFields[$fieldModel->getName()] = $fieldModel;
					}

					$refModuleEmailFields = $accesibleFields;
					if (!empty($refModuleEmailFields)) {
						$parentEmailFieldsInfo[$refModuleModel->getId()] = $refModuleEmailFields;
					}
				}
			}
		}
		return $parentEmailFieldsInfo;
	}

}
