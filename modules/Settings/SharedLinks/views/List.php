<?php

/**
 * 共有リンクの管理画面
 *
 * 画面本体は Web コンポーネント（<shared-links-page>）で描画し、
 * データの取得・更新は SharedLinkAPI（JSON）を使用する。
 * 設定画面配下のためアクセスできるのはシステム管理者のみ。
 */
class Settings_SharedLinks_List_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request): void
    {
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);

        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('SharedLinksList.tpl', $qualifiedModuleName);
    }

    public function validateRequest(Vtiger_Request $request): bool
    {
        return true;
    }
}
