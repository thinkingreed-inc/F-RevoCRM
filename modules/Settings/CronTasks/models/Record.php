<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

include_once 'vtlib/Vtiger/Cron.php';
include_once 'include/utils/CronDispatcher.php';

class Settings_CronTasks_Record_Model extends Settings_Vtiger_Record_Model {

	static $STATUS_DISABLED = 0;
    static $STATUS_ENABLED = 1;
    static $STATUS_RUNNING = 2;
	static $STATUS_COMPLETED = 3;

	/**
	 * 画面から指定できる周期の下限（秒）。
	 * スケジューラー（cron）自体の一般的な起動間隔である 1 分より短くしても実行されない。
	 */
	const MINIMUM_FREQUENCY_SECONDS = 60;

	/**
	 * 実行状態（--status で表示するもの）に対応する表示ラベルと強調の度合い。
	 *
	 * status 列は実行中のタスクをすべて「実行中」としか表せない。異常終了して実行状態が
	 * 残っているもの（DEAD）やハングの疑いがあるもの（HUNG）も同じ 2 になるため、画面から
	 * 異常に気付けなかった。vtigercron.php --status と同じ判定結果をここで表示に反映する。
	 *
	 *   キー => array(言語ラベル, Bootstrap のラベル種別)
	 */
	static $RUNTIME_STATE_LABELS = array(
		'RUNNING'  => array('LBL_RUNNING', 'info'),
		'STARTING' => array('LBL_STARTING', 'info'),
		'REMOTE'   => array('LBL_RUNNING_ON_OTHER_HOST', 'info'),
		'NOSTART'  => array('LBL_NOT_STARTED', 'danger'),
		'DEAD'     => array('LBL_TASK_DEAD', 'danger'),
		'HUNG'     => array('LBL_TASK_HUNG', 'warning'),
		'STALE'    => array('LBL_TASK_STALE', 'warning'),
		'UNKNOWN'  => array('LBL_STATE_UNKNOWN', 'default'),
	);

	/**
	 * Function to get Id of this record instance
	 * @return <Integer> id
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * Function to get Name of this record
	 * @return <String>
	 */
	public function getName() {
		return $this->get('name');
	}

	/**
	 * Function to get module instance of this record
	 * @return <type>
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * Function to set module to this record instance
	 * @param <Settings_CronTasks_Module_Model> $moduleModel
	 * @return <Settings_CronTasks_Record_Model> record model
	 */
	public function setModule($moduleModel) {
		$this->module = $moduleModel;
		return $this;
	}

    public function isDisabled() {
        if($this->get('status') == self::$STATUS_DISABLED){
            return true;
        }
        return false;
    }
    
    public function isRunning() {
        if($this->get('status') == self::$STATUS_RUNNING){
            return true;
        }
        return false;
    }
    
    public function isCompleted() {
        if($this->get('status') == self::$STATUS_COMPLETED){
            return true;
        }
        return false;
    }
    
    public function isEnabled() {
        if($this->get('status') == self::$STATUS_ENABLED){
            return true;
        }
        return false;
    }
    
    /**
     * Detect if the task was started by never finished.
     */
    function hadTimedout() {
		$laststart = intval($this->get('laststart'));
		$lastend = intval($this->get('lastend'));
		$retryTimeout = intval($this->get('retry_timeout'));
		$currentTime = time();
		if($laststart > 0 && $lastend === 0 && $currentTime - $laststart > $retryTimeout) {
			return true;
		}
		return false;
	}
    
    /**
     * Get the user datetimefeild
     */
    function getLastEndDateTime() {
        if($this->get('lastend') != NULL) {
		    $lastScannedTime = Vtiger_Datetime_UIType::getDisplayDateTimeValue(date('Y-m-d H:i:s', $this->get('lastend')));
		    $userModel = Users_Record_Model::getCurrentUserModel();
			$hourFormat = $userModel->get('hour_format');
		    if($hourFormat == '24') {
				return $lastScannedTime;
		    } else {
				$dateTimeList = explode(" ", $lastScannedTime);
                return $dateTimeList[0]." ".date('g:i:sa', strtotime($dateTimeList[1]));
			}
		} else {
			return '';
		}
    }
    
    /**
     * Get Time taken to complete task
     */
    function getTimeDiff() {
        $lastStart = intval($this->get('laststart'));
        $lastEnd   = intval($this->get('lastend'));
        $timeDiff  = $lastEnd - $lastStart;
        return $timeDiff;
    }

	/**
	 * Function to get display value of every field from this record
	 * @param <String> $fieldName
	 * @return <String>
	 */
	public function getDisplayValue($fieldName) {
		$fieldValue = $this->get($fieldName);
		switch ($fieldName) {
			case 'frequency'	: $fieldValue = $this->getScheduleDisplayValue();
								  break;
			case 'status'		: $fieldValue = $this->getStatusDisplayValue();
								  break;
			// 最終開始日時・最終終了日時・次回実行予定は、いずれも列見出しが「日時」なので
			// 日時そのものを表示する。以前は経過時間（「1 時間」など）を出しており、
			// 見出しと合わず、いつ実行されたのかも読み取れなかった。
			case 'laststart'	:
			case 'lastend'		:
			case 'next_run_at'	: $fieldValue = $this->formatTimestamp($fieldValue);
								  break;
			case 'retry_timeout': $fieldValue = $this->getRetryTimeoutDisplayValue();
								  break;
			case 'log_retention_count':
								  $fieldValue = $this->getLogRetentionDisplayValue();
								  break;
		}
		return $fieldValue;
	}

	/**
	 * UNIX時間を、ログイン中のユーザーの書式で日時として表示する。
	 * 未設定（0 以下）の場合は空文字。
	 *
	 * @param <Integer> $timestamp
	 * @return <String>
	 */
	protected function formatTimestamp($timestamp) {
		$timestamp = intval($timestamp);
		if ($timestamp <= 0) {
			return '';
		}
		$dateTime = new DateTimeField(date('Y-m-d H:i:s', $timestamp));
		return $dateTime->getDisplayDateTimeValue();
	}

	/**
	 * 周期列の表示。
	 * 毎日決まった時刻に実行する指定なら、周期ではなくその時刻を示す。
	 *
	 * @return <String>
	 */
	public function getScheduleDisplayValue() {
		$time = $this->getRunAtTimeDisplayValue();

		switch ($this->getScheduleType()) {
			case Vtiger_Cron::SCHEDULE_DAILY:
				return $this->translate('LBL_DAILY_AT') . ' ' . $time;
			case Vtiger_Cron::SCHEDULE_WEEKLY:
				return $this->translate('LBL_WEEKLY_AT') . ' '
						. $this->getRunOnWeekdaysDisplayValue() . ' ' . $time;
			case Vtiger_Cron::SCHEDULE_MONTHLY:
				return $this->translate('LBL_MONTHLY_AT') . ' '
						. $this->getRunOnDayDisplayValue() . ' ' . $time;
		}
		return $this->getFrequencyDisplayValue();
	}

	/**
	 * 実行周期の HH:MM 表記。毎日指定時刻の場合も周期そのものの値を返す。
	 * @return <String>
	 */
	public function getFrequencyDisplayValue() {
		$frequency = intval($this->get('frequency'));
		return str_pad((int) ($frequency / (60 * 60)), 2, 0, STR_PAD_LEFT) . ':'
				. str_pad((int) (($frequency % (60 * 60)) / 60), 2, 0, STR_PAD_LEFT);
	}

	/**
	 * ステータス列の表示。
	 *
	 * 実行中のタスクは vtigercron.php --status と同じ判定（プロセスの生死・担当サーバーの
	 * ハートビート）まで行い、異常な状態を色付きで区別できるようにする。
	 *
	 * @return <String> Bootstrap のラベル要素
	 */
	public function getStatusDisplayValue() {
		$status = intval($this->get('status'));

		if ($status === self::$STATUS_RUNNING) {
			$state = $this->getRuntimeState();
			if (isset(self::$RUNTIME_STATE_LABELS[$state])) {
				list($label, $level) = self::$RUNTIME_STATE_LABELS[$state];
				$text = $this->translate($label);
				// 他サーバーが担当している場合はどのサーバーかを添える
				if ($state === 'REMOTE' || $state === 'STALE') {
					$host = $this->getSafeOwnerHost();
					if ($host !== '') {
						$text .= ' (' . $host . ')';
					}
				}
				return $this->renderStateLabel($text, $level);
			}
			return $this->renderStateLabel($this->translate('LBL_RUNNING'), 'info');
		}
		if ($status === self::$STATUS_COMPLETED) {
			return $this->renderStateLabel($this->translate('LBL_COMPLETED'), 'success');
		}
		if ($status === self::$STATUS_ENABLED) {
			return $this->renderStateLabel($this->translate('LBL_ACTIVE'), 'success');
		}
		return $this->renderStateLabel($this->translate('LBL_INACTIVE'), 'default');
	}

	/**
	 * 実行中タスクの詳細な状態。vtigercron.php --status と同じ判定を用いる。
	 *
	 * @return <String> RUNNING / STARTING / NOSTART / HUNG / DEAD / REMOTE / STALE / UNKNOWN
	 *                  判定できない場合は空文字
	 */
	public function getRuntimeState() {
		$cronTask = Vtiger_Cron::getInstance($this->getName());
		if ($cronTask === false) {
			return '';
		}
		$rows = FR_CronDispatcher::describe(array($cronTask));
		return isset($rows[0]['state']) ? $rows[0]['state'] : '';
	}

	/**
	 * タイムアウト列の表示。
	 * 未設定（0）のタスクには config.inc.php の既定値が適用されるため、その値を添えて示す。
	 *
	 * @return <String>
	 */
	public function getRetryTimeoutDisplayValue() {
		$configured = intval($this->get('retry_timeout'));
		$effective = ($configured > 0) ? $configured : FR_CronDispatcher::getDefaultRetryTimeout();

		$hours = str_pad((int) ($effective / (60 * 60)), 2, 0, STR_PAD_LEFT);
		$minutes = str_pad((int) (($effective % (60 * 60)) / 60), 2, 0, STR_PAD_LEFT);
		$display = $hours . ':' . $minutes;

		if ($configured <= 0) {
			$display .= ' (' . $this->translate('LBL_DEFAULT_VALUE') . ')';
		}
		return $display;
	}

	/**
	 * 一覧のセルへ出す色付きラベル。
	 *
	 * 一覧テンプレートは表示値を vtranslate() に通す（内部で html_entity_decode される）ため、
	 * ここでは HTML 実体参照を作らない。埋め込む文字列は translate() の言語ファイル由来か、
	 * getSafeOwnerHost() で記号を除いたホスト名のみに限る。
	 *
	 * @param <String> $text
	 * @param <String> $level Bootstrap のラベル種別
	 * @return <String>
	 */
	protected function renderStateLabel($text, $level) {
		return '<span class="label label-' . $level . '">' . strip_tags($text) . '</span>';
	}

	/**
	 * 表示に使えるよう記号を除いたホスト名。
	 * @return <String>
	 */
	protected function getSafeOwnerHost() {
		return preg_replace('/[^A-Za-z0-9_\.\-]/', '', (string) $this->get('owner_host'));
	}

	/**
	 * このモジュールの言語ファイルから訳文を得る。
	 * @param <String> $label
	 * @return <String>
	 */
	protected function translate($label) {
		$moduleModel = $this->getModule();
		return vtranslate($label, $moduleModel->getParentName() . ':' . $moduleModel->getName());
	}
	
	/*
	 * Function to get Edit view url
	 */
	public function getEditViewUrl() {
		return 'module=CronTasks&parent=Settings&view=EditAjax&record='.$this->getId();
	}

	/**
	 * 実行ログを表示する画面の URL。
	 * @return <String>
	 */
	public function getLogViewUrl() {
		return 'module=CronTasks&parent=Settings&view=LogAjax&record='.$this->getId();
	}

	/**
	 * タイムアウトを指定しなかった場合に適用される秒数。
	 * @return <Integer>
	 */
	public function getDefaultRetryTimeout() {
		return FR_CronDispatcher::getDefaultRetryTimeout();
	}

	/**
	 * 実行タイミングの種別。列が無い場合は周期実行として扱う。
	 * @return <String> interval / daily / weekly / monthly
	 */
	public function getScheduleType() {
		$type = (string) $this->get('schedule_type');
		if (in_array($type, Vtiger_Cron::$SCHEDULE_TYPES, true)) {
			return $type;
		}
		return Vtiger_Cron::SCHEDULE_INTERVAL;
	}

	/**
	 * 決まった時刻に実行する指定（毎日・毎週・毎月）になっているか。
	 * @return <Boolean>
	 */
	public function isFixedTimeSchedule() {
		return $this->getScheduleType() !== Vtiger_Cron::SCHEDULE_INTERVAL;
	}

	/**
	 * 毎日決まった時刻に実行する指定になっているか。
	 * @return <Boolean>
	 */
	public function isDailySchedule() {
		return $this->getScheduleType() === Vtiger_Cron::SCHEDULE_DAILY;
	}

	/**
	 * 実行する時刻（0 時からの経過分）。指定が無い場合は null。
	 * @return <Integer>|null
	 */
	public function getRunAtMinutes() {
		$minutes = $this->get('run_at_minutes');
		if ($minutes === null || $minutes === '') {
			return null;
		}
		$minutes = intval($minutes);
		return ($minutes >= 0 && $minutes < 1440) ? $minutes : null;
	}

	/**
	 * 実行する曜日の一覧（0=日曜 〜 6=土曜）。指定が無い場合は空配列。
	 * @return <Array>
	 */
	public function getRunOnWeekdays() {
		return Vtiger_Cron::parseWeekdays($this->get('run_on_weekdays'));
	}

	/**
	 * 実行する日（1〜31）。0 は月末。指定が無い場合は null。
	 * @return <Integer>|null
	 */
	public function getRunOnDay() {
		$day = $this->get('run_on_day');
		if ($day === null || $day === '') {
			return null;
		}
		$day = intval($day);
		return ($day >= 0 && $day <= 31) ? $day : null;
	}

	/**
	 * 実行する時刻の HH:MM 表記。時刻の指定が無い場合は空文字。
	 * @return <String>
	 */
	public function getRunAtTimeDisplayValue() {
		$minutes = $this->getRunAtMinutes();
		if ($minutes === null) {
			return '';
		}
		return str_pad((int) ($minutes / 60), 2, 0, STR_PAD_LEFT) . ':'
				. str_pad($minutes % 60, 2, 0, STR_PAD_LEFT);
	}

	/**
	 * 曜日の表示名。複数指定されている場合は「・」で連ねる。
	 * @return <String>
	 */
	public function getRunOnWeekdaysDisplayValue() {
		$weekdays = $this->getRunOnWeekdays();
		if (count($weekdays) === 0) {
			return '';
		}
		$labels = array('LBL_SUNDAY', 'LBL_MONDAY', 'LBL_TUESDAY', 'LBL_WEDNESDAY',
				'LBL_THURSDAY', 'LBL_FRIDAY', 'LBL_SATURDAY');
		$names = array();
		foreach ($weekdays as $weekday) {
			$names[] = $this->translate($labels[$weekday]);
		}
		return implode($this->translate('LBL_WEEKDAY_SEPARATOR'), $names);
	}

	/**
	 * 実行する日の表示名。月末は言語ファイルの表記を使う。
	 * @return <String>
	 */
	public function getRunOnDayDisplayValue() {
		$day = $this->getRunOnDay();
		if ($day === null) {
			return '';
		}
		if ($day === Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH) {
			return $this->translate('LBL_LAST_DAY_OF_MONTH');
		}
		// 言語ごとに日の表記が違う（日本語は「15日」、英語は「day 15」）ため書式で持つ
		return str_replace('%s', strval($day), $this->translate('LBL_DAY_OF_MONTH_FORMAT'));
	}

	/**
	 * このタスクの実行ログを残す世代数。未設定なら null。
	 * @return <Integer>|null
	 */
	public function getLogRetentionCount() {
		$count = $this->get('log_retention_count');
		if ($count === null || $count === '') {
			return null;
		}
		return max(0, intval($count));
	}

	/**
	 * 実行ログを残す世代数の表示。
	 * 未設定なら config.inc.php の既定値を、0 なら「無期限」と示す。
	 *
	 * @return <String>
	 */
	public function getLogRetentionDisplayValue() {
		$count = $this->getLogRetentionCount();
		if ($count === null) {
			return FR_CronDispatcher::getLogRetentionCount() . ' ('
					. $this->translate('LBL_DEFAULT_VALUE') . ')';
		}
		if ($count === 0) {
			return $this->translate('LBL_LOG_RETENTION_UNLIMITED');
		}
		return strval($count);
	}

	/**
	 * 未設定のときに適用される世代数（画面の初期表示に使う）。
	 * @return <Integer>
	 */
	public function getDefaultLogRetentionCount() {
		return FR_CronDispatcher::getLogRetentionCount();
	}

	/**
	 * 最新のログファイルの内容（末尾のみ）。
	 *
	 * @param <Integer> $lines 取り出す行数
	 * @return <Array> file / content / size / modified / truncated をキーに持つ配列。
	 *                 ログが無い場合は file が null。
	 */
	public function getLatestLog($lines = 200) {
		$empty = array('file' => null, 'content' => '', 'size' => 0, 'modified' => 0, 'truncated' => false);

		$cronTask = Vtiger_Cron::getInstance($this->getName());
		if ($cronTask === false) {
			return $empty;
		}
		$file = FR_CronDispatcher::getLatestLogFile($cronTask);
		if ($file === null) {
			return $empty;
		}
		$tail = FR_CronDispatcher::tailLogFile($file, $lines);
		$tail['file'] = $file;
		return $tail;
	}

	/**
	 * Function to save the record
	 */
	public function save() {
		$db = PearDatabase::getInstance();

		// 指定内容を検証して正規化する。矛盾があれば周期実行（従来の動作）に落とす。
		$schedule = $this->normalizeScheduleInput();

		// 決まった時刻に実行する場合、周期そのものは使わない。ただし表示や
		// 移行前データのフォールバックが周期を参照するため、目安の値を入れておく。
		$frequency = $schedule['frequency'];

		// 設定を変更したら次回実行予定時刻も引き直す。
		// これをしないと、変更が次回の完了時まで反映されない。
		$nextRunAt = $schedule['next_run_at'];

		$assignments = array('frequency = ?', 'status = ?', 'next_run_at = ?',
				'schedule_type = ?', 'run_at_minutes = ?', 'run_on_weekdays = ?', 'run_on_day = ?');
		$params = array($frequency, $this->get('status'), $nextRunAt,
				$schedule['schedule_type'], $schedule['run_at_minutes'],
				$schedule['run_on_weekdays'], $schedule['run_on_day']);

		// タイムアウトは画面から送られてきた場合だけ更新する。
		// 空欄は「未設定（config.inc.php の既定値に従う）」として 0 を保存する。
		$retryTimeout = $this->get('retry_timeout');
		if ($retryTimeout !== null) {
			$assignments[] = 'retry_timeout = ?';
			$params[] = max(0, intval($retryTimeout));
		}

		// 実行ログの保持世代数。空欄は NULL（既定値に従う）として保存する。
		$logRetention = $this->get('log_retention_count');
		if ($logRetention !== null) {
			$assignments[] = 'log_retention_count = ?';
			$params[] = ($logRetention === '') ? null : max(0, intval($logRetention));
		}

		$params[] = $this->getId();
		$db->pquery('UPDATE vtiger_cron_task SET ' . implode(', ', $assignments) . ' WHERE id = ?', $params);
	}

	/**
	 * 画面から渡された実行タイミングの指定を検証して、保存する値に整える。
	 *
	 * 指定に必要な値が欠けている場合（毎週なのに曜日が無い等）は、実行されないタスクを
	 * 作らないよう周期実行として扱う。
	 *
	 * @return <Array> schedule_type / run_at_minutes / run_on_weekday / run_on_day
	 *                 / frequency / next_run_at をキーに持つ配列
	 */
	protected function normalizeScheduleInput() {
		$type = (string) $this->get('schedule_type');
		if (!in_array($type, Vtiger_Cron::$SCHEDULE_TYPES, true)) {
			$type = Vtiger_Cron::SCHEDULE_INTERVAL;
		}

		$runAtMinutes = $this->normalizeIntegerInput($this->get('run_at_minutes'), 0, 1439);
		$runOnWeekdays = Vtiger_Cron::parseWeekdays($this->get('run_on_weekdays'));
		$runOnDay = $this->normalizeIntegerInput($this->get('run_on_day'), 0, 31);

		// 時刻が無ければ時刻指定の実行はできない
		if ($type !== Vtiger_Cron::SCHEDULE_INTERVAL && $runAtMinutes === null) {
			$type = Vtiger_Cron::SCHEDULE_INTERVAL;
		}
		if ($type === Vtiger_Cron::SCHEDULE_WEEKLY && count($runOnWeekdays) === 0) {
			$type = Vtiger_Cron::SCHEDULE_INTERVAL;
		}
		if ($type === Vtiger_Cron::SCHEDULE_MONTHLY && $runOnDay === null) {
			$type = Vtiger_Cron::SCHEDULE_INTERVAL;
		}

		switch ($type) {
			case Vtiger_Cron::SCHEDULE_DAILY:
				return array(
					'schedule_type' => $type,
					'run_at_minutes' => $runAtMinutes,
					'run_on_weekdays' => null,
					'run_on_day' => null,
					'frequency' => 24 * 60 * 60,
					'next_run_at' => Vtiger_Cron::computeNextDailyRunAt($runAtMinutes),
				);
			case Vtiger_Cron::SCHEDULE_WEEKLY:
				return array(
					'schedule_type' => $type,
					'run_at_minutes' => $runAtMinutes,
					'run_on_weekdays' => implode(',', $runOnWeekdays),
					'run_on_day' => null,
					// 曜日を複数指定した場合は 1 週間より短い間隔で実行されるが、
					// frequency は表示や移行前データのための目安なので 1 週間としておく
					'frequency' => 7 * 24 * 60 * 60,
					'next_run_at' => Vtiger_Cron::computeNextWeeklyRunAt($runOnWeekdays, $runAtMinutes),
				);
			case Vtiger_Cron::SCHEDULE_MONTHLY:
				return array(
					'schedule_type' => $type,
					'run_at_minutes' => $runAtMinutes,
					'run_on_weekdays' => null,
					'run_on_day' => $runOnDay,
					// 月の長さは一定でないため目安の値
					'frequency' => 30 * 24 * 60 * 60,
					'next_run_at' => Vtiger_Cron::computeNextMonthlyRunAt($runOnDay, $runAtMinutes),
				);
		}

		$frequency = intval($this->get('frequency'));
		return array(
			'schedule_type' => Vtiger_Cron::SCHEDULE_INTERVAL,
			'run_at_minutes' => null,
			'run_on_weekdays' => null,
			'run_on_day' => null,
			'frequency' => $frequency,
			'next_run_at' => Vtiger_Cron::computeNextRunAt($frequency),
		);
	}

	/**
	 * 画面から渡された整数値を範囲で検証する。空欄・範囲外は null。
	 *
	 * @param <Mixed> $value
	 * @param <Integer> $min
	 * @param <Integer> $max
	 * @return <Integer>|null
	 */
	protected function normalizeIntegerInput($value, $min, $max) {
		if ($value === null || $value === '' || !is_numeric($value)) {
			return null;
		}
		$value = intval($value);
		return ($value >= $min && $value <= $max) ? $value : null;
	}

	/**
	 * Function to get record instance by using id and moduleName
	 * @param <Integer> $recordId
	 * @param <String> $qualifiedModuleName
	 * @return <Settings_CronTasks_Record_Model> RecordModel
	 */
	static public function getInstanceById($recordId, $qualifiedModuleName) {
		$db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT * FROM vtiger_cron_task WHERE id = ?", array($recordId));
		if ($db->num_rows($result)) {
			$recordModelClass = Vtiger_Loader::getComponentClassName('Model', 'Record', $qualifiedModuleName);
			$moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
			$rowData = $db->query_result_rowdata($result, 0);
			$recordModel = new $recordModelClass();
			$recordModel->setData($rowData)->setModule($moduleModel);
			return $recordModel;
		}
		return false;
	}
	
    public static function getInstanceByName($name) {
        $db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT * FROM vtiger_cron_task WHERE name = ?", array($name));
		if ($db->num_rows($result)) {
			$moduleModel = new Settings_CronTasks_Module_Model();
			$rowData = $db->query_result_rowdata($result, 0);
			$recordModel = new self();
			$recordModel->setData($rowData)->setModule($moduleModel);
			return $recordModel;
		}
		return false;
    }


		/**
	 * Function to get the list view actions for the record
	 * @return <Array> - Associate array of Vtiger_Link_Model instances
	 */
	public function getRecordLinks() {

		$links = array();

		$recordLinks = array(
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => "javascript:Settings_CronTasks_List_Js.triggerEditEvent('".$this->getEditViewUrl()."')",
				'linkicon' => 'fa-pencil'
			),
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_VIEW_LOG',
				'linkurl' => "javascript:Settings_CronTasks_List_Js.triggerLogEvent('".$this->getLogViewUrl()."')",
				'linkicon' => 'fa-file-text-o'
			)
		);
		foreach($recordLinks as $recordLink) {
			$links[] = Vtiger_Link_Model::getInstanceFromValues($recordLink);
		}

		return $links;
	}
	
	/**
	 * 画面から指定できる周期の下限（秒）。
	 *
	 * 従来は getMinimumCronFrequency()（既定 15 分）を下限にしていたため、15 分より短い
	 * 周期を指定できなかった。実際に意味のある下限は「スケジューラー（cron）自体を起動する
	 * 間隔」で、一般的な設定では 1 分である。それより短くしても cron が起動しないので
	 * 実行されない。下限は 1 分とし、config.inc.php の $MINIMUM_CRON_FREQUENCY で
	 * それより長い下限を明示している場合だけ、その値を尊重する。
	 *
	 * @return <Integer> 秒
	 */
	public function getMinimumFrequency() {
		global $MINIMUM_CRON_FREQUENCY;

		// 明示的に設定されている場合はその値を下限にする（従来の動作を保つ）
		if (!empty($MINIMUM_CRON_FREQUENCY)) {
			return intval($MINIMUM_CRON_FREQUENCY) * 60;
		}
		return self::MINIMUM_FREQUENCY_SECONDS;
	}
}