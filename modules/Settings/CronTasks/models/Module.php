<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_CronTasks_Module_Model extends Settings_Vtiger_Module_Model {

	var $baseTable = 'vtiger_cron_task';
	var $baseIndex = 'id';
	// frequency 列は周期だけでなく「毎日 03:30」のような指定も表示するため、
	// 見出しは「周期」ではなく「実行タイミング」にしている
	var $listFields = array('sequence' => 'Sequence', 'name' => 'Cron Job', 'frequency' => 'LBL_SCHEDULE_TYPE',
			'retry_timeout' => 'LBL_RETRY_TIMEOUT', 'status' => 'Status',
			'laststart' => 'Last Start', 'lastend' => 'Last End', 'next_run_at' => 'LBL_NEXT_RUN_AT');
	var $nameFields = array('');
	var $name = 'CronTasks';

	/**
	 * Function to get editable fields from this module
	 * @return <Array> List of fieldNames
	 */
	public function getEditableFieldsList() {
		return array('frequency', 'status', 'retry_timeout',
				'schedule_type', 'run_at_minutes', 'run_on_weekdays', 'run_on_day',
				'log_retention_count');
	}

	/**
	 * Function to update sequence of several records
	 * @param <Array> $sequencesList
	 */
	public function updateSequence($sequencesList) {
		$db = PearDatabase::getInstance();

		$updateQuery = "UPDATE vtiger_cron_task SET sequence = CASE";

		foreach ($sequencesList as $sequence => $recordId) {
			$updateQuery .= " WHEN id = $recordId THEN $sequence ";
		}
		$updateQuery .= " END";
		$db->pquery($updateQuery, array());
	}
	
	public function hasCreatePermissions() {
		return false;
	}
	
	public function isPagingSupported() {
		return false;
	}

}
