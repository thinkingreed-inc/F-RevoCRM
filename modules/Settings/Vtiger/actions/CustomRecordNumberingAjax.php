<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Vtiger_CustomRecordNumberingAjax_Action extends Settings_Vtiger_Index_Action {

	public function __construct() {
		parent::__construct();
		$this->exposeMethod('saveModuleCustomNumberingData');
	}

	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		$qualifiedModuleName = $request->getModule(false);
		$sourceModule = $request->get('sourceModule');

		if(!$sourceModule) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $qualifiedModuleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * 自動採番項目の設定を保存する
	 * @param Vtiger_Request $request
	 */
	public function saveModuleCustomNumberingData(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		$sourceModule = $request->get('sourceModule');

		$moduleModel = Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance($sourceModule);
		$config = Settings_Vtiger_CustomRecordNumberingModule_Model::getConfigFromParams($request->getAll());
		$errors = $moduleModel->saveFieldConfig((string) $request->get('field'), $config);

		$response = new Vtiger_Response();
		if (empty($errors)) {
			$response->setResult(vtranslate('LBL_SUCCESSFULLY_UPDATED', $qualifiedModuleName));
		} else {
			$response->setError(0, Settings_Vtiger_CustomRecordNumberingModule_Model::getErrorMessage($errors));
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
