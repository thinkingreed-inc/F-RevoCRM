<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class WSAPP_Logs {
	const basetable = 'vtiger_wsapp_logs_basic';
	const detailstable = 'vtiger_wsapp_logs_details';

	/**
	 * Function to add sync details
	 * @global type $adb
	 * @param type $syncRecords
	 */
	static function add($syncRecords) {
		global $adb;
		$wsappLogs = new WSAPP_Logs();
		$extensiontabid = getTabid($syncRecords['Extension'][0]);

		//To cleanup logs table once in 6 months
		WSAPP_Logs::purge($extensiontabid);

		$recordDetails = $wsappLogs->getSyncRecordsDetails($syncRecords);
		$recordsCount = $recordDetails[0];
		$recordInfo = $recordDetails[1];
		$extensionModule = $recordDetails[2];
		$syncTime = $syncRecords['synctime'];
		$id = $adb->getUniqueID('vtiger_wsapp_logs_basic');
		$userId = $syncRecords['user'];
		$params1 = array($id, $extensiontabid, $extensionModule, $syncTime, $recordsCount['app']['create'],
						$recordsCount['app']['update'], $recordsCount['app']['delete'], $recordsCount['app']['skipped'],
						$recordsCount['vtiger']['create'], $recordsCount['vtiger']['update'],
						$recordsCount['vtiger']['delete'], $recordsCount['vtiger']['skipped'], $userId);

		$query = 'INSERT INTO '.self::basetable.' VALUES('.generateQuestionMarks($params1).')';
		$adb->pquery($query, $params1);

		$app_create = Zend_Json::encode($recordInfo['app_record']['create']);
		$app_update = Zend_Json::encode($recordInfo['app_record']['update']);
		$app_delete = Zend_Json::encode($recordInfo['app_record']['delete']);
		$app_skipped = Zend_Json::encode($recordInfo['app_record']['skipped']);
		$vt_create = Zend_Json::encode($recordInfo['vt_record']['create']);
		$vt_update = Zend_Json::encode($recordInfo['vt_record']['update']);
		$vt_delete = Zend_Json::encode($recordInfo['vt_record']['delete']);
		$vt_skipped = Zend_Json::encode($recordInfo['vt_record']['skipped']);
		$params2 = array($id, $app_create, $app_update, $app_delete, $app_skipped, $vt_create, $vt_update, $vt_delete, $vt_skipped);

		$query = 'INSERT INTO '.self::detailstable.' VALUES('.generateQuestionMarks($params2) .')';
		$adb->pquery($query, $params2);
	}

	/**
	 * Function to get the sync count based on Extension or Extension and Module
	 * @global type $adb
	 * @param type $pagingModel
	 * @param type $extension
	 * @param type $module
	 * @return $syncCounts
	 */
	static function getSyncCounts($pagingModel, $extension, $module = false) {
		global $adb;
		$tabid = getTabid($extension);
		$user = Users_Record_Model::getCurrentUserModel();
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$query = 'SELECT * FROM '.self::basetable.' WHERE extensiontabid=?';
		$params = array($tabid);
		if($module) {
			$query .= ' AND module=?';
			$params[] = $module;
		}

		if($extension == 'Google') {
			$query .= ' AND userid=?';
			$params[] = $user->getId();
		}

		$query .= " ORDER BY sync_datetime DESC LIMIT $startIndex,".($pageLimit+1);

		$result = $adb->pquery($query, $params);
		$syncCounts = array();

		if($adb->num_rows($result)) {
			for($i=0;$i<$adb->num_rows($result);$i++) {
				$syncCounts[] = $adb->query_result_rowdata($result, $i);
			}
		}

		return $syncCounts;
	}

	/**
	 * Function get the total number of syncs
	 * @param <string> $extension
	 * @param <string> $module
	 * @return <int> $syncCount
	 */

	static function getTotalSyncCount($extension, $module = false) {
		global $adb;
		$user = Users_Record_Model::getCurrentUserModel();
		$tabid = getTabid($extension);

		$query = 'SELECT count(*) as count FROM '.self::basetable.' WHERE extensiontabid=?';
		$params = array($tabid);
		if($module) {
			$query .= ' AND module=?';
			$params[] = $module;
		}

		if($extension == 'Google') {
			$query .= ' AND userid=?';
			$params[] = $user->getId();
		}

		$result = $adb->pquery($query, $params);

		$syncCount = 0;
		if($adb->num_rows($result)) {
			$syncCount = $adb->query_result($result, 0, 'count');
		}

		return $syncCount;
	}

	/**
	 * Function to get details of sync for a logid
	 * @global type $adb
	 * @param type $logId
	 * @param type $pagingModel
	 * @return $syncRecordDetails
	 */
	static function getSyncCountDetails($logId) {
		global $adb;
		$syncRecordDetails = array(); 

		$query = 'SELECT * from '.self::detailstable.' WHERE id=?';
		$result = $adb->pquery($query, array($logId));

		if($adb->num_rows($result) > 0) {
			$rowdata = $adb->query_result_rowdata($result, 0);
			$syncRecordDetails = $rowdata;
		}

		return $syncRecordDetails;
	}

	/**
	 * Fuction to get sync count and details from sync record list
	 * @param type $syncRecords
	 * @return type
	 */
	function getSyncRecordsDetails($syncRecords) {
		$a = $b = $c = $d = $i = $j = $k = $l = 0;
		$recordCount = array('vtiger'	=> array('update' => 0, 'create' => 0, 'delete' => 0, 'skipped' => 0), 
							 'app'		=> array('update' => 0, 'create' => 0, 'delete' => 0, 'skipped' => 0));
		$recordDetails = array(	'vt_record'	=> array('update' => array(),'create' => array(),'delete' => array(), 'skipped' => array()),
								'app_record'=> array('update' => array(),'create' => array(),'delete' => array(), 'skipped' => array()));
		$extensionModule = $syncRecords['ExtensionModule'];

		foreach ($syncRecords as $key => $records) {
			if ($key == 'push') {
				foreach ($records as $record) {
					foreach ($record as $type => $data) {
						$recordInfo = $data->getData();
						if ($type == 'source') {
							if ($record['source']) {
								$source = $record['source']->getData();
								$recordInfo['id'] = $source['id'];
							}
							switch($data->getMode()) {
								case WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE	:	$recordCount['vtiger']['update']++;
																					$recordDetails['vt_record']['update'][$i++] = $recordInfo['id'];
																					break;
								case WSAPP_SyncRecordModel::WSAPP_CREATE_MODE	:	$recordCount['vtiger']['create']++;
																					$recordDetails['vt_record']['create'][$j++] = $recordInfo['id'];
																					break;
								case WSAPP_SyncRecordModel::WSAPP_DELETE_MODE	:	$recordCount['vtiger']['delete']++;
																					$recordDetails['vt_record']['delete'][$k++] = $source['_serverid'];
																					break;
								case 'skipped'									:	if(empty($recordInfo['messageidentifier'])) {
																						$moduleModel = Vtiger_Module_Model::getInstance($extensionModule);
																						$nameFields = $moduleModel->getNameFields();
																						foreach($nameFields as $nameField) {
																							$recordName = $recordInfo['record'][$nameField].' ';
																						}
																						$recordName = trim($recordName);
																					}else {
																						$recordName = $recordInfo['messageidentifier'];
																					}

																					$recordCount['vtiger']['skipped']++;
																					$recordDetails['vt_record']['skipped'][$l++] = array($recordName => $recordInfo['message']);
							}
						}
					}
				}
			} else if ($key == 'pull') {
				foreach ($records as $type => $record) {
					foreach ($record as $type => $data) {
						$recordInfo = $data->getData();
						if ($type == 'target') {
							if ($record['source']) {
								$source = $record['source']->getData();
								$recordInfo['id'] = $source['id'];
							}
							switch($data->getMode()) {
								case WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE	:	$recordCount['app']['update']++;
																					$recordDetails['app_record']['update'][$a++] = $recordInfo['id'];
																					break;
								case WSAPP_SyncRecordModel::WSAPP_CREATE_MODE	:	$recordCount['app']['create']++;
																					$recordDetails['app_record']['create'][$b++] = $recordInfo['id'];
																					break;
								case WSAPP_SyncRecordModel::WSAPP_DELETE_MODE	:	$recordCount['app']['delete']++;
																					$recordDetails['app_record']['delete'][$c++] = $source['_serverid'];
																					break;
								case 'skipped'									:	if(empty($recordInfo['messageidentifier'])) {
																						$entitydata = $recordInfo['entity']->getData();
																						$recordName = $entitydata['id'];
																					}else {
																						$recordName = $recordInfo['messageidentifier'];
																					}

																					$recordCount['app']['skipped']++;
																					$recordDetails['app_record']['skipped'][$d++] = array($recordName => $recordInfo['message']);
							}
						}
					}
				}
			}
		}
		return array($recordCount, $recordDetails, $extensionModule);
	}

	/**
	 * Function to purge all the log entries for an Entension every 6 months
	 * @global type $adb
	 * @param type $extensiontabid
	 */
	static function purge($extensiontabid) {
		global $adb;
		$currentdate = date('Y-m-d H:i:s');

		$query = 'SELECT sync_datetime FROM '.self::basetable.' WHERE extensiontabid=? ORDER BY sync_datetime DESC';
		$result = $adb->pquery($query, array($extensiontabid));

		if($adb->num_rows($result)) {
			$syncdate = $adb->query_result($result, 0, 'sync_datetime');
			$currentDatetime = new DateTime($currentdate);
			$syncDatetime = new DateTime($syncdate);
			$dateDiff = $currentDatetime->diff($syncDatetime);

			if($dateDiff->m >= 6) {
				$query = 'DELETE FROM '.self::basetable.' WHERE extensiontabid=?';
				$adb->pquery($query, array($extensiontabid));
			}
		}
	}

	/**
	 * Function to get the module name from logid
	 * @param <integer> $logId
	 * @return <string> $module
	 */
	static function getModuleFromLogId($logId) {
		global $adb;
		$query = 'SELECT module FROM '.self::basetable.' WHERE id=?';
		$result = $adb->pquery($query, array($logId));

		if($adb->num_rows($result) > 0) {
			$module = $adb->query_result($result, 0, 'module');
		}
		return $module;
	}
}