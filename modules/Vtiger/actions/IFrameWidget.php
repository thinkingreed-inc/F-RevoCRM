<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_IFrameWidget_Action extends Vtiger_Action_Controller {

	function __construct() {
		$this->exposeMethod('IFrameWidgetCreate');
	}

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		if($request->get('module') != 'Dashboard'){
			$request->set('custom_module', 'Dashboard');
			$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'DetailView');
		}else{
			$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		}

		return $permissions;
	}

	function process(Vtiger_Request $request) {
		$mode = $request->getMode();

		if($mode){
			$this->invokeExposedMethod($mode,$request);
		}
	}

	function IFrameWidgetCreate(Vtiger_Request $request){
		$adb = PearDatabase::getInstance();

		$moduleName = $request->getModule();
		$userModel = Users_Record_Model::getCurrentUserModel();
		$linkId = $request->get('linkId');
		$iframeWidgetTitle = $request->get('iframeWidgetTitle');
		$iframeWidgetUrl = $request->get('iframeWidgetUrl');
		$tabId = $request->get("tab");
		$userid = $userModel->getId();

		// Added for Vtiger7
		if(empty($tabId)){
			$dasbBoardModel = Vtiger_DashBoard_Model::getInstance($moduleName);
			$defaultTab = $dasbBoardModel->getUserDefaultTab($userModel->getId());
			$tabId = $defaultTab['id'];
		}

		if (empty($iframeWidgetUrl)) {
			$result = array();
			$result['success'] = false;
			$result['message'] = vtranslate('LBL_INVALID_URL', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$parsedUrl = parse_url(trim($iframeWidgetUrl));
		if ($parsedUrl === false || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
			$result = array();
			$result['success'] = false;
			$result['message'] = vtranslate('LBL_INVALID_URL', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$scheme = strtolower($parsedUrl['scheme']);
		if (!in_array($scheme, array('http', 'https'))) {
			$result = array();
			$result['success'] = false;
			$result['message'] = vtranslate('LBL_INVALID_URL', $moduleName);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}

		$sanitizedUrl = vtlib_purify($iframeWidgetUrl);
		$sanitizedTitle = vtlib_purify($iframeWidgetTitle);

		$dataValue = array();
		$dataValue['title'] = $sanitizedTitle ? $sanitizedTitle : 'iframe Widget';
		$dataValue['url'] = $sanitizedUrl;

		$data = Zend_Json::encode((object) $dataValue);

		$query="INSERT INTO vtiger_module_dashboard_widgets(linkid, userid, filterid, title, data,dashboardtabid) VALUES(?,?,?,?,?,?)";
		$params= array($linkId,$userid,0,$dataValue['title'],$data,$tabId);
		$adb->pquery($query, $params);
		$id = $adb->getLastInsertID();

		$result = array();
		$result['success'] = TRUE;
		$result['widgetId'] = $id;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();

	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}