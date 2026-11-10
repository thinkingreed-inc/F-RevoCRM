<?php

/**
 * 送信履歴の「すべて表示」と、宛先の一覧（仕様 動作定義 No.10）
 *
 * GET module=NotificationBlock&view=History&record=..                         … すべての送信履歴（新しい順）
 * GET module=NotificationBlock&view=History&record=..&mode=recipients&historyid=.. … 宛先の一覧
 */
class NotificationBlock_History_View extends NotificationBlock_Base_View
{
    public function process(Vtiger_Request $request): void
    {
        $module = NotificationBlock_Config_Model::MODULE_NAME;
        $viewer = $this->getViewer($request);
        $viewer->assign('NB_RECORD_ID', $this->recordId);
        if (NotificationBlock_Util_Helper::toStr($request->get('mode')) === 'recipients') {
            $historyId = NotificationBlock_Util_Helper::toInt($request->get('historyid'));
            $history = NotificationBlock_History_Model::getById($historyId);
            // ほかのレコードの送信履歴は出さない
            if ($history === null || $history['crmid'] !== $this->recordId) {
                throw new AppException(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RECORD_NOT_FOUND', $module)));
            }
            $viewer->assign('NB_HISTORY', $history);
            $viewer->assign('NB_RECIPIENTS', NotificationBlock_History_Model::getRecipients($historyId));
            $viewer->assign('NB_TITLE', vtranslate('LBL_RECIPIENT_LIST_TITLE', $module));
            $viewer->view('RecipientList.tpl', $module);
            return;
        }
        $viewer->assign('NB_HISTORIES', NotificationBlock_History_Model::getAll($this->recordId));
        $viewer->assign('NB_TITLE', vtranslate('LBL_HISTORY_TITLE', $module));
        $viewer->view('HistoryDialog.tpl', $module);
    }
}
