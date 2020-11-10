<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'modules/MailManager/runtime/Response.php';

class MailManager_List_View extends MailManager_Abstract_View {

	static $controllers = array(
			'mainui' =>	array('class' => 'MailManager_MainUI_View'),
			'folder' => array('class' => 'MailManager_Folder_View'),
			'mail'   => array('class' => 'MailManager_Mail_View'),
			'relation'=>array('class' => 'MailManager_Relation_View'),
			'settings'=>array('class' => 'MailManager_Settings_View'),
			'search'  =>array('class' => 'MailManager_Search_View'),
	);

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
				"libraries.jquery.ckeditor.ckeditor",
				"libraries.jquery.ckeditor.adapters.jquery",
				"modules.Vtiger.resources.CkEditor",
				"modules.Emails.resources.MassEdit",
				"modules.MailManager.resources.List"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function process(Vtiger_Request $request) {
		$request = MailManager_Request::getInstance($request);

		if (!$request->has('_operation')) {
			return $this->processRoot($request);
		}
		$operation = $request->getOperation();
		$controllerInfo = self::$controllers[$operation];
		$controller = new $controllerInfo['class'];

		// Making sure to close the open connection
		if ($controller) $controller->closeConnector();
        if($controller->validateRequest($request)) {
            $response = $controller->process($request);
            if ($response) $response->emit();
        }
		unset($request);
		unset($response);
	}

	public function processRoot(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('index.tpl', $moduleName);
	}
}