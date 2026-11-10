<?php

/**
 * 通知メールの文面を保存する（JSON。項目設定の「メール文面の設定」から）
 *
 * POST tabid=..&templates={"ja_jp": {"subject": .., "body": .., "use_default": "0|1"}, "en_us": {..}}
 *   成功：{"message": 画面に出すメッセージ}
 */
class Settings_NotificationBlock_SaveTemplate_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    /**
     * 設定できるのはシステム管理者だけ（仕様 項目設定 4-4 No.1）
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
        if (!NotificationBlock_Config_Model::isSupportedModule($moduleName)) {
            $this->sendError(NotificationBlock_Util_Helper::toStr(
                vtranslate('LBL_NOTIFICATION_BLOCK_NOT_SUPPORTED', NotificationBlock_Config_Model::SETTINGS_MODULE)
            ), 400);
        }
        // 本文は HTML なので、入力された文字のまま受け取る（保存の前に script とイベントの属性は取り除く）
        $templates = NotificationBlock_Util_Helper::toArray(json_decode(NotificationBlock_Util_Helper::toStr($request->getRaw('templates')), true));
        foreach ($templates as $template) {
            $subject = NotificationBlock_Util_Helper::toStr(NotificationBlock_Util_Helper::toArray($template)['subject'] ?? '');
            if (mb_strlen(trim($subject)) > NotificationBlock_Template_Model::SUBJECT_MAX_LENGTH) {
                $this->sendError(NotificationBlock_Util_Helper::toStr(vtranslate(
                    'LBL_SUBJECT_TOO_LONG',
                    NotificationBlock_Config_Model::SETTINGS_MODULE,
                    NotificationBlock_Template_Model::SUBJECT_MAX_LENGTH
                )), 400);
            }
        }
        NotificationBlock_Template_Model::save($tabId, $templates);
        return $this->sendSuccess([
            'message' => NotificationBlock_Util_Helper::toStr(vtranslate('LBL_TEMPLATE_SAVED', NotificationBlock_Config_Model::SETTINGS_MODULE)),
        ]);
    }
}
