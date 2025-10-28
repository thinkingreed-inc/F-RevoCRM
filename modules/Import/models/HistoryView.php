<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger ListView Model Class
 */
class Import_HistoryView_Model extends Vtiger_Base_Model {

    public static function getImportHistory($user) {
        $db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT importid, tabid FROM vtiger_import_queue WHERE userid = ? ORDER BY importid DESC', array($user->id));
		$histories = array();
		if($result && $db->num_rows($result) > 0) {
			$noofrows = $db->num_rows($result);
			for ($i = 0; $i < $noofrows; $i++) {
				$importid = $db->query_result($result, $i, 'importid');
				$tabid = $db->query_result($result, $i, 'tabid');
				$status = self::getImportStatusCount($importid, $user);
				$histories[] = array(
					'module' => getTabname($tabid),
					'importid' => $importid,
					'total'    => $status['TOTAL'],
					'imported' => $status['IMPORTED'],					
					'created'  => $status['CREATED'],
					'skipped'  => $status['SKIPPED'],
					'updated'  => $status['UPDATED'],
					'merged'   => $status['MERGED'],
					'failed'   => $status['FAILED'],
					'pending'  => $status['PENDING'],
					'starttime'  => self::getImportStartTime($importid),
					'link' => self::getHistoryLink($importid),
				);
			}
			return $histories;
		}
		return null;
    }

	private static function getImportStatusCount($importid,$user) {
		$db = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user,$importid);

		$query = 'SELECT status FROM '.$tableName;
		$result = $db->pquery($query, array());

		$statusCount = array('TOTAL' => 0, 'IMPORTED' => 0, 'FAILED' => 0, 'PENDING' => 0, 'CREATED' => 0, 'SKIPPED' => 0, 'UPDATED' => 0, 'MERGED' => 0);

		if ($result) {
			$noOfRows = $db->num_rows($result);
			$statusCount['TOTAL'] = $noOfRows;
			for ($i = 0; $i < $noOfRows; ++$i) {
				$status = $db->query_result($result, $i, 'status');
				if (Import_Data_Action::$IMPORT_RECORD_NONE == $status) {
					$statusCount['PENDING'] ++;
				} elseif (Import_Data_Action::$IMPORT_RECORD_FAILED == $status) {
					$statusCount['FAILED'] ++;
				} else {
					$statusCount['IMPORTED'] ++;
					switch ($status) {
						case Import_Data_Action::$IMPORT_RECORD_CREATED	: $statusCount['CREATED']++;	break;
						case Import_Data_Action::$IMPORT_RECORD_SKIPPED	: $statusCount['SKIPPED']++;	break;
						case Import_Data_Action::$IMPORT_RECORD_UPDATED	: $statusCount['UPDATED']++;	break;
						case Import_Data_Action::$IMPORT_RECORD_MERGED	: $statusCount['MERGED']++;		break;
					}
				}
			}
		}
		return $statusCount;
	}	

    private static function getHistoryLink($importid){
		return "index.php?module=Import&action=ExportHistory&importid={$importid}";			
	}	

	private static function getImportStartTime($importid){
		$db = PearDatabase::getInstance();
		$query = 'SELECT time_start FROM vtiger_import_queue WHERE importid = ?';
		$result = $db->pquery($query, array($importid));
		$startTime = $db->query_result($result, $i, 'time_start');

		return $startTime;			
	}	

}
