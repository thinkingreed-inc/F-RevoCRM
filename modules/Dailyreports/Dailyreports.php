<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
//require_once('data/CRMEntity.php');
//require_once('data/Tracker.php');
include_once('config.php');
require_once('include/logging.php');
//require_once('modules/Calendar/Activity.php');
require_once('user_privileges/default_module_view.php');
require_once('include/utils/CommonUtils.php');

class Dailyreports extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name = 'vtiger_dailyreports';
	var $table_index= 'dailyreportsid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_dailyreportscf', 'dailyreportsid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_dailyreports', 'vtiger_dailyreportscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
			'vtiger_crmentity' => 'crmid',
			'vtiger_dailyreports'   => 'dailyreportsid',
			'vtiger_dailyreportscf' => 'dailyreportsid'
			);

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'DailyreportsName' => Array('dailyreports', 'dailyreportsname'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'DailyreportsName' => 'dailyreportsname',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'DailyreportsName';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'DailyreportsName' => Array('dailyreports', 'dailyreportsname'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'DailyreportsName' => 'dailyreportsname',
		'Assigned To' => 'assigned_user_id'
	);

	// For Popup window record selection
	var $popup_fields = Array('DailyreportsName');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array('reportsdate','reports_to_id');

	// For Alphabetical search
	var $def_basicsearch_col = 'DailyreportsName';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'DailyreportsName';

	// Required Information for enabling Import feature
	var $required_fields = Array('dailyreporstname'=>1);

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'reportsdate';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'DailyreportsName', 'reportsdate');
	
	function __construct() {
		global $log, $currentModule;
		$this->column_fields = getColumnFields($currentModule);
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function getSortOrder() {
		global $currentModule;

		$sortorder = $this->default_sort_order;
		if($_REQUEST['sorder']) $sortorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		else if($_SESSION[$currentModule.'_Sort_Order']) 
			$sortorder = $_SESSION[$currentModule.'_Sort_Order'];

		return $sortorder;
	}

	function getOrderBy() {
		global $currentModule;
		
		$use_default_order_by = '';		
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		
		$orderby = $use_default_order_by;
		if($_REQUEST['order_by']) $orderby = $this->db->sql_escape_string($_REQUEST['order_by']);
		else if($_SESSION[$currentModule.'_Order_By'])
			$orderby = $_SESSION[$currentModule.'_Order_By'];
		return $orderby;
	}

	function save_module($module) {
		//Inserting into Dailyreports Comment Table
	}


	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb;
		
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug("Entering get_activities(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/Activity.php");
		$other = new Activity();
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == 'true')
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		$button .= '<input type="hidden" name="activity_mode">';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				if(getFieldVisibilityPermission('Calendar',$current_user->id,'parent_id', 'readwrite') == '0') {
					$button .= "<input title='".getTranslatedString('LBL_NEW'). " ". getTranslatedString('LBL_TODO', $related_module) ."' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\";this.form.return_module.value=\"$this_module\";this.form.activity_mode.value=\"Task\";' type='submit' name='button'" .
						" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_TODO', $related_module) ."'>&nbsp;";
				}
				if(getFieldVisibilityPermission('Events',$current_user->id,'parent_id', 'readwrite') == '0') {
					$button .= "<input title='".getTranslatedString('LBL_NEW'). " ". getTranslatedString('LBL_TODO', $related_module) ."' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\";this.form.return_module.value=\"$this_module\";this.form.activity_mode.value=\"Events\";' type='submit' name='button'" .
						" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_EVENT', $related_module) ."'>";
				}
			}
		}

		$record = Vtiger_DetailView_Model::getInstance('Dailyreports', $id);
		$recordModel = $record->getRecord();
		$user        = $recordModel->get('assigned_user_id');
		$reportsterm = $recordModel->get('reportsterm');
		$reportsdate = $recordModel->get('ReportsDate');

        $date_arr = Array(
            'min'   => '', 
            'hour'  => '', 
            'day'   => '', 
            'month' => '',
            'year'  => '', 
        );

		$sh = '00';
		$sm = '00';
		$eh = '23';
		$em = '59';
		$report_ts = strtotime($reportsdate);
		$dt = new vt_DateTime($date_arr, true);

		if (isset($reportsterm) && $reportsterm == 'Week') {
				$dt->setDateTime($report_ts);
				$new_dt = $dt->getThisweekDaysbyIndex(1); // 1 eq Monday
				$start_date = $new_dt->get_formatted_date();
				$dt->setDateTime(strtotime($start_date));
				$startDate = new DateTimeField($dt->year . "-" . $dt->z_month . "-" . $dt->z_day." $sh:$sm");

				$dt->setDateTime($report_ts);
				$new_dt = $dt->getThisweekDaysbyIndex(7); // 7 eq Sunday
				$end_date = $new_dt->get_formatted_date();
				$dt->setDateTime(strtotime($end_date));
				$endDate = new DateTimeField($dt->year . "-" . $dt->z_month . "-" . $dt->z_day." $eh:$em");
		} else {
				$dt->setDateTime($report_ts);
				$startDate = new DateTimeField($dt->year . "-" . $dt->z_month . "-" . $dt->z_day." $sh:$sm");
				$endDate = new DateTimeField($dt->year . "-" . $dt->z_month . "-" . $dt->z_day." $eh:$em");
		}
		$beginning_week_date = $startDate->getDBInsertDateTimeValue();
		$ending_week_date = $endDate->getDBInsertDateTimeValue();

		$query = "SELECT vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.setype, vtiger_crmentity.description,
				vtiger_activity.*,
				vtiger_seactivityrel.crmid AS parent_id,
				CASE
					WHEN (vtiger_activity.activitytype = 'Task') THEN (vtiger_activity.status)
					ELSE (vtiger_activity.eventstatus)
				END AS status
				FROM vtiger_activity
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
					left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
					left join vtiger_seactivityrel ON  vtiger_seactivityrel.activityid = vtiger_activity.activityid
				WHERE (vtiger_activity.activitytype != 'Emails')
				AND
				vtiger_crmentity.deleted = 0
				AND
					CAST(CONCAT(date_start,' ',time_start) AS DATETIME) >='" . $beginning_week_date ."'
				AND
					CAST(CONCAT(date_start,' ',time_start) AS DATETIME) <='" . $ending_week_date . "'";

		$query .= " AND vtiger_crmentity.smownerid = ".$user;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_activities method ...");
		return $return_value;
	}
	
	/** 
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }
	
	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
