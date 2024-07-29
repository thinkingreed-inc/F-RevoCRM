<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'include/events/VTEntityData.inc';

class VTEntityDelta extends VTEventHandler {
	private static $oldEntity = array();
	private static $newEntity = array();
	private static $entityDelta;

	function  __construct() {
		
	}

	function handleEvent($eventName, $entityData) {

		$adb = PearDatabase::getInstance();
		$moduleName = $entityData->getModuleName();
		$recordId = $entityData->getId();

		if($eventName == 'vtiger.entity.beforesave') {
			if(!empty($recordId)) {
				$entityData = VTEntityData::fromEntityId($adb, $recordId, $moduleName);
				if($moduleName == 'HelpDesk') {
					$entityData->set('comments', getTicketComments($recordId));
				} elseif($moduleName == 'Invoice'){
					$entityData->set('invoicestatus', getInvoiceStatus($recordId));
				}
				self::$oldEntity[$moduleName][$recordId] = $entityData;
				self::$oldEntity[$moduleName][$recordId."_org"] = $entityData;
			}
		}

		if($eventName == 'vtiger.entity.aftersave'){
			$this->fetchEntity($moduleName, $recordId);
			$this->computeDelta($moduleName, $recordId);
		}
	}

	function fetchEntity($moduleName, $recordId) {
		$adb = PearDatabase::getInstance();
		$entityData = VTEntityData::fromEntityId($adb, $recordId, $moduleName);
		if($moduleName == 'HelpDesk') {
			$entityData->set('comments', getTicketComments($recordId));
		} elseif($moduleName == 'Invoice') {
			$entityData->set('invoicestatus', getInvoiceStatus($recordId));
		}
		if(!array_key_exists($moduleName, self::$newEntity) && !isset(self::$newEntity[$moduleName][$recordId])) {
			self::$newEntity[$moduleName][$recordId."_org"] = $entityData;
		}
		self::$newEntity[$moduleName][$recordId] = $entityData;
	}

	function computeDelta($moduleName, $recordId) {

		$delta = array();

		$oldData = array();
		if(!empty(self::$oldEntity[$moduleName][$recordId])) {
			$oldEntity = self::$oldEntity[$moduleName][$recordId];
			$oldData = $oldEntity->getData();
		}
		$newEntity = self::$newEntity[$moduleName][$recordId];
		$newData = $newEntity->getData();
		/** Detect field value changes **/
		foreach($newData as $fieldName => $fieldValue) {
			$isModified = false;
			if(empty($oldData[$fieldName])) {
				if(!empty($newData[$fieldName])) {
					$isModified = true;
				}
			} elseif(str_replace(array("\r\n","\r","\n"), "", $oldData[$fieldName]) != str_replace(array("\r\n","\r","\n"), "", $newData[$fieldName])) {
				$isModified = true;
			}
			if($isModified) {
				$delta[$fieldName] = array('oldValue' => $oldData[$fieldName],
										'currentValue' => $newData[$fieldName]);
			}
		}
		self::$entityDelta[$moduleName][$recordId] = $delta;

		if($newEntity == self::$newEntity[$moduleName][$recordId."_org"]) {
			self::$entityDelta[$moduleName][$recordId."_org"] = $delta;
		}
	}

	function getEntityDelta($moduleName, $recordId, $forceFetch=false) {
		if($forceFetch) {
			$this->fetchEntity($moduleName, $recordId);
			$this->computeDelta($moduleName, $recordId);
		}
		return self::$entityDelta[$moduleName][$recordId];
	}

	function setEntityDelta($moduleName, $recordId, $entityDelta) {
		return self::$entityDelta[$moduleName][$recordId] = $entityDelta;
	}

	function getOldValue($moduleName, $recordId, $fieldName) {
		$entityDelta = self::$entityDelta[$moduleName][$recordId];
		return $entityDelta[$fieldName]['oldValue'];
	}

	function getCurrentValue($moduleName, $recordId, $fieldName) {
		$entityDelta = self::$entityDelta[$moduleName][$recordId];
		return $entityDelta[$fieldName]['currentValue'];
	}

	function getOldEntity($moduleName, $recordId) {
		return self::$oldEntity[$moduleName][$recordId];
	}

	function getNewEntity($moduleName, $recordId) {
		return self::$newEntity[$moduleName][$recordId];
	}
	
	function setNewEntity($moduleName, $recordId, $newEntity) {
		if(!array_key_exists($moduleName, self::$newEntity) && !array_key_exists($recordId, self::$newEntity[$moduleName])) {
			self::$newEntity[$moduleName][$recordId."_org"] = $newEntity;
		}
		return self::$newEntity[$moduleName][$recordId] = $newEntity;
	}

	function hasChanged($moduleName, $recordId, $fieldName, $fieldValue = NULL, $isCheckOrg = false) {
		if($isCheckOrg) {
			$recordId = $recordId."_org";
		}
		if(empty(self::$oldEntity[$moduleName][$recordId])) {
			return false;
		}
		$fieldDelta = self::$entityDelta[$moduleName][$recordId][$fieldName];
		if(is_array($fieldDelta)) {
			$fieldDelta = array_map('decode_html', $fieldDelta);
		}
				
		$module = Vtiger_Module::getInstance($moduleName);
        $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $module);
        if($fieldModel->getFieldDataType() == "multipicklist") {
			$fieldDelta_oldValue = explode(' |##| ', $fieldDelta['oldValue']);
			$fieldDelta_currentValue = explode(' |##| ', $fieldDelta['currentValue']);
			$result = !empty(array_diff($fieldDelta_oldValue, $fieldDelta_currentValue)) || !empty(array_diff($fieldDelta_currentValue, $fieldDelta_oldValue));
			if ($fieldValue !== NULL) { //$fieldValueは「～に変更された」条件のとき
				$fieldValue = explode(' |##| ', $fieldValue);
				$result = $result && (empty(array_diff($fieldValue, $fieldDelta_currentValue)) && empty(array_diff($fieldDelta_currentValue, $fieldValue)));
			}
		} else {
			$result = str_replace(array("\r\n","\r","\n"), "", $fieldDelta['oldValue']) != str_replace(array("\r\n","\r","\n"), "", $fieldDelta['currentValue']);
		}
		if ($fieldValue !== NULL) {
			$result = $result && ($fieldDelta['currentValue'] === $fieldValue);
		}
		return $result;
	}

	function hasChangedWithPostTranslatedWording($moduleName, $recordId, $fieldName, $fieldValue = NULL, $isCheckOrg = false){
		if($isCheckOrg) {
			$recordId = $recordId."_org";
		}
		if(empty(self::$oldEntity[$moduleName][$recordId])) {
			return false;
		}
		$fieldDelta = self::$entityDelta[$moduleName][$recordId][$fieldName];
		if(is_array($fieldDelta)) {
			$fieldDelta = array_map('decode_html', $fieldDelta);
		}
		$result = str_replace(array("\r\n","\r","\n"), "", $fieldDelta['oldValue']) != str_replace(array("\r\n","\r","\n"), "", $fieldDelta['currentValue']);
		if ($fieldValue !== NULL) {
			$result = $result && (vtranslate($fieldDelta['currentValue'] , $moduleName) === $fieldValue);
		}
		return $result;
	}

}