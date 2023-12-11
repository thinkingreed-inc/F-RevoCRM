<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/*
 * Vtiger Settings MenuItem Model Class
 */
class Settings_Vtiger_MenuItem_Model extends Vtiger_Base_Model {

	protected static $itemsTable = 'vtiger_settings_field';
	protected static $itemId = 'fieldid';

	/**
	 * Function to get the Id of the menu item
	 * @return <Number> - Menu Item Id
	 */
	public function getId() {
		return $this->get(self::$itemId);
	}

	/**
	 * Function to get the Menu to which the Item belongs
	 * @return Settings_Vtiger_Menu_Model instance
	 */
	public function getMenu() {
		return $this->menu;
	}

	/**
	 * Function to set the Menu to which the Item belongs, given Menu Id
	 * @param <Number> $menuId
	 * @return Settings_Vtiger_MenuItem_Model
	 */
	public function setMenu($menuId) {
		$this->menu = Settings_Vtiger_Menu_Model::getInstanceById($menuId);
		return $this;
	}

	/**
	 * Function to set the Menu to which the Item belongs, given Menu Model instance
	 * @param <Settings_Vtiger_Menu_Model> $menu - Settings Menu Model instance
	 * @return Settings_Vtiger_MenuItem_Model
	 */
	public function setMenuFromInstance($menu) {
		$this->menu = $menu;
		return $this;
	}

	/**
	 * Function to get the url to get to the Settings Menu Item
	 * @return <String> - Menu Item landing url
	 */
	public function getUrl() {
		$url = decode_html($this->get('linkto'));
		$menu = $this->getMenu();
		$url .= '&block='.$this->getMenu()->getId().'&fieldid='.$this->getId();
		return $url;
	}

	/**
	 * Function to get the module name, to which the Settings Menu Item belongs to
	 * @return <String> - Module to which the Menu Item belongs
	 */
	public function getModuleName() {
		return 'Settings:Vtiger';
	}
    /**
	 *  Function to get the pin and unpin action url
	 */
	public function getPinUnpinActionUrl() {
		return 'index.php?module=Vtiger&parent=Settings&action=Basic&mode=updateFieldPinnedStatus&fieldid='.$this->getId();
	}

	/**
	 * Function to verify whether menuitem is pinned or not
	 * @return <Boolean> true to pinned, false to not pinned.
	 */
	public function isPinned() {
		$pinStatus = $this->get('pinned');
		return $pinStatus == '1' ? true : false;
	}

	/**
     * Function which will update the pin status 
     * @param <Boolean> $pinned - true to enable , false to disable
     */
    private function updatePinStatus($pinned=false){
        $db = PearDatabase::getInstance();
        
        $pinnedStaus = 0;
        if($pinned) {
            $pinnedStaus = 1;
        }
        
        $query = 'UPDATE '.self::$itemsTable.' SET pinned='.$pinnedStaus.' WHERE '.self::$itemId.'='.$this->getId();
        $db->pquery($query,array());
    }
    
    /**
     * Function which will enable the field as pinned
     */
    public function markPinned() {
        $this->updatePinStatus(1);
    }
    
    /**
     * Function which will disable the field pinned status
     */
    public function unMarkPinned() {
        $this->updatePinStatus();
    }

	/**
	 * Function to get the instance of the Menu Item model given the valuemap array
	 * @param <Array> $valueMap
	 * @return Settings_Vtiger_MenuItem_Model instance
	 */
	public static function getInstanceFromArray($valueMap) {
		return new self($valueMap);
	}

	/**
	 * Function to get the instance of the Menu Item model, given name and Menu instance
	 * @param <String> $name
	 * @param <Settings_Vtiger_Menu_Model> $menuModel
	 * @return Settings_Vtiger_MenuItem_Model instance
	 */
	public static function getInstance($name, $menuModel=false) {
		$db = PearDatabase::getInstance();

		$sql = 'SELECT * FROM '.self::$itemsTable. ' WHERE name = ?';
		$params = array($name);

		if($menuModel) {
			$sql .= ' WHERE blockid = ?';
			$params[] = $menuModel->getId();
		}
		$result = $db->pquery($sql, $params);

		if($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			$menuItem = Settings_Vtiger_MenuItem_Model::getInstanceFromArray($rowData);
			if($menuModel) {
				$menuItem->setMenuFromInstance($menuModel);
			} else {
				$menuItem->setMenu($rowData['blockid']);
			}
			return $menuItem;
		}
		return false;
	}

	/**
	 * Function to get the instance of the Menu Item model, given item id and Menu instance
	 * @param <String> $name
	 * @param <Settings_Vtiger_Menu_Model> $menuModel
	 * @return Settings_Vtiger_MenuItem_Model instance
	 */
	public static function getInstanceById($id, $menuModel=false) {
		$db = PearDatabase::getInstance();

		$sql = 'SELECT * FROM '.self::$itemsTable. ' WHERE ' .self::$itemId. ' = ?';
		$params = array($id);

		if($menuModel) {
			$sql .= ' WHERE blockid = ?';
			$params[] = $menuModel->getId();
		}
		$result = $db->pquery($sql, $params);

		if($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			$menuItem = Settings_Vtiger_MenuItem_Model::getInstanceFromArray($rowData);
			if($menuModel) {
				$menuItem->setMenuFromInstance($menuModel);
			} else {
				$menuItem->setMenu($rowData['blockid']);
			}
			return $menuItem;
		}
		return false;
	}

	/**
	 * Static function to get the list of all the items of the given Menu, all items if Menu is not specified
	 * @param <Settings_Vtiger_Menu_Model> $menuModel
	 * @return <Array> - List of Settings_Vtiger_MenuItem_Model instances
	 */
	public static function getAll($menuModel=false, $onlyActive=true) {
		$skipMenuItemList = array('LBL_AUDIT_TRAIL', 'LBL_SYSTEM_INFO', 'LBL_PROXY_SETTINGS', 'LBL_DEFAULT_MODULE_VIEW',
								'LBL_FIELDFORMULAS', 'LBL_FIELDS_ACCESS', 'LBL_MAIL_MERGE', 'NOTIFICATIONSCHEDULERS',
								'INVENTORYNOTIFICATION', 'ModTracker', 'LBL_WORKFLOW_LIST','LBL_TOOLTIP_MANAGEMENT','Webforms Configuration Editor');

		$db = PearDatabase::getInstance();
		$sql = 'SELECT * FROM '.self::$itemsTable;
		$params = array();

		$conditionsSqls = array();
		if($menuModel != false) {
			$conditionsSqls[] = 'blockid = ?';
			$params[] = $menuModel->getId();
		}
		if($onlyActive) {
			$conditionsSqls[] = 'active = 0';
		}
		if(php7_count($conditionsSqls) > 0) {
			$sql .= ' WHERE '. implode(' AND ', $conditionsSqls);
		}
		$sql .= ' AND name NOT IN ('.generateQuestionMarks($skipMenuItemList).')';

		$sql .= ' ORDER BY sequence';
		$result = $db->pquery($sql, array_merge($params, $skipMenuItemList));
		$noOfMenus = $db->num_rows($result);

		$menuItemModels = array();
		for($i=0; $i<$noOfMenus; ++$i) {
			$fieldId = $db->query_result($result, $i, self::$itemId);
			$rowData = $db->query_result_rowdata($result, $i);
			$menuItem = Settings_Vtiger_MenuItem_Model::getInstanceFromArray($rowData);
			if($menuModel) {
				$menuItem->setMenuFromInstance($menuModel);
			} else {
				$menuItem->setMenu($rowData['blockid']);
			}
			$menuItemModels[$fieldId] = $menuItem;
		}
		return $menuItemModels;
	}
    
    /**
     * Function to get the pinned items 
	 * @param array of fieldids.
     * @return <Array> - List of Settings_Vtiger_MenuItem_Model instances
     */
    public static function getPinnedItems($fieldList = array()) {
		$skipMenuItemList = array('LBL_AUDIT_TRAIL', 'LBL_SYSTEM_INFO', 'LBL_PROXY_SETTINGS', 'LBL_DEFAULT_MODULE_VIEW',
								'LBL_FIELDFORMULAS', 'LBL_FIELDS_ACCESS', 'LBL_MAIL_MERGE', 'NOTIFICATIONSCHEDULERS',
								'INVENTORYNOTIFICATION', 'ModTracker', 'LBL_WORKFLOW_LIST','LBL_TOOLTIP_MANAGEMENT','Webforms Configuration Editor');
		
        $db = PearDatabase::getInstance();
        
        $query = 'SELECT * FROM '.self::$itemsTable.' WHERE pinned=1 AND active = 0';
		if(!empty($fieldList)) {
			if(!is_array($fieldList)){
				$fieldList = array($fieldList);
			}
			$query .=' AND '.self::$itemsId.' IN ('.generateQuestionMarks($fieldList).')';
		}
		$query .= ' AND name NOT IN ('.generateQuestionMarks($skipMenuItemList).')';
        
		$result = $db->pquery($query, array_merge($fieldList, $skipMenuItemList));
        $noOfMenus = $db->num_rows($result);
        
        $menuItemModels = array();
		for($i=0; $i<$noOfMenus; ++$i) {
			$fieldId = $db->query_result($result, $i, self::$itemId);
			$rowData = $db->query_result_rowdata($result, $i);
			$menuItem = Settings_Vtiger_MenuItem_Model::getInstanceFromArray($rowData);
            $menuItem->setMenu($rowData['blockid']);
			$menuItemModels[$fieldId] = $menuItem;
		}
		return $menuItemModels;
    }
}
