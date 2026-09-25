<?php
/**
 * Language file for the electronic bookkeeping (compliance) settings
 */
$languageStrings = array(
	'DocumentsCompliance' => 'Electronic Bookkeeping',
	'LBL_DOCUMENTS_COMPLIANCE' => 'Electronic Bookkeeping',
	'LBL_DOCUMENTS_COMPLIANCE_DESCRIPTION' => 'Configure how the input deadline for scanned documents is calculated.',

	// Input deadline settings
	'LBL_INPUT_DEADLINE_SETTINGS' => 'Input deadline calculation',
	'LBL_POLICY' => 'Deadline policy',
	'LBL_POLICY_PROMPT' => 'Promptly',
	'LBL_POLICY_PROMPT_DESCRIPTION' => 'Documents are entered within about 7 business days of receipt. Use this when no internal processing rules are defined.',
	'LBL_POLICY_CYCLE' => 'After the business processing cycle',
	'LBL_POLICY_CYCLE_DESCRIPTION' => 'Use this when internal processing rules are defined: documents are entered within about 7 business days after the processing cycle (up to 2 months).',
	'LBL_BUSINESS_DAYS' => 'Grace period (business days)',
	'LBL_BUSINESS_DAYS_NOTE' => 'Counted from the receipt date (or from the end of the processing cycle). The statutory guideline is 7 business days.',
	'LBL_CYCLE_MONTHS' => 'Business processing cycle',
	'LBL_CYCLE_MONTHS_NOTE' => 'Used when "After the business processing cycle" is selected. The statutory maximum is 2 months.',
	'LBL_WARNING_DAYS' => 'Near deadline (business days)',
	'LBL_WARNING_DAYS_NOTE' => 'The deadline status becomes "Near Deadline" when this many business days or fewer remain.',
	'LBL_SETTINGS_NOTE' => 'Business days are determined by the holidays master (public and company holidays) and the weekly days off. Registering a holiday moves the deadline to the next business day.',
	'LBL_HOLIDAYS_LINK' => 'Open holidays master',
	'LBL_EXAMPLE' => 'With the current settings, a document received on %s has an input deadline of %s',

	// Recalculation
	'LBL_RECALCULATE' => 'Recalculate existing deadlines',
	'LBL_RECALCULATE_NOTE' => 'Changing the policy or the number of days does not update deadlines of existing documents. Recalculate them if needed.',
	'LBL_CONFIRM_RECALCULATE' => 'Recalculate the input deadline of scanned documents that have a receipt date, using the current settings. Continue?',
	'LBL_RECALCULATE_RESULT' => 'Input deadlines recalculated (%s checked, %s changed)',

	// Actions
	'LBL_SAVE' => 'Save',
	'LBL_SAVING' => 'Saving...',
	'LBL_LOADING' => 'Loading...',
	'LBL_SETTINGS_SAVED' => 'Input deadline settings saved',
	'LBL_DAY_SUFFIX' => 'business days',
	'LBL_MONTH_SUFFIX' => 'months',

	// Transaction records per document category
	'LBL_TRANSACTION_MODULE_SETTINGS' => 'Transaction records',
	'LBL_TRANSACTION_MODULE_NOTE' => 'For each document category, choose which modules a document must be linked to in order to be considered compliant. This is used for the searchability requirement of the Electronic Books Preservation Act.',
	'LBL_DOCUMENT_CATEGORY' => 'Document category',
	'LBL_CATEGORY_MODULES_SAVED' => 'Transaction record settings saved',
	'LBL_NO_MODULE_SELECTED_NOTE' => 'Categories with no module selected do not require a linked record to be compliant.',
	'LBL_RECHECK_COMPLIANCE' => 'Re-run compliance check',
	'LBL_RECHECK_NOTE' => 'Changing the criteria does not update the compliance status of existing documents. Re-run the check if needed.',
	'LBL_CONFIRM_RECHECK' => 'Re-run the compliance check for all documents in scope using the current criteria. Continue?',
	'LBL_RECHECK_RESULT' => 'Compliance check finished (%s checked, %s compliant, %s non-compliant)',
	'LBL_INVALID_CATEGORY_MODULES' => 'Invalid transaction record settings',
	'LBL_INVALID_CATEGORY' => 'Invalid document category',
	'LBL_INVALID_MODULE' => 'Documents cannot be linked to the specified module (%s)',

	// Messages
	'LBL_INVALID_POLICY' => 'Invalid deadline policy',
	'LBL_INVALID_BUSINESS_DAYS' => 'Enter the grace period as an integer between 1 and %s',
	'LBL_INVALID_CYCLE_MONTHS' => 'Enter the business processing cycle as an integer between 1 and %s months',
	'LBL_INVALID_WARNING_DAYS' => 'Enter the near-deadline period as an integer between 1 and %s',
);
