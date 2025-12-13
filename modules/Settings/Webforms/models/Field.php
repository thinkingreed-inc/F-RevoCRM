<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Webforms_Field_Model extends Vtiger_Field_Model {

	/**
	 * Function get field is viewable or not
	 * @return <Boolean> true/false
	 */
	public function isViewable() {
		return true;
	}

	/**
	 * Function to get instance of Field by using array of data
	 * @param <Array> $rowData
	 * @return <Settings_Webforms_Field_Model> FieldModel
	 */
	static public function getInstanceByRow($rowData) {
		$fieldModel = new self();
		foreach ($rowData as $name => $value) {
			$fieldModel->set($name, $value);
		}
		return $fieldModel;
	}

	/**
	 * Function to check whether this field editable or not
	 * @return <Boolean> true/false
	 */
	public function isEditable() {
		if (($this->getName() === 'publicid') || ($this->getName() === 'posturl')) {
			return false;
		}
		return true;
	}
	
	public function isReadOnly() {
		if ($this->getName() === 'name') {
			return $this->get('readonly');
		}
		return false;
	}
    
    /**
	 * Function to get the value of a given property
	 * @param <String> $propertyName
	 * @return <Object>
	 * @throws Exception
	 */
    public function get($propertyName) {
		if($propertyName == 'fieldvalue' && $this->name == 'roundrobin_userid') {
            $value = str_replace('&quot;', '"', $this->$propertyName);
			return json_decode($value,true);
		}
		return parent::get($propertyName);
	}
	
    /**
	 * Function to get Picklist values
	 * @return <Array> Picklist values
	 */
	public function getPicklistValues() {
		if ($this->getName() === 'targetmodule') {
			return Settings_Webforms_Module_Model::getsupportedModulesList();
		}
		return array();
	}
    
    /**
	 * Function to get Editable Picklist values
	 * @return <Array> Editable Picklist values
	 */
	public function getEditablePicklistValues() {
		return $this->getPicklistValues();
	}
	
	public function getDisplayValue($value, $record=false, $recordInstance = false) {
		if ($this->getName() === 'enabled') {
			$moduleName = 'Settings:Webforms';
			if ($value) {
				return vtranslate('LBL_ACTIVE', $moduleName);
			}
			return vtranslate('LBL_INACTIVE', $moduleName);
		}
		return parent::getDisplayValue($value);
	}
    
	public function getPermissions($accessmode = 'readonly') {
		return true;
	}
    
    /**
     * Function which will check if empty piclist option should be given
     */
    public function isEmptyPicklistOptionAllowed() {
        return false;
    }

	/**
	 * Function to get the field details
	 * @return <Array> - array of field values
	 */
	public function getFieldInfo() {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$fieldInfo =  array(
			'mandatory' => $this->isMandatory(),
			'type' => $this->getFieldDataType(),
			'name' => $this->getFieldName(),
			'label' => vtranslate($this->get('label'), $this->getModuleName()),
			'defaultValue' => $this->getEditViewDisplayValue($this->getDefaultFieldValue()),
			'customField' => Settings_Webforms_Record_Model::isCustomField($this->get('name')),
			'specialValidator' => $this->getValidator()
		);
		$pickListValues = $this->getPicklistValues();
		$picklistColors = $this->getPicklistColors();
		if(!empty($pickListValues)) {
			$fieldInfo['picklistvalues'] = $pickListValues;
            $fieldInfo['editablepicklistvalues'] = $pickListValues;
			$fieldInfo['picklistColors'] = $picklistColors;
		}
		if($this->getFieldDataType() == 'date' || $this->getFieldDataType() == 'datetime'){
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$fieldInfo['date-format'] = $currentUser->get('date_format');
		}
		if($this->getFieldDataType() == 'currency') {
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$fieldInfo['currency_symbol'] = $currentUser->get('currency_symbol');
			$fieldInfo['decimalSeparator'] = $currentUser->get('currency_decimal_separator');
			$fieldInfo['groupSeparator'] = $currentUser->get('currency_grouping_separator');
		}
		if($this->getFieldDataType() == 'owner') {
			$userList = $currentUser->getAccessibleUsers();
			$groupList = $currentUser->getAccessibleGroups();
			$pickListValues = array();
			$pickListValues[vtranslate('LBL_USERS', $this->getModuleName())] = $userList;
			$pickListValues[vtranslate('LBL_GROUPS', $this->getModuleName())] = $groupList;
			$fieldInfo['picklistvalues'] = $pickListValues;
		}
		if($this->getFieldDataType() == 'reference') {
			$referenceList = $this->getReferenceList();
			$fieldInfo['referencemodules']= $referenceList;
		}
		if($this->getFieldDataType() == 'ownergroup') {
			$groupList = $currentUser->getAccessibleGroups();
			$pickListValues = array();
			$fieldInfo['picklistvalues'] = $groupList;
		}
		if($fieldInfo['type'] == 'boolean') {
			$fieldInfo['type'] = 'picklist';
			$fieldInfo['picklistvalues'] = array('' => vtranslate('LBL_SELECT_OPTION'), 'on' => vtranslate('LBL_YES'), 'off' => vtranslate('LBL_NO'));
		}
		return $fieldInfo;
	}

}