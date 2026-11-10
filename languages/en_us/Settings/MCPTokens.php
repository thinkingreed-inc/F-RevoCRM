<?php
/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/
/**
 * MCP Token Management - English Language File
 */
$languageStrings = array(
	'MCPTokens'              => 'MCP Token Management',
	'LBL_MCP_TOKENS'         => 'MCP Token Management',
	'LBL_LABEL'              => 'Label',
	'LBL_USER'               => 'User',
	'LBL_ENABLED'            => 'Enabled',
	'LBL_CREATED_AT'         => 'Created At',
	'LBL_ACTIONS'            => 'Actions',
	'LBL_ACTIVE'             => 'Active',
	'LBL_DISABLED'           => 'Disabled',
	'LBL_DISABLE'            => 'Disable',
	'LBL_ALREADY_DISABLED'   => 'Already Disabled',
	'LBL_CREATE_TOKEN'       => 'Generate New Token',
	'LBL_SELECT_USER'        => '-- Select User --',
	'LBL_LABEL_PLACEHOLDER'  => 'e.g. Claude MCP',
	'LBL_CANCEL'             => 'Cancel',
	'LBL_GENERATE'           => 'Generate',
	'LBL_TOKEN_GENERATED'    => 'Token Generated',
	'LBL_TOKEN_WARNING'      => 'Copy this token and store it securely. It cannot be displayed again.',
	'LBL_TOKEN_VALUE'        => 'Token',
	'LBL_COPY'               => 'Copy',
	'LBL_SAVED_AND_CLOSE'    => 'Saved & Close',
	'LBL_NO_TOKENS_FOUND'    => 'No MCP tokens found',
	'LBL_MCP_PREFIX'       => 'Prefix',
	'LBL_MCP_LAST_USED'    => 'Last used',
	'LBL_MCP_EXPIRES'      => 'Expires',
	'LBL_MCP_EXPIRES_30'   => '30 days',
	'LBL_MCP_EXPIRES_60'   => '60 days',
	'LBL_MCP_EXPIRES_90'   => '90 days',
	'LBL_MCP_EXPIRES_NONE' => 'No expiry',
	'LBL_MCP_EXPIRED'      => 'Expired',
	'LBL_MCP_ADMIN_REQUIRED'       => 'Administrator privileges are required',
	'LBL_MCP_SELECT_USER_REQUIRED' => 'Please select a user',
	'LBL_MCP_LABEL_REQUIRED'       => 'Please enter a label',
	'LBL_MCP_LABEL_TOO_LONG'       => 'The label must be 100 characters or less',
	'LBL_MCP_INVALID_EXPIRES'      => 'The expiry period is invalid',
	'LBL_MCP_USER_NOT_FOUND'       => 'The specified user was not found or is inactive',
	'LBL_MCP_INVALID_RECORD'       => 'Invalid record ID',
	'LBL_MCP_TOKEN_NOT_FOUND'      => 'The token was not found',
	'LBL_MCP_TOKEN_ISSUE_FAILED'   => 'Failed to issue the token',
	'LBL_MCP_TOKEN_LIST_FAILED'    => 'Failed to retrieve the token list',
	'LBL_MCP_TOKEN_COUNT_FAILED'   => 'Failed to count the tokens',
	'LBL_MCP_TOKEN_DISABLE_FAILED' => 'Failed to disable the token',
	'LBL_SETUP_PARAMETER_MESSAGE_MCP_RATE_LIMIT_WINDOW' => 'The length of the MCP rate limit window in seconds.
Use a positive integer. Anything else (decimals, 0 or less, non-numeric text) falls back to the default (10 seconds).
A changed value takes effect from the next request.',
	'LBL_SETUP_PARAMETER_MESSAGE_MCP_RATE_LIMIT_MAX' => 'The maximum number of requests allowed within MCP_RATE_LIMIT_WINDOW.
Counted per source IP before authentication (failed authentication attempts and client registration) and per user after authentication.
Use a positive integer. Anything else (decimals, 0 or less, non-numeric text) falls back to the default (20 requests).
A changed value takes effect from the next request.',
);

$jsLanguageStrings = array(
	'JS_MCP_SELECT_USER_REQUIRED' => 'Please select a user',
	'JS_MCP_LABEL_REQUIRED'       => 'Please enter a label',
	'JS_MCP_ISSUE_FAILED'         => 'Failed to issue the token',
	'JS_MCP_COPIED'               => 'Copied',
	'JS_MCP_DISABLE_CONFIRM'      => 'Disable token "%s"? This cannot be undone.',
	'JS_MCP_DISABLED'             => 'The token has been disabled',
	'JS_MCP_DISABLE_FAILED'       => 'Failed to disable the token',
);
