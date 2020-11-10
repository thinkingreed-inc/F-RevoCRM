<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Reports_ExportReport_View extends Vtiger_View_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('GetPrintReport');
		$this->exposeMethod('GetXLS');
		$this->exposeMethod('GetCSV');
	}

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}

	function preProcess(Vtiger_Request $request) {
		return false;
	}

	function postProcess(Vtiger_Request $request) {
		return false;
	}

	function process(Vtiger_request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	/**
	 * Function exports the report in a Excel sheet
	 * @param Vtiger_Request $request
	 */
	function GetXLS(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$reportModel = Reports_Record_Model::getInstanceById($recordId);
        $this->checkReportModulePermission($request);
        $reportModel->set('advancedFilter', $request->get('advanced_filter'));
		$reportModel->getReportXLS($request->get('source'));
	}

	/**
	 * Function exports report in a CSV file
	 * @param Vtiger_Request $request
	 */
	function GetCSV(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$reportModel = Reports_Record_Model::getInstanceById($recordId);
        $this->checkReportModulePermission($request);
        $reportModel->set('advancedFilter', $request->get('advanced_filter'));
		$reportModel->getReportCSV($request->get('source'));
	}

	/**
	 * Function displays the report in printable format
	 * @param Vtiger_Request $request
	 */
	function GetPrintReport(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$recordId = $request->get('record');
		$reportModel = Reports_Record_Model::getInstanceById($recordId);
        $this->checkReportModulePermission($request);
        $reportModel->set('advancedFilter', $request->get('advanced_filter'));
		$printData = $reportModel->getReportPrint();

		$viewer->assign('REPORT_NAME', $reportModel->getName());
		$viewer->assign('PRINT_DATA', $printData['data'][0]);
		$viewer->assign('TOTAL', $printData['total']);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('ROW', $printData['data'][1]);

		$viewer->view('PrintReport.tpl', $moduleName);
	}
    
    function checkReportModulePermission(Vtiger_Request $request){
        $viewer = $this->getViewer($request);
        $recordId = $request->get('record');
		$reportModel = Reports_Record_Model::getInstanceById($recordId);
        $primaryModule = $reportModel->getPrimaryModule();
		$secondaryModules = $reportModel->getSecondaryModules();
        $modulesList = array($primaryModule);
        if($secondaryModules){
            if(stripos($secondaryModules, ':') >= 0){
                $secmodules = explode(':', $secondaryModules);
                $modulesList = array_merge($modulesList, $secmodules);
            }else{
                array_push($modulesList, $secondaryModules);
            }
        }
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userPrivilegesModel = Users_Privileges_Model::getInstanceById($currentUser->getId());
        foreach ($modulesList as $checkModule) {
            $moduleInstance = Vtiger_Module_Model::getInstance($checkModule);
            $permission = $userPrivilegesModel->hasModulePermission($moduleInstance->getId());
            if(!$permission) {
                $viewer->assign('MODULE', $primaryModule);
                $viewer->assign('MESSAGE', vtranslate('LBL_PERMISSION_DENIED'));
                $viewer->view('OperationNotPermitted.tpl', $primaryModule);
                exit;
            }
        }
    }
}