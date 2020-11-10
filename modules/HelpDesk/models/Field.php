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
 * Calendar Field Model Class
 */
class HelpDesk_Field_Model extends Vtiger_Field_Model {
	/**
	 * Function to retieve display value for a value
	 * @param <String> $value - value which need to be converted to display value
	 * @return <String> - converted display value
	 */
	public function getDisplayValue($value, $record=false, $recordInstance = false) {
		if($this->getName() == 'description' || $this->getName() == 'solution') {
				return html_entity_decode($value);
		}
		return parent::getDisplayValue($value, $record, $recordInstance);
	}
	public function isCkEditor() {
		if($this->getName() == 'description' || $this->getName() == 'solution') {
			return true;
		}
		return false;
	}
}
