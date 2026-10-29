<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Reports_SaveAjax_View extends Vtiger_IndexAjax_View {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}
	
	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$record = $request->get('record');
		$reportModel = Reports_Record_Model::getInstanceById($record);

		$reportModel->setModule('Reports');

		$reportModel->set('advancedFilter', $request->get('advanced_filter'));

		$page = $request->get('page');
		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', Reports_Detail_View::REPORT_LIMIT);

		if ($mode === 'save') {
			$reportModel->saveAdvancedFilters();
			$reportData = $reportModel->getReportData($pagingModel);
			$data = $reportData['data'];
		} else if ($mode === 'generate') {
			$reportData = $reportModel->generateData($pagingModel);
			$data = $reportData['data'];
		}
		$calculation = $reportModel->generateCalculationData();

		// Detail.php と同じ補助変数を渡す。未設定だと ReportContents.tpl の in_array() が PHP 8 で TypeError になる（上流 Issue #1837）
		$groupingModules = array();
		$groupingList = ReportRun::getInstance($record)->_groupinglist;
		if (is_array($groupingList)) {
			foreach ($groupingList as $groupingKey => $groupingValues) {
				$moduleField = explode(':', $groupingKey)[2];
				$groupingModules[] = explode('_', $moduleField)[0];
			}
		}
		// もとからアンダーバーを含む項目ラベル（グループ化列名の復元に使う）
		$includedUnderbar = array();
		global $adb;
		$result = $adb->pquery('SELECT DISTINCT fieldlabel FROM vtiger_field WHERE fieldlabel LIKE "%\_%"');
		$rows = $adb->num_rows($result);
		for ($i = 0; $i < $rows; $i++) {
			$includedUnderbar[] = $adb->query_result($result, $i, 'fieldlabel');
		}
		$viewer->assign('GROUPING_MODULES', $groupingModules);
		$viewer->assign('INCLUDED_UNDERBAR_LABELS', $includedUnderbar);

		$viewer->assign('PRIMARY_MODULE', $reportModel->getPrimaryModule());
		$viewer->assign('CALCULATION_FIELDS', $calculation);
		$viewer->assign('DATA', $data);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('NEW_COUNT',$reportData['count']);
		$viewer->assign('REPORT_RUN_INSTANCE', ReportRun::getInstance($record));
		$viewer->assign('REPORT_MODEL', $reportModel);
		$viewer->view('ReportContents.tpl', $moduleName);
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

}
