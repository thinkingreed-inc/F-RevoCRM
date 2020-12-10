<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_PDFExport_View extends Vtiger_Index_View {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
		
		return $permissions;
	}

	function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);

		$source_module = $request->getModule();
		$viewId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$tagParams = $request->get('tag_params');
		$page = $request->get('page');
		$templateName = $request->get('templateName');
		$template = $request->get('template');

		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('VIEWID', $viewId);
		$viewer->assign('PAGE', $page);
		$viewer->assign('SOURCE_MODULE', $source_module);
		$viewer->assign('MODULE','Export');
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('TAG_PARAMS', $tagParams);
		$viewer->assign('TEMPLATE_NAME', $templateName);
		$viewer->assign('TEMPLATE', $template);

         // for the option of selecting currency while exporting inventory module records
        if(in_array($source_module, Vtiger_Functions::getLineItemFieldModules())){
           $viewer->assign('MULTI_CURRENCY',true);
        }
        
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
		$viewer->assign('SUPPORTED_FILE_TYPES', array('csv', 'ics'));
		$viewer->assign('SEARCH_PARAMS', $request->get('search_params'));
		$viewer->view('PDFExport.tpl', $source_module);
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$moduleName = $request->getModule();
		if (in_array($moduleName, getInventoryModules())) {
			$moduleEditFile = 'modules.'.$moduleName.'.resources.Edit';
			unset($headerScriptInstances[$moduleEditFile]);

			$jsFileNames = array(
				'modules.Inventory.resources.Edit',
				'modules.'.$moduleName.'.resources.Edit',
			);
		}

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}