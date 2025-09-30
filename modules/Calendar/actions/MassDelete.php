<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_MassDelete_Action extends Vtiger_MassDelete_Action {

	public function process(Vtiger_Request $request) {
		$adb = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$cvId = $request->get('viewname');

		if ($request->get('selected_ids') == 'all' && $request->get('mode') == 'FindDuplicates') {
            $recordIds = Vtiger_FindDuplicate_Model::getMassDeleteRecords($request);
        } else {
            $recordIds = $this->getRecordsListFromRequest($request);
        }
		
		$skipRecords = [];
		asort($recordIds, SORT_NUMERIC);
		foreach($recordIds as $recordId) {
			if(Users_Privileges_Model::isPermitted($moduleName, 'Delete', $recordId)) {
				// Inveteeで作成された子レコードは親が削除された際に削除されるので、改めて削除処理行われないようにする
				$query = 'SELECT activityid 
						  FROM vtiger_activity 
						  WHERE invitee_parentid = ? 
						  	AND deleted = 0 
							AND activityid <> ? 
						  ORDER BY activityid';
				$result = $adb->pquery($query, array($recordId, $recordId));
				for($i = 0; $i < $adb->num_rows($result); $i++) {
					$skipRecords[$adb->query_result($result, $i, 'activityid')] = true;
				}
				
				if (isset($skipRecords[$recordId])) {
					unset($skipRecords[$recordId]);
					continue;
				}
				
				$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleModel);
				$parentRecurringId = $recordModel->getParentRecurringRecord();
				$adb->pquery('DELETE FROM vtiger_activity_recurring_info WHERE activityid=? AND recurrenceid=?', array($parentRecurringId, $recordId));
				$recordModel->delete();
				deleteRecordFromDetailViewNavigationRecords($recordId, $cvId, $moduleName);
			}
		}
		$response = new Vtiger_Response();
		$response->setResult(array('viewname'=>$cvId, 'module'=>$moduleName));
		$response->emit();
	}
}
