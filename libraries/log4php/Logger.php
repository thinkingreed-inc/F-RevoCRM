<?php
require_once 'vendor/autoload.php';
include_once dirname(__FILE__) . '/LoggerPropertyConfigurator.php';

class Logger {
	private static $instances = array();
	private $log = null;
		
	function __construct($name, $configinfo = false) {
		// log4phpは開発終了のため、monologを利用する
		$dateFormat = "Y-m-d H:i:s";
		$output     = "[%datetime%][%level_name%][".$name."] %message%\n";  // 複数チャンネルあるなら[%channel%]も追加
		$formatter  = new Monolog\Formatter\LineFormatter($output, $dateFormat, false, true);
		$formatter->includeStacktraces(true); // これでスタックトレースをキレイに出せる

		$filepath = $configinfo['File'];
		$maxbackup = $configinfo['MaxBackupIndex'];
		$level = $this->getLogLevel($configinfo['level']);

		// RotatingFileHandler追加。日単位でログファイルを残す。
		$rotatingFileHandler = new Monolog\Handler\RotatingFileHandler($filepath, $maxbackup, $level);
		$rotatingFileHandler->setFilenameFormat('{filename}_{date}', 'Ymd');
		$rotatingFileHandler->setFormatter($formatter); // ログ書式
		$log = new Monolog\Logger($name);
		
		$log->pushHandler($rotatingFileHandler);

		$this->log = $log;
	}
	
	function info($message) {
		$this->log->info($message);
	}
	
	function debug($message) {
		$this->log->debug($message);
	}
	
	function warn($message) {
		$this->log->warning($message);
	}
	
	function fatal($message) {
		$this->log->critical($message);
	}
	
	function error($message) {
		$this->log->error($message);
	}
        
	static function getlogger($name = 'ROOT') {
		if(self::$instances[$name]) {
			return self::$instances[$name];
		}

		$configinfo = LoggerPropertyConfigurator::getInstance()->getConfigInfo($name);
		$logger = new Logger($name, $configinfo);

		return $logger;
	}

	static function configure($config) {
		$configinfo = LoggerPropertyConfigurator::getInstance();
		$configinfo->configure($config);
	}

	function getLogLevel($loglevel) {
		$level = Monolog\Logger::INFO;
		switch($loglevel) {
			case 'ERROR':
				$level = Monolog\Logger::ERROR;
				break;
			case 'WARN':
				$level = Monolog\Logger::WARNING;
				break;
			case 'INFO':
				$level = Monolog\Logger::INFO;
				break;
			case 'DEBUG':
				$level = Monolog\Logger::DEBUG;
				break;
			case 'FATAL':
				$level = Monolog\Logger::CRITICAL;
				break;
		}
		return $level;
	}
}
