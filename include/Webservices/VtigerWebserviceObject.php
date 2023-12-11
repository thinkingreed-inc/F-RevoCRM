<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class VtigerWebserviceObject{
	
	private $id;
	private $name;
	private $handlerPath;
	private $handlerClass;
	
	private function __construct($entityId,$entityName,$handler_path,$handler_class)
	{
		$this->id = $entityId;
		$this->name = $entityName;
		// Quick Fix to override default Actor class & path (good to update DB itself)
		if ($entityName == 'CompanyDetails') {
			$handler_path = 'include/Webservices/Custom/VtigerCompanyDetails.php';
			$handler_class= 'VtigerCompanyDetails';
		}
		// END
		$this->handlerPath = $handler_path;
		$this->handlerClass = $handler_class;
	}
	
	private function VtigerWebserviceObject($entityId,$entityName,$handler_path,$handler_class){
		// PHP4-style constructor.
		// This will NOT be invoked, unless a sub-class that extends `foo` calls it.
		// In that case, call the new-style constructor to keep compatibility.
		self::__construct($entityId,$entityName,$handler_path,$handler_class);
	}
	
	// Cache variables to enable result re-use
	private static $_fromNameCache = array();
		
	static function fromName($adb,$entityName){
		
		$rowData = false;
		
		// If the information not available in cache?
		if(!isset(self::$_fromNameCache[$entityName])) {
			$cacheLength = php7_count(self::$_fromNameCache);
			
			$result = null;
			if ($cacheLength == 0) {
				$result = $adb->pquery("select * from vtiger_ws_entity where name=?",array($entityName));
			} else {
				// Could repeat more number of times...so let us pull rest of details into cache.
				$result = $adb->pquery("select * from vtiger_ws_entity", array());
			}
			
			if($result){
				$rowCount = $adb->num_rows($result);
				while ($rowCount > 0) {
					$rowData = $adb->query_result_rowdata($result,$rowCount-1);
					self::$_fromNameCache[$rowData['name']] = $rowData;
					--$rowCount;
				}
			}
		}
		
		$rowData = self::$_fromNameCache[$entityName];
		
		if($rowData) {
			return new VtigerWebserviceObject($rowData['id'],$rowData['name'],
						$rowData['handler_path'],$rowData['handler_class']);
		}
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied for name : $entityName");
	}

	// Cache variables to enable result re-use
	private static $_fromIdCache = array();	
	
	static function fromId($adb,$entityId){		
		$rowData = false;
		
		// If the information not available in cache?
		if(!isset(self::$_fromIdCache[$entityId])) {
			$result = $adb->pquery("select * from vtiger_ws_entity where id=?",array($entityId));
			if($result){
				$rowCount = $adb->num_rows($result);
				if($rowCount === 1){
					$rowData = $adb->query_result_rowdata($result,0);
					self::$_fromIdCache[$entityId] = $rowData;
				}
			}
		}
		
		$rowData = self::$_fromIdCache[$entityId];
		
		if($rowData) {
			return new VtigerWebserviceObject($rowData['id'],$rowData['name'],
					$rowData['handler_path'],$rowData['handler_class']);
		}
		
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied for id");
	}
	
	static function fromQuery($adb,$query){
		$moduleRegex = "/[fF][rR][Oo][Mm]\s+([^\s;]+)/";
		$matches = array();
		$found = preg_match($moduleRegex,$query,$matches);
		if($found === 1){
			return VtigerWebserviceObject::fromName($adb,trim($matches[1]));
		}
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied for query");
	}
	
	public function getEntityName(){
		return $this->name;
	}
	
	public function getEntityId(){
		return $this->id;
	}
	
	public function getHandlerPath(){
		return $this->handlerPath;
	}
	
	public function getHandlerClass(){
		return $this->handlerClass;
	}
	
}
?>