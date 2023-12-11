<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * User Field Model Class
 */
class Users_Field_Model extends Vtiger_Field_Model {

	/**
	 * Function to check whether the current field is read-only
	 * @return <Boolean> - true/false
	 */
	public function isReadOnly() {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if(($currentUserModel->isAdminUser() == false && $this->get('uitype') == 98) || $this->get('uitype') == 106 || $this->get('uitype') == 156 || $this->get('uitype') == 115) {
			return true;
		}
	}


	/**
	 * Function to check if the field is shown in detail view
	 * @return <Boolean> - true/false
	 */
	public function isViewEnabled() {
		if($this->getDisplayType() == '4' || in_array($this->get('presence'), array(1,3))) {
			return false;
		}
		return true;
	}


	/**
	 * Function to get the Webservice Field data type
	 * @return <String> Data type of the field
	 */
	public function getFieldDataType() {
		if($this->get('uitype') == 99){
			return 'password';
		}else if(in_array($this->get('uitype'), array(32, 115))) {
			return 'picklist';
		} else if($this->get('uitype') == 101) {
			return 'userReference';
		} else if($this->get('uitype') == 98) {
			return 'userRole';
		} elseif($this->get('uitype') == 105) {
			return 'image';
		} else if($this->get('uitype') == 31) {
			return 'theme';
		}
		return parent::getFieldDataType();
	}

	/**
	 * Function to check whether field is ajax editable'
	 * @return <Boolean>
	 */
	public function isAjaxEditable() {
		if(!$this->isEditable() || $this->get('uitype') == 105 || $this->get('uitype') == 106 || $this->get('uitype') == 98 || $this->get('uitype') == 101) {
			return false;
		}
		return true;
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @return <Array> List of picklist values if the field is of type picklist or multipicklist, null otherwise.
	 */
	public function getPicklistValues() {
        $fieldName = $this->getName(); 
		if($this->get('uitype') == 32) {
			 if($fieldName == 'language'){ 
                return Vtiger_Language_Handler::getAllLanguages(); 
            } else if($fieldName == 'defaultlandingpage'){
				$db = PearDatabase::getInstance();
                    $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
                    $presence = array(0);
                    $restrictedModules = array('Webmails', 'Emails', 'Integration', 'Dashboard','ModComments');
                    $query = 'SELECT name, tablabel, tabid FROM vtiger_tab WHERE presence IN (' . generateQuestionMarks($presence) . ') AND isentitytype = ? AND name NOT IN (' . generateQuestionMarks($restrictedModules) . ')';

                    $result = $db->pquery($query, array($presence, '1', $restrictedModules));
                    $numOfRows = $db->num_rows($result);

                    $moduleData = array('Home' => vtranslate('Home','Home'));
                    for ($i = 0; $i < $numOfRows; $i++) {
                        $tabId = $db->query_result($result, $i, 'tabid');
                        // check the module access permission, if user has permission then show it in default module list
                        if($currentUserPriviligesModel->hasModulePermission($tabId)){
                            $moduleName = $db->query_result($result, $i, 'name');
                            $moduleLabel = $db->query_result($result, $i, 'tablabel');
                            $moduleData[$moduleName] = vtranslate($moduleLabel,$moduleName);
                        }
                    }
                    return $moduleData;
			}
		}
		else if ($this->get('uitype') == '115') {
			$db = PearDatabase::getInstance();

			$query = 'SELECT '.$this->getFieldName().' FROM vtiger_'.$this->getFieldName();
			$result = $db->pquery($query, array());
			$num_rows = $db->num_rows($result);
			$fieldPickListValues = array();
			for($i=0; $i<$num_rows; $i++) {
				$picklistValue = $db->query_result($result,$i,$this->getFieldName());
				$fieldPickListValues[$picklistValue] = vtranslate($picklistValue,$this->getModuleName());
			}
			return $fieldPickListValues;
		}
		return parent::getPicklistValues();
	}
    
    /**
	 * Function to get all the available picklist values for the current field
	 * @return <Array> List of picklist values if the field is of type picklist or multipicklist, null otherwise.
	 */
	public function getEditablePicklistValues() {
		return $this->getPicklistValues();
	}

	/**
	 * Function to returns all skins(themes)
	 * @return <Array>
	 */
	public function getAllSkins(){
		return Vtiger_Theme::getAllSkins();
	}

	/**
	 * Function to retieve display value for a value
	 * @param <String> $value - value which need to be converted to display value
	 * @return <String> - converted display value
	 */
	public function getDisplayValue($value, $recordId = false,$recordInstance=false) {

		 if($this->get('uitype') == 32){
			return Vtiger_Language_Handler::getLanguageLabel($value);
		 }
		 $fieldName = $this->getFieldName();
		 if(($fieldName == 'currency_decimal_separator' || $fieldName == 'currency_grouping_separator') && ($value == "&nbsp;")) {
			 return vtranslate('Space', 'Users');
		 }
		return parent::getDisplayValue($value, $recordId);
	}

	/**
	 * Function returns all the User Roles
	 * @return
	 */
	 public function getAllRoles(){
		$roleModels = Settings_Roles_Record_Model::getAll();
		$roles = array();
		foreach ($roleModels as $roleId=>$roleModel) {
			$roleName = $roleModel->getName();
			$roles[$roleName] = $roleId;
		}
		return $roles;
	}

	/**
	 * Function to check whether this field editable or not
	 * return <boolen> true/false
	 */
	public function isEditable() {
		$isEditable = $this->get('editable');
		if (!$isEditable) {
			$this->set('editable', parent::isEditable());
		}
		return $this->get('editable');
	}

	/**
	 * Function which will check if empty piclist option should be given
	 */
	public function isEmptyPicklistOptionAllowed() {
		// cf関連のフィールドの場合は、空の値を許可する
		$fieldname = $this->getFieldName();
		if(strpos($fieldname, 'cf_') !== false){
			return true;
		}
		// 空も許可する項目の場合はtrueを返す
		if($fieldname == 'reminder_interval') {
			return true;
		}
		return false;
	}

	public function getUIType() {
		return $this->get('uitype');
	}

	public function getPicklistDetails() {
		if ($this->get('uitype') == 98) {
			$picklistValues = $this->getAllRoles();
			$picklistValues = array_flip($picklistValues);
		} else {
			$picklistValues = $this->getPicklistValues();
		}

		$pickListDetails = array();
		foreach ($picklistValues as $value => $transValue) {
			$pickListDetails[] = array('label' => $transValue, 'value' => $value);
		}
		return $pickListDetails;
	}
}
