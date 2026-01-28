<?php
/*+**********************************************************************************
 * SearchRecords API - 参照フィールド用レコード検索
 * WebComponents QuickCreate用
 *
 * パラメータ:
 *   - module: 検索対象モジュール名（必須）
 *   - search: 簡易検索キーワード（オプション）
 *   - search_fields: フィールド条件検索（JSON形式、オプション）
 *     例: {"accountname":"テスト","industry":"IT"}
 *   - include_fields: trueの場合、検索可能フィールド情報を含める
 *   - limit: 取得件数（デフォルト20）
 ************************************************************************************/

class Vtiger_SearchRecords_Api extends Vtiger_Api_Controller {

    function loginRequired() {
        return true;
    }

    function requiresPermission(Vtiger_Request $request) {
        return array(
            array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => null)
        );
    }

    function checkPermission(Vtiger_Request $request) {
        parent::checkPermission($request);
        return true;
    }

    /**
     * レコード検索処理
     */
    protected function processApi(Vtiger_Request $request) {
        try {
            $moduleName = $request->get('module');
            $searchValue = $request->get('search', '');
            $includeFields = $request->get('include_fields', '');
            $includeFields = ($includeFields === '1' || $includeFields === 'true' || $includeFields === true);
            $limit = (int)$request->get('limit', 20);

            // limit範囲チェック（1〜100）
            if ($limit < 1) {
                $limit = 1;
            } elseif ($limit > 100) {
                $limit = 100;
            }

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

            $records = array();
            $searchFields = array();

            // 表示用フィールド情報を先に取得
            $displayFields = array();
            if ($includeFields) {
                $displayFields = $this->getSearchableFields($moduleModel);
            }

            // フィールド条件検索
            $searchFieldsParam = $request->get('search_fields', '');
            if (!empty($searchFieldsParam)) {
                // 配列として渡された場合はそのまま使用、文字列ならJSONデコード
                if (is_array($searchFieldsParam)) {
                    $searchFields = $searchFieldsParam;
                } else {
                    $searchFields = json_decode($searchFieldsParam, true);
                }
                if (is_array($searchFields) && !empty($searchFields)) {
                    $records = $this->searchByFields($moduleName, $searchFields, $limit, $displayFields);
                }
            }
            // 簡易キーワード検索
            else if (!empty($searchValue)) {
                $matchingRecords = Vtiger_Record_Model::getSearchResult($searchValue, $moduleName, $limit);

                if (!empty($matchingRecords[$moduleName])) {
                    foreach ($matchingRecords[$moduleName] as $recordId => $recordModel) {
                        if (is_object($recordModel)) {
                            $record = array(
                                'id' => $recordId,
                                'label' => decode_html($recordModel->get('label')),
                                'module' => $moduleName
                            );
                            // フィールド値を追加
                            if (!empty($displayFields)) {
                                $record['fieldValues'] = $this->getRecordFieldValues($recordId, $moduleName, $displayFields);
                            }
                            $records[] = $record;
                        }
                    }
                }
            }
            // キーワードなしの場合は最近のレコード
            else {
                $records = $this->getRecentRecords($moduleName, $limit, $displayFields);
            }

            $result = array(
                'module' => $moduleName,
                'search' => $searchValue,
                'searchFields' => $searchFields,
                'totalRecords' => count($records),
                'records' => $records,
                'timestamp' => date('Y-m-d H:i:s')
            );

            // フィールド情報を含める
            if ($includeFields) {
                $result['fields'] = $this->getSearchableFields($moduleModel);
            }

            return $this->sendSuccess($result);

        } catch (Exception $e) {
            return $this->sendError('Search failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * フィールド条件でレコード検索（QueryGenerator使用）
     */
    private function searchByFields($moduleName, $searchFields, $limit, $displayFields = array()) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);

        // 基本フィールドを設定
        $queryGenerator->setFields(array('id'));

        // 検索条件を追加
        $first = true;
        foreach ($searchFields as $fieldName => $fieldValue) {
            if (empty($fieldValue)) continue;

            // フィールドの存在確認
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, Vtiger_Module_Model::getInstance($moduleName));
            if (!$fieldModel) continue;

            $fieldType = $fieldModel->getFieldDataType();

            // フィールドタイプに応じた検索条件
            if (in_array($fieldType, array('picklist', 'multipicklist', 'owner'))) {
                // 完全一致
                $queryGenerator->addCondition($fieldName, $fieldValue, 'e', $first ? '' : 'AND');
            } else {
                // 部分一致
                $queryGenerator->addCondition($fieldName, $fieldValue, 'c', $first ? '' : 'AND');
            }
            $first = false;
        }

        // クエリ実行
        $query = $queryGenerator->getQuery();
        $query .= " LIMIT " . (int)$limit;

        global $adb;
        $queryResult = $adb->pquery($query, array());

        // モジュールのIDフィールド名を取得
        $focus = CRMEntity::getInstance($moduleName);
        $idColumn = $focus->table_index ?? 'crmid';

        $records = array();
        while ($row = $adb->fetch_array($queryResult)) {
            $recordId = $row['crmid'] ?? $row[$idColumn] ?? $row['id'] ?? null;
            if (!$recordId) continue;

            // 権限チェック
            if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
                continue;
            }

            // ラベル取得
            $label = $this->getRecordLabel($recordId);

            $record = array(
                'id' => $recordId,
                'label' => $label,
                'module' => $moduleName
            );

            // フィールド値を追加
            if (!empty($displayFields)) {
                $record['fieldValues'] = $this->getRecordFieldValues($recordId, $moduleName, $displayFields);
            }

            $records[] = $record;
        }

        return $records;
    }

    /**
     * レコードラベルを取得
     */
    private function getRecordLabel($recordId) {
        global $adb;
        $result = $adb->pquery(
            "SELECT label FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0",
            array($recordId)
        );
        if ($adb->num_rows($result) > 0) {
            return decode_html($adb->query_result($result, 0, 'label'));
        }
        return '';
    }

    /**
     * レコードの各フィールド値を取得
     */
    private function getRecordFieldValues($recordId, $moduleName, $displayFields) {
        $fieldValues = array();

        try {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            if (!$recordModel) {
                return $fieldValues;
            }

            foreach ($displayFields as $field) {
                $fieldName = $field['name'];
                $rawValue = $recordModel->get($fieldName);
                $displayValue = '';

                // フィールドタイプに応じた表示値の取得
                $fieldType = $field['type'];

                if ($fieldType === 'owner') {
                    // 担当者フィールド
                    if (!empty($rawValue)) {
                        $displayValue = Vtiger_Util_Helper::getOwnerName($rawValue);
                    }
                } elseif (in_array($fieldType, array('picklist', 'multipicklist'))) {
                    // ピックリスト - ラベルを取得
                    $displayValue = decode_html($rawValue);
                } elseif ($fieldType === 'reference') {
                    // 参照フィールド
                    if (!empty($rawValue)) {
                        $displayValue = $this->getRecordLabel($rawValue);
                    }
                } else {
                    // その他のフィールド
                    $displayValue = decode_html($rawValue);
                }

                $fieldValues[$fieldName] = $displayValue;
            }
        } catch (Exception $e) {
            // エラー時は空配列を返す
        }

        return $fieldValues;
    }

    /**
     * 検索可能なフィールド情報を取得
     */
    private function getSearchableFields($moduleModel) {
        $fields = array();
        $moduleFields = $moduleModel->getFields();

        // 検索に適したフィールドタイプ
        $searchableTypes = array(
            'string', 'text', 'email', 'phone', 'url',
            'picklist', 'multipicklist',
            'date', 'datetime',
            'integer', 'double', 'currency',
            'owner'
        );

        foreach ($moduleFields as $fieldName => $fieldModel) {
            // 非表示フィールドはスキップ
            if (!$fieldModel->isViewEnabled() || !$fieldModel->isActiveField()) {
                continue;
            }

            $fieldType = $fieldModel->getFieldDataType();

            // 検索可能なタイプのみ
            if (!in_array($fieldType, $searchableTypes)) {
                continue;
            }

            $fieldInfo = array(
                'name' => $fieldName,
                'label' => vtranslate($fieldModel->get('label'), $moduleModel->getName()),
                'type' => $fieldType,
                'uitype' => $fieldModel->get('uitype'),
                'mandatory' => $fieldModel->isMandatory()
            );

            // ピックリストの場合は選択肢を含める
            if (in_array($fieldType, array('picklist', 'multipicklist'))) {
                $picklistValues = $fieldModel->getPicklistValues();
                if ($picklistValues) {
                    $fieldInfo['picklistValues'] = array();
                    foreach ($picklistValues as $value => $label) {
                        $fieldInfo['picklistValues'][] = array(
                            'value' => $value,
                            'label' => $label
                        );
                    }
                }
            }

            // 担当者フィールドの場合
            if ($fieldType === 'owner') {
                $fieldInfo['ownerOptions'] = $this->getOwnerOptions();
            }

            $fields[] = $fieldInfo;
        }

        return $fields;
    }

    /**
     * 担当者オプションを取得
     */
    private function getOwnerOptions() {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $users = Users_Record_Model::getActiveAdminUsers();
        $groups = Settings_Groups_Record_Model::getAll();

        $options = array(
            'users' => array(),
            'groups' => array()
        );

        foreach ($users as $userId => $userModel) {
            $options['users'][] = array(
                'value' => $userId,
                'label' => $userModel->getName()
            );
        }

        foreach ($groups as $groupId => $groupModel) {
            $options['groups'][] = array(
                'value' => $groupId,
                'label' => $groupModel->getName()
            );
        }

        return $options;
    }

    /**
     * 最近のレコードを取得（vtiger_crmentity.labelを使用）
     */
    private function getRecentRecords($moduleName, $limit, $displayFields = array()) {
        global $adb;

        $records = array();

        $query = "SELECT crmid, label
                  FROM vtiger_crmentity
                  WHERE setype = ? AND deleted = 0 AND label != ''
                  ORDER BY modifiedtime DESC
                  LIMIT ?";

        $result = $adb->pquery($query, array($moduleName, $limit));

        while ($row = $adb->fetch_array($result)) {
            // 権限チェック
            if (Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $row['crmid'])) {
                $record = array(
                    'id' => $row['crmid'],
                    'label' => decode_html($row['label']),
                    'module' => $moduleName
                );

                // フィールド値を追加
                if (!empty($displayFields)) {
                    $record['fieldValues'] = $this->getRecordFieldValues($row['crmid'], $moduleName, $displayFields);
                }

                $records[] = $record;
            }
        }

        return $records;
    }
}
