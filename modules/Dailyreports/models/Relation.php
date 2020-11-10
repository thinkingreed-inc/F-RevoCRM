<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Dailyreports_Relation_Model extends Vtiger_Relation_Model{
	/**
	 * Function which will specify whether the relation is deletable
	 * @return <Boolean>
	 */
	public function isDeletable() {
		if($this->getRelationModuleName() == "Calendar") {
			return false;
		}
		return $this->getRelationModuleModel()->isPermitted('Delete');
	}
}
