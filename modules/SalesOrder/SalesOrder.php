<?php
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************
 * $Header$
 * Description:  Defines the Account SugarBean Account entity with the necessary
 * methods and variables.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
class SalesOrder extends CRMEntity {
	var $log;
	var $db;

	var $table_name = "vtiger_salesorder";
	var $table_index= 'salesorderid';
	var $tab_name = Array('vtiger_crmentity','vtiger_salesorder','vtiger_sobillads','vtiger_soshipads','vtiger_salesordercf','vtiger_invoice_recurring_info','vtiger_inventoryproductrel');
	var $tab_name_index = Array('vtiger_crmentity'=>'crmid','vtiger_salesorder'=>'salesorderid','vtiger_sobillads'=>'sobilladdressid','vtiger_soshipads'=>'soshipaddressid','vtiger_salesordercf'=>'salesorderid','vtiger_invoice_recurring_info'=>'salesorderid','vtiger_inventoryproductrel'=>'id');
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_salesordercf', 'salesorderid');
	var $entity_table = "vtiger_crmentity";

	var $billadr_table = "vtiger_sobillads";

	var $object_name = "SalesOrder";

	var $new_schema = true;

	var $update_product_array = Array();

	var $column_fields = Array();

	var $sortby_fields = Array('subject','smownerid','accountname','lastname');

	// This is used to retrieve related vtiger_fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'smownerid', 'opportunity_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id' );

	// This is the list of vtiger_fields that are in the lists.
	var $list_fields = Array(
				// Module Sequence Numbering
				//'Order No'=>Array('crmentity'=>'crmid'),
				'Order No'=>Array('salesorder','salesorder_no'),
				// END
				'Subject'=>Array('salesorder'=>'subject'),
				'Account Name'=>Array('account'=>'accountid'),
				'Quote Name'=>Array('quotes'=>'quoteid'),
				'Total'=>Array('salesorder'=>'total'),
				'Assigned To'=>Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
				        'Order No'=>'salesorder_no',
				        'Subject'=>'subject',
				        'Account Name'=>'account_id',
				        'Quote Name'=>'quote_id',
					'Total'=>'hdnGrandTotal',
				        'Assigned To'=>'assigned_user_id'
				      );
	var $list_link_field= 'subject';

	var $search_fields = Array(
				'Order No'=>Array('salesorder'=>'salesorder_no'),
				'Subject'=>Array('salesorder'=>'subject'),
				'Account Name'=>Array('account'=>'accountid'),
				'Quote Name'=>Array('salesorder'=>'quoteid')
				);

	var $search_fields_name = Array(
					'Order No'=>'salesorder_no',
				        'Subject'=>'subject',
				        'Account Name'=>'account_id',
				        'Quote Name'=>'quote_id'
				      );

	// This is the list of vtiger_fields that are required.
	var $required_fields =  array("accountname"=>1);

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'subject';
	var $default_sort_order = 'ASC';
	//var $groupTable = Array('vtiger_sogrouprelation','salesorderid');

	var $mandatory_fields = Array('subject','createdtime' ,'modifiedtime', 'assigned_user_id','quantity', 'listprice', 'productid');

	// For Alphabetical search
	var $def_basicsearch_col = 'subject';

	// For workflows update field tasks is deleted all the lineitems.
	var $isLineItemUpdate = true;

	/** Constructor Function for SalesOrder class
	 *  This function creates an instance of Logger class using getLogger method
	 *  creates an instance for PearDatabase class and get values for column_fields array of SalesOrder class.
	 */
        function __construct() {
            $this->log =Logger::getLogger('SalesOrder');
            $this->db = PearDatabase::getInstance();
            $this->column_fields = getColumnFields('SalesOrder');
        }
	function SalesOrder() {
            self::__construct();
	}

	function save_module($module)
	{
		/* $_REQUEST['REQUEST_FROM_WS'] is set from webservices script.
		 * Depending on $_REQUEST['totalProductCount'] value inserting line items into DB.
		 * This should be done by webservices, not be normal save of Inventory record.
		 * So unsetting the value $_REQUEST['totalProductCount'] through check point
		 */
		if (isset($_REQUEST['REQUEST_FROM_WS']) && $_REQUEST['REQUEST_FROM_WS']) {
			unset($_REQUEST['totalProductCount']);
		}


		//in ajax save we should not call this function, because this will delete all the existing product values
		if($_REQUEST['action'] != 'SalesOrderAjax' && $_REQUEST['ajxaction'] != 'DETAILVIEW'
				&& $_REQUEST['action'] != 'MassEditSave' && $_REQUEST['action'] != 'ProcessDuplicates'
				&& $_REQUEST['action'] != 'SaveAjax' && $this->isLineItemUpdate != false) {
			//Based on the total Number of rows we will save the product relationship with this entity
			saveInventoryProductDetails($this, 'SalesOrder');
		}

		// Update the currency id and the conversion rate for the sales order
		$update_query = "update vtiger_salesorder set currency_id=?, conversion_rate=? where salesorderid=?";
		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id);
		$this->db->pquery($update_query, $update_params);
	}

	/** Function to get activities associated with the Sales Order
	 *  This function accepts the id as arguments and execute the MySQL query using the id
	 *  and sends the query and the id as arguments to renderRelatedActivities() method
	 */
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
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name', ), 'Users');
		$query = "SELECT case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name,vtiger_contactdetails.lastname, vtiger_contactdetails.firstname, vtiger_contactdetails.contactid, vtiger_activity.*,vtiger_seactivityrel.crmid as parent_id,vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime from vtiger_activity inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid= vtiger_activity.activityid left join vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid where vtiger_seactivityrel.crmid=".$id." and activitytype='Task' and vtiger_crmentity.deleted=0 and (vtiger_activity.status is not NULL and vtiger_activity.status != 'Completed') and (vtiger_activity.status is not NULL and vtiger_activity.status !='Deferred')";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_activities method ...");
		return $return_value;
	}

	/** Function to get the activities history associated with the Sales Order
	 *  This function accepts the id as arguments and execute the MySQL query using the id
	 *  and sends the query and the id as arguments to renderRelatedHistory() method
	 */
	function get_history($id)
	{
		global $log;
		$log->debug("Entering get_history(".$id.") method ...");
		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name', ), 'Users');
		$query = "SELECT vtiger_contactdetails.lastname, vtiger_contactdetails.firstname,
			vtiger_contactdetails.contactid,vtiger_activity.*, vtiger_seactivityrel.*,
			vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime,
			vtiger_crmentity.createdtime, vtiger_crmentity.description, case when
			(vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname
			end as user_name from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid= vtiger_activity.activityid
				left join vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
                                left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
			where activitytype='Task'
				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred')
				and vtiger_seactivityrel.crmid=".$id."
                                and vtiger_crmentity.deleted = 0";
		//Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

		$log->debug("Exiting get_history method ...");
		return getHistory('SalesOrder',$query,$id);
	}



	/** Function to get the invoices associated with the Sales Order
	 *  This function accepts the id as arguments and execute the MySQL query using the id
	 *  and sends the query and the id as arguments to renderRelatedInvoices() method.
	 */
	function get_invoices($id)
	{
		global $log,$singlepane_view;
		$log->debug("Entering get_invoices(".$id.") method ...");
		require_once('modules/Invoice/Invoice.php');

		$focus = new Invoice();

		$button = '';
		if($singlepane_view == 'true')
			$returnset = '&return_module=SalesOrder&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module=SalesOrder&return_action=CallRelatedList&return_id='.$id;

		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name', ), 'Users');
		$query = "select vtiger_crmentity.*, vtiger_invoice.*, vtiger_account.accountname,
			vtiger_salesorder.subject as salessubject, case when
			(vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname
			end as user_name from vtiger_invoice
			inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_invoice.invoiceid
			left outer join vtiger_account on vtiger_account.accountid=vtiger_invoice.accountid
			inner join vtiger_salesorder on vtiger_salesorder.salesorderid=vtiger_invoice.salesorderid
            LEFT JOIN vtiger_invoicecf ON vtiger_invoicecf.invoiceid = vtiger_invoice.invoiceid
			LEFT JOIN vtiger_invoicebillads ON vtiger_invoicebillads.invoicebilladdressid = vtiger_invoice.invoiceid
			LEFT JOIN vtiger_invoiceshipads ON vtiger_invoiceshipads.invoiceshipaddressid = vtiger_invoice.invoiceid
			left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
			left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
			where vtiger_crmentity.deleted=0 and vtiger_salesorder.salesorderid=".$id;

		$log->debug("Exiting get_invoices method ...");
		return GetRelatedList('SalesOrder','Invoice',$focus,$query,$button,$returnset);

	}

	/**	Function used to get the Status history of the Sales Order
	 *	@param $id - salesorder id
	 *	@return $return_data - array with header and the entries in format Array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are arrays which contains header values and all column values of all entries
	 */
	function get_sostatushistory($id)
	{
		global $log;
		$log->debug("Entering get_sostatushistory(".$id.") method ...");

		global $adb;
		global $mod_strings;
		global $app_strings;

		$query = 'select vtiger_sostatushistory.*, vtiger_salesorder.salesorder_no from vtiger_sostatushistory inner join vtiger_salesorder on vtiger_salesorder.salesorderid = vtiger_sostatushistory.salesorderid inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_salesorder.salesorderid where vtiger_crmentity.deleted = 0 and vtiger_salesorder.salesorderid = ?';
		$result=$adb->pquery($query, array($id));
		$noofrows = $adb->num_rows($result);

		$header[] = $app_strings['Order No'];
		$header[] = $app_strings['LBL_ACCOUNT_NAME'];
		$header[] = $app_strings['LBL_AMOUNT'];
		$header[] = $app_strings['LBL_SO_STATUS'];
		$header[] = $app_strings['LBL_LAST_MODIFIED'];

		//Getting the field permission for the current user. 1 - Not Accessible, 0 - Accessible
		//Account Name , Total are mandatory fields. So no need to do security check to these fields.
		global $current_user;

		//If field is accessible then getFieldVisibilityPermission function will return 0 else return 1
		$sostatus_access = (getFieldVisibilityPermission('SalesOrder', $current_user->id, 'sostatus') != '0')? 1 : 0;
		$picklistarray = getAccessPickListValues('SalesOrder');

		$sostatus_array = ($sostatus_access != 1)? $picklistarray['sostatus']: array();
		//- ==> picklist field is not permitted in profile
		//Not Accessible - picklist is permitted in profile but picklist value is not permitted
		$error_msg = ($sostatus_access != 1)? 'Not Accessible': '-';

		while($row = $adb->fetch_array($result))
		{
			$entries = Array();

			// Module Sequence Numbering
			//$entries[] = $row['salesorderid'];
			$entries[] = $row['salesorder_no'];
			// END
			$entries[] = $row['accountname'];
			$entries[] = $row['total'];
			$entries[] = (in_array($row['sostatus'], $sostatus_array))? $row['sostatus']: $error_msg;
			$date = new DateTimeField($row['lastmodified']);
			$entries[] = $date->getDisplayDateTimeValue();

			$entries_list[] = $entries;
		}

		$return_data = Array('header'=>$header,'entries'=>$entries_list);

	 	$log->debug("Exiting get_sostatushistory method ...");

		return $return_data;
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsSecQuery($module,$secmodule,$queryPlanner, $reportid = false){
		$matrix = $queryPlanner->newDependencyMatrix();
		$matrix->setDependency('vtiger_crmentitySalesOrder', array('vtiger_usersSalesOrder', 'vtiger_groupsSalesOrder', 'vtiger_lastModifiedBySalesOrder'));
		$matrix->setDependency('vtiger_inventoryproductrelSalesOrder', array('vtiger_productsSalesOrder', 'vtiger_serviceSalesOrder'));
		if (!$queryPlanner->requireTable('vtiger_salesorder', $matrix)) {
			return '';
		}
        $matrix->setDependency('vtiger_salesorder',array('vtiger_crmentitySalesOrder', "vtiger_currency_info$secmodule",
				'vtiger_salesordercf', 'vtiger_potentialRelSalesOrder', 'vtiger_sobillads','vtiger_soshipads',
				'vtiger_inventoryproductrelSalesOrder', 'vtiger_contactdetailsSalesOrder', 'vtiger_accountSalesOrder',
				'vtiger_invoice_recurring_info','vtiger_quotesSalesOrder'));


		$query = $this->getRelationQuery($module,$secmodule,"vtiger_salesorder","salesorderid", $queryPlanner, $reportid);
		if ($queryPlanner->requireTable("vtiger_crmentitySalesOrder",$matrix)){
			$query .= " left join vtiger_crmentity as vtiger_crmentitySalesOrder on vtiger_crmentitySalesOrder.crmid=vtiger_salesorder.salesorderid and vtiger_crmentitySalesOrder.deleted=0";
		}
		if ($queryPlanner->requireTable("vtiger_salesordercf")){
			$query .= " left join vtiger_salesordercf on vtiger_salesorder.salesorderid = vtiger_salesordercf.salesorderid";
		}
		if ($queryPlanner->requireTable("vtiger_sobillads")){
			$query .= " left join vtiger_sobillads on vtiger_salesorder.salesorderid=vtiger_sobillads.sobilladdressid";
		}
		if ($queryPlanner->requireTable("vtiger_soshipads")){
			$query .= " left join vtiger_soshipads on vtiger_salesorder.salesorderid=vtiger_soshipads.soshipaddressid";
		}
		if ($queryPlanner->requireTable("vtiger_currency_info$secmodule")){
			$query .= " left join vtiger_currency_info as vtiger_currency_info$secmodule on vtiger_currency_info$secmodule.id = vtiger_salesorder.currency_id";
		}
		if ($queryPlanner->requireTable("vtiger_inventoryproductrelSalesOrder", $matrix)){
		}
		if ($queryPlanner->requireTable("vtiger_productsSalesOrder")){
			$query .= " left join vtiger_products as vtiger_productsSalesOrder on vtiger_productsSalesOrder.productid = vtiger_inventoryproductreltmpSalesOrder.productid";
		}
		if ($queryPlanner->requireTable("vtiger_serviceSalesOrder")){
			$query .= " left join vtiger_service as vtiger_serviceSalesOrder on vtiger_serviceSalesOrder.serviceid = vtiger_inventoryproductreltmpSalesOrder.productid";
		}
		if ($queryPlanner->requireTable("vtiger_groupsSalesOrder")){
			$query .= " left join vtiger_groups as vtiger_groupsSalesOrder on vtiger_groupsSalesOrder.groupid = vtiger_crmentitySalesOrder.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_usersSalesOrder")){
			$query .= " left join vtiger_users as vtiger_usersSalesOrder on vtiger_usersSalesOrder.id = vtiger_crmentitySalesOrder.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_potentialRelSalesOrder")){
			$query .= " left join vtiger_potential as vtiger_potentialRelSalesOrder on vtiger_potentialRelSalesOrder.potentialid = vtiger_salesorder.potentialid";
		}
		if ($queryPlanner->requireTable("vtiger_contactdetailsSalesOrder")){
			$query .= " left join vtiger_contactdetails as vtiger_contactdetailsSalesOrder on vtiger_salesorder.contactid = vtiger_contactdetailsSalesOrder.contactid";
		}
		if ($queryPlanner->requireTable("vtiger_invoice_recurring_info")){
			$query .= " left join vtiger_invoice_recurring_info on vtiger_salesorder.salesorderid = vtiger_invoice_recurring_info.salesorderid";
		}
		if ($queryPlanner->requireTable("vtiger_quotesSalesOrder")){
			$query .= " left join vtiger_quotes as vtiger_quotesSalesOrder on vtiger_salesorder.quoteid = vtiger_quotesSalesOrder.quoteid";
		}
		if ($queryPlanner->requireTable("vtiger_accountSalesOrder")){
			$query .= " left join vtiger_account as vtiger_accountSalesOrder on vtiger_accountSalesOrder.accountid = vtiger_salesorder.accountid";
		}
		if ($queryPlanner->requireTable("vtiger_lastModifiedBySalesOrder")){
			$query .= " left join vtiger_users as vtiger_lastModifiedBySalesOrder on vtiger_lastModifiedBySalesOrder.id = vtiger_crmentitySalesOrder.modifiedby ";
		}
		if ($queryPlanner->requireTable("vtiger_createdbySalesOrder")){
			$query .= " left join vtiger_users as vtiger_createdbySalesOrder on vtiger_createdbySalesOrder.id = vtiger_crmentitySalesOrder.smcreatorid ";
		}

		//if secondary modules custom reference field is selected
        $query .= parent::getReportsUiType10Query($secmodule, $queryPlanner);

		return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		$rel_tables = array (
			"Calendar" =>array("vtiger_seactivityrel"=>array("crmid","activityid"),"vtiger_salesorder"=>"salesorderid"),
			"Invoice" =>array("vtiger_invoice"=>array("salesorderid","invoiceid"),"vtiger_salesorder"=>"salesorderid"),
			"Documents" => array("vtiger_senotesrel"=>array("crmid","notesid"),"vtiger_salesorder"=>"salesorderid"),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Accounts') {
			$this->trash('SalesOrder',$id);
		}
		elseif($return_module == 'Quotes') {
			$relation_query = 'UPDATE vtiger_salesorder SET quoteid=? WHERE salesorderid=?';
			$this->db->pquery($relation_query, array(null, $id));
		}
		elseif($return_module == 'Potentials') {
			$relation_query = 'UPDATE vtiger_salesorder SET potentialid=? WHERE salesorderid=?';
			$this->db->pquery($relation_query, array(null, $id));
		}
		elseif($return_module == 'Contacts') {
			$relation_query = 'UPDATE vtiger_salesorder SET contactid=? WHERE salesorderid=?';
			$this->db->pquery($relation_query, array(null, $id));
		} elseif($return_module == 'Documents') {
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql, array($id, $return_id));
        } else {
			parent::unlinkRelationship($id, $return_module, $return_id);
		}
	}

	public function getJoinClause($tableName) {
		if ($tableName == 'vtiger_invoice_recurring_info') {
			return 'LEFT JOIN';
		}
		return parent::getJoinClause($tableName);
	}

	function insertIntoEntityTable($table_name, $module, $fileid = '')  {
		//Ignore relation table insertions while saving of the record
		if($table_name == 'vtiger_inventoryproductrel') {
			return;
		}
		parent::insertIntoEntityTable($table_name, $module, $fileid);
	}

	/*Function to create records in current module.
	**This function called while importing records to this module*/
	function createRecords($obj) {
		$createRecords = createRecords($obj);
		return $createRecords;
	}

	/*Function returns the record information which means whether the record is imported or not
	**This function called while importing records to this module*/
	function importRecord($obj, $inventoryFieldData, $lineItemDetails) {
		$entityInfo = importRecord($obj, $inventoryFieldData, $lineItemDetails);
		return $entityInfo;
	}

	/*Function to return the status count of imported records in current module.
	**This function called while importing records to this module*/
	function getImportStatusCount($obj) {
		$statusCount = getImportStatusCount($obj);
		return $statusCount;
	}

	function undoLastImport($obj, $user) {
		$undoLastImport = undoLastImport($obj, $user);
	}

	/** Function to export the lead records in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export SalesOrder Query.
	*/
	function create_export_query($where)
	{
		global $log;
		global $current_user;
		$log->debug("Entering create_export_query(".$where.") method ...");

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("SalesOrder", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);
		$fields_list .= getInventoryFieldsForExport($this->table_name);
		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name'=>'vtiger_users.first_name', ), 'Users');

		$query = "SELECT $fields_list FROM ".$this->entity_table."
				INNER JOIN vtiger_salesorder ON vtiger_salesorder.salesorderid = vtiger_crmentity.crmid
				LEFT JOIN vtiger_salesordercf ON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid
				LEFT JOIN vtiger_sobillads ON vtiger_sobillads.sobilladdressid = vtiger_salesorder.salesorderid
				LEFT JOIN vtiger_soshipads ON vtiger_soshipads.soshipaddressid = vtiger_salesorder.salesorderid
				LEFT JOIN vtiger_inventoryproductrel ON vtiger_inventoryproductrel.id = vtiger_salesorder.salesorderid
				LEFT JOIN vtiger_products ON vtiger_products.productid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_service ON vtiger_service.serviceid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_salesorder.contactid
				LEFT JOIN vtiger_invoice_recurring_info ON vtiger_invoice_recurring_info.salesorderid = vtiger_salesorder.salesorderid
				LEFT JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_salesorder.potentialid
				LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_salesorder.accountid
				LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_salesorder.currency_id
				LEFT JOIN vtiger_quotes ON vtiger_quotes.quoteid = vtiger_salesorder.quoteid
				LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";

		$query .= $this->getNonAdminAccessControlQuery('SalesOrder',$current_user);
		$where_auto = " vtiger_crmentity.deleted=0";

		if($where != "") {
			$query .= " where ($where) AND ".$where_auto;
		} else {
			$query .= " where ".$where_auto;
		}

		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

    /**
	 * Function which will give the basic query to find duplicates
	 * @param <String> $module
	 * @param <String> $tableColumns
	 * @param <String> $selectedColumns
	 * @param <Boolean> $ignoreEmpty
     * @param <Array> $requiredTables 
	 * @return string
	 */
	// Note : remove getDuplicatesQuery API once vtiger5 code is removed
    function getQueryForDuplicates($module, $tableColumns, $selectedColumns = '', $ignoreEmpty = false,$requiredTables = array()) {
		if(is_array($tableColumns)) {
			$tableColumnsString = implode(',', $tableColumns);
		}
        $selectClause = "SELECT " . $this->table_name . "." . $this->table_index . " AS recordid," . $tableColumnsString;

        // Select Custom Field Table Columns if present
        if (isset($this->customFieldTable))
            $query .= ", " . $this->customFieldTable[0] . ".* ";

        $fromClause = " FROM $this->table_name";

        $fromClause .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		if($this->tab_name) {
			foreach($this->tab_name as $tableName) {
				if($tableName != 'vtiger_crmentity' && $tableName != $this->table_name && $tableName != 'vtiger_inventoryproductrel' && in_array($tableName,$requiredTables)) {
                    if($tableName == 'vtiger_invoice_recurring_info') {
						$fromClause .= " LEFT JOIN " . $tableName . " ON " . $tableName . '.' . $this->tab_name_index[$tableName] .
							" = $this->table_name.$this->table_index";
					}elseif($this->tab_name_index[$tableName]) {
						$fromClause .= " INNER JOIN " . $tableName . " ON " . $tableName . '.' . $this->tab_name_index[$tableName] .
							" = $this->table_name.$this->table_index";
					}
				}
			}
		}
        $fromClause .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

        $whereClause = " WHERE vtiger_crmentity.deleted = 0";
        $whereClause .= $this->getListViewSecurityParameter($module);

		if($ignoreEmpty) {
			foreach($tableColumns as $tableColumn){
				$whereClause .= " AND ($tableColumn IS NOT NULL AND $tableColumn != '') ";
			}
		}

        if (isset($selectedColumns) && trim($selectedColumns) != '') {
            $sub_query = "SELECT $selectedColumns FROM $this->table_name AS t " .
                    " INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t." . $this->table_index;
            // Consider custom table join as well.
            if (isset($this->customFieldTable)) {
                $sub_query .= " LEFT JOIN " . $this->customFieldTable[0] . " tcf ON tcf." . $this->customFieldTable[1] . " = t.$this->table_index";
            }
            $sub_query .= " WHERE crm.deleted=0 GROUP BY $selectedColumns HAVING COUNT(*)>1";
        } else {
            $sub_query = "SELECT $tableColumnsString $fromClause $whereClause GROUP BY $tableColumnsString HAVING COUNT(*)>1";
        }

		$i = 1;
		foreach($tableColumns as $tableColumn){
			$tableInfo = explode('.', $tableColumn);
			$duplicateCheckClause .= " ifnull($tableColumn,'null') = ifnull(temp.$tableInfo[1],'null')";
			if (php7_count($tableColumns) != $i++) $duplicateCheckClause .= " AND ";
		}

        $query = $selectClause . $fromClause .
                " LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=" . $this->table_name . "." . $this->table_index .
                " INNER JOIN (" . $sub_query . ") AS temp ON " . $duplicateCheckClause .
                $whereClause .
                " ORDER BY $tableColumnsString," . $this->table_name . "." . $this->table_index . " ASC";
        return $query;
    }

	/**
	 * Function to get importable mandatory fields
	 * By default some fields like Quantity, List Price is not mandaroty for Invertory modules but
	 * import fails if those fields are not mapped during import.
	 */
	function getMandatoryImportableFields() {
		return getInventoryImportableMandatoryFeilds($this->moduleName);
	}
}

?>
