<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once('modules/Calendar/CalendarCommon.php');

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
     * 活動レコードのinvitee_parentidが同じ複数の招待レコードにて
	 * 参加者を重複登録しようとした場合は重複した招待レコードを論理削除する
     *
     * @param Vtiger_Record_Model $recordModel
     * @return void
    */
    public function deleteDuplicateInviteeEvent(Vtiger_Record_Model $recordModel)
    {
		global $adb, $log;
		$recordId = $recordModel->getId();
		$ownerId  = $recordModel->get('assigned_user_id');
		
		// 参加者が重複している活動レコードを探す
		$getDuplicateQuery    = "SELECT activityid, invitee_parentid
								 FROM vtiger_activity 
								 WHERE invitee_parentid = ( SELECT invitee_parentid 
															FROM vtiger_activity 
															WHERE activityid = ? ) 
								   AND smownerid = ? AND deleted = 0";
		$getDuplicateParams   = [ $recordId, $ownerId ];
        $duplicateResult      = $adb->pquery($getDuplicateQuery, $getDuplicateParams);
		$duplicateResultCount = $adb->num_rows($duplicateResult);
		
		// 重複がない場合
		if(!$duplicateResult || $duplicateResultCount === 1) {
			return;
		}
		
		// 参加者が重複している活動レコードがあれば論理削除する
		// 親レコードと招待レコードが重複している場合は招待レコードを削除する制御を行う

		$isDuplicateParentRecord = false;
		for($i=0; $i<$duplicateResultCount; $i++) {
			$activityId       = $adb->query_result($duplicateResult, $i, 'activityid');
			$inviteeParentId  = $adb->query_result($duplicateResult, $i, 'invitee_parentid');
			
			// 親レコードは削除しないで親レコードと重複したことを記録
			if($activityId === $inviteeParentId) {
				$isDuplicateParentRecord = true;
				continue;
			}
			
			// 1. 招待レコード間で重複した場合、編集された招待レコード自身は削除しない
			// 2. 親レコードと重複した場合は招待レコード自身を削除
			$isDuplicateInviteeRecords = $duplicateResultCount > 1 && $activityId === $recordId;
			if($isDuplicateInviteeRecords && !$isDuplicateParentRecord) {
				continue;
			}
			
			try {
				// 削除する招待レコードのRecordModelを取得
				$recordModel  = Vtiger_Record_Model::getInstanceById($activityId, 'Events');
				
				// 削除する招待レコードの繰り返し情報を削除
				$deleteRecurringQuery  = "DELETE 
										  FROM vtiger_activity_recurring_info 
										  WHERE recurrenceid= ?";
				$deleteRecurringParams = [ $activityId ]; 
				$adb->pquery($deleteRecurringQuery, $deleteRecurringParams);
				
				// 削除する招待レコードの参加者を除いて、参加者情報（vtiger_invitees）を再作成
				reCreateInviteesRecord($activityId, $inviteeParentId);
				
				// 招待レコードの削除
				$recordModel->getModule()->deleteRecord($recordModel);
				
			} catch(Exception $e) {
				$errMsg = "deleteDuplicateInviteeEvent error  "
						.$e->getMessage().":".$e->getTraceAsString();
				$log->error($errMsg);
				throw new Exception($errMsg);
			}
		} 
    }
}
