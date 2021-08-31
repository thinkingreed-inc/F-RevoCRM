<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_RelatedList_View extends Vtiger_Index_View {
	
	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		$permissions[] = array('module_parameter' => 'relatedModule', 'action' => 'DetailView');
		
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}
	
	function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$parentId = $request->get('record');
		$label = $request->get('tab_label');

		$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
		$moduleFields = $relatedModuleModel->getFields();
        $searchParams = $request->get('search_params');
        
        if(empty($searchParams)) {
            $searchParams = array();
        }
        
        $whereCondition = array();
        
        foreach($searchParams as $fieldListGroup){
            foreach($fieldListGroup as $fieldSearchInfo){
                $fieldModel = $moduleFields[$fieldSearchInfo[0]];
                $tableName = $fieldModel->get('table');
                $column = $fieldModel->get('column');
                $whereCondition[$fieldSearchInfo[0]] = array($tableName.'.'.$column, $fieldSearchInfo[1],  $fieldSearchInfo[2], $fieldSearchInfo[3]);
                
                $fieldSearchInfoTemp= array();
                $fieldSearchInfoTemp['searchValue'] = $fieldSearchInfo[2];
                $fieldSearchInfoTemp['fieldName'] = $fieldName = $fieldSearchInfo[0];
                $fieldSearchInfoTemp['comparator'] = $fieldSearchInfo[1];
                $searchParams[$fieldName] = $fieldSearchInfoTemp;
            }
       }
       
		$requestedPage = $request->get('page');
		if(empty($requestedPage)) {
			$requestedPage = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page',$requestedPage);

		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
        
        if(!empty($whereCondition))
            $relationListView->set('whereCondition', $whereCondition);
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		if($sortOrder == 'ASC') {
			$nextSortOrder = 'DESC';
			$sortImage = 'icon-chevron-down';
            $faSortImage = "fa-sort-desc";
		} else {
			$nextSortOrder = 'ASC';
			$sortImage = 'icon-chevron-up';
            $faSortImage = "fa-sort-asc";
		}
		if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder',$sortOrder);
		}
		$relationListView->tab_label = $request->get('tab_label');
		$models = $relationListView->getEntries($pagingModel);
		$links = $relationListView->getLinks();
		$header = $relationListView->getHeaders();
		$noOfEntries = $pagingModel->get('_relatedlistcount');
		if(!$noOfEntries) {
			$noOfEntries = count($models);
		}
		$relationModel = $relationListView->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relationField = $relationModel->getRelationField();
        
		$fieldsInfo = array();
		foreach($moduleFields as $fieldName => $fieldModel){
				$fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RELATED_FIELDS_INFO', json_encode($fieldsInfo));
		$viewer->assign('IS_CREATE_PERMITTED', isPermitted($relatedModuleName, 'CreateView'));
		$viewer->assign('RELATED_RECORDS' , $models);
		$viewer->assign('PARENT_RECORD', $parentRecordModel);
		$viewer->assign('RELATED_LIST_LINKS', $links);
		/**
		 * UITYPE10の項目を活動に追加した場合に表示される関連では、
		 * 追加ボタンを活動、TODOのどちらかだけを表示する必要がある（活動関連の場合にTODOを選んで追加しても一覧に表示されない為）
		 * その為、UITYPE10の関連を表示する場合のみ、どのモジュールに紐付いているかを確認し、Calendar、またはEventsの場合は
		 * 判定を行い、表示切り替えを行えるようにする
		 */
		// RELATED_LIST_LINKSがカレンダーの場合、条件に応じて表示する項目を変更する
		$relationFieldId = $relationField->id;
		// uitypeを確認する
		global $adb;
		$result = $adb->pquery("SELECT uitype FROM vtiger_field WHERE fieldid = ?", array($relationFieldId));
		$cnt = $adb->num_rows($result);
		$relmodules = array();
		$calFieldCheck = false;
		if($cnt > 0){
			$uitype = $adb->query_result($result, 0, 'uitype');
			if($uitype == 10){
				$result = $adb->pquery("SELECT module FROM vtiger_fieldmodulerel WHERE fieldid = ?", array($relationFieldId));
				$cnt = $adb->num_rows($result);
				if($cnt > 0){
					for ($i=0; $i < $cnt; $i++) { 
						$relmodule = $adb->query_result($result, $i, 'module');
						if($relmodule == 'Calendar'){
							$relmodule = 'Events';
						}
						$relmodules[] = $relmodule;
						if($relmodule == 'Calendar' || $relmodule == 'Events'){
							$calFieldCheck = true;
						}
					}
				}
			}
		}
		$viewer->assign('FIELD_RELATED_MODULES', $relmodules);
		$viewer->assign('CAL_FIELD_CHECK', $calFieldCheck);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE', $relatedModuleModel);
		$viewer->assign('RELATED_ENTIRES_COUNT', $noOfEntries);
		$viewer->assign('RELATION_FIELD', $relationField);
		$appName = $request->get('app');
		if(!empty($appName)){
			$viewer->assign('SELECTED_MENU_CATEGORY',$appName);
		}

		if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
			$totalCount = $relationListView->getRelatedEntriesCount();
			$pageLimit = $pagingModel->getPageLimit();
			$pageCount = ceil((int) $totalCount / (int) $pageLimit);

			if($pageCount == 0){
				$pageCount = 1;
			}
			$viewer->assign('PAGE_COUNT', $pageCount);
			$viewer->assign('TOTAL_ENTRIES', $totalCount);
			$viewer->assign('PERFORMANCE', true);
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('PAGING', $pagingModel);

		$viewer->assign('ORDER_BY',$orderBy);
		$viewer->assign('SORT_ORDER',$sortOrder);
		$viewer->assign('NEXT_SORT_ORDER',$nextSortOrder);
		$viewer->assign('SORT_IMAGE',$sortImage);
        $viewer->assign('FASORT_IMAGE',$faSortImage);
		$viewer->assign('COLUMN_NAME',$orderBy);

		$viewer->assign('IS_EDITABLE', $relationModel->isEditable());
		$viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('PARENT_ID', $parentId);
        $viewer->assign('SEARCH_DETAILS', $searchParams);
		$viewer->assign('TAB_LABEL', $request->get('tab_label'));
        return $viewer->view('RelatedList.tpl', $moduleName, 'true');
	}
}
