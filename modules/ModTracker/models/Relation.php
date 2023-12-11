<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ModTracker_Relation_Model extends Vtiger_Record_Model {

	function setParent($parent) {
		$this->parent = $parent;
	}

	function getParent() {
		return $this->parent;
	}

	function getLinkedRecord() {
        $db = PearDatabase::getInstance();
        
		$targetId = $this->get('targetid');
		$targetModule = $this->get('targetmodule');
        
        if(!Users_Privileges_Model::isPermitted($targetModule, 'DetailView', $targetId)) {
			return false;
		}
        $query = 'SELECT * FROM vtiger_crmentity WHERE crmid = ?';
		$params = array($targetId);
		$result = $db->pquery($query, $params);
		$noOfRows = $db->num_rows($result);
		$moduleModels = array();
		if($noOfRows) {
			if(!array_key_exists($targetModule, $moduleModels)) {
				$moduleModel = Vtiger_Module_Model::getInstance($targetModule);
			}
			$row = $db->query_result_rowdata($result, 0);
			$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $targetModule);
			$recordInstance = new $modelClassName();
			$recordInstance->setData($row)->setModuleFromInstance($moduleModel);
			$recordInstance->set('id', $row['crmid']);
			if($targetModule == 'Emails') {
				$recordInstance->set('parent_id', $this->parent->get('crmid'));
			}
			return $recordInstance;
		}
		return false;
	}

	public function getRecordDetailViewUrl() {
		try {
			$recordModel = Vtiger_Record_Model::getInstanceById($this->get('targetid'), $this->get('targetmodule'));
			if ($this->get('targetmodule') == 'Emails') {
				return $recordModel->getDetailViewUrl($this->parent->get('crmid'));
			}
			return $recordModel->getDetailViewUrl();
		} catch (Exception $e) {
			return false;
		}
	}

}
