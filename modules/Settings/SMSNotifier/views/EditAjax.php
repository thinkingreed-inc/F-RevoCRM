<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_SMSNotifier_EditAjax_View extends Settings_Vtiger_IndexAjax_View {

	public function process(Vtiger_Request $request) {
		$providerName = $request->get('provider');
		$qualifiedModuleName = $request->getModule(false);

		$providerModel = SMSNotifier_Provider_Model::getInstance($providerName);
		$templateName = Settings_SMSNotifier_ProviderField_Model::getEditFieldTemplateName($providerName);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', new Vtiger_Base_Model());
		$viewer->assign('PROVIDER_MODEL', Settings_SMSNotifier_ProviderField_Model::getFieldInstanceByProvider($providerModel));
		$viewer->assign('QUALIFIED_MODULE_NAME', $qualifiedModuleName);
		$viewer->view($templateName, $qualifiedModuleName);
	}
}
