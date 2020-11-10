<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class EmailTemplates_List_View extends Vtiger_Index_View {

	function __construct() {
		parent::__construct();
	}

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
    public function checkPermission($request) {
        $moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if(!$moduleModel->isActive()){
            return false;
        }
        return true;
    }
    
	function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, false);

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$listViewModel = EmailTemplates_ListView_Model::getInstance($moduleName);

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$quickLinkModels = $listViewModel->getSideBarLinks($linkParams);

		$viewer->assign('QUICK_LINKS', $quickLinkModels);

		$this->initializeListViewContents($request, $viewer);

		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}

	function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('REQUEST_INSTANCE', $request);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$orderParams = Vtiger_ListView_Model::getSortParamsSession($moduleName);
		// TODO : need to remove this when vtiger6 is removed
		$defaultLayout = Vtiger_Viewer::getDefaultLayoutName();
		if($orderParams['viewType'] == 'grid' && $defaultLayout == 'v7'){
			$viewer->view('GridViewContents.tpl',$moduleName);
		} else {
			$viewer->view('ListViewContents.tpl', $moduleName);
		}
	}

	function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$viewer->view('ListViewPostProcess.tpl', $moduleName);
		parent::postProcess($request);
	}

	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */
	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$viewType = $request->get('viewType');
		$pageNumber = $request->get('page');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$sourceModule = $request->get('sourceModule');
		$operator = $request->get('operator');
		$orderParams = Vtiger_ListView_Model::getSortParamsSession($moduleName);
		if ($request->get('mode') == 'removeAlphabetSearch') {
			Vtiger_ListView_Model::deleteParamsSession($moduleName, array('search_key', 'search_value', 'operator'));
			$searchKey = '';
			$searchValue = '';
			$operator = '';
		}
		if ($request->get('mode') == 'removeSorting') {
			Vtiger_ListView_Model::deleteParamsSession($moduleName, array('orderby', 'sortorder'));
			$orderBy = '';
			$sortOrder = '';
		}
		if (empty($orderBy) && empty($searchValue) && empty($pageNumber)) {
			$orderParams = Vtiger_ListView_Model::getSortParamsSession($moduleName);
			if ($orderParams) {
				$pageNumber = $orderParams['page'];
				$orderBy = $orderParams['orderby'];
				$sortOrder = $orderParams['sortorder'];
				$searchKey = $orderParams['search_key'];
				$searchValue = $orderParams['search_value'];
				$operator = $orderParams['operator'];
				$viewType = $orderParams['viewType']; // Retrieving value from session
			}
		} else if ($request->get('nolistcache') != 1) {
			//Setting params to session
			$params = array('page' => $pageNumber, 'orderby' => $orderBy, 'sortorder' => $sortOrder, 'search_key' => $searchKey,
							'search_value' => $searchValue, 'operator' => $operator,'viewType' => $viewType);
			Vtiger_ListView_Model::setSortParamsSession($moduleName, $params);
		}

		if ($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "icon-chevron-down";
			$faSortImage = "fa-sort-desc";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "icon-chevron-up";
			$faSortImage = "fa-sort-asc";
		}

		if (empty($pageNumber)) {
			$pageNumber = '1';
		}

		$listViewModel = EmailTemplates_ListView_Model::getInstance($moduleName, $cvId);
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'), 'CVID' => $cvId);
		$linkModels = $listViewModel->getListViewMassActions($linkParams);

		// preProcess is already loading this, we can reuse
		if (!$this->pagingModel) {
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', $pageNumber);
		} else {
			$pagingModel = $this->pagingModel;
		}

		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}

		if (!empty($operator)) {
			$listViewModel->set('operator', $operator);
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
		}
		if (!empty($searchKey)) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}

		//Setting this key to get only that particular module templates
		if(!empty($sourceModule)){
			$listViewModel->set('sourceModule',$sourceModule);
		}

		if(empty($viewType)){
			$viewType = 'list';
		}

		$listViewModel->set('viewType',$viewType);

		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}

		if (!$this->pagingModel) {
			$this->pagingModel = $pagingModel;
		}

		$noOfEntries = count($this->listViewEntries);
		$viewer->assign('VIEWID', $cvId);
		$viewer->assign('MODULE', $moduleName);

		if (!$this->listViewLinks) {
			$this->listViewLinks = $listViewModel->getListViewLinks($linkParams);
		}
		$viewer->assign('LISTVIEW_LINKS', $this->listViewLinks);
		$viewer->assign('LISTVIEW_MASSACTIONS', $linkModels['LISTVIEWMASSACTION']);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('VIEWTYPE', $viewType);
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('SEARCH_VALUE', $searchValue);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);
		$viewer->assign('COLUMN_NAME', $orderBy);
		$viewer->assign('FASORT_IMAGE', $faSortImage);
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('RECORD_COUNT', $noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
			if (!$this->listViewCount) {
				$this->listViewCount = $listViewModel->getListViewCount();
			}
			$viewer->assign('LISTVIEW_COUNT', $this->listViewCount);
		}

		$viewer->assign('LIST_VIEW_MODEL', $listViewModel);
		$viewer->assign('IS_CREATE_PERMITTED', $listViewModel->getModule()->isPermitted('CreateView'));
		$viewer->assign('IS_MODULE_EDITABLE', $listViewModel->getModule()->isPermitted('EditView'));
		$viewer->assign('IS_MODULE_DELETABLE', $listViewModel->getModule()->isPermitted('Delete'));
	}

	/**
	 * Function returns the number of records for the current filter
	 * @param Vtiger_Request $request
	 */
	function getRecordsCount(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$count = $this->getListViewCount($request);

		$result = array();
		$result['module'] = $moduleName;
		$result['count'] = $count;

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get listView count
	 * @param Vtiger_Request $request
	 */
	function getListViewCount(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$sourceModule = $request->get('sourceModule');

		$listViewModel = EmailTemplates_ListView_Model::getInstance($moduleName);
		$listViewModel->set('search_key', $searchKey);
		$listViewModel->set('search_value', $searchValue);
		$listViewModel->set('operator', $request->get('operator'));
		$listViewModel->set('sourceModule', $sourceModule); // To get only that particular module records

		return $listViewModel->getListViewCount();
	}

	/**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	function getPageCount(Vtiger_Request $request) {
		$listViewCount = $this->getListViewCount($request);
		$pagingModel = new Vtiger_Paging_Model();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = ceil((int) $listViewCount / (int) $pageLimit);

		$result = array();
		$result['page'] = $pageCount;
		$result['numberOfRecords'] = $listViewCount;
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$jsFileNames = array(
					'modules.Vtiger.resources.List',
					'modules.EmailTemplates.resources.List',
					'modules.Settings.Vtiger.resources.Vtiger',
					'modules.Settings.Vtiger.resources.Index',
					"~layouts/v7/lib/jquery/sadropdown.js",
					"~layouts/".Vtiger_Viewer::getDefaultLayoutName()."/lib/jquery/floatThead/jquery.floatThead.js",
					"~layouts/".Vtiger_Viewer::getDefaultLayoutName()."/lib/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js",
					"~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js"
				);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

		 public function getHeaderCss(Vtiger_Request $request) {
			$headerCssInstances = parent::getHeaderCss($request);

			$cssFileNames = array(
				"~layouts/".Vtiger_Viewer::getDefaultLayoutName()."/lib/jquery/perfect-scrollbar/css/perfect-scrollbar.css",
			);
			$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
			$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

			return $headerCssInstances;
		}
}