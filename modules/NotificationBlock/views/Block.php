<?php

/**
 * 詳細画面・概要画面の通知ブロックの中身（送信したあと・通知先を変えたあとに読み直す）
 *
 * GET module=NotificationBlock&view=Block&record=..
 */
class NotificationBlock_Block_View extends NotificationBlock_Base_View
{
    public function process(Vtiger_Request $request): void
    {
        $viewer = $this->getViewer($request);
        $viewer->assign('NB', NotificationBlock_Display_Helper::getDetailData($this->sourceModule, $this->recordId));
        $viewer->view('BlockContents.tpl', NotificationBlock_Config_Model::MODULE_NAME);
    }
}
