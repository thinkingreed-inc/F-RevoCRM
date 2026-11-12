<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
$languageStrings = array(
	// Basic Strings
	'Leads' => 'Leads',
	'SINGLE_Leads' => 'Lead',
	'LBL_RECORDS_LIST' => 'Leads List',
	'LBL_ADD_RECORD' => 'Add Lead',

	// Convert Lead (Web Components)
	'LBL_CONVERT_TITLE' => 'Convert Lead',
	'LBL_CONVERT_MODE_CREATE' => 'Create new',
	'LBL_CONVERT_MODE_EXISTING' => 'Link to existing record',
	'LBL_CONVERT_MODE_NONE' => 'Do not create',
	'LBL_CONVERT_MATCH_FOUND' => 'Existing %s found (rule: %s / %s records)',
	'LBL_CONVERT_MATCH_HINT' => 'The matched record is selected. Switch to "Create new" to create a new one instead.',
	'LBL_CONVERT_MATCH_CANDIDATES' => 'Candidates',
	'LBL_CONVERT_SELECT_EXISTING' => 'Record to link',
	'LBL_CONVERT_EXISTING_REQUIRED' => 'Select the %s to link',
	'LBL_CONVERT_CONTACT_OTHER_ACCOUNT' => 'This contact belongs to another organization (%s). The organization will not be changed',
	'LBL_CONVERT_CONTACT_ACCOUNT_SET' => 'This contact has no organization. The organization of this conversion will be set',
	'LBL_CONVERT_FORCED_ON' => 'Required by a mandatory field of %s',
	'LBL_CONVERT_REQUIRE_ONE' => 'Either an organization or a contact must be created or linked',
	'LBL_CONVERT_COMMON' => 'Common settings',
	'LBL_CONVERT_SUBMIT' => 'Convert',
	'LBL_CONVERT_SUBMITTING' => 'Converting...',
	'LBL_CONVERT_ERRORS_TITLE' => 'Could not convert the lead',
	'LBL_CONVERT_FIX_FIELDS' => 'Please check the highlighted fields',
	'LBL_CONVERT_ADDED_FIELD' => 'Fields added because they need your attention',
	'LBL_CONVERT_NO_FIELDS' => 'No fields to enter',
	'LBL_CONVERT_LOAD_FAILED' => 'Failed to load the conversion information',
	'LBL_CONVERT_ERR_REQUIRED' => '%s is required',
	'LBL_CONVERT_ERR_EMAIL' => '%s is not a valid email address',
	'LBL_CONVERT_ERR_URL' => '%s is not a valid URL',
	'LBL_CONVERT_ERR_INTEGER' => 'Enter an integer for %s',
	'LBL_CONVERT_ERR_NUMBER' => 'Enter a number for %s',
	'LBL_CONVERT_ERR_DATE' => '%s is not a valid date',
	'LBL_CONVERT_ERR_TIME' => '%s is not a valid time',
	'LBL_CONVERT_ERR_BOOLEAN' => '%s has an invalid value',
	'LBL_CONVERT_ERR_PICKLIST' => '%s has a value that cannot be selected',
	'LBL_CONVERT_ERR_INVALID' => '%s has an invalid value',
	'LBL_CONVERT_ERR_NOT_EDITABLE' => '%2$s (You do not have permission to edit this field. Ask your administrator to check the lead mapping settings)',
	'LBL_CONVERT_ERR_ALREADY_CONVERTED' => 'This lead has already been converted',
	'LBL_CONVERT_ERR_ACCOUNT_OR_CONTACT' => 'Either an organization or a contact must be created or linked',
	'LBL_CONVERT_ERR_INVALID_REQUEST' => 'Invalid conversion request',
	'LBL_CONVERT_ERR_NO_PERMISSION' => 'You do not have permission to create %s',
	'LBL_CONVERT_ERR_EXISTING_NOT_FOUND' => 'The %s to link was not found or you do not have permission to view it',
	'LBL_CONVERT_ERR_LINK_REQUIRED' => '%1$s requires %2$s. Create or link %2$s as well',
	'LBL_CONVERT_ERR_FAILED' => 'Failed to convert the lead. %s',

	// Blocks
	'LBL_LEAD_INFORMATION' => 'Lead Details',

	//Field Labels
	'Lead No' => 'Lead Number',
	'Company' => 'Company',
	'Designation' => 'Designation',
	'Website' => 'Website',
	'Industry' => 'Industry',
	'Lead Status' => 'Lead Status',
	'No Of Employees' => 'Number of Employees',
	'Phone' => 'Primary Phone',
	'Secondary Email' => 'Secondary Email',
	'Email' => 'Primary Email',

	//Added for Existing Picklist Entries

	'--None--'=>'--None--',
	'Mr.'=>'Mr.',
	'Ms.'=>'Ms.',
	'Mrs.'=>'Mrs.',
	'Dr.'=>'Dr.',
	'Prof.'=>'Prof.',

	//Lead Status Picklist values
	'Attempted to Contact'=>'Attempted to Contact',
	'Cold'=>'Cold',
	'Contact in Future'=>'Contact in Future',
	'Contacted'=>'Contacted',
	'Hot'=>'Hot',
	'Junk Lead'=>'Junk Lead',
	'Lost Lead'=>'Lost Lead',
	'Not Contacted'=>'Not Contacted',
	'Pre Qualified'=>'Pre Qualified',
	'Qualified'=>'Qualified',
	'Warm'=>'Warm',

	// Mass Action
	'LBL_CONVERT_LEAD' => 'Convert Lead',

	//Convert Lead
	'LBL_TRANSFER_RELATED_RECORD' => 'Transfer related record to',
	'LBL_CONVERT_LEAD_ERROR' => 'You have to enable either Organization or Contact to convert the Lead',
	'LBL_LEADS_FIELD_MAPPING_INCOMPLETE' => 'Leads Field Mapping is incomplete(Settings > Module Manager > Leads > Leads Field Mapping)',
	'LBL_LEADS_FIELD_MAPPING' => 'Leads Field Mapping',

	//Leads Custom Field Mapping
	'LBL_CUSTOM_FIELD_MAPPING'=> 'Lead Conversion Data Mapping',
	'LBL_IMAGE_INFORMATION' => 'Profile Picture',
	'Lead Image' => 'Lead Image',

	//F-RevoCRM
	'Hot Leads' => 'High Potential Customers',
	'This Month Leads' => 'Leads of This Month',
	'last_action_date' => 'Last Activity Date',
);
$jsLanguageStrings = array(
	'JS_SELECT_CONTACTS' => 'Select Contacts to proceed',
	'JS_SELECT_ORGANIZATION' => 'Select Organization to proceed',
	'JS_SELECT_ORGANIZATION_OR_CONTACT_TO_CONVERT_LEAD' => 'Conversion requires selection of Contact or Organization'
);