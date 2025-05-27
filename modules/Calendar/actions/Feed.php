<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

vimport ('~~/include/Webservices/Query.php');

class Calendar_Feed_Action extends Vtiger_BasicAjax_Action {
	private $cacheUser = array();
	private $cacheParent = array();
	private $cacheModule = array();

	public function process(Vtiger_Request $request) {
		if($request->get('mode') === 'batch') {
			$feedsRequest = $request->get('feedsRequest',array());
			$result = array();
			if(php7_count($feedsRequest)) {
				foreach($feedsRequest as $key=>$value) {
					$requestParams = array();
					$requestParams['start'] = $value['start'];
					$requestParams['end'] = $value['end'];
					$requestParams['type'] = $value['type'];
					$requestParams['userid'] = $value['userid'];
					$requestParams['color'] = $value['color'];
					$requestParams['textColor'] = $value['textColor'];
					$requestParams['targetModule'] = $value['targetModule'];
					$requestParams['fieldname'] = $value['fieldname'];
					$requestParams['group'] = $value['group'];
					$requestParams['mapping'] = $value['mapping'];
					$requestParams['conditions'] = $value['conditions'];
					$requestParams['is_own'] = $value['is_own'];
					$result[$key] = $this->_process($requestParams);
				}
			}
			echo json_encode($result);
		} else {
			$requestParams = array();
			$requestParams['start'] = $request->get('start');
			$requestParams['end'] = $request->get('end');
			$requestParams['type'] = $request->get('type');
			$requestParams['userid'] = $request->get('userid');
			$requestParams['color'] = $request->get('color');
			$requestParams['textColor'] = $request->get('textColor');
			$requestParams['targetModule'] = $request->get('targetModule');
			$requestParams['fieldname'] = $request->get('fieldname');
			$requestParams['group'] = $request->get('group');
			$requestParams['mapping'] = $request->get('mapping');
			$requestParams['conditions'] = $request->get('conditions','');
			$requestParams['is_own'] = $request->get('is_own','1');
			echo $this->_process($requestParams);
		}
	}

	public function _process($request) {
		try {
			$start = $request['start'];
			$end = $request['end'];
			$type = $request['type'];
			$userid = $request['userid'];
			$color = $request['color'];
			$textColor = $request['textColor'];
			$targetModule = $request['targetModule'];
			$fieldName = $request['fieldname'];
			$isGroupId = $request['group'];
			$mapping = $request['mapping'];
			$conditions = $request['conditions'];
			$isOwn = $request['is_own'];
			$result = array();
			switch ($type) {
				case 'Events'			:	if($fieldName == 'date_start,due_date' || $userid) {
												$this->pullEvents($start, $end, $result,$userid,$color,$textColor,$isGroupId,$conditions);
											} else {
												$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor, $conditions);
											}
											break;
				case 'Calendar'			:	if($fieldName == 'date_start,due_date') {
												$this->pullTasks($start, $end, $result,$color,$textColor);
											} else {
												$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor);
											}
											break;
				case 'MultipleEvents'	:	$this->pullMultipleEvents($start,$end, $result,$mapping);break;
				case $type				:	$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor, $conditions ,$isOwn);break;
			}
			return json_encode($result);
		} catch (Exception $ex) {
			return $ex->getMessage();
		}
	}

	private function valForSql($value) {
		return Vtiger_Util_Helper::validateStringForSql($value);
	}

	protected function pullDetails($start, $end, &$result, $type, $fieldName, $color = null, $textColor = 'white', $conditions = '', $isOwn = '1') {
		$moduleModel = Vtiger_Module_Model::getInstance($type);
		$nameFields = $moduleModel->getNameFields();
		foreach($nameFields as $i => $nameField) {
			$fieldInstance = $moduleModel->getField($nameField);
			if(!$fieldInstance->isViewable()) {
				unset($nameFields[$i]);
			}
		}
		$nameFields = array_values($nameFields);
		$selectFields = implode(',', $nameFields);		
		$fieldsList = explode(',', $fieldName);
		if(php7_count($fieldsList) == 2) {
			$db = PearDatabase::getInstance();
			$user = Users_Record_Model::getCurrentUserModel();
			$userAndGroupIds = array_merge(array($user->getId()),$this->getGroupsIdsForUsers($user->getId()));
			$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
			$meta = $queryGenerator->getMeta($moduleModel->get('name'));

			$queryGenerator->setFields(array_merge(array_merge($nameFields, array('id')), $fieldsList));
			//カレンダー種別でタスク-開始日,終了日を選択している時の処理
			if($type == 'ProjectTask'){
				$queryGenerator->setFields(array_merge(array_merge($nameFields, array('id','projecttaskstatus')), $fieldsList));
			}
			$query = $queryGenerator->getQuery();
			$startDateColumn = Vtiger_Util_Helper::validateStringForSql($fieldsList[0]);
			$endDateColumn = Vtiger_Util_Helper::validateStringForSql($fieldsList[1]);
			$query.= " AND (($startDateColumn >= ? AND $endDateColumn < ?) OR ($endDateColumn >= ?)) ";
			$params = array($start,$end,$start);
			if(!empty($isOwn)) {
				$query.= " AND vtiger_crmentity.smownerid IN (".generateQuestionMarks($userAndGroupIds).")";
				$params = array_merge($params, $userAndGroupIds);
			}
			$queryResult = $db->pquery($query, $params);

			$records = array();
			while($rowData = $db->fetch_array($queryResult)) {
				$records[] = DataTransform::sanitizeDataWithColumn($rowData, $meta);
			}
		} else {
			if($fieldName == 'birthday') {
				$startDateComponents = split('-', $start);
				$endDateComponents = split('-', $end);

				$year = $startDateComponents[0];
				$db = PearDatabase::getInstance();
				$user = Users_Record_Model::getCurrentUserModel();
				$userAndGroupIds = array_merge(array($user->getId()),$this->getGroupsIdsForUsers($user->getId()));
				$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
				$meta = $queryGenerator->getMeta($moduleModel->get('name'));

				$queryGenerator->setFields(array_merge(array_merge($nameFields, array('id')), $fieldsList));
				$query = $queryGenerator->getQuery();
				$query.= " AND ((CONCAT(?, date_format(birthday,'%m-%d')) >= ? AND CONCAT(?, date_format(birthday,'%m-%d')) <= ? )";
				$params = array("$year-",$start,"$year-",$end);
				$endDateYear = $endDateComponents[0]; 
				if ($year !== $endDateYear) {
					$query .= " OR (CONCAT(?, date_format(birthday,'%m-%d')) >= ?  AND CONCAT(?, date_format(birthday,'%m-%d')) <= ? )"; 
					$params = array_merge($params,array("$endDateYear-",$start,"$endDateYear-",$end));
				} 
				$query .= ")";
				if(!empty($isOwn)) {
					$query.= " AND vtiger_crmentity.smownerid IN (".  generateQuestionMarks($userAndGroupIds).")";
					$params = array_merge($params,$userAndGroupIds);
				}
				$queryResult = $db->pquery($query, $params);
				$records = array();
				while($rowData = $db->fetch_array($queryResult)) {
					$records[] = DataTransform::sanitizeDataWithColumn($rowData, $meta);
				}
			} else {
				$query = "SELECT $selectFields, $fieldsList[0] FROM $type";
				$query.= " WHERE $fieldsList[0] >= '$start' AND $fieldsList[0] <= '$end' ";


				if(!empty($conditions)) {
					$conditions = Zend_Json::decode(Zend_Json::decode($conditions));
					$query .=  'AND '.$this->generateCalendarViewConditionQuery($conditions);
				}

				if($type == 'PriceBooks') {
					$records = $this->queryForRecords($query, false);
				//カレンダー種別でタスク-開始日またはタスク-終了日を選択している時の処理
				}else if($type == 'ProjectTask'){
					$query = "SELECT $selectFields, $fieldsList[0],projecttaskstatus FROM $type";
					$query.= " WHERE $fieldsList[0] >= '$start' AND $fieldsList[0] <= '$end' ";
					$records = $this->queryForRecords($query, !empty($isOwn));
				} else {
					$records = $this->queryForRecords($query, !empty($isOwn));
				}
			}
		}
		foreach ($records as $record) {
			$item = array();
			list ($modid, $crmid) = vtws_getIdComponents($record['id']);
			$item['id'] = $crmid;
			$item['title'] = decode_html($record[$nameFields[0]]);
			if(php7_count($nameFields) > 1) {
				$item['title'] = decode_html(trim($record[$nameFields[0]].' '.$record[$nameFields[1]]));
			}
			if(!empty($record[$fieldsList[0]])) {
				$item['start'] = $record[$fieldsList[0]];
			} else {
				$item['start'] = $record[$fieldsList[1]];
			}
			if(php7_count($fieldsList) == 2) {
				$item['end'] = $record[$fieldsList[1]];
			}
			if($fieldName == 'birthday') {
				$recordDateTime = new DateTime($record[$fieldName]); 

				$calendarYear = $year; 
				if($recordDateTime->format('m') < $startDateComponents[1]) { 
						$calendarYear = $endDateYear; 
				} 
				$recordDateTime->setDate($calendarYear, $recordDateTime->format('m'), $recordDateTime->format('d'));
				$item['start'] = $recordDateTime->format('Y-m-d');
			}
			if($type == 'ProjectTask'&& $record['projecttaskstatus']!=null){
				$item['title'] = $item['title'].' - ('.decode_html(vtranslate($record['projecttaskstatus'],'ProjectTask')).')';
			}

			$urlModule = $type;
			if ($urlModule === 'Events') {
				$urlModule = 'Calendar';
			}
			$item['status'] = $record['projecttaskstatus'];
			$item['url']   = sprintf('index.php?module='.$urlModule.'&view=Detail&record=%s', $crmid);
			$item['color'] = $color;
			$item['textColor'] = $textColor;
			$item['module'] = $moduleModel->getName();
			$item['sourceModule'] = $moduleModel->getName();
			$item['fieldName'] = $fieldName;
			$item['conditions'] = '';
			$item['end'] = date('Y-m-d', strtotime(($item['end'] ?: $item['start']).' +1day'));
                        if(!empty($conditions)) {
                            $item['conditions'] = Zend_Json::encode(Zend_Json::encode($conditions));
                        }
                        $result[] = $item;
		}
	}

	protected function generateCalendarViewConditionQuery($conditions) {
		$conditionQuery = $operator = '';
		switch ($conditions['operator']) {
			case 'e' : $operator = '=';
		}

		if(!empty($operator) && !empty($conditions['fieldname']) && !empty($conditions['value'])) {
			$fieldname = vtlib_purifyForSql($conditions['fieldname']);
			if (empty($fieldname)) throw new Exception('Invalid fieldname.');
			$conditionQuery = ' '.$fieldname.$operator.'\'' .Vtiger_Functions::realEscapeString($conditions['value']).'\' ';
		}
		return $conditionQuery;
	}

	protected function getGroupsIdsForUsers($userId) {
		vimport('~~/include/utils/GetUserGroups.php');

		$userGroupInstance = new GetUserGroups();
		$userGroupInstance->getAllUserGroups($userId);
		return $userGroupInstance->user_groups;
	}

	protected function queryForRecords($query, $onlymine=true) {
		$user = Users_Record_Model::getCurrentUserModel();
		if ($onlymine) {
			$groupIds = $this->getGroupsIdsForUsers($user->getId());
			$groupWsIds = array();
			foreach($groupIds as $groupId) {
				$groupWsIds[] = vtws_getWebserviceEntityId('Groups', $groupId);
			}
			$userwsid = vtws_getWebserviceEntityId('Users', $user->getId());
			$userAndGroupIds = array_merge(array($userwsid),$groupWsIds);
			$query .= " AND assigned_user_id IN ('".implode("','",$userAndGroupIds)."')";
		}
		// TODO take care of pulling 100+ records
		return vtws_query($query.';', $user);
	}

	protected function pullEvents($start, $end, &$result, $userid = false, $color = null, $textColor = 'white', $isGroupId = false, $conditions = '') {
		$dbStartDateOject = DateTimeField::convertToDBTimeZone($start);
		$dbStartDate = $dbStartDateOject->format('Y-m-d');

		$dbEndDateObject = DateTimeField::convertToDBTimeZone($end);
		$dbEndDate = $dbEndDateObject->format('Y-m-d');

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();
		$groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
		require('user_privileges/user_privileges_'.$currentUser->id.'.php');
		require('user_privileges/sharing_privileges_'.$currentUser->id.'.php');

		$moduleModel = Vtiger_Module_Model::getInstance('Events');
		// if($userid && !$isGroupId){
		// 	$focus = new Users();
		// 	$focus->id = $userid;
		// 	$focus->retrieve_entity_info($userid, 'Users');
		// 	$user = Users_Record_Model::getInstanceFromUserObject($focus);
		// 	$userName = $user->getName();
		// 	$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
		// }else{
			$queryGenerator = new QueryGenerator($moduleModel->get('name'), $currentUser);
		// }

		$queryGenerator->setFields(array('subject', 'eventstatus', 'visibility','date_start','time_start','due_date','time_end','assigned_user_id','id','activitytype','recurringtype','parent_id','description', 'location', 'creator', 'modifiedby'));
		$query = $queryGenerator->getQuery();

		$query.= " AND vtiger_activity.activitytype NOT IN ('Emails','Task') AND ";
		$hideCompleted = $currentUser->get('hidecompletedevents');
		if($hideCompleted)
			$query.= "vtiger_activity.eventstatus != 'HELD' AND ";

		if(!empty($conditions)) {
			$conditions = Zend_Json::decode(Zend_Json::decode($conditions));
			$query .=  $this->generateCalendarViewConditionQuery($conditions).'AND ';
		}
		
		$query.= " date_start <= ? AND due_date >= ?";
		
		if(empty($userid)){
			$eventUserId  = $currentUser->getId();
		}else{
			$eventUserId = $userid;
		}
		$userIds = array_merge(array($eventUserId), $this->getGroupsIdsForUsers($eventUserId));
		
		$query.= " AND vtiger_crmentity.smownerid IN (".  generateQuestionMarks($userIds).")";
		
		$params = array($dbEndDate, $dbStartDate, $userIds);
		$queryResult = $db->pquery($query, $params);

		$creatorfield = Vtiger_Field_Model::getInstance('creator', $moduleModel);

		while($record = $db->fetchByAssoc($queryResult)){
			if(!array_key_exists($record['smownerid'], $this->cacheUser)) {
				$this->cacheUser[$record['smownerid']] = Vtiger_functions::getUserRecordLabel($record['smownerid']);
			}
			if(!array_key_exists($record['smcreatorid'], $this->cacheUser)) {
				$this->cacheUser[$record['smcreatorid']] = Vtiger_functions::getUserRecordLabel($record['smcreatorid']);
			}
			if(!array_key_exists($record['modifiedby'], $this->cacheUser)) {
				$this->cacheUser[$record['modifiedby']] = Vtiger_functions::getUserRecordLabel($record['modifiedby']);
			}
			$item = array();
			$crmid = $record['activityid'];
			$visibility = $record['visibility'];
			$activitytype = $record['activitytype'];
			$status = $record['eventstatus'];
			$ownerId = $record['smownerid'];
			$item['id'] = $crmid;
			$item['visibility'] = $visibility;
			$item['activitytype'] = vtranslate($activitytype, 'Events');
			$item['status'] = $status;
			$recordBusy = true;
			if(in_array($ownerId, $groupsIds)) {
				$recordBusy = false;
			} else if($ownerId == $currentUser->getId()){
				$recordBusy = false;
			}
			// if the user is having view all permission then it should show the record
			// as we are showing in detail view
			if($profileGlobalPermission[1] ==0 || $profileGlobalPermission[2] ==0) {
				$recordBusy = false;
			}

			$recurringCheck = false;
			if($record['recurringtype'] != '' && $record['recurringtype'] != '--None--') {
				$recurringCheck = true;
			}
			$item['recurringcheck'] = $recurringCheck;

			if(!$currentUser->isAdminUser() && $visibility == 'Private' && $userid && $userid != $currentUser->getId() && $recordBusy) {
				$item['title'] = decode_html($userName).' - '.decode_html(vtranslate('Busy','Events')).'*';
				$item['url']   = '';
			} else {
				$item['title'] = decode_html($record['subject']).' - ('.decode_html(vtranslate($record['eventstatus'],'Calendar')).')';
				$item['url']   = sprintf('javascript:Calendar_Calendar_Js.editCalendarEvent(%s,%s)', $crmid, $recurringCheck);
			}

			$dateTimeFieldInstance = new DateTimeField($record['date_start'].' '.$record['time_start']);
			$userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue($currentUser);
			$dateTimeComponents = explode(' ',$userDateTimeString);
			$dateComponent = $dateTimeComponents[0];
			//Conveting the date format in to Y-m-d.since full calendar expects in the same format
			$dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $currentUser->get('date_format'));
			$item['start'] = $dataBaseDateFormatedString.' '. $dateTimeComponents[1];

			$dateTimeFieldInstance = new DateTimeField($record['due_date'].' '.$record['time_end']);
			$userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue($currentUser);
			$dateTimeComponents = explode(' ',$userDateTimeString);
			$dateComponent = $dateTimeComponents[0];
			//Conveting the date format in to Y-m-d.since full calendar expects in the same format
			$dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $currentUser->get('date_format'));
			$item['end']   =  $dataBaseDateFormatedString.' '. $dateTimeComponents[1];

			$item['className'] = $cssClass;
			if(preg_match('/ 00:00/', $item['start']) && preg_match('/ 00:00/', $item['end'])) {
				$item['allDay'] = true;	
				if($item['start'] != $item['end']) {
					$end = new DateTime($item['end']);
					$end->modify('+1 days');
					$item['end'] = $end->format('Y-m-d').' 00:00:00';
				}
			} else {
				$item['allDay'] = false;
			}
			$item['color'] = $color;
			$item['textColor'] = $textColor;
			$item['module'] = $moduleModel->getName();
			$item['userid'] = $eventUserId;
			$item['fieldName'] = 'date_start,due_date';
			$item['conditions'] = '';
			if(!empty($conditions)) {
				$item['conditions'] = Zend_Json::encode(Zend_Json::encode($conditions));
			}

			$item['assigned_user_id'] = $this->cacheUser[$record['smownerid']];
			$item['creator'] = $this->cacheUser[$record['smcreatorid']];
			$item['creator_field_label'] = vtranslate($creatorfield->get('label'));
			$item['modifiedby'] = $this->cacheUser[$record['modifiedby']];
			$item['modifiedby_field_label'] = vtranslate('Last Modified By', 'Events');
			if(!empty($record['crmid'])) {
				if(!array_key_exists($record['crmid'], $this->cacheParent)) {
					$this->cacheParent[$record['crmid']] = Vtiger_functions::getCRMRecordLabel($record['crmid']);
					$this->cacheModule[$record['related_module']] = Vtiger_functions::getCRMRecordType($record['crmid']);
				}
				$item['parent_id'] = $this->cacheParent[$record['crmid']];
				$item['related_id'] = $record['crmid'];
				$item['related_module'] = $this->cacheModule[$record['related_module']];
			} else {
				$item['parent_id'] = '';
				$item['related_id'] = '';
				$item['related_module'] = '';
			}
			$item['location'] = $record['location'];
			$item['description'] = $record['description'];

			$inviteeDetails = $this->getInviteeNames($record['activityid']);
			$group = Settings_Groups_Record_Model::getInstance($ownerId);
			if(!empty($group)) {
				$inviteeDetails[$ownerId] = $group->getName();
			}
			if(php7_count($inviteeDetails) > 0) {
				$inviteeMessage = '';
				if(count($inviteeDetails) == 1 && array_key_exists($currentUser->getId(), $inviteeDetails)) {
					$inviteeMessage = '';
				} else {
					$inviteeMessage = '<br>'.vtranslate('LBL_INVITE_USERS', 'Events').'<br>'.implode(', ', $inviteeDetails).'';
				}
				if(!empty($record['description']) && !empty($inviteeMessage)) {
					$inviteeMessage ='<br>'.$inviteeMessage;
				}
				$item['description'] = $record['description'].$inviteeMessage;
			}

			$result[] = $item;
		}
	}

	protected function pullMultipleEvents($start, $end, &$result, $data) {

		foreach ($data as $id=>$backgroundColorAndTextColor) {
			$userEvents = array();
			$colorComponents = explode(',',$backgroundColorAndTextColor);
			$this->pullEvents($start, $end, $userEvents ,$id, $colorComponents[0], $colorComponents[1], $colorComponents[2]);
			$result[$id] = $userEvents;
		}
	}

	protected function pullTasks($start, $end, &$result, $color = null,$textColor = 'white') {
		$user = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		$moduleModel = Vtiger_Module_Model::getInstance('Calendar');
		$userAndGroupIds = array_merge(array($user->getId()),$this->getGroupsIdsForUsers($user->getId()));
		$queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);

		$queryGenerator->setFields(array('activityid','subject', 'taskstatus','activitytype', 'date_start','time_start','due_date','time_end','id', 'assigned_user_id','parent_id','description'));
		$query = $queryGenerator->getQuery();

		$query.= " AND vtiger_activity.activitytype = 'Task' AND ";
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$hideCompleted = $currentUser->get('hidecompletedevents');
		if($hideCompleted)
			$query.= "vtiger_activity.status != 'Completed' AND ";
		$query.= " ((date_start >= ? AND due_date < ? ) OR ( due_date >= ? ))";
		$params=array($start,$end,$start);
		$userIds = $userAndGroupIds;
		$query.= " AND vtiger_activity.smownerid IN (".generateQuestionMarks($userIds).")";
		$params=array_merge($params,$userIds);
		$queryResult = $db->pquery($query,$params);

		while($record = $db->fetchByAssoc($queryResult)){
			$item = array();
			$crmid = $record['activityid'];
			$item['title'] = decode_html($record['subject']).' - ('.decode_html(vtranslate($record['status'],'Calendar')).')';
			$item['status'] = $record['status'];
			$item['activitytype'] = vtranslate($record['activitytype'], 'Calendar');
			$item['id'] = $crmid;
			$dateTimeFieldInstance = new DateTimeField($record['date_start'].' '.$record['time_start']);
			$userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue();
			$dateTimeComponents = explode(' ',$userDateTimeString);
			$dateComponent = $dateTimeComponents[0];
			//Conveting the date format in to Y-m-d.since full calendar expects in the same format
			$dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $user->get('date_format'));
			$item['start'] = $dataBaseDateFormatedString.' '. $dateTimeComponents[1];

			$dueDate = new DateTime($record['due_date'].' '.$record['time_end']);
			$dueDate = $dueDate->modify('+1 day')->format('Y-m-d');
			$dateTimeFieldInstance = new DateTimeField($dueDate.' '.$record['time_start']);
			$userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue();
			$dateTimeComponents = explode(' ',$userDateTimeString);
			$dateComponent = $dateTimeComponents[0];
			//Conveting the date format in to Y-m-d.since full calendar expects in the same format
			$dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $user->get('date_format'));
			$item['end']   = $dataBaseDateFormatedString.' '. $dateTimeComponents[1];

			$item['url']   = sprintf('index.php?module=Calendar&view=Detail&record=%s', $crmid);
			$item['color'] = $color;
			$item['textColor'] = $textColor;
			$item['module'] = $moduleModel->getName();
			$item['allDay'] = true;
			$item['fieldName'] = 'date_start,due_date';
			$item['conditions'] = '';

			if(!empty($record['parent_id'])) {
				$item['parent_id'] = Vtiger_functions::getCRMRecordLabel($record['parent_id']);
			} else {
				$item['parent_id'] = '';
			}

			$ownerId = $record['smownerid'];

			$inviteeDetails = $this->getInviteeNames($record['activityid']);
			$group = Settings_Groups_Record_Model::getInstance($ownerId);
			if(!empty($group)) {
				$inviteeDetails[$ownerId] = $group->getName();
			}
			if(php7_count($inviteeDetails) > 0) {
				$inviteeMessage = '';
				if(count($inviteeDetails) == 1 && array_key_exists($currentUser->getId(), $inviteeDetails)) {
					$inviteeMessage = '';
				} else {
					$inviteeMessage = '<br>'.vtranslate('LBL_INVITE_USERS', 'Events').'<br>'.implode(', ', $inviteeDetails).'';
				}
				if(!empty($record['description']) && !empty($inviteeMessage)) {
					$inviteeMessage ='<br>'.$inviteeMessage;
				}
				$item['description'] = $record['description'].$inviteeMessage;
			}

			$result[] = $item;
		}
	}

	private function getInviteeNames($activityid) {
		global $adb;

		$inviteeDetails = array();

		$sql = "SELECT
					i.*,
					u.first_name,
					u.last_name
				FROM
					vtiger_invitees i
					INNER JOIN vtiger_users u ON u.id = i.inviteeid
				WHERE
					i.activityid=(SELECT invitee_parentid FROM vtiger_activity WHERE activityid = ?)";

		$result = $adb->pquery($sql, array($activityid));
		$num_rows = $adb->num_rows($result);

		for($i=0; $i<$num_rows; $i++) {
			$userid = $adb->query_result($result, $i, 'inviteeid');
			$name = $adb->query_result($result, $i, 'last_name').''.$adb->query_result($result, $i, 'first_name');
			if(empty($name)) {
				$group = Settings_Groups_Record_Model::getInstance($userid);
				$name = $group->getName();
			}
			$inviteeDetails[$userid] = $name;
		}

		return $inviteeDetails;
	}
}
