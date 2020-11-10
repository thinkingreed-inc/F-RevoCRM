<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'include/Webservices/Retrieve.php';

class Mobile_WS_FetchRecord extends Mobile_WS_Controller {
	
	private $module = false;
	
	protected $resolvedValueCache = array();
	
	protected function detectModuleName($recordid) {
		if($this->module === false) {
			$this->module = Mobile_WS_Utils::detectModulenameFromRecordId($recordid);
		}
		return $this->module;
	}
	
	protected function processRetrieve(Mobile_API_Request $request) {
		$current_user = $this->getActiveUser();

		$recordid = $request->get('record');
		if($request->get('view_mode') == 'web'){
			$module = $request->get('module');
			$recordid = vtws_getWebserviceEntityId($module, $recordid);
		}
		$record = vtws_retrieve($recordid, $current_user);
		
		return $record;
	}
	
	function process(Mobile_API_Request $request) {
		$current_user = $this->getActiveUser();
		$mode = $request->get('mode');
		if(!empty($mode) && method_exists($this, $mode)) {
			$result = $this->$mode($request);
			
			$response = new Mobile_API_Response();
			$response->setResult($result);
        
			return $response;
		}
		else{
			$record = $this->processRetrieve($request);
		
			$this->resolveRecordValues($record, $current_user);
		
			$response = new Mobile_API_Response();
			$response->setResult(array('record' => $record));
		
			return $response;
		}
	}
	
	public function resolveRecordValues(&$record, $user, $ignoreUnsetFields=false) {
		if(empty($record)) return $record;

        $fieldnamesToResolve = Mobile_WS_Utils::detectFieldnamesToResolve(
			$this->detectModuleName($record['id']) );
		
		if(!empty($fieldnamesToResolve)) {
			foreach($fieldnamesToResolve as $resolveFieldname) {
				if ($ignoreUnsetFields === false || isset($record[$resolveFieldname])) {
					$fieldvalueid = $record[$resolveFieldname];
					$fieldvalue = $this->fetchRecordLabelForId($fieldvalueid, $user);
					$record[$resolveFieldname] = array('value' => $fieldvalueid, 'label'=>decode_html($fieldvalue));
				}
			}
		}
	}
	
	function fetchRecordLabelForId($id, $user) {
		$value = null;
		
		if (isset($this->resolvedValueCache[$id])) {
			$value = $this->resolvedValueCache[$id];
		} else if(!empty($id)) {
			$value = trim(vtws_getName($id, $user));
			$this->resolvedValueCache[$id] = $value;
		} else {
			$value = $id;
		}
		return decode_html($value);
	}
	
	function getRelatedRecordCount(Mobile_API_Request $request) {
		$record = $request->get('record');
		$module = $request->get('module');
        global $currentModule;
        $currentModule = $module;
        
		$parentModuleModel = Vtiger_Module_Model::getInstance($module);
        $parentRecordModel = Vtiger_Record_Model::getInstanceById($record, $parentModuleModel);
        $relationModels = $parentModuleModel->getRelations();
        $relatedRecordsCount = array();
		
		foreach($relationModels as $relation) {
            $relatedModuleName = $relation->get('relatedModuleName');
            if($relatedModuleName === 'ModTracker') {
                continue;
            }
            $relationId = $relation->getId();
			$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $relation->get('label'),$relationId);
			$count = $relationListView->getRelatedEntriesCount();
			$relatedLabel = vtranslate($relation->get('label'), $relatedModuleName);
			$relatedRecordsCount[$relatedLabel] = array('count'=>$count,'relatedModule'=>$relatedModuleName);
        }

		return $relatedRecordsCount;
	}
}
