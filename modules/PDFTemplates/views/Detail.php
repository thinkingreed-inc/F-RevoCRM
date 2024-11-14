<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class PDFTemplates_Detail_View extends Vtiger_Index_View {

	public function checkPermission($request) {
        $moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if($moduleModel->isActive()) {
			return parent::checkPermission($request);
		}
		
		throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
    }

	function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request, false);

		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		if(!$this->record){
			$this->record = PDFTemplates_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);

		$viewer->assign('MODULE_MODEL', $this->record->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);

		$viewer->assign('IS_EDITABLE', $this->record->getRecord()->isEditable($moduleName));
		$viewer->assign('IS_DELETABLE', $this->record->getRecord()->isDeletable($moduleName));

		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'));
		$linkModels = $this->record->getSideBarLinks($linkParams);
		$viewer->assign('QUICK_LINKS', $linkModels);

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('DEFAULT_RECORD_VIEW', $currentUserModel->get('default_record_view'));
		$viewer->assign('NO_PAGINATION', true);

		if($display) {
			$this->preProcessDisplay($request);
		}
	}

	function preProcessTplName(Vtiger_Request $request) {
		return 'DetailViewPreProcess.tpl';
	}

	function process(Vtiger_Request $request) {
		global $is_headlesschrome;
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$viewer = $this->getViewer($request);

		$recordModel = PDFTemplates_Record_Model::getInstanceById($record);
		$recordModel->setModule($moduleName);

		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		if ($request->isAjax()) {
			$viewer->assign('MODULE_MODEL', $recordModel->getModule());
		}

		$viewer->view('DetailViewFullContents.tpl', $moduleName);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$jsFileNames = array(
			'modules.Vtiger.resources.Detail',
			'modules.PDFTemplates.resources.Detail',
			'modules.Settings.Vtiger.resources.Index',
			"~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js"

		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
