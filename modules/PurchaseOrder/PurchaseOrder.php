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
class PurchaseOrder extends CRMEntity {
	var $log;
	var $db;

	var $table_name = "vtiger_purchaseorder";
	var $table_index= 'purchaseorderid';
	var $tab_name = Array('vtiger_crmentity','vtiger_purchaseorder','vtiger_pobillads','vtiger_poshipads','vtiger_purchaseordercf','vtiger_inventoryproductrel');
	var $tab_name_index = Array('vtiger_crmentity'=>'crmid','vtiger_purchaseorder'=>'purchaseorderid','vtiger_pobillads'=>'pobilladdressid','vtiger_poshipads'=>'poshipaddressid','vtiger_purchaseordercf'=>'purchaseorderid','vtiger_inventoryproductrel'=>'id');
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_purchaseordercf', 'purchaseorderid');
	var $entity_table = "vtiger_crmentity";

	var $billadr_table = "vtiger_pobillads";

	var $column_fields = Array();

	var $sortby_fields = Array('subject','tracking_no','smownerid','lastname');

	// This is used to retrieve related vtiger_fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'smownerid', 'opportunity_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id' );

	// This is the list of vtiger_fields that are in the lists.
	var $list_fields = Array(
				//  Module Sequence Numbering
				//'Order No'=>Array('crmentity'=>'crmid'),
				'Order No'=>Array('purchaseorder'=>'purchaseorder_no'),
				// END
				'Subject'=>Array('purchaseorder'=>'subject'),
				'Vendor Name'=>Array('purchaseorder'=>'vendorid'),
				'Tracking Number'=>Array('purchaseorder'=> 'tracking_no'),
				'Total'=>Array('purchaseorder'=>'total'),
				'Assigned To'=>Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
				        'Order No'=>'purchaseorder_no',
				        'Subject'=>'subject',
				        'Vendor Name'=>'vendor_id',
					'Tracking Number'=>'tracking_no',
					'Total'=>'hdnGrandTotal',
				        'Assigned To'=>'assigned_user_id'
				      );
	var $list_link_field= 'subject';

	var $search_fields = Array(
				'Order No'=>Array('purchaseorder'=>'purchaseorder_no'),
				'Subject'=>Array('purchaseorder'=>'subject'),
				);

	var $search_fields_name = Array(
				        'Order No'=>'purchaseorder_no',
				        'Subject'=>'subject',
				      );
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('subject', 'vendor_id','createdtime' ,'modifiedtime', 'assigned_user_id', 'quantity', 'listprice', 'productid');

	// This is the list of vtiger_fields that are required.
	var $required_fields =  array("accountname"=>1);

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'subject';
	var $default_sort_order = 'ASC';

	// For Alphabetical search
	var $def_basicsearch_col = 'subject';

	// For workflows update field tasks is deleted all the lineitems.
	var $isLineItemUpdate = true;

	//var $groupTable = Array('vtiger_pogrouprelation','purchaseorderid');
	/** Constructor Function for Order class
	 *  This function creates an instance of Logger class using getLogger method
	 *  creates an instance for PearDatabase class and get values for column_fields array of Order class.
	 */
        function __construct() {
            $this->log =Logger::getLogger('PurchaseOrder');
            $this->db = PearDatabase::getInstance();
            $this->column_fields = getColumnFields('PurchaseOrder');
        }
	function PurchaseOrder() {
            self::__construct();
	}

	function save_module($module)
	{
		global $adb, $updateInventoryProductRel_deduct_stock;
		$updateInventoryProductRel_deduct_stock = false;

		$requestProductIdsList = $requestQuantitiesList = array();
		$totalNoOfProducts = $_REQUEST['totalProductCount'];
		for($i=1; $i<=$totalNoOfProducts; $i++) {
			$productId = $_REQUEST['hdnProductId'.$i];
			$requestProductIdsList[$productId] = $productId;
			//Checking same item more than once
			if(array_key_exists($productId, $requestQuantitiesList)) {
				$requestQuantitiesList[$productId] = $requestQuantitiesList[$productId] + $_REQUEST['qty'.$i];
				continue;
			}
			$requestQuantitiesList[$productId] = $_REQUEST['qty'.$i];
		}

		global $itemQuantitiesList, $isItemsRequest;
		$itemQuantitiesList = array();
		$statusValue = $this->column_fields['postatus'];

		if ($totalNoOfProducts) {
			$isItemsRequest = true;
		}

		if ($this->mode == '' && $statusValue === 'Received Shipment') {
			$itemQuantitiesList['new'] = $requestQuantitiesList;

		} else if ($this->mode != '' && in_array($statusValue, array('Received Shipment', 'Cancelled'))) {

			$productIdsList = $quantitiesList = array();
			$recordId = $this->id;
			$result = $adb->pquery("SELECT productid, quantity FROM vtiger_inventoryproductrel WHERE id = ?", array($recordId));
			$numOfRows = $adb->num_rows($result);
			for ($i=0; $i<$numOfRows; $i++) {
				$productId = $adb->query_result($result, $i, 'productid');
				$productIdsList[$productId] = $productId;
				if(array_key_exists($productId, $quantitiesList)) {
					$quantitiesList[$productId] = $quantitiesList[$productId] + $adb->query_result($result, $i, 'quantity');
					continue;
				}
				$qty = $adb->query_result($result, $i, 'quantity');
				$quantitiesList[$productId] = $qty;
				$subProductQtys = $this->getSubProductsQty($productId);
				if ($statusValue === 'Cancelled' && !empty($subProductQtys)) {
					foreach ($subProductQtys as $subProdId => $subProdQty) {
						$subProdQty = $subProdQty * $qty;
						if (array_key_exists($subProdId, $quantitiesList)) {
							$quantitiesList[$subProdId] = $quantitiesList[$subProdId] + $subProdQty;
							continue;
						}
						$quantitiesList[$subProdId] = $subProdQty;
					}
				}
			}
				
			if ($statusValue === 'Cancelled') {
				$itemQuantitiesList = $quantitiesList;
			} else {

				//Constructing quantities array for newly added line items
				$newProductIds = array_diff($requestProductIdsList, $productIdsList);
				if ($newProductIds) {
					$newQuantitiesList = array();
					foreach ($newProductIds as $productId) {
						$newQuantitiesList[$productId] = $requestQuantitiesList[$productId];
					}
					if ($newQuantitiesList) {
						$itemQuantitiesList['new'] = $newQuantitiesList;
					}
				}

				//Constructing quantities array for deleted line items
				$deletedProductIds = array_diff($productIdsList, $requestProductIdsList);
				if ($deletedProductIds && $totalNoOfProducts) {//$totalNoOfProducts is exist means its not ajax save
					$deletedQuantitiesList = array();
					foreach ($deletedProductIds as $productId) {
						//Checking same item more than once
						if(array_key_exists($productId, $deletedQuantitiesList)) {
							$deletedQuantitiesList[$productId] = $deletedQuantitiesList[$productId] + $quantitiesList[$productId];
							continue;
						}
						$deletedQuantitiesList[$productId] = $quantitiesList[$productId];
					}

					if ($deletedQuantitiesList) {
						$itemQuantitiesList['deleted'] = $deletedQuantitiesList;
					}
				}

				//Constructing quantities array for updated line items
				$updatedProductIds = array_intersect($productIdsList, $requestProductIdsList);
				if (!$totalNoOfProducts) {//$totalNoOfProducts is null then its ajax save
					$updatedProductIds = $productIdsList;
				}
				if ($updatedProductIds) {
					$updatedQuantitiesList = array();
					foreach ($updatedProductIds as $productId) {
						//Checking same item more than once
						if(array_key_exists($productId, $updatedQuantitiesList)) {
							$updatedQuantitiesList[$productId] = $updatedQuantitiesList[$productId] + $quantitiesList[$productId];
							continue;
						}
						
						$quantity = $quantitiesList[$productId];
						if ($totalNoOfProducts) {
							$quantity = $requestQuantitiesList[$productId] - $quantitiesList[$productId];
						}

						if ($quantity) {
							$updatedQuantitiesList[$productId] = $quantity;
						}
						//Check for subproducts
						$subProductQtys = $this->getSubProductsQty($productId);
						if (!empty($subProductQtys) && $quantity) {
							foreach ($subProductQtys as $subProdId => $subProductQty) {
								$subProductQty = $subProductQty * $quantity;
								if (array_key_exists($subProdId, $updatedQuantitiesList)) {
									$updatedQuantitiesList[$subProdId] = $updatedQuantitiesList[$subProdId] + ($subProductQty);
									continue;
								}
								$updatedQuantitiesList[$subProdId] = $subProductQty;
							}
						}
					}
					if ($updatedQuantitiesList) {
						$itemQuantitiesList['updated'] = $updatedQuantitiesList;
					}
				}
			}
		}

		/* $_REQUEST['REQUEST_FROM_WS'] is set from webservices script.
		 * Depending on $_REQUEST['totalProductCount'] value inserting line items into DB.
		 * This should be done by webservices, not be normal save of Inventory record.
		 * So unsetting the value $_REQUEST['totalProductCount'] through check point
		 */
		if (isset($_REQUEST['REQUEST_FROM_WS']) && $_REQUEST['REQUEST_FROM_WS']) {
			unset($_REQUEST['totalProductCount']);
		}

		//in ajax save we should not call this function, because this will delete all the existing product values
		if($_REQUEST['action'] != 'PurchaseOrderAjax' && $_REQUEST['ajxaction'] != 'DETAILVIEW'
				&& $_REQUEST['action'] != 'MassEditSave' && $_REQUEST['action'] != 'ProcessDuplicates'
				&& $_REQUEST['action'] != 'SaveAjax' && $this->isLineItemUpdate != false && $_REQUEST['action'] != 'FROM_WS') {

			//Based on the total Number of rows we will save the product relationship with this entity
			saveInventoryProductDetails($this, 'PurchaseOrder');
		}

		// Update the currency id and the conversion rate for the purchase order
		$update_query = "update vtiger_purchaseorder set currency_id=?, conversion_rate=? where purchaseorderid=?";
		$update_params = array($this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id);
		$adb->pquery($update_query, $update_params);
	}

	/** Function to get subproducts quantity for given product
	 *  This function accepts the productId as arguments and returns array of subproduct qty for given productId
	 */
	function getSubProductsQty($productId) {
		$subProductQtys = array();
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery("SELECT sequence_no FROM vtiger_inventoryproductrel WHERE id = ? and productid=?", array($this->id, $productId));
		$numOfRows = $adb->num_rows($result);
		if ($numOfRows > 0) {
			for ($i = 0; $i < $numOfRows; $i++) {
				$sequenceNo = $adb->query_result($result, $i, 'sequence_no');
				$subProdQuery = $adb->pquery("SELECT productid, quantity FROM vtiger_inventorysubproductrel WHERE id=? AND sequence_no=?", array($this->id, $sequenceNo));
				if ($adb->num_rows($subProdQuery) > 0) {
					for ($j = 0; $j < $adb->num_rows($subProdQuery); $j++) {
						$subProdId = $adb->query_result($subProdQuery, $j, 'productid');
						$subProdQty = $adb->query_result($subProdQuery, $j, 'quantity');
						$subProductQtys[$subProdId] = $subProdQty;
					}
				}
			}
		}
		return $subProductQtys;
	}

	/** Function to get activities associated with the Purchase Order
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
		$query = "SELECT case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name,vtiger_contactdetails.lastname, vtiger_contactdetails.firstname, vtiger_contactdetails.contactid,vtiger_activity.*,vtiger_seactivityrel.crmid as parent_id,vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime from vtiger_activity inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid= vtiger_activity.activityid left join vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid where vtiger_seactivityrel.crmid=".$id." and activitytype='Task' and vtiger_crmentity.deleted=0 and (vtiger_activity.status is not NULL && vtiger_activity.status != 'Completed') and (vtiger_activity.status is not NULL and vtiger_activity.status != 'Deferred') ";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_activities method ...");
		return $return_value;
	}

	/** Function to get the activities history associated with the Purchase Order
	 *  This function accepts the id as arguments and execute the MySQL query using the id
	 *  and sends the query and the id as arguments to renderRelatedHistory() method
	 */
	function get_history($id)
	{
		global $log;
		$log->debug("Entering get_history(".$id.") method ...");
		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name', ), 'Users');
		$query = "SELECT vtiger_contactdetails.lastname, vtiger_contactdetails.firstname,
			vtiger_contactdetails.contactid,vtiger_activity.* ,vtiger_seactivityrel.*,
			vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime,
			vtiger_crmentity.createdtime, vtiger_crmentity.description,case when
			(vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end
			as user_name from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid= vtiger_activity.activityid
				left join vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
                                left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
			where vtiger_activity.activitytype='Task'
				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred')
				and vtiger_seactivityrel.crmid=".$id."
                                and vtiger_crmentity.deleted = 0";
		//Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

        $returnValue = getHistory('PurchaseOrder',$query,$id);
		$log->debug("Exiting get_history method ...");
		return $returnValue;
	}


	/**	Function used to get the Status history of the Purchase Order
	 *	@param $id - purchaseorder id
	 *	@return $return_data - array with header and the entries in format Array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are arrays which contains header values and all column values of all entries
	 */
	function get_postatushistory($id)
	{
		global $log;
		$log->debug("Entering get_postatushistory(".$id.") method ...");

		global $adb;
		global $mod_strings;
		global $app_strings;

		$query = 'select vtiger_postatushistory.*, vtiger_purchaseorder.purchaseorder_no from vtiger_postatushistory inner join vtiger_purchaseorder on vtiger_purchaseorder.purchaseorderid = vtiger_postatushistory.purchaseorderid inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_purchaseorder.purchaseorderid where vtiger_crmentity.deleted = 0 and vtiger_purchaseorder.purchaseorderid = ?';
		$result=$adb->pquery($query, array($id));
		$noofrows = $adb->num_rows($result);

		$header[] = $app_strings['Order No'];
		$header[] = $app_strings['Vendor Name'];
		$header[] = $app_strings['LBL_AMOUNT'];
		$header[] = $app_strings['LBL_PO_STATUS'];
		$header[] = $app_strings['LBL_LAST_MODIFIED'];

		//Getting the field permission for the current user. 1 - Not Accessible, 0 - Accessible
		//Vendor, Total are mandatory fields. So no need to do security check to these fields.
		global $current_user;

		//If field is accessible then getFieldVisibilityPermission function will return 0 else return 1
		$postatus_access = (getFieldVisibilityPermission('PurchaseOrder', $current_user->id, 'postatus') != '0')? 1 : 0;
		$picklistarray = getAccessPickListValues('PurchaseOrder');

		$postatus_array = ($postatus_access != 1)? $picklistarray['postatus']: array();
		//- ==> picklist field is not permitted in profile
		//Not Accessible - picklist is permitted in profile but picklist value is not permitted
		$error_msg = ($postatus_access != 1)? 'Not Accessible': '-';

		while($row = $adb->fetch_array($result))
		{
			$entries = Array();

			//Module Sequence Numbering
			//$entries[] = $row['purchaseorderid'];
			$entries[] = $row['purchaseorder_no'];
			// END
			$entries[] = $row['vendorname'];
			$entries[] = $row['total'];
			$entries[] = (in_array($row['postatus'], $postatus_array))? $row['postatus']: $error_msg;
			$date = new DateTimeField($row['lastmodified']);
			$entries[] = $date->getDisplayDateTimeValue();

			$entries_list[] = $entries;
		}

		$return_data = Array('header'=>$header,'entries'=>$entries_list);

	 	$log->debug("Exiting get_postatushistory method ...");

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
		$matrix->setDependency('vtiger_crmentityPurchaseOrder', array('vtiger_usersPurchaseOrder', 'vtiger_groupsPurchaseOrder', 'vtiger_lastModifiedByPurchaseOrder'));
		$matrix->setDependency('vtiger_inventoryproductrelPurchaseOrder', array('vtiger_productsPurchaseOrder', 'vtiger_servicePurchaseOrder'));
		
		if (!$queryPlanner->requireTable('vtiger_purchaseorder', $matrix)) {
			return '';
		}
        $matrix->setDependency('vtiger_purchaseorder',array('vtiger_crmentityPurchaseOrder', "vtiger_currency_info$secmodule",
				'vtiger_purchaseordercf', 'vtiger_vendorRelPurchaseOrder', 'vtiger_pobillads',
				'vtiger_poshipads', 'vtiger_inventoryproductrelPurchaseOrder', 'vtiger_contactdetailsPurchaseOrder'));

		$query = $this->getRelationQuery($module,$secmodule,"vtiger_purchaseorder","purchaseorderid",$queryPlanner, $reportid);
		if ($queryPlanner->requireTable("vtiger_crmentityPurchaseOrder", $matrix)){
			$query .= " left join vtiger_crmentity as vtiger_crmentityPurchaseOrder on vtiger_crmentityPurchaseOrder.crmid=vtiger_purchaseorder.purchaseorderid and vtiger_crmentityPurchaseOrder.deleted=0";
		}
		if ($queryPlanner->requireTable("vtiger_purchaseordercf")){
			$query .= " left join vtiger_purchaseordercf on vtiger_purchaseorder.purchaseorderid = vtiger_purchaseordercf.purchaseorderid";
		}
		if ($queryPlanner->requireTable("vtiger_pobillads")){
			$query .= " left join vtiger_pobillads on vtiger_purchaseorder.purchaseorderid=vtiger_pobillads.pobilladdressid";
		}
		if ($queryPlanner->requireTable("vtiger_poshipads")){
			$query .= " left join vtiger_poshipads on vtiger_purchaseorder.purchaseorderid=vtiger_poshipads.poshipaddressid";
		}
		if ($queryPlanner->requireTable("vtiger_currency_info$secmodule")){
			$query .= " left join vtiger_currency_info as vtiger_currency_info$secmodule on vtiger_currency_info$secmodule.id = vtiger_purchaseorder.currency_id";
		}
		if ($queryPlanner->requireTable("vtiger_inventoryproductrelPurchaseOrder", $matrix)){
		}
		if ($queryPlanner->requireTable("vtiger_productsPurchaseOrder")){
			$query .= " left join vtiger_products as vtiger_productsPurchaseOrder on vtiger_productsPurchaseOrder.productid = vtiger_inventoryproductreltmpPurchaseOrder.productid";
		}
		if ($queryPlanner->requireTable("vtiger_servicePurchaseOrder")){
			$query .= " left join vtiger_service as vtiger_servicePurchaseOrder on vtiger_servicePurchaseOrder.serviceid = vtiger_inventoryproductreltmpPurchaseOrder.productid";
		}
		if ($queryPlanner->requireTable("vtiger_usersPurchaseOrder")){
			$query .= " left join vtiger_users as vtiger_usersPurchaseOrder on vtiger_usersPurchaseOrder.id = vtiger_crmentityPurchaseOrder.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_groupsPurchaseOrder")){
			$query .= " left join vtiger_groups as vtiger_groupsPurchaseOrder on vtiger_groupsPurchaseOrder.groupid = vtiger_crmentityPurchaseOrder.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_vendorRelPurchaseOrder")){
			$query .= " left join vtiger_vendor as vtiger_vendorRelPurchaseOrder on vtiger_vendorRelPurchaseOrder.vendorid = vtiger_purchaseorder.vendorid";
		}
		if ($queryPlanner->requireTable("vtiger_contactdetailsPurchaseOrder")){
			$query .= " left join vtiger_contactdetails as vtiger_contactdetailsPurchaseOrder on vtiger_contactdetailsPurchaseOrder.contactid = vtiger_purchaseorder.contactid";
		}
		if ($queryPlanner->requireTable("vtiger_lastModifiedByPurchaseOrder")){
			$query .= " left join vtiger_users as vtiger_lastModifiedByPurchaseOrder on vtiger_lastModifiedByPurchaseOrder.id = vtiger_crmentityPurchaseOrder.modifiedby ";
		}
        if ($queryPlanner->requireTable("vtiger_createdbyPurchaseOrder")){
			$query .= " left join vtiger_users as vtiger_createdbyPurchaseOrder on vtiger_createdbyPurchaseOrder.id = vtiger_crmentityPurchaseOrder.smcreatorid ";
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
			"Calendar" =>array("vtiger_seactivityrel"=>array("crmid","activityid"),"vtiger_purchaseorder"=>"purchaseorderid"),
			"Documents" => array("vtiger_senotesrel"=>array("crmid","notesid"),"vtiger_purchaseorder"=>"purchaseorderid"),
			"Contacts" => array("vtiger_purchaseorder"=>array("purchaseorderid","contactid")),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Vendors') {
			$sql_req ='UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid= ?';
			$this->db->pquery($sql_req, array($id));
			CRMEntity::updateBasicInformation(null, $id);
		} elseif($return_module == 'Contacts') {
			$sql_req ='UPDATE vtiger_purchaseorder SET contactid=? WHERE purchaseorderid = ?';
			$this->db->pquery($sql_req, array(null, $id));
		} elseif($return_module == 'Documents') {
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Accounts') {
			$sql ='UPDATE vtiger_purchaseorder SET accountid=? WHERE purchaseorderid=?';
			$this->db->pquery($sql, array(null, $id));
		} else {
			parent::unlinkRelationship($id, $return_module, $return_id);
		}
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
	* Returns Export PurchaseOrder Query.
	*/
	function create_export_query($where)
	{
		global $log;
		global $current_user;
		$log->debug("Entering create_export_query(".$where.") method ...");

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("PurchaseOrder", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);
		$fields_list .= getInventoryFieldsForExport($this->table_name);
		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name'=>'vtiger_users.first_name', ), 'Users');

		$query = "SELECT $fields_list FROM ".$this->entity_table."
				INNER JOIN vtiger_purchaseorder ON vtiger_purchaseorder.purchaseorderid = vtiger_crmentity.crmid
				LEFT JOIN vtiger_purchaseordercf ON vtiger_purchaseordercf.purchaseorderid = vtiger_purchaseorder.purchaseorderid
				LEFT JOIN vtiger_pobillads ON vtiger_pobillads.pobilladdressid = vtiger_purchaseorder.purchaseorderid
				LEFT JOIN vtiger_poshipads ON vtiger_poshipads.poshipaddressid = vtiger_purchaseorder.purchaseorderid
				LEFT JOIN vtiger_inventoryproductrel ON vtiger_inventoryproductrel.id = vtiger_purchaseorder.purchaseorderid
				LEFT JOIN vtiger_products ON vtiger_products.productid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_service ON vtiger_service.serviceid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_purchaseorder.contactid
				LEFT JOIN vtiger_vendor ON vtiger_vendor.vendorid = vtiger_purchaseorder.vendorid
				LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_purchaseorder.currency_id
				LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";

		$query .= $this->getNonAdminAccessControlQuery('PurchaseOrder',$current_user);
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
	 * Function to get importable mandatory fields
	 * By default some fields like Quantity, List Price is not mandaroty for Invertory modules but
	 * import fails if those fields are not mapped during import.
	 */
	function getMandatoryImportableFields() {
		return getInventoryImportableMandatoryFeilds($this->moduleName);
	}
}

?>