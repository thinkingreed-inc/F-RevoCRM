<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/** Classes to avoid logging */
class LoggerPropertyConfigurator {
	
	static $singleton = false;
	
	function __construct() {
	}
	
	function configure($configfile) {
		$configinfo = parse_ini_file($configfile);
		
		$types = array();
		$appenders = array();
		
		foreach($configinfo as $k=>$v) {
			if(preg_match("/log4php.rootLogger/i", $k, $m)) {
				$name = 'ROOT';
				list($level, $appender) = explode(',', $v);
				$types[$name]['level'] = $level;
				$types[$name]['MaxBackupIndex'] = $configinfo['log4php.appender.'.$appender.'.MaxBackupIndex'];
				$types[$name]['File'] = $configinfo['log4php.appender.'.$appender.'.File'];
			}
			if(preg_match("/log4php.logger.(.*)/i", $k, $m)) {
				$name = $m[1];
				list($level, $appender) = explode(',', $v);
				$types[$name]['level'] = $level;
				$types[$name]['MaxBackupIndex'] = $configinfo['log4php.appender.'.$appender.'.MaxBackupIndex'];
				$types[$name]['File'] = $configinfo['log4php.appender.'.$appender.'.File'];
			}
			
		}
		
		$this->types = $types;
		$this->appenders = $appenders;		
	}

	function getConfigInfo($type) {
		if(isset($this->types[$type])) {
			$typeinfo = $this->types[$type];
			return $typeinfo;
		} else {
			return $this->types['ROOT'];
		}
	}
	
	static function getInstance() {
		if (!self::$singleton) self::$singleton = new static();
		return self::$singleton;
	}
}
?>
