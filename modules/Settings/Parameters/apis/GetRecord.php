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
 * GetRecord API - システム変数の詳細取得
 * 
 * Parameters:
 *   - id: レコードID
 * 
 * Response:
 *   {
 *     "id": 1,
 *     "key": "FORCE_MULTI_FACTOR_AUTH",
 *     "value": "",  // secret=1の場合は空文字
 *     "type": "boolean",
 *     "secret": 1,
 *     "description": "多要素認証を強制するフラグです..."
 *   }
 */
class Settings_Parameters_GetRecord_Api extends Vtiger_Api_Controller {

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
        $id = $request->get('id');
        
        // IDのバリデーション
        if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
            throw new ApiBadRequestException('Invalid ID');
        }
        $id = (int)$id;
        
        // レコード取得
        $recordModel = Settings_Parameters_Record_Model::getInstanceById($id);
        
        if (!$recordModel || !$recordModel->getId()) {
            throw new ApiNotFoundException('Record not found');
        }
        
        // レスポンス構築
        $result = array(
            'id' => (int)$recordModel->getId(),
            'key' => $recordModel->getKey(),
            'value' => $recordModel->getSecret() ? '' : $recordModel->getValue(),
            'type' => $recordModel->getType(),
            'secret' => (int)$recordModel->getSecret(),
            'description' => $recordModel->getDescription()
        );
        
        return $this->sendSuccess($result);
    }
}
