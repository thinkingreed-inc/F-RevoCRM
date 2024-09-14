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
    private $newEmptyColumnData;

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

            $response->setResult($responseData);
        }catch(Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $this->newEmptyColumnData = $responseData; 
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

        try{
            $this->_deleteField($fieldInstance);
            // 空白項目に置き換える場合
            if($request->get('isReplaceEmptyColumn') === "true"){
                $this->replaceEmptyColumn($blockId, $sourceModule, $preSequence);
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
    private function replaceEmptyColumn($blockId, $sourceModule, $preSequence) {
        $values = array("fieldid" => "","addToBaseTable" => "","source" => "","fieldname" => "","blockid" => "","fieldType" => "Empty","fieldLabel" => "","fieldLength" => "NaN","decimal" => "","pickListValues" => "","fieldDefaultValue" => "","presence" => "2","mandatory" => "O","quickcreate" => "1","isquickcreatesupported" => "1","summaryfield" => "0","headerfield" => "0","masseditable" => "2","module" => "LayoutEditor","parent" => "Settings","action" => "Field","mode" => "add","sourceModule" => "Faq",);
        $values['blockid'] = $blockId;
        $values['sourceModule'] = $sourceModule;
        $rawvalues = $values;
        $emptyRequest = new Vtiger_Request($values, $rawvalues);

        // 空白項目を追加
        $this->add($emptyRequest);
        $responceData = $this->newEmptyColumnData;
        $newfieldid = $responceData['id'];
        $newBlockid = $responceData['blockid'];

        // 全フィールドリストを取得
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName($sourceModule);
        $fieldList = $moduleModel->getFields();
        if(!is_countable($fieldList)) {
            return ;
        }

        // 並び順を調査
        $isMatch = false;
        for ($i=0; $i < count($fieldList); $i++) {
            $fieldid = $fieldList[$i]->get('id');
            $sequence = $fieldList[$i]->get('sequence');
            $blockid = $fieldList[$i]->get('block')->id;

            if($newBlockid !== $blockid){
                continue ;
            }

            // 削除した項目より前にある項目のidもしくは重複する並び順を取得
            if($preSequence > $sequence){
                $previousId = $fieldid;
            }else if($preSequence === $sequence){
                $previousId = $fieldid;
                $isMatch = true;
            }
        }

        // フィールドリストの並び替え
        $updatedFieldsList = array();
        $isNext = false;
        $count = count($fieldList);
        // 置き換える項目の直前までループして項目を挿入する
        for ($i=0; $i < $count; $i++) {
            $fieldid = $fieldList[$i]->get('id');
            if($fieldid === $previousId && $isMatch === false){
                $isNext = true;
            }else if($fieldid === $previousId && $isMatch === true || $isNext === true){
                $fieldid = $newfieldid;
                $sequence = $preSequence;
                $blockid = $newBlockid;
                $updatedFieldsList[$i] = array('fieldid' => $fieldid, 'sequence' => $sequence, 'block' => $blockid);
                break ;
            }
            $fieldid = $fieldList[$i]->get('id');
            $sequence = $fieldList[$i]->get('sequence');
            $blockid = $fieldList[$i]->get('block')->id;
            $updatedFieldsList[$i] = array('fieldid' => $fieldid, 'sequence' => $sequence, 'block' => $blockid);
            unset($fieldList[$i]);
        }

        // 残りのフィールドと合わせる
        if(!is_countable($fieldList)) {
            return ;
        }
        foreach ($fieldList as $key => $value) {
            $fieldid = $fieldList[$key]->get('id');
            $sequence = $fieldList[$key]->get('sequence');
            $blockid = $fieldList[$key]->get('block')->id;
            array_push($updatedFieldsList,array('fieldid' => $fieldid, 'sequence' => $sequence, 'block' => $blockid));
        }

        // 並び替えの登録
        $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
        Settings_LayoutEditor_Block_Model::updateFieldSequenceNumber($updatedFieldsList,$moduleModel);
        $response = new Vtiger_Response();
        $response->setResult(array('success'=>true));
    }
}