<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_GetRecord_Api extends Vtiger_Api_Controller {

    function loginRequired() {
        // WebComponents開発用だが、セキュリティのためログインは必要
        return true;
    }

    function requiresPermission(Vtiger_Request $request) {
        // レコードアクセス権限をチェック
        return array(
            array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record')
        );
    }

    function checkPermission(Vtiger_Request $request) {
        parent::checkPermission($request);
        $moduleName = $request->getModule();
        $recordId = $request->get('record');

        // デバッグ情報をログに出力（本番環境ではDEBUGレベル）
        global $log;
        if ($log) {
            $log->debug("GetRecord checkPermission: module=$moduleName, record=$recordId");
        }

        $nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates', 'PDFTemplates');
        if ($recordId && !in_array($moduleName, $nonEntityModules)) {
            $recordEntityName = getSalesEntityType($recordId);
            
            // デバッグ情報をログに出力（本番環境ではDEBUGレベル）
            if ($log) {
                $log->debug("GetRecord permission check: recordEntityName=$recordEntityName, moduleName=$moduleName");
            }
            
            if ($recordEntityName !== $moduleName) {
                throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
            }
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request) {
        $recordId = $request->get('record');
        $sourceModule = $request->get('module');

        // recordIdの数値バリデーション
        if (empty($recordId) || !is_numeric($recordId) || (int)$recordId <= 0) {
            return $this->sendError('Invalid Record ID', 400);
        }
        $recordId = (int)$recordId;

        if (empty($sourceModule)) {
            return $this->sendError('Source module is required', 400);
        }

        try {
            // モジュール指定でレコードを取得
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $sourceModule);

            if (!$recordModel) {
                return $this->sendError('Record not found', 404);
            }

            $data = $recordModel->getData();

            // 機密フィールドを除外
            $sensitiveFields = array('user_password', 'confirm_password', 'accesskey', 'crypt_type', 'user_hash');
            $data = array_diff_key($data, array_flip($sensitiveFields));

            // HTMLをデコード
            $decodedData = array_map('decode_html', $data);

            // 追加情報も含める
            $result = array(
                'record' => $decodedData,
                'displayValues' => array(),
                'module' => $sourceModule,
                'recordId' => $recordId,
                'timestamp' => date('Y-m-d H:i:s')
            );

            return $this->sendSuccess($result);

        } catch (Exception $e) {
            // ログに詳細情報を記録（本番環境では露出させない）
            global $log;
            if ($log) {
                $log->error("GetRecord API Error: " . $e->getMessage());
                $log->error("Stack trace: " . $e->getTraceAsString());
                $log->error("RecordId: " . $recordId);
            }

            // APIレスポンスには詳細を含めない（セキュリティ対策）
            return $this->sendError(vtranslate('LBL_FAILED_TO_RETRIEVE_RECORD'), 500);
        }
    }
}