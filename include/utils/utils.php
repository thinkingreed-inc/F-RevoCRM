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
 * Description:  Includes generic helper functions used throughout the application.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
require_once('include/database/PearDatabase.php');
require_once('include/ComboUtil.php'); //new
require_once('include/utils/ListViewUtils.php');
require_once('include/utils/EditViewUtils.php');
require_once('include/utils/CommonUtils.php');
require_once('include/utils/InventoryUtils.php');
require_once('include/utils/SearchUtils.php');
require_once('include/FormValidationUtil.php');
require_once('include/events/SqlResultIterator.inc');
require_once('include/fields/DateTimeField.php');
require_once('include/fields/CurrencyField.php');
require_once('data/CRMEntity.php');
require_once 'vtlib/Vtiger/Language.php';
require_once("include/ListView/ListViewSession.php");

require_once 'vtlib/Vtiger/Functions.php';
require_once 'vtlib/Vtiger/Deprecated.php';

require_once 'includes/runtime/Cache.php';
require_once 'modules/Vtiger/helpers/Util.php';
require_once 'vtlib/Vtiger/AccessControl.php';
// Constants to be defined here

// For Migration status.
define("MIG_CHARSET_PHP_UTF8_DB_UTF8", 1);
define("MIG_CHARSET_PHP_NONUTF8_DB_NONUTF8", 2);
define("MIG_CHARSET_PHP_NONUTF8_DB_UTF8", 3);
define("MIG_CHARSET_PHP_UTF8_DB_NONUTF8", 4);

// For Customview status.
define("CV_STATUS_DEFAULT", 0);
define("CV_STATUS_PRIVATE", 1);
define("CV_STATUS_PENDING", 2);
define("CV_STATUS_PUBLIC", 3);

// For Restoration.
define("RB_RECORD_DELETED", 'delete');
define("RB_RECORD_INSERTED", 'insert');
define("RB_RECORD_UPDATED", 'update');

/** Function to return a full name
  * @param $row -- row:: Type integer
  * @param $first_column -- first column:: Type string
  * @param $last_column -- last column:: Type string
  * @returns $fullname -- fullname:: Type string
  *
*/
function return_name(&$row, $first_column, $last_column)
{
	global $log;
	$log->debug("Entering return_name(".$row.",".$first_column.",".$last_column.") method ...");
	$first_name = "";
	$last_name = "";
	$full_name = "";

	if(isset($row[$first_column]))
	{
		$first_name = stripslashes($row[$first_column]);
	}

	if(isset($row[$last_column]))
	{
		$last_name = stripslashes($row[$last_column]);
	}

	$full_name = $last_name;

	// If we have a last name and we have a first name
	if($full_name != "" && $first_name != "")
	{
		// append a space, then the first name
		$full_name .= " ".$first_name;
	}
	// If we have no last name, but we have a first name
	else if($first_name != "")
	{
		// append the first name without the space.
		$full_name .= $first_name;
	}

	$log->debug("Exiting return_name method ...");
	return $full_name;
}

/** Function returns the user key in user array
  * @param $add_blank -- boolean:: Type boolean
  * @param $status -- user status:: Type string
  * @param $assigned_user -- user id:: Type string
  * @param $private -- sharing type:: Type string
  * @returns $user_array -- user array:: Type array
  *
*/

//used in module file
function get_user_array($add_blank=true, $status="Active", $assigned_user="",$private="",$module=false)
{
	global $log;
	$log->debug("Entering get_user_array(".$add_blank.",". $status.",".$assigned_user.",".$private.") method ...");
	global $current_user;
	if(isset($current_user) && $current_user->id != '')
	{
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
	}
	static $user_array = null;
	if(!$module){
        $module=$_REQUEST['module'];
    }


	if($user_array == null)
	{
		require_once('include/database/PearDatabase.php');
		$db = PearDatabase::getInstance();
		$temp_result = Array();
		// Including deleted vtiger_users for now.
		if (empty($status)) {
				$query = "SELECT id, user_name from vtiger_users";
				$params = array();
		}
		else {
				if($private == 'private')
				{
					$log->debug("Sharing is Private. Only the current user should be listed");
					$query = "select id as id,user_name as user_name,last_name,first_name from vtiger_users where id=? and status='Active' union select vtiger_user2role.userid as id,vtiger_users.user_name as user_name ,
							  vtiger_users.first_name as first_name ,vtiger_users.last_name as last_name
							  from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like ? and status='Active' union
							  select shareduserid as id,vtiger_users.user_name as user_name ,
							  vtiger_users.first_name as first_name ,vtiger_users.last_name as last_name  from vtiger_tmp_write_user_sharing_per inner join vtiger_users on vtiger_users.id=vtiger_tmp_write_user_sharing_per.shareduserid where status='Active' and vtiger_tmp_write_user_sharing_per.userid=? and vtiger_tmp_write_user_sharing_per.tabid=? and (user_name != 'admin' OR is_owner=1)";
					$params = array($current_user->id, $current_user_parent_role_seq."::%", $current_user->id, getTabid($module));
				}
				else
				{
					$log->debug("Sharing is Public. All vtiger_users should be listed");
					$query = "SELECT id, user_name,last_name,first_name from vtiger_users WHERE status=? and (user_name != 'admin' OR is_owner=1)";
					$params = array($status);
				}
		}
		if (!empty($assigned_user)) {
			 $query .= " OR id=?";
			 array_push($params, $assigned_user);
		}

		$query .= " order by user_name ASC";

		$result = $db->pquery($query, $params, true, "Error filling in user array: ");

		if ($add_blank==true){
			// Add in a blank row
			$temp_result[''] = '';
		}

		// Get the id and the name.
		while($row = $db->fetchByAssoc($result))
		{
			$temp_result[$row['id']] = getFullNameFromArray('Users', $row);
		}

		$user_array = &$temp_result;
	}

	$log->debug("Exiting get_user_array method ...");
	return $user_array;
}

function get_group_array($add_blank=true, $status="Active", $assigned_user="",$private="",$module = false)
{
	global $log;
	$log->debug("Entering get_user_array(".$add_blank.",". $status.",".$assigned_user.",".$private.") method ...");
	global $current_user;
	if(isset($current_user) && $current_user->id != '')
	{
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
	}
	static $group_array = null;
	if(!$module){
        $module=$_REQUEST['module'];
    }

	if($group_array == null)
	{
		require_once('include/database/PearDatabase.php');
		$db = PearDatabase::getInstance();
		$temp_result = Array();
		// Including deleted vtiger_users for now.
		$log->debug("Sharing is Public. All vtiger_users should be listed");
		$query = "SELECT groupid, groupname from vtiger_groups";
		$params = array();

		if($private == 'private'){

			$query .= " WHERE groupid=?";
			$params = array( $current_user->id);

			if(!empty($current_user_groups) && (count($current_user_groups) != 0)) {
				$query .= " OR vtiger_groups.groupid in (".generateQuestionMarks($current_user_groups).")";
				array_push($params, $current_user_groups);
			}
			$log->debug("Sharing is Private. Only the current user should be listed");
			$query .= " union select vtiger_group2role.groupid as groupid,vtiger_groups.groupname as groupname from vtiger_group2role inner join vtiger_groups on vtiger_groups.groupid=vtiger_group2role.groupid inner join vtiger_role on vtiger_role.roleid=vtiger_group2role.roleid where vtiger_role.parentrole like ?";
			array_push($params, $current_user_parent_role_seq."::%");

			if(!empty($current_user_groups) && (count($current_user_groups) != 0)) {
				$query .= " union select vtiger_groups.groupid as groupid,vtiger_groups.groupname as groupname from vtiger_groups inner join vtiger_group2rs on vtiger_groups.groupid=vtiger_group2rs.groupid where vtiger_group2rs.roleandsubid in (".generateQuestionMarks($parent_roles).")";
				array_push($params, $parent_roles);
			}

			$query .= " union select sharedgroupid as groupid,vtiger_groups.groupname as groupname from vtiger_tmp_write_group_sharing_per inner join vtiger_groups on vtiger_groups.groupid=vtiger_tmp_write_group_sharing_per.sharedgroupid where vtiger_tmp_write_group_sharing_per.userid=?";
			array_push($params, $current_user->id);

			$query .= " and vtiger_tmp_write_group_sharing_per.tabid=?";
			array_push($params,  getTabid($module));
		}
		$query .= " order by groupname ASC";

		$result = $db->pquery($query, $params, true, "Error filling in user array: ");

		if ($add_blank==true){
			// Add in a blank row
			$temp_result[''] = '';
		}

		// Get the id and the name.
		while($row = $db->fetchByAssoc($result))
		{
			$temp_result[$row['groupid']] = $row['groupname'];
		}

		$group_array = &$temp_result;
	}

	$log->debug("Exiting get_user_array method ...");
	return $group_array;
}

/** This function retrieves an application language file and returns the array of strings included in the $app_list_strings var.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 * If you are using the current language, do not call this function unless you are loading it for the first time */

function return_app_list_strings_language($language) {
	return Vtiger_Deprecated::return_app_list_strings_language($language);
}

/**
 * Retrieve the app_currency_strings for the required language.
 */
function return_app_currency_strings_language($language) {
	return Vtiger_Deprecated::return_app_list_strings_language($language);
}

/** This function retrieves an application language file and returns the array of strings included.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 * If you are using the current language, do not call this function unless you are loading it for the first time */
function return_application_language($language) {
	return Vtiger_Deprecated::return_app_list_strings_language($language);
	}

/** This function retrieves a module's language file and returns the array of strings included.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 * If you are in the current module, do not call this function unless you are loading it for the first time */
function return_module_language($language, $module) {
	return Vtiger_Deprecated::getModuleTranslationStrings($language, $module);
}

/*This function returns the mod_strings for the current language and the specified module
*/

function return_specified_module_language($language, $module) {
	return Vtiger_Deprecated::return_app_list_strings_language($language, $module);
}

/**
 * Return an array of directory names.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */
function get_themes() {
	return Vtiger_Theme::getAllSkins();
   }


/** Function to set default varibles on to the global variable
  * @param $defaults -- default values:: Type array
       */
function set_default_config(&$defaults)
{
	global $log;
	$log->debug("Entering set_default_config(".$defaults.") method ...");

	foreach ($defaults as $name=>$value)
	{
		if ( ! isset($GLOBALS[$name]) )
		{
			$GLOBALS[$name] = $value;
		}
	}
	$log->debug("Exiting set_default_config method ...");
}

/**
 * Function to decide whether to_html should convert values or not for a request
 * @global type $doconvert
 * @global type $inUTF8
 * @global type $default_charset
 */
function decide_to_html() {
	global $doconvert, $inUTF8, $default_charset;
 	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : ''; 
 		     
    $inUTF8 = (strtoupper($default_charset) == 'UTF-8'); 

    $doconvert = true; 
	if ($action == 'ExportData') {
        $doconvert = false; 
    } 
}
decide_to_html();

/** Function to convert the given string to html
  * @param $string -- string:: Type string
  * @param $encode -- boolean:: Type boolean
    * @returns $string -- string:: Type string
       */
function to_html($string, $encode=true) {
	// For optimization - default_charset can be either upper / lower case.
    global $doconvert,$inUTF8,$default_charset,$htmlCache;

    if(is_string($string)) {
		// In vtiger5 ajax request are treated specially and the data is encoded
		if ($doconvert == true) {
            if(isset($htmlCache[$string])){
                $string = $htmlCache[$string];
            }else{
			if($inUTF8)
				$string = htmlentities($string, ENT_QUOTES, $default_charset);
			else
				$string = preg_replace(array('/</', '/>/', '/"/'), array('&lt;', '&gt;', '&quot;'), $string);
                $htmlCache[$string] = $string;
            }
		}
	}
	return $string;
}

/** Function to get the tablabel for a given id
  * @param $tabid -- tab id:: Type integer
  * @returns $string -- string:: Type string
*/

function getTabname($tabid)
{
	global $log;
	$log->debug("Entering getTabname(".$tabid.") method ...");
        $log->info("tab id is ".$tabid);
        global $adb;

	static $cache = array();

	if (!isset($cache[$tabid])) {
		$sql = "select tablabel from vtiger_tab where tabid=?";
		$result = $adb->pquery($sql, array($tabid));
		$tabname=  $adb->query_result($result,0,"tablabel");
		$cache[$tabid] = $tabname;
	}

	$log->debug("Exiting getTabname method ...");
	return $cache[$tabid];

}

/** Function to get the tab module name for a given id
  * @param $tabid -- tab id:: Type integer
    * @returns $string -- string:: Type string
      *
       */

function getTabModuleName($tabid)
{
	return Vtiger_Functions::getModuleName($tabid);
}

/** Function to get column fields for a given module
  * @param $module -- module:: Type string
    * @returns $column_fld -- column field :: Type array
      *
       */

function getColumnFields($module)
{
	global $log;
	$log->debug("Entering getColumnFields(".$module.") method ...");
	$log->debug("in getColumnFields ".$module);

	// Lookup in cache for information
	$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);

	if($cachedModuleFields === false) {
		global $adb;
		$tabid = getTabid($module);

		if ($module == 'Calendar') {
    		$tabid = array('9','16');
    	}

		// To overcome invalid module names.
		if (empty($tabid)) {
			return array();
		}

    	// Let us pick up all the fields first so that we can cache information
		$sql = "SELECT tabid, fieldname, fieldid, fieldlabel, columnname, tablename, uitype, typeofdata, presence
		FROM vtiger_field WHERE tabid in (" . generateQuestionMarks($tabid) . ")";

        $result = $adb->pquery($sql, array($tabid));
        $noofrows = $adb->num_rows($result);

        if($noofrows) {
        	while($resultrow = $adb->fetch_array($result)) {
        		// Update information to cache for re-use
        		VTCacheUtils::updateFieldInfo(
        			$resultrow['tabid'], $resultrow['fieldname'], $resultrow['fieldid'],
        			$resultrow['fieldlabel'], $resultrow['columnname'], $resultrow['tablename'],
        			$resultrow['uitype'], $resultrow['typeofdata'], $resultrow['presence']
        		);
        	}
        }

        // For consistency get information from cache
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
	}

	if($module == 'Calendar') {
		$cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module('Events');
		if (!$cachedEventsFields) {
            getColumnFields('Events');
            $cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module('Events');
        }
        
		if (!$cachedModuleFields) {
            $cachedModuleFields = $cachedEventsFields;
		} else {
            $cachedModuleFields = array_merge($cachedModuleFields, $cachedEventsFields);
        }
    }

	$column_fld = new TrackableObject();
	if($cachedModuleFields) {
		foreach($cachedModuleFields as $fieldinfo) {
			$column_fld[$fieldinfo['fieldname']] = '';
		}
	}

	$log->debug("Exiting getColumnFields method ...");
	return $column_fld;
}

/** Function to get a users's mail id
  * @param $userid -- userid :: Type integer
    * @returns $email -- email :: Type string
      *
       */

function getUserEmail($userid)
{
	global $log;
	$log->debug("Entering getUserEmail(".$userid.") method ...");
	$log->info("in getUserEmail ".$userid);

        global $adb;
        if($userid != '')
        {
                $sql = "select email1 from vtiger_users where id=?";
                $result = $adb->pquery($sql, array($userid));
                $email = $adb->query_result($result,0,"email1");
        }
	$log->debug("Exiting getUserEmail method ...");
        return $email;
}

/** Function to get a userid for outlook
  * @param $username -- username :: Type string
    * @returns $user_id -- user id :: Type integer
       */

//outlook security
function getUserId_Ol($username)
{
	global $log;
	$log->debug("Entering getUserId_Ol(".$username.") method ...");
	$log->info("in getUserId_Ol ".$username);
	$cache = Vtiger_Cache::getInstance();
	if($cache->getUserId($username) || $cache->getUserId($username) === 0){
		return $cache->getUserId($username);
	} else {
	global $adb;
	$sql = "select id from vtiger_users where user_name=?";
	$result = $adb->pquery($sql, array($username));
	$num_rows = $adb->num_rows($result);
	if($num_rows > 0)
	{
		$user_id = $adb->query_result($result,0,"id");
    	}
	else
	{
		$user_id = 0;
	}
	$log->debug("Exiting getUserId_Ol method ...");
		$cache->setUserId($username,$user_id);
	return $user_id;
	}
}


/** Function to get a action id for a given action name
  * @param $action -- action name :: Type string
    * @returns $actionid -- action id :: Type integer
       */

//outlook security

function getActionid($action)
{
	global $log;
	$log->debug("Entering getActionid(".$action.") method ...");
	global $adb;
	$log->info("get Actionid ".$action);
	$actionid = '';
	if(file_exists('tabdata.php') && (filesize('tabdata.php') != 0))
	{
		include('tabdata.php');
		$actionid= $action_id_array[$action];
	}
	else
	{
		$query="select * from vtiger_actionmapping where actionname=?";
        	$result =$adb->pquery($query, array($action));
        	$actionid=$adb->query_result($result,0,'actionid');

	}
	$log->info("action id selected is ".$actionid );
	$log->debug("Exiting getActionid method ...");
	return $actionid;
}

/** Function to get a action for a given action id
  * @param $action id -- action id :: Type integer
    * @returns $actionname-- action name :: Type string
       */


function getActionname($actionid)
{
	global $log;
	$log->debug("Entering getActionname(".$actionid.") method ...");
	global $adb;

	$actionname='';

	if (file_exists('tabdata.php') && (filesize('tabdata.php') != 0))
	{
		include('tabdata.php');
		$actionname= $action_name_array[$actionid];
	}
	else
	{

		$query="select * from vtiger_actionmapping where actionid=? and securitycheck=0";
		$result =$adb->pquery($query, array($actionid));
		$actionname=$adb->query_result($result,0,"actionname");
	}
	$log->debug("Exiting getActionname method ...");
	return $actionname;
}

/** Function to get a user id or group id for a given entity
  * @param $record -- entity id :: Type integer
    * @returns $ownerArr -- owner id :: Type array
       */

function getRecordOwnerId($record)
{
	global $log;
	$log->debug("Entering getRecordOwnerId(".$record.") method ...");
	global $adb;
	$ownerArr=Array();

	// Look at cache first for information
	$ownerId = VTCacheUtils::lookupRecordOwner($record);

	if($ownerId === false) {
		$query="select smownerid from vtiger_crmentity where crmid = ?";
		$result=$adb->pquery($query, array($record));
		if($adb->num_rows($result) > 0)
		{
			$ownerId=$adb->query_result($result,0,'smownerid');
			// Update cache for re-use
			VTCacheUtils::updateRecordOwner($record, $ownerId);
		}
	}

	if($ownerId)
	{
		// Look at cache first for information
		$count = VTCacheUtils::lookupOwnerType($ownerId);

		if($count === false) {
			$sql_result = $adb->pquery('SELECT 1 FROM vtiger_users WHERE id = ?', array($ownerId));
			$count = $adb->query_result($sql_result, 0, 1);
			// Update cache for re-use
			VTCacheUtils::updateOwnerType($ownerId, $count);
		}
		if($count > 0)
			$ownerArr['Users'] = $ownerId;
		else
			$ownerArr['Groups'] = $ownerId;
	}
	$log->debug("Exiting getRecordOwnerId method ...");
	return $ownerArr;

}

/** Function to insert value to profile2field table
  * @param $profileid -- profileid :: Type integer
       */


function insertProfile2field($profileid)
{
	global $log;
	$log->debug("Entering insertProfile2field(".$profileid.") method ...");
        $log->info("in insertProfile2field ".$profileid);

	global $adb;
	$adb->database->SetFetchMode(ADODB_FETCH_ASSOC);
	$fld_result = $adb->pquery("select * from vtiger_field where generatedtype=1 and displaytype in (1,2,3) and vtiger_field.presence in (0,2) and tabid != 29", array());
    $num_rows = $adb->num_rows($fld_result);
    for($i=0; $i<$num_rows; $i++) {
         $tab_id = $adb->query_result($fld_result,$i,'tabid');
         $field_id = $adb->query_result($fld_result,$i,'fieldid');
		 $params = array($profileid, $tab_id, $field_id, 0, 0);
         $adb->pquery("insert into vtiger_profile2field values (?,?,?,?,?)", $params);
	}
	$log->debug("Exiting insertProfile2field method ...");
}

/** Function to insert into default org field
       */

function insert_def_org_field()
{
	global $log;
	$log->debug("Entering insert_def_org_field() method ...");
	global $adb;
	$adb->database->SetFetchMode(ADODB_FETCH_ASSOC);
	$fld_result = $adb->pquery("select * from vtiger_field where generatedtype=1 and displaytype in (1,2,3) and vtiger_field.presence in (0,2) and tabid != 29", array());
        $num_rows = $adb->num_rows($fld_result);
        for($i=0; $i<$num_rows; $i++)
        {
                 $tab_id = $adb->query_result($fld_result,$i,'tabid');
                 $field_id = $adb->query_result($fld_result,$i,'fieldid');
				 $params = array($tab_id, $field_id, 0, 0);
                 $adb->pquery("insert into vtiger_def_org_field values (?,?,?,?)", $params);
	}
	$log->debug("Exiting insert_def_org_field() method ...");
}

/** Function to update product quantity
  * @param $product_id -- product id :: Type integer
  * @param $upd_qty -- quantity :: Type integer
  */

function updateProductQty($product_id, $upd_qty)
{
	global $log;
	$log->debug("Entering updateProductQty(".$product_id.",". $upd_qty.") method ...");
	global $adb;
	$query= "update vtiger_products set qtyinstock=? where productid=?";
    $adb->pquery($query, array($upd_qty, $product_id));
	$log->debug("Exiting updateProductQty method ...");

}

/** This Function adds the specified product quantity to the Product Quantity in Stock in the Warehouse
  * The following is the input parameter for the function:
  *  $productId --> ProductId, Type:Integer
  *  $qty --> Quantity to be added, Type:Integer
  */
function addToProductStock($productId,$qty)
{
	global $log;
	$log->debug("Entering addToProductStock(".$productId.",".$qty.") method ...");
	global $adb;
	$qtyInStck=getProductQtyInStock($productId);
	$updQty=$qtyInStck + $qty;
	$sql = "UPDATE vtiger_products set qtyinstock=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting addToProductStock method ...");

    }

/**	This Function adds the specified product quantity to the Product Quantity in Demand in the Warehouse
  *	@param int $productId - ProductId
  *	@param int $qty - Quantity to be added
  */
function addToProductDemand($productId,$qty)
{
	global $log;
	$log->debug("Entering addToProductDemand(".$productId.",".$qty.") method ...");
		global $adb;
	$qtyInStck=getProductQtyInDemand($productId);
	$updQty=$qtyInStck + $qty;
	$sql = "UPDATE vtiger_products set qtyindemand=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting addToProductDemand method ...");

		}

/**	This Function subtract the specified product quantity to the Product Quantity in Stock in the Warehouse
  *	@param int $productId - ProductId
  *	@param int $qty - Quantity to be subtracted
  */
function deductFromProductStock($productId,$qty)
{
	global $log;
	$log->debug("Entering deductFromProductStock(".$productId.",".$qty.") method ...");
	global $adb;
	$qtyInStck=getProductQtyInStock($productId);
	$updQty=$qtyInStck - $qty;
	$sql = "UPDATE vtiger_products set qtyinstock=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting deductFromProductStock method ...");

}

/**	This Function subtract the specified product quantity to the Product Quantity in Demand in the Warehouse
  *	@param int $productId - ProductId
  *	@param int $qty - Quantity to be subtract
  */
function deductFromProductDemand($productId,$qty)
{
	global $log;
	$log->debug("Entering deductFromProductDemand(".$productId.",".$qty.") method ...");
	global $adb;
	$qtyInStck=getProductQtyInDemand($productId);
	$updQty=$qtyInStck - $qty;
	$sql = "UPDATE vtiger_products set qtyindemand=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting deductFromProductDemand method ...");

    }


/** This Function returns the current product quantity in stock.
  * The following is the input parameter for the function:
  *  $product_id --> ProductId, Type:Integer
  */
function getProductQtyInStock($product_id)
{
	global $log;
	$log->debug("Entering getProductQtyInStock(".$product_id.") method ...");
	global $adb;
        $query1 = "select qtyinstock from vtiger_products where productid=?";
        $result=$adb->pquery($query1, array($product_id));
        $qtyinstck= $adb->query_result($result,0,"qtyinstock");
	$log->debug("Exiting getProductQtyInStock method ...");
        return $qtyinstck;


}

/**	This Function returns the current product quantity in demand.
  *	@param int $product_id - ProductId
  *	@return int $qtyInDemand - Quantity in Demand of a product
  */
function getProductQtyInDemand($product_id)
{
	global $log;
	$log->debug("Entering getProductQtyInDemand(".$product_id.") method ...");
	global $adb;
        $query1 = "select qtyindemand from vtiger_products where productid=?";
        $result = $adb->pquery($query1, array($product_id));
        $qtyInDemand = $adb->query_result($result,0,"qtyindemand");
	$log->debug("Exiting getProductQtyInDemand method ...");
        return $qtyInDemand;
}

/**     Function to get the vtiger_table name from 'field' vtiger_table for the input vtiger_field based on the module
 *      @param  : string $module - current module value
 *      @param  : string $fieldname - vtiger_fieldname to which we want the vtiger_tablename
 *      @return : string $tablename - vtiger_tablename in which $fieldname is a column, which is retrieved from 'field' vtiger_table per $module basis
  */
function getTableNameForField($module,$fieldname)
{
	global $log;
	$log->debug("Entering getTableNameForField(".$module.",".$fieldname.") method ...");
	global $adb;
	$tabid = getTabid($module);
	//Asha
	if($module == 'Calendar') {
		$tabid = array('9','16');
}
	$sql = "select tablename from vtiger_field where tabid in (". generateQuestionMarks($tabid) .") and vtiger_field.presence in (0,2) and columnname like ?";
	$res = $adb->pquery($sql, array($tabid, '%'.$fieldname.'%'));

	$tablename = '';
	if($adb->num_rows($res) > 0)
{
		$tablename = $adb->query_result($res,0,'tablename');
	}

	$log->debug("Exiting getTableNameForField method ...");
	return $tablename;
}

/** Function to get parent record owner
  * @param $tabid -- tabid :: Type integer
  * @param $parModId -- parent module id :: Type integer
  * @param $record_id -- record id :: Type integer
  * @returns $parentRecOwner -- parentRecOwner:: Type integer
  */

function getParentRecordOwner($tabid,$parModId,$record_id)
 {
	global $log;
	$log->debug("Entering getParentRecordOwner(".$tabid.",".$parModId.",".$record_id.") method ...");
	$parentRecOwner=Array();
	$parentTabName=getTabname($parModId);
	$relTabName=getTabname($tabid);
	$fn_name="get".$relTabName."Related".$parentTabName;
	$ent_id=$fn_name($record_id);
	if($ent_id != '')
        {
		$parentRecOwner=getRecordOwnerId($ent_id);
        }
	$log->debug("Exiting getParentRecordOwner method ...");
	return $parentRecOwner;
        }

/** Function to get potential related accounts
  * @param $record_id -- record id :: Type integer
  * @returns $accountid -- accountid:: Type integer
  */

function getPotentialsRelatedAccounts($record_id)
{
	global $log;
	$log->debug("Entering getPotentialsRelatedAccounts(".$record_id.") method ...");
	global $adb;
	$query="select related_to from vtiger_potential where potentialid=?";
	$result=$adb->pquery($query, array($record_id));
	$accountid=$adb->query_result($result,0,'related_to');
	$log->debug("Exiting getPotentialsRelatedAccounts method ...");
	return $accountid;
}

/** Function to get email related accounts
  * @param $record_id -- record id :: Type integer
  * @returns $accountid -- accountid:: Type integer
  */
function getEmailsRelatedAccounts($record_id)
{
	global $log;
	$log->debug("Entering getEmailsRelatedAccounts(".$record_id.") method ...");
	global $adb;
	$query = "select vtiger_seactivityrel.crmid from vtiger_seactivityrel inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_seactivityrel.crmid where vtiger_crmentity.setype='Accounts' and activityid=?";
	$result = $adb->pquery($query, array($record_id));
	$accountid=$adb->query_result($result,0,'crmid');
	$log->debug("Exiting getEmailsRelatedAccounts method ...");
	return $accountid;
}
/** Function to get email related Leads
  * @param $record_id -- record id :: Type integer
  * @returns $leadid -- leadid:: Type integer
  */

function getEmailsRelatedLeads($record_id)
{
	global $log;
	$log->debug("Entering getEmailsRelatedLeads(".$record_id.") method ...");
	global $adb;
	$query = "select vtiger_seactivityrel.crmid from vtiger_seactivityrel inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_seactivityrel.crmid where vtiger_crmentity.setype='Leads' and activityid=?";
	$result = $adb->pquery($query, array($record_id));
	$leadid=$adb->query_result($result,0,'crmid');
	$log->debug("Exiting getEmailsRelatedLeads method ...");
	return $leadid;
}

/** Function to get HelpDesk related Accounts
  * @param $record_id -- record id :: Type integer
  * @returns $accountid -- accountid:: Type integer
  */

function getHelpDeskRelatedAccounts($record_id)
{
	global $log;
	$log->debug("Entering getHelpDeskRelatedAccounts(".$record_id.") method ...");
	global $adb;
        $query="select parent_id from vtiger_troubletickets inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_troubletickets.parent_id where ticketid=? and vtiger_crmentity.setype='Accounts'";
        $result=$adb->pquery($query, array($record_id));
        $accountid=$adb->query_result($result,0,'parent_id');
	$log->debug("Exiting getHelpDeskRelatedAccounts method ...");
        return $accountid;
}

/** Function to get Quotes related Accounts
  * @param $record_id -- record id :: Type integer
  * @returns $accountid -- accountid:: Type integer
  */

function getQuotesRelatedAccounts($record_id)
{
	global $log;
	$log->debug("Entering getQuotesRelatedAccounts(".$record_id.") method ...");
	global $adb;
        $query="select accountid from vtiger_quotes where quoteid=?";
        $result=$adb->pquery($query, array($record_id));
        $accountid=$adb->query_result($result,0,'accountid');
	$log->debug("Exiting getQuotesRelatedAccounts method ...");
        return $accountid;
}

/** Function to get Quotes related Potentials
  * @param $record_id -- record id :: Type integer
  * @returns $potid -- potid:: Type integer
  */

function getQuotesRelatedPotentials($record_id)
{
	global $log;
	$log->debug("Entering getQuotesRelatedPotentials(".$record_id.") method ...");
	global $adb;
        $query="select potentialid from vtiger_quotes where quoteid=?";
        $result=$adb->pquery($query, array($record_id));
        $potid=$adb->query_result($result,0,'potentialid');
	$log->debug("Exiting getQuotesRelatedPotentials method ...");
        return $potid;
}

/** Function to get Quotes related Potentials
  * @param $record_id -- record id :: Type integer
  * @returns $accountid -- accountid:: Type integer
  */

function getSalesOrderRelatedAccounts($record_id)
{
	global $log;
	$log->debug("Entering getSalesOrderRelatedAccounts(".$record_id.") method ...");
	global $adb;
        $query="select accountid from vtiger_salesorder where salesorderid=?";
        $result=$adb->pquery($query, array($record_id));
        $accountid=$adb->query_result($result,0,'accountid');
	$log->debug("Exiting getSalesOrderRelatedAccounts method ...");
        return $accountid;
}

/** Function to get SalesOrder related Potentials
  * @param $record_id -- record id :: Type integer
  * @returns $potid -- potid:: Type integer
  */

function getSalesOrderRelatedPotentials($record_id)
{
	global $log;
	$log->debug("Entering getSalesOrderRelatedPotentials(".$record_id.") method ...");
	global $adb;
        $query="select potentialid from vtiger_salesorder where salesorderid=?";
        $result=$adb->pquery($query, array($record_id));
        $potid=$adb->query_result($result,0,'potentialid');
	$log->debug("Exiting getSalesOrderRelatedPotentials method ...");
        return $potid;
}
/** Function to get SalesOrder related Quotes
  * @param $record_id -- record id :: Type integer
  * @returns $qtid -- qtid:: Type integer
  */

function getSalesOrderRelatedQuotes($record_id)
{
	global $log;
	$log->debug("Entering getSalesOrderRelatedQuotes(".$record_id.") method ...");
	global $adb;
        $query="select quoteid from vtiger_salesorder where salesorderid=?";
        $result=$adb->pquery($query, array($record_id));
        $qtid=$adb->query_result($result,0,'quoteid');
	$log->debug("Exiting getSalesOrderRelatedQuotes method ...");
        return $qtid;
}

/** Function to get Invoice related Accounts
  * @param $record_id -- record id :: Type integer
  * @returns $accountid -- accountid:: Type integer
  */

function getInvoiceRelatedAccounts($record_id)
{
	global $log;
	$log->debug("Entering getInvoiceRelatedAccounts(".$record_id.") method ...");
	global $adb;
        $query="select accountid from vtiger_invoice where invoiceid=?";
        $result=$adb->pquery($query, array($record_id));
        $accountid=$adb->query_result($result,0,'accountid');
	$log->debug("Exiting getInvoiceRelatedAccounts method ...");
        return $accountid;
}
/** Function to get Invoice related SalesOrder
  * @param $record_id -- record id :: Type integer
  * @returns $soid -- soid:: Type integer
  */

function getInvoiceRelatedSalesOrder($record_id)
{
	global $log;
	$log->debug("Entering getInvoiceRelatedSalesOrder(".$record_id.") method ...");
	global $adb;
        $query="select salesorderid from vtiger_invoice where invoiceid=?";
        $result=$adb->pquery($query, array($record_id));
        $soid=$adb->query_result($result,0,'salesorderid');
	$log->debug("Exiting getInvoiceRelatedSalesOrder method ...");
        return $soid;
}

/**
* the function is like unescape in javascript
* added by dingjianting on 2006-10-1 for picklist editor
*/
function utf8RawUrlDecode ($source) {
    global $default_charset;
    $decodedStr = "";
    $pos = 0;
    $len = strlen ($source);
    while ($pos < $len) {
        $charAt = substr ($source, $pos, 1);
        if ($charAt == '%') {
            $pos++;
            $charAt = substr ($source, $pos, 1);
            if ($charAt == 'u') {
                // we got a unicode character
                $pos++;
                $unicodeHexVal = substr ($source, $pos, 4);
                $unicode = hexdec ($unicodeHexVal);
                $entity = "&#". $unicode . ';';
                $decodedStr .= utf8_encode ($entity);
                $pos += 4;
		}
            else {
                // we have an escaped ascii character
                $hexVal = substr ($source, $pos, 2);
                $decodedStr .= chr (hexdec ($hexVal));
                $pos += 2;
 }
	} else {
            $decodedStr .= $charAt;
            $pos++;
        }
	}
    if(strtolower($default_charset) == 'utf-8')
	    return html_to_utf8($decodedStr);
    else
	    return $decodedStr;
    //return html_to_utf8($decodedStr);
}

/**
*simple HTML to UTF-8 conversion:
  */
function html_to_utf8 ($data)
{
	return preg_replace("/\\&\\#([0-9]{3,10})\\;/e", '_html_to_utf8("\\1")', $data);
	}

function _html_to_utf8 ($data)
	{
	if ($data > 127)
		{
		$i = 5;
		while (($i--) > 0)
		{
			if ($data != ($a = $data % ($p = pow(64, $i))))
	{
				$ret = chr(base_convert(str_pad(str_repeat(1, $i + 1), 8, "0"), 2, 10) + (($data - $a) / $p));
				for ($i; $i > 0; $i--)
					$ret .= chr(128 + ((($data % pow(64, $i)) - ($data % ($p = pow(64, $i - 1)))) / $p));
				break;
	}
}
	}
	else
		$ret = "&#$data;";
	return $ret;
	}

// Return Question mark
function _questionify($v){
	return "?";
}

/**
* Function to generate question marks for a given list of items
  */
function generateQuestionMarks($items_list) {
	// array_map will call the function specified in the first parameter for every element of the list in second parameter
	if (is_array($items_list)) {
		return implode(",", array_map("_questionify", $items_list));
	} else {
		return implode(",", array_map("_questionify", explode(",", $items_list)));
}
}

/**
* Function to find the UI type of a field based on the uitype id
  */
function is_uitype($uitype, $reqtype) {
	$ui_type_arr = array(
		'_date_' => array(5, 6, 23, 70),
		'_picklist_' => array(15, 16, 52, 53, 54, 55, 59, 62, 63, 66, 68, 76, 77, 78, 80, 98, 101, 115, 357),
		'_users_list_' => array(52),
	);

	if ($ui_type_arr[$reqtype] != null) {
		if (in_array($uitype, $ui_type_arr[$reqtype])) {
			return true;
	}
	}
	return false;
	}
/**
 * Function to escape quotes
 * @param $value - String in which single quotes have to be replaced.
 * @return Input string with single quotes escaped.
  */
function escape_single_quotes($value) {
	if (isset($value)) $value = str_replace("'", "\'", $value);
	return $value;
}

/**
 * Function to format the input value for SQL like clause.
 * @param $str - Input string value to be formatted.
 * @param $flag - By default set to 0 (Will look for cases %string%).
 *                If set to 1 - Will look for cases %string.
 *                If set to 2 - Will look for cases string%.
 * @return String formatted as per the SQL like clause requirement
  */
function formatForSqlLike($str, $flag=0,$is_field=false) {
	global $adb;
	if (isset($str)) {
		if($is_field==false){
			$str = str_replace('%', '\%', $str);
			$str = str_replace('_', '\_', $str);
			if ($flag == 0) {
                // If value what to search is null then we should not add % which will fail
                if(empty($str))
                    $str = ''.$str.'';
                else
                    $str = '%'. $str .'%';
			} elseif ($flag == 1) {
				$str = '%'. $str;
			} elseif ($flag == 2) {
				$str = $str .'%';
			}
		} else {
			if ($flag == 0) {
				$str = 'concat("%",'. $str .',"%")';
			} elseif ($flag == 1) {
				$str = 'concat("%",'. $str .')';
			} elseif ($flag == 2) {
				$str = 'concat('. $str .',"%")';
			}
		}
	}
	return $adb->sql_escape_string($str);
}

/**	Function used to get all the picklists and their values for a module
	@param string $module - Module name to which the list of picklists and their values needed
	@return array $fieldlists - Array of picklists and their values
**/
function getAccessPickListValues($module)
{
	global $adb, $log;
	global $current_user;
	$log->debug("Entering into function getAccessPickListValues($module)");

	$id = getTabid($module);
	$query = "select fieldname,columnname,fieldid,fieldlabel,tabid,uitype from vtiger_field where tabid = ? and uitype in ('15','33','55') and vtiger_field.presence in (0,2)";
	$result = $adb->pquery($query, array($id));

	$roleid = $current_user->roleid;
	$subrole = getRoleSubordinates($roleid);

	if(!empty($subrole) && (count($subrole)> 0))
{
		$roleids = $subrole;
		array_push($roleids, $roleid);
	}
	else
	{
		$roleids = $roleid;
	}

	$temp_status = Array();
	for($i=0;$i < $adb->num_rows($result);$i++)
{
		$fieldname = Vtiger_Util_Helper::validateStringForSql($adb->query_result($result,$i,"fieldname"));
		$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
		$columnname = $adb->query_result($result,$i,"columnname");
		$tabid = $adb->query_result($result,$i,"tabid");
		$uitype = $adb->query_result($result,$i,"uitype");

		$keyvalue = $columnname;
		$fieldvalues = Array();
		if (!empty($roleids) && (count($roleids) > 1))
	{
			$mulsel="select distinct $fieldname from vtiger_$fieldname inner join vtiger_role2picklist on vtiger_role2picklist.picklistvalueid = vtiger_$fieldname.picklist_valueid where roleid in (\"". implode($roleids,"\",\"") ."\") and picklistid in (select picklistid from vtiger_$fieldname) order by sortid asc";
	}
	else
	{
			$mulsel="select distinct $fieldname from vtiger_$fieldname inner join vtiger_role2picklist on vtiger_role2picklist.picklistvalueid = vtiger_$fieldname.picklist_valueid where roleid ='".$roleid."' and picklistid in (select picklistid from vtiger_$fieldname) order by sortid asc";
	}
		if($fieldname != 'firstname')
			$mulselresult = $adb->pquery($mulsel, array());
		for($j=0;$j < $adb->num_rows($mulselresult);$j++)
		{
			$fieldvalues[] = $adb->query_result($mulselresult,$j,$fieldname);
}
		$field_count = count($fieldvalues);
		if($uitype == 15 && $field_count > 0 && ($fieldname == 'taskstatus' || $fieldname == 'eventstatus'))
		{
			$temp_count =count($temp_status[$keyvalue]);
			if($temp_count > 0)
{
				for($t=0;$t < $field_count;$t++)
	{
					$temp_status[$keyvalue][($temp_count+$t)] = $fieldvalues[$t];
				}
				$fieldvalues = $temp_status[$keyvalue];
	}
			else
				$temp_status[$keyvalue] = $fieldvalues;
	}
		if($uitype == 33)
			$fieldlists[1][$keyvalue] = $fieldvalues;
		else if($uitype == 55 && $fieldname == 'salutationtype')
			$fieldlists[$keyvalue] = $fieldvalues;
		else if($uitype == 15)
			$fieldlists[$keyvalue] = $fieldvalues;
	}
	$log->debug("Exit from function getAccessPickListValues($module)");

	return $fieldlists;
	}

function get_config_status() {
	global $default_charset;
	if(strtolower($default_charset) == 'utf-8')
		$config_status=1;
	else
		$config_status=0;
	return $config_status;
        }

function getMigrationCharsetFlag() {
	global $adb;

	if(!$adb->isPostgres())
		$db_status=$adb->check_db_utf8_support();
	$config_status=get_config_status();

	if ($db_status == $config_status) {
		if ($db_status == 1) { // Both are UTF-8
			$db_migration_status = MIG_CHARSET_PHP_UTF8_DB_UTF8;
		} else { // Both are Non UTF-8
			$db_migration_status = MIG_CHARSET_PHP_NONUTF8_DB_NONUTF8;
}
		} else {
			if ($db_status == 1) { // Database charset is UTF-8 and CRM charset is Non UTF-8
				$db_migration_status = MIG_CHARSET_PHP_NONUTF8_DB_UTF8;
		} else { // Database charset is Non UTF-8 and CRM charset is UTF-8
			$db_migration_status = MIG_CHARSET_PHP_UTF8_DB_NONUTF8;
	}
	}
	return $db_migration_status;
}

/** Function to get on clause criteria for duplicate check queries */
function get_on_clause($field_list,$uitype_arr,$module)
{
	$field_array = explode(",",$field_list);
	$ret_str = '';
	$i=1;
	foreach($field_array as $fld)
	{
		$sub_arr = explode(".",$fld);
		$tbl_name = $sub_arr[0];
		$col_name = $sub_arr[1];
		$fld_name = $sub_arr[2];

		$ret_str .= " ifnull($tbl_name.$col_name,'null') = ifnull(temp.$col_name,'null')";

		if (count($field_array) != $i) $ret_str .= " and ";
		$i++;
	}
	return $ret_str;
}

// Update all the data refering to currency $old_cur to $new_cur
function transferCurrency($old_cur, $new_cur) {

	// Transfer User currency to new currency
	transferUserCurrency($old_cur, $new_cur);

	// Transfer Product Currency to new currency
	transferProductCurrency($old_cur, $new_cur);

	// Transfer PriceBook Currency to new currency
	transferPriceBookCurrency($old_cur, $new_cur);
    
    // Transfer Services Currency to new currency
    transferServicesCurrency($old_cur, $new_cur);
}

// Function to transfer the users with currency $old_cur to $new_cur as currency
function transferUserCurrency($old_cur, $new_cur) {
	global $log, $adb, $current_user;
	$log->debug("Entering function transferUserCurrency...");

	$sql = 'UPDATE vtiger_users SET currency_id=? WHERE currency_id=?';
	$adb->pquery($sql, array($new_cur, $old_cur));

	$currentUserId = $current_user->id;
	$current_user->retrieve_entity_info($currentUserId, 'Users');
	unset(Users_Record_Model::$currentUserModels[$currentUserId]);

	require_once('modules/Users/CreateUserPrivilegeFile.php'); 
	createUserPrivilegesfile($currentUserId);

	$log->debug("Exiting function transferUserCurrency...");
}

// Function to transfer the products with currency $old_cur to $new_cur as currency
function transferProductCurrency($old_cur, $new_cur) {
	global $log, $adb;
	$log->debug("Entering function updateProductCurrency...");
	$prod_res = $adb->pquery("select productid from vtiger_products where currency_id = ?", array($old_cur));
	$numRows = $adb->num_rows($prod_res);
	$prod_ids = array();
	for($i=0;$i<$numRows;$i++) {
		$prod_ids[] = $adb->query_result($prod_res,$i,'productid');
	}
	if(!empty($prod_ids) && (count($prod_ids) > 0)) {
		$prod_price_list = getPricesForProducts($new_cur,$prod_ids);

		for($i=0;$i<count($prod_ids);$i++) {
			$product_id = $prod_ids[$i];
			$unit_price = $prod_price_list[$product_id];
			$query = "update vtiger_products set currency_id=?, unit_price=? where productid=?";
			$params = array($new_cur, $unit_price, $product_id);
			$adb->pquery($query, $params);
		}
}
	$log->debug("Exiting function updateProductCurrency...");
}

// Function to transfer the pricebooks with currency $old_cur to $new_cur as currency
// and to update the associated products with list price in $new_cur currency
function transferPriceBookCurrency($old_cur, $new_cur) {
	global $log, $adb;
	$log->debug("Entering function updatePriceBookCurrency...");
	$pb_res = $adb->pquery("select pricebookid from vtiger_pricebook where currency_id = ?", array($old_cur));
	$numRows = $adb->num_rows($pb_res);
	$pb_ids = array();
	for($i=0;$i<$numRows;$i++) {
		$pb_ids[] = $adb->query_result($pb_res,$i,'pricebookid');
}

	if(!empty($pb_ids) && (count($pb_ids) > 0)) {
		require_once('modules/PriceBooks/PriceBooks.php');

		for($i=0;$i<count($pb_ids);$i++) {
			$pb_id = $pb_ids[$i];
			$focus = new PriceBooks();
			$focus->id = $pb_id;
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($pb_id, "PriceBooks");
			$focus->column_fields['currency_id'] = $new_cur;
			$focus->save("PriceBooks");
}
}

	$log->debug("Exiting function updatePriceBookCurrency...");
}

//To transfer all services after deleting currency to transfered currency
function transferServicesCurrency($old_cur, $new_cur) {
    global $log, $adb;
    $log->debug("Entering function updateServicesCurrency...");
	$ser_res = $adb->pquery('SELECT serviceid FROM vtiger_service WHERE currency_id = ?', array($old_cur));
    $numRows = $adb->num_rows($ser_res);
    $ser_ids = array();
    for ($i = 0; $i < $numRows; $i++) {
        $ser_ids[] = $adb->query_result($ser_res, $i, 'serviceid');
    }
    if (!empty($ser_ids) && (count($ser_ids) > 0)) {
        $ser_price_list = getPricesForProducts($new_cur, $ser_ids, 'Services');
        for ($i = 0; $i < count($ser_ids); $i++) {
            $service_id = $ser_ids[$i];
            $unit_price = $ser_price_list[$service_id];
			$query = 'UPDATE vtiger_service SET currency_id=?, unit_price=? WHERE serviceid=?';
            $params = array($new_cur, $unit_price, $service_id);
            $adb->pquery($query, $params);
        }
    }
    $log->debug("Exiting function updateServicesCurrency...");
}

/**
 * this function searches for a given number in vtiger and returns the callerInfo in an array format
 * currently the search is made across only leads, accounts and contacts modules
 *
 * @param $number - the number whose information you want
 * @return array in format array(name=>callername, module=>module, id=>id);
  */
function getCallerInfo($number){
	global $adb, $log;
	if(empty($number)){
		return false;
}
	$caller = "Unknown Number (Unknown)"; //declare caller as unknown in beginning

	$params = array();
	$name = array('Contacts', 'Accounts', 'Leads');
	foreach ($name as $module) {
		$focus = CRMEntity::getInstance($module);
		$query = $focus->buildSearchQueryForFieldTypes(11, $number);
		if(empty($query)) return;

		$result = $adb->pquery($query, array());
		if($adb->num_rows($result) > 0 ){
			$callerName = $adb->query_result($result, 0, "name");
			$callerID = $adb->query_result($result,0,'id');
			$data = array("name"=>$callerName, "module"=>$module, "id"=>$callerID);
			return $data;
		}
	}
	return false;
}

/**
 * this function returns the value of use_asterisk from the database for the current user
 * @param string $id - the id of the current user
 */
function get_use_asterisk($id){
	global $adb;
	if(!vtlib_isModuleActive('PBXManager') || isPermitted('PBXManager', 'index') == 'no'){
		return false;
	}
	$sql = "select * from vtiger_asteriskextensions where userid = ?";
	$result = $adb->pquery($sql, array($id));
	if($adb->num_rows($result)>0){
		$use_asterisk = $adb->query_result($result, 0, "use_asterisk");
		$asterisk_extension = $adb->query_result($result, 0, "asterisk_extension");
		if($use_asterisk == 0 || empty($asterisk_extension)){
			return 'false';
		}else{
			return 'true';
	}
	}else{
		return 'false';
	}
}

/**
 * this function adds a record to the callhistory module
 *
 * @param string $userExtension - the extension of the current user
 * @param string $callfrom - the caller number
 * @param string $callto - the called number
 * @param string $status - the status of the call (outgoing/incoming/missed)
 * @param object $adb - the peardatabase object
  */
function addToCallHistory($userExtension, $callfrom, $callto, $status, $adb, $useCallerInfo){
	$sql = "select * from vtiger_asteriskextensions where asterisk_extension=?";
	$result = $adb->pquery($sql,array($userExtension));
	$userID = $adb->query_result($result, 0, "userid");
	if(empty($userID)) {
		// we have observed call to extension not configured in Vtiger will returns NULL
		return;
	}
	if(empty($callfrom)){
		$callfrom = "Unknown";
	}
	if(empty($callto)){
		$callto = "Unknown";
}

	if($status == 'outgoing'){
		//call is from user to record
		$sql = "select * from vtiger_asteriskextensions where asterisk_extension=?";
		$result = $adb->pquery($sql, array($callfrom));
		if($adb->num_rows($result)>0){
			$userid = $adb->query_result($result, 0, "userid");
			$callerName = getUserFullName($userid);
		}

		$receiver = $useCallerInfo;
		if(empty($receiver)){
			$receiver = "Unknown";
		}else{
			$receiver = "<a href='index.php?module=".$receiver['module']."&action=DetailView&record=".$receiver['id']."'>".$receiver['name']."</a>";
		}
	}else{
		//call is from record to user
		$sql = "select * from vtiger_asteriskextensions where asterisk_extension=?";
		$result = $adb->pquery($sql,array($callto));
		if($adb->num_rows($result)>0){
			$userid = $adb->query_result($result, 0, "userid");
			$receiver = getUserFullName($userid);
		}
		$callerName = $useCallerInfo;
		if(empty($callerName)){
			$callerName = "Unknown $callfrom";
		}else{
			$callerName = "<a href='index.php?module=".$callerName['module']."&action=DetailView&record=".$callerName['id']."'>".decode_html($callerName['name'])."</a>";
}
}

	$crmID = $adb->getUniqueID('vtiger_crmentity');
	$timeOfCall = date('Y-m-d H:i:s');

	$query = "INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,modifiedby,setype,description,createdtime,
			modifiedtime,viewedtime,status,version,presence,deleted,label) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
	$adb->pquery($query, array($crmID, $userID, $userID, 0, "PBXManager", "", $timeOfCall, $timeOfCall, NULL, NULL, 0, 1, 0, $callerName));

	$sql = "insert into vtiger_pbxmanager (pbxmanagerid,callfrom,callto,timeofcall,status)values (?,?,?,?,?)";
	$params = array($crmID, $callerName, $receiver, $timeOfCall, $status);
	$adb->pquery($sql, $params);
	return $crmID;
}
//functions for asterisk integration end

/* Function to get the related tables data
 * @param - $module - Primary module name
 * @param - $secmodule - Secondary module name
 * return Array $rel_array tables and fields to be compared are sent
 * */
function getRelationTables($module,$secmodule){
	global $adb;
	$primary_obj = CRMEntity::getInstance($module);
	$secondary_obj = CRMEntity::getInstance($secmodule);

	$ui10_query = $adb->pquery("SELECT vtiger_field.tabid AS tabid,vtiger_field.tablename AS tablename, vtiger_field.columnname AS columnname FROM vtiger_field INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid WHERE (vtiger_fieldmodulerel.module=? AND vtiger_fieldmodulerel.relmodule=?) OR (vtiger_fieldmodulerel.module=? AND vtiger_fieldmodulerel.relmodule=?)",array($module,$secmodule,$secmodule,$module));
		if($adb->num_rows($ui10_query)>0){
			$ui10_tablename = $adb->query_result($ui10_query,0,'tablename');
			$ui10_columnname = $adb->query_result($ui10_query,0,'columnname');
			$ui10_tabid = $adb->query_result($ui10_query,0,'tabid');

			if($primary_obj->table_name == $ui10_tablename){
				$reltables = array($ui10_tablename=>array("".$primary_obj->table_index."","$ui10_columnname"));
			} else if($secondary_obj->table_name == $ui10_tablename){
				$reltables = array($ui10_tablename=>array("$ui10_columnname","".$secondary_obj->table_index.""),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
			} else {
				if(isset($secondary_obj->tab_name_index[$ui10_tablename])){
					$rel_field = $secondary_obj->tab_name_index[$ui10_tablename];
					$reltables = array($ui10_tablename=>array("$ui10_columnname","$rel_field"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
				} else {
					$rel_field = $primary_obj->tab_name_index[$ui10_tablename];
					$reltables = array($ui10_tablename=>array("$rel_field","$ui10_columnname"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
				}
			}
	}else {
		if(method_exists($primary_obj,'setRelationTables')){
			$reltables = $primary_obj->setRelationTables($secmodule);
		} else {
			$reltables = '';
		}
	}
	if(is_array($reltables) && !empty($reltables)){
		$rel_array = $reltables;
	} else {
		$rel_array = array("vtiger_crmentityrel"=>array("crmid","relcrmid"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
	}
	return $rel_array;
}

/**
 * This function returns no value but handles the delete functionality of each entity.
 * Input Parameter are $module - module name, $return_module - return module name, $focus - module object, $record - entity id, $return_id - return entity id.
  */
function DeleteEntity($module,$return_module,$focus,$record,$return_id) {
	global $log;
	$log->debug("Entering DeleteEntity method ($module, $return_module, $record, $return_id)");

	if ($module != $return_module && !empty($return_module) && !empty($return_id)) {
		$focus->unlinkRelationship($record, $return_module, $return_id);
		$focus->trackUnLinkedInfo($return_module, $return_id, $module, $record);
	} else {
		$focus->trash($module, $record);
	}
	$log->debug("Exiting DeleteEntity method ...");
}

/**
 * Function to related two records of different entity types
  */
function relateEntities($focus, $sourceModule, $sourceRecordId, $destinationModule, $destinationRecordIds) {
    $db = PearDatabase::getInstance();
    $em = new VTEventsManager($db);
    $data = array('sourceModule'=>$sourceModule, 'sourceRecordId'=>$sourceRecordId,
                  'destinationModule'=>$destinationModule,'destinationRecordIds'=>$destinationRecordIds);
    $em->triggerEvent("vtiger.entity.beforerelate", $data);
	if(!is_array($destinationRecordIds)) $destinationRecordIds = Array($destinationRecordIds);
	foreach($destinationRecordIds as $destinationRecordId) {
		$focus->save_related_module($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId);
		$focus->trackLinkedInfo($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId);
	}
    
    $em->triggerEvent("vtiger.entity.afterrelate", $data);
}

/**
 * Track install/update vtlib module in current run.
 */
$_installOrUpdateVtlibModule = array();

/* Function to install Vtlib Compliant modules
 * @param - $packagename - Name of the module
 * @param - $packagepath - Complete path to the zip file of the Module
  */
  function installVtlibModule($packagename, $packagepath, $customized=false, $overwrite = true) {
	global $log, $Vtiger_Utils_Log, $_installOrUpdateVtlibModule;
	if(!file_exists($packagepath)) return;

	if (isset($_installOrUpdateVtlibModule[$packagename.$packagepath])) return;
	$_installOrUpdateVtlibModule[$packagename.$packagepath] = 'install';

	require_once('vtlib/Vtiger/Package.php');
	require_once('vtlib/Vtiger/Module.php');
	$Vtiger_Utils_Log = defined('INSTALLATION_MODE_DEBUG')? INSTALLATION_MODE_DEBUG : true;
	$package = new Vtiger_Package();

	if($package->isLanguageType($packagepath)) {
		$package = new Vtiger_Language();
		$package->import($packagepath, true);
		return;
}
	$module = $package->getModuleNameFromZip($packagepath);

	// Customization
	if($package->isLanguageType()) {
		require_once('vtlib/Vtiger/Language.php');
		$languagePack = new Vtiger_Language();
		@$languagePack->import($packagepath, true);
		return;
}
	// END

	$module_exists = false;
	$module_dir_exists = false;
	if($module == null) {
		$log->fatal("$packagename Module zipfile is not valid!");
	} else if(Vtiger_Module::getInstance($module)) {
		$log->fatal("$module already exists!");
		$module_exists = true;
	}
	if($module_exists == false) {
		$log->debug("$module - Installation starts here");
		$package->import($packagepath, $overwrite);
		$moduleInstance = Vtiger_Module::getInstance($module);
		if (empty($moduleInstance)) {
			$log->fatal("$module module installation failed!");
		}
	}
}

/* Function to update Vtlib Compliant modules
 * @param - $module - Name of the module
 * @param - $packagepath - Complete path to the zip file of the Module
 */
function updateVtlibModule($module, $packagepath, $overwrite = true) {
	global $log, $_installOrUpdateVtlibModule;
	if(!file_exists($packagepath)) return;

	if (isset($_installOrUpdateVtlibModule[$module.$packagepath])) return;
	$_installOrUpdateVtlibModule[$module.$packagepath] = 'update';

	require_once('vtlib/Vtiger/Package.php');
	require_once('vtlib/Vtiger/Module.php');
	$Vtiger_Utils_Log = defined('INSTALLATION_MODE_DEBUG')? INSTALLATION_MODE_DEBUG : true;
	$package = new Vtiger_Package();

	if($package->isLanguageType($packagepath) && $overwrite) {
		require_once('vtlib/Vtiger/Language.php');
		$languagePack = new Vtiger_Language();
		$languagePack->update(null, $packagepath, true);
		return;
	}

	if($module == null) {
		$log->fatal("Module name is invalid");
	} else {
		$moduleInstance = Vtiger_Module::getInstance($module);
		if($moduleInstance || $package->isModuleBundle($packagepath)) {
			$log->debug("$module - Module instance found - Update starts here");
			$package->update($moduleInstance, $packagepath, $overwrite);
		} else {
			$log->fatal("$module doesn't exists!");
		}
	}
}

/**
 * this function checks if a given column exists in a given table or not
 * @param string $columnName - the columnname
 * @param string $tableName - the tablename
 * @return boolean $status - true if column exists; false otherwise
 */
function columnExists($columnName, $tableName){
	global $adb;
	$columnNames = array();
	$columnNames = $adb->getColumnNames($tableName);

	if(in_array($columnName, $columnNames)){
		return true;
	}else{
		return false;
	}
}

/* To get modules list for which work flow and field formulas is permitted*/
function com_vtGetModules($adb) {
	$sql="select distinct vtiger_field.tabid, name
		from vtiger_field
		inner join vtiger_tab
			on vtiger_field.tabid=vtiger_tab.tabid
		where vtiger_field.tabid not in(9,10,16,15,29) and vtiger_tab.presence = 0 and vtiger_tab.isentitytype=1";
	$it = new SqlResultIterator($adb, $adb->pquery($sql, array()));
	$modules = array();
	foreach($it as $row) {
		if(isPermitted($row->name,'index') == "yes") {
			$modules[$row->name] = getTranslatedString($row->name);
		}
	}
	return $modules;
        }

/**
 * Function to check if a given record exists (not deleted)
 * @param integer $recordId - record id
 */
function isRecordExists($recordId) {
	global $adb;
	$query = "SELECT crmid FROM vtiger_crmentity where crmid=? AND deleted=0";
	$result = $adb->pquery($query, array($recordId));
	if ($adb->num_rows($result)) {
		return true;
	}
	return false;
}

/** Function to set date values compatible to database (YY_MM_DD)
  * @param $value -- value :: Type string
  * @returns $insert_date -- insert_date :: Type string
  */
function getValidDBInsertDateValue($value) {
	global $log;
	$log->debug("Entering getValidDBInsertDateValue(".$value.") method ...");
	$value = trim($value);
	$delim = array('/','.');
	foreach ($delim as $delimiter){
		$x = strpos($value, $delimiter);
		if($x === false) continue;
		else{
			$value=str_replace($delimiter, '-', $value);
			break;
		}
	}
	list($y,$m,$d) = explode('-',$value);
	if(strlen($y) == 1) $y = '0'.$y;
	if(strlen($m) == 1) $m = '0'.$m;
	if(strlen($d) == 1) $d = '0'.$d;
	$value = implode('-', array($y,$m,$d));

	if(strlen($y)<4){
		$insert_date = DateTimeField::convertToDBFormat($value);
	} else {
		$insert_date = $value;
	}

	if (preg_match("/^[0-9]{2,4}[-][0-1]{1,2}?[0-9]{1,2}[-][0-3]{1,2}?[0-9]{1,2}$/", $insert_date) == 0) {
		return '';
	}

	$log->debug("Exiting getValidDBInsertDateValue method ...");
	return $insert_date;
		}

function getValidDBInsertDateTimeValue($value) {
	$value = trim($value);
	$valueList = explode(' ',$value);
    //checking array count = 3 if datatime format is 12hr.
	if(is_array($valueList) && (count($valueList) == 2 || count($valueList) == 3)) {
		$dbDateValue = getValidDBInsertDateValue($valueList[0]);
		$dbTimeValue = $valueList[1];
		if(!empty($dbTimeValue) && strpos($dbTimeValue, ':') === false) {
			$dbTimeValue = $dbTimeValue.':';
		}
		$timeValueLength = strlen($dbTimeValue);
		if(!empty($dbTimeValue) &&  strrpos($dbTimeValue, ':') == ($timeValueLength-1)) {
			$dbTimeValue = $dbTimeValue.'00';
		}
		try {
			$dateTime = new DateTimeField($dbDateValue.' '.$dbTimeValue);
			return $dateTime->getDBInsertDateTimeValue();
		} catch (Exception $ex) {
			return '';
		}
	} elseif(is_array($valueList) && count($valueList) == 1) {
		return getValidDBInsertDateValue($value);
	}
}

// 「日時」フィールドのフォーマットは「Time & Date」もしくは「Date」である必要があるため判別する
function checkDatetimeFieldvalue($value){
	if($value == null){
		return false;
	}
	$format = null;
	$valueArray = explode(' ', $value);
	if(count($valueArray) == 1){
		if($value === date('H:i:s', strtotime($value))){
			return false;
		}
		$value = getValidDBInsertDateValue($value);
		if($value && $value === date('Y-m-d', strtotime($value))){
			$format = 'Y-m-d';
		}
	}elseif(count($valueArray) == 2 || count($valueArray) == 3){
		$value = getValidDBInsertDateTimeValue($value);
		if($value && $value === date('Y-m-d H:i:s', strtotime($value))){
			$format = 'Y-m-d H:i:s';
		}
	}
	if(!empty($format)){
		return array($format, date($format, strtotime($value)));
	}else{
		return false;
	}
}

/** Function to set the PHP memory limit to the specified value, if the memory limit set in the php.ini is less than the specified value
 * @param $newvalue -- Required Memory Limit
 */
function _phpset_memorylimit_MB($newvalue) {
    $current = @ini_get('memory_limit');
    if(preg_match("/(.*)M/", $current, $matches)) {
        // Check if current value is less then new value
        if($matches[1] < $newvalue) {
            @ini_set('memory_limit', "{$newvalue}M");
	}
	}
	}

/** Function to sanitize the upload file name when the file name is detected to have bad extensions
 * @param String -- $fileName - File name to be sanitized
 * @return String - Sanitized file name
 */
function sanitizeUploadFileName($fileName, $badFileExtensions) {

	$fileName = preg_replace('/\s+/', '_', $fileName);//replace space with _ in filename
	$fileName = rtrim($fileName, '\\/<>?*:"<>|');

	$fileNameParts = explode(".", $fileName);
	$countOfFileNameParts = count($fileNameParts);
	$badExtensionFound = false;

	for ($i=0;$i<$countOfFileNameParts;++$i) {
		$partOfFileName = $fileNameParts[$i];
		if(in_array(strtolower($partOfFileName), $badFileExtensions)) {
			$badExtensionFound = true;
			$fileNameParts[$i] = $partOfFileName . 'file';
	}
	}

	$newFileName = implode(".", $fileNameParts);

	if ($badExtensionFound) {
		$newFileName .= ".txt";
		}
	return $newFileName;
		}

/** Function to get the tab meta information for a given id
  * @param $tabId -- tab id :: Type integer
  * @returns $tabInfo -- array of preference name to preference value :: Type array
  */
function getTabInfo($tabId) {
	global $adb;

	$tabInfoResult = $adb->pquery('SELECT prefname, prefvalue FROM vtiger_tab_info WHERE tabid=?', array($tabId));
	$tabInfo = array();
	for($i=0; $i<$adb->num_rows($tabInfoResult); ++$i) {
		$prefName = $adb->query_result($tabInfoResult, $i, 'prefname');
		$prefValue = $adb->query_result($tabInfoResult, $i, 'prefvalue');
		$tabInfo[$prefName] = $prefValue;
}
}

/** Function to return block name
 * @param Integer -- $blockid
 * @return String - Block Name
 */
function getBlockName($blockid) {
	global $adb;

	$blockname = VTCacheUtils::lookupBlockLabelWithId($blockid);

	if(!empty($blockid) && $blockname === false){
		$block_res = $adb->pquery('SELECT blocklabel FROM vtiger_blocks WHERE blockid = ?',array($blockid));
		if($adb->num_rows($block_res)){
			$blockname = $adb->query_result($block_res,0,'blocklabel');
		} else {
			$blockname = '';
		}
		VTCacheUtils::updateBlockLabelWithId($blockname, $blockid);
	}
	return $blockname;
}

function validateAlphaNumericInput($string){
    preg_match('/^[\w _\-]+$/', $string, $matches);
    if(count($matches) == 0) {
        return false;
	}
    return true;
}

function validateServerName($string){
    preg_match('/^[\w\-\.\\/:]+$/', $string, $matches);
    if(count($matches) == 0) {
        return false;
		}
    return true;
	}

function validateEmailId($string){
    preg_match('/^[a-zA-Z0-9]+([\_\-\.]*[a-zA-Z0-9]+[\_\-]?)*@[a-zA-Z0-9]+([\_\-]?[a-zA-Z0-9]+)*\.+([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)*$/', $string, $matches);
    if(count($matches) == 0) {
        return false;
    }
		return true;
}

/**
* Function to get the approximate difference between two date time values as string
*/
function dateDiffAsString($d1, $d2) {
	global $currentModule;

	$dateDiff = dateDiff($d1, $d2);

	$years = $dateDiff['years'];
	$months = $dateDiff['months'];
	$days = $dateDiff['days'];
	$hours = $dateDiff['hours'];
	$minutes = $dateDiff['minutes'];
	$seconds = $dateDiff['seconds'];

	if($years > 0) {
		$diffString = "$years ".getTranslatedString('LBL_YEARS',$currentModule);
	} elseif($months > 0) {
		$diffString = "$months ".getTranslatedString('LBL_MONTHS',$currentModule);
	} elseif($days > 0) {
		$diffString = "$days ".getTranslatedString('LBL_DAYS',$currentModule);
	} elseif($hours > 0) {
		$diffString = "$hours ".getTranslatedString('LBL_HOURS',$currentModule);
	} elseif($minutes > 0) {
		$diffString = "$minutes ".getTranslatedString('LBL_MINUTES',$currentModule);
				} else {
		$diffString = "$seconds ".getTranslatedString('LBL_SECONDS',$currentModule);
				}
	return $diffString;
			}

function getMinimumCronFrequency() {
	global $MINIMUM_CRON_FREQUENCY;

	if(!empty($MINIMUM_CRON_FREQUENCY)) {
		return $MINIMUM_CRON_FREQUENCY;
		}
	return 15;
}

//Function returns Email related Modules
function getEmailRelatedModules() {
	global $current_user;
	$handler = vtws_getModuleHandlerFromName('Emails',$current_user);
	$meta = $handler->getMeta();
	$moduleFields = $meta->getModuleFields();
	$fieldModel = $moduleFields['parent_id'];
	$relatedModules = $fieldModel->getReferenceList();
	foreach($relatedModules as $key=>$value) {
		if($value == 'Users') {
			unset($relatedModules[$key]);
	}
}
	return $relatedModules;
}

//Get the User selected NumberOfCurrencyDecimals
function getCurrencyDecimalPlaces($user = null) {
    global $current_user;
    if (!empty($user)) {
        $currency_decimal_places = $user->no_of_currency_decimals;
    } else {
        $currency_decimal_places = $current_user->no_of_currency_decimals;
    }
    if (isset($currency_decimal_places)) {
        return $currency_decimal_places;
    } else {
        return 2;
    }
}

function getInventoryModules() {
	$inventoryModules = array('Invoice','Quotes','PurchaseOrder','SalesOrder');
	return $inventoryModules;
}

/* Function to only initialize the update of Vtlib Compliant modules
 * @param - $module - Name of the module
 * @param - $packagepath - Complete path to the zip file of the Module
 */
function initUpdateVtlibModule($module, $packagepath) {
	global $log;
	require_once('vtlib/Vtiger/Package.php');
	require_once('vtlib/Vtiger/Module.php');
	$Vtiger_Utils_Log = true;
	$package = new Vtiger_Package();

	if($module == null) {
		$log->fatal("Module name is invalid");
	} else {
		$moduleInstance = Vtiger_Module::getInstance($module);
		if($moduleInstance) {
			$log->debug("$module - Module instance found - Init Update starts here");
			$package->initUpdate($moduleInstance, $packagepath, true);
		} else {
			$log->fatal("$module doesn't exists!");
		}
	}
}


/**
 * Function to get the list of Contacts related to an activity
 * @param Integer $activityId
 * @return Array $contactsList - List of Contact ids, mapped to Contact Names
 */
function getActivityRelatedContacts($activityId) {
	$adb = PearDatabase::getInstance();

	$query = 'SELECT * FROM vtiger_cntactivityrel WHERE activityid=?';
	$result = $adb->pquery($query, array($activityId));

	$noOfContacts = $adb->num_rows($result);
	$contactsList = array();
	for ($i = 0; $i < $noOfContacts; ++$i) {
		$contactId = $adb->query_result($result, $i, 'contactid');
		$displayValueArray = getEntityName('Contacts', $contactId);
		if (!empty($displayValueArray)) {
			foreach ($displayValueArray as $key => $field_value) {
				$contact_name = $field_value;
			}
		} else {
			$contact_name='';
		}
		$contactsList[$contactId] = $contact_name;
	}
	return $contactsList;
}

function isLeadConverted($leadId) {
	$adb = PearDatabase::getInstance();

	$query = 'SELECT converted FROM vtiger_leaddetails WHERE converted = 1 AND leadid=?';
	$params = array($leadId);

	$result = $adb->pquery($query, $params);

	if($result && $adb->num_rows($result) > 0) {
		return true;
	}
	return false;
}

function getSelectedRecords($input,$module,$idstring,$excludedRecords) {
	global $current_user, $adb;

	if($idstring == 'relatedListSelectAll') {

		$recordid = vtlib_purify($input['recordid']);
		if($module == 'Accounts') {
			$result = getCampaignAccountIds($recordid);
		}
		if($module == 'Contacts') {
			$result = getCampaignContactIds($recordid);
		}
		if($module == 'Leads') {
			$result = getCampaignLeadIds($recordid);
		}
		$storearray = array();
		for ($i = 0; $i < $adb->num_rows($result); $i++) {
			$storearray[] = $adb->query_result($result, $i, 'id');
		}

		$excludedRecords=explode(';',$excludedRecords);
		$storearray=array_diff($storearray,$excludedRecords);

	} else if($module == 'Documents') {

		if($input['selectallmode']=='true') {
			$result = getSelectAllQuery($input,$module);
			$storearray = array();
			$focus = CRMEntity::getInstance($module);

			for ($i = 0; $i < $adb->num_rows($result); $i++) {
				$storearray[] = $adb->query_result($result, $i, $focus->table_index);
			}

			$excludedRecords = explode(';',$excludedRecords);
			$storearray = array_diff($storearray,$excludedRecords);
			if($idstring != 'all') {
				$storearray = array_merge($storearray,explode(';',$idstring));
			}
			$storearray = array_unique($storearray);

		} else {
			$storearray = explode(";",$idstring);
		}

	} elseif($idstring == 'all') {

		$result = getSelectAllQuery($input,$module);
		$storearray = array();
		$focus = CRMEntity::getInstance($module);

		for ($i = 0; $i < $adb->num_rows($result); $i++) {
			$storearray[] = $adb->query_result($result, $i, $focus->table_index);
		}

		$excludedRecords = explode(';',$excludedRecords);
		$storearray = array_diff($storearray,$excludedRecords);

	} else {
		$storearray = explode(";",$idstring);
	}

	return $storearray;
}

function getSelectAllQuery($input,$module) {
	global $adb,$current_user;

	$viewid = vtlib_purify($input['viewname']);

	if($module == "Calendar") {
		$listquery = getListQuery($module);
		$oCustomView = new CustomView($module);
		$query = $oCustomView->getModifiedCvListQuery($viewid,$listquery,$module);
		$where = '';
		if($input['query'] == 'true') {
			list($where, $ustring) = split("#@@#",getWhereCondition($module, $input));
			if(isset($where) && $where != '') {
				$query .= " AND " .$where;
			}
		}
	} else {
		$queryGenerator = new QueryGenerator($module, $current_user);
		$queryGenerator->initForCustomViewById($viewid);

		if($input['query'] == 'true') {
			$queryGenerator->addUserSearchConditions($input);
		}

		$queryGenerator->setFields(array('id'));
		$query = $queryGenerator->getQuery();

		if($module == 'Documents') {
			$folderid = vtlib_purify($input['folderidstring']);
			$folderid = str_replace(';', ',', $folderid);
			$query .= " AND vtiger_notes.folderid in (".$folderid.")";
		}
	}

	$result = $adb->pquery($query, array());
	return $result;
}

function getCampaignAccountIds($id) {
	global $adb;
	$sql="SELECT vtiger_account.accountid as id FROM vtiger_account
		INNER JOIN vtiger_campaignaccountrel ON vtiger_campaignaccountrel.accountid = vtiger_account.accountid
		LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid
		WHERE vtiger_campaignaccountrel.campaignid = ? AND vtiger_crmentity.deleted=0";
	$result = $adb->pquery($sql, array($id));
	return $result;
}

function getCampaignContactIds($id) {
	global $adb;
	$sql="SELECT vtiger_contactdetails.contactid as id FROM vtiger_contactdetails
		INNER JOIN vtiger_campaigncontrel ON vtiger_campaigncontrel.contactid = vtiger_contactdetails.contactid
		LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
		WHERE vtiger_campaigncontrel.campaignid = ? AND vtiger_crmentity.deleted=0";
	$result = $adb->pquery($sql, array($id));
	return $result;
}

function getCampaignLeadIds($id) {
	global $adb;
	$sql="SELECT vtiger_leaddetails.leadid as id FROM vtiger_leaddetails
		INNER JOIN vtiger_campaignleadrel ON vtiger_campaignleadrel.leadid = vtiger_leaddetails.leadid
		LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
		WHERE vtiger_campaignleadrel.campaignid = ? AND vtiger_crmentity.deleted=0";
	$result = $adb->pquery($sql, array($id));
	return $result;
}

/** Function to get the difference between 2 datetime strings or millisecond values */
function dateDiff($d1, $d2){
	$d1 = (is_string($d1) ? strtotime($d1) : $d1);
	$d2 = (is_string($d2) ? strtotime($d2) : $d2);

	$diffSecs = abs($d1 - $d2);
	$baseYear = min(date("Y", $d1), date("Y", $d2));
	$diff = mktime(0, 0, $diffSecs, 1, 1, $baseYear);
	return array(
		"years" => date("Y", $diff) - $baseYear,
		"months_total" => (date("Y", $diff) - $baseYear) * 12 + date("n", $diff) - 1,
		"months" => date("n", $diff) - 1,
		"days_total" => floor($diffSecs / (3600 * 24)),
		"days" => date("j", $diff) - 1,
		"hours_total" => floor($diffSecs / 3600),
		"hours" => date("G", $diff),
		"minutes_total" => floor($diffSecs / 60),
		"minutes" => (int) date("i", $diff),
		"seconds_total" => $diffSecs,
		"seconds" => (int) date("s", $diff)
	);
}

function getExportRecordIds($moduleName, $viewid, $input) {
	global $adb, $current_user, $list_max_entries_per_page;

	$idstring = vtlib_purify($input['idstring']);
	$export_data = vtlib_purify($input['export_data']);

	if (in_array($moduleName, getInventoryModules()) && $export_data == 'currentpage') {
		$queryGenerator = new QueryGenerator($moduleName, $current_user);
		$queryGenerator->initForCustomViewById($viewid);

		if($input['query'] == 'true') {
			$queryGenerator->addUserSearchConditions($input);
		}

		$queryGenerator->setFields(array('id'));
		$query = $queryGenerator->getQuery();
		$current_page = ListViewSession::getCurrentPage($moduleName,$viewid);
		$limit_start_rec = ($current_page - 1) * $list_max_entries_per_page;
		if ($limit_start_rec < 0) $limit_start_rec = 0;
		$query .= ' LIMIT '.$limit_start_rec.','.$list_max_entries_per_page;

		$result = $adb->pquery($query, array());
		$idstring = array();
		$focus = CRMEntity::getInstance($moduleName);
		for ($i = 0; $i < $adb->num_rows($result); $i++) {
			$idstring[] = $adb->query_result($result, $i, $focus->table_index);
		}
		$idstring = implode(';',$idstring);
		$export_data = 'selecteddata';
	}
	return $idstring. '#@@#' .$export_data;
}

/**
 * Function to get combinations of string from Array
 * @param <Array> $array
 * @param <String> $tempString
 * @return <Array>
 */
function getCombinations($array, $tempString = '') {
	for ($i=0; $i<count($array); $i++) {
		$splicedArray = $array;
		$element = array_splice($splicedArray, $i, 1);// removes and returns the i'th element
		if (count($splicedArray) > 0) {
			 if(!is_array($result)) {
				 $result = array();
			 }
			 $result = array_merge($result, getCombinations($splicedArray, $tempString. ' |##| ' .$element[0]));
		} else {
			return array($tempString. ' |##| ' . $element[0]);
		}
	}
	return $result;
}

function getCompanyDetails() {
	global $adb;

	$sql="select * from vtiger_organizationdetails";
	$result = $adb->pquery($sql, array());

	$companyDetails = array();
	$companyDetails['companyname'] = $adb->query_result($result,0,'organizationname');
	$companyDetails['website'] = $adb->query_result($result,0,'website');
	$companyDetails['address'] = $adb->query_result($result,0,'address');
	$companyDetails['city'] = $adb->query_result($result,0,'city');
	$companyDetails['state'] = $adb->query_result($result,0,'state');
	$companyDetails['country'] = $adb->query_result($result,0,'country');
	$companyDetails['phone'] = $adb->query_result($result,0,'phone');
	$companyDetails['fax'] = $adb->query_result($result,0,'fax');
	$companyDetails['logoname'] = $adb->query_result($result,0,'logoname');

	return $companyDetails;
}

/** call back function to change the array values in to lower case */
function lower_array(&$string){
	$string = strtolower(trim($string));
}

if (!function_exists('get_magic_quotes_runtime')) { function get_magic_quotes_runtime() { return false; } }
if (!function_exists('set_magic_quotes_runtime')) { function set_magic_quotes_runtime($flag) {} }

/** 
 * Function to escape backslash (\ to \\) in a string
 * @param string $value String to be escaped
 * @return string escaped string
 */
function escapeSlashes($value) {
    return str_replace('\\', '\\\\', $value);
}

/** Function to get a user id or group id for a given entity
 *  @param $record -- entity id :: Type integer
 *  @returns reports to id <int>
 */
function getRecordOwnerReportsToId($record) {
    global $adb;
    $reportsToId = false;
    $ownerArr = getRecordOwnerId($record);
    if ($ownerArr['Users']) {
        $query = "SELECT reports_to_id FROM vtiger_users WHERE id = ?";
        $result = $adb->pquery($query, array($ownerArr['Users']));
        if ($adb->num_rows($result) > 0) {
            $reportsToId = $adb->query_result($result, 0, 'reports_to_id');
        }
    }
    return $reportsToId;
}

/**
 * Function to get last week range of give date
 * @param type $date
 * @return array($timestamps)
 */
function getLastWeekRange($date) {
	$given_time = strtotime($date);
	$day = gmdate('D', $given_time);

	if ($day == 'Sun') {
		$thissunday = strtotime("this sunday", $given_time);
	} else {
		$thissunday = strtotime("-1 week sunday", $given_time);
	}

	$lastSunday = $thissunday - (7 * 24 * 60 * 60); // 7 days; 24 hours; 60 mins; 60secs
	$lastSaturday = $lastSunday + (6 * 24 * 60 * 60);

	$lastWeekRange = array("start" => $lastSunday, "end" => $lastSaturday);
	return $lastWeekRange;
}

/**
 * Function to get current week range of given date
 * @param type $date
 * @return array($timestamps)
 */
function getCurrentWeekRange($date) {
	$given_time = strtotime($date);
	$day = gmdate('D', $given_time);

	if ($day == 'Sun') {
		$thissunday = $given_time;
	} else {
		$thissunday = strtotime("-1 week sunday", $given_time);
	}
	$thisSaturday = $thissunday + (6 * 24 * 60 * 60);

	$currentWeekRange = array("start" => $thissunday, "end" => $thisSaturday);
	return $currentWeekRange;
}

/**
 * Function to get a group id for a given entity
 * @param $record -- entity id :: Type integer
 * @returns group id <int>
 */
function getRecordGroupId($record) {
	global $adb;
	// Look at cache first for information
	$groupId = VTCacheUtils::lookupRecordGroup($record);

	if ($groupId === false) {
		$query = "SELECT smgroupid FROM vtiger_crmentity WHERE crmid = ?";
		$result = $adb->pquery($query, array($record));
		if ($adb->num_rows($result) > 0) {
			$groupId = $adb->query_result($result, 0, 'smgroupid');
			// Update cache forupdateRecordGroup re-use
			VTCacheUtils::updateRecordGroup($record, $groupId);
		}
	}

	return $groupId;
}

/**
 * Function to delete record from $_SESSION[$moduleName.'_DetailView_Navigation'.$cvId]
 */
function deleteRecordFromDetailViewNavigationRecords($recordId, $cvId, $moduleName) {
	$recordNavigationInfo = Zend_Json::decode($_SESSION[$moduleName . '_DetailView_Navigation' . $cvId]);
	if (!empty($recordNavigationInfo) && (count($recordNavigationInfo) != 0)) {
		foreach ($recordNavigationInfo as $key => $recordIdList) {
			$recordIdList = array_diff($recordIdList, array($recordId));
			$recordNavigationInfo[$key] = $recordIdList;
		}
		$_SESSION[$moduleName . '_DetailView_Navigation' . $cvId] = Zend_Json::encode($recordNavigationInfo);
	}
}

function sendMailToUserOnDuplicationPrevention($moduleName, $fieldData, $mailBody, $userModel = '') {
	if (!$userModel) {
		$userId = $_SESSION['authenticated_user_id'];
		if ($userId) {
			$userModel = Users_Record_Model::getInstanceFromPreferenceFile($userId);
		} else {
			$userModel = Users_Record_Model::getCurrentUserModel();
		}
	}

	$mailer = Emails_Mailer_Model::getInstance();
	$mailer->IsHTML(true);

	$emailRecordModel = Emails_Record_Model::getCleanInstance('Emails');
	$fromEmail = $emailRecordModel->getFromEmailAddress();
	$replyTo = $emailRecordModel->getReplyToEmail();
	$userName = $userModel->getName();

	$mailer->ConfigSenderInfo($fromEmail, $userName, $replyTo);
	$mailer->Subject = vtranslate('LBL_VTIGER_NOTIFICATION');
	$body = $mailBody;

	$body .= '<br>';
	$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
	$fieldModels = $moduleModel->getFields();
	foreach ($fieldModels as $fieldName => $fieldModel) {
		if ($fieldModel->isUniqueField() && $fieldModel->isViewable()) {
			$fieldValue = $fieldData[$fieldName];

			switch($fieldModel->getFieldDataType()) {
				case 'reference'		:	list($refModuleId, $refRecordId) = vtws_getIdComponents($fieldValue);
											$fieldValue = Vtiger_Functions::getCRMRecordLabel($refRecordId);
											break;
				case 'date'				:
				case 'datetime'			:
				case 'currency'			:
				case 'currencyList'		:
				case 'documentsFolder'	:
				case 'multipicklist'	:	if ($fieldValue) {
												$fieldValue = $fieldModel->getDisplayValue($fieldValue);
											}
											break;
			}

			$fieldLabel = $fieldModel->get('label');
			$body .= '<br>'.vtranslate($fieldLabel, $moduleName)." : $fieldValue<br>";
		}
	}
	$body .= '<br>';

	if ($userModel->isAdminUser()) {
		$siteURL = vglobal('site_URL');
		$url = "$siteURL/index.php?parent=Settings&module=LayoutEditor&sourceModule=$moduleName&mode=showDuplicationHandling";
		$here = '<a href="'.$url.'" target="_blank">'.vtranslate('LBL_CLICK_HERE', $moduleName).'</a>';
		$body .= vtranslate('LBL_DUPLICATION_FAILURE_FOR_ADMIN', $moduleName, $here);
	} else {
		$body .= vtranslate('LBL_DUPLICATION_FAILURE_FOR_NON_ADMIN', $moduleName);
	}

	$mailer->Body = $body;
	$mailer->AddAddress($userModel->get('email1'), $userName);
	$mailer->Send(false);
}

function getDuplicatesPreventionMessage($moduleName, $duplicateRecordsList) {
	$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
	$fieldModels = $moduleModel->getFields();

	$recordId = reset($duplicateRecordsList);
	$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
	$recordData = $recordModel->getData();

	$uniqueFields = array();
	foreach ($fieldModels as $fieldName => $fieldModel) {
		$fieldDataType = $fieldModel->getFieldDataType();
		$fieldValue = $recordData[$fieldName];

		if ($fieldDataType === 'reference' && $fieldValue == 0) {
			$fieldValue = '';
		}

		if ($fieldModel->isUniqueField() && $fieldModel->isViewable() && $fieldValue !== '' && $fieldValue !== NULL) {
			$uniqueFields[] = $fieldModel;
		}
	}

	$fieldsString = '';
	$uniqueFieldsCount = count($uniqueFields);
	for($i=0; $i<$uniqueFieldsCount; $i++) {
		$fieldModel = $uniqueFields[$i];
		$fieldLabel = $fieldModel->get('label');
		$fieldsString .= vtranslate($fieldLabel, $moduleName);

		if ($uniqueFieldsCount != 1 && $i == ($uniqueFieldsCount-2)) {
			$fieldsString .= ' '.vtranslate('LBL_AND', $moduleName).' ';
		} else if ($i != ($uniqueFieldsCount-1)) {
			$fieldsString .= ', ';
		}
	}
	$fieldsString = rtrim($fieldsString, ',');

	$singleModuleName = vtranslate('SINGLE_'.$moduleName, $moduleName);
	$translatedModuleName = $singleModuleName;
	$duplicateRecordsCount = count($duplicateRecordsList);
	if ($duplicateRecordsCount > 1) {
		$translatedModuleName = vtranslate($moduleName, $moduleName);
	}
	$message = vtranslate('LBL_DUPLICATES_FOUND_MESSAGE', $moduleName, $singleModuleName, $translatedModuleName, $fieldsString).' ';

	$currentUserModel = Users_Record_Model::getCurrentUserModel();
	if ($currentUserModel->isAdminUser()) {
		$url = "index.php?parent=Settings&module=LayoutEditor&sourceModule=$moduleName&mode=showDuplicationHandling";
		$here = '<a href="'.$url.'" target="_blank" style="color:#15c !important">'.vtranslate('LBL_CLICK_HERE', $moduleName).'</a>';
		$message .= vtranslate('LBL_DUPLICATION_FAILURE_FOR_ADMIN', $moduleName, $here);
	} else {
		$message .= vtranslate('LBL_DUPLICATION_FAILURE_FOR_NON_ADMIN', $moduleName);
	}

	$message .= '<br><br>';
    $message .= vtranslate('LBL_DUPLICATE_RECORD_LISTS', $moduleName, $singleModuleName).'<br>';
	for ($i=0; $i<$duplicateRecordsCount && $i<5; $i++) {
		$dupliRecordId = $duplicateRecordsList[$i];
		$dupliRecordModel = new Vtiger_Record_Model();
		$dupliRecordModel->setId($dupliRecordId)->setModuleFromInstance($moduleModel);
		$message .= '<a href="'.$dupliRecordModel->getDetailViewUrl().'" target="_blank" style="color:#15c !important">'.Vtiger_Functions::getCRMRecordLabel($dupliRecordId).'</a><br>';
	}

	if ($duplicateRecordsCount === 6) {
		$searchParams = array();
		foreach ($uniqueFields as $fieldModel) {
			$fieldName = $fieldModel->getName();
			$fieldValue = $recordData[$fieldName];
			$fieldDataType = $fieldModel->getFieldDataType();
			switch($fieldDataType) {
				case 'reference'		:	$fieldValue = Vtiger_Functions::getCRMRecordLabel($fieldValue);
											break;
				case 'date'				:
				case 'datetime'			:
				case 'currency'			:
				case 'currencyList'		:
				case 'documentsFolder'	:
				case 'multipicklist'	:	if ($fieldValue) {
												$fieldValue = $fieldModel->getDisplayValue($fieldValue);
											}
											break;
			}

			$comparator = 'e';
			if (in_array($fieldDataType, array('date', 'datetime'))) {
				$comparator = 'bw';
				$fieldValue = "$fieldValue,$fieldValue";
			}
			$searchParams[] = array($fieldName, $comparator, $fieldValue);
		}

		$listViewUrl = $moduleModel->getListViewUrl().'&search_params='.json_encode(array($searchParams));
		$message .= "<a href='$listViewUrl' target='_blank' style='color:#15c !important'>+".  strtolower(vtranslate('LBL_MORE', $moduleName)).'</a>';
	}

	return $message;
}

?>
