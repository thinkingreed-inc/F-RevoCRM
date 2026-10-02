<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
********************************************************************************/

/**
 * Class to handle repeating events
 */
class Calendar_RepeatEvents {
	
	public static $recurringDataChanged;
	public static $recurringTypeChanged;

	/**
	 * Get timing using YYYY-MM-DD HH:MM:SS input string.
	 */
	static function mktime($fulldateString) {
		$splitpart = self::splittime($fulldateString);
		$datepart = split('-', $splitpart[0]);
		$timepart = split(':', $splitpart[1]);
		return mktime($timepart[0], $timepart[1], 0, $datepart[1], $datepart[2], $datepart[0]);
	}
	/**
	 * Increment the time by interval and return value in YYYY-MM-DD HH:MM format.
	 */
	static function nexttime($basetiming, $interval) {
		return date('Y-m-d H:i', strtotime($interval, $basetiming));
	}
	/**
	 * Based on user time format convert the YYYY-MM-DD HH:MM value.
	 */
	static function formattime($timeInYMDHIS) {
		global $current_user;
		$format_string = 'Y-m-d H:i';
		switch($current_user->date_format) {
			case 'dd.mm.yyyy': $format_string = 'd.m.Y H:i'; break;
			case 'mm.dd.yyyy': $format_string = 'm.d.Y H:i'; break;
			case 'yyyy.mm.dd': $format_string = 'Y.m.d H:i'; break;
			
			case 'dd/mm/yyyy': $format_string = 'd/m/Y H:i'; break;
			case 'mm/dd/yyyy': $format_string = 'm/d/Y H:i'; break;
			case 'yyyy/mm/dd': $format_string = 'Y/m/d H:i'; break;

			case 'dd-mm-yyyy': $format_string = 'd-m-Y H:i'; break;
			case 'mm-dd-yyyy': $format_string = 'm-d-Y H:i'; break;
			case 'yyyy-mm-dd': $format_string = 'Y-m-d H:i'; break;
		}
		return date($format_string, self::mktime($timeInYMDHIS));
	}
	/**
	 * Split full timing into date and time part.
	 */
	static function splittime($fulltiming) {
		return split(' ', $fulltiming);
	}
	/**
	 * Calculate the time interval to create repeated event entries.
	 */
	static function getRepeatInterval($type, $frequency, $recurringInfo, $start_date, $limit_date) {
		$repeatInterval = Array();
		$starting = self::mktime($start_date);
		$limiting = self::mktime($limit_date);

		if($type == 'Daily') {	
			$count = 0;
			while(true) {
				++$count;
				$interval = ($count * $frequency);
				if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
					break;
				}
				$repeatInterval[] = $interval;
			}
		} else if($type == 'Weekly') {
			if($recurringInfo->dayofweek_to_rpt == null) {
				$count = 0;
				$weekcount = 7;
				while(true) {
					++$count;
					$interval = $count * $weekcount;
					if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
						break;
					}
					$repeatInterval[] = $interval;
				}
			} else {
				$count = 0;
				while(true) {
					++$count;
					$interval = $count;
					$new_timing = self::mktime(self::nexttime($starting, "+$interval days"));
					$new_timing_dayofweek = date('N', $new_timing);
					if($new_timing > $limiting) {
						break;
					}
					if(in_array($new_timing_dayofweek-1, $recurringInfo->dayofweek_to_rpt)) {
						$repeatInterval[] = $interval;
					}
				}
			}
		} else if($type == 'Monthly') {
			$count = 0;
			$avg_monthcount = 30; // TODO: We need to handle month increments precisely!
			while(true) {
				++$count;
				$interval = $count * $avg_monthcount;
				if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
					break;
				}
				$repeatInterval[] = $interval;
			}
		} else if($type == 'Yearly') {
			$count = 0;
			$avg_monthcount = 30;
				while(true) {
					++$count;
					$interval = $count * $avg_monthcount;
					if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
						break;
				}
				$repeatInterval[] = $interval;
			}
		}
		return $repeatInterval;
	}

	/**
	 * 繰り返し系列の親 ID と、更新対象になる活動 ID を返す。
	 *
	 * 招待された参加者用にコピーされた活動（invitee_parentid が自分以外）は
	 * vtiger_activity_recurring_info に登録されないため、招待元の活動をたどって
	 * 本体の系列を返す。論理削除済みの回は更新対象から除く。
	 *
	 * 戻り値の recordId は、渡された活動が系列上のどの回にあたるかを示す。
	 * 招待コピーを渡した場合は招待元の回の ID になる。
	 *
	 * 招待された参加者が「以降の活動を含む」「全ての活動」を選ぶと、招待元や他の
	 * 参加者の回も更新・削除の対象になる。これは #1857 で求められた仕様であり、
	 * 呼び出し側でも系列の各回に対する編集・削除権限は確認していない。
	 *
	 * @param int|string $recordId
	 * @return array{parentId: int|null, recordId: int|null, records: list<int>}
	 */
	static function resolveSeries($recordId) {
		$adb = PearDatabase::getInstance();
		$recordId = (int) $recordId;
		$seriesRecordId = $recordId;
		$parentId = self::findSeriesParentId($recordId);

		if (empty($parentId)) {
			$result = $adb->pquery('SELECT invitee_parentid FROM vtiger_activity WHERE activityid = ?', array($recordId));
			if ($adb->num_rows($result) > 0) {
				$inviteeParentId = (int) $adb->query_result($result, 0, 'invitee_parentid');
				if (!empty($inviteeParentId) && $inviteeParentId !== $recordId) {
					$parentId = self::findSeriesParentId($inviteeParentId);
					if (!empty($parentId)) {
						$seriesRecordId = $inviteeParentId;
					}
				}
			}
		}

		if (empty($parentId)) {
			return array('parentId' => null, 'recordId' => null, 'records' => array());
		}

		$result = $adb->pquery('SELECT ri.recurrenceid FROM vtiger_activity_recurring_info ri
					INNER JOIN vtiger_crmentity ce ON ce.crmid = ri.recurrenceid
					INNER JOIN vtiger_activity a ON a.activityid = ri.recurrenceid
					WHERE ri.activityid = ? AND ce.deleted = 0
					ORDER BY a.date_start, a.time_start, ri.recurrenceid', array($parentId));

		$records = array();
		$noofrows = $adb->num_rows($result);
		for ($i = 0; $i < $noofrows; $i++) {
			$records[] = (int) $adb->query_result($result, $i, 'recurrenceid');
		}

		return array('parentId' => $parentId, 'recordId' => $seriesRecordId, 'records' => $records);
	}

	/**
	 * 保存前のスナップショットと保存後の値から、変更されたフィールドを返す。
	 *
	 * 比較の仕方は VTEntityDelta::computeDelta() に合わせる。保存前の値が空なら
	 * 値の入ったフィールドをすべて変更扱いにし、改行コードだけの違いは変更としない。
	 *
	 * @param array<string, mixed>|null $oldData 保存前のフィールド値
	 * @param array<string, mixed> $newData 保存後のフィールド値
	 * @return array<string, array{oldValue: mixed, currentValue: mixed}>
	 */
	static function computeDeltaFromSnapshot($oldData, $newData) {
		$delta = array();
		$oldData = is_array($oldData) ? $oldData : array();

		foreach ($newData as $fieldName => $newValue) {
			$oldValue = array_key_exists($fieldName, $oldData) ? $oldData[$fieldName] : null;
			$isModified = false;

			if (empty($oldValue)) {
				if (!empty($newValue)) {
					$isModified = true;
				}
			} elseif (self::normalizeForComparison($oldValue) != self::normalizeForComparison($newValue)) {
				$isModified = true;
			}

			if ($isModified) {
				$delta[$fieldName] = array('oldValue' => $oldValue, 'currentValue' => $newValue);
			}
		}

		return $delta;
	}

	/**
	 * 活動の保存前スナップショットを取る。
	 *
	 * 繰り返しの他の回へコピーするのは「今回の保存で変更されたフィールド」だけで、
	 * その判定に VTEntityDelta の保存前データを使っていた。しかし保存後に動く
	 * ワークフロー (VTUpdateFieldsTask) が vtiger.entity.beforesave を再発火して
	 * 保存前データを保存後の値で上書きするため、差分が空になり他の回へ何も
	 * 反映されなくなる。保存前にここで控えた値を使う。
	 *
	 * @param int|string $recordId
	 * @return array<string, mixed>|null
	 */
	static function captureEntitySnapshot($recordId) {
		if (empty($recordId)) {
			return null;
		}

		// 保存前に呼ばれるため、イベントハンドラ経由の読み込みを当てにできない
		require_once 'include/events/VTEntityData.inc';

		$adb = PearDatabase::getInstance();
		/** @var VTEntityData $entityData コアの docblock が壊れており型を解決できないため明示する */
		$entityData = VTEntityData::fromEntityId($adb, $recordId, 'Events');

		return self::normalizeSnapshot($entityData->getData());
	}

	/**
	 * 他の回へコピーするフィールドを決める差分を返す。
	 *
	 * 保存前スナップショットがあればそれと保存後の値を突き合わせる。
	 * 無い場合は従来どおり VTEntityDelta に任せる。
	 *
	 * @param int|string $recordId
	 * @param array<string, mixed>|null $preSaveData
	 * @return array<string, array{oldValue: mixed, currentValue: mixed}>
	 */
	private static function resolveEntityDelta($recordId, $preSaveData) {
		if (is_array($preSaveData)) {
			$currentData = self::captureEntitySnapshot($recordId);

			return self::computeDeltaFromSnapshot($preSaveData, is_array($currentData) ? $currentData : array());
		}

		require_once 'data/VTEntityDelta.php';

		$vtEntityDelta = new VTEntityDelta();

		return $vtEntityDelta->getEntityDelta('Events', $recordId, true);
	}

	/**
	 * 保存前スナップショットを差分計算で扱える配列にそろえる。
	 *
	 * CRMEntity の column_fields は素の配列のことも TrackableObject のこともある。
	 * どちらでもない場合は「保存前データなし」として null を返す。
	 *
	 * @param mixed $data
	 * @return array<mixed, mixed>|null
	 */
	static function normalizeSnapshot($data) {
		if (is_array($data)) {
			return $data;
		}

		if (is_object($data) && method_exists($data, 'getColumnFields')) {
			$fields = $data->getColumnFields();

			return is_array($fields) ? $fields : null;
		}

		return null;
	}

	/**
	 * 差分比較用に値をそろえる。改行コードの違いは変更とみなさない。
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private static function normalizeForComparison($value) {
		if (!is_string($value)) {
			return $value;
		}

		return str_replace(array("\r\n", "\r", "\n"), '', $value);
	}

	/**
	 * 活動を繰り返し系列から取り除く。
	 *
	 * 削除された活動が系列に残っていると、以降の更新で回と活動の対応がずれ、
	 * 末尾の回に変更が届かなくなるため、削除時には必ず取り除く。
	 * 系列の親を取り除いた場合も、残りの回は同じ親の系列にとどまる。
	 *
	 * @param int|string $recordId
	 * @return void
	 */
	static function removeFromSeries($recordId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery('DELETE FROM vtiger_activity_recurring_info WHERE recurrenceid = ?', array((int) $recordId));
	}

	/**
	 * 活動が属する繰り返し系列の親 ID を返す。属していなければ null。
	 *
	 * @param int $recordId
	 * @return int|null
	 */
	private static function findSeriesParentId($recordId) {
		$adb = PearDatabase::getInstance();

		$result = $adb->pquery('SELECT 1 FROM vtiger_activity_recurring_info WHERE activityid = ? LIMIT 1', array($recordId));
		if ($adb->num_rows($result) > 0) {
			return (int) $recordId;
		}

		$result = $adb->pquery('SELECT activityid FROM vtiger_activity_recurring_info WHERE recurrenceid = ? LIMIT 1', array($recordId));
		if ($adb->num_rows($result) > 0) {
			return (int) $adb->query_result($result, 0, 'activityid');
		}

		return null;
	}

	/**
	 * Repeat Activity instance till given limit.
	 *
	 * @param array<mixed, mixed>|null $preSaveData 保存前スナップショット。null なら VTEntityDelta にフォールバックする
	 */
	static function repeat($focus, $recurObj, $preSaveData = null) {
		$adb = PearDatabase::getInstance();
		$frequency = $recurObj->recur_freq;
		$repeattype= $recurObj->recur_type;
		
		$base_focus = CRMEntity::getInstance('Events');
		$base_focus->column_fields = $focus->column_fields;
		$base_focus->id = $focus->id;
		$parentId = $focus->column_fields['id'];
		
		$delta = self::resolveEntityDelta($parentId, $preSaveData);
		$skip_focus_fields = Array ('record_id', 'createdtime', 'modifiedtime');
		
		if($focus->column_fields['mode'] == 'edit') {
			$recurringEditMode = $focus->column_fields['recurringEditMode'];

			$series = self::resolveSeries($parentId);
			$parentRecurringId = $series['parentId'];
			// 招待された参加者用のコピーを編集した場合、系列上の位置は招待元の回で判定する
			$seriesRecordId = $series['recordId'];
			$childRecords = $series['records'];

			// 系列を引けない活動をここで繰り返すと、元の系列とは別に活動が作られて
			// 重複するため、繰り返しの対象外として扱う
			if(empty($childRecords)) {
				return;
			}

			// 繰り返しでない活動を繰り返しに変更した保存では、系列は直前に登録された
			// 自分自身の 1 件だけで、これから 2 回目以降を作る段階にある。
			// 確認ダイアログが出ないため recurringEditMode は空で送られてくる
			$isNewSeries = (count($childRecords) === 1 && $childRecords[0] == $seriesRecordId);

			// 既存の系列に対して future / all のどちらでもない場合は、
			// この回だけの更新として系列の他の回には触らない
			if(!$isNewSeries && $recurringEditMode != 'future' && $recurringEditMode != 'all') {
				return;
			}

			if($seriesRecordId != $parentId) {
				// 招待された参加者用のコピーを起点にした場合、既存の回の担当は書き換えない。
				// 回ごとに担当（招待元は招待者、コピーは参加者）が異なるため、
				// 上書きすると他の参加者の担当まで入れ替わってしまう。
				// 新しく作る回の担当だけ系列の活動に合わせる
				$skip_focus_fields[] = 'assigned_user_id';
				$seriesRecordModel = Vtiger_Record_Model::getInstanceById($seriesRecordId, 'Events');
				$focus->column_fields['assigned_user_id'] = $seriesRecordModel->get('assigned_user_id');
			}

			if($focus->column_fields['recurringEditMode'] == 'all' && $seriesRecordId != $childRecords[0]) {
				// 全ての回を対象にする場合は、系列の 1 回目を起点に日付を計算し直す
				$parentModel = Vtiger_Record_Model::getInstanceById($childRecords[0]);
				$_REQUEST['date_start'] = $parentModel->get('date_start');
				$recurObj = getrecurringObjValue();
			}

			if($focus->column_fields['recurringEditMode'] == 'future') {
				$parentKey = array_keys($childRecords, $seriesRecordId);
				// 起点が系列に見つからない場合、そのまま進めると系列全体が更新対象になり
				// 過去の回まで書き換わるため、繰り返しの対象外として扱う
				if(empty($parentKey)) {
					return;
				}
				$childRecords = array_slice($childRecords, $parentKey[0]);
			}
			$eventStartDate = $focus->column_fields['date_start'];
			$interval = strtotime($focus->column_fields['due_date']) - 
						strtotime($focus->column_fields['date_start']);
			$i = 0;
			// 編集した回そのものは保存済み。future では更新済み扱いにして削除対象に回さない。
			// all は系列の 1 回目から回を割り当て直すため、繰り返しの範囲外になった回は
			// 削除されるべきで、ここで守らない
			$updatedRecords = ($focus->column_fields['recurringEditMode'] == 'future') ? array($seriesRecordId) : array();

			if(self::$recurringTypeChanged && $focus->column_fields['recurringEditMode'] == 'future') {
				foreach($childRecords as $record) {
					$adb->pquery("DELETE FROM vtiger_activity_recurring_info WHERE activityid=? AND recurrenceid=?", array($parentRecurringId, $record));
				}
				// 新しい系列の親は系列上の回にする。招待コピーを親にすると
				// 招待コピー起点の別系列が作られてしまう
				$parentRecurringId = $seriesRecordId;
			}
			
			foreach ($recurObj->recurringdates as $index => $startDate) {
				$recordId = $childRecords[$i];
				if(!empty($recordId) && !empty($startDate)) {
					$i++;
					if(!self::$recurringDataChanged && empty($delta['date_start']) && empty($delta['due_date'])) {
						$skip_focus_fields[] = 'date_start';
						$skip_focus_fields[] = 'due_date';
					}
					if($index == 0 && $eventStartDate == $startDate && $focus->column_fields['recurringEditMode'] != 'future') {
						$updatedRecords[] = $recordId;
						continue;
					}
					$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
					$recordModel->set("is_allday", $focus->is_allday); //recordModulにis_alldayが無いので追加
					$recordModel->set('mode', 'edit');
					if($focus->column_fields['recurringEditMode'] == 'future' && $recordModel->get('date_start') >= $eventStartDate && $recordModel->get('date_start') != $eventStartDate) {
						$startDateTimestamp = strtotime($startDate);
						$endDateTime = $startDateTimestamp + $interval;
						$endDate = date('Y-m-d', $endDateTime);
						
						foreach($base_focus->column_fields as $key=>$value) {
							if(in_array($key, $skip_focus_fields)) {
								// skip copying few fields
							} else if($key == 'date_start') {
								$recordModel->set('date_start',$startDate);
							} else if($key == 'due_date') {
								$recordModel->set('due_date',$endDate);
							}  else {
								if(!empty($delta[$key])) {
									$recordModel->set($key, $value);
								}
							}
						}
						$recordModel->set('id', $recordId);
						if($numberOfRepeats > 10 && $index > 10) {
							unset($recordModel['sendnotification']);
						}
						$updatedRecords[] = $recordId;
						$recordModel->save('Calendar');
						if(self::$recurringTypeChanged) {
							$adb->pquery("INSERT INTO vtiger_activity_recurring_info VALUES (?,?)", array($parentRecurringId, $recordId));
						}
					} else if($focus->column_fields['recurringEditMode'] == 'all' && $recordModel->get('date_start') != $eventStartDate) {
						$startDateTimestamp = strtotime($startDate);
						$endDateTime = $startDateTimestamp + $interval;
						$endDate = date('Y-m-d', $endDateTime);
						foreach($base_focus->column_fields as $key=>$value) {
							if(in_array($key, $skip_focus_fields)) {
								// skip copying few fields
							} else if($key == 'date_start') {
								$recordModel->set('date_start',$startDate);
							} else if($key == 'due_date') {
								$recordModel->set('due_date',$endDate);
							} else {
								if(!empty($delta[$key])) {
									$recordModel->set($key, $value);
								}
							}
						}
						$recordModel->set('id', $recordId);

						if($numberOfRepeats > 10 && $index > 10) {
							unset($recordModel['sendnotification']);
						}
						$updatedRecords[] = $recordId;
						$recordModel->save('Calendar');
					}

				} else if(empty($recordId) && !empty($startDate) && self::$recurringDataChanged) {
					//create new record with new start date
					$datesList = array();
					$datesList[] = $startDate;
					if(empty($focus->column_fields['eventstatus'])) {
						$focus->column_fields['eventstatus'] = 'Planned';
					}
					self::createRecurringEvents($focus, $recurObj, $datesList, $parentRecurringId);
				}
			}
			$deletingRecords = array_diff($childRecords, $updatedRecords);
			if(self::$recurringDataChanged && !empty($deletingRecords)) {
				foreach($deletingRecords as $record) {
					//delete reocrd with that reocrdid
					$recordModel = Vtiger_Record_Model::getInstanceById($record);
					self::removeFromSeries($record);
					$recordModel->delete();
				}
			}
		} else {
			$recurringDates = $recurObj->recurringdates;
			self::createRecurringEvents($focus, $recurObj, $recurringDates);
		}
	}
	
	static function createRecurringEvents($focus, $recurObj, $recurringDates, $parentId = false) {
		$adb = PearDatabase::getInstance();
		$base_focus = CRMEntity::getInstance('Events');
		$base_focus->column_fields = $focus->column_fields;
		$base_focus->id = $focus->id;
		$skip_focus_fields = Array ('record_id', 'createdtime', 'modifiedtime');
		if(empty($parentId)) {
			$parentId = $focus->column_fields['id'];
		}

		$eventStartDate = $focus->column_fields['date_start'];
		$interval = strtotime($focus->column_fields['due_date']) - 
				strtotime($focus->column_fields['date_start']);
		
		foreach ($recurringDates as $index => $startDate) {
			if($index == 0 && $eventStartDate == $startDate) {
				continue;
			}
			$startDateTimestamp = strtotime($startDate);
			$endDateTime = $startDateTimestamp + $interval;
			$endDate = date('Y-m-d', $endDateTime);

			$new_focus = CRMEntity::getInstance('Events');

			// Reset the new_focus and prepare for reuse
			if(isset($new_focus->id)) unset($new_focus->id);
			$new_focus->column_fields = new TrackableObject();
			$new_focus->invitee_parentid = false;
			$new_focus->is_not_invitees_update = false;

			foreach($base_focus->column_fields as $key=>$value) {
				if(in_array($key, $skip_focus_fields)) {
					// skip copying few fields
				} else if($key == 'date_start') {
					$new_focus->column_fields['date_start'] = $startDate;
				} else if($key == 'due_date') {
					$new_focus->column_fields['due_date']   = $endDate;
				} else if($key == 'is_allday') {
					$new_focus->is_allday                   = $value;
					$new_focus->column_fields[$key]         = $value;
				} else {
					$new_focus->column_fields[$key]         = $value;
				}
			}
			if($numberOfRepeats > 10 && $index > 10) {
				unset($new_focus->column_fields['sendnotification']);
			}
			$new_focus->save('Calendar');
			$record = $new_focus->id;
			
			$adb->pquery("INSERT INTO vtiger_activity_recurring_info VALUES (?,?)", array($parentId, $record));

		}
	}
	
	/**
	 * リクエストの繰り返し設定をもとに系列を作り直す。
	 *
	 * @param array<mixed, mixed>|null $preSaveData 保存前スナップショット。repeat() へそのまま渡す
	 */
	static function repeatFromRequest($focus, $recurObjDb = false, $preSaveData = null) {
		global $log, $default_charset, $current_user;
		$adb = PearDatabase::getInstance();
		$recurObj = getrecurringObjValue();
		self::$recurringDataChanged = self::checkRecurringDataChanged($recurObj, $recurObjDb);
		if(!empty($recurObjDb) && self::$recurringDataChanged && $recurObj->recur_type != $recurObjDb->recur_type) {
			self::$recurringTypeChanged = true;
		} else {
			self::$recurringTypeChanged = false;
		}
		if($focus->column_fields['recurringtype'] != '' && $focus->column_fields['recurringtype'] != '--None--' && $focus->column_fields['recurringEditMode'] != 'current') {
			//If no followup mode, recurring events status should not be held for future events
			if($focus->column_fields['eventstatus'] == 'Held') {
				if($focus->column_fields['mode'] == '') {
					$focus->column_fields['eventstatus'] = 'Planned';
				} else {
					unset($focus->column_fields['eventstatus']);
				}
			}
			
			$originalRecordId = $focus->column_fields['id'];
			//If recurring Enabled, insert the entry only once for parent also
			if(empty($recurObjDb) && self::$recurringDataChanged) {
				$adb->pquery("INSERT INTO vtiger_activity_recurring_info VALUES (?,?)", array($originalRecordId, $originalRecordId));
			}
			self::repeat($focus, $recurObj, $preSaveData);
		} else if(empty($recurObj) && self::$recurringDataChanged) {
			//If recurring info unchecked, should delete all the events in the series
			self::deleteRepeatEvents($focus->column_fields['id']);
		}
	}
    
	static function deleteRepeatEvents($parentId) {
		$adb = PearDatabase::getInstance();
		$recordModel = Vtiger_Record_Model::getCleanInstance('Events');
		$recordModel->set('id', $parentId);
		$recurringRecordsList = $recordModel->getRecurringRecordsList();
		foreach($recurringRecordsList as $parent=>$childs) {
			$parentRecurringId = $parent;
			$childRecords = $childs;
		}
		foreach($childRecords as $record) {
			$recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$adb->pquery("DELETE FROM vtiger_activity_recurring_info WHERE activityid=? AND recurrenceid=?", array($parentRecurringId, $record));
			if($record == $parentId) {
				continue;
			}
			$recordModel->delete();
		}
	}
	
    static function checkRecurringDataChanged($recurObjRequest, $recurObjDb) {
        if(($recurObjRequest->recur_type == $recurObjDb->recur_type) && ($recurObjRequest->recur_freq == $recurObjDb->recur_freq)
                && ($recurObjRequest->recurringdates[0] == $recurObjDb->recurringdates[0]) && ($recurObjRequest->recurringenddate == $recurObjDb->recurringenddate)
                && ($recurObjRequest->dayofweek_to_rpt == $recurObjDb->dayofweek_to_rpt) && ($recurObjRequest->repeat_monthby == $recurObjDb->repeat_monthby)
                && ($recurObjRequest->rptmonth_datevalue == $recurObjDb->rptmonth_datevalue) && ($recurObjRequest->rptmonth_daytype == $recurObjDb->rptmonth_daytype)) {
            return false;
        } else {
            return true;
        }
    }
}

?>