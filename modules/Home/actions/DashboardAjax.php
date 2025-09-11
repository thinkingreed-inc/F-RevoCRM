<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Home_DashboardAjax_Action extends Vtiger_Action_Controller {

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		
		if ($mode == 'addWidget') {
			$this->addWidget($request);
		}
	}
	
	public function addWidget(Vtiger_Request $request) {
		try {
			$linkId = $request->get('linkid');
			$widgetName = $request->get('name');
			$title = $request->get('title');
			$url = $request->get('url');
			$tabId = $request->get('tab', 1); // Default to tab 1
			
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$db = PearDatabase::getInstance();
			
			// Validate required parameters
			if (!$linkId || !$widgetName) {
				throw new Exception('Missing required parameters');
			}
			
			// Prepare initial data based on widget type
			$data = array();
			if ($widgetName == 'IFrameWidget') {
				$data['title'] = $title ? $title : 'IFrame Widget';
				$data['url'] = $url ? $url : 'https://www.example.com';
			}
			
			$jsonData = Zend_Json::encode((object) $data);
			
			// Add widget to database
			$result = $db->pquery('INSERT INTO vtiger_module_dashboard_widgets (linkid, userid, filterid, title, data, dashboardtabid, position, size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 
				array($linkId, $currentUser->getId(), NULL, $data['title'], $jsonData, $tabId, NULL, NULL));
			
			$response = new Vtiger_Response();
			if ($result) {
				$widgetId = $db->getLastInsertID();
				$response->setResult(array(
					'success' => true, 
					'message' => 'Widget added successfully',
					'widgetId' => $widgetId
				));
			} else {
				$response->setResult(array('success' => false, 'message' => 'Failed to add widget to database'));
			}
		} catch (Exception $e) {
			$response = new Vtiger_Response();
			$response->setResult(array(
				'success' => false, 
				'message' => 'Error: ' . $e->getMessage()
			));
		}
		$response->emit();
	}
}