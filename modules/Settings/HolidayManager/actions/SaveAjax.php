<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_HolidayManager_SaveAjax_Action extends Settings_Vtiger_Basic_Action {
    public function process(Vtiger_Request $request) {
        
        $record = $request->get('record');
        if(empty($record)) {
            $recordModel = Settings_HolidayManager_Record_Model::getInstanceById($request->get('id'));
            if(empty($recordModel)) {
				$recordModel = new Settings_HolidayManager_Record_Model();
			}
		} else {
            $recordModel = Settings_HolidayManager_Record_Model::getInstanceById($record);
        }
        
        $fieldList = array('holidayname','date',);
        
        foreach ($fieldList as $fieldName) {
            if($request->has($fieldName)) {
                $recordModel->set($fieldName,$request->get($fieldName));
            }
        }
        $response = new Vtiger_Response();
        try{
            $id = $recordModel->save();
            $recordModel = Settings_HolidayManager_Record_Model::getInstance($id);
            $response->setResult(array_merge($recordModel->getData(),array('record'=> $recordModel->getId())));
        }catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}