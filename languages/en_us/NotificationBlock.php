<?php

/**
 * Notification block (#1965)
 */
$languageStrings = [
    'NotificationBlock' => 'Notification Block',

    // Notification block on the detail / create / edit views
    'LBL_TARGETS' => 'Notify',
    'LBL_NO_TARGETS' => 'No one to notify',
    'LBL_TARGET_TYPE_role' => 'Role',
    'LBL_TARGET_TYPE_group' => 'Group',
    'LBL_TARGET_TYPE_user' => 'User',
    'LBL_DISABLED_SUFFIX' => ' (Inactive)',
    'LBL_ADD_TARGETS' => 'Select who to notify',
    'LBL_SEARCH_TARGETS' => 'Filter by name',
    'LBL_NO_CANDIDATES' => 'No candidates',
    'LBL_MORE_CANDIDATES' => 'Showing the first 50 candidates. Type a name to narrow down',
    'LBL_REMOVE_TARGET' => 'Remove',
    'LBL_EDIT_TARGETS' => 'Change who to notify',
    'LBL_SAVE' => 'Save',
    'LBL_CANCEL' => 'Cancel',
    'LBL_TARGETS_SAVED' => 'Saved who to notify',
    'LBL_TARGETS_SAVE_FAILED' => 'Could not save who to notify',
    'LBL_SEND_NOTIFICATION' => 'Send Notification',
    'LBL_SENDING_IN_PROGRESS' => 'A notification is being sent. You can send again when it finishes',
    'LBL_SEND_HISTORY' => 'Sending History',
    'LBL_NO_HISTORY' => 'No sending history',
    'LBL_SHOW_ALL_HISTORY' => 'Show all',

    // Send dialog
    'LBL_SEND_DIALOG_TITLE' => 'Send Notification',
    'LBL_RECIPIENTS' => 'Recipients',
    'LBL_RECIPIENT_COUNT' => '%s people',
    'LBL_EXCLUDED' => 'Excluded',
    'LBL_EXCLUDED_DISABLED' => 'Inactive: %s',
    'LBL_EXCLUDED_NO_EMAIL' => 'No email address: %s',
    'LBL_NO_RECIPIENTS' => 'There are no recipients',
    'LBL_COMMENT' => 'Comment',
    'LBL_COMMENT_HELP' => 'Optional. Up to %s characters',
    'LBL_COMMENT_TOO_LONG' => 'The comment can be up to %s characters',
    'LBL_SEND' => 'Send',
    'LBL_SEND_TIMEOUT' => 'Sending is taking a while. Check the result in the sending history',
    'LBL_SEND_ERROR' => 'Could not send the notification',

    // Results
    'LBL_RESULT_SENT' => 'Notification sent (%s people)',
    'LBL_RESULT_PARTIAL' => 'Notification sent (could not send to %2$s of %1$s people)',
    'LBL_RESULT_FAILED' => 'Could not send the notification',
    'LBL_RESULT_NO_SERVER' => 'The notification cannot be sent because the outgoing mail server is not configured',
    'LBL_RESULT_NO_RECIPIENTS_ON_CREATE' => 'The notification was not sent because there are no recipients',
    'LBL_RESULT_NO_PERMISSION' => 'You do not have permission to edit this record',
    'LBL_RESULT_DELETED' => 'The record has been deleted',
    'LBL_RESULT_NO_BLOCK' => 'There is no notification block',

    // Sending history
    'LBL_HISTORY_TITLE' => 'Sending History',
    'LBL_SENT_AT' => 'Sent At',
    'LBL_SENDER' => 'Sender',
    'LBL_METHOD' => 'Method',
    'LBL_RESULT' => 'Result',
    'LBL_METHOD_MANUAL' => 'Manual',
    'LBL_METHOD_CREATE' => 'On Create',
    'LBL_METHOD_EXTERNAL' => 'Other Feature',
    'LBL_STATUS_SENDING' => 'Sending',
    'LBL_STATUS_SENT' => 'Sent',
    'LBL_STATUS_PARTIAL' => 'Partially Failed',
    'LBL_STATUS_FAILED' => 'Failed',
    'LBL_BACK_TO_HISTORY' => 'Back to Sending History',
    'LBL_RECIPIENT_LIST_TITLE' => 'Recipients',
    'LBL_NAME' => 'Name',
    'LBL_EMAIL' => 'Email',
    'LBL_REASON' => 'Reason',
    'LBL_RECIPIENT_STATUS_PENDING' => 'Sending',
    'LBL_RECIPIENT_STATUS_SENT' => 'Sent',
    'LBL_RECIPIENT_STATUS_FAILED' => 'Failed',
    'LBL_RECIPIENT_STATUS_ABORTED' => 'Not Sent (Interrupted)',
    'LBL_REASON_NO_SERVER' => 'The outgoing mail server is not configured',
    'LBL_REASON_ABORTED' => 'Processing stopped while sending',
    'LBL_CLOSE' => 'Close',
    'LBL_RECORD_NOT_FOUND' => 'The record was not found',

    // Placeholders
    'LBL_PLACEHOLDER_SENDER_NAME' => 'Sender Name',
    'LBL_PLACEHOLDER_MODULE_NAME' => 'Module Name',
    'LBL_PLACEHOLDER_RECORD_NAME' => 'Record Name',
    'LBL_PLACEHOLDER_RECORD_URL' => 'Link to the Record',
    'LBL_PLACEHOLDER_COMMENT' => 'Comment',
    'LBL_PLACEHOLDER_SENT_AT' => 'Sent At',

    // Default mail (used in the recipient's language)
    'LBL_DEFAULT_MAIL_SUBJECT' => '[Notification] {module_name}: {record_name}',
    'LBL_DEFAULT_MAIL_INTRO' => '{sender_name} sent you a notification about {module_name}.',
    'LBL_DEFAULT_MAIL_RECORD' => 'Record: {record_name}',
    'LBL_DEFAULT_MAIL_LINK' => 'Link: {record_url}',
    'LBL_DEFAULT_MAIL_COMMENT' => 'Comment: {comment}',
    'LBL_DEFAULT_MAIL_SENT_AT' => 'Sent at: {sent_at}',
];

$jsLanguageStrings = [
];
