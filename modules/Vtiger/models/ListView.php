<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger ListView Model Class
 */
class Vtiger_ListView_Model extends Vtiger_Base_Model {



	/**
	 * Function to get the Module Model
	 * @return Vtiger_Module_Model instance
	 */
	public function getModule() {
		return $this->get('module');
	}

	/**
	 * Function to get the Quick Links for the List view of the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$linkTypes = array('SIDEBARLINK', 'SIDEBARWIDGET');
		$moduleLinks = $this->getModule()->getSideBarLinks($linkParams);

		$listLinkTypes = array('LISTVIEWSIDEBARLINK', 'LISTVIEWSIDEBARWIDGET');
		$listLinks = Vtiger_Link_Model::getAllByType($this->getModule()->getId(), $listLinkTypes);

		if($listLinks['LISTVIEWSIDEBARLINK']) {
			foreach($listLinks['LISTVIEWSIDEBARLINK'] as $link) {
				$moduleLinks['SIDEBARLINK'][] = $link;
			}
		}

		if($listLinks['LISTVIEWSIDEBARWIDGET']) {
			foreach($listLinks['LISTVIEWSIDEBARWIDGET'] as $link) {
				$moduleLinks['SIDEBARWIDGET'][] = $link;
			}
		}

		return $moduleLinks;
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	public function getListViewLinks($linkParams) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWBASIC', 'LISTVIEW', 'LISTVIEWSETTING');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);

		$basicLinks = $this->getBasicLinks();

		foreach($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
		}

		$advancedLinks = $this->getAdvancedLinks();

		foreach($advancedLinks as $advancedLink) {
			$links['LISTVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($advancedLink);
		}

		if($currentUserModel->isAdminUser()) {

			$settingsLinks = $this->getSettingLinks();
			foreach($settingsLinks as $settingsLink) {
				$links['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
			}
		}

		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams) {
		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWMASSACTION');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);


		$massActionLinks = array();
		if($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'EditView')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_EDIT',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerMassEdit("index.php?module='.$moduleModel->get('name').'&view=MassActionAjax&mode=showMassEditForm");',
				'linkicon' => ''
			);
		}
		if($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'Delete')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => 'javascript:Vtiger_List_Js.massDeleteRecords("index.php?module='.$moduleModel->get('name').'&action=MassDelete");',
				'linkicon' => ''
			);
		}

		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
		if($moduleModel->isCommentEnabled() && $modCommentsModel->isPermitted('CreateView')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_ADD_COMMENT',
				'linkurl' => 'index.php?module='.$moduleModel->get('name').'&view=MassActionAjax&mode=showAddCommentForm',
				'linkicon' => ''
			);
		}

		foreach($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		return $links;
	}

	/**
	 * Function to get the list view header
	 * @return <Array> - List of Vtiger_Field_Model instances
	 */
	public function getListViewHeaders() {
		$listViewContoller = $this->get('listview_controller');
		$module = $this->getModule();
		$headerFieldModels = array();
		$headerFields = $listViewContoller->getListViewHeaderFields();
		foreach($headerFields as $fieldName => $webserviceField) {
			if($webserviceField && !in_array($webserviceField->getPresence(), array(0,2))) continue;
			if($webserviceField && isset($webserviceField->parentReferenceField) && !in_array($webserviceField->parentReferenceField->getPresence(), array(0,2))){
				continue;
			}
			if($webserviceField->getDisplayType() == '6') continue;
			// check if the field is reference field
			preg_match('/(\w+) ; \((\w+)\) (\w+)/', $fieldName, $matches);
			if(php7_count($matches) > 0) {
				list($full, $referenceParentField, $referenceModule, $referenceFieldName) = $matches;
				$referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModule);
				$referenceFieldModel = Vtiger_Field_Model::getInstance($referenceFieldName, $referenceModuleModel);
				$referenceFieldModel->set('webserviceField', $webserviceField);
				// added tp use in list view to see the title, for reference field rawdata key is different than the actual field
				// eg: in rawdata its account_idcf_2342 (raw column name used in querygenerator), actual field name (account_id ;(Accounts) cf_2342)
				// When generating the title we use rawdata and from field model we have no way to find querygenrator raw column name.

				$referenceFieldModel->set('listViewRawFieldName', $referenceParentField.$referenceFieldName);

				// this is added for picklist colorizer (picklistColorMap.tpl), for fetching picklist colors we need the actual field name of the picklist
				$referenceFieldModel->set('_name', $referenceFieldName);
				$headerFieldModels[$fieldName] = $referenceFieldModel->set('name', $fieldName); // resetting the fieldname as we use it to fetch the value from that name
				$matches=null;
			} else {
				$fieldInstance = Vtiger_Field_Model::getInstance($fieldName,$module);
				
				if(!$fieldInstance && $module->getName() === 'Calendar') {
					$eventsModule = Vtiger_Module_Model::getInstance('Events');
					$fieldInstance = Vtiger_Field_Model::getInstance($fieldName,$eventsModule);
				}
				
				$fieldInstance->set('listViewRawFieldName', $fieldInstance->get('column'));
				$headerFieldModels[$fieldName] = $fieldInstance;
			}
		}
		return $headerFieldModels;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewEntries($pagingModel) {
		$db = PearDatabase::getInstance();

		$moduleName = $this->getModule()->get('name');
		$moduleFocus = CRMEntity::getInstance($moduleName);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$queryGenerator = $this->get('query_generator');
		$listViewContoller = $this->get('listview_controller');

		 $searchParams = $this->get('search_params');
		if(empty($searchParams)) {
			$searchParams = array();
		}
		$glue = "";
		if(php7_count($queryGenerator->getWhereFields()) > 0 && (php7_count($searchParams)) > 0) {
			$glue = QueryGenerator::$AND;
		}
		$queryGenerator->parseAdvFilterList($searchParams, $glue);

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if(!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}

		$sortConditions = $this->getSortConditions();
		$this->setupQueryGeneratorForSort($queryGenerator, $sortConditions);

		$listQuery = $this->getQuery();

		$sourceModule = $this->get('src_module');
		if(!empty($sourceModule)) {
			if(method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $listQuery,$this->get('relationId'));
				if(!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
		}

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$listQuery .= $this->getOrderBySql($queryGenerator, $sortConditions, $moduleFocus);

		$viewid = ListViewSession::getCurrentView($moduleName);
		if(empty($viewid)) {
			$viewid = $pagingModel->get('viewid');
		}
		$_SESSION['lvs'][$moduleName][$viewid]['start'] = $pagingModel->get('page');

		ListViewSession::setSessionQuery($moduleName, $listQuery, $viewid);

		$listQuery .= " LIMIT $startIndex,".($pageLimit+1);
		
		$listResult = $db->pquery($listQuery, array());

		$listViewRecordModels = array();
		$listViewEntries =  $listViewContoller->getListViewRecords($moduleFocus,$moduleName, $listResult);

		$pagingModel->calculatePageRange($listViewEntries);

		if($db->num_rows($listResult) > $pageLimit){
			array_pop($listViewEntries);
			$pagingModel->set('nextPageExists', true);
		}else{
			$pagingModel->set('nextPageExists', false);
		}

		$index = 0;
		foreach($listViewEntries as $recordId => $record) {
			$rawData = $db->query_result_rowdata($listResult, $index++);
			$record['id'] = $recordId;
			$listViewRecordModels[$recordId] = $moduleModel->getRecordFromArray($record, $rawData);
		}
		return $listViewRecordModels;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewCount() {
		$db = PearDatabase::getInstance();

		$queryGenerator = $this->get('query_generator');


		$searchParams = $this->get('search_params');
		if(empty($searchParams)) {
			$searchParams = array();
		}

		// for Documents folders we should filter with folder id as well
		$folderKey = $this->get('folder_id');
		$folderValue = $this->get('folder_value');
		if(!empty($folderValue)) {
			$queryGenerator->addCondition($folderKey,$folderValue,'e');
		}

		$glue = "";
		if(php7_count($queryGenerator->getWhereFields()) > 0 && (php7_count($searchParams)) > 0) {
			$glue = QueryGenerator::$AND;
		}
		$queryGenerator->parseAdvFilterList($searchParams, $glue);

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if(!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}
		$moduleName = $this->getModule()->get('name');
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$listQuery = $this->getQuery();
		$sourceModule = $this->get('src_module');
		if(!empty($sourceModule)) {
			$moduleModel = $this->getModule();
			if(method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $listQuery);
				if(!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
		}
		$position = stripos($listQuery, ' from ');
		if ($position) {
			$split = preg_split('/ from /i', $listQuery);
			$splitCount = php7_count($split);
			// If records is related to two records then we'll get duplicates. Then count will be wrong
			$meta = $queryGenerator->getMeta($this->getModule()->getName());
			$columnIndex = $meta->getObectIndexColumn();
			$baseTable = $meta->getEntityBaseTable();
			$listQuery = "SELECT count(distinct($baseTable.$columnIndex)) AS count ";
			for ($i=1; $i<$splitCount; $i++) {
				$listQuery = $listQuery. ' FROM ' .$split[$i];
			}
		}

		if($this->getModule()->get('name') == 'Calendar'){
			$listQuery .= ' AND activitytype <> "Emails"';
		}

		$listResult = $db->pquery($listQuery, array());
		return $db->query_result($listResult, 0, 'count');
	}

	function getQuery() {
		$queryGenerator = $this->get('query_generator');
		$listQuery = $queryGenerator->getQuery();
		return $listQuery;
	}
	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param <String> $moduleName - Module Name
	 * @param <Number> $viewId - Custom View Id
	 * @return Vtiger_ListView_Model instance
	 */
	public static function getInstance($moduleName, $viewId='0', $listHeaders = array()) {
		$db = PearDatabase::getInstance();
		$currentUser = vglobal('current_user');

		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'ListView', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$queryGenerator = new EnhancedQueryGenerator($moduleModel->get('name'), $currentUser);
		$customView = new CustomView();
		if (!empty($viewId) && $viewId != "0") {
			$queryGenerator->initForCustomViewById($viewId);

			//Used to set the viewid into the session which will be used to load the same filter when you refresh the page
			$viewId = $customView->getViewId($moduleName);
		} else {
			$viewId = $customView->getViewId($moduleName);
			if(!empty($viewId) && $viewId != 0) {
				$queryGenerator->initForDefaultCustomView();
			} else {
				$entityInstance = CRMEntity::getInstance($moduleName);
				$listFields = $entityInstance->list_fields_name;
				$listFields[] = 'id';
				$queryGenerator->setFields($listFields);
			}
		}

		$fieldsList = $queryGenerator->getFields();
		if(!empty($listHeaders) && is_array($listHeaders) && php7_count($listHeaders) > 0) {
			$fieldsList = $listHeaders;
			$fieldsList[] = 'id';
		}
		//to show starred field in list view
		$fieldsList[] = 'starred';
		$queryGenerator->setFields($fieldsList);

		$moduleSpecificControllerPath = 'modules/'.$moduleName.'/controllers/ListViewController.php';
		if(file_exists($moduleSpecificControllerPath)) {
			include_once $moduleSpecificControllerPath;
			$moduleSpecificControllerClassName = $moduleName.'ListViewController';
			$controller = new $moduleSpecificControllerClassName($db, $currentUser, $queryGenerator);
		} else {
			$controller = new ListViewController($db, $currentUser, $queryGenerator);
		}

		return $instance->set('module', $moduleModel)->set('query_generator', $queryGenerator)->set('listview_controller', $controller);
	}

	/**
	 * Function to create clean instance
	 * @param type $moduleName -- module for which list view model has to be created
	 * @return type -- List view model  
	 */
	public static function getCleanInstance($moduleName) {
		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'ListView', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		return $instance->set('module', $moduleModel);
	}

	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param <String> $value - Module Name
	 * @param <Number> $viewId - Custom View Id
	 * @return Vtiger_ListView_Model instance
	 */
	public static function getInstanceForPopup($value) {
		$db = PearDatabase::getInstance();
		$currentUser = vglobal('current_user');

		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'ListView', $value);
		$instance = new $modelClassName();
		$moduleModel = Vtiger_Module_Model::getInstance($value);

		$queryGenerator = new EnhancedQueryGenerator($moduleModel->get('name'), $currentUser);

		$listFields = $moduleModel->getPopupViewFieldsList();

		$listFields[] = 'id';
		$queryGenerator->setFields($listFields);

		$controller = new ListViewController($db, $currentUser, $queryGenerator);

		return $instance->set('module', $moduleModel)->set('query_generator', $queryGenerator)->set('listview_controller', $controller);
	}

	/*
	 * Function to give advance links of a module
	 *	@RETURN array of advanced links
	 */
	public function getAdvancedLinks(){
		$moduleModel = $this->getModule();
		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'CreateView');
		$advancedLinks = array();
		$importPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Import');
		if($importPermission && $createPermission) {
			$advancedLinks[] = array(
							'linktype' => 'LISTVIEW',
							'linklabel' => 'LBL_IMPORT',
							'linkurl' => $moduleModel->getImportUrl(),
							'linkicon' => ''
			);
		}

		$duplicatePermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'DuplicatesHandling');
		$editPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'EditView');
		if($duplicatePermission && $editPermission) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_FIND_DUPLICATES',
				'linkurl' => 'Javascript:Vtiger_List_Js.showDuplicateSearchForm("index.php?module='.$moduleModel->getName().
								'&view=MassActionAjax&mode=showDuplicatesSearchForm")',
				'linkicon' => ''
			);
		}

		$exportPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Export');
		if($exportPermission) {
			$advancedLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_EXPORT',
					'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("'.$this->getModule()->getExportUrl().'")',
					'linkicon' => ''
				);
		}

		return $advancedLinks;
	}

	/*
	 * Function to get Setting links
	 * @return array of setting links
	 */
	public function getSettingLinks() {
		return $this->getModule()->getSettingLinks();
	}

	/*
	 * Function to get Basic links
	 * @return array of Basic links
	 */
	public function getBasicLinks(){
		$basicLinks = array();
		$moduleModel = $this->getModule();
		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'CreateView');
		if($createPermission) {
			$basicLinks[] = array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => 'LBL_ADD_RECORD',
					'linkurl' => $moduleModel->getCreateRecordUrl(),
					'linkicon' => ''
			);
		}
		return $basicLinks;
	}

	public function extendPopupFields($fieldsList) {
		$moduleModel = $this->get('module');
		$queryGenerator = $this->get('query_generator');

		$listFields = $moduleModel->getPopupViewFieldsList();

		$listFields[] = 'id';
		$listFields = array_merge($listFields, $fieldsList);
		$queryGenerator->setFields($listFields);
		$this->get('query_generator', $queryGenerator);
	}

	public static function getSortParamsSession($key) {
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
			}

	public static function setSortParamsSession($key, $params) {
		$_SESSION[$key] = $params;
	}

	public static function deleteParamsSession($key, $params) {
		if(!is_array($params)) {
			$params = array($params);
		}
		foreach($params as $param) {
			$_SESSION[$key][$param] = '';
		}
	}

	public function isImportEnabled() {
		$linkParams = array('MODULE'=>$this->getModule()->getName(), 'ACTION'=>'LIST');
		$listViewLinks = $this->getListViewLinks($linkParams);
		$listViewActions = $listViewLinks['LISTVIEW'];
		if (is_array($listViewActions)) {
			foreach($listViewActions as $linkAction) {
				if($linkAction->getLabel() == 'LBL_IMPORT'){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Helper method to clean and parse sort conditions from array or JSON string.
	 * @param mixed $rawSortConditions
	 * @param string $defaultOrder
	 * @return array
	 */
	public static function cleanSortConditions($rawSortConditions, $defaultOrder = 'ASC') {
		$sortConditions = array();
		if (empty($rawSortConditions)) {
			return $sortConditions;
		}

		$decoded = null;
		if (is_array($rawSortConditions)) {
			$decoded = $rawSortConditions;
		} else if (is_string($rawSortConditions)) {
			$decoded = json_decode($rawSortConditions, true);
			if (!is_array($decoded)) {
				$decoded = json_decode(html_entity_decode($rawSortConditions, ENT_QUOTES, 'UTF-8'), true);
			}
			if (!is_array($decoded)) {
				$decoded = json_decode(htmlspecialchars_decode($rawSortConditions), true);
			}
			if (!is_array($decoded)) {
				$decoded = json_decode(stripslashes($rawSortConditions), true);
			}
		}

		if (is_array($decoded) && !empty($decoded)) {
			foreach ($decoded as $item) {
				if (is_object($item)) $item = (array)$item;
				if (is_array($item) && !empty($item['field']) && is_string($item['field'])) {
					$field = trim($item['field']);
					$order = (!empty($item['order']) && is_string($item['order']) && strtoupper($item['order']) === 'DESC') ? 'DESC' : 'ASC';
					$sortConditions[] = array('field' => $field, 'order' => $order);
				}
			}
		} else if (is_string($rawSortConditions) && strpos($rawSortConditions, '[') === false && strpos($rawSortConditions, '{') === false) {
			$field = trim($rawSortConditions);
			if (!empty($field)) {
				$order = (strtoupper($defaultOrder) === 'DESC') ? 'DESC' : 'ASC';
				$sortConditions[] = array('field' => $field, 'order' => $order);
			}
		}
		return $sortConditions;
	}

	public function getSortConditions() {
		$moduleName = $this->getModule()->get('name');
		$orderBy = $this->get('orderby');
		$sortOrder = $this->get('sortorder');

		$sortConditions = array();
		if (!empty($orderBy)) {
			$sortConditions = self::cleanSortConditions($orderBy, $sortOrder);
		} else {
			$viewid = ListViewSession::getCurrentView($moduleName);
			if (!empty($viewid)) {
				$cvModel = CustomView_Record_Model::getInstanceById($viewid);
				if ($cvModel) {
					$sortConditions = $cvModel->getSortConditions();
				}
			}
		}
		return $sortConditions;
	}

	public function setupQueryGeneratorForSort($queryGenerator, $sortConditions) {
		if (!empty($sortConditions)) {
			$fieldModels = $queryGenerator->getModuleFields();
			foreach ($sortConditions as $cond) {
				if (is_object($cond)) $cond = (array)$cond;
				$rawFieldName = isset($cond['field']) ? $cond['field'] : '';
				if (empty($rawFieldName)) continue;

				$pureFieldName = $rawFieldName;
				$parentField = '';
				preg_match('/(\w+) ; \((\w+)\) (\w+)/', $rawFieldName, $m);
				if (!empty($m[1])) {
					$parentField = $m[1];
					$pureFieldName = $m[3];
				}

				$checkField = !empty($parentField) ? $parentField : $pureFieldName;
				if (isset($fieldModels[$checkField])) {
					$condFieldModel = $fieldModels[$checkField];
					if ($condFieldModel && ($condFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE ||
							$condFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)) {
						$queryGenerator->addWhereField($rawFieldName);
					}
				}
			}
		}
	}

	public function getOrderBySql($queryGenerator, $sortConditions, $moduleFocus = null) {
		$moduleName = $this->getModule()->get('name');
		$orderBySql = '';
		if (!empty($sortConditions)) {
			$fieldModels = $queryGenerator->getModuleFields();
			$orderByParts = array();
			foreach ($sortConditions as $cond) {
				if (is_object($cond)) $cond = (array)$cond;
				$rawFieldName = isset($cond['field']) ? $cond['field'] : '';
				if (empty($rawFieldName)) continue;
				$order = (!empty($cond['order']) && strtoupper($cond['order']) === 'DESC') ? 'DESC' : 'ASC';

				$pureFieldName = $rawFieldName;
				$parentField = '';
				preg_match('/(\w+) ; \((\w+)\) (\w+)/', $rawFieldName, $m);
				if (!empty($m[1])) {
					$parentField = $m[1];
					$pureFieldName = $m[3];
				}

				// Whitelist validation: check if field exists in module or is an allowed special field
				$checkField = !empty($parentField) ? $parentField : $pureFieldName;
				$isValidField = isset($fieldModels[$checkField]);
				if (!$isValidField && $moduleName === 'Users' && ($pureFieldName === 'roleid' || $pureFieldName === 'first_name')) {
					$isValidField = true;
				}
				if (!$isValidField && $moduleName === 'Calendar' && ($pureFieldName === 'date_start' || $pureFieldName === 'due_date')) {
					$isValidField = true;
				}

				if (!$isValidField) {
					continue;
				}

				if ($pureFieldName == 'roleid' && $moduleName == 'Users') {
					$orderByParts[] = 'vtiger_role.rolename ' . $order;
				} else if ($pureFieldName == 'date_start' && $moduleName == 'Calendar') {
					$orderByParts[] = "str_to_date(concat(date_start,time_start),'%Y-%m-%d %H:%i:%s') " . $order;
				} else if ($pureFieldName == 'due_date' && $moduleName == 'Calendar') {
					$orderByParts[] = "str_to_date(concat(due_date,time_end),'%Y-%m-%d %H:%i:%s') " . $order;
				} else {
					$columnSql = $queryGenerator->getOrderByColumn($rawFieldName);
					if (!empty($columnSql)) {
						$orderByParts[] = $columnSql . ' ' . $order;
					}
				}
			}
			if (!empty($orderByParts)) {
				$orderBySql = ' ORDER BY ' . implode(', ', $orderByParts);
				if (isset($sortConditions[0]['field']) && $sortConditions[0]['field'] == 'first_name' && $moduleName == 'Users') {
					$existingFields = array();
					foreach ($sortConditions as $cond) {
						$f = isset($cond['field']) ? $cond['field'] : '';
						preg_match('/(\w+) ; \((\w+)\) (\w+)/', $f, $m);
						$existingFields[] = !empty($m[3]) ? $m[3] : $f;
					}
					if (!in_array('last_name', $existingFields)) {
						$orderBySql .= ' , last_name ' . $sortConditions[0]['order'];
					}
					if (!in_array('email1', $existingFields)) {
						$orderBySql .= ' , email1 ' . $sortConditions[0]['order'];
					}
				}
			}
		} else if ($moduleName != "Users") {
			$baseTable = $moduleFocus ? $moduleFocus->table_name : '';
			if(empty($baseTable)) {
				$baseTable = "vtiger_crmentity";
			}
			$orderBySql = " ORDER BY ".$baseTable.".modifiedtime DESC";
		}
		return $orderBySql;
	}
}
