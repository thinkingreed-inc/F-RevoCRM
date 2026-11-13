<?php

/**
 * 送信履歴のダイアログと、その中で切り替えて出す宛先の一覧
 *
 * GET module=NotificationBlock&view=History&record=..
 *     … 送信履歴のダイアログ（すべての送信履歴を新しい順に。通知ブロックの「送信履歴（N 件）」から開く）
 * GET module=NotificationBlock&view=History&record=..&mode=recipient_content&historyid=..
 *     … 宛先の一覧の中身だけ（送信履歴のダイアログの中で切り替えて出す。ダイアログを重ねて開かないため）
 */
class NotificationBlock_History_View extends NotificationBlock_Base_View
{
    public function process(Vtiger_Request $request): void
    {
        $module = NotificationBlock_Config_Model::MODULE_NAME;
        $viewer = $this->getViewer($request);
        $viewer->assign('NB_RECORD_ID', $this->recordId);
        $mode = NotificationBlock_Util_Helper::toStr($request->get('mode'));
        if ($mode === 'recipient_content') {
            $historyId = NotificationBlock_Util_Helper::toInt($request->get('historyid'));
            $history = NotificationBlock_History_Model::getById($historyId);
            // ほかのレコードの送信履歴は出さない
            if ($history === null || $history['crmid'] !== $this->recordId) {
                throw new AppException(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RECORD_NOT_FOUND', $module)));
            }
            $viewer->assign('NB_HISTORY', $history);
            $viewer->assign('NB_RECIPIENTS', NotificationBlock_History_Model::getRecipients($historyId));
            $viewer->view('RecipientContent.tpl', $module);
            return;
        }
        $viewer->assign('NB_HISTORIES', NotificationBlock_History_Model::getAll($this->recordId));
        $viewer->assign('NB_TITLE', vtranslate('LBL_HISTORY_TITLE', $module));
        $viewer->view('HistoryDialog.tpl', $module);
    }
}
