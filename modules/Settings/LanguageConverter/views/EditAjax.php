<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Settings_LanguageConverter_EditAjax_View extends Settings_Vtiger_IndexAjax_View {

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = Settings_LanguageConverter_Record_Model::getInstanceById($recordId);
		$viewer = $this->getViewer($request);

		$userModuleModel = new Users_Module_Model();
		$languageList = $userModuleModel->getLanguagesList();

		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('LANGUAGE_LIST', $languageList);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('ALL_MODULES', Settings_LayoutEditor_Module_Model::getEntityModulesList());
		$viewer->view('EditAjax.tpl', $qualifiedModuleName);
	}

}