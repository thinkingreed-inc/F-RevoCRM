<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Import_ExportHistory_Action extends Vtiger_Action_Controller {

    public function process(Vtiger_Request $request) {
        $db = PearDatabase::getInstance();        
        $obj = new Vtiger_ExportData_Action();

        $current_user = Users_Record_Model::getCurrentUserModel();
        $importid = $request->get('importid');
        $userid = $request->get('userid');
        if ($current_user->isAdminUser()) {
			$importUser = Users_Record_Model::getInstanceById($userid, 'Users');
        } else {
            $importUser = $current_user;
        }

        $moduleName = Import_Queue_Action::getModulenameByImportid($importid);
        $request->set('source_module',$moduleName);

        $tableName = Import_Utils_Helper::getDbTableName($importUser,$importid);
        $query = "select * from $tableName";
		$result = $db->pquery($query, array());
        $entries = array();
		for ($j = 0; $j < $db->num_rows($result); $j++) {
            $entries[] = $db->fetchByAssoc($result, $j);
		}

        for ($i = 0; $i < $j; $i++){
            if (array_key_exists('status',$entries[$i])){
                $entries[$i]['status'] = $this->translateStatus($entries[$i]['status']);
            }
		}

        $translatedHeaders = $this->getHeaders($tableName, $moduleName);
		$obj->output($request, $translatedHeaders, $entries);

    }

    public function getHeaders($tableName, $moduleName) {
        $db = PearDatabase::getInstance();        
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $fields = $moduleModel->getFields();

        $result = $db->pquery("SHOW COLUMNS FROM $tableName", array());
        $columns = [];
        while ($row = $db->fetchByAssoc($result)) {
            $columns[] = $row['field'];
        }
        $translatedHeaders = array();
		foreach($columns as $column) {
            $label = null;
            if (isset($fields[$column])) {
                $label = $fields[$column]->get('label');
            } else {
                $label = $column;
            }
            
			$translatedHeaders[] = vtranslate($label, $moduleName);
		}

		$translatedHeaders = array_map('decode_html', $translatedHeaders);
		return $translatedHeaders;
    }

    public function translateStatus($status) {
        $statusLabel = $status;
        switch ($status) {
            case Import_Data_Action::$IMPORT_RECORD_NONE	: $statusLabel = "LBL_IMPORT_RECORD_NONE";    break;	
            case Import_Data_Action::$IMPORT_RECORD_CREATED	: $statusLabel = "LBL_IMPORT_RECORD_CREATED";	break;	
            case Import_Data_Action::$IMPORT_RECORD_SKIPPED	: $statusLabel = "LBL_IMPORT_RECORD_SKIPPED";	break;	
            case Import_Data_Action::$IMPORT_RECORD_UPDATED	: $statusLabel = "LBL_IMPORT_RECORD_UPDATED";	break;
            case Import_Data_Action::$IMPORT_RECORD_MERGED	: $statusLabel = "LBL_IMPORT_RECORD_MERGED";	break;	
            case Import_Data_Action::$IMPORT_RECORD_FAILED	: $statusLabel = "LBL_IMPORT_RECORD_FAILED";	break;
        }
        return vtranslate($statusLabel, 'Import');
    } 

}
