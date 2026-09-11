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
	'CronTasks' => 'Scheduler',

	//Basic Field Names
	'Id' => 'Id',
	'Cron Job' => 'Cron Job',
	'Frequency' => 'Frequency',
	'Status' => 'Status',
	'Last Start' => 'Last scan started',
	'Last End' => 'Last scan ended',
	'Sequence' => 'Sequence',

	//Actions
	'LBL_COMPLETED' => 'Completed',
	'LBL_RUNNING' => 'Running',
	'LBL_ACTIVE' => 'Active',
	'LBL_INACTIVE' => 'In Active',

	//F-RevoCRM
	'Frequency(H:M)' => 'Frequency (H:M)',
	'Recommended frequency for Workflow is 15 mins' => 'Recommended frequency for Workflow is 15 mins',
	'Recommended frequency for RecurringInvoice is 12 hours' => 'Recommended frequency for RecurringInvoice is 12 hours',
	'Recommended frequency for SendReminder is 15 mins' => 'Recommended frequency for SendReminder is 15 mins',
	'Recommended frequency for MailScanner is 15 mins' => 'Recommended frequency for MailScanner is 15 mins',
	'Recommended frequency for ScheduleReports is 15 mins' => 'Recommended frequency for ScheduleReports is 15 mins',

	//F-RevoCRM scheduler stability (#1823)
	'LBL_NEXT_RUN_AT' => 'Next Run',
	'LBL_RETRY_TIMEOUT' => 'Timeout',
	'LBL_RETRY_TIMEOUT_INFO' => 'A task still running after this period is treated as hung. Its process is terminated so that later runs can resume.',
	'LBL_DEFAULT_VALUE' => 'default',
	'LBL_EMPTY_MEANS_DEFAULT' => 'leave empty to use the default',

	// Runtime state (same decision as vtigercron.php --status)
	'LBL_STARTING' => 'Starting',
	'LBL_NOT_STARTED' => 'Not started',
	'LBL_TASK_DEAD' => 'Terminated abnormally',
	'LBL_TASK_HUNG' => 'Possibly hung',
	'LBL_TASK_STALE' => 'Owner not responding',
	'LBL_RUNNING_ON_OTHER_HOST' => 'Running on another server',
	'LBL_STATE_UNKNOWN' => 'Unknown',

	// Schedule
	'LBL_SCHEDULE_TYPE' => 'Schedule',
	'LBL_SCHEDULE_INTERVAL' => 'Run at a fixed interval',
	'LBL_SCHEDULE_DAILY' => 'Run daily at a given time',
	'LBL_SCHEDULE_WEEKLY' => 'Run weekly on a given day',
	'LBL_SCHEDULE_MONTHLY' => 'Run monthly on a given date',
	'LBL_RUN_AT_TIME' => 'Run at',
	'LBL_RUN_ON_WEEKDAY' => 'Day of week',
	'LBL_WEEKDAY_SEPARATOR' => ', ',
	'LBL_WEEKLY_SCHEDULE_INFO' => 'You can select more than one day. The task runs at the given time on each selected day.',
	'JS_PLEASE_SELECT_AT_LEAST_ONE_WEEKDAY' => 'Please select at least one day of the week',
	'LBL_RUN_ON_DAY' => 'Day of month',
	'LBL_DAILY_AT' => 'Daily',
	'LBL_WEEKLY_AT' => 'Weekly',
	'LBL_MONTHLY_AT' => 'Monthly',
	'LBL_LAST_DAY_OF_MONTH' => 'Last day of month',
	'LBL_DAY_OF_MONTH_FORMAT' => 'day %s',
	'LBL_SUNDAY' => 'Sunday',
	'LBL_MONDAY' => 'Monday',
	'LBL_TUESDAY' => 'Tuesday',
	'LBL_WEDNESDAY' => 'Wednesday',
	'LBL_THURSDAY' => 'Thursday',
	'LBL_FRIDAY' => 'Friday',
	'LBL_SATURDAY' => 'Saturday',
	'LBL_FIXED_TIME_SCHEDULE_INFO' => 'The time cannot be finer than the interval the scheduler (cron) itself is started at. The task runs on the first start after the given time.',
	'LBL_MONTHLY_SCHEDULE_INFO' => 'Choosing "Last day of month" runs the task on the final day whatever the month length. If you choose 29-31, months without that date run on their last day.',
	'LBL_FREQUENCY_NOT_ALIGNED' => 'This interval does not divide a day evenly, so run times are not pinned to fixed slots. Any delay carries over to later runs. Choose "Run daily at a given time" to pin the time.',

	// Parallel execution
	'LBL_MAX_PARALLEL' => 'Max parallel tasks',
	'LBL_MAX_PARALLEL_INFO' => 'The maximum number of tasks run at the same time. Once the limit is reached, tasks that are due are deferred to the next scheduler (cron) run. Change it with $cron_max_parallel in config.inc.php.',
	'LBL_PARALLEL_UNAVAILABLE_INFO' => 'Parallel execution is not available in this environment, so tasks run one at a time (exec()/popen() disabled, or $cron_max_parallel is 1).',

	// Execution log
	'LBL_VIEW_LOG' => 'Execution log',
	'LBL_EXECUTION_LOG' => 'Execution log',
	'LBL_NO_EXECUTION_LOG' => 'No execution log yet. Logs are recorded only for tasks run in parallel (dispatch) mode.',
	'LBL_LOG_TRUNCATED' => 'The log is large, so only the end is shown. Lines shown',
	'LBL_LAST_WRITTEN' => 'Last written',
	'LBL_LOG_RETENTION_COUNT' => 'Log generations to keep',
	'LBL_LOG_RETENTION_COUNT_INFO' => 'Execution logs are split per day. The newest files up to this number are kept and older ones are removed automatically. 0 keeps everything. Leave empty to use the default from config.inc.php.',
	'LBL_LOG_RETENTION_UNLIMITED' => 'Unlimited',
	'LBL_GENERATIONS' => 'generations',
	'LBL_CLOSE' => 'Close',
);
