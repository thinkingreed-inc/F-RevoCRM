<?php

/**
 * 通知メールの文面の設定ダイアログ（項目設定の通知ブロックの「メール文面の設定」）
 *
 * 開いたときは、保存済みの文面（未設定なら既定の文面）を入れて出す。
 * 管理者だけが開ける（Settings_Vtiger_Index_View::checkPermission()）。
 */
class Settings_NotificationBlock_TemplateDialog_View extends Settings_Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request): void
    {
        $tabId = NotificationBlock_Util_Helper::toInt($request->get('tabid'));
        $moduleName = NotificationBlock_Util_Helper::toStr(getTabModuleName($tabId));
        if (!NotificationBlock_Config_Model::isSupportedModule($moduleName)) {
            throw new AppException(NotificationBlock_Util_Helper::toStr(
                vtranslate('LBL_NOTIFICATION_BLOCK_NOT_SUPPORTED', NotificationBlock_Config_Model::SETTINGS_MODULE)
            ));
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('TAB_ID', $tabId);
        $viewer->assign('SOURCE_MODULE_LABEL', vtranslate($moduleName, $moduleName));
        $viewer->assign('TEMPLATES', NotificationBlock_Template_Model::getForDialog($tabId));
        $viewer->assign('PLACEHOLDERS', NotificationBlock_Template_Model::getPlaceholderList());
        $viewer->assign('SUBJECT_MAX_LENGTH', NotificationBlock_Template_Model::SUBJECT_MAX_LENGTH);
        $viewer->assign('QUALIFIED_MODULE', NotificationBlock_Config_Model::SETTINGS_MODULE);
        $viewer->view('TemplateDialog.tpl', NotificationBlock_Config_Model::SETTINGS_MODULE);
    }
}
