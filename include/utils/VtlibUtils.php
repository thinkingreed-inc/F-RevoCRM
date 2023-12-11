<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/*
 * Check for image existence in themes orelse
 * use the common one.
 */
// Let us create cache to improve performance
if(!isset($__cache_vtiger_imagepath)) {
	$__cache_vtiger_imagepath = Array();
}
function vtiger_imageurl($imagename, $themename) {
	global $__cache_vtiger_imagepath;
	if($__cache_vtiger_imagepath[$imagename]) {
		$imagepath = $__cache_vtiger_imagepath[$imagename];
	} else {
		$imagepath = false;
		// Check in theme specific folder
		if(file_exists("themes/$themename/images/$imagename")) {
			$imagepath =  "themes/$themename/images/$imagename";
		} else if(file_exists("themes/images/$imagename")) {
			// Search in common image folder
			$imagepath = "themes/images/$imagename";
		} else {
			// Not found anywhere? Return whatever is sent
			$imagepath = $imagename;
		}
		$__cache_vtiger_imagepath[$imagename] = $imagepath;
	}
	return $imagepath;
}

/**
 * Get module name by id.
 */
function vtlib_getModuleNameById($tabid) {
	global $adb;
	$sqlresult = $adb->pquery("SELECT name FROM vtiger_tab WHERE tabid = ?",array($tabid));
	if($adb->num_rows($sqlresult)) return $adb->query_result($sqlresult, 0, 'name');
	return null;
}

/**
 * Get module names for which sharing access can be controlled.
 * NOTE: Ignore the standard modules which is already handled.
 */
function vtlib_getModuleNameForSharing() {
	global $adb;
	$std_modules = array('Calendar','Leads','Accounts','Contacts','Potentials',
			'HelpDesk','Campaigns','Quotes','PurchaseOrder','SalesOrder','Invoice','Events');
	$modulesList = getSharingModuleList($std_modules);
	return $modulesList;
}

/**
 * Cache the module active information for performance
 */
$__cache_module_activeinfo = Array();

/**
 * Fetch module active information at one shot, but return all the information fetched.
 */
function vtlib_prefetchModuleActiveInfo($force = true) {
	global $__cache_module_activeinfo;

	// Look up if cache has information
	$tabrows = VTCacheUtils::lookupAllTabsInfo();

	// Initialize from DB if cache information is not available or force flag is set
	if($tabrows === false || $force) {
		global $adb;
		$tabres = $adb->pquery("SELECT * FROM vtiger_tab", array());
		$tabrows = array();
		if($tabres) {
			while($tabresrow = $adb->fetch_array($tabres)) {
				$tabrows[] = $tabresrow;
				$__cache_module_activeinfo[$tabresrow['name']] = $tabresrow['presence'];
			}
			// Update cache for further re-use
			VTCacheUtils::updateAllTabsInfo($tabrows);
		}
	}

	return $tabrows;
}

/**
 * Check if module is set active (or enabled)
 */
function vtlib_isModuleActive($module) {
	global $adb, $__cache_module_activeinfo;

	if(in_array($module, vtlib_moduleAlwaysActive())){
		return true;
	}

	if(!isset($__cache_module_activeinfo[$module])) {
		include 'tabdata.php';
		$tabId = $tab_info_array[$module];
		$presence = $tab_seq_array[$tabId];
		$__cache_module_activeinfo[$module] = $presence;
	} else {
		$presence = $__cache_module_activeinfo[$module];
	}

	$active = false;
	//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7991
	if($presence === 0 || $presence==='0') $active = true; 

	return $active;
}

/**
 * Recreate user privileges files.
 */
function vtlib_RecreateUserPrivilegeFiles() {
	global $adb;
	$userres = $adb->pquery('SELECT id FROM vtiger_users WHERE deleted = 0', array());
	if($userres && $adb->num_rows($userres)) {
		while($userrow = $adb->fetch_array($userres)) {
			createUserPrivilegesfile($userrow['id']);
		}
	}
}

/**
 * Get list module names which are always active (cannot be disabled)
 */
function vtlib_moduleAlwaysActive() {
	$modules = Array (
		'Administration', 'CustomView', 'Settings', 'Users', 'Migration',
		'Utilities', 'uploads', 'Import', 'System', 'com_vtiger_workflow', 'PickList'
	);
	return $modules;
}

/**
 * Toggle the module (enable/disable)
 */
function vtlib_toggleModuleAccess($modules, $enable_disable) {
	global $adb, $__cache_module_activeinfo;

	include_once('vtlib/Vtiger/Module.php');

	if(is_string($modules)) $modules = array($modules);
	$event_type = false;

	if($enable_disable === true) {
		$enable_disable = 0;
		$event_type = Vtiger_Module::EVENT_MODULE_ENABLED;
	} else if($enable_disable === false) {
		$enable_disable = 1;
		$event_type = Vtiger_Module::EVENT_MODULE_DISABLED;
        //Update default landing page to dashboard if module is disabled.
        $adb->pquery('UPDATE vtiger_users SET defaultlandingpage = ? WHERE defaultlandingpage IN(' . generateQuestionMarks($modules) . ')', array_merge(array('Home'), $modules));
	}

	$checkResult = $adb->pquery('SELECT name FROM vtiger_tab WHERE name IN ('. generateQuestionMarks($modules) .')', array($modules));
	$rows = $adb->num_rows($checkResult);
	for($i=0; $i<$rows; $i++) {
		$existingModules[] = $adb->query_result($checkResult, $i, 'name');
	}

	foreach($modules as $module) {
		if (in_array($module, $existingModules)) { // check if module exists then only update and trigger events
			$adb->pquery("UPDATE vtiger_tab set presence = ? WHERE name = ?", array($enable_disable, $module));
			$__cache_module_activeinfo[$module] = $enable_disable;
			Vtiger_Module::fireEvent($module, $event_type);
			Vtiger_Cache::flushModuleCache($module);
		}
	}

	create_tab_data_file();
	create_parenttab_data_file();

	// UserPrivilege file needs to be regenerated if module state is changed from
	// vtiger 5.1.0 onwards
	global $vtiger_current_version;
	if(version_compare($vtiger_current_version, '5.0.4', '>')) {
		vtlib_RecreateUserPrivilegeFiles();
	}
}

/**
 * Get list of module with current status which can be controlled.
 */
function vtlib_getToggleModuleInfo() {
	global $adb;

	$modinfo = Array();

	$sqlresult = $adb->pquery("SELECT name, presence, customized, isentitytype FROM vtiger_tab WHERE name NOT IN ('Users','Home') AND presence IN (0,1) ORDER BY name", array());
	$num_rows  = $adb->num_rows($sqlresult);
	for($idx = 0; $idx < $num_rows; ++$idx) {
		$module = $adb->query_result($sqlresult, $idx, 'name');
		$presence=$adb->query_result($sqlresult, $idx, 'presence');
		$customized=$adb->query_result($sqlresult, $idx, 'customized');
		$isentitytype=$adb->query_result($sqlresult, $idx, 'isentitytype');
		$hassettings=file_exists("modules/$module/Settings.php");

		$modinfo[$module] = Array( 'customized'=>$customized, 'presence'=>$presence, 'hassettings'=>$hassettings, 'isentitytype' => $isentitytype );
	}
	return $modinfo;
}

/**
 * Get list of language and its current status.
 */
function vtlib_getToggleLanguageInfo() {
	global $adb;

	// The table might not exists!
	$old_dieOnError = $adb->dieOnError;
	$adb->dieOnError = false;

	$langinfo = Array();
	$sqlresult = $adb->pquery("SELECT * FROM vtiger_language", array());
	if($sqlresult) {
		for($idx = 0; $idx < $adb->num_rows($sqlresult); ++$idx) {
			$row = $adb->fetch_array($sqlresult);
			$langinfo[$row['prefix']] = Array( 'label'=>$row['label'], 'active'=>$row['active'] );
		}
	}
	$adb->dieOnError = $old_dieOnError;
	return $langinfo;
}

/**
 * Toggle the language (enable/disable)
 */
function vtlib_toggleLanguageAccess($langprefix, $enable_disable) {
	global $adb;

	// The table might not exists!
	$old_dieOnError = $adb->dieOnError;
	$adb->dieOnError = false;

	if($enable_disable === true) $enable_disable = 1;
	else if($enable_disable === false) $enable_disable = 0;

	$adb->pquery('UPDATE vtiger_language set active = ? WHERE prefix = ?', Array($enable_disable, $langprefix));

	$adb->dieOnError = $old_dieOnError;
}

/**
 * Get help information set for the module fields.
 */
function vtlib_getFieldHelpInfo($module) {
	global $adb;
	$fieldhelpinfo = Array();
	if(in_array('helpinfo', $adb->getColumnNames('vtiger_field'))) {
		$result = $adb->pquery('SELECT fieldname,helpinfo FROM vtiger_field WHERE tabid=?', Array(getTabid($module)));
		if($result && $adb->num_rows($result)) {
			while($fieldrow = $adb->fetch_array($result)) {
				$helpinfo = decode_html($fieldrow['helpinfo']);
				if(!empty($helpinfo)) {
					$fieldhelpinfo[$fieldrow['fieldname']] = getTranslatedString($helpinfo, $module);
				}
			}
		}
	}
	return $fieldhelpinfo;
}

/**
 * Setup mandatory (requried) module variable values in the module class.
 */
function vtlib_setup_modulevars($module, $focus) {
	if($module == 'Events') $module='Calendar';

	$checkfor = Array('table_name', 'table_index', 'related_tables', 'popup_fields', 'IsCustomModule');
	foreach($checkfor as $check) {
		if(!isset($focus->$check)) $focus->$check = __vtlib_get_modulevar_value($module, $check);
	}
}
function __vtlib_get_modulevar_value($module, $varname) {
	$mod_var_mapping =
		Array(
			'Accounts' =>
			Array(
				'IsCustomModule'=>false,
				'table_name'  => 'vtiger_account',
				'table_index' => 'accountid',
				// related_tables variable should define the association (relation) between dependent tables
				// FORMAT: related_tablename => Array ( related_tablename_column[, base_tablename, base_tablename_column] )
				// Here base_tablename_column should establish relation with related_tablename_column
				// NOTE: If base_tablename and base_tablename_column are not specified, it will default to modules (table_name, related_tablename_column)
				'related_tables' => Array(
					'vtiger_accountbillads' => Array ('accountaddressid', 'vtiger_account', 'accountid'),
					'vtiger_accountshipads' => Array ('accountaddressid', 'vtiger_account', 'accountid'),
					'vtiger_accountscf' => Array ('accountid', 'vtiger_account', 'accountid'),
				),
				'popup_fields' => Array('accountname'), // TODO: Add this initialization to all the standard module
			),
			'Contacts' =>
			Array(
				'IsCustomModule'=>false,
				'table_name'  => 'vtiger_contactdetails',
				'table_index' => 'contactid',
				'related_tables'=> Array( 
					'vtiger_account' => Array ('accountid' ),
					//REVIEW: Added these tables for displaying the data into relatedlist (based on configurable fields)
					'vtiger_contactaddress' => Array('contactaddressid', 'vtiger_contactdetails', 'contactid'),
					'vtiger_contactsubdetails' => Array('contactsubscriptionid', 'vtiger_contactdetails', 'contactid'),
					'vtiger_customerdetails' => Array('customerid', 'vtiger_contactdetails', 'contactid'),
					'vtiger_contactscf' => Array('contactid', 'vtiger_contactdetails', 'contactid')
					),
				'popup_fields' => Array ('lastname'),
			),
			'Leads' =>
			Array(
				'IsCustomModule'=>false,
				'table_name'  => 'vtiger_leaddetails',
				'table_index' => 'leadid',
				'related_tables' => Array (
					'vtiger_leadsubdetails' => Array ( 'leadsubscriptionid', 'vtiger_leaddetails', 'leadid' ),
					'vtiger_leadaddress'    => Array ( 'leadaddressid', 'vtiger_leaddetails', 'leadid' ),
					'vtiger_leadscf'    => Array ( 'leadid', 'vtiger_leaddetails', 'leadid' ),
				),
				'popup_fields'=> Array ('lastname'),
			),
			'Campaigns' =>
			Array(
				'IsCustomModule'=>false,
				'table_name'  => 'vtiger_campaign',
				'table_index' => 'campaignid',
				'popup_fields' => Array ('campaignname'),
			),
			'Potentials' =>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_potential',
				'table_index'=> 'potentialid',
				// NOTE: UIType 10 is being used instead of direct relationship from 5.1.0
				//'related_tables' => Array ('vtiger_account' => Array('accountid')),
				'popup_fields'=> Array('potentialname'),
				'related_tables' => Array (
					'vtiger_potentialscf'    => Array ( 'potentialid', 'vtiger_potential', 'potentialid' ),
				),
			),
			'Quotes' =>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_quotes',
				'table_index'=> 'quoteid',
				'related_tables' => Array (
					'vtiger_quotescf' => array('quoteid', 'vtiger_quotes', 'quoteid'),
					'vtiger_account' => Array('accountid')
				),
				'popup_fields'=>Array('subject'),
			),
			'SalesOrder'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_salesorder',
				'table_index'=> 'salesorderid',
				'related_tables'=> Array (
					'vtiger_salesordercf' => array('salesorderid', 'vtiger_salesorder', 'salesorderid'),
					'vtiger_account' => Array('accountid')
				),
				'popup_fields'=>Array('subject'),
			),
			'PurchaseOrder'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_purchaseorder',
				'table_index'=> 'purchaseorderid',
				'related_tables'=> Array (
					'vtiger_purchaseordercf' => Array('purchaseorderid','vtiger_purchaseorder','purchaseorderid'),
					'vtiger_poshipads' => Array('poshipaddressid','vtiger_purchaseorder','purchaseorderid'),
					'vtiger_pobillads' => Array('pobilladdressid','vtiger_purchaseorder','purchaseorderid'),
				),
				'popup_fields'=>Array('subject'),
			),
			'Invoice'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_invoice',
				'table_index'=> 'invoiceid',
				'popup_fields'=> Array('subject'),
				'related_tables'=> Array( 
					'vtiger_invoicecf' => Array('invoiceid', 'vtiger_invoice', 'invoiceid'),
					'vtiger_invoiceshipads' => Array('invoiceshipaddressid','vtiger_invoice','invoiceid'),
					'vtiger_invoicebillads' => Array('invoicebilladdressid','vtiger_invoice','invoiceid'),
					),
			),
			'HelpDesk'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_troubletickets',
				'table_index'=> 'ticketid',
				'related_tables'=> Array ('vtiger_ticketcf' => Array('ticketid')),
				'popup_fields'=> Array('ticket_title')
			),
			'Faq'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_faq',
				'table_index'=> 'id',
				'related_tables'=> Array ('vtiger_faqcf' => Array('faqid', 'vtiger_faq', 'id'))
			),
			'Documents'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_notes',
				'table_index'=> 'notesid',
				'related_tables' => Array(
					'vtiger_notescf' => Array('notesid', 'vtiger_notes', 'notesid')
				),
			),
			'Products'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_products',
				'table_index'=> 'productid',
				'related_tables' => Array(
					'vtiger_productcf' => Array('productid')
				),
				'popup_fields'=> Array('productname'),
			),
			'PriceBooks'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_pricebook',
				'table_index'=> 'pricebookid',
			),
			'Vendors'=>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_vendor',
				'table_index'=> 'vendorid',
				'popup_fields'=>Array('vendorname'),
				'related_tables'=> Array( 
					'vtiger_vendorcf' => Array('vendorid', 'vtiger_vendor', 'vendorid')
					),
			),
			'Project' => 
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_project',
				'table_index'=> 'projectid',
				'related_tables'=> Array( 
					'vtiger_projectcf' => Array('projectid', 'vtiger_project', 'projectid')
					),
			),
			'ProjectMilestone' =>
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_projectmilestone',
				'table_index'=> 'projectmilestoneid',
				'related_tables'=> Array( 
					'vtiger_projectmilestonecf' => Array('projectmilestoneid', 'vtiger_projectmilestone', 'projectmilestoneid')
					),
			),
			'ProjectTask' => 
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_projecttask',
				'table_index'=> 'projecttaskid',
				'related_tables'=> Array( 
					'vtiger_projecttaskcf' => Array('projecttaskid', 'vtiger_projecttask', 'projecttaskid')
					),
			),
			'Services' => 
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_service',
				'table_index'=> 'serviceid',
				'related_tables'=> Array( 
					'vtiger_servicecf' => Array('serviceid')
					),
			),
			'ServiceContracts' => 
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_servicecontracts',
				'table_index'=> 'servicecontractsid',
				'related_tables'=> Array( 
					'vtiger_servicecontractscf' => Array('servicecontractsid')
					),
			),
			'Assets' => 
			Array(
				'IsCustomModule'=>false,
				'table_name' => 'vtiger_assets',
				'table_index'=> 'assetsid',
				'related_tables'=> Array( 
					'vtiger_assetscf' => Array('assetsid')
					),
			)
	);
	if(array_key_exists($module,$mod_var_mapping) && array_key_exists($varname, $mod_var_mapping[$module])) {
		return $mod_var_mapping[$module][$varname];
	} else {
		if ($varname != 'related_tables' || !$module) {
			return '';
		}
		$focus = CRMEntity::getInstance($module);
		$customFieldTable = isset($focus->customFieldTable) ? $focus->customFieldTable: null;
		if (!empty($customFieldTable)) {
			$returnValue = array();
			$returnValue['related_tables'][$customFieldTable[0]] = array($customFieldTable[1], $focus->table_name, $focus->table_index);

			return $returnValue['related_tables'];
		}
	}
}

/**
 * Convert given text input to singular.
 */
function vtlib_tosingular($text) {
	$lastpos = strripos($text, 's');
	if($lastpos == strlen($text)-1)
		return substr($text, 0, -1);
	return $text;
}

/**
 * Get picklist values that is accessible by all roles.
 */
function vtlib_getPicklistValues_AccessibleToAll($field_columnname) {
	global $adb;

	$columnname =  $adb->sql_escape_string($field_columnname);
	$tablename = "vtiger_$columnname";

	// Gather all the roles (except H1 which is organization role)
	$roleres = $adb->pquery("SELECT roleid FROM vtiger_role WHERE roleid != 'H1'", array());
	$roleresCount= $adb->num_rows($roleres);
	$allroles = Array();
	if($roleresCount) {
		for($index = 0; $index < $roleresCount; ++$index)
			$allroles[] = $adb->query_result($roleres, $index, 'roleid');
	}
	sort($allroles);

	// Get all the picklist values associated to roles (except H1 - organization role).
	$picklistres = $adb->pquery(
		"SELECT $columnname as pickvalue, roleid FROM $tablename
		INNER JOIN vtiger_role2picklist ON $tablename.picklist_valueid=vtiger_role2picklist.picklistvalueid
		WHERE roleid != 'H1'", array());

	$picklistresCount = $adb->num_rows($picklistres);

	$picklistval_roles = Array();
	if($picklistresCount) {
		for($index = 0; $index < $picklistresCount; ++$index) {
			$picklistval = $adb->query_result($picklistres, $index, 'pickvalue');
			$pickvalroleid=$adb->query_result($picklistres, $index, 'roleid');
			$picklistval_roles[$picklistval][] = $pickvalroleid;
		}
	}
	// Collect picklist value which is associated to all the roles.
	$allrolevalues = Array();
	foreach($picklistval_roles as $picklistval => $pickvalroles) {
		sort($pickvalroles);
		$diff = array_diff($pickvalroles,$allroles);
		if(empty($diff)) $allrolevalues[] = $picklistval;
	}

	return $allrolevalues;
}

/**
 * Get all picklist values for a non-standard picklist type.
 */
function vtlib_getPicklistValues($field_columnname) {
	global $adb;
	$picklistvalues = Vtiger_Cache::get('PicklistValues', $field_columnname);
	if (!$picklistvalues) {
		$columnname =  $adb->sql_escape_string($field_columnname);
		$tablename = "vtiger_$columnname";

		$picklistres = $adb->pquery("SELECT $columnname as pickvalue FROM $tablename", array());

		$picklistresCount = $adb->num_rows($picklistres);

		$picklistvalues = Array();
		if($picklistresCount) {
			for($index = 0; $index < $picklistresCount; ++$index) {
				$picklistvalues[] = $adb->query_result($picklistres, $index, 'pickvalue');
			}
		}
	}
	return $picklistvalues;
}

/**
 * Check for custom module by its name.
 */
function vtlib_isCustomModule($moduleName) {
	$moduleFile = "modules/$moduleName/$moduleName.php";
	if(file_exists($moduleFile)) {
		if(function_exists('checkFileAccessForInclusion')) {
			checkFileAccessForInclusion($moduleFile);
		}
		include_once($moduleFile);
		$focus = new $moduleName();
		return (isset($focus->IsCustomModule) && $focus->IsCustomModule);
	}
	return false;
}

/**
 * Get module specific smarty template path.
 */
function vtlib_getModuleTemplate($module, $templateName) {
	return ("modules/$module/$templateName");
}

/**
 * Check if give path is writeable.
 */
function vtlib_isWriteable($path) {
	if(is_dir($path)) {
		return vtlib_isDirWriteable($path);
	} else {
		return is_writable($path);
	}
}

/**
 * Check if given directory is writeable.
 * NOTE: The check is made by trying to create a random file in the directory.
 */
function vtlib_isDirWriteable($dirpath) {
	if(is_dir($dirpath)) {
		do {
			$tmpfile = 'vtiger' . time() . '-' . rand(1,1000) . '.tmp';
			// Continue the loop unless we find a name that does not exists already.
			$usefilename = "$dirpath/$tmpfile";
			if(!file_exists($usefilename)) break;
		} while(true);
		$fh = @fopen($usefilename,'a');
		if($fh) {
			fclose($fh);
			unlink($usefilename);
			return true;
		}
	}
	return false;
}

/** HTML Purifier global instance */
$__htmlpurifier_instance = false;
/**
 * Purify (Cleanup) malicious snippets of code from the input
 *
 * @param String $value
 * @param Boolean $ignore Skip cleaning of the input
 * @return String
 */
function vtlib_purify($input, $ignore = false) {
    global $__htmlpurifier_instance, $root_directory, $default_charset;

    static $purified_cache = array();
    $value = $input;

	$encryptInput = null;
    if (!is_array($input)) {
        $encryptInput = hash('sha256',$input);
        if (array_key_exists($encryptInput, $purified_cache)) {
            $value = $purified_cache[$encryptInput];
            //to escape cleaning up again
            $ignore = true;
        }
    }
    $use_charset = $default_charset;
    $use_root_directory = $root_directory;


    if (!$ignore) {
        // Initialize the instance if it has not yet done
        if ($__htmlpurifier_instance == false) {
            if (empty($use_charset))
                $use_charset = 'UTF-8';
            if (empty($use_root_directory))
                $use_root_directory = dirname(__FILE__) . '/../..';

            $allowedSchemes = array(
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'data' => true
            );

            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', $use_charset);
            $config->set('Cache.SerializerPath', "$use_root_directory/test/vtlib");
            $config->set('CSS.AllowTricky', true);
            $config->set('URI.AllowedSchemes', $allowedSchemes);
            $config->set('Attr.EnableID', true);

            $__htmlpurifier_instance = new HTMLPurifier($config);
        }
        if ($__htmlpurifier_instance) {
            // Composite type
            if (is_array($input)) {
                $value = array();
                foreach ($input as $k => $v) {
                    $value[$k] = vtlib_purify($v, $ignore);
                }
            } else { // Simple type
                $value = $__htmlpurifier_instance->purify($input);
                $value = purifyHtmlEventAttributes($value, true);
            }
        }
        if ($encryptInput != null) {
			$purified_cache[$encryptInput] = $value;
    	}
	}

	if ($value && !is_array($value)) {
		$value = str_replace('&amp;', '&', $value);
	}
    return $value;
}

/**
 * To purify malicious html event attributes
 * @param <String> $value
 * @return <String>
 */
function purifyHtmlEventAttributes($value,$replaceAll = false){
	
$tmp_markers = $office365ImageMarkers =  array();
$value = Vtiger_Functions::strip_base64_data($value,true,$tmp_markers);	
$value = Vtiger_Functions::stripInlineOffice365Image($value,true,$office365ImageMarkers);		
$tmp_markers = array_merge($tmp_markers, $office365ImageMarkers);

$htmlEventAttributes = "onerror|onblur|onchange|oncontextmenu|onfocus|oninput|oninvalid|onresize|onauxclick|oncancel|oncanplay|oncanplaythrough|".
                        "onreset|onsearch|onselect|onsubmit|onkeydown|onkeypress|onkeyup|onclose|oncuechange|ondurationchange|onemptied|onended|".
                        "onclick|ondblclick|ondrag|ondragend|ondragenter|ondragleave|ondragover|ondragexit|onformdata|onloadeddata|onloadedmetadata|".
                        "ondragstart|ondrop|onmousedown|onmousemove|onmouseout|onmouseover|onmouseenter|onmouseleave|onpause|onplay|onplaying|".
                        "onmouseup|onmousewheel|onscroll|onwheel|oncopy|oncut|onpaste|onload|onprogress|onratechange|onsecuritypolicyviolation|".
                        "onselectionchange|onabort|onselectstart|onstart|onfinish|onloadstart|onshow|onreadystatechange|onseeked|onslotchange|".
                        "onseeking|onstalled|onsubmit|onsuspend|ontimeupdate|ontoggle|onvolumechange|onwaiting|onwebkitanimationend|onstorage|".
                        "onwebkitanimationiteration|onwebkitanimationstart|onwebkittransitionend|onafterprint|onbeforeprint|onbeforeunload|".
                        "onhashchange|onlanguagechange|onmessage|onmessageerror|onoffline|ononline|onpagehide|onpageshow|onpopstate|onunload|".
                        "onrejectionhandled|onunhandledrejection|onloadend|onpointerenter|ongotpointercapture|onlostpointercapture|onpointerdown|".
                        "onpointermove|onpointerup|onpointercancel|onpointerover|onpointerout|onpointerleave|onactivate|onafterscriptexecute|".
                        "onanimationcancel|onanimationend|onanimationiteration|onanimationstart|onbeforeactivate|onbeforedeactivate|onbeforescriptexecute|".
                        "onbegin|onbounce|ondeactivate|onend|onfocusin|onfocusout|onrepeat|ontransitioncancel|ontransitionend|ontransitionrun|".
                        "ontransitionstart|onbeforecopy|onbeforecut|onbeforepaste|onfullscreenchange|onmozfullscreenchange|onpointerrawupdate|".
                        "ontouchend|ontouchmove|ontouchstart";

    // remove malicious html attributes with its value.
    if ($replaceAll) {
        $regex = '\s*[=&%#]\s*(?:"[^"]*"[\'"]*|\'[^\']*\'[\'"]*|[^]*[\s\/>])*/i';
        $value = preg_replace("/\s*(" . $htmlEventAttributes . ")" . $regex, '', $value);
		
        //remove script tag with contents
        $value = purifyScript($value);
        //purify javascript alert from the tag contents
        $value = purifyJavascriptAlert($value); 
	
    } else {
        if (preg_match("/\s*(" . $htmlEventAttributes . ")\s*=/i", $value)) {
            $value = str_replace("=", "&equals;", $value);
        }
    }

    //Replace any strip-markers
	if ($tmp_markers){
		$keys = array();
		$values = array();
		foreach ($tmp_markers as $k => $v){
			$keys[] = $k;
			$values[] = $v;
		}
		$value = str_replace($keys, $values, $value);
	}
	
    return $value;
}

//function to remove script tag and its contents
function purifyScript($value){
    $scriptRegex = '/(&.*?lt;|<)script[\w\W]*?(>|&.*?gt;)[\w\W]*?(&.*?lt;|<)\/script(>|&.*?gt;|\s)/i';
    $value = preg_replace($scriptRegex,'',$value);
    return $value;
}



//function to purify html tag having 'javascript:' string by removing the tag contents.
function purifyJavascriptAlert($value){
    $restrictJavascriptInTags = array('a','iframe','object','embed','animate','set','base','button','input','form');
    
    foreach($restrictJavascriptInTags as $tag){
        
        if(!empty($value)){
            $originalValue = $value;
        }
        
        // skip javascript: contents check if tag is not available,as javascript: regex will cause performace issue if the contents will be large 
        if (preg_match_all('/(&.*?lt;|<)'.$tag.'[^>]*?(>|&.*?gt;)/i', $value,$matches)) {
            $javaScriptRegex = '/(&.*?lt;|<).?'.$tag.' [^>]*(j[\s]?a[\s]?v[\s]?a[\s]?s[\s]?c[\s]?r[\s]?i[\s]?p[\s]?t[\s]*[=&%#:])[^>]*?(>|&.*?gt;)/i';
            foreach($matches[0] as $matchedValue){
                //strict check addded - if &tab;/&newLine added in the above tags we are replacing it to spaces.
                $purifyContent = preg_replace('/&NewLine;|&amp;NewLine;|&Tab;|&amp;Tab;|\t/i',' ',$matchedValue);
                $purifyContent = preg_replace($javaScriptRegex,"<$tag>",$purifyContent);
                $value = str_replace($matchedValue, $purifyContent, $value);
                
                /*
                * if the content length will more. In that case, preg_replace will fail and return Null due to PREG_BACKTRACK_LIMIT_ERROR error
                * so skipping the validation and reseting the value - TODO
                */
               if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
                   $value = $originalValue;
                   return $value;
               }
            }        
        }
    }
    
    return $value;
}

/**
 * Function to return the valid SQl input.
 * @param <String> $string
 * @param <Boolean> $skipEmpty Skip the check if string is empty.
 * @return <String> $string/false
 */
function vtlib_purifyForSql($string, $skipEmpty=true) {
	$pattern = "/^[_a-zA-Z0-9.:\-]+$/";
	if ((empty($string) && $skipEmpty) || preg_match($pattern, $string)) {
		return $string;
	}
	return false;
}

/**
 * Process the UI Widget requested
 * @param Vtiger_Link $widgetLinkInfo
 * @param Current Smarty Context $context
 * @return
 */
function vtlib_process_widget($widgetLinkInfo, $context = false) {
	if (preg_match("/^block:\/\/(.*)/", $widgetLinkInfo->linkurl, $matches)) {
		list($widgetControllerClass, $widgetControllerClassFile) = explode(':', $matches[1]);
		if (!class_exists($widgetControllerClass)) {
			checkFileAccessForInclusion($widgetControllerClassFile);
			include_once $widgetControllerClassFile;
		}
		if (class_exists($widgetControllerClass)) {
			$widgetControllerInstance = new $widgetControllerClass;
			$widgetInstance = $widgetControllerInstance->getWidget($widgetLinkInfo->linklabel);
			if ($widgetInstance) {
				return $widgetInstance->process($context);
			}
		}
	}
	return "";
}

function vtlib_module_icon($modulename){
	if($modulename == 'Events'){
		return "modules/Calendar/Events.png";
	}
	if(file_exists("modules/$modulename/$modulename.png")){
		return "modules/$modulename/$modulename.png";
	}
	return "modules/Vtiger/Vtiger.png";
}

function vtlib_mime_content_type($filename) {
	return Vtiger_Functions::mime_content_type($filename);
}

/**
 * Function to add settings entry in CRM settings page
 * @param string $linkName
 * @param string $linkURL
 * @param string $blockName
 * @return boolean true/false
 */
function vtlib_addSettingsLink($linkName, $linkURL, $blockName = false) {
	$success = true;
	$db = PearDatabase::getInstance();

	//Check entry name exist in DB or not
	$result = $db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?', array($linkName));
	if ($result && !$db->num_rows($result)) {
		$blockId = 0;
		if ($blockName) {
			$blockId = getSettingsBlockId($blockName);//Check block name exist in DB or not
		}

		if (!$blockId) {
			$blockName = 'LBL_OTHER_SETTINGS';
			$blockId = getSettingsBlockId($blockName);//Check block name exist in DB or not
		}

		//Add block in to DB if not exists
		if (!$blockId) {
			$blockSeqResult = $db->pquery('SELECT MAX(sequence) AS sequence FROM vtiger_settings_blocks', array());
			if ($db->num_rows($blockSeqResult)) {
				$blockId = $db->getUniqueID('vtiger_settings_blocks');
				$blockSequence = $db->query_result($blockSeqResult, 0, 'sequence');
				$db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?,?,?)', array($blockId, 'LBL_OTHER_SETTINGS', $blockSequence++));
			}
		}

		//Add settings field in to DB
		if ($blockId) {
			$fieldSeqResult = $db->pquery('SELECT MAX(sequence) AS sequence FROM vtiger_settings_field WHERE blockid=?', array($blockId));
			if ($db->num_rows($fieldSeqResult)) {
				$fieldId = $db->getUniqueID('vtiger_settings_field');
				$linkURL = ($linkURL) ? $linkURL : '';
				$fieldSequence = $db->query_result($fieldSeqResult, 0, 'sequence');

				$db->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) VALUES(?,?,?,?,?,?,?,?,?)', array($fieldId, $blockId, $linkName, '', $linkName, $linkURL, $fieldSequence++, 0, 0));
			}
		} else {
			$success = false;
		}
	}
	return $success;
}

/**
 * PHP7 support for split function
 * split : Case sensitive.
 */
if (!function_exists('split')) {
    function split($pattern, $string, $limit = null) {
        $regex = '/' . preg_replace('/\//', '\\/', $pattern) . '/';
        return preg_split($regex, $string, $limit);
    }

}

function php7_compat_ereg($pattern, $str, $ignore_case=false) {
	$regex = '/'. preg_replace('/\//', '\\/', $pattern) .'/' . ($ignore_case ? 'i': '');
	return preg_match($regex, $str);
}

if (!function_exists('ereg')) { function ereg($pattern, $str) { return php7_compat_ereg($pattern, $str); } }
if (!function_exists('eregi')) { function eregi($pattern, $str) { return php7_compat_ereg($pattern, $str, true); } }

/**
 * PHP8 support
 */
if (!function_exists('get_magic_quotes_gpc')) {
	function get_magic_quotes_gpc() {
		return false;
	}
}

function php7_count($value) {
	// PHP 8.x does not like count(null) or count(string)
	return is_null($value) || !is_array($value) ? 0 : count($value);
}

?>
