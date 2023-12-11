<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Reports_Detail_View extends Vtiger_Index_View {

	protected $reportData;
	protected $calculationFields;
	protected $count;

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		$record = $request->get('record');
		$reportModel = Reports_Record_Model::getCleanInstance($record);
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		$owner = $reportModel->get('owner');
		$sharingType = $reportModel->get('sharingtype');

		$isRecordShared = true;
		if(($currentUserPriviligesModel->id != $owner) && $sharingType == "Private"){
			$isRecordShared = $reportModel->isRecordHasViewAccess($sharingType);
		}
		if(!$isRecordShared) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	const REPORT_LIMIT = 500;

	function preProcess(Vtiger_Request $request, $display=true) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$detailViewModel = Reports_DetailView_Model::getInstance($moduleName, $recordId);
		$reportModel = $detailViewModel->getRecord();
		$viewer->assign('REPORT_NAME', $reportModel->getName());
		parent::preProcess($request);

		$page = $request->get('page');
		$reportModel->setModule('Reports');

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', self::REPORT_LIMIT);

		$reportData = $reportModel->getReportData($pagingModel);
		$this->reportData = $reportData['data'];
		$this->calculationFields = $reportModel->getReportCalulationData();

		$this->count = $reportData['count'];

		$primaryModule = $reportModel->getPrimaryModule();
		$secondaryModules = $reportModel->getSecondaryModules();
        $modulesList = array($primaryModule);
        if(!empty($secondaryModules)){
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

		$detailViewLinks = $detailViewModel->getDetailViewLinks();

		// Advanced filter conditions
		$viewer->assign('SELECTED_ADVANCED_FILTER_FIELDS', $reportModel->transformToNewAdvancedFilter());
		$viewer->assign('PRIMARY_MODULE', $primaryModule);

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($reportModel);
		$primaryModuleRecordStructure = $recordStructureInstance->getPrimaryModuleRecordStructure();
		$secondaryModuleRecordStructures = $recordStructureInstance->getSecondaryModuleRecordStructure();

		//TODO : We need to remove "update_log" field from "HelpDesk" module in New Look
		// after removing old look we need to remove this field from crm
		if($primaryModule == 'HelpDesk'){
			foreach($primaryModuleRecordStructure as $blockLabel => $blockFields){
				foreach($blockFields as $field => $object){
					if($field == 'update_log'){
						unset($primaryModuleRecordStructure[$blockLabel][$field]);
					}
				}
			}
		}
		if(!empty($secondaryModuleRecordStructures)){
			foreach($secondaryModuleRecordStructures as $module => $structure){
				if($module == 'HelpDesk'){
					foreach($structure as $blockLabel => $blockFields){
						foreach($blockFields as $field => $object){
							if($field == 'update_log'){
								unset($secondaryModuleRecordStructures[$module][$blockLabel][$field]);
							}
						}
					}
				}
			}
		}
		// End

		$viewer->assign('PRIMARY_MODULE_RECORD_STRUCTURE', $primaryModuleRecordStructure);
		$viewer->assign('SECONDARY_MODULE_RECORD_STRUCTURES', $secondaryModuleRecordStructures);

		$secondaryModuleIsCalendar = strpos($secondaryModules, 'Calendar');
		if(($primaryModule == 'Calendar') || ($secondaryModuleIsCalendar !== FALSE)){
			$advanceFilterOpsByFieldType = Calendar_Field_Model::getAdvancedFilterOpsByFieldType();
		} else{
			$advanceFilterOpsByFieldType = Vtiger_Field_Model::getAdvancedFilterOpsByFieldType();
		}
		$viewer->assign('ADVANCED_FILTER_OPTIONS', Vtiger_Field_Model::getAdvancedFilterOptions());
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', $advanceFilterOpsByFieldType);
		$dateFilters = Vtiger_Field_Model::getDateFilterTypes();
		foreach($dateFilters as $comparatorKey => $comparatorInfo) {
			$comparatorInfo['startdate'] = DateTimeField::convertToUserFormat($comparatorInfo['startdate']);
			$comparatorInfo['enddate'] = DateTimeField::convertToUserFormat($comparatorInfo['enddate']);
			$comparatorInfo['label'] = vtranslate($comparatorInfo['label'],$module);
			$dateFilters[$comparatorKey] = $comparatorInfo;
		}
		$viewer->assign('DATE_FILTERS', $dateFilters);
		$viewer->assign('LINEITEM_FIELD_IN_CALCULATION', $reportModel->showLineItemFieldsInFilter(false));
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('DETAILVIEW_ACTIONS', $detailViewModel->getDetailViewActions());
		$viewer->assign('REPORT_MODEL', $reportModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('COUNT',$this->count);
		$viewer->assign('REPORT_LIMIT',self::REPORT_LIMIT);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('ReportHeader.tpl', $moduleName);
	}

	function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		echo $this->getReport($request);
	}

	function getReport(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$record = $request->get('record');
		$page = $request->get('page');

		$data = $this->reportData;
		$calculation = $this->calculationFields;

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', self::REPORT_LIMIT+1);

		if(empty($data)){
			$reportModel = Reports_Record_Model::getInstanceById($record);
			$reportModel->setModule('Reports');
			$reportType = $reportModel->get('reporttype');

			$reportData = $reportModel->getReportData($pagingModel);
			$data = $reportData['data'];
			$this->count = $reportData['count'];
			$calculation = $reportModel->getReportCalulationData();
		}

		$groupingList = ReportRun::getInstance($record)->_groupinglist;
		foreach($groupingList as $groupingKey => $groupingValues){
			$moduleField = explode(":", $groupingKey)[2];
			$groupingModules[] = explode("_", $moduleField)[0];
		}

		// グループ化するカラムを判定するラベルをDB(vtiger_reportsortcol,vtiger_reportgroupbycolumn)からアンダーバーの置換で生成しているため，もとからアンダーバーを含むラベルを生成できない
		// もとからアンダーバーを含むラベル抽出し置換を行うかどうかの判別をする
		global $adb;
		$result = $adb->pquery('SELECT DISTINCT fieldlabel FROM vtiger_field WHERE fieldlabel LIKE "%\_%"');
		$rows = $adb->num_rows($result);
		for ($i = 0; $i < $rows; $i++) {
			$includedUnderbar[] = $adb->query_result($result, $i, 'fieldlabel');
		}

		$viewer->assign('CALCULATION_FIELDS',$calculation);
		$viewer->assign('DATA', $data);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('COUNT', $this->count);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('GROUPING_MODULES', $groupingModules);
		$viewer->assign('INCLUDED_UNDERBAR_LABELS', $includedUnderbar);
		$viewer->assign('REPORT_RUN_INSTANCE', ReportRun::getInstance($record));
		if (php7_count($data) > self::REPORT_LIMIT) {
			$viewer->assign('LIMIT_EXCEEDED', true);
		}

		$viewer->view('ReportContents.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.Detail',
			"modules.$moduleName.resources.Detail"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

}
