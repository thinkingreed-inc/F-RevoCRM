<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once dirname(__FILE__) . '/../../api/ws/LoginAndFetchModules.php';

class Mobile_WS_UserInfo extends Mobile_WS_Controller {

	function process(Mobile_API_Request $request) {
		$current_user = $this->getActiveUser();
		
		$userinfo = array(
			'username' => $current_user->user_name,
			'id'       => $current_user->id,
			'first_name' => $current_user->first_name,
			'last_name' => $current_user->last_name,
			'email' => $current_user->email1,
			'time_zone' => $current_user->time_zone,
			'hour_format' => $current_user->hour_format,
			'date_format' => $current_user->date_format,
			'is_admin' => $current_user->is_admin,
			'call_duration' => $current_user->callduration,
			'other_event_duration' => $current_user->othereventduration,
		);
		
		$allVisibleModules = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		$appModulesMap = array();
		
		foreach($allVisibleModules as $app => $moduleModels) {
            $moduleInfo = array();
			foreach($moduleModels as $moduleModel) {
				$moduleInfo[] = array('name' => $moduleModel->get('name'), 'label'=>vtranslate($moduleModel->get('label'), $moduleModel->get('name')));
			}
			$appModulesMap[$app] = $moduleInfo;
		}
		
		$response = new Mobile_API_Response();
		$result['userinfo'] = $userinfo;
		$result['menus'] = $appModulesMap;
		$result['apps'] = Vtiger_MenuStructure_Model::getAppMenuList();
		$result['defaultApp'] = $this->_getDefaultApp();
		
		$response->setResult($result);
		return $response;
	}
	
	function _getDefaultApp() {
		return '';
	}
}