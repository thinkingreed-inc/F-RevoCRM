<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ******************************************************************************/
 require_once("include/events/SqlResultIterator.inc");

class MailScannerEntityMethodManager {
	
	function __construct($adb){
		$this->adb = $adb;
		 if (!Vtiger_Utils::CheckTable('vtiger_mailscanner_entitymethod')) {
                
                $adb->pquery('CREATE TABLE IF NOT EXISTS `vtiger_mailscanner_entitymethod` (
  `mailscanner_entitymethod_id` int(11) NOT NULL AUTO_INCREMENT,
 	`module_name` varchar(100) ,
	 	`method_name` varchar(100) ,
		 	`function_path` varchar(400) ,
			 	`function_name` varchar(100) ,
 	PRIMARY KEY (`mailscanner_entitymethod_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
', array());

 $adb->pquery('ALTER TABLE `vtiger_mailscanner_entitymethod`
  ADD PRIMARY KEY (`mailscanner_entitymethod_id`),
  ADD UNIQUE KEY `mailscanner_entitymethod_idx` (`mailscanner_entitymethod_id`);
', array());
/*
 $adb->pquery('CREATE TABLE IF NOT EXISTS `vtiger_mailscanner_entitymethod_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
', array());*/

$adb->pquery('ALTER TABLE `vtiger_mailscanner_actions`
   CHANGE `actiontype` `actiontype` VARCHAR(100), CHANGE `module` `module` VARCHAR(100), CHANGE `lookup` `lookup` VARCHAR(100) ;
', array());


            }
	}
	
	
	function addEntityMethod($moduleName, $methodName, $functionPath, $functionName){
		$adb = $this->adb;
		$id = $adb->getUniqueId("vtiger_mailscanner_entitymethod");
		$adb->pquery("insert into vtiger_mailscanner_entitymethod ( module_name, function_path, function_name, method_name) values (?,?,?,?)", array( $moduleName, $functionPath, $functionName, $methodName));
	}
	
	
	
	function executeMethod($moduleName, $methodName,$params){
		$adb = $this->adb;
		$moduleNameModel=Vtiger_Module_Model::getInstance($moduleName);
		if($moduleNameModel){
		if($moduleNameModel->isActive()){		
		$result = $adb->pquery("select function_path, function_name from vtiger_mailscanner_entitymethod where module_name=? and method_name=?", array($moduleName, $methodName));
		if($adb->num_rows($result)!=0){
			$data = $adb->raw_query_result_rowdata($result, 0);
			$functionPath = $data['function_path'];
			$functionName = $data['function_name'];
			if(file_exists($functionPath)){
			require_once($functionPath);
			$functionName($params);}
		}
		}
	}
	}
	
	function methodsForModule($moduleName){
		$adb = $this->adb;
		$result = $adb->pquery("select method_name from vtiger_mailscanner_entitymethod where module_name=?", array($moduleName));
		$it = new SqlResultIterator($adb, $result);
		$methodNames = array();
		foreach($it as $row){
			$methodNames[] = $row->method_name;
		}
		return $methodNames;
	}
	/*
	private function methodExists($object, $methodName){
		$className = get_class($object);
		$class = new ReflectionClass($className);
		$methods = $class->getMethods();
		foreach($methods as $method){
			if($method->getName()==$methodName){
				return true;
			}
		}
		return false;
	}*/

	/**
	 * Function to remove mailscanner action entity method 
	 * @param <String> Module Name
	 * @param <String> Entity Method Name.
	 */
	function removeEntityMethod($moduleName, $methodName){
		$adb = $this->adb;
		$adb->pquery("DELETE FROM vtiger_mailscanner_entitymethod WHERE module_name = ? and method_name= ?", array($moduleName, $methodName));
	}
}
?>