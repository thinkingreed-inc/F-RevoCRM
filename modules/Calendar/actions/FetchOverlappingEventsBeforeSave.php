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
					WHERE deleted = 0
					AND (
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
		$queryResult = $db->pquery($query, array($start,$end, $start,$end, $start,$end,$start, $start,$end,$end, $start,$end));

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
		
		$response = new Vtiger_Response();
		$response->setResult((array('message' => $message)));
		$response->emit();
	}
}