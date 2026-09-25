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

	// Blocks
	'LBL_LEAD_INFORMATION' => 'Lead Details',

	//Field Labels
	'Lead No' => 'Lead Number',
	'Company' => 'Company',
	'Designation' => 'Designation',
	'Website' => 'Website',
	'Industry' => 'Industry',
	'Lead Status' => 'Lead Status',
	'Converted' => 'Converted',
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

	//Parameters
	'LBL_SETUP_PARAMETER_MESSAGE_SHOW_CONVERTED_LEADS' => 'Controls whether converted Leads are listed in the CRM.
true  : Converted Leads are shown in list views, searches, related lists and dashboards.
false : Converted Leads are hidden (default behaviour).',
	'LBL_SETUP_PARAMETER_MESSAGE_ALLOW_RECONVERT_LEAD' => 'Controls whether an already converted Lead can be converted again.
true  : A converted Lead can be converted again. A new Contact / Opportunity is created on every conversion (an existing Organization with the same name is reused).
false : A Lead can be converted only once (default behaviour).',
);
$jsLanguageStrings = array(
	'JS_SELECT_CONTACTS' => 'Select Contacts to proceed',
	'JS_SELECT_ORGANIZATION' => 'Select Organization to proceed',
	'JS_SELECT_ORGANIZATION_OR_CONTACT_TO_CONVERT_LEAD' => 'Conversion requires selection of Contact or Organization',
	'JS_LEAD_ALREADY_CONVERTED_CONFIRMATION' => 'This Lead has already been converted.<br>A new Contact / Opportunity will be created (an existing Organization with the same name is reused).<br>Do you want to convert it again?'
);