<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_LayoutEditor_Module_Action extends Settings_Vtiger_Index_Action {
    
    public function __construct() {
        $this->exposeMethod('updateEditReadonlyDisplay');
    }
    
    public function updateEditReadonlyDisplay(Vtiger_Request $request) {
        $response = new Vtiger_Response();
        try {
            $editReadonlyDisplay = $request->get('edit_readonly_display');
            $sourceModule = $request->get('sourceModule');

            // モジュールの存在チェック
            $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
            if (!$moduleModel) {
                throw new Exception('Invalid module: ' . $sourceModule);
            }

            $moduleModel->updateEditreadonlydisplay($editReadonlyDisplay);
            $response->setResult(array('success' => true));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}