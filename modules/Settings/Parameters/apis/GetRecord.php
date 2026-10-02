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
 *
 * Note:
 *   - key / value / description は DB に保存されたままの文字列を返す。
 *     PearDatabase::query_result() が to_html() で HTML エスケープした値を返すため、
 *     API 側で元へ戻してから JSON に載せる。
 *     エスケープしたまま返すと、編集ダイアログに `&amp;` のような実体参照が表示され、
 *     そのまま保存して二重エスケープが蓄積してしまう。
 *     JSON としての安全性は json_encode が担保し、表示側は React がエスケープする。
 */
class Settings_Parameters_GetRecord_Api extends Vtiger_Api_Controller {

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
            'key' => $this->decodeHtmlEntities($recordModel->getKey()),
            'value' => $recordModel->getSecret() ? '' : $this->decodeHtmlEntities($recordModel->getValue()),
            'type' => $recordModel->getType(),
            'secret' => (int)$recordModel->getSecret(),
            'description' => $this->decodeHtmlEntities($recordModel->getDescription())
        );

        return $this->sendSuccess($result);
    }

    /**
     * to_html() で変換された HTML 実体参照を元の文字列へ戻す
     *
     * @param mixed $value
     * @return mixed 文字列ならデコード結果、それ以外はそのまま
     */
    private function decodeHtmlEntities($value) {
        global $default_charset;

        if (!is_string($value)) {
            return $value;
        }

        $charset = empty($default_charset) ? 'UTF-8' : $default_charset;

        return html_entity_decode($value, ENT_QUOTES, $charset);
    }
}
