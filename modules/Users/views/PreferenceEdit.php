<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Users_PreferenceEdit_View extends Vtiger_Edit_View {

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$record = $request->get('record');
		if (!empty($record) && $currentUserModel->get('id') != $record) {
			$recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
			if($recordModel->get('status') != 'Active') {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		if(($currentUserModel->isAdminUser() == true || $currentUserModel->get('id') == $record)) {
			return true;
		} else {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	function preProcessTplName(Vtiger_Request $request) {
		return 'UserEditViewPreProcess.tpl';
	}


	public function preProcess (Vtiger_Request $request, $display=true) {
		if($this->checkPermission($request)) {
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$viewer = $this->getViewer($request);
			$qualifiedModuleName = $request->getModule(false);
			$menuModelsList = Vtiger_Menu_Model::getAll(true);
			$selectedModule = $request->getModule();
			$moduleName = $selectedModule;
			$menuStructure = Vtiger_MenuStructure_Model::getInstanceFromMenuList($menuModelsList, $selectedModule);

			// Order by pre-defined automation process for QuickCreate.
			uksort($menuModelsList, array('Vtiger_MenuStructure_Model', 'sortMenuItemsByProcess'));

			$companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
			$companyLogo = $companyDetails->getLogo();

			$viewer->assign('CURRENTDATE', date('Y-n-j'));
			$viewer->assign('MODULE', $selectedModule);
			$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
			$viewer->assign('PARENT_MODULE', $request->get('parent'));
			$viewer->assign('VIEW', $request->get('view'));
			$viewer->assign('MENUS', $menuModelsList);
			$viewer->assign('QUICK_CREATE_MODULES', Vtiger_Menu_Model::getAllForQuickCreate());
			$viewer->assign('MENU_STRUCTURE', $menuStructure);
			$viewer->assign('MENU_SELECTED_MODULENAME', $selectedModule);
			$viewer->assign('MENU_TOPITEMS_LIMIT', $menuStructure->getLimit());
			$viewer->assign('COMPANY_LOGO',$companyLogo);
			$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
			$viewer->assign('SEARCHABLE_MODULES', Vtiger_Module_Model::getSearchableModules());

			$homeModuleModel = Vtiger_Module_Model::getInstance('Home');
			$viewer->assign('HOME_MODULE_MODEL', $homeModuleModel);
			$viewer->assign('HEADER_LINKS',$this->getHeaderLinks());
			$viewer->assign('ANNOUNCEMENT', $this->getAnnouncement());

			$viewer->assign('CURRENT_VIEW', $request->get('view'));
			$viewer->assign('PAGETITLE', $this->getPageTitle($request));
			$viewer->assign('SCRIPTS',$this->getHeaderScripts($request));
			$viewer->assign('STYLES',$this->getHeaderCss($request));
			$viewer->assign('LANGUAGE_STRINGS', $this->getJSLanguageStrings($request));
			$viewer->assign('SKIN_PATH', Vtiger_Theme::getCurrentUserThemePath());
			$viewer->assign('IS_PREFERENCE', true);
			$viewer->assign('CURRENT_USER_MODEL', $currentUser);
			$viewer->assign('LANGUAGE', $currentUser->get('language'));
			$viewer->assign('COMPANY_DETAILS_SETTINGS',new Settings_Vtiger_CompanyDetails_Model());
			$viewer->assign('SELECTED_MENU_CATEGORY', 'MARKETING');
			$viewer->assign('ACTIVE_BLOCK', Settings_Vtiger_Module_Model::getActiveBlockName($request));

			$settingsModel = Settings_Vtiger_Module_Model::getInstance();
			$menuModels = $settingsModel->getMenus();

			if(!empty($selectedMenuId)) {
				$selectedMenu = Settings_Vtiger_Menu_Model::getInstanceById($selectedMenuId);
			} elseif(!empty($moduleName) && $moduleName != 'Vtiger') {
				$fieldItem = Settings_Vtiger_Index_View::getSelectedFieldFromModule($menuModels,$moduleName);
				if($fieldItem){
					$selectedMenu = Settings_Vtiger_Menu_Model::getInstanceById($fieldItem->get('blockid'));
					$fieldId = $fieldItem->get('fieldid');
				} else {
					reset($menuModels);
					$firstKey = key($menuModels);
					$selectedMenu = $menuModels[$firstKey];
				}
			} else {
				reset($menuModels);
				$firstKey = key($menuModels);
				$selectedMenu = $menuModels[$firstKey];
			}

			$settingsMenItems = array();
			foreach($menuModels as $menuModel) {
				$menuItems = $menuModel->getMenuItems();
				foreach($menuItems as $menuItem) {
					$settingsMenItems[$menuItem->get('name')] = $menuItem;
				}
			}
			$viewer->assign('SETTINGS_MENU_ITEMS', $settingsMenItems);

			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			$moduleFields = $moduleModel->getFields();
			foreach($moduleFields as $fieldName => $fieldModel){
				$fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
			}
			$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
                        
			if($display) {
				$this->preProcessDisplay($request);
			}
		}
	}

	protected function preProcessDisplay(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view($this->preProcessTplName($request), $request->getModule());
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		if (!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		}

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$dayStartPicklistValues = Users_Record_Model::getDayStartsPicklistValues($recordStructureInstance->getStructure());

		$viewer = $this->getViewer($request);
		$viewer->assign("DAY_STARTS", Zend_Json::encode($dayStartPicklistValues));
		$viewer->assign('TAG_CLOUD', $recordModel->getTagCloudStatus());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
                
                $runtime_configs = Vtiger_Runtime_Configs::getInstance();
                $password_regex = $runtime_configs->getValidationRegex('password_regex');
                $viewer->assign('PWD_REGEX', $password_regex);

		parent::process($request);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();
		$moduleDetailFile = 'modules.'.$moduleName.'.resources.PreferenceEdit';
		unset($headerScriptInstances[$moduleDetailFile]);

		$jsFileNames = array(
			"modules.Users.resources.Edit",
			'modules.'.$moduleName.'.resources.PreferenceEdit',
			"modules.Vtiger.resources.CkEditor",
			'modules.Settings.Vtiger.resources.Index',
			"~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}