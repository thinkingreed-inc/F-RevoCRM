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
	'PDF Templates' => 'PDF Templates',
	'LBL_ADD_RECORD' => 'Add PDF Template',
	'SINGLE_PDFTemplates' => 'PDF Template',
	'LBL_PDF_TEMPLATES'=> 'PDF Templates',
	'LBL_PDF_TEMPLATE' => 'PDF Template',
	
	'LBL_TEMPLATE_NAME' => 'Template Name',
	'LBL_DESCRIPTION' => 'Description',
	'LBL_SUBJECT' => 'Subject',
	'LBL_SELECT_FIELD_TYPE' => 'Select Module and Field',
	'LBL_MODULE_NAME' => 'Module Name',
	
	'LBL_PDF_TEMPLATE_DESCRIPTION'=>'Manage PDF Templates',
	'LBL_NO_PERMISSIONS_TO_DELETE_SYSTEM_TEMPLATE' => 'No permission to delete system template',
	'LBL_RECORD_ID' => 'Record ID',

	'Current Date' => 'Current Date',
	'Current Time' => 'Current Time',
	'System Timezone' => 'System Timezone',
	'User Timezone' => 'User Timezone',
	'View in browser' => 'View in browser',
	'CRM Detail View Url' => 'CRM Detail View URL',
	'Portal Detail View Url' => 'Portal Detail View URL',
	'Site Url' => 'F-RevoCRM Login URL',
	'Portal Url' => 'Portal Login URL',

	'PDF Template - Properties of ' => 'PDF Template - Properties of ',
	'Templatename' => 'Template Name',
	'Message' => 'Template Content',
	'LBL_BLOCK_MESSAGE' => '※ By enclosing rows in a table with "$loop-products$", it is possible to output values for each item.<br>※ By enclosing rows in a table with "$loop-child$", it is possible to output values for each child module. Only values of a single child module can be displayed within rows enclosed by "$loop-child$".<br>
	 If you want to filter child records by conditions, set the condition in the format [FUNCTION|loop-child_where|Child Module Field Name|Comparison Operator (==, !=, etc.)|Comparison Value|FUNCTION] within the row containing "$loop-child$".<br>
	※ Images can be pasted via copy & paste.',

	'LBL_PDF_FORMAT' => 'PDF Size',
	'LBL_PDF_MARGIM' => 'PDF Margins (Unit: mm)',
	'LBL_PDF_MARGIM_TOP' => 'Top',
	'LBL_PDF_MARGIM_BOTTOM' => 'Bottom',
	'LBL_PDF_MARGIM_LEFT' => 'Left',
	'LBL_PDF_MARGIM_RIGHT' => 'Right',
	'LBL_CUSTOM_FUNCTIONS' => 'Functions',
	'LBL_EXPORT_TO_PDF' => 'Export to PDF',
	'LBL_PDF_FILE_NAME' => 'PDF File Name (if not specified, template name will be used)',

	"Conditional branching (if)" => "Conditional branching (if)",
	"Conditional branching (ifs)" => "Conditional branching (ifs)",
	"Date format conversion (datefmt)" => "Date format conversion (datefmt)",
	"String replacement (strreplace)" => "String replacement (strreplace)",
	"Aggregating the sum of child records (aggset_sum)" => "Aggregating the sum of child records (aggset_sum)",
	"Aggregate average value of child records (aggset_average)" => "Aggregate average value of child records (aggset_average)",
	"Aggregate the minimum value of child records (aggset_min)" => "Aggregate the minimum value of child records (aggset_min)",
	"Aggregate the minimum value of a child record (aggset_max)" => "Aggregate the maximum value of child records (aggset_max)",

	'Conditional setting for child records (loop-child_where) *must be on the same line as the line containing $loop-child$.' => 'Conditional setting for child records (loop-child_where) *must be on the same line as the line containing $loop-child$.',
	'Setting the sort order of child records (loop-child_sortorder) *must be on the same line as the line containing $loop-child$.' => 'Setting the sort order of child records (loop-child_sortorder) *must be on the same line as the line containing $loop-child$.',


	"[FUNCTION|if|columnname|==|value|iftrue|iffalse|FUNCTION]" => "[FUNCTION|if|Field Name|Comparison Operator (==, !=, etc.)|Comparison Value|If True|If False|FUNCTION]",
	"[FUNCTION|ifs|columnname1|==|value1|ANDOR|columnname2|==|value2|iftrue|iffalse|FUNCTION]" => "[FUNCTION|ifs|Field Name 1|Comparison Operator (==, !=, etc.)|Comparison Value 1|AND or OR|Field Name 2|Comparison Operator (==, !=, etc.)|Comparison Value 2|If True|If False|FUNCTION]",
	"[FUNCTION|datefmt|columnname|formatstring|FUNCTION]" => "[FUNCTION|datefmt|Field Name|Date Format (e.g., Y-m-d)|FUNCTION]",
	"[FUNCTION|strreplace|columnname|searchstring|replacestring|FUNCTION]" => "[FUNCTION|strreplace|Field Name|Search String|Replace String|FUNCTION]",
	"[FUNCTION|aggset_sum|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_sum|Aggregate Field|Field Name 1|Comparison Operator (==, !=, etc.)|Comparison Value 1|AND or OR|Field Name 2|Comparison Operator (==, !=, etc.)|Comparison Value 2|FUNCTION]",
	"[FUNCTION|aggset_average|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_average|Aggregate Field|Field Name 1|Comparison Operator (==, !=, etc.)|Comparison Value 1|AND or OR|Field Name 2|Comparison Operator (==, !=, etc.)|Comparison Value 2|FUNCTION]",
	"[FUNCTION|aggset_min|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_min|Aggregate Field|Field Name 1|Comparison Operator (==, !=, etc.)|Comparison Value 1|AND or OR|Field Name 2|Comparison Operator (==, !=, etc.)|Comparison Value 2|FUNCTION]",
	"[FUNCTION|aggset_max|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_max|Aggregate Field|Field Name 1|Comparison Operator (==, !=, etc.)|Comparison Value 1|AND or OR|Field Name 2|Comparison Operator (==, !=, etc.)|Comparison Value 2|FUNCTION]",
	"[FUNCTION|loop-child_where|columnname|==|value|FUNCTION]" => "[FUNCTION|loop-child_where|Child Module Field Name|Comparison Operator (==, !=, etc.)|Comparison Value|FUNCTION]",
	"[FUNCTION|loop-child_sortorder|columnname|sortorder|FUNCTION]" => "[FUNCTION|loop-child_sortorder|Child Module Field Name|Sort Order (ASC or DESC)|FUNCTION]",

	"LBL_INVOICE" => "Invoice",
	"LBL_FOR_THE_ATTENTION_OF" => "For the Attention Of",
	"LBL_PLEASEFINDOURINVOICEBELOW"=>"Please find our invoice below.",
	'LBL_TOTAL_AMOUNT_BILLED' => 'Total Amount Billed',
	'TOTAL_AMOUNT_BILLED' => 'Total Amount Billed',
	'LBL_DUE_DATE' => 'Due Date',
	'LBL_ITEM' => 'Item',
	'LBL_QUANTITY' => 'Quantity',
	'LBL_UNIT_PRICE' => 'Unit Price',
	'LBL_DISCOUNT' => 'Discount',
	'LBL_SUB_TOTAL' => 'Sub Total',
	'LBL_TAX' => 'Tax',
	'LBL_GRAND_TOTAL' => 'Grand Total',
	'LBL_REMARKS' => 'Remarks',
	'LBL_PURCHASE_ORDER' => 'Purchase Order', 
	'LBL_YOUR_ESTIMATE_AMOUNT' => 'Your Estimated Amount',
	'LBL_AMOUNT' => 'Amount',
	'LBL_SPECIAL_DISCOUNT' => 'Special Discount',
	'LBL_REDUCED_TAX_RATE_TARGET' => '*Subject to reduced Tax Rate',
	'LBL_BREAKDOWN' => 'Breakdown',
	'LBL_TAX_RATE' => 'Tax Rate',
	'LBL_TARGET_AMOUNT' => 'Eligible Amount',
	'LBL_QUOTATION' => 'Quotation',
	'LBL_PLEASEFINDOURQUOTATIONBELOW'=>'Please find our quotation below.<br />We look forward to your order.',
	'LBL_YOUR_QUOTATION_AMOUNT' => 'Your Quotation Amount',
	'LBL_TOTAL_AMOUNT' => 'Total Amount',
	'LBL_OFFER_PRICE' => 'Offer Price',
	'LBL_ORDER_CONFIRMATION' => 'Order Confirmation',
	'LBL_THANK_YOU_FOR_YOUR_ORDER' => 'Thank you for your order.<br />We have received your order as per the details below.',
	'LBL_QUOTATION_NO' => 'Quotation No',
	'LBL_YOUR_QUOTATION_AMOUNT' => 'Your Quotation Amount',
);

$jsLanguageStrings = array(
	'LBL_CUTOMER_LOGIN_DETAILS_TEMPLATE_DELETE_MESSAGE' => 'Deleting the "Customer Login Details" template will prevent sending customer portal login details to contacts. Do you want to continue?',
	'JS_REQUIRED_FIELD' => '* Content of the system email template is required',
);
