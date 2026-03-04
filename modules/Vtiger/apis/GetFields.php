<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Vtiger_GetFields_Api extends Vtiger_Api_Controller {

    function loginRequired() {
        // WebComponents開発用だが、セキュリティのためログインは必要
        return true;
    }

    function requiresPermission(Vtiger_Request $request) {
        // GetRecordと同様の権限要求（レコードIDはnullでも可）
        return array(
            array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => null)
        );
    }

    function checkPermission(Vtiger_Request $request) {
        // デバッグ情報をログに出力
        global $log;
        if ($log) {
            $log->info("GetFields checkPermission: module=" . $request->getModule());
        }
        
        // 先に親クラスの権限チェック（ログインチェック含む）を実行
        parent::checkPermission($request);
        
        return true;
    }

    /**
     * モジュールのフィールド情報を取得するメイン処理
     */
    protected function processApi(Vtiger_Request $request) {
        try {
            $moduleName = $request->get('module');
            if (empty($moduleName)) {
                throw new Exception('Module name is required');
            }

            // モジュール名のバリデーション（英数字とアンダースコアのみ許可）
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $moduleName)) {
                throw new Exception('Invalid module name format');
            }

            // モジュールモデルの取得
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            if (empty($moduleModel)) {
                throw new Exception("Module '$moduleName' not found");
            }

            // RecordTypeパラメータの取得
            $recordTypeFields = $request->get('recordtype_fields');
            $recordFieldIdList = $request->get('recordfieldidlist');
            $includeRecordTypeInfo = $request->get('include_recordtype_info', false);
            
            // RecordTypeフィールド値が文字列の場合はJSON解析
            if (is_string($recordTypeFields) && !empty($recordTypeFields)) {
                $recordTypeFields = json_decode($recordTypeFields, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON format in recordtype_fields parameter');
                }
            }
            
            // RecordFieldIDリストが文字列の場合はJSON解析
            if (is_string($recordFieldIdList) && !empty($recordFieldIdList)) {
                $recordFieldIdList = json_decode($recordFieldIdList, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON format in recordfieldidlist parameter');
                }
            }

            // RecordFieldIDリストの計算
            if (empty($recordFieldIdList) && !empty($recordTypeFields)) {
                $recordFieldIdList = $this->calculateRecordFieldIdList($moduleName, $recordTypeFields);
            }

            // レコードモデルの作成（空のレコード）
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            
            // RecordTypeフィールド値を設定（フィルタリングに必要）
            if (!empty($recordTypeFields)) {
                foreach ($recordTypeFields as $fieldName => $fieldValue) {
                    $recordModel->set($fieldName, $fieldValue);
                }
            }
            
            // Edit用のレコード構造を取得
            $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel(
                $recordModel, 
                Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT
            );
            
            // フィールド構造の取得（RecordType対応）
            if (!empty($recordFieldIdList)) {
                $recordStructure = $recordStructureInstance->getStructure($recordFieldIdList);
            } else {
                $recordStructure = $recordStructureInstance->getStructure();
            }
            
            // API用のフィールド情報に変換
            $apiFields = array();

            foreach ($recordStructure as $blockLabel => $blockFields) {
                foreach ($blockFields as $fieldModel) {
                    $fieldInfo = $this->formatFieldInfo($fieldModel, $moduleName, $blockLabel);
                    if ($fieldInfo) {
                        $apiFields[] = $fieldInfo;
                    }
                }
            }

            // viewパラメータによるフィルタリング
            $viewMode = $request->get('view', 'edit');
            if ($viewMode === 'quickcreate') {
                // QuickCreate対象フィールドのみフィルタリング
                $apiFields = array_filter($apiFields, function($field) {
                    return $field['quickcreate'] === true;
                });

                // quickcreatesequenceでソート
                usort($apiFields, function($a, $b) {
                    $seqA = isset($a['quickcreatesequence']) ? (int)$a['quickcreatesequence'] : PHP_INT_MAX;
                    $seqB = isset($b['quickcreatesequence']) ? (int)$b['quickcreatesequence'] : PHP_INT_MAX;
                    return $seqA - $seqB;
                });

                // array_filterの結果をリインデックス
                $apiFields = array_values($apiFields);
            }

            // モジュールの翻訳されたラベルを取得（SINGLE_ModuleName形式）
            $moduleLabel = vtranslate('SINGLE_' . $moduleName, $moduleName);
            // 翻訳が見つからない場合（SINGLE_XXXがそのまま返される場合）はモジュール名を使用
            if ($moduleLabel === 'SINGLE_' . $moduleName) {
                $moduleLabel = vtranslate($moduleName, $moduleName);
            }

            // レスポンス構築
            $result = array(
                'module' => $moduleName,
                'moduleLabel' => $moduleLabel,
                'totalFields' => count($apiFields),
                'fields' => $apiFields,
                'timestamp' => date('Y-m-d H:i:s')
            );
            
            // RecordType情報を含める場合
            if ($includeRecordTypeInfo) {
                $recordTypeInfo = array(
                    'available' => $this->getRecordTypeInfo($moduleName),
                    'applied' => $recordTypeFields ? $recordTypeFields : new stdClass(),
                    'recordFieldIdList' => $recordFieldIdList ? $recordFieldIdList : array()
                );
                $result['recordTypeInfo'] = $recordTypeInfo;
            }

            // PickListDependency情報を追加（ピックリストフィールドが存在する場合のみ）
            $picklistDependency = $this->getPicklistDependency($moduleName);
            if (!empty($picklistDependency)) {
                $result['picklistDependency'] = $picklistDependency;
            }

            return $this->sendSuccess($result);
            
        } catch (Exception $e) {
            error_log("GetFields API Error: " . $e->getMessage());
            return $this->sendError('Failed to retrieve fields: ' . $e->getMessage(), 500);
        }
    }

    /**
     * RecordType情報を取得する
     * Note: RecordType機能は本リポジトリでは未実装のため空配列を返す
     */
    private function getRecordTypeInfo($moduleName) {
        // RecordType機能が必要な場合は、Vtiger_Module_Model::getRecordTypeList()を実装する
        return array();
    }

    /**
     * RecordTypeフィールド値からRecordFieldIDリストを計算
     * Note: RecordType機能は本リポジトリでは未実装のため空配列を返す
     */
    private function calculateRecordFieldIdList($moduleName, $recordTypeFields) {
        // RecordType機能が必要な場合は、Vtiger_Module_Model::getRecordTypeList()を実装する
        return array();

        /* Original implementation (requires getRecordTypeList):
        try {
            if (empty($recordTypeFields) || !is_array($recordTypeFields)) {
                return array();
            }

            $recordFieldIdList = array();
            $recordTypeList = Vtiger_Module_Model::getRecordTypeList($moduleName);
            
            foreach ($recordTypeList as $recordType) {
                $fieldName = $recordType['fieldname'];
                $pickValue = $recordType['pickvalue'];
                $recordFieldId = $recordType['recordfieldid'];
                
                // RecordTypeフィールドの値と一致するかチェック
                if (isset($recordTypeFields[$fieldName]) && 
                    $recordTypeFields[$fieldName] == $pickValue) {
                    
                    if (!in_array($recordFieldId, $recordFieldIdList)) {
                        $recordFieldIdList[] = $recordFieldId;
                    }
                }
            }

            return $recordFieldIdList;

        } catch (Exception $e) {
            error_log("RecordFieldId calculation error: " . $e->getMessage());
            return array();
        }
        */
    }

    /**
     * フィールドモデルをAPI用の情報に変換
     */
    private function formatFieldInfo($fieldModel, $moduleName, $blockLabel) {
        try {
            $fieldName = $fieldModel->getFieldName();
            $uitype = $fieldModel->get('uitype');
            
            // 基本情報
            $fieldInfo = array(
                'name' => $fieldName,
                'label' => vtranslate($fieldModel->get('label'), $moduleName),
                'uitype' => $uitype,
                'type' => $fieldModel->getFieldDataType(),
                'mandatory' => $fieldModel->isMandatory(),
                'readonly' => $fieldModel->isReadOnly(),
                'editable' => $fieldModel->isEditable(),
                'displaytype' => $fieldModel->get('displaytype'),
                'block' => vtranslate($blockLabel, $moduleName)
            );

            // 最大文字数
            $maxlength = $fieldModel->getMaxFieldLength();
            if (!empty($maxlength)) {
                $fieldInfo['maxlength'] = $maxlength;
            }

            // デフォルト値の取得
            $defaultValue = $fieldModel->get('defaultvalue');
            if ($defaultValue !== null && $defaultValue !== '') {
                $fieldInfo['defaultValue'] = $defaultValue;
            }

            // バリデーション情報
            $fieldInfoDetails = $fieldModel->getFieldInfo();
            if (!empty($fieldInfoDetails)) {
                $fieldInfo['fieldinfo'] = $fieldInfoDetails;
            }

            // ピックリスト値（該当するUITypeの場合）
            if (in_array($uitype, array('15', '16', '33'))) {
                try {
                    $picklistValues = $fieldModel->getPicklistValues();
                    if (!empty($picklistValues)) {
                        $formattedPicklist = array();
                        foreach ($picklistValues as $value => $label) {
                            $formattedPicklist[] = array(
                                'value' => $value,
                                'label' => $label
                            );
                        }
                        $fieldInfo['picklistValues'] = $formattedPicklist;
                    }
                } catch (Exception $e) {
                    // ピックリスト取得エラーはログに記録するが、フィールド情報は返す
                    error_log("Picklist error for field $fieldName: " . $e->getMessage());
                }
            }

            // 参照フィールドの場合
            // isReferenceField() は 'reference' タイプのみ対応するため、
            // 'multireference' タイプ（UIType 57等）も含めてチェック
            $isReference = $fieldModel->isReferenceField() ||
                           $fieldModel->getFieldDataType() === 'multireference';

            if ($isReference) {
                $referenceList = $fieldModel->getReferenceList();

                // UIType 57 (Contact参照) の場合、参照リストが空でも Contacts を設定
                if (empty($referenceList) && $uitype === '57') {
                    $referenceList = array('Contacts');
                }

                if (!empty($referenceList)) {
                    $fieldInfo['referenceModules'] = $referenceList;
                    // 翻訳されたモジュール名も含める
                    $translatedModules = array();
                    foreach ($referenceList as $refModule) {
                        $translatedLabel = vtranslate('SINGLE_' . $refModule, $refModule);
                        // 翻訳が見つからない場合はモジュール名をそのまま使用
                        if ($translatedLabel === 'SINGLE_' . $refModule) {
                            $translatedLabel = vtranslate($refModule, $refModule);
                        }
                        $translatedModules[$refModule] = $translatedLabel;
                    }
                    $fieldInfo['referenceModuleLabels'] = $translatedModules;
                }

                // datatype を追加
                $fieldDataType = $fieldModel->getFieldDataType();
                $fieldInfo['datatype'] = $fieldDataType;

                // multireference の場合は isMultiple フラグを追加
                if ($fieldDataType === 'multireference') {
                    $fieldInfo['isMultiple'] = true;
                }
            }

            // 通貨フィールドの場合
            if (in_array($uitype, array('71', '72'))) {
                // 通貨記号は別途取得する必要がある場合のみ実装
                $fieldInfo['isCurrency'] = true;
            }

            // QuickCreate情報
            $fieldInfo['quickcreate'] = $fieldModel->isQuickCreateEnabled();
            $fieldInfo['quickcreatesequence'] = $fieldModel->get('quickcreatesequence');

            // CustomValidation情報を追加
            $customValidations = $this->getCustomValidationsForField($fieldModel->getId());
            if (!empty($customValidations)) {
                $fieldInfo['customValidations'] = $customValidations;
            }

            return $fieldInfo;
            
        } catch (Exception $e) {
            error_log("Field formatting error for {$fieldModel->getFieldName()}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * フィールドに紐づくCustomValidation情報を取得
     * @param int $fieldId フィールドID
     * @return array バリデーション情報の配列
     */
    private function getCustomValidationsForField($fieldId) {
        if (empty($fieldId)) {
            return array();
        }

        try {
            // Settings_CustomValidation_Module_Modelを使用
            if (class_exists('Settings_CustomValidation_Module_Model')) {
                return Settings_CustomValidation_Module_Model::getValidationInfoForApi($fieldId);
            }
            return array();
        } catch (Exception $e) {
            error_log("CustomValidation retrieval error for field $fieldId: " . $e->getMessage());
            return array();
        }
    }

    /**
     * モジュールのPickListDependency情報を取得
     * @param string $moduleName モジュール名
     * @return array 連動設定データソース
     */
    private function getPicklistDependency($moduleName) {
        try {
            // DependentPickListUtilsを読み込み
            require_once 'modules/PickList/DependentPickListUtils.php';

            // 連動設定データを取得
            $picklistDependency = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

            return $picklistDependency;
        } catch (Exception $e) {
            error_log("PicklistDependency retrieval error for module $moduleName: " . $e->getMessage());
            return array();
        }
    }
}