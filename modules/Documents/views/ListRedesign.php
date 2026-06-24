<?php

class Documents_ListRedesign_View extends Vtiger_Index_View {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, $display);
	}

	/**
	 * 新UIではReact側に登録ボタンがあるため、ヘッダーの旧登録ドロップダウンを非表示にする
	 */
	public function setModuleInfo($request, $moduleModel) {
		parent::setModuleInfo($request, $moduleModel);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_BASIC_ACTIONS', array());
		$viewer->assign('MODULE_SETTING_ACTIONS', array());
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = Users_Record_Model::getCurrentUserModel();

		$folderId = $request->get('folder_id', '');
		$viewMode = $request->get('view_mode', 'list');

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENT_USER_ID', $currentUser->getId());
		$viewer->assign('FOLDER_ID', $folderId);
		$viewer->assign('VIEW_MODE', $viewMode);

		$viewer->view('ListRedesign.tpl', $moduleName);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScripts = parent::getHeaderScripts($request);
		return $headerScripts;
	}
}
