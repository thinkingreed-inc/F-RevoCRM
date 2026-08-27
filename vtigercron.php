<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

/**
 * Start the cron services configured.
 *
 * 実行モードは 4 つある。
 *
 *   1. 振り分け（既定・CLI で引数なし）
 *        実行時刻を迎えたタスクを 1 タスク 1 プロセスで起動し、完了を待たずに終了する。
 *        1 タスクの異常終了・長時間実行が他タスクの実行を妨げないようにするための本体。
 *          php -f vtigercron.php
 *
 *   2. 子プロセス（--child --service=<名前>）
 *        振り分けモードから起動される。実行権は親が獲得済みのため再取得しない。
 *
 *   3. 単体実行（--service=<名前> / ?service=<名前>）
 *        指定した 1 タスクをこのプロセスで同期実行する。手動実行・調査用。
 *
 *   4. 逐次実行（--serial、または並列実行が使えない環境でのフォールバック）
 *        従来通り全タスクを順番に実行する。config.inc.php で $cron_max_parallel = 1
 *        とした場合もこのモードになる。
 *        ただし 1 タスクずつ子プロセスへ追い出して待ち合わせる。fatal error は
 *        try/catch で捕捉できずプロセスごと停止するため、1 プロセスで全タスクを
 *        実行すると 1 タスクの fatal error で後続タスクが実行されなくなる。
 *        子プロセスを起動できない環境（exec()/popen() が無効）では従来通り同じ
 *        プロセスで実行し、停止した場合は実行されなかったタスクを報告する。
 *
 * このほか --status で、タスクを実行せずに現在の状態（実行中プロセスの生死を含む）を
 * 一覧表示できる。ハングしたタスクがあれば終了コード 1 を返すので監視から利用できる。
 *          php -f vtigercron.php -- --status
 */

// 子プロセスとして起動された場合でも相対 include を解決できるようにする
chdir(dirname(__FILE__));

include_once 'vtlib/Vtiger/Cron.php';
require_once 'config.inc.php';
require_once('modules/Emails/mail.php');

if (file_exists('config_override.php')) {
	include_once 'config_override.php';
}

// Extended inclusions
require_once 'includes/Loader.php';
vimport ('includes.runtime.EntryPoint');
require_once 'include/utils/CronDispatcher.php';

$site_URLArray = explode('/',$site_URL);

$version = explode('.', phpversion());

$php = ($version[0] * 10000 + $version[1] * 100 + $version[2]);
if($php <  50300){
	$hostName = php_uname('n');
} else {
	$hostName = gethostname();
}

$mailbody ="Instance dir : $root_directory <br/> Site Url : $site_URL <br/> Host Name : $hostName<br/>";
$mailSubject = "[Alert] ";

function vtigercron_detect_run_in_cli(){
	return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' ||  is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}

/**
 * コマンドライン引数を解析する。
 * PHP CLI は $_REQUEST を argv から組み立てないため、明示的に解釈する。
 *
 * @param array $argv
 * @return array service / child / serial をキーに持つ配列
 */
function vtigercron_parse_arguments($argv) {
	$options = array('service' => false, 'child' => false, 'serial' => false, 'status' => false);
	if (!is_array($argv)) {
		return $options;
	}
	foreach (array_slice($argv, 1) as $argument) {
		if ($argument === '--child') {
			$options['child'] = true;
		} else if ($argument === '--serial') {
			$options['serial'] = true;
		} else if ($argument === '--status') {
			$options['status'] = true;
		} else if (strpos($argument, '--service=') === 0) {
			$options['service'] = substr($argument, strlen('--service='));
		}
	}
	return $options;
}

/**
 * 各 cron タスクの状態を一覧表示する（--status）。
 *
 * 自分のサーバーが担当しているタスクは、子プロセスの生死まで確認して分類する。
 *   RUNNING  : 正常に実行中
 *   STARTING : 実行権を取った直後で、子プロセスがまだ起動途中
 *   NOSTART  : 猶予を過ぎても子プロセスが起動していない（PHP バイナリを実行できない等）
 *   HUNG     : 子プロセスは生きているが retry_timeout を超過している（ハングの疑い）
 *   DEAD     : 子プロセスが存在しない（異常終了して実行状態が残っている）
 *   UNKNOWN  : 生死を判定できない環境
 *
 * 他のサーバーが担当しているタスクはプロセスを確認できないため、ハートビートで判断する。
 *   REMOTE   : 他のサーバーで正常に実行中
 *   STALE    : 他のサーバーの担当だがハートビートが途絶えている（引き継ぎ対象）
 *
 * @param array $cronTasks
 * @return integer 終了コード。要対応の状態があれば 1 を返し、監視から検知できるようにする。
 */
function vtigercron_print_status($cronTasks) {
	$rows = FR_CronDispatcher::describe($cronTasks);

	echo sprintf("this host: %s\n\n", FR_CronDispatcher::getHostName());
	printf("%-20s %-9s %-16s %-20s %-20s %8s %10s %8s\n",
			'NAME', 'STATE', 'HOST', 'LAST START', 'NEXT RUN', 'FREQ', 'ELAPSED', 'PID');
	echo str_repeat('-', 118)."\n";

	$attention = array('HUNG', 'DEAD', 'STALE', 'NOSTART');
	$exitCode = 0;
	foreach ($rows as $row) {
		printf("%-20s %-9s %-16s %-20s %-20s %8s %10s %8s\n",
				mb_strimwidth($row['name'], 0, 20, ''),
				$row['state'],
				$row['host'] === '' ? '-' : mb_strimwidth($row['host'], 0, 16, ''),
				$row['laststart'] > 0 ? date('Y-m-d H:i:s', $row['laststart']) : '-',
				$row['nextrunat'] > 0 ? date('Y-m-d H:i:s', $row['nextrunat']) : '-',
				$row['frequency'].'s',
				$row['elapsed'] === null ? '-' : $row['elapsed'].'s',
				$row['pid'] === null ? '-' : $row['pid']);
		if (in_array($row['state'], $attention, true)) {
			$exitCode = 1;
		}
	}
	return $exitCode;
}

/**
 * cron タスクの実行を開始する。
 *
 * @param Vtiger_Cron $cronTask
 * @param boolean $claimed 呼び出し元（振り分けモード）で既に実行権を獲得済みか
 * @param float $cronRunId
 * @return boolean 実行してよいか
 */
function vtigercron_begin_task($cronTask, $claimed, $cronRunId) {
	global $site_URL;

	$cronTask->setBulkMode(true);

	if (!$claimed) {
		// Not ready to run yet?
		if (!$cronTask->isRunnable()) {
			echo sprintf("[INFO] %s - not ready to run as the time to run again is not completed\n", $cronTask->getName());
			return false;
		}
		if ($cronTask->hadTimedout()) {
			echo sprintf("[INFO] %s - cron task had timedout as it is not completed last time it run- restarting\n", $cronTask->getName());
		}
		// 実行権の獲得。確認と更新を 1 クエリで行うことで、cron プロセスが重なった場合の
		// 二重起動を防ぐ（従来の isRunning() → markRunning() は 2 クエリに分かれていた）。
		if (!FR_CronDispatcher::claim($cronTask)) {
			echo sprintf("[INFO] %s - cron task is already running\n", $cronTask->getName());
			return false;
		}
		// claim() は DB のみを更新するため、インスタンス側の状態も合わせておく
		$cronTask->markRunning();
	}

	// fatal error でこのプロセスが停止した場合に実行状態を解除するため記録しておく
	$GLOBALS['vtigercron_current_task'] = $cronTask;

	// 実行中のまま終わらないタスクを後から判定できるよう、担当サーバーと PID を記録する。
	// 複数のサーバーから参照できるようデータベースに持つ。
	FR_CronDispatcher::recordChildPid($cronTask);

	// このタスクの出力をタスクごとのログファイルへも残す
	vtigercron_start_task_logging($cronTask);

	echo sprintf('[CRON],"%s",%s,%s,"%s","",[STARTS]',$cronRunId,$site_URL,$cronTask->getName(),date('Y-m-d H:i:s',$cronTask->getLastStart()))."\n";
	return true;
}

/**
 * このタスクの出力を、タスクごとのログファイルへ書き出し始める。
 *
 * 振り分けモードでは子プロセスの標準出力をシェルがログへリダイレクトしているため、
 * ログはそちらに残る。一方で逐次実行や単体実行（--service=）はこのプロセスで直接
 * 実行するため、従来はタスクごとのログが残らなかった。実行モードによらず同じ場所に
 * ログが残るよう、リダイレクトが効いていない場合はこちらで書き出す。
 *
 * 出力はバッファのコールバックでファイルへ追記し、そのまま標準出力へも流す。
 * cron のメール通知や画面表示は従来どおりになる。
 *
 * @param Vtiger_Cron $cronTask
 */
function vtigercron_start_task_logging($cronTask) {
	if (!empty($GLOBALS['vtigercron_log_active'])) {
		return;
	}
	// 子プロセスはシェルのリダイレクトで既に同じファイルへ書かれている
	if (!empty($GLOBALS['vtigercron_options']['child'])) {
		return;
	}

	$logFile = FR_CronDispatcher::getLogFile($cronTask);
	// 書き込めない場所ならログを諦めて実行そのものは続ける
	$directory = dirname($logFile);
	if (!is_dir($directory) || !is_writable($directory)) {
		return;
	}

	// ハンドルを持ち回すとシャットダウン時に閉じられている恐れがあるため、
	// コールバックの中で毎回追記する
	$writer = function ($buffer) use ($logFile) {
		if ($buffer !== '') {
			@file_put_contents($logFile, $buffer, FILE_APPEND);
		}
		// 標準出力へはそのまま流す
		return $buffer;
	};

	if (ob_start($writer, 4096)) {
		$GLOBALS['vtigercron_log_active'] = true;
	}
}

/**
 * タスクごとのログ書き出しを終える。
 *
 * 呼ばれないまま停止した場合も、PHP がシャットダウン時にバッファを流すため
 * それまでの出力はログに残る。
 */
function vtigercron_stop_task_logging() {
	if (empty($GLOBALS['vtigercron_log_active'])) {
		return;
	}
	$GLOBALS['vtigercron_log_active'] = false;
	@ob_end_flush();
}

/**
 * タスクのログファイルへ直接追記する。
 *
 * fatal error で停止した場合、PHP は出力バッファのコールバック（ユーザー定義関数）を
 * 呼ばずにバッファを吐き出すため、vtigercron_start_task_logging() の仕組みではログに
 * 残らない。停止の理由はログにこそ残す必要があるため、この経路だけは自分で書き込む。
 *
 * @param Vtiger_Cron $cronTask
 * @param string $message
 */
function vtigercron_append_task_log($cronTask, $message) {
	// 子プロセスはシェルのリダイレクトで既に同じファイルへ書かれているため二重に書かない
	if (!empty($GLOBALS['vtigercron_options']['child'])) {
		return;
	}
	$logFile = FR_CronDispatcher::getLogFile($cronTask);
	if (!is_dir(dirname($logFile))) {
		return;
	}
	@file_put_contents($logFile, $message, FILE_APPEND);
}

/**
 * cron タスクの正常終了を記録する。
 *
 * @param Vtiger_Cron $cronTask
 * @param float $cronRunId
 */
function vtigercron_end_task($cronTask, $cronRunId) {
	global $site_URL;

	$cronTask->markFinished();
	$GLOBALS['vtigercron_current_task'] = null;

	echo "\n".sprintf('[CRON],"%s",%s,%s,"%s","%s",[ENDS]',$cronRunId,$site_URL,$cronTask->getName(),date('Y-m-d H:i:s',$cronTask->getLastStart()),date('Y-m-d H:i:s',$cronTask->getLastEnd()))."\n";

	vtigercron_stop_task_logging();
}

/**
 * cron タスクの異常終了を記録する。
 *
 * catch (Exception) では PHP7 以降の Error（TypeError 等）を捕捉できないため、
 * 呼び出し側は Throwable を捕捉してこの関数へ渡す。
 *
 * @param Vtiger_Cron $cronTask
 * @param Throwable $throwable
 */
function vtigercron_fail_task($cronTask, $throwable) {
	$cronTask->markFinished();
	$GLOBALS['vtigercron_current_task'] = null;

	echo sprintf("[ERROR]: %s - cron task execution throwed exception.\n", $cronTask->getName());
	echo $throwable->getMessage().PHP_EOL;
	echo $throwable->getTraceAsString().PHP_EOL;

	vtigercron_stop_task_logging();
}

/**
 * プロセス終了時に、実行中のまま残ったタスクの実行状態を解除する。
 *
 * fatal error（メモリ不足、max_execution_time 超過など）やハンドラ内の exit() は
 * try/catch で捕捉できず、markFinished() に到達しないまま停止する。その場合タスクは
 * 実行中のまま残り、retry_timeout が経過するまで以降の cron で毎回スキップされてしまう。
 */
function vtigercron_shutdown_handler() {
	if (empty($GLOBALS['vtigercron_current_task'])) {
		return;
	}
	$cronTask = $GLOBALS['vtigercron_current_task'];
	$GLOBALS['vtigercron_current_task'] = null;

	// ここに到達した時点でタスクは正常終了も例外終了もしていない。次回実行できるよう解放する。
	$cronTask->markFinished();

	$error = error_get_last();
	$fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
	if ($error !== null && in_array($error['type'], $fatalTypes, true)) {
		$message = sprintf("[ERROR]: %s - cron task was terminated by a fatal error: %s in %s on line %d\n",
				$cronTask->getName(), $error['message'], $error['file'], $error['line']);
	} else {
		$message = sprintf("[ERROR]: %s - cron task did not complete as the process exited unexpectedly.\n",
				$cronTask->getName());
	}

	// 子プロセスへ分離できない環境では、ここで停止すると後続タスクもこの回は実行されない。
	// 次回の cron で実行されるが、取りこぼしに気付けるよう残ったタスクを報告する。
	if (!empty($GLOBALS['vtigercron_pending_tasks'])) {
		$message .= sprintf("[WARN] these cron task(s) were not executed in this run because the process stopped: %s\n",
				implode(', ', $GLOBALS['vtigercron_pending_tasks']));
	}

	echo $message;
	vtigercron_stop_task_logging();
	// 出力バッファのコールバックは fatal error 時に呼ばれないため、ログへは自分で書く
	vtigercron_append_task_log($cronTask, $message);
}

if (!vtigercron_detect_run_in_cli()
		&& !(isset($_SESSION["authenticated_user_id"]) && isset($_SESSION["app_unique_key"])
			&& $_SESSION["app_unique_key"] == $application_unique_key)) {
	echo("Access denied!");
	exit(1);
}

$vtigercronOptions = vtigercron_parse_arguments(isset($argv) ? $argv : array());
// タスクごとのログ書き出しの判定で参照する
$GLOBALS['vtigercron_options'] = &$vtigercronOptions;
$GLOBALS['vtigercron_log_active'] = false;
if ($vtigercronOptions['service'] === false && isset($_REQUEST['service'])) {
	$vtigercronOptions['service'] = $_REQUEST['service'];
}
if ($vtigercronOptions['child'] && $vtigercronOptions['service'] === false) {
	echo "[ERROR]: --child requires --service=<name>.\n";
	exit(1);
}

$cronRunId = microtime(true);
$cronStarts = date('Y-m-d H:i:s');

//set global current user permissions
global $current_user;
$current_user = Users::getActiveAdminUser();

$GLOBALS['vtigercron_current_task'] = null;
register_shutdown_function('vtigercron_shutdown_handler');

if ($vtigercronOptions['service'] !== false) {
	// Run specific service
	$vtigercronTasks = array(Vtiger_Cron::getInstance($vtigercronOptions['service']));
	if ($vtigercronTasks[0] === false) {
		echo sprintf("[ERROR]: %s - no cron task is registered with this name.\n", $vtigercronOptions['service']);
		exit(1);
	}
} else {
	// Run all service
	$vtigercronTasks = Vtiger_Cron::listAllActiveInstances();
}

if ($vtigercronOptions['status']) {
	// --- 状態表示モード ---------------------------------------------------------
	// タスクは実行せず、実行中タスクの生死だけを確認して一覧表示する。
	exit(vtigercron_print_status($vtigercronTasks));
}

// 振り分けモードを使える条件。単体実行・子プロセス・Web からの実行では使わない。
$vtigercronParallel = !$vtigercronOptions['child']
		&& !$vtigercronOptions['serial']
		&& $vtigercronOptions['service'] === false
		&& vtigercron_detect_run_in_cli()
		&& FR_CronDispatcher::isSupported();

if ($vtigercronParallel) {
	// --- 振り分けモード -------------------------------------------------------
	// 実行対象を子プロセスへ渡し、完了を待たずに終了する。
	// 重いタスクがスロットを占有していても、他のタスクはこの回で起動される。
	echo sprintf('[CRON],"%s",%s,Instance,"%s","",[STARTS]',$cronRunId,$site_URL,$cronStarts)."\n";

	$vtigercronDispatcher = new FR_CronDispatcher();
	$vtigercronSummary = $vtigercronDispatcher->dispatch($vtigercronTasks);

	if ($vtigercronSummary['locked']) {
		echo "[INFO] another dispatcher is already assigning cron tasks - nothing to do\n";
	}
	foreach ($vtigercronSummary['remote'] as $vtigercronName => $vtigercronHost) {
		echo sprintf("[INFO] %s - running on %s\n", $vtigercronName, $vtigercronHost);
	}
	foreach ($vtigercronSummary['stale'] as $vtigercronName => $vtigercronStale) {
		echo sprintf("[WARN] %s - owner %s stopped reporting (last heartbeat %s) - taking over\n",
				$vtigercronName, $vtigercronStale['host'],
				$vtigercronStale['heartbeat'] > 0 ? date('Y-m-d H:i:s', $vtigercronStale['heartbeat']) : 'never');
	}
	foreach ($vtigercronSummary['dead'] as $vtigercronName => $vtigercronPid) {
		echo sprintf("[WARN] %s - was marked as running but its process (pid %d) is gone - released\n",
				$vtigercronName, $vtigercronPid);
	}
	foreach ($vtigercronSummary['notstarted'] as $vtigercronName => $vtigercronElapsed) {
		echo sprintf("[ERROR]: %s - a child process never started (released after %d seconds)"
				. " - check the php binary (\$cron_php_binary) and logs/cron\n",
				$vtigercronName, $vtigercronElapsed);
	}
	foreach ($vtigercronSummary['hung'] as $vtigercronName => $vtigercronHang) {
		echo sprintf("[WARN] %s - still running after %d seconds (retry timeout %d seconds, pid %d) - possibly hung\n",
				$vtigercronName, $vtigercronHang['elapsed'], $vtigercronHang['timeout'], $vtigercronHang['pid']);
	}
	foreach ($vtigercronSummary['killed'] as $vtigercronName => $vtigercronPid) {
		echo sprintf("[WARN] %s - terminated the hung process (pid %d) and released the task\n",
				$vtigercronName, $vtigercronPid);
	}
	if (count($vtigercronSummary['prunedlogs']) > 0) {
		echo sprintf("[INFO] removed %d old log file(s) from %s (keeping %d generation(s) per task by default)\n",
				count($vtigercronSummary['prunedlogs']),
				FR_CronDispatcher::LOG_DIRECTORY,
				FR_CronDispatcher::getLogRetentionCount());
	}
	foreach ($vtigercronSummary['dispatched'] as $vtigercronName) {
		echo sprintf("[INFO] %s - dispatched to a child process\n", $vtigercronName);
	}
	foreach ($vtigercronSummary['skipped'] as $vtigercronName => $vtigercronReason) {
		echo sprintf("[INFO] %s - skipped (%s)\n", $vtigercronName, $vtigercronReason);
	}
	foreach ($vtigercronSummary['failed'] as $vtigercronName) {
		echo sprintf("[ERROR]: %s - failed to start a child process (php binary: %s)\n",
				$vtigercronName, FR_CronDispatcher::getPhpBinary());
	}

	echo sprintf('[CRON],"%s",%s,Instance,"%s","%s",[ENDS]',$cronRunId,$site_URL,$cronStarts,date('Y-m-d H:i:s'))."\n";
	exit(0);
}

// --- 実行モード ---------------------------------------------------------------
// 子プロセス（--child）、単体実行（--service=）、および並列実行が使えない環境での
// 逐次実行フォールバック。
//
// ハンドラの require_once は必ずこのグローバルスコープで行うこと。ハンドラ（.service）は
// $adb / $current_user などをグローバル変数として直接読み書きしているため、関数の中で
// include するとスコープが変わって正しく動作しない。
echo sprintf('[CRON],"%s",%s,Instance,"%s","",[STARTS]',$cronRunId,$site_URL,$cronStarts)."\n";

// 逐次実行で複数タスクを回す場合は、1 タスクずつ子プロセスへ追い出して実行する。
// fatal error（メモリ不足、max_execution_time 超過、ハンドラ内の exit() など）は
// try/catch で捕捉できずプロセスごと停止するため、同じプロセスで全タスクを実行すると
// 1 タスクの fatal error で後続タスクが実行されないままになる。子プロセスを待ち合わせる
// ことで、実行順序を保ったまま停止の影響をそのタスクだけに閉じ込める。
//
// 単体実行（--service=）と子プロセス（--child）は 1 タスクだけなので分離しない。
$vtigercronIsolate = $vtigercronOptions['service'] === false
		&& !$vtigercronOptions['child']
		&& count($vtigercronTasks) > 1
		&& vtigercron_detect_run_in_cli()
		&& FR_CronDispatcher::isChildLaunchable();
$vtigercronForegroundDispatcher = $vtigercronIsolate ? new FR_CronDispatcher() : null;

// 分離できない環境で fatal error が起きた場合に、この回で実行されなかったタスクを
// 報告できるようにしておく（shutdown handler が参照する）
$GLOBALS['vtigercron_pending_tasks'] = array();

foreach ($vtigercronTasks as $vtigercronIndex => $vtigercronTask) {
	$GLOBALS['vtigercron_pending_tasks'] = array_map(
			function ($pending) { return $pending->getName(); },
			array_slice($vtigercronTasks, $vtigercronIndex + 1));

	if ($vtigercronIsolate) {
		// 子プロセスの起動自体が無駄にならないよう、実行対象かどうかは先にここで確かめる。
		// 実行権の獲得は子プロセス側の claim() が行うため、二重起動にはならない。
		if (!$vtigercronTask->isRunnable()) {
			echo sprintf("[INFO] %s - not ready to run as the time to run again is not completed\n", $vtigercronTask->getName());
			continue;
		}
		$vtigercronExitCode = $vtigercronForegroundDispatcher->runForeground($vtigercronTask);
		if ($vtigercronExitCode === null) {
			// 子プロセスを起動できなかった。このタスクはこのプロセスで実行する。
			echo sprintf("[WARN] %s - could not run in a separate process (php binary: %s) - running inline\n",
					$vtigercronTask->getName(), FR_CronDispatcher::getPhpBinary());
		} else {
			continue;
		}
	}

	if (!vtigercron_begin_task($vtigercronTask, $vtigercronOptions['child'], $cronRunId)) {
		continue;
	}
	try {
		// checkFileAccess() は条件を満たさないと die() でプロセス全体を止めてしまうため使わない。
		// ハンドラファイルが無いタスクが 1 つあるだけで後続タスクが道連れになるのを防ぐ。
		// また、存在しないファイルの require_once は捕捉できない fatal error になるため、
		// 読み込む前に必ずここで判定する。
		$vtigercronHandlerFile = $vtigercronTask->getHandlerFile();
		if (!isFileAccessible($vtigercronHandlerFile)) {
			throw new RuntimeException(sprintf(
					'handler file is missing or outside the application directory: %s', $vtigercronHandlerFile));
		}
		require_once $vtigercronHandlerFile;

		vtigercron_end_task($vtigercronTask, $cronRunId);
	} catch (Throwable $vtigercronThrowable) {
		// Throwable を捕捉することで、1 タスクの失敗で後続タスクが止まらないようにする
		vtigercron_fail_task($vtigercronTask, $vtigercronThrowable);
	}
}

$cronEnds = date('Y-m-d H:i:s');
echo sprintf('[CRON],"%s",%s,Instance,"%s","%s",[ENDS]',$cronRunId,$site_URL,$cronStarts,$cronEnds)."\n";
