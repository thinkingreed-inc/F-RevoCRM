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
 *   - secret: シークレットフラグ（オプション。設定できる条件は Note を参照）
 *   - description: 備考
 * 
 * Response:
 *   { "saved": true }
 *   エラー時は Vtiger_Response が { "success": false, "error": {...} } を返す
 * 
 * Note:
 *   - key, type の変更は受け付けない（valueのみ更新可能）
 *   - value / description は未送信の場合に既存値を維持する。
 *     シークレット変数は GetRecord が値を返さないため、値欄に触れずに保存された
 *     場合に既存値を破壊しないようにするための仕様。
 *   - シークレットのまま value を空文字で送った場合も既存値を維持する。
 *     値欄へ入力してから消した場合にフロントが空文字を送ることがあり、
 *     上書きすると一覧はマスク表示のままで値の破壊に気づけないため。
 *   - secret を 1 から 0 へ戻す場合は value の再送信を必須にする。
 *     未入力のまま解除できると、秘匿していた値をそのまま画面へ露出させられるため。
 *   - boolean 型は secret を設定できない。値が true / false の 2 択しかなく、
 *     マスクしても秘匿にならないため。
 *   - value / description は Vtiger_Request::getRaw() で受け取る。
 *     get() は vtlib_purify() で HTML 特殊文字を実体参照へ変換し、`{` `[` で始まる値を
 *     JSON としてデコードしてしまうため、トークンのような任意の文字列を保存できない。
 *     値の検証は validateValue() が型・最大長・スカラー判定で行う。
 *     保存値をそのまま HTML へ出力する箇所（一覧）では表示側でエスケープすること。
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
     * @return Vtiger_Response
     */
    protected function processApi(Vtiger_Request $request) {
        // CSRFトークン検証
        $request->validateWriteAccess();

        $id = $request->get('id');
        $secret = $request->get('secret');

        // IDのバリデーション
        if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
            throw new ApiBadRequestException('Invalid ID');
        }
        $id = (int)$id;

        // レコード取得
        $recordModel = Settings_Parameters_Record_Model::getInstanceById($id);
        if (!$recordModel->getId()) {
            throw new ApiNotFoundException('Record not found');
        }

        // 変更前のシークレット状態。解除の判定に使う
        $originalSecret = $recordModel->getSecret();

        // value / description は get() ではなく getRaw() で受け取る。
        // get() は vtlib_purify() で HTML 特殊文字を実体参照へ変換し、`{` `[` で始まる値を
        // JSON へデコードしてしまうため、入力した文字列をそのまま保存できない。
        $hasValueParam = $request->has('value');
        $rawValue = $hasValueParam ? $request->getRaw('value') : null;
        $isEmptyValue = is_scalar($rawValue) && (string)$rawValue === '';

        // シークレットフラグの処理（0↔1 どちらにも変更可能）
        $requestedSecret = null;
        if ($secret !== null && $secret !== '') {
            $requestedSecret = (int)$secret ? 1 : 0;
        }

        // boolean 型は値が true / false の 2 択しかなく、マスクしても値を推測できるため
        // シークレットを許可しない。不整合なデータが残っていても保存時に解消する。
        if ($recordModel->getType() === 'boolean') {
            if ($requestedSecret === 1) {
                throw new ApiBadRequestException('Secret is not available for boolean parameters');
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
            $hasNewValue = $hasValueParam && !$isEmptyValue;
            if (!$hasNewValue) {
                throw new ApiBadRequestException('A new value is required to turn off the secret setting');
            }
        }

        // シークレットのままの保存では、空文字を「未入力」として扱い既存値を維持する。
        // 値欄へ一度入力してから消した場合にフロントが空文字を送ることがあり、
        // 上書きしてしまうと一覧はマスク表示のままで値の破壊に気づけない。
        // 値を空にしたい場合はシークレットを解除してから保存する。
        $keepsSecret = ($originalSecret === 1 && $requestedSecret !== 0);

        // 値は value が送信された場合のみ更新する。
        // シークレット変数は GetRecord が値を返さないため、値欄に触れずに保存されたときに
        // 既存値を空文字で上書きしてしまわないよう、value 未送信＝変更なしとして扱う。
        if ($hasValueParam && !($keepsSecret && $isEmptyValue)) {
            $recordModel->set('value', $this->validateValue($rawValue, $recordModel->getType()));
        }

        // 備考も value と同様、送信された場合のみ更新する
        if ($request->has('description')) {
            $description = $request->getRaw('description');
            $recordModel->set('description', is_scalar($description) ? (string)$description : '');
        }

        try {
            $recordModel->save();
        } catch (Exception $e) {
            // 予期しないエラーの詳細（SQL エラー等）はクライアントへ返さず、内部ログにのみ出す
            error_log('Parameters Save API Error: ' . $e->getMessage());
            return $this->sendError('Failed to save the parameter', 500);
        }

        return $this->sendSuccess(['saved' => true]);
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
