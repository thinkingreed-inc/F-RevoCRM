<?php
/*+**********************************************************************************
 * F-RevoCRM
 *
 * cron タスクを子プロセスへ振り分けて並列実行するための補助クラス。
 *
 * 従来の vtigercron.php は登録済みの全 cron タスクを 1 プロセスの逐次ループで実行していた。
 * そのため以下の問題があった。
 *   - 1 タスクが fatal error でプロセスごと落ちると、後続タスクが 1 件も実行されない
 *   - 1 タスクの実行に時間が掛かると、後続タスクの開始がその分だけ遅れる
 *     （遅延が laststart に積み上がり「15 分毎」の設定でも実行時刻がずれていく）
 *
 * 本クラスは実行対象のタスクを 1 タスク 1 プロセスに切り出し、親プロセスは完了を待たずに
 * 終了する。これにより 1 タスクの異常終了・長時間実行が他タスクへ波及しなくなる。
 *********************************************************************************** */

include_once 'vtlib/Vtiger/Cron.php';

class FR_CronDispatcher {

	/** 同時に実行する子プロセス数の既定値 */
	const DEFAULT_MAX_PARALLEL = 4;

	/**
	 * retry_timeout が未設定（0）のタスクに適用する既定のタイムアウト秒数。
	 *
	 * Vtiger_Cron::register() は retry_timeout を設定しないため、独自に登録された
	 * タスクは retry_timeout = 0 になる。そのまま「経過時間 > retry_timeout」で
	 * 判定するとタイムアウト扱いが常に成立し、実行中のタスクを二重起動してしまう。
	 */
	const DEFAULT_RETRY_TIMEOUT = 3600;

	/** 子プロセスの出力先ディレクトリ */
	const LOG_DIRECTORY = 'logs/cron';

	/** @var boolean 名前付きロックを保持しているか */
	protected $lockAcquired = false;

	/**
	 * 同時実行数の上限。config.inc.php の $cron_max_parallel で変更できる。
	 * 1 以下を指定すると並列実行を行わず、従来通りの逐次実行にフォールバックする。
	 * @return integer
	 */
	public static function getMaxParallel() {
		global $cron_max_parallel;
		if (!isset($cron_max_parallel)) {
			return self::DEFAULT_MAX_PARALLEL;
		}
		return intval($cron_max_parallel);
	}

	/**
	 * 互いに同時実行させたくないタスク名。config.inc.php の $cron_serial_tasks で指定する。
	 *
	 * ここに挙げたタスクは「同時に 1 つまで」に制限される。指定外のタスクとは並列に走るため、
	 * 重いタスクが他タスクの実行を止めることはない。
	 * @return array
	 */
	public static function getSerialTaskNames() {
		global $cron_serial_tasks;
		if (isset($cron_serial_tasks) && is_array($cron_serial_tasks)) {
			return $cron_serial_tasks;
		}
		return array();
	}

	/**
	 * retry_timeout が未設定のタスクに適用するタイムアウト秒数。
	 * config.inc.php の $cron_default_retry_timeout で変更できる。
	 * @return integer
	 */
	public static function getDefaultRetryTimeout() {
		global $cron_default_retry_timeout;
		if (isset($cron_default_retry_timeout) && intval($cron_default_retry_timeout) > 0) {
			return intval($cron_default_retry_timeout);
		}
		return self::DEFAULT_RETRY_TIMEOUT;
	}

	/**
	 * retry_timeout を過ぎても生存し続けている（ハングしたとみなせる）子プロセスを
	 * 強制終了するか。config.inc.php の $cron_kill_timed_out で変更できる。
	 *
	 * 既定は true。従来はタイムアウトしたタスクを「古いプロセスを残したまま」再実行して
	 * いたため、ハングしたタスクのプロセスが実行の度に積み上がっていた。終了させてから
	 * 解放することで、自動復帰させつつ二重起動を防ぐ。
	 *
	 * false にした場合は警告を出すだけで再実行もしない。原因調査を優先したい場合に使う。
	 * @return boolean
	 */
	public static function shouldKillTimedOut() {
		global $cron_kill_timed_out;
		return isset($cron_kill_timed_out) ? (bool) $cron_kill_timed_out : true;
	}

	/**
	 * 子プロセスの起動に使う PHP バイナリ。
	 * PHP-FPM 等から起動された場合 PHP_BINARY は CLI バイナリを指さないため、
	 * CLI SAPI のときだけ PHP_BINARY を採用する。
	 * @return string
	 */
	public static function getPhpBinary() {
		global $cron_php_binary;
		if (!empty($cron_php_binary)) {
			return $cron_php_binary;
		}
		if (defined('PHP_BINARY') && PHP_BINARY !== '' && strpos(PHP_SAPI, 'cli') === 0) {
			return PHP_BINARY;
		}
		return 'php';
	}

	/**
	 * F-RevoCRM のインストールディレクトリ（末尾のセパレータなし）。
	 * @return string
	 */
	public static function getRootDirectory() {
		global $root_directory;
		if (!empty($root_directory)) {
			return rtrim($root_directory, '/\\');
		}
		return rtrim(realpath(dirname(__FILE__) . '/../..'), '/\\');
	}

	/**
	 * @return boolean Windows 環境か
	 */
	public static function isWindows() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	/**
	 * 並列実行が可能な環境か。
	 *
	 * 共有ホスティング等で exec()/popen() が無効化されている場合は false を返し、
	 * 呼び出し側は従来通りの逐次実行へフォールバックする。
	 * @return boolean
	 */
	public static function isSupported() {
		if (self::getMaxParallel() <= 1) {
			return false;
		}
		$required = self::isWindows() ? 'popen' : 'exec';
		if (!function_exists($required)) {
			return false;
		}
		$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
		return !in_array($required, $disabled, true);
	}

	/**
	 * 振り分け処理の排他ロックを取得する。
	 *
	 * 保持するのは「実行対象の選定と子プロセスの起動」の間だけで、タスクの実行時間中は
	 * 保持しない。そのため次回の cron 起動がこのロックで待たされることはない。
	 *
	 * @return boolean 取得できたか（他のディスパッチャが処理中なら false）
	 */
	public function acquireLock() {
		global $adb;

		// ファイルロックはサーバーをまたげないため、データベースの名前付きロックを使う。
		// 同じ MySQL サーバーに複数の F-RevoCRM が同居していても衝突しないよう、
		// ロック名にデータベース名を含める。
		$result = $adb->pquery('SELECT GET_LOCK(?, 0) AS acquired', array(self::getLockName()));
		if (!$result || !$adb->num_rows($result)) {
			// ロックを扱えない場合はロック無しで続行する。実行権の獲得は claim() が
			// 1 クエリで排他的に行うため、タスクの二重実行までは起きない。
			return true;
		}

		$acquired = $adb->query_result($result, 0, 'acquired');
		if ($acquired === null || $acquired === '') {
			// エラー時（NULL）はロック無しで続行する
			return true;
		}
		if (intval($acquired) !== 1) {
			return false;
		}

		$this->lockAcquired = true;
		return true;
	}

	/**
	 * 振り分け処理の排他ロックを解放する。
	 *
	 * プロセスが異常終了した場合も、接続が切れた時点で MySQL 側が自動的に解放する。
	 * ディスパッチャは CLI で実行され、プロセス終了とともに接続も切れるため取り残されない。
	 */
	public function releaseLock() {
		global $adb;

		if (!$this->lockAcquired) {
			return;
		}
		$adb->pquery('SELECT RELEASE_LOCK(?)', array(self::getLockName()));
		$this->lockAcquired = false;
	}

	/**
	 * 名前付きロックの名前。データベース名で名前空間を分ける。
	 * @return string
	 */
	public static function getLockName() {
		global $dbconfig;

		$database = (isset($dbconfig['db_name']) && $dbconfig['db_name'] !== '') ? $dbconfig['db_name'] : 'frevocrm';
		// MySQL の名前付きロックは 64 文字まで
		return substr('frevocrm_cron_dispatch_' . $database, 0, 64);
	}

	/**
	 * このサーバーを識別する名前。
	 * @return string
	 */
	public static function getHostName() {
		global $cron_host_name;

		if (!empty($cron_host_name)) {
			return $cron_host_name;
		}
		$hostName = gethostname();
		return ($hostName === false || $hostName === '') ? 'unknown' : $hostName;
	}

	/**
	 * 担当サーバーが落ちたとみなすまでの秒数。
	 *
	 * 担当サーバーのディスパッチャは、自分が起動した子プロセスの生存を確認するたびに
	 * last_heartbeat を更新する。この秒数を超えて更新が止まっていれば、担当サーバー自体が
	 * 停止したとみなし、他のサーバーがタスクを引き継ぐ。
	 *
	 * cron の起動間隔（1 分）の数回分を見込んだ値にしておくこと。
	 * @return integer
	 */
	public static function getHeartbeatTimeout() {
		global $cron_heartbeat_timeout;

		if (isset($cron_heartbeat_timeout) && intval($cron_heartbeat_timeout) > 0) {
			return intval($cron_heartbeat_timeout);
		}
		return 300;
	}

	/**
	 * タスクの実行権を 1 クエリで獲得する（compare-and-swap）。
	 *
	 * 従来の「isRunning() で確認してから markRunning() する」流れは 2 クエリに分かれており、
	 * 複数の cron プロセスが同時に走ると双方が確認を通過して二重起動する余地があった。
	 * status の判定と更新を 1 文にまとめ、更新件数が 1 件のときだけ実行権を得たとみなす。
	 *
	 * 実行中（status = RUNNING）のタスクを取り直せるのは次の場合。
	 *   - ハートビートが途絶えている（担当サーバーが落ちた）
	 *   - ハートビートが記録されていない古いデータで、retry_timeout を過ぎている
	 *
	 * 担当サーバーが生きていて子プロセスも動いている限りハートビートは更新され続けるため、
	 * 実行に何時間かかるタスクでも他のサーバーに横取りされることはない。
	 *
	 * 時刻の比較はすべてデータベースの時計（UNIX_TIMESTAMP）で行う。サーバーごとの
	 * 時計のずれで「開始したばかりのタスク」をタイムアウトと誤判定しないため。
	 *
	 * @param Vtiger_Cron $cronTask
	 * @return boolean 実行権を獲得できたか
	 */
	public static function claim($cronTask) {
		global $adb;

		$sql = 'UPDATE vtiger_cron_task
				   SET status = ?, laststart = UNIX_TIMESTAMP(), lastend = 0,
					   owner_host = ?, owner_pid = 0, last_heartbeat = UNIX_TIMESTAMP()
				 WHERE id = ?
				   AND status <> ?
				   AND (status <> ?
						OR (last_heartbeat > 0
							AND (UNIX_TIMESTAMP() - last_heartbeat) > ?)
						OR (last_heartbeat = 0 AND laststart > 0 AND lastend = 0
							AND (UNIX_TIMESTAMP() - laststart) > (CASE WHEN retry_timeout > 0 THEN retry_timeout ELSE ? END)))';
		$params = array(
			Vtiger_Cron::$STATUS_RUNNING,
			self::getHostName(),
			$cronTask->getId(),
			Vtiger_Cron::$STATUS_DISABLED,
			Vtiger_Cron::$STATUS_RUNNING,
			self::getHeartbeatTimeout(),
			self::getDefaultRetryTimeout(),
		);

		$result = $adb->pquery($sql, $params);
		if (!$result) {
			return false;
		}
		return intval($adb->getAffectedRowCount($result)) === 1;
	}

	/**
	 * 子プロセスが自分の PID を記録する。
	 *
	 * PID ファイルではなくデータベースに持つことで、他のサーバーからも
	 * 「どのサーバーの、どのプロセスが担当か」を確認できるようにする。
	 *
	 * @param Vtiger_Cron $cronTask
	 */
	public static function recordChildPid($cronTask) {
		global $adb;

		$adb->pquery('UPDATE vtiger_cron_task SET owner_host = ?, owner_pid = ?, last_heartbeat = UNIX_TIMESTAMP() WHERE id = ?',
				array(self::getHostName(), getmypid(), $cronTask->getId()));
	}

	/**
	 * 担当サーバーが「子プロセスはまだ動いている」ことを記録する。
	 *
	 * これが途絶えることが、担当サーバーが落ちたことの唯一の手掛かりになる。
	 *
	 * @param Vtiger_Cron $cronTask
	 */
	public static function heartbeat($cronTask) {
		// インスタンス側の値も一緒に更新されるので、同じ処理の後続の判定が古い値を見ない
		$cronTask->markHeartbeat();
	}

	/**
	 * 実行中（かつ引き継ぎ可能になっていない）タスクの件数。
	 *
	 * 子プロセスは親プロセスから切り離されており、また他のサーバーで動いている場合もあるため、
	 * 実行中の本数は共有されているデータベースの状態から数える。振り分けは名前付きロックで
	 * 直列化されているので、この件数を基準にすれば同時実行数の上限が全サーバー合計で効く。
	 *
	 * @param array $names 対象を特定のタスク名に絞る場合に指定する
	 * @return integer
	 */
	public static function countRunning($names = null) {
		global $adb;

		$sql = 'SELECT COUNT(*) AS running_count FROM vtiger_cron_task
				 WHERE status = ?
				   AND NOT ((last_heartbeat > 0 AND (UNIX_TIMESTAMP() - last_heartbeat) > ?)
							OR (last_heartbeat = 0 AND laststart > 0 AND lastend = 0
								AND (UNIX_TIMESTAMP() - laststart) > (CASE WHEN retry_timeout > 0 THEN retry_timeout ELSE ? END)))';
		$params = array(Vtiger_Cron::$STATUS_RUNNING, self::getHeartbeatTimeout(), self::getDefaultRetryTimeout());

		if (is_array($names) && count($names) > 0) {
			$sql .= ' AND name IN (' . implode(',', array_fill(0, count($names), '?')) . ')';
			$params = array_merge($params, array_values($names));
		}

		$result = $adb->pquery($sql, $params);
		if (!$result || !$adb->num_rows($result)) {
			return 0;
		}
		return intval($adb->query_result($result, 0, 'running_count'));
	}

	/**
	 * 実行が遅れているタスクを先頭に並べ替える。
	 *
	 * 同時実行数の上限に達している状況で常に sequence 順で振り分けると、後ろのタスクが
	 * いつまでも実行されない（飢餓状態になる）。予定時刻からの遅れが大きい順に処理する。
	 *
	 * @param array $cronTasks Vtiger_Cron の配列
	 * @return array 並べ替えた Vtiger_Cron の配列
	 */
	public static function sortByUrgency($cronTasks) {
		$now = time();
		$entries = array();
		foreach (array_values($cronTasks) as $index => $cronTask) {
			// Vtiger_Cron::isRunnable() と同じ基準時刻を使う
			$lastTime = ($cronTask->getLastStart() > 0) ? $cronTask->getLastStart() : $cronTask->getLastEnd();
			$entries[] = array(
				'overdue' => ($now - $lastTime) - $cronTask->getFrequency(),
				'index' => $index,
				'task' => $cronTask,
			);
		}

		usort($entries, function ($a, $b) {
			if ($a['overdue'] === $b['overdue']) {
				// 遅れが同じなら元の sequence 順を保つ
				return $a['index'] - $b['index'];
			}
			return ($a['overdue'] < $b['overdue']) ? 1 : -1;
		});

		$sorted = array();
		foreach ($entries as $entry) {
			$sorted[] = $entry['task'];
		}
		return $sorted;
	}

	/**
	 * 子プロセスの出力先ファイルパス。日付ごとに分けて肥大化を防ぐ。
	 * @param Vtiger_Cron $cronTask
	 * @return string
	 */
	public static function getLogFile($cronTask) {
		$directory = self::getRootDirectory() . DIRECTORY_SEPARATOR . self::LOG_DIRECTORY;
		if (!is_dir($directory)) {
			@mkdir($directory, 0755, true);
		}
		// ファイル名に使えない文字を含むタスク名があり得るため置換する
		$name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $cronTask->getName());
		return $directory . DIRECTORY_SEPARATOR . $name . '_' . date('Ymd') . '.log';
	}

	/**
	 * タスクに適用する実効的なタイムアウト秒数。
	 * retry_timeout が未設定のタスクには既定値を使う。
	 *
	 * @param Vtiger_Cron $cronTask
	 * @return integer
	 */
	public static function getEffectiveRetryTimeout($cronTask) {
		$configured = $cronTask->getRetryTimeout();
		return ($configured > 0) ? $configured : self::getDefaultRetryTimeout();
	}

	/**
	 * 実行中のタスクがタイムアウト秒数を超過しているか。
	 * 超過している場合は実行権を取り直せる（claim() の条件と揃えてある）。
	 *
	 * @param Vtiger_Cron $cronTask
	 * @return boolean
	 */
	public static function hasExceededRetryTimeout($cronTask) {
		if ($cronTask->getLastStart() <= 0) {
			return false;
		}
		return (Vtiger_Cron::currentTime() - $cronTask->getLastStart()) > self::getEffectiveRetryTimeout($cronTask);
	}

	/**
	 * ハートビートが途絶えているか（＝担当サーバーが落ちたとみなせるか）。
	 *
	 * @param Vtiger_Cron $cronTask
	 * @return boolean
	 */
	public static function hasStaleHeartbeat($cronTask) {
		$heartbeat = $cronTask->getLastHeartbeat();
		if ($heartbeat <= 0) {
			// ハートビートが記録されていない古いデータは retry_timeout で判断する
			return self::hasExceededRetryTimeout($cronTask);
		}
		return (Vtiger_Cron::currentTime() - $heartbeat) > self::getHeartbeatTimeout();
	}

	/**
	 * このタスクを実行しているのが自分のサーバーか。
	 *
	 * @param Vtiger_Cron $cronTask
	 * @return boolean
	 */
	public static function isOwnedByThisHost($cronTask) {
		$owner = $cronTask->getOwnerHost();
		return ($owner !== '' && $owner === self::getHostName());
	}

	/**
	 * 指定した PID のコマンドラインを取得する。
	 *
	 * @param integer $pid
	 * @return string|false|null コマンドライン / false=プロセス不在 / null=判定できない環境
	 */
	protected static function readProcessCommandLine($pid) {
		if (!self::isWindows() && is_dir('/proc')) {
			$path = '/proc/' . $pid . '/cmdline';
			if (!file_exists($path)) {
				return false;
			}
			$raw = @file_get_contents($path);
			if ($raw === false) {
				// 他ユーザーのプロセスなどで読めない場合
				return null;
			}
			return str_replace("\0", ' ', $raw);
		}

		if (!function_exists('exec')) {
			return null;
		}

		$output = array();
		$status = 0;
		if (self::isWindows()) {
			@exec(sprintf('wmic process where processid=%d get commandline /format:list', $pid), $output, $status);
		} else {
			@exec(sprintf('ps -p %d -o args=', $pid), $output, $status);
		}
		if (intval($status) !== 0) {
			return false;
		}
		$commandLine = trim(implode(' ', $output));
		return ($commandLine === '') ? false : $commandLine;
	}

	/**
	 * 指定した PID が、このタスクの子プロセスとして実際に動いているか。
	 *
	 * PID の生存だけを見ると危険で、サーバーを再起動した後などは PID が全く無関係な
	 * プロセスに再利用されている。PID ファイルは再起動をまたいで残るため、そのままでは
	 * 無関係なプロセスを「実行中のタスク」と誤認し、強制終了の対象にしてしまう。
	 * コマンドラインを照合し、確かに自分たちが起動した子プロセスである場合だけ true を返す。
	 *
	 * @param integer $pid
	 * @param string $taskName
	 * @return boolean|null true=このタスクの子プロセスが稼働中 / false=いない / null=判定できない環境
	 */
	public static function isTaskProcessRunning($pid, $taskName) {
		if ($pid <= 0) {
			return false;
		}

		$commandLine = self::readProcessCommandLine($pid);
		if ($commandLine === null) {
			return null;
		}
		if ($commandLine === false) {
			return false;
		}

		return (strpos($commandLine, 'vtigercron.php') !== false)
				&& (strpos($commandLine, '--service=' . $taskName) !== false);
	}

	/**
	 * このタスクの子プロセスを終了させる。
	 *
	 * 終了させる直前にもう一度コマンドラインを照合する。判定から終了までの間に
	 * プロセスが入れ替わっていた場合に、無関係なプロセスを巻き込まないため。
	 *
	 * @param integer $pid
	 * @param string $taskName
	 * @return boolean
	 */
	public static function killTaskProcess($pid, $taskName) {
		if (self::isTaskProcessRunning($pid, $taskName) !== true) {
			return false;
		}

		if (self::isWindows()) {
			if (!function_exists('exec')) {
				return false;
			}
			$status = 0;
			$output = array();
			@exec(sprintf('taskkill /F /PID %d', $pid), $output, $status);
			return intval($status) === 0;
		}
		if (function_exists('posix_kill')) {
			return @posix_kill($pid, 15); // SIGTERM
		}
		return false;
	}

	/**
	 * 実行中のまま終わらないタスクを調べ、自分の担当分について後始末をする。
	 *
	 * 自分のサーバーが担当しているタスクだけを対象にする。他のサーバーが担当している
	 * プロセスは生死を確認することも終了させることもできないため、絶対に手を出さない。
	 * 担当サーバーが落ちた場合は、ハートビートの途絶を根拠に claim() が引き継ぐ。
	 *
	 * 自分の担当分の判定は以下の 3 通り。
	 *   dead      : 子プロセスが存在しない。異常終了して実行状態が解除されなかったもの。
	 *               retry_timeout を待たずに解放し、同時実行スロットを空ける。
	 *   hung      : 子プロセスは生きているが retry_timeout を超過している。ハングの疑い。
	 *               $cron_kill_timed_out が true なら強制終了して解放する。
	 *   heartbeat : 正常に実行中。ハートビートを更新し、他サーバーに引き継がれないようにする。
	 *
	 * @param array $cronTasks Vtiger_Cron の配列
	 * @return array dead / hung / killed / remote / stale をキーに持つ配列
	 */
	public static function reap($cronTasks) {
		$findings = array(
			'dead' => array(), 'hung' => array(), 'killed' => array(),
			'remote' => array(), 'stale' => array(),
		);
		$now = Vtiger_Cron::currentTime();

		foreach ($cronTasks as $cronTask) {
			if (!$cronTask->isRunning()) {
				continue;
			}

			$name = $cronTask->getName();

			if (!self::isOwnedByThisHost($cronTask)) {
				// 他のサーバーが担当している。プロセスには触れられないので状態だけ記録する。
				if (self::hasStaleHeartbeat($cronTask)) {
					$findings['stale'][$name] = array(
						'host' => $cronTask->getOwnerHost(),
						'heartbeat' => $cronTask->getLastHeartbeat(),
					);
				} else {
					$findings['remote'][$name] = $cronTask->getOwnerHost();
				}
				continue;
			}

			$pid = $cronTask->getOwnerPid();
			if ($pid <= 0) {
				// 実行権を取った直後で、子プロセスがまだ PID を記録していない。
				// ハートビートは claim() が打ってあるので、次回の起動で改めて確認する。
				continue;
			}

			$alive = self::isTaskProcessRunning($pid, $name);
			if ($alive === null) {
				continue;
			}

			if ($alive === false) {
				// 子プロセスが存在しない。異常終了して実行状態が残っているか、
				// サーバー再起動をまたいで実行中の記録だけが残っている。
				$cronTask->markFinished();
				$findings['dead'][$name] = $pid;
				continue;
			}

			$timeout = self::getEffectiveRetryTimeout($cronTask);
			$elapsed = $now - $cronTask->getLastStart();
			if ($elapsed <= $timeout) {
				// 正常に実行中。担当サーバーが生きていることを示すため記録を更新する。
				// これを続けている限り、他のサーバーはこのタスクを引き継がない。
				self::heartbeat($cronTask);
				continue;
			}

			$findings['hung'][$name] = array('pid' => $pid, 'elapsed' => $elapsed, 'timeout' => $timeout);
			if (self::shouldKillTimedOut() && self::killTaskProcess($pid, $name)) {
				$cronTask->markFinished();
				$findings['killed'][$name] = $pid;
			} else {
				// 終了させない場合も、担当は自分のままなので引き継がれないようにする
				self::heartbeat($cronTask);
			}
		}

		return $findings;
	}

	/**
	 * タスクを子プロセスとして起動する。親プロセスは完了を待たない。
	 *
	 * 子プロセスは vtigercron.php を --child --service=<名前> で再実行する形で起動する。
	 * 実行権は呼び出し元（dispatch）が claim() で獲得済みである前提。
	 *
	 * @param Vtiger_Cron $cronTask
	 * @return boolean 起動コマンドを発行できたか
	 */
	public function spawn($cronTask) {
		$php = escapeshellarg(self::getPhpBinary());
		$script = escapeshellarg(self::getRootDirectory() . DIRECTORY_SEPARATOR . 'vtigercron.php');
		$service = escapeshellarg('--service=' . $cronTask->getName());
		$logFile = escapeshellarg(self::getLogFile($cronTask));

		// "--" 以降が vtigercron.php への引数として $argv に渡る
		$arguments = sprintf('%s -f %s -- --child %s', $php, $script, $service);

		if (self::isWindows()) {
			// start /B で新しいプロセスを起動し、親は待たずに戻る
			$command = sprintf('start /B "" %s >> %s 2>&1', $arguments, $logFile);
			$handle = @popen($command, 'r');
			if ($handle === false) {
				return false;
			}
			pclose($handle);
			return true;
		}

		// 末尾の "&" でシェルにバックグラウンド実行させ、exec() は即座に戻る。
		// 出力をファイルへ向けないと exec() が出力待ちでブロックするため必ずリダイレクトする。
		$command = sprintf('%s >> %s 2>&1 &', $arguments, $logFile);
		$output = array();
		$status = 0;
		@exec($command, $output, $status);
		return intval($status) === 0;
	}

	/**
	 * 実行対象のタスクを子プロセスへ振り分ける。親プロセスは完了を待たずに戻る。
	 *
	 * @param array $cronTasks Vtiger_Cron の配列
	 * @return array 振り分け結果。キーは locked / dispatched / skipped / failed
	 */
	public function dispatch($cronTasks) {
		$summary = array(
			'locked' => false,
			'dispatched' => array(),
			'skipped' => array(),
			'failed' => array(),
			'dead' => array(),
			'hung' => array(),
			'killed' => array(),
			'remote' => array(),
			'stale' => array(),
		);

		if (!$this->acquireLock()) {
			// 別のサーバー（または同じサーバーの別プロセス）が振り分け中。
			// 二重に振り分けて同時実行数の上限を超えないよう、ここで終了する。
			$summary['locked'] = true;
			return $summary;
		}

		try {
			// 実行中のまま終わらないタスクを先に始末して、空けられるスロットを回収する
			$findings = self::reap($cronTasks);
			$summary['dead'] = $findings['dead'];
			$summary['hung'] = $findings['hung'];
			$summary['killed'] = $findings['killed'];
			$summary['remote'] = $findings['remote'];
			$summary['stale'] = $findings['stale'];

			// 終了させられなかったハング中のタスクは、この回では起動しない。
			// 生きているプロセスを残したまま再実行すると同じタスクが二重に走ってしまう。
			$hungNames = array_diff(array_keys($findings['hung']), array_keys($findings['killed']));

			$serialNames = self::getSerialTaskNames();
			$freeSlots = self::getMaxParallel() - self::countRunning();
			$serialBusy = (count($serialNames) > 0) ? (self::countRunning($serialNames) > 0) : false;

			foreach (self::sortByUrgency($cronTasks) as $cronTask) {
				$name = $cronTask->getName();

				if (in_array($name, $hungNames, true)) {
					$summary['skipped'][$name] = 'possibly hung - left untouched';
					continue;
				}
				// 実行中のタスクは countRunning() で既にスロットを 1 つ数えている。
				// 先に判定しないと「スロットの空きが無い」と報告されて紛らわしい。
				//
				// ただしハートビートが途絶えたものはここで止めない。担当サーバーが落ちた
				// 可能性があり、この先の claim() が実行権を取り直して引き継ぐ。
				if ($cronTask->isRunning() && !self::hasStaleHeartbeat($cronTask)) {
					$summary['skipped'][$name] = self::isOwnedByThisHost($cronTask)
							? 'already running'
							: 'running on ' . $cronTask->getOwnerHost();
					continue;
				}
				if (!$cronTask->isRunnable()) {
					$summary['skipped'][$name] = 'not due';
					continue;
				}
				if ($freeSlots <= 0) {
					// 上限に達している。次回の cron 起動で改めて振り分けられる。
					$summary['skipped'][$name] = 'no free slot';
					continue;
				}

				$isSerial = in_array($name, $serialNames, true);
				if ($isSerial && $serialBusy) {
					$summary['skipped'][$name] = 'serial task already running';
					continue;
				}

				if (!self::claim($cronTask)) {
					// 他のディスパッチャに先を越された（実行権は 1 クエリで排他的に取る）
					$summary['skipped'][$name] = 'claimed by another process';
					continue;
				}

				if (!$this->spawn($cronTask)) {
					// 起動に失敗したタスクを実行中のまま残さない
					$cronTask->markFinished();
					$summary['failed'][] = $name;
					continue;
				}

				$freeSlots--;
				if ($isSerial) {
					$serialBusy = true;
				}
				$summary['dispatched'][] = $name;
			}
		} finally {
			$this->releaseLock();
		}

		return $summary;
	}

	/**
	 * 各タスクの現在の状態を、運用者が確認できる形にまとめる（--status 用）。
	 *
	 * 実行中タスクについては PID の生死まで確認し、以下のいずれかに分類する。
	 *   RUNNING : 正常に実行中
	 *   HUNG    : プロセスは生きているが retry_timeout を超過している（ハングの疑い）
	 *   DEAD    : プロセスが存在しない（異常終了して実行状態が残っている）
	 *   UNKNOWN : PID を確認できず生死を判定できない
	 *
	 * @param array $cronTasks Vtiger_Cron の配列
	 * @return array 表示用の連想配列の配列
	 */
	public static function describe($cronTasks) {
		$now = Vtiger_Cron::currentTime();
		$rows = array();

		foreach ($cronTasks as $cronTask) {
			$timeout = self::getEffectiveRetryTimeout($cronTask);

			$row = array(
				'name' => $cronTask->getName(),
				'state' => 'IDLE',
				'laststart' => $cronTask->getLastStart(),
				'nextrunat' => $cronTask->getNextRunAt(),
				'elapsed' => null,
				'host' => $cronTask->getOwnerHost(),
				'pid' => null,
				'timeout' => $timeout,
				'frequency' => $cronTask->getFrequency(),
			);

			if ($cronTask->isDisabled()) {
				$row['state'] = 'DISABLED';
				$rows[] = $row;
				continue;
			}
			if (!$cronTask->isRunning()) {
				$rows[] = $row;
				continue;
			}

			$row['elapsed'] = $now - $cronTask->getLastStart();

			if (!self::isOwnedByThisHost($cronTask)) {
				// 他のサーバーが担当している。プロセスは確認できないのでハートビートで判断する。
				$row['state'] = self::hasStaleHeartbeat($cronTask) ? 'STALE' : 'REMOTE';
				$rows[] = $row;
				continue;
			}

			$pid = $cronTask->getOwnerPid();
			if ($pid <= 0) {
				// 実行権を取った直後で、子プロセスがまだ PID を記録していない
				$row['state'] = 'STARTING';
				$rows[] = $row;
				continue;
			}

			$row['pid'] = $pid;
			$alive = self::isTaskProcessRunning($pid, $cronTask->getName());
			if ($alive === null) {
				$row['state'] = 'UNKNOWN';
			} else if ($alive === false) {
				$row['state'] = 'DEAD';
			} else {
				$row['state'] = ($row['elapsed'] > $timeout) ? 'HUNG' : 'RUNNING';
			}
			$rows[] = $row;
		}

		return $rows;
	}
}
