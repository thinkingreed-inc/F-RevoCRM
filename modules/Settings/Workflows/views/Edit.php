<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Workflows_Edit_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if ($mode == 'v7Edit') {
			$this->$mode($request);
		} else if ($mode) {
			$this->$mode($request);
		} else {
			$this->step1($request);
		}
	}

	public function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request);
		$viewer = $this->getViewer($request);

		$recordId = $request->get('record');
		$viewer->assign('RECORDID', $recordId);
		if($recordId) {
			$workflowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		}
		$viewer->assign('RECORD_MODE', $request->getMode());
		$viewer->view('EditHeader.tpl', $request->getModule(false));
	}

	public function step1(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');
		if ($recordId) {
			$workflowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$viewer->assign('RECORDID', $recordId);
			$viewer->assign('MODULE_MODEL', $workflowModel->getModule());
			$viewer->assign('MODE', 'edit');
		} else {
			$workflowModel = Settings_Workflows_Record_Model::getCleanInstance($moduleName);
            $selectedModule = $request->get('source_module');
            if(!empty($selectedModule)) {
                $viewer->assign('SELECTED_MODULE', $selectedModule);
            }
		}
		$db = PearDatabase::getInstance();
		$workflowManager = new VTWorkflowManager($db);
		$viewer->assign('MAX_ALLOWED_SCHEDULED_WORKFLOWS', $workflowManager->getMaxAllowedScheduledWorkflows());
		$viewer->assign('SCHEDULED_WORKFLOW_COUNT', $workflowManager->getScheduledWorkflowsCount());
		$viewer->assign('WORKFLOW_MODEL', $workflowModel);
		$viewer->assign('ALL_MODULES', Settings_Workflows_Module_Model::getSupportedModules());
		$viewer->assign('TRIGGER_TYPES', Settings_Workflows_Module_Model::getTriggerTypes());

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('CURRENT_USER', $currentUser);
		$admin = Users::getActiveAdminUser();
		$viewer->assign('ACTIVE_ADMIN', $admin);
		$viewer->view('Step1.tpl', $qualifiedModuleName);
	}

	public function step2(Vtiger_Request $request) {

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');

		if ($recordId) {
			$workFlowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$selectedModule = $workFlowModel->getModule();
			$selectedModuleName = $selectedModule->getName();
		} else {
			$selectedModuleName = $request->get('module_name');
			$selectedModule = Vtiger_Module_Model::getInstance($selectedModuleName);
			$workFlowModel = Settings_Workflows_Record_Model::getCleanInstance($selectedModuleName);
		}

		$requestData = $request->getAll();
		foreach($requestData as $name=>$value) {
			if($name == 'schdayofweek' || $name == 'schdayofmonth' || $name == 'schannualdates') {
				if(is_string($value)) {	// need to save these as json data
					$value = array($value);
				}
			}
			$workFlowModel->set($name,$value);
		}
		//Added to support advance filters
		$recordStructureInstance = Settings_Workflows_RecordStructure_Model::getInstanceForWorkFlowModule($workFlowModel,
																			Settings_Workflows_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);

		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
        $recordStructure = $recordStructureInstance->getStructure();
        if(in_array($selectedModuleName,  getInventoryModules())){
            $itemsBlock = "LBL_ITEM_DETAILS";
            unset($recordStructure[$itemsBlock]);
        }
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);

		$viewer->assign('WORKFLOW_MODEL',$workFlowModel);

		$viewer->assign('MODULE_MODEL', $selectedModule);
		$viewer->assign('SELECTED_MODULE_NAME', $selectedModuleName);

		$dateFilters = Vtiger_Field_Model::getDateFilterTypes();
        foreach($dateFilters as $comparatorKey => $comparatorInfo) {
            $comparatorInfo['startdate'] = DateTimeField::convertToUserFormat($comparatorInfo['startdate']);
            $comparatorInfo['enddate'] = DateTimeField::convertToUserFormat($comparatorInfo['enddate']);
            $comparatorInfo['label'] = vtranslate($comparatorInfo['label'], $qualifiedModuleName);
            $dateFilters[$comparatorKey] = $comparatorInfo;
        }
        $viewer->assign('DATE_FILTERS', $dateFilters);
		$viewer->assign('ADVANCED_FILTER_OPTIONS', Settings_Workflows_Field_Model::getAdvancedFilterOptions());
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', Settings_Workflows_Field_Model::getAdvancedFilterOpsByFieldType());
		$viewer->assign('COLUMNNAME_API', 'getWorkFlowFilterColumnName');

		$viewer->assign('FIELD_EXPRESSIONS', Settings_Workflows_Module_Model::getExpressions());
		$viewer->assign('META_VARIABLES', Settings_Workflows_Module_Model::getMetaVariables());

		// Added to show filters only when saved from vtiger6
		if($workFlowModel->isFilterSavedInNew()) {
			$viewer->assign('ADVANCE_CRITERIA', $workFlowModel->transformToAdvancedFilterCondition());
		} else {
			$viewer->assign('ADVANCE_CRITERIA', "");
		}

		$viewer->assign('IS_FILTER_SAVED_NEW',$workFlowModel->isFilterSavedInNew());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        
        $userModel = Users_Record_Model::getCurrentUserModel();
        $viewer->assign('DATE_FORMAT', $userModel->get('date_format'));

		$viewer->view('Step2.tpl', $qualifiedModuleName);
	}

	function Step3(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');

		if ($recordId) {
			$workFlowModel = Settings_Workflows_Record_Model::getInstance($recordId);
			$selectedModule = $workFlowModel->getModule();
			$selectedModuleName = $selectedModule->getName();
		} else {
			$selectedModuleName = $request->get('module_name');
			$selectedModule = Vtiger_Module_Model::getInstance($selectedModuleName);
			$workFlowModel = Settings_Workflows_Record_Model::getCleanInstance($selectedModuleName);
		}

		$moduleModel = $workFlowModel->getModule();
		$viewer->assign('TASK_TYPES', Settings_Workflows_TaskType_Model::getAllForModule($moduleModel));
		$viewer->assign('SOURCE_MODULE',$selectedModuleName);
		$viewer->assign('RECORD',$recordId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('WORKFLOW_MODEL',$workFlowModel);
		$viewer->assign('TASK_LIST', $workFlowModel->getTasks());
		$viewer->assign('QUALIFIED_MODULE',$qualifiedModuleName);

		$viewer->view('Step3.tpl', $qualifiedModuleName);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit",
			"modules.Settings.$moduleName.resources.Edit1",
			"modules.Settings.$moduleName.resources.Edit2",
			"modules.Settings.$moduleName.resources.Edit3",
			"modules.Settings.$moduleName.resources.AdvanceFilter",
			'~libraries/jquery/ckeditor/ckeditor.js',
			"modules.Vtiger.resources.CkEditor",
            '~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js',
			'~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.js',
			'~libraries/jquery/datetimepicker/js/jquery.datetimepicker.full.min.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = array(
			'~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.css',
            '~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css',
			'~/libraries/jquery/datetimepicker/css/jquery.datetimepicker.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
    
   function v7Edit(Vtiger_Request $request) {
      $currentUser = Users_Record_Model::getCurrentUserModel();
      $viewer = $this->getViewer($request);
      $moduleName = $request->getModule();
      $qualifiedModuleName = $request->getModule(false);
      $allModules = Settings_Workflows_Module_Model::getSupportedModules();

      $recordId = $request->get('record');
      if ($recordId) {
         $workflowModel = Settings_Workflows_Record_Model::getInstance($recordId);
         $workflowSourceModuleModel = $workflowModel->getModule();
         $viewer->assign('RECORDID', $recordId);
         $viewer->assign('MODULE_MODEL', $workflowSourceModuleModel);
         $viewer->assign('SELECTED_MODULE', $workflowSourceModuleModel->getName());
         $viewer->assign('MODE', 'edit');
      } else {
         $workflowModel = Settings_Workflows_Record_Model::getCleanInstance($moduleName);
         $selectedModule = $request->get('source_module');
         if (!empty($selectedModule)) {
            $viewer->assign('SELECTED_MODULE', $selectedModule);
         } else {
             foreach ($allModules as $moduleModel) {
                 $viewer->assign('SELECTED_MODULE', $moduleModel->getName());
                 break;
             }
         }
      }

      $db = PearDatabase::getInstance();
      $workflowManager = new VTWorkflowManager($db);
      $viewer->assign('MAX_ALLOWED_SCHEDULED_WORKFLOWS', $workflowManager->getMaxAllowedScheduledWorkflows());
      $viewer->assign('SCHEDULED_WORKFLOW_COUNT', $workflowManager->getScheduledWorkflowsCount());

      $viewer->assign('WORKFLOW_MODEL', $workflowModel);
      $viewer->assign('ALL_MODULES', $allModules);
      $viewer->assign('TRIGGER_TYPES', Settings_Workflows_Module_Model::getTriggerTypes());

      $viewer->assign('MODULE', $moduleName);
      $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
      $viewer->assign('CURRENT_USER', $currentUser);
      $admin = Users::getActiveAdminUser();
      $viewer->assign('ACTIVE_ADMIN', $admin);
      $viewer->assign('RETURN_SOURCE_MODULE', $request->get("returnsourceModule"));
      $viewer->assign('RETURN_PAGE', $request->get("returnpage"));
      $viewer->assign('RETURN_SEARCH_VALUE',$request->get("returnsearch_value"));
      
      $viewer->view('EditView.tpl', $qualifiedModuleName);
   }
   
}