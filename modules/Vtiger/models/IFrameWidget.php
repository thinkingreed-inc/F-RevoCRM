<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_IFrameWidget_Model extends Vtiger_Widget_Model {
	
	public function getTitle() {
		$data = $this->getData();
		if (isset($data['title']) && !empty($data['title'])) {
			return $data['title'];
		}
		return parent::getTitle();
	}
	
	public function getUrl() {
		$data = $this->getData();
		if (isset($data['url']) && !empty($data['url'])) {
			return $data['url'];
		}
		return 'https://www.example.com';
	}
	
	
	public function getData() {
		$dataString = $this->get('data');
		if (!empty($dataString)) {
			return Zend_Json::decode(decode_html($dataString));
		}
		return array();
	}
	
	public static function getUserInstance($widgetId) {
		$currentUser = Users_Record_Model::getCurrentUserModel();

		$db = PearDatabase::getInstance();
		
		// linkurl is needed for dashboard widget to load in Vtiger7
		$result = $db->pquery('SELECT vtiger_module_dashboard_widgets.*,vtiger_links.linkurl FROM vtiger_module_dashboard_widgets 
		INNER JOIN vtiger_links ON vtiger_links.linkid = vtiger_module_dashboard_widgets.linkid 
		WHERE linktype = ? AND vtiger_module_dashboard_widgets.id = ? AND vtiger_module_dashboard_widgets.userid = ?', 
		array('DASHBOARDWIDGET', $widgetId, $currentUser->getId()));
		
		$self = new self();
		if($db->num_rows($result)) {
			$row = $db->query_result_rowdata($result, 0);
			$self->setData($row);
		}
		return $self;
	}
}