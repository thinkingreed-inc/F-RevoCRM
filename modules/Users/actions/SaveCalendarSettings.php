<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_SaveCalendarSettings_Action extends Users_Save_Action {


	public function process(Vtiger_Request $request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		
		$recordModel->save();
		$this->saveCalendarSharing($request);

		$arrayofViews = array('ListView' => 'List', 'MyCalendar' => 'Calendar','SharedCalendar'=>'SharedCalendar');
		$calendarViewName = $recordModel->get('defaultcalendarview');
		if(array_key_exists($calendarViewName, $arrayofViews)) {
			$calendarViewName = $arrayofViews[$calendarViewName];
		}
		if(empty($calendarViewName)) {
			$calendarViewName = 'Calendar';
		}

		header("Location: index.php?module=Calendar&view=$calendarViewName");
	}

	/**
	 * Function to update Calendar Sharing information
	 * @params - Vtiger_Request $request
	 */
	public function saveCalendarSharing(Vtiger_Request $request){
		
		$sharedIds = $request->get('sharedIds');
		$sharedType = $request->get('sharedtype');

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');
		$recordId = $request->get('record');
		if (empty($recordId)) {
			$recordId = $currentUserModel->id;
		}

		if($sharedType == 'private'){
			$calendarModuleModel->deleteSharedUsers($recordId);
		}else if($sharedType == 'public'){
            $allUsers = Users_Record_Model::getAll(true);
			$accessibleUsers = array();
			foreach ($allUsers as $id => $userModel) {
				$accessibleUsers[$id] = $id;
			}
			$calendarModuleModel->deleteSharedUsers($recordId);
			$calendarModuleModel->insertSharedUsers($recordId, array_keys($accessibleUsers));
		}else{
			if(!empty($sharedIds)){
				$calendarModuleModel->deleteSharedUsers($recordId);
				$calendarModuleModel->insertSharedUsers($recordId, $sharedIds);
			}else{
				$calendarModuleModel->deleteSharedUsers($recordId);
			}
		}
	}
}
