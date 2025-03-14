<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_FetchOverlappingEventsBeforeSave_Action extends Vtiger_BasicAjax_Action {

	function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('module');

		// カレンダー共有設定を参照しつつ, 確認ダイアログに表示するoverlap_useridを取得する
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser->getId();
		$query = 'SELECT overlap_userid FROM vtiger_calendar_overlaps WHERE userid = ?;';
		$queryResult = $db->pquery($query, array($userId));

		$sharedUsers = Calendar_Module_Model::getSharedUsersOfCurrentUser($userId);
		$sharedGroups = Calendar_Module_Model::getSharedCalendarGroupsList($userId);
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

		// 活動期間が重なる活動を取得する
		if (empty($overlap_userids)) { // 重複活動チェック対象が設定されていない場合
			$message = '';
		} else {
			$dbStartDateOject = DateTimeField::convertToDBTimeZone($request->get('start'));
			$start = $dbStartDateOject->format('Y-m-d H:i:s');
			if ($request->get('is_allday')) {
				$dbEndDateOject = clone $dbStartDateOject;
				$dbEndDateOject->modify('+1 day');
				$end = $dbEndDateOject->format('Y-m-d H:i:s');
			} else {
				$dbEndDateOject = DateTimeField::convertToDBTimeZone($request->get('end'));
				$end = $dbEndDateOject->format('Y-m-d H:i:s');
			}

			$query = 'SELECT activityid, subject FROM vtiger_activity
						WHERE deleted = 0'.
						' AND smownerid IN ('.generateQuestionMarks($overlap_userids).')'.
						'AND (
							-- 1. 活動が指定期間の開始前に始まり, 終了後に終わる場合
							(TIMESTAMP(date_start, time_start) <= ? AND TIMESTAMP(due_date, time_end) >= ?)
							-- 2. 活動が指定期間の完全に中に収まる場合	
							OR (TIMESTAMP(date_start, time_start) >= ? AND TIMESTAMP(due_date, time_end) <= ?)
							-- 3. 活動が指定期間の開始にかかる場合
							OR (TIMESTAMP(date_start, time_start) <= ? AND TIMESTAMP(due_date, time_end) <= ? AND TIMESTAMP(due_date, time_end) >= ?)
							-- 4. 活動が指定期間の終了にかかる場合
							OR (TIMESTAMP(date_start, time_start) >= ? AND TIMESTAMP(due_date, time_end) >= ? AND TIMESTAMP(date_start, time_start) <= ?)
							-- 5. 終日の予定が指定期間に重なる場合
							OR (allday = 1 AND (date_start <= DATE(?) AND due_date >= DATE(?)))
						)';
			$params = array();
			$params = array_merge($params, $overlap_userids);
			$params = array_merge($params, array($start,$end, $start,$end, $start,$end,$start, $start,$end,$end, $start,$end));
			$queryResult = $db->pquery($query, $params);

			$num_rows = $db->num_rows($queryResult);
			if ($num_rows > 0) {
				$message = vtranslate('OVERLAPPING_TAG_EXISTS', 'Events');
				for ($i = 0; $i < $num_rows; $i++) {
					$id = $db->query_result($queryResult, $i, 'activityid');
					$subject = $db->query_result($queryResult, $i, 'subject');
					$recordModel = Vtiger_Record_Model::getInstanceById($id, $moduleName);

					$message .= '<a href="'.$recordModel->getDetailViewUrl().'" target="_blank" style="color:#15c !important">'.$subject.' </a>';
				}
			} else {
				$message = '';
			}
		}
		
		$response = new Vtiger_Response();
		$response->setResult((array('message' => $message)));
		$response->emit();
	}
}