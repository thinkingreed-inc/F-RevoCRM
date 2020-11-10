<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

abstract class Vtiger_Mass_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView');
        return $permissions;
	}
	
	protected function getRecordsListFromRequest(Vtiger_Request $request) {
		$cvId = $request->get('viewname');
		$module = $request->get('module');
		if(!empty($cvId) && $cvId=="undefined"){
			$sourceModule = $request->get('sourceModule');
			$cvId = CustomView_Record_Model::getAllFilterByModule($sourceModule)->getId();
		}
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		if(!empty($selectedIds) && $selectedIds != 'all') {
			if(!empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}
        $tagParams = $request->get('tag_params');
        $tag = $request->get('tag');
        $listViewSessionKey = $module.'_'.$cvId;

        if(!empty($tag)) {
            $listViewSessionKey .='_'.$tag;
        }

        $orderParams = Vtiger_ListView_Model::getSortParamsSession($listViewSessionKey);
        if(!empty($tag) && empty($tagParams)){
            $tagParams = $orderParams['tag_params'];
        }

        if(empty($tagParams)){
            $tagParams = array();
        }
        
        $searchParams = $request->get('search_params');
        if(empty($searchParams) || !is_array($searchParams)){
            $searchParams = array();
        }
        
        $searchAndTagParams = array_merge($searchParams, $tagParams);
        
		$customViewModel = CustomView_Record_Model::getInstanceById($cvId);
		if($customViewModel) {
            $searchKey = $request->get('search_key');
            $searchValue = $request->get('search_value');
            $operator = $request->get('operator');
            if(!empty($operator)) {
                $customViewModel->set('operator', $operator);
                $customViewModel->set('search_key', $searchKey);
                $customViewModel->set('search_value', $searchValue);
            }

            /**
			 *  Mass action on Documents if we select particular folder is applying on all records irrespective of
			 *  seleted folder
			 */
			if ($module == 'Documents') {
				$customViewModel->set('folder_id', $request->get('folder_id'));
				$customViewModel->set('folder_value', $request->get('folder_value'));
			}

			$customViewModel->set('search_params',$searchAndTagParams);
			return $customViewModel->getRecordIds($excludedIds,$module);
		}
	}
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}
