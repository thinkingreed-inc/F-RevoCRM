<?php
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
require_once('setup/utils/FRFilterSetting.php');
$log->debug("[START] Add cron retry timeout column.\n");
global $adb;
// vtiger_cron_taskテーブルにretry_timeoutカラムが存在しているかチェック
$result = $adb->pquery('SHOW COLUMNS FROM vtiger_cron_task LIKE ?', array('retry_timeout'));
if ($adb->num_rows($result) == 0) {
    // 存在していない場合は追加
    $adb->query('ALTER TABLE vtiger_cron_task ADD COLUMN retry_timeout INT DEFAULT 0');
}
// 各タスクに対するリトライタイムアウトを設定
$tasks = array(
    'Workflow' => 60*60,
    'RecurringInvoice' => 5*60*60,
    'SendReminder' => 60*60,
    'MailScanner' => 60*60,
    'Scheduled Import' => 24*60*60,
    'ScheduleReports' => 3*60*60,
);
foreach ($tasks as $task => $timeout) {
    $adb->pquery('UPDATE vtiger_cron_task SET retry_timeout = ? WHERE name = ?', array($timeout, $task));
}
$log->debug("[END] Add cron retry timeout column.\n");