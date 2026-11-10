<?php

/**
 * 通知ブロックの「作成時に送信」を保存する（JSON。項目設定の切り替えから、その場で保存する）
 *
 * POST tabid=..&enabled=1|0
 *   成功：{"enabled": true|false, "message": 画面に出すメッセージ}
 */
class Settings_NotificationBlock_SaveSendOnCreate_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * 設定を書き換えるので、POST と CSRF トークンも確かめる
     */
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    /**
     * 設定できるのは項目設定を使える管理者だけ（仕様 項目設定 4-3 No.2）
     */
    public function checkPermission(Vtiger_Request $request): bool
    {
        Settings_NotificationBlock_Permission_Helper::checkAdmin();
        return true;
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $tabId = NotificationBlock_Util_Helper::toInt($request->get('tabid'));
        $moduleName = NotificationBlock_Util_Helper::toStr(getTabModuleName($tabId));
        if (!NotificationBlock_Config_Model::hasBlock($moduleName)) {
            $this->sendError(NotificationBlock_Util_Helper::toStr(
                vtranslate('LBL_RESULT_NO_BLOCK', NotificationBlock_Config_Model::MODULE_NAME)
            ), 400);
        }
        $enabled = NotificationBlock_Util_Helper::toStr($request->get('enabled')) === '1';
        NotificationBlock_Config_Model::saveSendOnCreate($tabId, $enabled);
        return $this->sendSuccess([
            'enabled' => $enabled,
            'message' => NotificationBlock_Util_Helper::toStr(vtranslate('LBL_SETTING_SAVED', NotificationBlock_Config_Model::SETTINGS_MODULE)),
        ]);
    }
}
