<?php

/**
 * 通知ブロックの項目設定（作成時に送信・メール文面の設定）を使えるかの判定
 */
class Settings_NotificationBlock_Permission_Helper
{
    /**
     * システム管理者でなければ止める
     */
    public static function checkAdmin(): void
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser || !$currentUser->isAdminUser()) {
            throw new ApiForbiddenException(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_PERMISSION_DENIED')));
        }
    }
}
