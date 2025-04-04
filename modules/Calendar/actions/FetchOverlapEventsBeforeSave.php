<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_FetchOverlapEventsBeforeSave_Action extends Vtiger_BasicAjax_Action {
	
	/**
	 * 重複している活動の最大表示件数
	 * @var int
	 */
	const DISPLAY_OVERLAP_EVENTS = 10;
	
	/**
	 * 繰り返し活動を検索する最大の期間(日数)
	 * @var int
	 */
	const RECURRING_DATE_LIMIT = 365;

	public function process(Vtiger_Request $request) 
	{
		global $log;
		$response = new Vtiger_Response();
		try {
			$checkOverlapUserIds = $this->getTargetUserIds($request);
			$recurringDays = $this->getRecurringType($request);
			$overlapEvents = $this->getOverlapEventIds($request, $checkOverlapUserIds, $recurringDays);
			$overlapEventUsers = $this->getOverlapEventUsers($overlapEvents);
			$response->setResult(['message' => $this->buildOverlapMessageHTML($overlapEvents, $overlapEventUsers)]);
			
		}catch (Exception $e) {
			$log->error('Calendar_FetchOverlapEventsBeforeSave_Action: '.$e->getCode().':  '.$e->getMessage());
			$log->error($e->getTraceAsString());
			$response->setError($e->getMessage());			
		}finally {
			$response->emit();
		}
	}
	
	/*
	 * 期間重複を確認するユーザのIDを取得
	 * formからの入力を優先する 
	 * 
	 * @param Vtigerrequest $request
	 * @return array
	 */
	private function getTargetUserIds(Vtiger_Request $request) :array
	{
		// formからの入力
		$ownerId     = (int)$request->get('assigned_user_id');
		$invitiesIds = !empty($request->get('selectedusers')) 
					 ? $request->get('selectedusers') 
					 : [];
		$recordId    = $request->get('record_id');
		
		// 参加者が一人の場合文字列になっている
		if (!empty($invitiesIds) && !is_array($invitiesIds)) {
			$invitiesIds = explode(',', $invitiesIds);
		}
		
		// ドラッグアンドドロップなどマウス操作のみの入力
		if((empty($ownerId) || empty($invitiesIds)) && !empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Events');
			
			$ownerId     = !empty($ownerId) 
						 ? (int)$ownerId 
						 : (int)$recordModel->get('assigned_user_id');
			$invitiesIds = !empty($invitiesIds) 
						 ? $invitiesIds 
						 : $recordModel->getInvities();
		}
		
		return array_unique(array_merge($invitiesIds, [$ownerId]));
	}
	
	/**
	 * 繰り返し活動の設定を取得
	 * 
	 */
	private function getNextRecurringDates(string $recordId) :string
	{
		$db = PearDatabase::getInstance();
		$nextRecordTime = '';

		$query = 'SELECT recurrenceid 
				  FROM vtiger_activity_recurring_info 
				  WHERE activityid = (SELECT activityid 
									  FROM vtiger_activity_recurring_info 
									  WHERE recurrenceid = ?) AND recurrenceid > ?';
		$result = $db->pquery($query, [$recordId, $recordId]);
		if($db->num_rows($result) > 0) {
			$nextRecordId = $db->query_result($result, 0,"recurrenceid");
			$nextRecordModel = Vtiger_Record_Model::getInstanceById($nextRecordId, 'Events');
			$nextRecordTime = $nextRecordModel->get('date_start');
		}
		
		return $nextRecordTime;
	}
	
	/*
	 * Formからの繰り返し活動の設定を取得
	 * 
	 * @param Vtiger_Request $request
	 * @return array
	 */
	private function getRecurringTypeFromRequest(Vtiger_Request $request) : array
	{
		$recurringData = [];
		
		// 繰り返し周期の単位設定（日、週、月）
		$type = $request->get('recurringtype');
		if (empty($type) || $type === '--None--') {
			return [];
		}
		$recurringData['type'] = $type;
		
		// 繰り返し周期の頻度設定
		$frequency = $request->get('repeat_frequency');
		if (!empty($frequency)) {
			$recurringData['repeat_frequency'] = $frequency;
		}
		
		// 繰り返し終了日の設定（繰り返しを検索する範囲は制限する）
		$limitDate = $request->get('calendar_repeat_limit_date');
		if (!empty($limitDate)) {
			// $limitDateが一年以内の日付か判定する
			$limitDateTimeObj  = new DateTime($limitDate);
			$targetDateTimeObj = (new DateTime('today'))
							   ->modify('+'.self::RECURRING_DATE_LIMIT.' days');
			
			$recurringData['recurringenddate'] = $limitDateTimeObj <= $targetDateTimeObj 
											   ? $limitDate 
											   : $targetDateTimeObj->format('Y-m-d');
		} 
		
		// 活動の開始日時の設定
		$recurringData['startdate'] = $request->get('date_start');
		$recurringData['starttime'] = $request->get('time_start');
		
		// 繰り返し予定が「以降の活動を含む」
		$editMode = $request->get('recurringEditMode');
		if($editMode === 'future') {
			$nextRecordStartDateTime = $this->getNextRecurringDates($request->get('record_id'));
			
			if(!empty($nextRecordStartDateTime)) {
				$recurringData['startdate'] = $nextRecordStartDateTime;
			}
		}
		
		// 活動の終了日時の設定
		if (!empty($limitDate) ) {
			$recurringData['enddate']   = $limitDate;
		}else {
			$recurringData['enddate']   = $request->get('due_date');
		}
		
		$recurringData['endtime']   = $request->get('time_end');

		// 繰り返し周期の単位設定が「週」の場合
		if ($type === 'Weekly') {
			$recurringWeekdays = [];
			if(!empty($request->get('recurring_weekdays'))) {
				$recurringWeekdays = $request->get('recurring_weekdays');
			}
			// 曜日フラグ項目の設定
			foreach($recurringWeekdays as $day) {
				if(isset($recurringData[$day])) {
					$recurringData[$day] = true;
				}
			}
		}
		
		// 繰り返し周期の単位設定が「月」の場合
		if ($type === 'Monthly') {
			$repeatMonth = $request->get('repeatMonth');
			$recurringData['repeatmonth_type'] = $repeatMonth;
			if ($repeatMonth === 'date') {
				$repeatMonthDate = $request->get('repeatMonth_date');
				$recurringData['repeatmonth_date'] = empty($repeatMonthDate) 
												   ? $repeatMonthDate 
												   : 1;
			}
			
			if ($repeatMonth === 'day') {
				$recurringData['repeatmonth_daytype'] = $request->get('repeatMonth_daytype');
				$weeks = [
					0 => 'sun_flag',
					1 => 'mon_flag',
					2 => 'tue_flag',
					3 => 'wed_flag',
					4 => 'thu_flag',
					5 => 'fri_flag',
					6 => 'sat_flag'
				];
				if(isset($weeks[$recurringData['repeatmonth_daytype']])) {
					$recurringData[$weeks[$recurringData['repeatmonth_daytype']]] = true;
				}
			}
		}

		$recurringType = RecurringType::fromUserRequest($recurringData);
		return $recurringType->recurringdates ?? [];
	}
	
	/*
	 * DBからの繰り返し活動の設定を取得
	 * 
	 * @param Vtiger_Request $request
	 * @return array
	 */
	private function getRecurringTypeFromDB(Vtiger_Request $request) : array
	{
		$db = PearDatabase::getInstance();
		$recurringDates = [];
		$diffDate = 0;
		
		$recordId = $request->get('record_id');
		$editMode = $request->get('recurringEditMode');
		
		$query = 'SELECT 
					vre.*, 
					va.date_start, 
					va.time_start, 
					va.due_date, 
					va.time_end 
				  FROM vtiger_recurringevents AS vre
					INNER JOIN vtiger_activity AS va
						ON va.activityid = vre.activityid
				  WHERE vre.activityid = ?';
		$result = $db->pquery($query, [$recordId]);
		
		if ($db->num_rows($result)) {
			$recurringDataRow = $db->query_result_rowdata($result, 0);
			// 開始日時、終了日時はFormからのリクエスト値がある場合は優先する
			// 活動の開始日時の設定
			$requestDateStart = $request->get('date_start');
			if(!empty($requestDateStart)) {
				// DBからの開始日とRequestからの開始日に差分があれば取得
				$diffDate = strtotime($recurringDataRow['date_start']) - strtotime($requestDateStart);
				$recurringDataRow['date_start'] = $requestDateStart;
			}
			
			$requestTimeStart = $request->get('time_start');
			if(!empty($requestTimeStart)) {
				$recurringDataRow['time_start'] = $requestTimeStart;
			}
			
			// 繰り返し予定が「以降の活動を含む」場合
			if($editMode === 'future') {
				$nextRecordStartDateTime = $this->getNextRecurringDates($request->get('record_id'));
				if($diffDate !== 0) {
					$nextRecordStartDateTime = 
							(new DateTime($nextRecordStartDateTime))
								->modify('-'.$diffDate.' seconds')
								->format('Y-m-d');
				}
				
				if(!empty($nextRecordStartDateTime)) {
					$recurringDataRow['date_start'] = $nextRecordStartDateTime;
				}
			}
			
			// 活動の終了日時の設定
			$requestDueDate = $request->get('due_date');
			if (!empty($requestDueDate) ) {
				$recurringDataRow['enddate']   = $requestDueDate;
			}
			
			$requestEndTime = $request->get('time_end');
			if (!empty($requestEndTime) ) {
				$recurringDataRow['endtime']   = $requestEndTime;
			}
			
			// DBからの開始日とRequestからの開始日の差分があれば反映
			if($diffDate !== 0) {
				$recurringDataRow['recurringenddate'] = 
						(new DateTime($recurringDataRow['recurringenddate']))
							->modify('-'.$diffDate.' seconds')
							->format('Y-m-d');
			}
			
			$recurringType = RecurringType::fromDBRequest($recurringDataRow);
			$recurringDates = $recurringType->recurringdates ?? [];
			
			if(count($recurringDates) > self::RECURRING_DATE_LIMIT) {
				$recurringDates = array_slice($recurringDates, 0, self::RECURRING_DATE_LIMIT);
			}
		}
		
		return $recurringDates;
	}
	
	/*
	 * 繰り返し活動の日付を取得
	 * 
	 * @param Vtigerrequest $request
	 * @return array
	 */
	private function getRecurringType(Vtiger_Request $request): array
	{
		$recurringDays = [];
		// 繰り返しの他の予定を更新しない場合
		if($request->get('recurringEditMode') === 'current'){
			return $recurringDays;
		}		
		
		// Requestからの取得
		$recurringDays = $this->getRecurringTypeFromRequest($request);
		
		// DBからの取得
		if (count($recurringDays) === 0 && !empty($request->get('record_id'))) {
			$recurringDays = $this->getRecurringTypeFromDB($request);
		}
		
		return $recurringDays;
	}
	
	/*
	 * 開始日と終了日を取得または計算
	 * 
	 * @param string $startDateTime
	 * @param string $endDateTime
	 * @param string $allDayFlg
	 * @return array
	 */
	private function getDateTimeValues(
		string $startDateTime, 
		string $endDateTime, 
		string $allDayFlg): array
	{
		$dbStartDateOject = DateTimeField::convertToDBTimeZone($startDateTime);
		$startDateTime = $dbStartDateOject->format('Y-m-d H:i:s');
		if ($allDayFlg === 'on' || $allDayFlg === 'true') {
			$dbEndDateOject = clone $dbStartDateOject;
			$dbEndDateOject->modify('+1 day');
			$endDateTime = $dbEndDateOject->format('Y-m-d H:i:s');
		} else {
			$dbEndDateOject = DateTimeField::convertToDBTimeZone($endDateTime);
			$endDateTime = $dbEndDateOject->format('Y-m-d H:i:s');
		}
		
		return ['start' => $startDateTime, 'end' => $endDateTime];
	}	
	
	/*
	 * 活動期間が重なる活動を取得する
	 * 
	 * @param string $start
	 * @param string $end
	 * @param array $checkOverlapUserIds
	 * @param string $recordId
	 * @param bool $isReccurring
	 * @return array 
	 */
	private function fetchOverlapEventIds(
		string $start, 
		string $end, 
		array $checkOverlapUserIds, 
		string $recordId = '', 
		bool $isReccurring = false) :array
	{
		$db = PearDatabase::getInstance();
		$overlapEventIds = [];		
		
		$query      = 'SELECT vac.activityid 
						FROM vtiger_activity AS vac ';
		
		if($isReccurring) {
			$query .= 'LEFT JOIN vtiger_activity_recurring_info AS vri 
						ON vac.activityid = vri.recurrenceid ';
		}
		
		$query     .= 'WHERE deleted = 0 '.
						'AND smownerid IN ('.generateQuestionMarks($checkOverlapUserIds).') '.
						'AND (
							-- 1. 活動が$start前に始まり, $end後に終わる場合
							(TIMESTAMP(date_start, time_start) <= ? 
								AND TIMESTAMP(due_date, time_end) >= ?)
							-- 2. 活動が指定期間内に収まる場合	
							OR (TIMESTAMP(date_start, time_start) > ? 
								AND TIMESTAMP(due_date, time_end) < ?)
							-- 3. 活動が指定期間の開始にかかる場合
							OR (TIMESTAMP(date_start, time_start) <= ? 
								AND TIMESTAMP(due_date, time_end) < ? 
								AND TIMESTAMP(due_date, time_end) > ?)
							-- 4. 活動が指定期間の終了にかかる場合
							OR (TIMESTAMP(date_start, time_start) >= ? 
								AND TIMESTAMP(due_date, time_end) >= ? 
								AND TIMESTAMP(date_start, time_start) < ?)
							-- 5. 終日の活動が指定期間に重なる場合
							OR (allday = 1 
								AND (date_start <= DATE(?) 
								AND due_date >= DATE(?)))
							-- 6. 終日の活動が指定期間の開始または終了にかかる場合
							OR (allday = 1 
								AND (date_start = DATE(?) 
								OR due_date = DATE(?)))
						)';
		$params = array_merge(
			$checkOverlapUserIds,
			[
				$start, $end, // 1 
				$start, $end, // 2
				$start, $end, $start, // 3 
				$start, $end, $end, // 4
				$start, $end, // 5
				$start, $start // 6
			]
		);

		if (!empty($recordId)) {
			// 編集中の活動を含めない
			$query .= ' AND vac.activityid != ?';
			$params[] = $recordId;

			// 参加者の活動を含めない
			$query .= ' AND invitee_parentid != (
							SELECT invitee_parentid 
							FROM vtiger_activity 
							WHERE activityid = ?
						) 
						AND invitee_parentid = vac.activityid';
			$params[] = $recordId;
			
			// 繰り返しの活動を含めない
			if($isReccurring) {			
				// 繰り返しの親レコ―ドIDを取得し除外する	
				$query .= ' AND invitee_parentid NOT IN (
								SELECT recurrenceid 
								FROM vtiger_activity_recurring_info 
								WHERE activityid = (
									SELECT activityid 
									FROM vtiger_activity_recurring_info 
									WHERE recurrenceid = (
										SELECT invitee_parentid 
										FROM vtiger_activity 
										WHERE activityid = ?
									)
								)
							)';

				$params[] = $recordId;
				
				$query .= ' AND ( 
								vri.activityid != ? AND vri.recurrenceid != ? 
								OR (vri.activityid IS NULL AND vri.recurrenceid IS NULL) 
							)';
				$params[] = $recordId;
				$params[] = $recordId;
			}
			
			$query     .= ' ORDER BY date_start, time_start';
		}
		
		$queryResult = $db->pquery($query, $params);
		$num_rows = $db->num_rows($queryResult);
		if ($num_rows > 0) {
			for ($i = 0; $i < $num_rows; $i++) {
				$id = $db->query_result($queryResult, $i, 'activityid');
				$overlapEventIds[] = $id;
			}
		}

		return $overlapEventIds;
	}
	
	/*
	 * 活動期間が重なる活動を取得する
	 * 
	 * @param Vtigerrequest $request
	 * @param array $checkOverlapUserIds
	 * @param array $recurringDays
	 * @return array 
	 */
	private function getOverlapEventIds(
		Vtiger_Request $request, 
		array $checkOverlapUserIds, 
		array $recurringDays) :array
	{
		$overlapEvents = [];
		
		if (empty($checkOverlapUserIds)) {
			return $overlapEvents;
		}
		
		$dateStart = $request->get('date_start');
		$dateEnd   = $request->get('due_date');
		$timeStart = $request->get('time_start');
		$timeEnd   = $request->get('time_end');
		$allDayFlg = $request->get('is_allday');
		$recordId  = $request->get('record_id');
		$startDateTime = $dateStart.' '.$timeStart;
		$endDateTime   = $dateEnd.' '.$timeEnd;
		
		// 繰り返し活動場合
		if(count($recurringDays) > 1) {
			$intervalSec = strtotime($endDateTime) - strtotime($startDateTime);
			foreach($recurringDays as $startDay) {
				$recurringStartDateTime = $startDay.' '.$timeStart;
				$recurringEndDateTime = (new DateTime($recurringStartDateTime))
						->modify(+$intervalSec.' seconds')
						->format('Y-m-d H:i:s');
				
				[ 'start' => $start, 'end' => $end ] = 
					$this->getDateTimeValues($recurringStartDateTime, $recurringEndDateTime, $allDayFlg);
				$result = $this->fetchOverlapEventIds($start, $end, $checkOverlapUserIds, $recordId, true);
				$overlapEvents = array_unique(array_merge($overlapEvents, $result));
			}
		}else {
			[ 'start' => $start, 'end' => $end ] = 
				$this->getDateTimeValues($startDateTime, $endDateTime, $allDayFlg);
			$overlapEvents = $this->fetchOverlapEventIds($start, $end, $checkOverlapUserIds, $recordId);
		}
		
		return $overlapEvents;
	}
	
	/*
	 * 重複している活動のユーザを取得
	 * 
	 * @param array $overlapEvents
	 * @return array
	 */
	private function getOverlapEventUsers(array $overlapEvents) :array
	{
		$overlapEventUsers = [];
		
		if (empty($overlapEvents)) {
			return $overlapEventUsers;
		}
		
		$db = PearDatabase::getInstance();
		$query   = 'SELECT DISTINCT smownerid 
					FROM vtiger_activity 
					WHERE deleted = 0 
						AND invitee_parentid IN ('.generateQuestionMarks($overlapEvents).')';
		$result = $db->pquery($query, [$overlapEvents]);
		
		if ($db->num_rows($result) > 0) {
			for ($i = 0; $i < $db->num_rows($result); $i++) {
				$ownerId = $db->query_result($result, $i, 'smownerid');
				$overlapEventUsers[$ownerId] = getUserFullName($ownerId); 
			}
		}
		
		return array_unique($overlapEventUsers);
	}
	
	/*
	 * 重複している活動のメッセージをHTML形式で作成
	 * 
	 * @param array $overlapEvents
	 * @return string
	 */
	private function buildOverlapMessageHTML(array $overlapEvents, array $overlapEventUsers) :string
	{
		$message = '';

		if (!empty($overlapEvents)) { // 重複活動が存在する場合
			
			// ヘッダーのメッセージ
			$message  = '<div style="margin-bottom:10px;font-weight:bold;font-size:1.4rem;color:#333;">';
			$message .= vtranslate('OVERLAPPING_EXISTS', 'Events').'<br>';
			$message .= vtranslate('OVERLAPPING_CONFIRME_MSG', 'Events');
			$message .= '</div>';
			
			$message .= '<hr>';
			
			// 重複している活動のリスト
			$message .= '<div style="margin-bottom:5px;font-weight:bold;color:#333;">';
			$message .= sprintf(vtranslate('期間が重複している活動（最大%s件まで表示）', 'Events'), self::DISPLAY_OVERLAP_EVENTS);
			$message .= '</div>';
			$message .= '<ul style="list-style:none;margin: 0 0 20px 20px;padding: 0;">';
			
			$countNum = 1;
			foreach ($overlapEvents as $id) {
				$recordModel   = Vtiger_Record_Model::getInstanceById($id, 'Events');
				$startDateTime = new DateTime($recordModel->get('date_start').' '.$recordModel->get('time_start'));
				$endDateTime   = new DateTime($recordModel->get('due_date').' '.$recordModel->get('time_end'));
				$isSameDay     = $startDateTime->format('Y-m-d') === $endDateTime->format('Y-m-d');
				$isAllDay      = $recordModel->isAllDay();
				
				$message .= '<li style="margin-bottom: 8px;">';
				$message .= '<a href="'
								.$recordModel->getDetailViewUrl()
								.'" target="_blank" style="color:#15c !important">';
				$message .= $startDateTime->format('Y-m-d');
				
				// 終日の場合は開始時刻と終了日時を表示しない
				if($isAllDay) {
					$message .= '<span style="margin-left: 1.5rem">'.vtranslate('LBL_ALL_DAY', 'Events').'</span>';
				} else {
					$message .= '<span style="margin-left: 1.5rem">';
					$message .= $startDateTime->format('H:i').'&nbsp; - &nbsp;';
					
					// 開始日と終了日が同日の場合は日付を表示しない
					if(!$isSameDay) {
						$message .= $endDateTime->format('Y-m-d H:i');
					}else {
						$message .= $endDateTime->format('H:i');
					}
					$message .= '</span>';
				}
				
				$message .= '<div style="margin:0 0 5px 0;font-weight:bold;">'.$recordModel->get('subject').'</div>';
				$message .= '</a>';
				$message .= '</li>';
				
				$countNum++;
				if ($countNum > self::DISPLAY_OVERLAP_EVENTS) {
					break;
				}
			}
			
			$message .= '</ul>';
			
			// 重複している活動のユーザ
			$message .= '<div style="margin-bottom:5px;font-weight:bold;color:#333;">';
			$message .= vtranslate('期間が重複する活動の担当者・参加者', 'Events');
			$message .= '</div>';
			$message .= '<div style="margin: 0 0 5px 20px;">';
			$message .= implode('&nbsp;,&nbsp;&nbsp;&nbsp;', $overlapEventUsers);
			$message .= '</div>';
			
		}
		
		return $message;
	}	
}
