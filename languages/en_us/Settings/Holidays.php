<?php
/**
 * Language file for the Holidays master (Settings)
 */
$languageStrings = array(
	'Holidays' => 'Holidays',
	'LBL_HOLIDAYS' => 'Holidays',
	'LBL_HOLIDAYS_DESCRIPTION' => 'Register public and company holidays. Shared by every feature that calculates business days.',

	// Fields
	'LBL_HOLIDAY_DATE' => 'Date',
	'LBL_HOLIDAY_NAME' => 'Name',
	'LBL_DAY_TYPE' => 'Day Type',
	'LBL_HOLIDAY_TYPE' => 'Category',
	'LBL_DESCRIPTION' => 'Description',

	// Day types
	'LBL_DAY_TYPE_HOLIDAY' => 'Holiday',
	'LBL_DAY_TYPE_WORKDAY' => 'Business day (working on a day off)',

	// Categories
	'LBL_HOLIDAY_TYPE_NATIONAL' => 'Public holiday',
	'LBL_HOLIDAY_TYPE_COMPANY' => 'Company holiday',
	'LBL_HOLIDAY_TYPE_OTHER' => 'Other',

	// Actions
	'LBL_ADD_HOLIDAY' => 'Add Holiday',
	'LBL_EDIT_HOLIDAY' => 'Edit Holiday',
	'LBL_GENERATE_NATIONAL_HOLIDAYS' => 'Add calculated holidays',
	'LBL_YEAR_SUFFIX' => '',
	'LBL_WEEKLY_HOLIDAY_NOTE' => 'Weekly days off are configured in "Weekly days off" below; they are treated as holidays without being registered here. Register a working day that falls on a day off with the day type "Business day".',

	// Weekly days off
	'LBL_WEEKLY_HOLIDAYS' => 'Weekly days off',
	'LBL_WEEKLY_HOLIDAY_NONE' => 'None',
	'LBL_SETTINGS_SAVED' => 'Weekly days off saved',
	'LBL_INVALID_WEEKLY_HOLIDAY' => 'Invalid day of the week',
	'LBL_WEEKDAY_SUN' => 'Sun',
	'LBL_WEEKDAY_MON' => 'Mon',
	'LBL_WEEKDAY_TUE' => 'Tue',
	'LBL_WEEKDAY_WED' => 'Wed',
	'LBL_WEEKDAY_THU' => 'Thu',
	'LBL_WEEKDAY_FRI' => 'Fri',
	'LBL_WEEKDAY_SAT' => 'Sat',

	'LBL_NO_HOLIDAYS' => 'No holidays registered',
	'LBL_SAVE' => 'Save',
	'LBL_SAVING' => 'Saving...',
	'LBL_CANCEL' => 'Cancel',
	'LBL_EDIT' => 'Edit',
	'LBL_DELETE' => 'Delete',
	'LBL_LOADING' => 'Loading...',
	'LBL_COUNT_SUFFIX' => ' items',
	'LBL_CONFIRM_DELETE' => 'Delete this holiday?',
	'LBL_CONFIRM_GENERATE' => 'Register the public holidays for %s. Continue? (already registered dates are left unchanged)',
	'LBL_GENERATE_RESULT' => 'Public holidays registered (added: %s, skipped: %s)',

	// Importing the official data
	'LBL_IMPORT_OFFICIAL' => 'Import official data',
	'LBL_IMPORT_CSV_FILE' => 'Import from CSV file',
	'LBL_OFFICIAL_SOURCE_NOTE' => 'The official source is the Cabinet Office announcement of Japanese public holidays. One-off changes cannot be reproduced by calculation, so importing the official data is recommended. If the server has no outbound access, download the official CSV and use "Import from CSV file".',
	'LBL_GENERATE_NOTE' => '"Add calculated holidays" is for future years that have not been announced yet (calculated values, please verify).',
	'LBL_CONFIRM_IMPORT_OFFICIAL' => 'Import the official data (last year onwards). Public holidays of the target years will be updated or removed to match it (company holidays are left unchanged). Continue?',
	'LBL_IMPORT_RESULT' => 'Official data imported (%s-%s: added %s, updated %s, removed %s)',
	'LBL_IMPORTED_FROM_OFFICIAL' => 'Imported from the Cabinet Office data',
	'LBL_CSV_EMPTY' => 'The CSV is empty',
	'LBL_CSV_INVALID' => 'The CSV format is invalid (please specify the official public holiday CSV)',
	'LBL_CSV_YEAR_NOT_INCLUDED' => 'The CSV does not contain data for %s',
	'LBL_CSV_NOT_UPLOADED' => 'No CSV file was specified',
	'LBL_CSV_UPLOAD_FAILED' => 'Failed to upload the CSV file',
	'LBL_DOWNLOAD_FAILED' => 'Failed to download the official data (%s). If the server has no outbound access, import the CSV file instead.',

	// Messages
	'LBL_INVALID_DATE' => 'The date format is invalid',
	'LBL_NAME_REQUIRED' => 'Please enter a name',
	'LBL_DATE_ALREADY_REGISTERED' => 'This date is already registered',
	'LBL_RECORD_NOT_FOUND' => 'The record was not found',
	'LBL_YEAR_NOT_SUPPORTED' => 'Generating public holidays is supported from %s onwards',
);
