<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Project_ExportChart_View extends Vtiger_Index_View {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Reports_Module_Model::getInstance($moduleName);

		$record = $request->get('record');
		$reportModel = Reports_Record_Model::getCleanInstance($record);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	function preProcess(Vtiger_Request $request) {
		return false;
	}

	function postProcess(Vtiger_Request $request) {
		return false;
	}

	function process(Vtiger_request $request) {
		$this->GetPrintReport($request);
	}

	/**
	 * Function displays the report in printable format
	 * @param Vtiger_Request $request
	 */
	function GetPrintReport(Vtiger_Request $request) {
		$parentId = $request->get('record');
		$projectTasks = array();
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$projectTasks['tasks'] = $parentRecordModel->getProjectTasks();
		$projectTasks["selectedRow"] = 0;
		$projectTasks["canWrite"] = true;
		$projectTasks["canWriteOnParent"] = true;
		$viewer = $this->getViewer($request);
		$viewer->assign('PARENT_ID', $parentId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('PROJECT_TASKS', $projectTasks);
		$viewer->assign('TASK_STATUS_COLOR', $parentRecordModel->getStatusColors());
		$viewer->assign('USER_DATE_FORMAT', $currentUserModel->get('date_format'));
		$viewer->view('ShowChartPrintView.tpl', $moduleName);
	}
}