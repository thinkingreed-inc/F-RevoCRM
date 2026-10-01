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
 *   - value / description は未送信の場合に既存値を維持する。
 *     シークレット変数は GetRecord が値を返さないため、値欄に触れずに保存された
 *     場合に既存値を破壊しないようにするための仕様。
 *   - secret を 1 から 0 へ戻す場合は value の再送信を必須にする。
 *     未入力のまま解除できると、秘匿していた値をそのまま画面へ露出させられるため。
 *   - boolean 型は secret を設定できない。値が true / false の 2 択しかなく、
 *     マスクしても秘匿にならないため。
 */
class Settings_Parameters_Save_Api extends Vtiger_Api_Controller {

    /**
     * ログイン必須
     *
     * @return bool
     */
    function loginRequired() {
        return true;
    }

    /**
     * 権限チェック
     *
     * @param Vtiger_Request $request
     * @return bool
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
     *
     * @param Vtiger_Request $request
     * @return void
     */
    protected function processApi(Vtiger_Request $request) {
        // システム変数編集画面保存時のJSONレスポンス統一対応
        header('Content-Type: application/json; charset=utf-8');
        try {
            // CSRFトークン検証
            $request->validateWriteAccess();

            $id = $request->get('id');
            $secret = $request->get('secret');

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

            // 変更前のシークレット状態。解除の判定に使う
            $originalSecret = $recordModel->getSecret();

            // シークレットフラグの処理（0↔1 どちらにも変更可能）
            $requestedSecret = null;
            if ($secret !== null && $secret !== '') {
                $requestedSecret = (int)$secret ? 1 : 0;
            }

            // boolean 型は値が true / false の 2 択しかなく、マスクしても値を推測できるため
            // シークレットを許可しない。不整合なデータが残っていても保存時に解消する。
            if ($recordModel->getType() === 'boolean') {
                if ($requestedSecret === 1) {
                    echo json_encode([
                        'success' => false,
                        'error' => ['message' => 'Secret is not available for boolean parameters']
                    ]);
                    return;
                }
                $requestedSecret = 0;
            }

            if ($requestedSecret !== null) {
                $recordModel->set('secret', $requestedSecret);
            }

            // シークレットを解除する場合は値の再入力を必須にする。
            // 未入力のまま解除できると、秘匿していた値をそのまま画面へ露出させられてしまう。
            // boolean はシークレット自体を許可しないため対象外（不整合の解消を妨げない）。
            if ($originalSecret === 1 && $requestedSecret === 0 && $recordModel->getType() !== 'boolean') {
                $hasNewValue = $request->has('value') && (string)$request->get('value') !== '';
                if (!$hasNewValue) {
                    echo json_encode([
                        'success' => false,
                        'error' => ['message' => 'A new value is required to turn off the secret setting']
                    ]);
                    return;
                }
            }

            // 値は value が送信された場合のみ更新する。
            // シークレット変数は GetRecord が値を返さないため、値欄に触れずに保存されたときに
            // 既存値を空文字で上書きしてしまわないよう、value 未送信＝変更なしとして扱う。
            if ($request->has('value')) {
                // バリデーションエラー（ApiBadRequestException）は最外の catch でそのまま返す
                $recordModel->set('value', $this->validateValue($request->get('value'), $recordModel->getType()));
            }

            // 備考も value と同様、送信された場合のみ更新する
            if ($request->has('description')) {
                $description = $request->get('description');
                $recordModel->set('description', is_scalar($description) ? (string)$description : '');
            }

            $recordModel->save();

            // 正常系レスポンス（既存構造維持）
            echo json_encode(['success' => true]);
        } catch (ApiException $e) {
            // 入力値の誤りなど、利用者に伝えるべきエラーはそのまま返す
            echo json_encode([
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ]);
        } catch (Exception $e) {
            // 予期しないエラーの詳細（SQL エラー等）はクライアントへ返さず、内部ログにのみ出す
            error_log('Parameters Save API Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => ['message' => 'Failed to save the parameter']
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
        // 配列やオブジェクトが送られた場合は文字列化できないため弾く
        if ($value !== null && !is_scalar($value)) {
            throw new ApiBadRequestException('Invalid value type');
        }
        $stringValue = $value === null ? '' : (string)$value;

        switch ($type) {
            case 'boolean':
                // true/false、1/0、yes/no を受け付ける
                $lowerValue = strtolower($stringValue);
                if (in_array($lowerValue, array('true', '1', 'yes', 'on'), true)) {
                    return 'true';
                } else if (in_array($lowerValue, array('false', '0', 'no', 'off', ''), true)) {
                    return 'false';
                }
                throw new ApiBadRequestException('Invalid boolean value. Use true/false, 1/0, yes/no');
                
            case 'integer':
                // 空文字は0として扱う
                if ($stringValue === '') {
                    return '0';
                }
                if (!is_numeric($stringValue)) {
                    throw new ApiBadRequestException('Invalid integer value');
                }
                return (string)(int)$stringValue;
                
            case 'string':
            default:
                // 512文字制限（フロント側の maxLength と単位を揃える）
                if (mb_strlen($stringValue) > 512) {
                    throw new ApiBadRequestException('Value exceeds maximum length (512 characters)');
                }
                return $stringValue;
        }
    }
}
