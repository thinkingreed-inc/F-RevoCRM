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
}
