<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PDFTemplates_ShowTemplateContent_Action extends Vtiger_Action_Controller {

	function __construct() {
		$this->exposeMethod('getContent');
	}

    public function checkPermission($request) {
        $moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if(!$moduleModel->isActive()){
            return false;
        }
        return true;
    }
    
	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
		} else {
			throw new Exception("Invalid Mode");
		}
	}

	public function getContent(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$recordId = $request->get('record');
		$recordModel = PDFTemplates_Record_Model::getInstanceById($recordId);
		// 画像の URL の変数（$module-cf_xxx#src$）を src に持つ img は、画面では表示できないため代わりの画像にする
		require_once 'modules/Documents/utils/FieldFileTemplate.php';
		$content = Documents_FieldFileTemplate::replaceSrcVariablesForPreview((string) decode_html($recordModel->get('body')));
		$response->setResult(array("content" => $content));
		$response->emit();
	}

}
