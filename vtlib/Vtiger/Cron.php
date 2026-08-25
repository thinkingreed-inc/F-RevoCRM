<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************** */

include_once 'vtlib/Vtiger/Utils.php';
require_once('include/database/PearDatabase.php');

/**
 * Provides API to work with Cron tasks
 * @package vtlib
 */
class Vtiger_Cron {

	protected static $schemaInitialized = false;
	protected static $instanceCache = array();
	protected static $timeOffset = null;
	static $STATUS_DISABLED = 0;
	static $STATUS_ENABLED = 1;
	static $STATUS_RUNNING = 2;
	static $STATUS_COMPLETED = 3;
	protected $data;
	protected $bulkMode = false;

	/**
	 * Constructor
	 */
	protected function __construct($values) {
		$this->data = $values;
		self::$instanceCache[$this->getName()] = $this;
	}

	/**
	 * set the value to the data
	 * @param type $value,$key
	 */
	protected function set($key,$value){
		$this->data[$key] = $value;
		return $this;
	}

	/**
	 * Get id reference of this instance.
	 */
	function getId() {
		return $this->data['id'];
	}

	/**
	 * Get name of this task instance.
	 */
	function getName() {
		return decode_html($this->data['name']);
	}

	/**
	 * Get the frequency set.
	 */
	function getFrequency() {
		return intval($this->data['frequency']);
	}

	/**
	 * Get the status
	 */
	function getStatus() {
		return intval($this->data['status']);
	}

	/**
	 * 現在時刻（UNIX時間）。データベースの時計を基準にする。
	 *
	 * アプリケーションサーバーが複数台ある構成では、各サーバーの時計がずれていると
	 * 「別のサーバーが開始したばかりのタスク」をタイムアウトしたものと誤判定して
	 * 奪い合い、同じタスクが二重に実行されてしまう。laststart の書き込みと経過時間の
	 * 判定を、共有されているデータベースの時計に揃えることでこれを防ぐ。
	 *
	 * 問い合わせは 1 プロセスにつき 1 回だけ行い、以降はその差分を足して返す。
	 * 取得できない場合は PHP 側の時刻をそのまま使う。
	 *
	 * @return integer
	 */
	static function currentTime() {
		if (self::$timeOffset === null) {
			global $adb;
			self::$timeOffset = 0;
			$result = self::querySilent('SELECT UNIX_TIMESTAMP() AS db_time');
			if ($result && $adb->num_rows($result)) {
				$dbTime = intval($adb->query_result($result, 0, 'db_time'));
				if ($dbTime > 0) {
					self::$timeOffset = $dbTime - time();
				}
			}
		}
		return time() + self::$timeOffset;
	}

	/**
	 * 次に実行する予定の時刻（UNIX時間）。0 の場合は未設定。
	 */
	function getNextRunAt() {
		return isset($this->data['next_run_at']) ? intval($this->data['next_run_at']) : 0;
	}

	/**
	 * 実行中のまま終わらないタスクを再実行可能とみなすまでの秒数。
	 */
	function getRetryTimeout() {
		return isset($this->data['retry_timeout']) ? intval($this->data['retry_timeout']) : 0;
	}

	/**
	 * このタスクの実行権を持つサーバーのホスト名。
	 */
	function getOwnerHost() {
		return isset($this->data['owner_host']) ? (string) $this->data['owner_host'] : '';
	}

	/**
	 * 実行中の子プロセスの PID（担当サーバー上での値）。
	 */
	function getOwnerPid() {
		return isset($this->data['owner_pid']) ? intval($this->data['owner_pid']) : 0;
	}

	/**
	 * 担当サーバーが最後に「子プロセスは生きている」と記録した時刻。
	 */
	function getLastHeartbeat() {
		return isset($this->data['last_heartbeat']) ? intval($this->data['last_heartbeat']) : 0;
	}

	/**
	 * 次回実行予定時刻を表示用の書式で返す。
	 */
	function getNextRunAtDateTime() {
		$nextRunAt = $this->getNextRunAt();
		if ($nextRunAt <= 0) {
			return '';
		}
		$nextRunAtDateTime = new DateTimeField(date('Y-m-d H:i:s', $nextRunAt));
		return $nextRunAtDateTime->getDisplayDateTimeValue();
	}

	/**
	 * 実行周期に沿った「次の実行予定時刻」を求める。
	 *
	 * 従来は「前回開始時刻 + 周期」という相対的な判定をしていたため、直前のタスクを待って
	 * 開始が遅れるとその遅れが laststart に積み上がり、15 分毎の設定でも実行時刻が少しずつ
	 * ずれていった。ここでは実行時刻を固定のグリッド上に吸着させることで、遅れが次回以降へ
	 * 持ち越されないようにする。
	 *
	 * グリッドの基準は「その日の 0 時」。1 日を割り切れる周期であれば、15 分毎なら
	 * 0:00 / 0:15 / 0:30 ... 、12 時間毎なら 0:00 / 12:00 に揃う。割り切れない周期や
	 * 1 日を超える周期はグリッドを作れないため、基準時刻からの相対で決める。
	 *
	 * 遅延して実行できなかった回は取り戻さず、次のグリッドまで待つ。
	 *
	 * @param integer $frequency 実行周期（秒）
	 * @param integer $reference 基準時刻（UNIX時間）。省略時は現在時刻。
	 * @return integer 次の実行予定時刻（UNIX時間）
	 */
	static function computeNextRunAt($frequency, $reference = null) {
		$frequency = intval($frequency);
		if ($frequency <= 0) {
			$frequency = 900;
		}
		if ($reference === null) {
			$reference = self::currentTime();
		}

		if ($frequency > 86400 || (86400 % $frequency) !== 0) {
			return $reference + $frequency;
		}

		$dayStart = mktime(0, 0, 0, date('n', $reference), date('j', $reference), date('Y', $reference));
		$slots = (int) floor(($reference - $dayStart) / $frequency) + 1;
		return $dayStart + ($slots * $frequency);
	}
	/**
	 * Get the timestamp lastrun started.
	 */
	function getLastStart() {
		return intval($this->data['laststart']);
	}

	/**
	 * Get the timestamp lastrun ended.
	 */
	function getLastEnd() {
		return intval($this->data['lastend']);
	}

	/**
	 * Get the user datetimefeild
	 */
	function getLastEndDateTime() {
		if($this->data['lastend'] != NULL){
			$lastEndDateTime = new DateTimeField(date('Y-m-d H:i:s', $this->data['lastend']));
			return $lastEndDateTime->getDisplayDateTimeValue();
		} else {
			return '';
		}
	}

	/**
	 *
	 * get the last start datetime field
	 */
	function getLastStartDateTime() {
		if($this->data['laststart'] != NULL){
			$lastStartDateTime = new DateTimeField(date('Y-m-d H:i:s', $this->data['laststart']));
			return $lastStartDateTime->getDisplayDateTimeValue();
		} else {
			return '';
		}
	}

	/**
	 * Get Time taken to complete task
	 */
	function getTimeDiff() {
		$lastStart = $this->getLastStart();
		$lastEnd   = $this->getLastEnd();
		$timeDiff  = $lastEnd - $lastStart;
		return $timeDiff;
	}

	/**
	 * Get the configured handler file.
	 */
	function getHandlerFile() {
		return $this->data['handler_file'];
	}

	/**
	 *Get the Module name
	 */
	function getModule() {

		return $this->data['module'];
	}

	/**
	 * get the Sequence
	 */
	function getSequence() {
		return $this->data['sequence'];
	}

	/**
	 * get the description of cron
	 */
	function getDescription(){
		return $this->data['description'];
	}

	/**
	 * Check if task is right state for running.
	 *
	 * 判定は next_run_at（実行周期のグリッド上に固定された次回実行予定時刻）との比較で行う。
	 * 前回の実行時刻を基準にしないため、実行が遅れてもその遅れが次回以降へ持ち越されない。
	 *
	 * next_run_at が未設定の場合（マイグレーション直後や、独自に登録されて一度も完了して
	 * いないタスク）は、従来通り前回実行時刻からの相対経過で判定する。
	 */
	function isRunnable() {
		if ($this->isDisabled()) {
			return false;
		}

		$nextRunAt = $this->getNextRunAt();
		if ($nextRunAt > 0) {
			return self::currentTime() >= $nextRunAt;
		}

		// Take care of last time (end - on success, start - if timedout)
		// Take care to start the cron im
		$lastTime = ($this->getLastStart() > 0) ? $this->getLastStart() : $this->getLastEnd();
		$elapsedTime = self::currentTime() - $lastTime;
		return ($elapsedTime >= ($this->getFrequency()-60));
	}

	/**
	 * Helper function to check the status value.
	 */
	function statusEqual($value) {
		$status = intval($this->data['status']);
		return $status == $value;
	}

	/**
	 * Is task in running status?
	 */
	function isRunning() {
		return $this->statusEqual(self::$STATUS_RUNNING);
	}

	/**
	 * Is task enabled?
	 */
	function isEnabled() {
		return $this->statusEqual(self::$STATUS_ENABLED);
	}

	/**
	 * Is task disabled?
	 */
	function isDisabled() {
		return $this->statusEqual(self::$STATUS_DISABLED);
	}

	/**
	 * Update status
	 */
	function updateStatus($status) {
		switch (intval($status)) {
			case self::$STATUS_DISABLED:
			case self::$STATUS_ENABLED:
			case self::$STATUS_RUNNING:
				break;
			default:
				throw new Exception('Invalid status');
		}
		self::querySilent('UPDATE vtiger_cron_task SET status=? WHERE id=?', array($status, $this->getId()));
	}

	/*
	 * update frequency
	*/
	function updateFrequency($frequency) {
		// 周期を変えたら次回実行予定時刻も新しいグリッドに合わせて引き直す
		$nextRunAt = self::computeNextRunAt($frequency);
		self::querySilent('UPDATE vtiger_cron_task SET frequency=?, next_run_at=? WHERE id=?',
				array($frequency, $nextRunAt, $this->getId()));
		$this->data['frequency'] = $frequency;
		$this->data['next_run_at'] = $nextRunAt;
	}

	/**
	 * Mark this instance as running.
	 */
	function markRunning() {
		$time = self::currentTime();
		self::querySilent('UPDATE vtiger_cron_task SET status=?, laststart=?, lastend=? WHERE id=?', array(self::$STATUS_RUNNING, $time, 0, $this->getId()));
		$this->data["status"] = self::$STATUS_RUNNING;
		return $this->set('laststart',$time);
	}

	/**
	 * 実行中の子プロセスがまだ生きていることを記録する。
	 *
	 * アプリケーションサーバーが複数台ある構成で、担当サーバーが「自分はまだ動いている」と
	 * 示すために使う。これが途絶えることが、担当サーバーが落ちたことの手掛かりになる。
	 */
	function markHeartbeat() {
		$time = self::currentTime();
		self::querySilent('UPDATE vtiger_cron_task SET last_heartbeat=? WHERE id=?',
				array($time, $this->getId()));
		return $this->set('last_heartbeat', $time);
	}

	/**
	 * Mark this instance as finished.
	 *
	 * 完了時点を基準に次回実行予定時刻を引き直す。実行に周期以上の時間が掛かった場合は
	 * その間のグリッドを飛ばし、完了後の次のグリッドから再開する（遅れを取り戻さない）。
	 */
	function markFinished() {
		$time = self::currentTime();
		$nextRunAt = self::computeNextRunAt($this->getFrequency(), $time);
		// owner_pid と last_heartbeat は実行中であることを示す情報なのでここで消す。
		// owner_host は「最後にどのサーバーが実行したか」として残す。
		self::querySilent('UPDATE vtiger_cron_task SET status=?, lastend=?, next_run_at=?, owner_pid=0, last_heartbeat=0 WHERE id=?',
				array(self::$STATUS_ENABLED, $time, $nextRunAt, $this->getId()));
		$this->data["status"] = self::$STATUS_ENABLED;
		$this->data['next_run_at'] = $nextRunAt;
		$this->data['owner_pid'] = 0;
		$this->data['last_heartbeat'] = 0;
		return $this->set('lastend',$time);
	}

	/**
	 * Set the bulkMode flag
	 */
	function setBulkMode($mode = null) {
		$this->bulkMode = $mode;
	}

	/**
	 * Is task in bulk mode execution?
	 */
	function inBulkMode() {
		return $this->bulkMode;
	}

	/**
	 * Detect if the task was started by never finished.
	 */
	function hadTimedout() {
		$laststart = intval($this->data['laststart']);
		$lastend = intval($this->data['lastend']);
		$retryTimeout = intval($this->data['retry_timeout']);
		$currentTime = self::currentTime();
		if($laststart > 0 && $lastend === 0 && $currentTime - $laststart > $retryTimeout) {
			return true;
		}
		return false;
	}

	/**
	 * Execute SQL query silently (even when table doesn't exist)
	 */
	protected static function querySilent($sql, $params=false) {
		global $adb;
		$old_dieOnError = $adb->dieOnError;

		$adb->dieOnError = false;
		$result = $adb->pquery($sql, $params);
		$adb->dieOnError = $old_dieOnError;
		return $result;
	}

	/**
	 * Initialize the schema.
	 */
	protected static function initializeSchema() {
		if(!self::$schemaInitialized) {
			if(!Vtiger_Utils::CheckTable('vtiger_cron_task')) {
				Vtiger_Utils::CreateTable('vtiger_cron_task',
						'(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
					name VARCHAR(100) UNIQUE KEY, handler_file VARCHAR(100) UNIQUE KEY,
					frequency int, laststart int(11) unsigned, lastend int(11) unsigned, status int,module VARCHAR(100),
										sequence int,description TEXT,
										retry_timeout int DEFAULT 0, next_run_at int(11) unsigned DEFAULT 0,
										owner_host VARCHAR(255) DEFAULT NULL, owner_pid int(11) unsigned DEFAULT 0,
										last_heartbeat int(11) unsigned DEFAULT 0 )',true);
			}
			self::$schemaInitialized = true;
		}
	}

	static function nextSequence() {
		global $adb;
		$result = self::querySilent('SELECT MAX(sequence) FROM vtiger_cron_task ORDER BY SEQUENCE');
		if ($result && $adb->num_rows($result)) {
			$row = $adb->fetch_array($result);
		}
		if($row == NULL) {
			$row['max(sequence)'] = 1;
		}
		return $row['max(sequence)']+1;
	}

	/**
	 * Register cron task.
	 */
	static function register($name, $handler_file, $frequency, $module = 'Home', $status = 1, $sequence = 0, $description = '') {
		self::initializeSchema();
		global $adb;
		$instance = self::getInstance($name);
		if($sequence == 0) {
			$sequence = self::nextSequence();
		}
		self::querySilent('INSERT INTO vtiger_cron_task (name, handler_file, frequency, status, sequence,module,description,next_run_at) VALUES(?,?,?,?,?,?,?,?)',
				array($name, $handler_file, $frequency, $status, $sequence, $module,$description,
					self::computeNextRunAt($frequency)));
	}

	/**
	 * De-register cron task.
	 */
	static function deregister($name) {
		self::querySilent('DELETE FROM vtiger_cron_task WHERE name=?', array($name));
		if (isset(self::$instanceCache["$name"])) {
			unset(self::$instanceCache["$name"]);
		}
	}

	/**
	 * Get instances that are active (not disabled)
	 */
	static function listAllActiveInstances($byStatus = 0) {
		global $adb;

		$instances = array();
		if($byStatus == 0) {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE status <> ? ORDER BY SEQUENCE',array(self::$STATUS_DISABLED   ));
		}
		else {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task  ORDER BY SEQUENCE');

		}
		if ($result && $adb->num_rows($result)) {
			while ($row = $adb->fetch_array($result)) {
				$instances[] = new Vtiger_Cron($row);
			}
		}
		return $instances;
	}

	/**
	 * Get instance of cron task.
	 */
	static function getInstance($name) {
		global $adb;

		$instance = false;
		if (isset(self::$instanceCache["$name"])) {
			$instance = self::$instanceCache["$name"];
		}

		if ($instance === false) {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE name=?', array($name));
			if ($result && $adb->num_rows($result)) {
				$instance = new Vtiger_Cron($adb->fetch_array($result));
			}
		}
		return $instance;
	}


	/**
	 * Get instance of cron job by id
	 */
	static function getInstanceById($id) {
		global $adb;
		$instance = false;
		if (isset(self::$instanceCache[$id])) {
			$instance = self::$instanceCache[$id];
		}


		if ($instance === false) {
			$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE id=?', array($id));
			if ($result && $adb->num_rows($result)) {
				$instance = new Vtiger_Cron($adb->fetch_array($result));
			}
		}
		return $instance;
	}

	static function listAllInstancesByModule($module) {
		global $adb;

		$instances = array();
		$result = self::querySilent('SELECT * FROM vtiger_cron_task WHERE module=?',array($module));
		if ($result && $adb->num_rows($result)) {
			while ($row = $adb->fetch_array($result)) {
				$instances[] = new Vtiger_Cron($row);
			}
		}
		return $instances;
	}

	/*
	 * Fuction uses to log the cron when it is in running
	 *  for long time
	 *  @Params <boolean> Completed - flag when then the cron is completed after long time
	 */
	public function log($completed = false){
		global $adb;
		 $result = self::querySilent('SELECT id,iteration from vtiger_cron_log where start = ? AND name=?',array($this->getLastStart(),$this->getName()));
		  if ($result && $adb->num_rows($result) > 0) {
			  $row = $adb->fetch_array($result);
			  if($completed){
				  self::querySilent('UPDATE vtiger_cron_log set status = ?,end = ? where id = ?',array(self::$STATUS_COMPLETED,time(),$row['id']));
			  } else{

				 self::querySilent('UPDATE vtiger_cron_log set iteration = ? where id = ?',array($row['iteration']+1,$row['id']));
			  }
		  } else {
			  self::querySilent('INSERT INTO vtiger_cron_log (name,start,iteration,status) VALUES(?,?,?,?)',
								 array($this->getName(),$this->getLastStart(),1,self::$STATUS_RUNNING));
		  }

	 }

	 /*
	  *  Function to verify where the log Mail is sent are not
	  */
	 public function isSentLogMail(){
		 global $adb;
		 $result = self::querySilent('SELECT 1 from vtiger_cron_log where start = ? AND name=? AND iteration >= 4 ',array($this->getLastStart(),$this->getName()));
		 if ($result && $adb->num_rows($result)) {
			 return true;
		 }	else {
			return false;
		 }
	 }

	 /*
	  *  Function to get number of times a Cron task was skipped due to running state
	  *		@returns <int> Iterations
	  */
	 public function getIterations(){
		 global $adb;
		 $result = self::querySilent('SELECT iteration from vtiger_cron_log where start = ? AND name=?',array($this->getLastStart(),$this->getName()));
		 if ($result && $adb->num_rows($result)) {
			 $row = $adb->fetch_array($result);
			 return $row['iteration'];
		 }
	 }

	 /*
	  *  Function to get time to Complete the cron when it take
	  *		@returns <string> competed time in hours and mins
	  */
	 public function getCompletedTime(){
		 global $adb;
		 $result = self::querySilent('SELECT start,end from vtiger_cron_log where start = ? AND name=?',array($this->getLastStart(),$this->getName()));
		 if ($result && $adb->num_rows($result)) {
			$row = $adb->fetch_array($result);
			$duration = $row['end'] - $row['start'];
			$hours = (int) ($duration / 60);
			$minutes = $duration - ($hours * 60);

			return "$hours hours and $minutes minutes";
		}
	 }
}
?>