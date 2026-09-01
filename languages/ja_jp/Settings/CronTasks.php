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
	'CronTasks' => 'スケジューラ',

	//Basic Field Names
	'Id' => 'Id',
	'Cron Job' => 'Cronジョブ',
	'Frequency' => '周期',
	'Status' => 'ステータス',
	'Last Start' => '最終開始日時',
	'Last End' => '最終終了日時',
	'Sequence' => '順序',

	//Actions
	'LBL_COMPLETED' => '完了',
	'LBL_RUNNING' => '実行中',
	'LBL_ACTIVE' => '有効',
	'LBL_INACTIVE' => '無効',

	//F-RevoCRM
	'Frequency(H:M)' => '周期（時:分）',
	'Recommended frequency for Workflow is 15 mins' => '推奨周期は15分です',
	'Recommended frequency for RecurringInvoice is 12 hours' => '繰り返し請求の推奨周期は12時間です',
	'Recommended frequency for SendReminder is 15 mins' => 'リマインダーの推奨周期は15分です',
	'Recommended frequency for MailScanner is 15 mins' => 'メールスキャナーの推奨周期は15分です',
	'Recommended frequency for ScheduleReports is 15 mins' => 'スケジュールレポートの推奨周期は15分です',

	//F-RevoCRM スケジューラーの安定化（#1823）
	'LBL_NEXT_RUN_AT' => '次回実行予定',
	'LBL_RETRY_TIMEOUT' => 'タイムアウト',
	'LBL_RETRY_TIMEOUT_INFO' => '実行を開始してからこの時間を過ぎても終わらないタスクは、ハングしたものとして扱います。プロセスを終了させて次回以降の実行を再開します。',
	'LBL_DEFAULT_VALUE' => '既定',
	'LBL_EMPTY_MEANS_DEFAULT' => '空欄で既定値',

	// 実行状態（vtigercron.php --status と同じ判定）
	'LBL_STARTING' => '起動中',
	'LBL_NOT_STARTED' => '起動失敗',
	'LBL_TASK_DEAD' => '異常終了',
	'LBL_TASK_HUNG' => 'ハングの疑い',
	'LBL_TASK_STALE' => '担当サーバー応答なし',
	'LBL_RUNNING_ON_OTHER_HOST' => '他サーバーで実行中',
	'LBL_STATE_UNKNOWN' => '状態不明',

	// 実行タイミングの指定
	'LBL_SCHEDULE_TYPE' => '実行タイミング',
	'LBL_SCHEDULE_INTERVAL' => '一定の周期で実行',
	'LBL_SCHEDULE_DAILY' => '毎日、決まった時刻に実行',
	'LBL_SCHEDULE_WEEKLY' => '毎週、決まった曜日に実行',
	'LBL_SCHEDULE_MONTHLY' => '毎月、決まった日に実行',
	'LBL_RUN_AT_TIME' => '実行時刻',
	'LBL_RUN_ON_WEEKDAY' => '実行する曜日',
	'LBL_WEEKDAY_SEPARATOR' => '・',
	'LBL_WEEKLY_SCHEDULE_INFO' => '曜日は複数選択できます。選んだ曜日それぞれの指定時刻に実行します。',
	'JS_PLEASE_SELECT_AT_LEAST_ONE_WEEKDAY' => '曜日を1つ以上選択してください',
	'LBL_RUN_ON_DAY' => '実行する日',
	'LBL_DAILY_AT' => '毎日',
	'LBL_WEEKLY_AT' => '毎週',
	'LBL_MONTHLY_AT' => '毎月',
	'LBL_LAST_DAY_OF_MONTH' => '月末',
	'LBL_DAY_OF_MONTH_FORMAT' => '%s日',
	'LBL_SUNDAY' => '日',
	'LBL_MONDAY' => '月',
	'LBL_TUESDAY' => '火',
	'LBL_WEDNESDAY' => '水',
	'LBL_THURSDAY' => '木',
	'LBL_FRIDAY' => '金',
	'LBL_SATURDAY' => '土',
	'LBL_FIXED_TIME_SCHEDULE_INFO' => 'スケジューラー（cron）の起動間隔より細かい時刻は指定できません。指定した時刻を過ぎた直後の起動で実行されます。',
	'LBL_MONTHLY_SCHEDULE_INFO' => '「月末」を選ぶと、月の日数に関わらずその月の最後の日に実行します。29〜31日を選んだ場合、その日が無い月はその月の末日に実行します。',
	'LBL_FREQUENCY_NOT_ALIGNED' => 'この周期は1日を割り切れないため、実行予定時刻が固定されません。実行が遅れるとその分だけ次回以降の時刻がずれていきます。時刻を固定したい場合は「毎日決まった時刻に実行」を選んでください。',

	// 並列実行
	'LBL_MAX_PARALLEL' => '並列実行可能数',
	'LBL_MAX_PARALLEL_INFO' => '同時に実行するタスク数の上限です。上限に達した回は、実行時刻を迎えていても次回のスケジューラー（cron）起動まで見送られます。変更は config.inc.php の $cron_max_parallel で行います。',
	'LBL_PARALLEL_UNAVAILABLE_INFO' => 'この環境では並列実行が使えないため、タスクは 1 つずつ順番に実行されます（exec()／popen() が無効、または $cron_max_parallel が 1）。',

	// 実行ログ
	'LBL_VIEW_LOG' => '実行ログ',
	'LBL_EXECUTION_LOG' => '実行ログ',
	'LBL_NO_EXECUTION_LOG' => '実行ログがまだありません。並列実行（振り分けモード）で実行されたタスクのログのみ記録されます。',
	'LBL_LOG_TRUNCATED' => 'ログが大きいため末尾のみ表示しています。表示行数',
	'LBL_LAST_WRITTEN' => '最終書き込み',
	'LBL_LOG_RETENTION_COUNT' => 'ログの保持世代数',
	'LBL_LOG_RETENTION_COUNT_INFO' => '実行ログは日付ごとにファイルが分かれます。新しいものをこの数だけ残し、それより古いものは自動で削除します。0 を指定すると削除しません。空欄にすると config.inc.php の既定値に従います。',
	'LBL_LOG_RETENTION_UNLIMITED' => '無期限',
	'LBL_GENERATIONS' => '世代',
	'LBL_CLOSE' => '閉じる',
);
