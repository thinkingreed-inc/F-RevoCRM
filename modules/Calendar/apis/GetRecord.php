<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Calendar専用 GetRecord API
 *
 * 共有カレンダーでの他者の活動へのアクセスを許可するため、
 * isCalendarPermittedBySharing / isToDoPermittedBySharing を使用した
 * 特殊な権限チェックを行う。
 */
class Calendar_GetRecord_Api extends Vtiger_GetRecord_Api {

    /**
     * 権限チェック設定
     *
     * 親クラスの requiresPermission は使用せず、
     * checkPermission で独自の権限チェックを行う。
     */
    function requiresPermission(Vtiger_Request $request) {
        // 空の配列を返す（権限チェックはcheckPermissionで独自に行う）
        return array();
    }

    /**
     * 権限チェック
     *
     * 1. 基本権限チェック（EditView）
     * 2. 共有カレンダー権限チェック（isCalendarPermittedBySharing / isToDoPermittedBySharing）
     */
    function checkPermission(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $recordId = $request->get('record');

        // recordIdが空の場合はエラー
        if (empty($recordId)) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        // 1. 基本権限チェック（EditView）
        // Calendar/Events モジュールでの編集権限があればOK
        if (Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId)) {
            return true;
        }

        // 2. 共有カレンダー権限チェック
        // 必要な関数をインクルード
        require_once('include/utils/UserInfoUtil.php');

        // 活動タイプを取得（Events または Task）
        $activityType = vtws_getCalendarEntityType($recordId);

        if ($activityType == 'Events') {
            // イベントの場合: isCalendarPermittedBySharing を使用
            $permission = isCalendarPermittedBySharing($recordId);
        } else {
            // タスク（ToDo）の場合: isToDoPermittedBySharing を使用
            $permission = isToDoPermittedBySharing($recordId);
        }

        if (strtolower($permission) == 'yes') {
            return true;
        }

        // 権限なし
        throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
    }

    /**
     * API処理
     *
     * Events の場合は招待者情報を追加してレスポンスを返す
     */
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

            // Calendar/Events モジュールの場合、繰り返し活動情報を追加
            if (($sourceModule === 'Calendar' || $sourceModule === 'Events') && method_exists($recordModel, 'getRecurrenceInformation')) {
                $recurringInfo = $recordModel->getRecurrenceInformation();
                $data['recurringcheck'] = isset($recurringInfo['recurringcheck']) ? $recurringInfo['recurringcheck'] : 'No';
            }

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

            // Events の場合は招待者情報を追加
            if ($sourceModule === 'Events' && method_exists($recordModel, 'getInvities')) {
                $inviteeIds = $recordModel->getInvities();
                $result['selectedusers'] = array_map('intval', $inviteeIds);

                // 招待者の詳細情報も追加（名前、ステータス）
                if (method_exists($recordModel, 'getInviteesDetails')) {
                    $inviteesDetails = $recordModel->getInviteesDetails();
                    $invitees = array();
                    foreach ($inviteesDetails as $userId => $status) {
                        $userName = decode_html(getUserFullName($userId));
                        $invitees[] = array(
                            'id' => (int)$userId,
                            'name' => $userName,
                            'status' => $status
                        );
                    }
                    $result['invitees'] = $invitees;
                }
            }

            return $this->sendSuccess($result);

        } catch (Exception $e) {
            global $log;
            if ($log) {
                $log->error("Calendar GetRecord API Error: " . $e->getMessage());
            }
            return $this->sendError(vtranslate('LBL_FAILED_TO_RETRIEVE_RECORD'), 500);
        }
    }
}
