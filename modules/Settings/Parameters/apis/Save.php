<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Save API - システム変数の値を更新
 * 
 * Parameters:
 *   - id: レコードID
 *   - value: 新しい値
 *   - secret: シークレットフラグ（オプション、0↔1 双方向変更可能）
 *   - description: 備考
 * 
 * Response:
 *   { "success": true }
 *   または
 *   { "success": false, "error": "エラーメッセージ" }
 * 
 * Note: 
 *   - key, type の変更は受け付けない（valueのみ更新可能）
 *   - secretは 0↔1 双方向変更可能
 *   - 空欄保存は常に空文字で上書き
 */
class Settings_Parameters_Save_Api extends Vtiger_Api_Controller {

    /**
     * ログイン必須
     */
    function loginRequired() {
        return true;
    }

    /**
     * 権限チェック
     */
    function checkPermission(Vtiger_Request $request) {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    /**
     * API処理
     */
    protected function processApi(Vtiger_Request $request) {
        // システム変数編集画面保存時のJSONレスポンス統一対応
        header('Content-Type: application/json; charset=utf-8');
        try {
            // CSRFトークン検証
            $request->validateWriteAccess();

            $id = $request->get('id');
            $value = $request->get('value');
            $secret = $request->get('secret');
            $description = $request->get('description');

            // IDのバリデーション
            if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
                echo json_encode([
                    'success' => false,
                    'error' => ['message' => 'Invalid ID']
                ]);
                return;
            }
            $id = (int)$id;

            // レコード取得
            $recordModel = Settings_Parameters_Record_Model::getInstanceById($id);
            if (!$recordModel->getId()) {
                echo json_encode([
                    'success' => false,
                    'error' => ['message' => 'Record not found']
                ]);
                return;
            }

            // シークレットフラグの処理（0↔1 どちらにも変更可能）
            if ($secret !== null && $secret !== '') {
                $recordModel->set('secret', (int)$secret ? 1 : 0);
            }

            // 型に応じたバリデーション（空文字もそのまま保存）
            try {
                $validatedValue = $this->validateValue($value, $recordModel->getType());
                $validatedDescription = (string)$description;
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => ['message' => $e->getMessage()]
                ]);
                return;
            }





            // 値を更新
            $recordModel->set('value', $validatedValue);

            // 備考を更新
            $recordModel->set('description', $validatedDescription);

            try {
                $recordModel->save();
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => ['message' => $e->getMessage()]
                ]);
                return;
            }

            // 正常系レスポンス（既存構造維持）
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ]);
        }
        return;
    }
    
    /**
     * 型に応じた値のバリデーション
     * 
     * @param mixed $value 入力値
     * @param string $type 型（boolean, integer, string）
     * @return string バリデーション済みの値
     * @throws ApiBadRequestException バリデーションエラー時
     */
    private function validateValue($value, $type) {
        switch ($type) {
            case 'boolean':
                // true/false、1/0、yes/no を受け付ける
                $lowerValue = strtolower((string)$value);
                if (in_array($lowerValue, array('true', '1', 'yes', 'on'), true)) {
                    return 'true';
                } else if (in_array($lowerValue, array('false', '0', 'no', 'off', ''), true)) {
                    return 'false';
                }
                throw new ApiBadRequestException('Invalid boolean value. Use true/false, 1/0, yes/no');
                
            case 'integer':
                // 空文字は0として扱う
                if ($value === '' || $value === null) {
                    return '0';
                }
                if (!is_numeric($value)) {
                    throw new ApiBadRequestException('Invalid integer value');
                }
                return (string)(int)$value;
                
            case 'string':
            default:
                // 512バイト制限
                if (strlen((string)$value) > 512) {
                    throw new ApiBadRequestException('Value exceeds maximum length (512 bytes)');
                }
                return (string)$value;
        }
    }
}
