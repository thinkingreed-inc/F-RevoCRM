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
    /** 目印の色の数（SharedLinksWidget.tsx の BADGE_COLORS） */
    private const BADGE_COLORS = 8;

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
        // どのウィジェット（リンクのまとまり）かは linkid で見分ける。見出しは vtiger_links.linklabel
        $set = Settings_SharedLinks_Module_Model::getSetByLinkId((int) $request->get('linkid'));
        $visibleLinks = $set !== null ? Settings_SharedLinks_Module_Model::getVisibleLinksForCurrentUser($set['id']) : [];

        // <shared-links-widget>（React）に渡すリンク
        $links = [];
        foreach ($visibleLinks as $link) {
            $links[] = [
                'id' => $link['id'],
                'title' => $link['title'],
                'description' => $link['description'],
                'method' => $link['method'],
                'url' => $link['url'],
                'params' => $link['params'],
                'openMode' => $link['open_mode'],
                'width' => $link['window_width'],
                'height' => $link['window_height'],
                'noreferrer' => $link['noreferrer'],
                'blocked' => $link['post_blocked'],
                // 左に出す目印: タイトルの頭文字と、リンクごとに決まる色
                'initial' => mb_strtoupper(mb_substr($link['title'], 0, 1)),
                'color' => $link['id'] % self::BADGE_COLORS,
            ];
        }
        $labels = [
            'empty' => vtranslate('LBL_NO_SHARED_LINKS', $moduleName),
            'settings' => vtranslate('LBL_SHARED_LINKS_SETTINGS', $moduleName),
            'openInNewTab' => vtranslate('LBL_OPEN_IN_NEW_TAB', $moduleName),
            'openInNewWindow' => vtranslate('LBL_OPEN_IN_NEW_WINDOW', $moduleName),
            'popupBlocked' => vtranslate('LBL_SHARED_LINK_POPUP_BLOCKED', $moduleName),
            'postBlocked' => vtranslate('LBL_SHARED_LINK_POST_BLOCKED', $moduleName),
        ];

        $viewer->assign('WIDGET', $widget);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('SHARED_LINKS_JSON', json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $viewer->assign('SHARED_LINKS_LABELS_JSON', json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $viewer->assign('IS_ADMIN', $currentUser->isAdminUser());

        if (!empty($request->get('content'))) {
            $viewer->view('dashboards/SharedLinksContents.tpl', $moduleName);
        } else {
            $viewer->view('dashboards/SharedLinks.tpl', $moduleName);
        }
    }
}
