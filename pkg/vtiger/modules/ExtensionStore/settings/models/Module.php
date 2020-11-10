<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class Settings_ExtensionStore_Module_Model extends Vtiger_Module_Model {

	public function getDefaultViewName() {
		return 'ExtensionStore';
	}

	public function getDefaultUrl() {
		return 'index.php?module='.$this->getName().'&parent=Settings&view='.$this->getDefaultViewName();
	}

	public static function getInstance($moduleName = 'ExtensionStore') {
		$moduleModel = parent::getInstance($moduleName);
		$objectProperties = get_object_vars($moduleModel);

		$instance = new self();
		foreach ($objectProperties as $properName => $propertyValue) {
			$instance->$properName = $propertyValue;
		}
		return $instance;
	}

}
