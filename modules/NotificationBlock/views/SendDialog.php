<?php

/**
 * 送信ダイアログ（仕様 動作定義 No.7・No.9・No.22）
 *
 * 保存済みの通知先から展開した宛先（ユーザー名と人数）、除いた人数と理由、コメントの入力欄を出す。
 * 宛先が 0 人・送信中・編集権限なしのときは「送信」を押せない。
 *
 * GET module=NotificationBlock&view=SendDialog&record=..
 */
class NotificationBlock_SendDialog_View extends NotificationBlock_Base_View
{
    public function process(Vtiger_Request $request): void
    {
        $module = NotificationBlock_Config_Model::MODULE_NAME;
        $error = '';
        $preview = ['recipients' => [], 'excluded' => ['disabled' => 0, 'no_email' => 0]];
        if (!NotificationBlock_Config_Model::hasBlock($this->sourceModule)) {
            $error = NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_BLOCK', $module));
        } elseif (!Users_Privileges_Model::isPermitted($this->sourceModule, 'EditView', $this->recordId)) {
            $error = NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_PERMISSION', $module));
        } else {
            $preview = NotificationBlock_Notifier_Model::preview($this->recordId);
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('NB_RECORD_ID', $this->recordId);
        $viewer->assign('NB_ERROR', $error);
        $viewer->assign('NB_RECIPIENTS', $preview['recipients']);
        $viewer->assign('NB_EXCLUDED', $preview['excluded']);
        $viewer->assign('NB_IS_SENDING', NotificationBlock_History_Model::isSending($this->recordId));
        $viewer->assign('NB_COMMENT_MAX_LENGTH', NotificationBlock_Notifier_Model::COMMENT_MAX_LENGTH);
        $viewer->assign('NB_TITLE', vtranslate('LBL_SEND_DIALOG_TITLE', $module));
        $viewer->view('SendDialog.tpl', $module);
    }
}
