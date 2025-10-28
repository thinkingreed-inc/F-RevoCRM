<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Import_Queue_Action extends Vtiger_Action_Controller {

	static $IMPORT_STATUS_NONE = 0;
	static $IMPORT_STATUS_SCHEDULED = 1;
	static $IMPORT_STATUS_RUNNING = 2;
	static $IMPORT_STATUS_HALTED = 3;
	static $IMPORT_STATUS_COMPLETED = 4;

	public function  __construct() {
	}

	public function process(Vtiger_Request $request) {
		return;
	}

	public static function add($request, $user) {
		$db = PearDatabase::getInstance();
		$date_var = date("Y-m-d H:i:s");

		if (!Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			Vtiger_Utils::CreateTable(
							'vtiger_import_queue',
							"(importid INT NOT NULL PRIMARY KEY,
								userid INT NOT NULL,
								tabid INT NOT NULL,
								field_mapping TEXT,
								default_values TEXT,
								merge_type INT,
								merge_fields TEXT,
								status INT default 0,
								lineitem_currency_id INT(5),
								paging INT(1),
								time_start datetime,
								time_end datetime)",
							true);
		}

		if($request->get('is_scheduled')) {
			$status = self::$IMPORT_STATUS_SCHEDULED;
		} else {
			$status = self::$IMPORT_STATUS_NONE;
		}

		if($request->get('paging_enabled')){
			$paging = 1;
		}else{
			$paging = 0;
		}

		$db->pquery('INSERT INTO vtiger_import_queue VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
				array($db->getUniqueID('vtiger_import_queue'),
						$user->id,
						getTabid($request->get('module')),
						Zend_Json::encode($request->get('field_mapping')),
						Zend_Json::encode($request->get('default_values')),
						$request->get('merge_type'),
						Zend_Json::encode($request->get('merge_fields')),
						$status,
						$request->get('lineitem_currency'),
						$paging,
						$db->formatDate($date_var, true),
						null));
	}
	
	public static function finish($user, $importId) {
		// キューをcomplete
		$db = PearDatabase::getInstance();
		$configReader = new Import_Config_Model();

		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$date_var = date("Y-m-d H:i:s");
			$db->pquery('UPDATE vtiger_import_queue SET status = ?, time_end = ? WHERE importid=?', array(self::$IMPORT_STATUS_COMPLETED, $db->formatDate($date_var, true), $importId));
		}

		// ログを削除
		$importLogLimit = Settings_Parameters_Record_Model::getParameterValue("IMPORT_MAX_HISTORY_COUNT");
		if (empty($importLogLimit) || !is_numeric($importLogLimit)) {
			// 最大件数を取得できなかった場合は10とする
			$importLogLimit = 10;
		}
		if(method_exists($user, 'getId')){
			$userId = $user->getId();
		} else {
			$userId = $user->id;
		}		
		$queueList = $db->pquery("SELECT COUNT(*) AS count FROM vtiger_import_queue WHERE userid = ? AND status = ?", array($userId, self::$IMPORT_STATUS_COMPLETED));
		$queueCount = $db->query_result($queueList, 0, 'count');

		if ($queueCount > $importLogLimit){
			$deleteCount = max($queueCount - $importLogLimit, 0);
			$result = $db->pquery("SELECT importid, userid FROM vtiger_import_queue WHERE userid = ? AND status = ? ORDER BY importid ASC LIMIT ?", array($userId, self::$IMPORT_STATUS_COMPLETED, $deleteCount));
			$noofrows = $db->num_rows($result);
			for ($i = 0; $i < $noofrows; $i++) {
				$importid = $db->query_result($result, $i, 'importid');
				$userid = $db->query_result($result, $i, 'userid');
				self::remove($importid, $userid);
			}
		}

	}

	public static function remove($importId, $userid = null) {
		$db = PearDatabase::getInstance();
		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$db->pquery('DELETE FROM vtiger_import_queue WHERE importid=?', array($importId));
		}
		
		$user = new Users();
		if(method_exists($userid, 'getId')){
			$user->id = $userid->getId();
		} else if (!empty($userid->id)) {
			$user->id = $userid->id;
		} else {
			$user->id = $userid;
		}

		$db->pquery('DROP TABLE IF EXISTS '.Import_Utils_Helper::getDbTableName($user, $importId), array());

	}

	public static function removeForUser($user) {
		$db = PearDatabase::getInstance();
		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$db->pquery('UPDATE vtiger_import_queue SET status = ? WHERE userid=?', array(self::$IMPORT_STATUS_COMPLETED, $user->id));
		}
	}

	public static function getUserCurrentImportInfo($user) {
		$db = PearDatabase::getInstance();

		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$queueResult = $db->pquery('SELECT * FROM vtiger_import_queue WHERE status!=? AND userid=? ORDER BY importid DESC LIMIT 1', array(self::$IMPORT_STATUS_COMPLETED, $user->id));

			if($queueResult && $db->num_rows($queueResult) > 0) {
				$rowData = $db->raw_query_result_rowdata($queueResult, 0);
				return self::getImportInfoFromResult($rowData);
			}
		}
		return null;
	}

	public static function getImportInfo($module, $user) {
		$db = PearDatabase::getInstance();

		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$queueResult = $db->pquery('SELECT * FROM vtiger_import_queue WHERE status != ? AND tabid=? AND userid=?',
											array(self::$IMPORT_STATUS_COMPLETED, getTabid($module), $user->id));

			if($queueResult && $db->num_rows($queueResult) > 0) {
				$rowData = $db->raw_query_result_rowdata($queueResult, 0);
				return self::getImportInfoFromResult($rowData);
			}
		}
		return null;
	}

	public static function getImportInfoById($importId) {
		$db = PearDatabase::getInstance();

		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$queueResult = $db->pquery('SELECT * FROM vtiger_import_queue WHERE importid=?', array($importId));

			if($queueResult && $db->num_rows($queueResult) > 0) {
				$rowData = $db->raw_query_result_rowdata($queueResult, 0);
				return self::getImportInfoFromResult($rowData);
			}
		}
		return null;
	}

	public static function getAll($status=false) {
		$db = PearDatabase::getInstance();

		$query = 'SELECT * FROM vtiger_import_queue';
		$params = array();
		if($status !== false) {
			$query .= ' WHERE status = ?';
			array_push($params, $status);
		}
		$result = $db->pquery($query, $params);

		$noOfImports = $db->num_rows($result);
		$scheduledImports = array();
		for ($i = 0; $i < $noOfImports; ++$i) {
			$rowData = $db->raw_query_result_rowdata($result, $i);
			$scheduledImports[$rowData['importid']] = self::getImportInfoFromResult($rowData);
		}
		return $scheduledImports;
	}

	static function getImportInfoFromResult($rowData) {
		return array(
			'id' => $rowData['importid'],
			'module' => getTabModuleName($rowData['tabid']),
			'field_mapping' => Zend_Json::decode($rowData['field_mapping']),
			'default_values' => Zend_Json::decode($rowData['default_values']),
			'merge_type' => $rowData['merge_type'],
			'merge_fields' => Zend_Json::decode($rowData['merge_fields']),
			'user_id' => $rowData['userid'],
			'status' => $rowData['status'],
			'lineitem_currency_id' => $rowData['lineitem_currency_id'],
			'paging' => $rowData['paging']
		);
	}

	static function updateStatus($importId, $status) {
		$db = PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_import_queue SET status=? WHERE importid=?', array($status, $importId));
	}

	static function updateQueueInfo($importId, $request) {
		$db = PearDatabase::getInstance();

		if($request->get('is_scheduled')) {
			$status = self::$IMPORT_STATUS_SCHEDULED;
		} else {
			$status = self::$IMPORT_STATUS_NONE;
		}
		if($request->get('paging_enabled')){
			$paging = 1;
		}else{
			$paging = 0;
		}

		$db->pquery('UPDATE vtiger_import_queue SET status=?, paging=? WHERE importid=?', array($status, $paging, $importId));
	}

	static function getModulenameByImportid($importid) {
		$db = PearDatabase::getInstance();

		if(Vtiger_Utils::CheckTable('vtiger_import_queue')) {
			$queueResult = $db->pquery('SELECT tabid FROM vtiger_import_queue WHERE importid=?', array($importid));
			if($queueResult && $db->num_rows($queueResult) > 0) {
				$rowData = $db->raw_query_result_rowdata($queueResult, 0);
				$rowdata = self::getImportInfoFromResult($rowData);
				return $rowdata['module'];
			}
		}
		return null;

	}
}