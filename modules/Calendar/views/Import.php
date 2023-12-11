<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

vimport('~~/modules/Calendar/iCal/iCalendar_rfc2445.php');
vimport('~~/modules/Calendar/iCal/iCalendar_components.php');
vimport('~~/modules/Calendar/iCal/iCalendar_properties.php');
vimport('~~/modules/Calendar/iCal/iCalendar_parameters.php');
vimport('~~/modules/Calendar/iCal/ical-parser-class.php');
vimport('~~/modules/Calendar/iCalLastImport.php');

class Calendar_Import_View extends Vtiger_Import_View {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('importResult');
		$this->exposeMethod('undoIcalImport');
	}

	/**
	 * Function to show result of import
	 * @param Vtiger_Request $request
	 */
	public function importResult(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUserModel->getId();
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$request->set('type', 'ics');

		if (Import_Utils_Helper::validateFileUpload($request)) {
			$lastImport = new iCalLastImport();
			$lastImport->clearRecords($userId);

			$eventModule = 'Events';
			$todoModule = 'Calendar';

			$skipFields = array(
					$eventModule => array('duration_hours'),
					$todoModule => array('eventstatus')
			);

			$requiredFields = array();
			$modules = array($eventModule, $todoModule);
			$calendarModel = Vtiger_Module_Model::getInstance($moduleName);

			foreach($modules as $module) {
				$moduleRequiredFields = array_keys($calendarModel->getRequiredFields($module));
				$requiredFields[$module] = array_diff($moduleRequiredFields, $skipFields[$module]);
				$totalCount[$module] = 0;
				$skipCount[$module] = 0;
				$duplicatesCount[$module] = 0;
			}

			$ical = new iCal();
			$icalActivities = $ical->iCalReader("IMPORT_".$userId);
			$noOfActivities = php7_count($icalActivities);

			for($i=0; $i<$noOfActivities; $i++) {
				if($icalActivities[$i]['TYPE'] == 'VEVENT') {
					$activity = new iCalendar_event;
					$module = $eventModule;
				} else {
					$activity = new iCalendar_todo;
					$module = $todoModule;
				}

				$totalCount[$module]++;
				$activityFieldsList = $activity->generateArray($icalActivities[$i]);
				if (!array_key_exists('visibility', $activityFieldsList)) {
					$activityFieldsList['visibility'] = ' ';
				}
				if(array_key_exists('taskpriority',$activityFieldsList)) {
					$priorityMap = array('0'=>'Medium','1'=>'High','2'=>'Medium','3'=>'Low');
					$priorityval = $activityFieldsList['taskpriority'];
					if(array_key_exists($priorityval,$priorityMap))
						$activityFieldsList['taskpriority'] = $priorityMap[$priorityval];
				}
				if(array_key_exists('eventstatus',$activityFieldsList) && !isset($activityFieldsList['eventstatus'])) {
					$dueDate = date($activityFieldsList['due_date']);
					$currentDate = date('Y-m-d');
					$activityFieldsList['eventstatus'] = 'Planned';
					if(strtotime($dueDate) < strtotime($currentDate))
						$activityFieldsList['eventstatus'] = 'Held';
				}

				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
				$recordModel->setData($activityFieldsList);
				$recordModel->set('assigned_user_id', $userId);

				$skipRecord = false;
				foreach($requiredFields[$module] as $key) {
					$value = $recordModel->get($key);
					if(empty ($value)) {
						$skipCount[$module]++;
						$skipRecord = true;
						break;
					}
				}
				if($skipRecord === true) {
					continue;
				}
				try {
					$recordModel->save();

					$lastImport = new iCalLastImport();
					$lastImport->setFields(array('userid' => $userId, 'entitytype' => $todoModule, 'crmid' => $recordModel->getId()));
					$lastImport->save();

					if(!empty($icalActivities[$i]['VALARM'])) {
						$recordModel->setActivityReminder(0, '', '');
					}
				} catch (Exception $e) {
					$duplicatesCount[$module]++;
				}
			}

			$importedEvents = $totalCount[$eventModule] - $skipCount[$eventModule] - $duplicatesCount[$eventModule];
			$importedTasks = $totalCount[$todoModule] - $skipCount[$todoModule] - $duplicatesCount[$todoModule];

			$viewer->assign('SUCCESS_EVENTS', $importedEvents);
			$viewer->assign('SKIPPED_EVENTS', $skipCount[$eventModule]);
			$viewer->assign('DUPLICATE_EVENTS', $duplicatesCount[$eventModule]);

			$viewer->assign('SUCCESS_TASKS', $importedTasks);
			$viewer->assign('SKIPPED_TASKS', $skipCount[$todoModule]);
			$viewer->assign('DUPLICATE_TASKS', $duplicatesCount[$todoModule]);

		} else {
			$viewer->assign('ERROR_MESSAGE', $request->get('error_message'));
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEW', 'List');

		$viewer->view('ImportResult.tpl', $moduleName);
	}

	/**
	 * Function to show result of undo import
	 * @param Vtiger_Request $request
	 */
	public function undoIcalImport(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleName = $request->getModule();

		$lastImport = new iCalLastImport();
		$returnValue = $lastImport->undo($moduleName, $currentUserModel->getId());
		if(!empty($returnValue)) {
			$undoStatus = true;
		} else {
			$undoStatus = false;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEW', 'List');
		$viewer->assign('UNDO_STATUS', $undoStatus);

		$viewer->view('ImportFinalResult.tpl', $moduleName);
	}

}
