<?php

class Documents_DetailRedesign_View extends Vtiger_Index_View {

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
	 * 新UIではReact側にアクションボタンがあるため、ヘッダーの旧ボタンを非表示にする
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
		$recordId = $request->get('record');

		if (empty($recordId)) {
			throw new AppException('Record ID is required');
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD_ID', $recordId);

		$viewer->view('DetailRedesign.tpl', $moduleName);
	}
}
