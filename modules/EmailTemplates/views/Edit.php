<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class EmailTemplates_Edit_View extends Vtiger_Index_View {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
    public function checkPermission($request) {
        $moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if(!$moduleModel->isActive()){
            return false;
        }
        return true;
    }
    
    public function preProcess(Vtiger_Request $request, $display = true) {
		$record = $request->get('record');
		if (!empty($record)) {
			$recordModel = EmailTemplates_Record_Model::getInstanceById($record);
			$viewer = $this->getViewer($request);
			$viewer->assign('RECORD', $recordModel);
		}
		parent::preProcess($request, $display);
	}

	public function setModuleInfo($request, $moduleModel) {
		$fieldsInfo = array();
		$basicLinks = array();
		$settingLinks = array();

		$moduleFields = $moduleModel->getFields();
		foreach($moduleFields as $fieldName => $fieldModel){
			$fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
		$viewer->assign('MODULE_BASIC_ACTIONS', $basicLinks);
		$viewer->assign('MODULE_SETTING_ACTIONS', $settingLinks);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			"libraries.jquery.ckeditor.ckeditor",
			"libraries.jquery.ckeditor.adapters.jquery",
			'modules.Vtiger.resources.CkEditor',
			'modules.Settings.Vtiger.resources.Vtiger',
			'modules.Settings.Vtiger.resources.Index',
			"~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js"
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Replacing keyword $site_URL with value
	 * @global type $site_URL
	 * @param type $content
	 * @return type
	 */
	public function replaceSiteURLByValue($content) {
		global $site_URL;
		return str_replace('{$site_URL}', $site_URL, $content);
	}

	public function initializeContents(Vtiger_Request $request, Vtiger_Viewer $viewer){
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if (!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = EmailTemplates_Record_Model::getInstanceById($record);
			$viewer->assign('MODE', '');
		} else if (!empty($record)) {
			$recordModel = EmailTemplates_Record_Model::getInstanceById($record);
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
		} else {
			$recordModel = new EmailTemplates_Record_Model();
			$viewer->assign('MODE', '');
			$recordModel->set('templatename', '');
			$recordModel->set('description', '');
			$recordModel->set('subject', '');
			$recordModel->set('body', '');
		}
		$recordModel->setModule('EmailTemplates');
		if (!$this->record) {
			//Default templates will be having {$site_URL} keyword 
			//so replacing keyword with value
			$body = $recordModel->get('body');
			$body = $this->replaceSiteURLByValue($body);
			$recordModel->set('body', $body);
			$this->record = $recordModel;
		}
		$emailTemplateModuleModel = $this->record->getModule();

		$companyModuleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();
		$moduleFields = $this->record->getEmailTemplateFields();
		$viewer->assign('RECORD', $this->record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign("COMPANY_MODEL", $companyModuleModel);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('ALL_FIELDS', $moduleFields);
		$viewer->assign('COMPANY_FIELDS', $emailTemplateModuleModel->getCompanyMergeTagsInfo());
		$viewer->assign('GENERAL_FIELDS', $emailTemplateModuleModel->getCustomMergeTags());
	}

	/**
	 * Funtioin to process the Edit view
	 * @param Vtiger_Request $request
	 */
	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$this->initializeContents($request,$viewer);
		// added to set the return values
		if ($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}
		$viewer->view('EditView.tpl', $moduleName);
	}

}
