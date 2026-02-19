<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
vimport('~~include/utils/RecurringType.php');

class Calendar_Record_Model extends Vtiger_Record_Model {

/**
	 * Function returns the Entity Name of Record Model
	 * @return <String>
	 */
	function getName() {
		$name = $this->get('subject');
		if(empty($name)) {
			$name = parent::getName();
		}
		return $name;
	}

	/**
	 * Function to insert details about reminder in to Database
	 * @param <Date> $reminderSent
	 * @param <integer> $recurId
	 * @param <String> $reminderMode like edit/delete
	 */
	public function setActivityReminder($reminderSent = 0, $recurId = '', $reminderMode = '') {
		$moduleInstance = CRMEntity::getInstance($this->getModuleName());
		$moduleInstance->activity_reminder($this->getId(), $this->get('reminder_time'), $reminderSent, $recurId, $reminderMode);
	}

	/**
	 * Function returns the Module Name based on the activity type
	 * @return <String>
	 */
	function getType() {
		$activityType = $this->get('activitytype');
		if($activityType == 'Task') {
			return 'Calendar';
		}
		return 'Events';
	}

	/**
	 * Function to get the Detail View url for the record
	 * @return <String> - Record Detail View Url
	 */
	public function getDetailViewUrl() {
		$module = $this->getModule();
		return 'index.php?module=Calendar&view='.$module->getDetailViewName().'&record='.$this->getId();
	}

	/**
	 * Function returns recurring information for EditView
	 * @return <Array> - which contains recurring Information
	 */
	public function getRecurrenceInformation($request = false) {
		$recurringObject = $this->getRecurringObject();

		if ($request && !$request->get('id') && $request->get('repeat_frequency')) {
			$recurringObject = getrecurringObjValue();
		}

		if ($recurringObject) {
			$recurringData['recurringcheck'] = 'Yes';
			$recurringData['repeat_frequency'] = $recurringObject->getRecurringFrequency();
			$recurringData['eventrecurringtype'] = $recurringObject->getRecurringType();
			$recurringEndDate = $recurringObject->getRecurringEndDate(); 
			if(!empty($recurringEndDate)){ 
				$recurringData['recurringenddate'] = $recurringEndDate->get_formatted_date(); 
			} 
			$recurringInfo = $recurringObject->getUserRecurringInfo();

			if ($recurringObject->getRecurringType() == 'Weekly') {
				$noOfDays = php7_count($recurringInfo['dayofweek_to_repeat']);
				for ($i = 0; $i < $noOfDays; ++$i) {
					$recurringData['week'.$recurringInfo['dayofweek_to_repeat'][$i]] = 'checked';
				}
			} elseif ($recurringObject->getRecurringType() == 'Monthly') {
				$recurringData['repeatMonth'] = $recurringInfo['repeatmonth_type'];
				if ($recurringInfo['repeatmonth_type'] == 'date') {
					$recurringData['repeatMonth_date'] = $recurringInfo['repeatmonth_date'];
				} else {
					$recurringData['repeatMonth_daytype'] = $recurringInfo['repeatmonth_daytype'];
					$recurringData['repeatMonth_day'] = $recurringInfo['dayofweek_to_repeat'][0];
				}
			}
		} else {
			$recurringData['recurringcheck'] = 'No';
		}
		return $recurringData;
	}

	function save() {
		//Time should changed to 24hrs format
		$_REQUEST['time_start'] = Vtiger_Time_UIType::getTimeValueWithSeconds($_REQUEST['time_start']);
		$_REQUEST['time_end'] = Vtiger_Time_UIType::getTimeValueWithSeconds($_REQUEST['time_end']);
		parent::save();
	}
	
	// 振り返し予定の系列の予定を削除する際、担当者の繰り返し情報の削除後処理要否を判定する関数
	protected function shouldCleanupRecurringInfo($parentActivityId) {
		$adb = PearDatabase::getInstance();
		if (empty($parentActivityId)) return false;
		$sql = "SELECT COUNT(*) AS cnt
				FROM vtiger_activity a
				WHERE a.deleted = 0
				AND a.invitee_parentid IN (
					SELECT ri2.recurrenceid
					FROM vtiger_activity_recurring_info ri2
					WHERE ri2.activityid = (
						SELECT ri1.activityid
						FROM vtiger_activity_recurring_info ri1
						WHERE ri1.recurrenceid = ?
						LIMIT 1
					)
				)
			";
		$res = $adb->pquery($sql, array($parentActivityId));
		$cnt = (int)$adb->query_result($res, 0, 'cnt');
		return ($cnt == 0);
	}
	// 振り返し予定もう存在しない場合、vtiger_activity_recurring_infoを削除する
	protected function cleanupRecurringInfo($parentActivityId) {
		$adb = PearDatabase::getInstance();
		if (empty($parentActivityId)) return;

		$adb->pquery(
			"DELETE FROM vtiger_activity_recurring_info WHERE activityid = ?",
			array($parentActivityId)
		);
	}
	/**
	 * Function to delete the current Record Model
	 */
	public function delete() {
		$adb = PearDatabase::getInstance();
		$recurringEditMode = $this->get('recurringEditMode');
		$deletedRecords = array();
		$recordId = $this->getId();
		$deleteScope = $this->get('deleteScope');
		if($deleteScope == 'all') {
			if(!empty($recurringEditMode) && $recurringEditMode != 'current') {
			// 担当者と参加者ID取得
			$recurringRecordsList = $this->getDeleteRecurringList($deleteScope);
			foreach($recurringRecordsList as $parent=>$childs) {
				$parentRecurringId = $parent;
				$childRecords = $childs;
			}
			if($recurringEditMode == 'future') {
				$parentKey = array_keys($childRecords, $recordId);
				$childRecords = array_slice($childRecords, $parentKey[0]);
			}
			foreach($childRecords as $record) {
				$recordModel = $this->getInstanceById($record, $this->getModuleName());
				$recordModel->deleteInviteeRecord($childRecords);
				$recordModel->getModule()->deleteRecord($recordModel);
				$deletedRecords[] = $record;
			}
			} else {
				if($recurringEditMode == 'current') {
					$parentRecurringId = $this->getParentRecurringRecord();
				}
				$inviteeDeletedRecords = $this->deleteInviteeRecord();
				$deletedRecords = array_merge($deletedRecords, $inviteeDeletedRecords);
				$this->getModule()->deleteRecord($this);
				$deletedRecords[] = $recordId;
			}
		}
		else{
			$parentRecurringId = null;
			$recordModel = $this->getInstanceById($recordId, $this->getModuleName());
			// 非繰り返し・（繰り返しのうち、今回のみを選択した場合は、対象の予定のみ削除する）
			if (empty($recurringEditMode) || $recurringEditMode == 'current') {
				$recordModel->getModule()->deleteRecord($recordModel);
				$parentRecurringId = $recordModel->getParentRecurringRecord();
				if (!empty($parentRecurringId)) {
					$cleanupFlag = $this->shouldCleanupRecurringInfo($parentRecurringId);
					if ($cleanupFlag) {
						$this->cleanupRecurringInfo($parentRecurringId);
					}
				}
				return $deletedRecords[$recordId];
			}
			// 繰り返し予定
			$deleteScope = 'self';
			$childRecords = array();
			$recurringRecordsList = $recordModel->getDeleteRecurringList($deleteScope);
			if (!empty($recurringRecordsList)) {
				// 担当者と参加者ID取得
				foreach ($recurringRecordsList as $parent => $childs) {
					$parentRecurringId = $parent;
					$childRecords = $childs;
					break;
				}
			}
			// 以降の予定を削除する場合
			if ($recurringEditMode == 'future') {
				$parentKey = array_keys($childRecords, $recordId);// 対象IDの位置を検索
				if (!empty($parentKey)) {
					$childRecords = array_slice($childRecords, $parentKey[0]);// 対象日以降のみを削除対象に絞る
				} else {
					$childRecords = array();// 系列に対象IDが無い場合は削除しない
				}
			}
			foreach ($childRecords as $record) {
				$recordModel = $this->getInstanceById($record, $this->getModuleName());
				$recordModel->getModule()->deleteRecord($recordModel);// 子レコードを削除
				$deletedRecords[] = $record;// 削除済みIDを記録
			}
		}
		// 担当者の繰り返し情報の削除後処理要否を判定し、必要な場合は削除する
		if (!empty($parentRecurringId)) {
			$cleanupFlag = $this->shouldCleanupRecurringInfo($parentRecurringId);
			if ($cleanupFlag) {
				$this->cleanupRecurringInfo($parentRecurringId);
			}
		}
		return $deletedRecords;
	}

	/**
	 * Function to get recurring information for the current record in detail view
	 * @return <Array> - which contains Recurring Information
	 */
	public function getRecurringDetails() {
		$recurringObject = $this->getRecurringObject();
		if ($recurringObject) {
			$recurringInfoDisplayData = $recurringObject->getDisplayRecurringInfo();
			$recurringEndDate = $recurringObject->getRecurringEndDate(); 
		} else {
			$recurringInfoDisplayData['recurringcheck'] = vtranslate('LBL_NO', $currentModule);
			$recurringInfoDisplayData['repeat_str'] = '';
		}
		if(!empty($recurringEndDate)){ 
			$recurringInfoDisplayData['recurringenddate'] = $recurringEndDate->get_formatted_date(); 
		}

		return $recurringInfoDisplayData;
	}

	/**
	 * Function to get the recurring object
	 * @return Object - recurring object
	 */
	public function getRecurringObject() {
		$db = PearDatabase::getInstance();
		$query = 'SELECT vtiger_recurringevents.*, vtiger_activity.date_start, vtiger_activity.time_start, vtiger_activity.due_date, vtiger_activity.time_end FROM vtiger_recurringevents
					INNER JOIN vtiger_activity ON vtiger_activity.activityid = vtiger_recurringevents.activityid
					WHERE vtiger_recurringevents.activityid = ?';
		$result = $db->pquery($query, array($this->getId()));
		if ($db->num_rows($result)) {
			return RecurringType::fromDBRequest($db->query_result_rowdata($result, 0));
		}
		return false;
	}

	/**
	 * Function updates the Calendar Reminder popup's status
	 */
	public function updateReminderStatus($status=1) {
		$db = PearDatabase::getInstance();
		$db->pquery("UPDATE vtiger_activity_reminder_popup set status = ? where recordid = ?", array($status, $this->getId()));

	}
	/**
	 * Function to get parent recurring event Id
	 */
	public function getParentRecurringRecord() {
		$adb = PearDatabase::getInstance();
		$recordId = $this->getId();
		$result = $adb->pquery("SELECT * FROM vtiger_activity_recurring_info WHERE activityid=? OR activityid = (SELECT activityid FROM vtiger_activity_recurring_info WHERE recurrenceid=?) LIMIT 1", array($recordId, $recordId));
		$parentRecurringId = $adb->query_result($result, 0,"activityid");
		return $parentRecurringId;
	}
	
	/**
	 * Function to get recurring records list
	 */
	public function getRecurringRecordsList() {
		$adb = PearDatabase::getInstance();
		$recurringRecordsList = array();
		$recordId = $this->getId();
		$result = $adb->pquery("SELECT * FROM vtiger_activity_recurring_info WHERE activityid=? OR activityid = (SELECT activityid FROM vtiger_activity_recurring_info WHERE recurrenceid=?)", array($recordId, $recordId));
		$noofrows = $adb->num_rows($result);
		$parentRecurringId = $adb->query_result($result, 0,"activityid");
		$childRecords = array();
		for($i=0; $i<$noofrows; $i++) {
			$childRecords[] = $adb->query_result($result, $i,"recurrenceid");
		}
		$recurringRecordsList[$parentRecurringId] = $childRecords;
		return $recurringRecordsList;
	}

	// 削除する予定の系列のレコードIDを取得する関数
	public function getDeleteRecurringList($deleteScope) {
		$adb = PearDatabase::getInstance();
		$recurringRecordsList = array();
		$recordId = $this->getId();

		$res = $adb->pquery(
			"SELECT smownerid FROM vtiger_crmentity WHERE crmid = ?",
			array($recordId)
		);
		$ownerId = ($adb->num_rows($res) ? $adb->query_result($res, 0, 'smownerid') : null);
		if (empty($ownerId)) return array();

		// 1. invitee_parentid
		$res = $adb->pquery(
			"SELECT invitee_parentid FROM vtiger_activity WHERE activityid = ?",
			array($recordId)
		);
		$inviteeParentId = $adb->query_result($res, 0, 'invitee_parentid');
		if (empty($inviteeParentId)) return array();

		// 2. 担当者 activityid
		$res = $adb->pquery(
			"SELECT activityid FROM vtiger_activity_recurring_info WHERE recurrenceid = ? LIMIT 1",
			array($inviteeParentId)
		);
		$parentActivityId = $adb->query_result($res, 0, 'activityid');
		if (empty($parentActivityId)) return array();

		// 3. recurrenceids　担当者ID
		$res = $adb->pquery(
			"SELECT recurrenceid FROM vtiger_activity_recurring_info WHERE activityid = ?",
			array($parentActivityId)
		);

		$recurrenceIds = array();
		while ($row = $adb->fetch_array($res)) {
			$recurrenceIds[] = $row['recurrenceid'];
		}
		if (empty($recurrenceIds)) return array();

		// 4. active activities (ONLY SELF + ordered)
		$placeholders = generateQuestionMarks($recurrenceIds);
		$sql = "SELECT a.activityid
				FROM vtiger_activity a
				INNER JOIN vtiger_crmentity c ON c.crmid = a.activityid
				WHERE c.deleted = 0
				AND a.invitee_parentid IN ($placeholders)";
		$params = $recurrenceIds;

		if ($deleteScope === 'self') {
			$sql .= " AND c.smownerid = ?";
			$params = array_merge($params, array($ownerId));   
		}

		$sql .= " ORDER BY a.date_start ASC, a.time_start ASC, a.activityid ASC";

		$res = $adb->pquery($sql, $params);

		$childRecords = array();
		while ($row = $adb->fetch_array($res)) {
			$childRecords[] = $row['activityid'];
		}

		$recurringRecordsList = array($parentActivityId => $childRecords);// 削除対象系列リストを返却（）
		return $recurringRecordsList;
	}
	/**
	 * Function to get recurring enabled for record
	 */
	public function isRecurringEnabled() {
		$recurringInfo = $this->getRecurringDetails();
		if($recurringInfo['recurringcheck'] == 'Yes') {
			return true;
		}
		return false;
	}
	
	public function deleteInviteeRecord($excludeIds = array()) {
		// 取得済みの共同参加者予定IDを使い一度だけクエリを発行して削除する
		$recordIds = $this->getInviteeRecordById($this->getId());
		$deletedRecords = array();
		if (empty($recordIds)) return;

		foreach ($recordIds as $activityid) {
			if ($activityid == $this->getId()) {
				continue;
			}
			if (!empty($excludeIds) && in_array($activityid, $excludeIds)) {
				continue;
			}
			$recordModel = $this->getInstanceById($activityid, $this->getModuleName());
			if ($recordModel) {
				$recordModel->getModule()->deleteRecord($recordModel);
				$deletedRecords[] = $activityid;
			}
		}
		return $deletedRecords;
	}
	// 共同参加者のカレンダーidを取得する関数
	// deleteInviteeRecord()にて削除するカレンダーが対象
	public function getInviteeRecordById() {
		global $adb;
		$recordId = $this->getId();
		$result = $adb->pquery("SELECT
									a.activityid
								FROM
									vtiger_activity a
									INNER JOIN vtiger_crmentity c ON c.crmid = a.activityid
								WHERE
									c.deleted = 0
									AND a.invitee_parentid = (SELECT a2.invitee_parentid FROM vtiger_activity a2 WHERE a2.activityid = ?)
		", array($recordId));

		$recordids = array();
		for($i=0; $i<$adb->num_rows($result); $i++) {
			$recordids[] = $adb->query_result($result, $i, 'activityid');
		}

		return $recordids;
	}

	public function isAllDay() {
		global $adb;
		$isAllDay = false;

		$result = $adb->pquery("SELECT allday FROM vtiger_activity WHERE activityid = ?", array($this->getId()));
		if($adb->num_rows($result) > 0) {
			$isAllDay = $adb->query_result($result, 0, "allday");
		}

		return $isAllDay;
	}

	// HTMLタグを除外してDescriptionのデータを取得する
	public function getPlainTextDescription(){
		$html = html_entity_decode($this->get('description'), ENT_QUOTES, 'UTF-8');
		$plainText = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $html)));
		return nl2br($plainText);
	}
}
