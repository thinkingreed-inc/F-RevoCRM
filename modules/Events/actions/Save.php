<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Events_Save_Action extends Calendar_Save_Action {
	public function process(Vtiger_Request $request) {
		try {
			$recordModel = $this->saveRecord($request);
			$this->deleteDuplicateInviteeEvent($recordModel);

			$refererUrl  = $_SERVER['HTTP_REFERER'];
			$parsedurl = parse_url($refererUrl);
			$searchParams = explode('&', $parsedurl['query']);
			foreach ($searchParams as $searchParam){
				$explodedParams = explode('=', $searchParam);
				$parsedParams[$explodedParams[0]] =$explodedParams[1];
			}
			if($parsedParams['referer'] == 'SharedCalendar') {
				$loadUrl = 'index.php?module=Calendar&view=SharedCalendar&calendarStartDate='. $recordModel->get('date_start');
			} else {
				$loadUrl = 'index.php?module=Calendar&view=Calendar&calendarStartDate='. $recordModel->get('date_start');
			}

			if ($request->get('fromQuickCreate')) {
				$loadUrl = 'index.php'.$request->get('quickCreateReturnURL');
			} else if($request->get('returntab_label')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$moduleModel = $recordModel->getModule();
				$listViewUrl = $moduleModel->getListViewUrl();

				if ($recordModel->get('visibility') === 'Private') {
					$loadUrl = $listViewUrl;
				} else {
					$userId = $recordModel->get('assigned_user_id');
					$sharedType = $moduleModel->getSharedType($userId);
					if ($sharedType === 'selectedusers') {
						$currentUserModel = Users_Record_Model::getCurrentUserModel();
						$sharedUserIds = Calendar_Module_Model::getCaledarSharedUsers($userId);
						if (!array_key_exists($currentUserModel->id, $sharedUserIds)) {
							$loadUrl = $listViewUrl;
						}
					} else if ($sharedType === 'private') {
						$loadUrl = $listViewUrl;
					}
				}
			} else if ($request->get('returnmodule') && $request->get('returnview')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$mode = '';
			if ($request->getModule() === 'Events') {
				$mode = 'Events';
			}

			$requestData = $request->getAll();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['mode'] = $mode;
				$requestData['module'] = 'Calendar';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();
				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', "index.php?module=Calendar&view=Edit&mode=$mode&record=".$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
            }
		} catch (Exception $e) {
			 throw new Exception($e->getMessage());
		}
	}
	
	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$adb = PearDatabase::getInstance();
		$recordModel = $this->getRecordModelFromRequest($request);
		$recurObjDb = false;
		if($recordModel->get('mode') == 'edit') {
			$recurObjDb = $recordModel->getRecurringObject();
		}
		$recordModel->save();
		$originalRecordId = $recordModel->getId();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}

		// Handled to save follow up event
		$followupMode = $request->get('followup');

		//Start Date and Time values
		$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('followup_time_start'));
		$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('followup_date_start') . " " . $startTime);
		list($startDate, $startTime) = explode(' ', $startDateTime);

		$subject = $request->get('subject');
		if($followupMode == 'on' && $startTime != '' && $startDate != ''){
			$record = $this->getRecordModelFromRequest($request);
			$record->set('eventstatus', 'Planned');
			//recurring events status should not be held for future events
			$recordModel->set('eventstatus', 'Planned');
			$record->set('subject','[Followup] '.$subject);
			$record->set('date_start',$startDate);
			$record->set('time_start',$startTime);

			$currentUser = Users_Record_Model::getCurrentUserModel();
			$activityType = $record->get('activitytype');
			if($activityType == 'Call') {
				$minutes = $currentUser->get('callduration');
			} else {
				$minutes = $currentUser->get('othereventduration');
			}
			$dueDateTime = date('Y-m-d H:i:s', strtotime("$startDateTime+$minutes minutes"));
			list($startDate, $startTime) = explode(' ', $dueDateTime);

			$record->set('due_date',$startDate);
			$record->set('time_end',$startTime);
			$record->set('recurringtype', '');
			$record->set('mode', 'create');
			$record->save();
			$heldevent = true;
		}
		$recurringEditMode = $request->get('recurringEditMode');
		$recordModel->set('recurringEditMode', $recurringEditMode);

		vimport('~~/modules/Calendar/RepeatEvents.php');
		$recurObj = getrecurringObjValue();
		$recurringDataChanged = Calendar_RepeatEvents::checkRecurringDataChanged($recurObj, $recurObjDb);
		//TODO: remove the dependency on $_REQUEST
		if(($_REQUEST['recurringtype'] != '' && $_REQUEST['recurringtype'] != '--None--' && $recurringEditMode != 'current') || ($recurringDataChanged && empty($recurObj))) {
			$focus =  CRMEntity::getInstance('Events');
			//get all the stored data to this object
			$focus->column_fields = new TrackableObject($recordModel->getData());
			if($recordModel->get('is_allday')){
				$focus->is_allday = true;
			}else{
				$focus->is_allday = false;
			}
			try {
				Calendar_RepeatEvents::repeatFromRequest($focus, $recurObjDb);
			} catch (DuplicateException $e) {
                $requestData = $request->getAll();
			    $requestData['view'] = 'Edit';
				$requestData['mode'] = 'Events';
				$requestData['module'] = 'Events';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
                
                global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();
                $viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', 'index.php?module=Calendar&view=Edit&mode=Events&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
                exit();
            } catch (Exception $ex) {
				throw new Exception($ex->getMessage());
			}
		}
		return $recordModel;
	}


	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {
		$recordModel = parent::getRecordModelFromRequest($request);
		if($request->has('selectedusers')) {
			$recordModel->set('selectedusers', $request->get('selectedusers'));
		}
		return $recordModel;
	}
	
	/**
     * parentidが同じ複数のレコードで参加者が重複した場合はparentレコードではないものを論理削除する
     *
     * @param Vtiger_Record_Model $recordModel
     * @return void
     *
    */
    function deleteDuplicateInviteeEvent(Vtiger_Record_Model $recordModel)
    {
		global $adb;
		$recordId = $recordModel->getId();
		$ownerId  = $recordModel->get('assigned_user_id');
		
		// parentid と 参加者が重複している招待レコードを探す
		$query = "SELECT activityid, invitee_parentid, smownerid
				  FROM vtiger_activity 
				  WHERE invitee_parentid = ( SELECT invitee_parentid 
											 FROM vtiger_activity 
											 WHERE activityid = ? ) 
					AND smownerid = ? AND deleted = 0";
		$params      = [ $recordId, $ownerId ];
        $result      = $adb->pquery($query, $params);
		$resultCount = $adb->num_rows($result);
		
		if($resultCount === 1) {
			return;
		}
		
		// 参加者が重複している招待レコードがあれば論理削除する
		for($i=0; $i<$resultCount; $i++) {
			$activityId = $adb->query_result($result, $i, 'activityid');
			$inviteeParentId  = $adb->query_result($result, $i, 'invitee_parentid');
			$smownerId        = $adb->query_result($result, $i, 'smownerid');
			
			// 親レコードは消さない
			if($activityId === $inviteeParentId) {
				continue;
			}
			
			// 削除するレコードのRecordModelを取得
			$recordModel  = Vtiger_Record_Model::getInstanceById($activityId, 'Events');
			
			// 繰り返し情報を削除
			$recurringQuery  = "DELETE 
								FROM vtiger_activity_recurring_info 
								WHERE recurrenceid= ?";
			$recurringParams = [ $recordModel->getId() ]; 
			$adb->pquery($recurringQuery, $recurringParams);
			
			// 参加者情報を再度作り直す（子レコードで更新した場合に完全に招待が消えることがあるため）
			$inviteeQuery  = "DELETE FROM vtiger_invitees WHERE activityid = ?";
			$inviteeParams = [ $inviteeParentId ]; 
			$adb->pquery($inviteeQuery, $inviteeParams);
			
			// 参加者のIDを取得
			$query         = "SELECT smownerid 
							  FROM vtiger_activity 
							  WHERE deleted = 0 
							  	AND invitee_parentid = ? AND activityid <> ?";
			$params        = [ $inviteeParentId, $recordModel->getId() ];
			$result        = $adb->pquery($query, $params);
			
			for($i=0; $i<$adb->num_rows($result); $i++) {
				$smownerId = $adb->query_result($result, $i, 'smownerid');
				$inviteeQuery  = "INSERT INTO vtiger_invitees VALUES (?,?,?)";
				$inviteeParams = [ $inviteeParentId, $smownerId, 'sent' ]; 
				$adb->pquery($inviteeQuery, $inviteeParams);
			}
			
			// レコードの削除
			$recordModel->getModule()->deleteRecord($recordModel);
		}
    }
}
