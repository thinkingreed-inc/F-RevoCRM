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

    public static function getImportHistory($user, $tabid) {
        $db = PearDatabase::getInstance();
		// システム管理者の場合は全ユーザーの履歴を表示
		if(is_admin($user)){
			$result = $db->pquery('SELECT importid, userid FROM vtiger_import_queue WHERE tabid = ? ORDER BY importid DESC', array($tabid));
		} else {
			$result = $db->pquery('SELECT importid, userid FROM vtiger_import_queue WHERE userid = ? AND tabid = ? ORDER BY importid DESC', array($user->id, $tabid));
		}
		$histories = array();
		if($result && $db->num_rows($result) > 0) {
			$noofrows = $db->num_rows($result);
			for ($i = 0; $i < $noofrows; $i++) {
				$importid = $db->query_result($result, $i, 'importid');
				$moduleName = getTabname($tabid);
				$status = self::getImportStatusCount($importid, $user, $moduleName);
				$username = self::getUsernameByImportid($importid);
				$userid = $db->query_result($result, $i, 'userid');
				$histories[] = array(
					'module' => $moduleName,
					'username' => $username,
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
					'link' => self::getHistoryLink($importid,$userid),
				);
			}
			return $histories;
		}
		return null;
    }

	private static function getImportStatusCount($importid,$user,$moduleName='') {
		$db = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user,$importid);

		// 価格表の場合、同一booknameの中で最小idのstatusを返す
		if ($moduleName == 'PriceBooks') {
			$query = 'SELECT main.bookname, main.status FROM '.$tableName.' AS main'
				.' INNER JOIN (SELECT bookname, MIN(id) AS min_id FROM '.$tableName.' GROUP BY bookname) AS summary'
				.' ON main.bookname = summary.bookname AND main.id = summary.min_id';
		} else {
			$query = 'SELECT status FROM '.$tableName;
		}		
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

    private static function getHistoryLink($importid, $userid){
		return "index.php?module=Import&action=ExportHistory&importid={$importid}&userid={$userid}";			
	}	

	private static function getImportStartTime($importid){
		$db = PearDatabase::getInstance();
		$query = 'SELECT time_start FROM vtiger_import_queue WHERE importid = ?';
		$result = $db->pquery($query, array($importid));
		if ($db->num_rows($result) > 0) {
			return $db->query_result($result, 0, 'time_start');
		}
		return null;
	}
	
	private static function getUsernameByImportid($importid){
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT userid FROM vtiger_import_queue WHERE importid = ?', array($importid));
		if ($db->num_rows($result) > 0) {
			$userid = $db->query_result($result, 0, 'userid');
			return getUserFullName($userid);		
		}
		return null;
	}

}
