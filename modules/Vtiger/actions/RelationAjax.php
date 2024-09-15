<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_RelationAjax_Action extends Vtiger_Action_Controller {
	function __construct() {
		parent::__construct();
		$this->exposeMethod('addRelation');
		$this->exposeMethod('addRelationsForAllRecords');
		$this->exposeMethod('deleteRelation');
		$this->exposeMethod('getRelatedListPageCount');
		$this->exposeMethod('getRelatedRecordInfo');
	}

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				case 'addRelation':
				case 'deleteRelation':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'src_record');
					$permissions[] = array('module_parameter' => 'related_module', 'action' => 'DetailView');
					break;
				case 'getRelatedListPageCount':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
					$permissions[] = array('module_parameter' => 'relatedModule', 'action' => 'DetailView');
				case 'getRelatedRecordInfo':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'id');
				default:
					break;
			}
		}
		return $permissions;
	}
	
	function checkPermission(Vtiger_Request $request) {
 		return parent::checkPermission($request);
	}

	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}

	function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/*
	 * Function to add relation for specified source record id and related record id list
	 * @param <array> $request
	 *		keys					Content
	 *		src_module				source module name
	 *		src_record				source record id
	 *		related_module			related module name
	 *		related_record_list		json encoded of list of related record ids
	 */
	function addRelation($request) {
		$sourceModule = $request->getModule();
		$sourceRecordId = $request->get('src_record');

		$relatedModule = $request->get('related_module');
		$relatedRecordIdList = $request->get('related_record_list');

		$sourceModuleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModule);
		$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach($relatedRecordIdList as $relatedRecordId) {
			$relationModel->addRelation($sourceRecordId,$relatedRecordId);
		}

		$response = new Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	function addRelationsForAllRecords(Vtiger_Request $request) {
		$allRecordIds = $this->getNoRelatedRecordIds($request);		
		$request->set('related_record_list', $allRecordIds);
		$this->addRelation($request);
	}
	
	public function getNoRelatedRecordIds(Vtiger_Request $request) {
		global $adb;

		$moduleName =  $request->get('related_module');
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		
		$sourceModule = $request->getModule();
		$sourceField = $request->get('src_field');
		$sourceRecord = $request->get('src_record');
		
		$relatedParentModule = $request->get('related_parent_module');
		$relatedParentId = $request->get('related_parent_id');
		
		$relationId = $request->get('relationId'); 
		
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$searchParams=$request->get('search_params');
		
		$isRecordExists = Vtiger_Util_Helper::checkRecordExistance($relatedParentId);

		if($isRecordExists) {
			$relatedParentModule = '';
			$relatedParentId = '';
		} else if($isRecordExists === NULL) {
			$relatedParentModule = '';
			$relatedParentId = '';
		}

		if(!empty($relatedParentModule) && !empty($relatedParentId)) {
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($relatedParentId, $relatedParentModule);
			$listViewModel = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $moduleName);
			$searchModuleModel = $listViewModel->getRelatedModuleModel();
		}else{
			$listViewModel = Vtiger_ListView_Model::getInstanceForPopup($moduleName);
			$searchModuleModel = $listViewModel->getModule();
		}

		if($moduleName == 'Documents' && $sourceModule == 'Emails') {
			$listViewModel->extendPopupFields(array('filename'=>'filename'));
		}
		
		if(!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		
		if(!empty($sourceModule)) {
			$listViewModel->set('src_module', $sourceModule);
			$listViewModel->set('src_field', $sourceField);
			$listViewModel->set('src_record', $sourceRecord);
		}
		
		if((!empty($searchKey)) && (!empty($searchValue)))  {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}
		
		$listViewModel->set('relationId',$relationId);

		if(!empty($searchParams)){
			$transformedSearchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $searchModuleModel);
			$listViewModel->set('search_params',$transformedSearchParams);
		}
		
		if(!empty($relatedParentModule) && !empty($relatedParentId)) {
			$moduleFields = $moduleModel->getFields();
			$whereCondition = [];

			foreach ($searchParams as $fieldListGroup) {
				foreach ($fieldListGroup as $fieldSearchInfo) {
					$fieldModel = $moduleFields[$fieldSearchInfo[0]];
					$tableName = $fieldModel->get('table');
					$column = $fieldModel->get('column');
					$whereCondition[$fieldSearchInfo[0]] = [
						$tableName . '.' . $column,
						$fieldSearchInfo[1],
						$fieldSearchInfo[2],
						$fieldSearchInfo[3]
					];
				}
			}

			if (!empty($whereCondition)) {
				$listViewModel->set('whereCondition', $whereCondition);
			}
		}

		if((!isset($parent_related_records) || !$parent_related_records) && 
			!empty($relatedParentModule) && !empty($relatedParentId)){
			$relatedParentModule = null;
			$relatedParentId = null;
			$listViewModel = Vtiger_ListView_Model::getInstanceForPopup($moduleName);

			if(!empty($orderBy)) {
				$listViewModel->set('orderby', $orderBy);
				$listViewModel->set('sortorder', $sortOrder);
			}
			if(!empty($sourceModule)) {
				$listViewModel->set('src_module', $sourceModule);
				$listViewModel->set('src_field', $sourceField);
				$listViewModel->set('src_record', $sourceRecord);
			}
			if((!empty($searchKey)) && (!empty($searchValue)))  {
				$listViewModel->set('search_key', $searchKey);
				$listViewModel->set('search_value', $searchValue);
			}

			if(!empty($searchParams)) {
				$transformedSearchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $searchModuleModel);
				$listViewModel->set('search_params',$transformedSearchParams);
			}
		}  
		
		if(empty($searchParams) || !is_array($searchParams)) {
			$searchParams = array();
		}

		$queryGenerator = $listViewModel->get('query_generator');

		 $searchParams = $listViewModel->get('search_params');
		if(empty($searchParams)) {
			$searchParams = array();
		}
		$glue = "";
		if(php7_count($queryGenerator->getWhereFields()) > 0 && (php7_count($searchParams)) > 0) {
			$glue = QueryGenerator::$AND;
		}
		$queryGenerator->parseAdvFilterList($searchParams, $glue);

		$searchKey = $listViewModel->get('search_key');
		$searchValue = $listViewModel->get('search_value');
		$operator = $listViewModel->get('operator');
		if(!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}

		$orderBy = $listViewModel->getForSql('orderby');
		$sortOrder = $listViewModel->getForSql('sortorder');

		if(!empty($orderBy)){
			$queryGenerator = $listViewModel->get('query_generator');
			$fieldModels = $queryGenerator->getModuleFields();
			$orderByFieldModel = $fieldModels[$orderBy];
			if($orderByFieldModel && ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE ||
					$orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)){
				$queryGenerator->addWhereField($orderBy);
			}
		}
		$listQuery = $listViewModel->getQuery();
		$sourceModule = $listViewModel->get('src_module');
		if(!empty($sourceModule) && method_exists($moduleModel, 'getQueryByModuleField')) {
			$overrideQuery = $moduleModel->getQueryByModuleField(
										$sourceModule, $listViewModel->get('src_field'), 
										$listViewModel->get('src_record'), 
										$listQuery,
										$listViewModel->get('relationId')
							 );
			if(!empty($overrideQuery)) {
				$listQuery = $overrideQuery;
			}
		}
		
		// 取得するカラムをcrmidのみにする
		$split = preg_split('/ FROM /i', $listQuery) ?: [];
		$splitCount = count($split);
		$listQuery = "SELECT vtiger_crmentity.crmid ";
		for ($i=1; $i<$splitCount; $i++) {
			$listQuery = $listQuery. ' FROM ' .$split[$i];
		}
		
		// IDを取得
		$result = $adb->query($listQuery);
		$ids = [];
		for($i=0; $i<$adb->num_rows($result); $i++) {
			$ids[] = $adb->query_result($result, $i, 'crmid');
		}
		
		return $ids;
	}

	/**
	 * Function to delete the relation for specified source record id and related record id list
	 * @param <array> $request
	 *		keys					Content
	 *		src_module				source module name
	 *		src_record				source record id
	 *		related_module			related module name
	 *		related_record_list		json encoded of list of related record ids
	 */
	function deleteRelation($request) {
		$sourceModule = $request->getModule();
		$sourceRecordId = $request->get('src_record');

		$relatedModule = $request->get('related_module');
		$relatedRecordIdList = $request->get('related_record_list');
		$recurringEditMode = $request->get('recurringEditMode');
		$relatedRecordList = array();
		if($relatedModule == 'Calendar' && !empty($recurringEditMode) && $recurringEditMode != 'current') {
			foreach($relatedRecordIdList as $relatedRecordId) {
				$recordModel = Vtiger_Record_Model::getCleanInstance($relatedModule);
				$recordModel->set('id', $relatedRecordId);
				$recurringRecordsList = $recordModel->getRecurringRecordsList();
				foreach($recurringRecordsList as $parent => $childs) {
					$parentRecurringId = $parent;
					$childRecords = $childs;
				}
				if($recurringEditMode == 'future') {
					$parentKey = array_keys($childRecords, $relatedRecordId);
					$childRecords = array_slice($childRecords, $parentKey[0]);
				}
				foreach($childRecords as $recordId) {
					$relatedRecordList[] = $recordId;
				}
				$relatedRecordIdList = array_slice($relatedRecordIdList, $relatedRecordId);
			}
		}

		foreach($relatedRecordList as $record) {
			$relatedRecordIdList[] = $record;
		}

		//Setting related module as current module to delete the relation
		vglobal('currentModule', $relatedModule);

		$sourceModuleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModule);
		$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach($relatedRecordIdList as $relatedRecordId) {
			$response = $relationModel->deleteRelation($sourceRecordId,$relatedRecordId);
		}

		$response = new Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function to get the page count for reltedlist
	 * @return total number of pages
	 */
	function getRelatedListPageCount(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');
		$label = $request->get('tab_label');
		$pagingModel = new Vtiger_Paging_Model();
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$totalCount = $relationListView->getRelatedEntriesCount();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $totalCount / (int) $pageLimit);

		if($pageCount == 0){
			$pageCount = 1;
		}
		$result = array();
		$result['numberOfRecords'] = $totalCount;
		$result['page'] = $pageCount;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	function getRelatedRecordInfo($request) {
		try {
			return $this->getParentRecordInfo($request);
		} catch (Exception $e) {
			$response = new Vtiger_Response();
			$response->setError($e->getCode(), $e->getMessage());
			$response->emit();
		}
	}

	function getParentRecordInfo($request) {
		$moduleName = $request->get('module');
		$recordModel = Vtiger_Record_Model::getInstanceById($request->get('id'), $moduleName);
		$moduleModel = $recordModel->getModule();
		$autoFillData = $moduleModel->getAutoFillModuleAndField($moduleName);

		if($autoFillData) {
			foreach($autoFillData as $data) {
				$autoFillModule = $data['module'];
				$autoFillFieldName = $data['fieldname'];
				$autofillRecordId = $recordModel->get($autoFillFieldName);

				$autoFillNameArray = getEntityName($autoFillModule, $autofillRecordId);
				$autoFillName = $autoFillNameArray[$autofillRecordId];

				$resultData[] = array(	'id'		=> $request->get('id'), 
										'name'		=> decode_html($recordModel->getName()),
										'parent_id'	=> array(	'id' => $autofillRecordId,
																'name' => decode_html($autoFillName),
																'module' => $autoFillModule));
			}

			$result[$request->get('id')] = $resultData;

		} else {
			$resultData = array('id'	=> $request->get('id'), 
								'name'	=> decode_html($recordModel->getName()),
								'info'	=> $recordModel->getRawData());
			$result[$request->get('id')] = $resultData;
		}

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

}
