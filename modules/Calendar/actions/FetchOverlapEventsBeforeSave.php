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

	function process(Vtiger_Request $request) {
		$moduleName = $request->get('module');

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$overlap_userids = $this->getOverlapUserIds($currentUser->getId());

		$message = '';
		if (!empty($overlap_userids)) {
			$overlap_events = $this->getOverlapEvents($request, $overlap_userids);
			if (!empty($overlap_events)) { // 重複活動が存在する場合
				$message = vtranslate('OVERLAPPING_TAG_EXISTS', 'Events');
				$message .= '<ul>';
				foreach ($overlap_events as $id => $subject) {
					$recordModel = Vtiger_Record_Model::getInstanceById($id, $moduleName);
					$message .= '<li><a href="'.$recordModel->getDetailViewUrl().'" target="_blank" style="color:#15c !important">'.$subject.'&nbsp;&nbsp;</a></li>';
				}
				$message .= '</ul>';
			}
		}

		$response = new Vtiger_Response();
		$response->setResult((array('message' => $message)));
		$response->emit();
	}

	// カレンダー共有設定を参照しつつ, 確認ダイアログに表示するoverlap_useridを取得する
	private function getOverlapUserIds($userId) {
		$db = PearDatabase::getInstance();
		$sharedUsers = Calendar_Module_Model::getSharedUsersOfCurrentUser($userId);
		$sharedGroups = Calendar_Module_Model::getSharedCalendarGroupsList($userId);

		$query = 'SELECT overlap_userid FROM vtiger_calendar_overlaps WHERE userid = ?;';
		$queryResult = $db->pquery($query, array($userId));
		$num_rows = $db->num_rows($queryResult);
		$overlap_userids = array();
		if ($num_rows > 0) {
			for ($i = 0; $i < $num_rows; $i++) {
				$overlap_userid = $db->query_result($queryResult, $i, 'overlap_userid');
				if ($overlap_userid == $userId || array_key_exists($overlap_userid, $sharedUsers) || array_key_exists($overlap_userid, $sharedGroups)) {
					$overlap_userids[] = $overlap_userid;
				}
			}
		}

		return $overlap_userids;
	}

	// 活動期間が重なる活動を取得する
	private function getOverlapEvents($request, $overlap_userids) {
		$db = PearDatabase::getInstance();

		$dbStartDateOject = DateTimeField::convertToDBTimeZone($request->get('start'));
		$start = $dbStartDateOject->format('Y-m-d H:i:s');
		if ($request->get('is_allday') === 'on' || $request->get('is_allday') === 'true') {
			$dbEndDateOject = clone $dbStartDateOject;
			$dbEndDateOject->modify('+1 day');
			$end = $dbEndDateOject->format('Y-m-d H:i:s');
		} else {
			$dbEndDateOject = DateTimeField::convertToDBTimeZone($request->get('end'));
			$end = $dbEndDateOject->format('Y-m-d H:i:s');
		}

		$query = 'SELECT activityid, subject FROM vtiger_activity
						WHERE deleted = 0 '.
						'AND smownerid IN ('.generateQuestionMarks($overlap_userids).') '.
						'AND (
							-- 1. 活動が$start前に始まり, $end後に終わる場合
							(TIMESTAMP(date_start, time_start) <= ? AND TIMESTAMP(due_date, time_end) >= ?)
							-- 2. 活動が指定期間内に収まる場合	
							OR (TIMESTAMP(date_start, time_start) > ? AND TIMESTAMP(due_date, time_end) < ?)
							-- 3. 活動が指定期間の開始にかかる場合
							OR (TIMESTAMP(date_start, time_start) < ? AND TIMESTAMP(due_date, time_end) < ? AND TIMESTAMP(due_date, time_end) > ?)
							-- 4. 活動が指定期間の終了にかかる場合
							OR (TIMESTAMP(date_start, time_start) > ? AND TIMESTAMP(due_date, time_end) > ? AND TIMESTAMP(date_start, time_start) < ?)
							-- 5. 終日の活動が指定期間に重なる場合
							OR (allday = 1 AND (date_start <= DATE(?) AND due_date >= DATE(?)))
						)';
		$params = array_merge(
			$overlap_userids,
			[$start,$end, $start,$end, $start,$end,$start, $start,$end,$end, $start,$end]
		);

		if (!empty($request->get('record'))) {
			// 編集中の活動を含めない
			$query .= ' AND activityid != ?';
			$params[] = $request->get('record');

			// 参加者の活動を含めない
			$query .= ' AND invitee_parentid != (SELECT invitee_parentid from vtiger_activity WHERE activityid = ?)';
			$params[] = $request->get('record');
		}
		
		$queryResult = $db->pquery($query, $params);
		$num_rows = $db->num_rows($queryResult);
		$overlap_events = array();
		if ($num_rows > 0) {
			for ($i = 0; $i < $num_rows; $i++) {
				$id = $db->query_result($queryResult, $i, 'activityid');
				$subject = $db->query_result($queryResult, $i, 'subject');
				$overlap_events[$id] = $subject;
			}
		}

		return $overlap_events;
	}
}