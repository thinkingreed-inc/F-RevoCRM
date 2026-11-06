<?php

/**
 * ダッシュボードのウィジェット「共有リンク」
 *
 * 管理者が設定画面（設定 > 共有リンク）で登録したリンクのうち、
 * 見ているユーザーが公開範囲に含まれるものを表示する。
 * 仕様: docs/specs/shared_links_widget.md
 */
class Home_SharedLinks_Dashboard extends Vtiger_IndexAjax_View
{
    /**
     * @param Vtiger_Widget_Model|null $widget ShowWidget から渡される。追加した直後はタイトルを持たないため使わない
     */
    public function process(Vtiger_Request $request, $widget = null): void
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();

        // 見出しにタイトルを出すため、登録済みの内容（vtiger_links の linklabel など）を読み直す
        $widget = Vtiger_Widget_Model::getInstance($request->get('linkid'), $currentUser->getId());

        $links = [];
        foreach (Settings_SharedLinks_Module_Model::getVisibleLinksForCurrentUser() as $link) {
            // POST の送信内容と別ウィンドウの設定は、押したときに JS が読む
            $link['options_json'] = json_encode([
                'id' => $link['id'],
                'method' => $link['method'],
                'url' => $link['url'],
                'params' => $link['params'],
                'openMode' => $link['open_mode'],
                'width' => $link['window_width'],
                'height' => $link['window_height'],
                'noreferrer' => $link['noreferrer'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $links[] = $link;
        }

        $viewer->assign('WIDGET', $widget);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('SHARED_LINKS', $links);
        $viewer->assign('IS_ADMIN', $currentUser->isAdminUser());

        if (!empty($request->get('content'))) {
            $viewer->view('dashboards/SharedLinksContents.tpl', $moduleName);
        } else {
            $viewer->view('dashboards/SharedLinks.tpl', $moduleName);
        }
    }
}
