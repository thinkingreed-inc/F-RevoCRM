<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_LayoutEditor_Field_Action extends Settings_Vtiger_Index_Action {

    function __construct() {
		parent::__construct();
        $this->exposeMethod('add');
        $this->exposeMethod('save');
        $this->exposeMethod('delete');
        $this->exposeMethod('move');
        $this->exposeMethod('unHide');
		$this->exposeMethod('updateDuplicateHandling');
    }

    public function add(Vtiger_Request $request) {
        global $custom_field_limit;
        $type = $request->get('fieldType');
        $moduleName = $request->get('sourceModule');
        $blockId = $request->get('blockid');
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName($moduleName);
        $response = new Vtiger_Response();
        try{
            $cf_count = $this->getCustomFieldsCount($moduleName);
            if (isset($custom_field_limit) && isset($cf_count) && ($cf_count >= $custom_field_limit)) {
                throw new Exception(vtranslate('LBL_CUSTOM_FIELD_LIMIT'));
            }

            $fieldModel = $moduleModel->addField($type,$blockId,$request->getAll());
            $fieldInfo = $fieldModel->getFieldInfo();
            $responseData = array_merge(array('id'=>$fieldModel->getId(), 'blockid'=>$blockId, 'customField'=>$fieldModel->isCustomField()),$fieldInfo);

			$defaultValue = $fieldModel->get('defaultvalue');
			$responseData['fieldDefaultValueRaw'] = $defaultValue;
			if (isset($defaultValue)) {
				if ($defaultValue && $fieldInfo['type'] == 'date') {
					$defaultValue = DateTimeField::convertToUserFormat($defaultValue);
				} else if (!$defaultValue) {
					$defaultValue = $fieldModel->getDisplayValue($defaultValue);
				} else if (is_array($defaultValue)) {
					foreach ($defaultValue as $key => $value) {
						$defaultValue[$key] = $fieldModel->getDisplayValue($value);
					}
					$defaultValue = Zend_Json::encode($defaultValue);
				}
			}
			$responseData['fieldDefaultValue'] = $defaultValue;
            $responseData['sequence'] = $fieldModel->get('sequence');

            $response->setResult($responseData);
        }catch(Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }

    private function getCustomFieldsCount($moduleName) {
        global $adb;
        $query = "SELECT COUNT(generatedtype) AS cf_count  FROM vtiger_field INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid WHERE vtiger_tab.name = ? && vtiger_field.generatedtype = 2";
        $result = $adb->pquery($query, array($moduleName));
        return $adb->query_result($result, 0, "cf_count");
    }

    public function save(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
        $fieldId = $request->get('fieldid');
        $fieldInstance = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
        
        $fieldLabel = $fieldInstance->get('label');
        $uitype = $fieldInstance->get('uitype');
        $mandatory = $request->get('mandatory',null);
        $presence = $request->get('presence',null);
        $quickCreate = $request->get('quickcreate',null);
        $summaryField = $request->get('summaryfield',null);
        $massEditable = $request->get('masseditable',null);
        $headerField = $request->get('headerfield',null);

		if (!$fieldLabel) {
			$fieldInstance->set('label', $fieldLabel);
		}
		if(!empty($mandatory)){
            $fieldInstance->updateTypeofDataFromMandatory($mandatory);
        }
        if(!empty($presence)){
            $fieldInstance->set('presence', $presence);
        }
        
        if(!empty($quickCreate)){
            $fieldInstance->set('quickcreate', $quickCreate);
        }
        
        if(isset($summaryField) && $summaryField != null){
            $fieldInstance->set('summaryfield', $summaryField);
        }
        
        if(isset($headerField) && $headerField != null){
            $fieldInstance->set('headerfield', $headerField);
        }
        
        if(!empty($massEditable)){
            $fieldInstance->set('masseditable', $massEditable);
        }
        
        if($uitype == 33){
            $defaultValue = decode_html(implode(' |##| ', $request->get('fieldDefaultValue')));
        }else{
            $defaultValue = decode_html($request->get('fieldDefaultValue'));
        }
		$fieldInstance->set('defaultvalue', $defaultValue);
		$response = new Vtiger_Response();
        try{
            $fieldInstance->save();
			$fieldInstance = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
			$fieldLabel = decode_html($request->get('fieldLabel'));
			$fieldInfo = $fieldInstance->getFieldInfo();
			$fieldInfo['id'] = $fieldInstance->getId();

			$fieldInfo['fieldDefaultValueRaw'] = $defaultValue;
			if (isset($defaultValue)) {
				if ($defaultValue && $fieldInfo['type'] == 'date') {
					$defaultValue = DateTimeField::convertToUserFormat($defaultValue);
				} else if (!$defaultValue) {
					$defaultValue = $fieldInstance->getDisplayValue($defaultValue);
				} else if (is_array($defaultValue)) {
					foreach ($defaultValue as $key => $value) {
						$defaultValue[$key] = $fieldInstance->getDisplayValue($value);
					}
					$defaultValue = Zend_Json::encode($defaultValue);
				}
			}
			$fieldInfo['fieldDefaultValue'] = $defaultValue;

            $response->setResult(array_merge(array('success'=>true), $fieldInfo));
        }catch(Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

    public function delete(Vtiger_Request $request) {
        $fieldId = $request->get('fieldid');
        $fieldInstance = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
        $response = new Vtiger_Response();

        if(!$fieldInstance->isCustomField()) {
            $response->setError('122', 'Cannot delete Non custom field');
            $response->emit();
            return;
        }

        $block = $fieldInstance->get('block');
        $blockId = $block->id;
        $sourceModule = $block->module->name;
        $preSequence = $fieldInstance->get('sequence');
        $targetTable = $fieldInstance->get('table');
        try{
            $this->_deleteField($fieldInstance);
            // 空白項目に置き換える場合
            if($request->get('isReplaceBlankColumn') === "true"){
                $blankFieldArray = $this->replaceBlankColumn($blockId, $sourceModule, $preSequence, $targetTable);
                $response->setResult($blankFieldArray);
            }
        }catch(Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();
    }
    
    private function _deleteField($fieldInstance) {
        $sourceModule = $fieldInstance->get('block')->module->name;
        $fieldLabel = $fieldInstance->get('label');
        if($fieldInstance->uitype == 16 || $fieldInstance->uitype == 33){
            $pickListValues = Settings_Picklist_Field_Model::getEditablePicklistValues ($fieldInstance->name);
            $fieldLabel = array_merge(array($fieldLabel),$pickListValues);
        }
        $fieldInstance->delete();
//        Settings_LayoutEditor_Module_Model::removeLabelFromLangFile($sourceModule, $fieldLabel);
        //we should delete any update field workflow associated with custom field
        $moduleName = $fieldInstance->getModule()->getName();
        Settings_Workflows_Record_Model::deleteUpadateFieldWorkflow($moduleName, $fieldInstance->getFieldName());
    }

    public function move(Vtiger_Request $request) {
        $updatedFieldsList = $request->get('updatedFields');
        
        // for Clearing cache we need Module Model
        $sourceModule = $request->get('selectedModule');
        $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
        
		//This will update the fields sequence for the updated blocks
        Settings_LayoutEditor_Block_Model::updateFieldSequenceNumber($updatedFieldsList,$moduleModel);
        
        $response = new Vtiger_Response();
		$response->setResult(array('success'=>true));
        $response->emit();
    }

    public function unHide(Vtiger_Request $request) {
        $response = new Vtiger_Response();
        try{
			$fieldIds = $request->get('fieldIdList');
            Settings_LayoutEditor_Field_Model::makeFieldActive($fieldIds, $request->get('blockId'),$request->get('selectedModule'));
			$responseData = array();
			foreach($fieldIds as $fieldId) {
				$fieldModel = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
				$fieldInfo = $fieldModel->getFieldInfo();
				$responseData[] = array_merge(array('id'=>$fieldModel->getId(), 'blockid'=>$fieldModel->get('block')->id, 'customField'=>$fieldModel->isCustomField()),$fieldInfo);
			}
            $response->setResult($responseData);
        }catch(Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->emit();

    }

	public function updateDuplicateHandling(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			$sourceModule = $request->get('sourceModule');
			$moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName($sourceModule);

			$fieldIdsList = $request->get('fieldIdsList');
			$result = $moduleModel->updateDuplicateHandling($request->get('rule'), $fieldIdsList, $request->get('syncActionId'));

			$response->setResult($result);
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }

    // 削除した項目の場所に空白項目を置き換える
    private function replaceBlankColumn($blockId, $sourceModule, $preSequence, $targetTable)
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName($sourceModule);
        $max_fieldid = $moduleModel->getSequenceNumber() + 1;
        if (empty($max_fieldid)) {
            global $adb;
            $max_fieldid = $adb->getUniqueID("vtiger_field");
        }
        $columnName = 'cf_' . $max_fieldid;

        $blockInstance = Vtiger_Block::getInstance($blockId);

        $blankField = new Vtiger_Field_Model();
        $blankField->name = $columnName;
        $blankField->label = '';
        $blankField->table = $targetTable;
        $blankField->uitype = 999;
        $blankField->typeofdata = 'V~O';
        $blankField->displaytype = 1;
        $blankField->defaultvalue = "";
        $blankField->sequence = $preSequence;
        $blankField->readonly = 1;
        $blankField->presence = 2;
        $blankField->quickcreate = 1;
        $blankField->masseditable = 2;
        $blankField->summaryfield = 0;
        $blankField->generatedtype = 2;

        $blockInstance->addField($blankField);


        // 追加した空白項目の情報を取得
        $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($blankField->getId());

        $fieldInfo = $fieldModel->getFieldInfo();
        $responseData = array_merge(array('id' => $fieldModel->getId(), 'blockid' => $blockId, 'customField' => $fieldModel->isCustomField()), $fieldInfo);

        $defaultValue = $fieldModel->get('defaultvalue');
        $responseData['fieldDefaultValueRaw'] = $defaultValue;
        if (isset($defaultValue)) {
            if ($defaultValue && $fieldInfo['type'] == 'date') {
                $defaultValue = DateTimeField::convertToUserFormat($defaultValue);
            } else if (!$defaultValue) {
                $defaultValue = $fieldModel->getDisplayValue($defaultValue);
            } else if (is_array($defaultValue)) {
                foreach ($defaultValue as $key => $value) {
                    $defaultValue[$key] = $fieldModel->getDisplayValue($value);
                }
                $defaultValue = Zend_Json::encode($defaultValue);
            }
        }
        $responseData['fieldDefaultValue'] = $defaultValue;
        $responseData['blankFieldName'] = $blankField->getFieldName();
        $responseData['blankFieldId'] = $blankField->getId();

        return $responseData;
    }
}