<?php

declare(strict_types=1);

namespace Tests\Integration\Settings;

use Home_DashBoard_View;
use Home_SharedLinks_Dashboard;
use Settings_SharedLinks_Module_Model as LinkModel;
use Tests\Support\SharedLinksTestCase;
use Vtiger_DashBoard_Model;
use Vtiger_Request;
use Vtiger_Widget_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/SharedLinksTestCase.php';

/**
 * ダッシュボードのウィジェット「共有リンク」の描画の結合テスト（テスト用 DB を使う）
 *
 * テンプレート（Smarty）まで実際に通し、<shared-links-widget>（React）に渡す属性を確かめる。
 * 表示とリンクの開き方は Vitest（SharedLinksWidget.test.tsx / openSharedLink.test.ts）で確かめる。
 * テスト仕様書: docs/tests/SharedLinks/
 *   TS-02 公開範囲 / TS-04 リンクの開き方 / TS-07 ウィジェット / TS-08 ウィジェット（まとまり）
 */
final class SharedLinksWidgetTest extends SharedLinksTestCase
{
    private function linkIdOf(int $setId): int
    {
        $set = LinkModel::getSet($setId);
        $this->assertNotNull($set);
        return $set['linkid'];
    }

    /** ユーザーの既定のダッシュボードのタブにウィジェットを置く（ShowWidget と同じ手順） */
    private function placeWidget(int $userId, int $linkId): Vtiger_Widget_Model
    {
        $tab = Vtiger_DashBoard_Model::getInstance('Home')->getUserDefaultTab($userId);
        $widget = new Vtiger_Widget_Model();
        $widget->set('linkid', $linkId);
        $widget->set('userid', $userId);
        $widget->set('tabid', $tab['id']);
        $widget->add();
        return $widget;
    }

    private function render(int $userId, bool $contentOnly = false, ?int $setId = null): string
    {
        $linkId = $this->linkIdOf($setId ?? $this->setId);
        $this->loginAs($userId);
        $widget = $this->placeWidget($userId, $linkId);
        $values = ['module' => 'Home', 'view' => 'ShowWidget', 'name' => 'SharedLinks', 'linkid' => $linkId];
        if ($contentOnly) {
            $values['content'] = 'data';
        }
        ob_start();
        try {
            (new Home_SharedLinks_Dashboard())->process(new Vtiger_Request($values, $values), $widget);
        } finally {
            $html = (string) ob_get_clean();
            $this->db->pquery('DELETE FROM vtiger_module_dashboard_widgets WHERE linkid = ?', [$linkId]);
        }
        return $html;
    }

    /**
     * <shared-links-widget> の属性を取り出す
     *
     * @return array{links: list<array<string, mixed>>, labels: array<string, string>, settingsUrl: string|null}
     */
    private function widgetProps(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
        $elements = $dom->getElementsByTagName('shared-links-widget');
        $this->assertSame(1, $elements->length, '<shared-links-widget> が 1 つある');
        $element = $elements->item(0);
        $this->assertInstanceOf(\DOMElement::class, $element);
        $links = json_decode($element->getAttribute('links'), true);
        $labels = json_decode($element->getAttribute('labels'), true);
        $this->assertIsArray($links);
        $this->assertIsArray($labels);
        return [
            'links' => $links,
            'labels' => $labels,
            'settingsUrl' => $element->hasAttribute('settings-url') ? $element->getAttribute('settings-url') : null,
        ];
    }

    public function test_TC_WDG_001_002_TC_SET_010_ウィジェットごとに追加メニューに出て追加済みのタブでは出ない(): void
    {
        $this->createSet('申請');
        $tab = Vtiger_DashBoard_Model::getInstance('Home')->getUserDefaultTab(self::ADMIN_USER_ID);
        $selectable = function () use ($tab): array {
            $dashboard = Vtiger_DashBoard_Model::getInstance('Home');
            $dashboard->set('tabid', $tab['id']);
            return array_map(fn ($w) => $w->getTitle(), $dashboard->getSelectableDashboard());
        };

        $this->assertContains(self::PREFIX . 'set', $selectable());
        $this->assertContains(self::PREFIX . '申請', $selectable());

        $this->placeWidget(self::ADMIN_USER_ID, $this->linkIdOf($this->setId));
        try {
            $this->assertNotContains(self::PREFIX . 'set', $selectable());
            // 置いていないウィジェットは引き続き出る（同じタブに複数の共有リンクを置ける）
            $this->assertContains(self::PREFIX . '申請', $selectable());
        } finally {
            $this->db->pquery('DELETE FROM vtiger_module_dashboard_widgets WHERE linkid = ?', [$this->linkIdOf($this->setId)]);
        }
    }

    public function test_TC_WDG_003_014_TC_SET_011_見出しはウィジェットの見出しでフッターに更新と削除がある(): void
    {
        LinkModel::saveSet(self::PREFIX . '社内システム', $this->setId);

        $html = $this->render(self::ADMIN_USER_ID);

        $this->assertMatchesRegularExpression(
            '#<div class="dashboardTitle"[^>]*><b>' . preg_quote(self::PREFIX, '#') . '社内システム</b></div>#u',
            $html
        );
        $this->assertStringContainsString('name="drefresh"', $html);
        $this->assertStringContainsString('name="dclose"', $html);
    }

    public function test_TC_WDG_004_ダッシュボードで枠の調整のJSとCSSを読み込む(): void
    {
        $request = new Vtiger_Request(['module' => 'Home', 'view' => 'DashBoard']);
        $view = new Home_DashBoard_View();

        $scripts = $view->getHeaderScripts($request);
        $this->assertArrayHasKey('modules.Home.resources.SharedLinksWidget', $scripts);
        $this->assertFileExists(
            dirname(__DIR__, 3) . '/public/' . $scripts['modules.Home.resources.SharedLinksWidget']->get('src')
        );

        $hrefs = array_map(fn ($css) => (string) $css->get('href'), $view->getHeaderCss($request));
        $css = array_values(array_filter($hrefs, fn ($href) => str_contains($href, 'SharedLinksWidget.css')));
        $this->assertCount(1, $css);
        $this->assertFileExists(dirname(__DIR__, 3) . '/public/' . preg_replace('/\?.*$/', '', $css[0]));
    }

    public function test_TC_WDG_010_TC_SET_009_ウィジェットのリンクだけを並び順で渡す(): void
    {
        $a = $this->createLink(['title' => self::PREFIX . 'A', 'description' => "1行目\n2行目"]);
        $b = $this->createLink(['title' => self::PREFIX . 'B']);
        $c = $this->createLink(['title' => 'c小文字']);
        LinkModel::reorder([$c, $a, $b]);
        $otherSet = $this->createSet('別');
        LinkModel::saveLink(
            \Settings_SharedLinks_Rules_Model::normalize($this->linkInput(['title' => '別のウィジェット'])),
            $otherSet
        );

        $props = $this->widgetProps($this->render(self::ADMIN_USER_ID, true));

        $this->assertSame([$c, $a, $b], array_column($props['links'], 'id'));
        $this->assertSame("1行目\n2行目", $props['links'][1]['description']);
        // 目印: タイトルの頭文字（大文字にそろえる）と、リンクごとに決まる色
        $this->assertSame(['C', 'S', 'S'], array_column($props['links'], 'initial'));
        foreach ($props['links'] as $link) {
            $this->assertSame($link['id'] % 8, $link['color']);
        }
        $this->assertSame(
            ['empty', 'settings', 'openInNewTab', 'openInNewWindow', 'popupBlocked', 'postBlocked'],
            array_keys($props['labels'])
        );
        foreach ($props['labels'] as $key => $label) {
            $this->assertStringNotContainsString('LBL_', $label, $key);
        }
    }

    public function test_TC_WDG_011_0件なら空で管理者には設定画面のURLを渡す(): void
    {
        $props = $this->widgetProps($this->render(self::ADMIN_USER_ID, true));

        $this->assertSame([], $props['links']);
        $this->assertSame('index.php?module=SharedLinks&parent=Settings&view=List', $props['settingsUrl']);
        $this->assertSame('共有リンクが登録されていません', $props['labels']['empty']);
    }

    public function test_TC_WDG_012_管理者以外には設定画面のURLを渡さない(): void
    {
        $props = $this->widgetProps($this->render($this->nonAdminUserId(), true));

        $this->assertSame([], $props['links']);
        $this->assertNull($props['settingsUrl']);
    }

    public function test_TC_VIS_029_公開範囲外のリンクはURLもHTMLに出さない(): void
    {
        $this->createLink([
            'title' => self::PREFIX . 'secret',
            'url' => 'https://secret.example.com/hidden-path',
            'visibility' => 'targets',
            'targets' => [['type' => 'Users', 'id' => '999999']],
            'params' => [['name' => 'secret_param', 'value_type' => 'fixed', 'value' => 'secret-value']],
        ]);

        $html = $this->render(self::ADMIN_USER_ID, true);

        foreach (['secret.example.com', 'hidden-path', 'secret_param', 'secret-value', self::PREFIX . 'secret'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }

    public function test_TC_OPN_009_015_送信内容と開き方とリファラーの設定を渡しformを書き出さない(): void
    {
        $get = $this->createLink(['title' => self::PREFIX . 'get', 'noreferrer' => true]);
        $post = $this->createLink([
            'title' => self::PREFIX . 'post',
            'method' => 'POST',
            'open_mode' => 'window',
            'window_width' => 800,
            'window_height' => 600,
            'params' => [['name' => 'uid', 'value_type' => 'user', 'value' => 'id']],
        ]);

        // 送り先を CSP で許可していない状態にする（設定ファイルの中身に左右されないように）
        $saved = $GLOBALS['csp_allowed_domains'] ?? null;
        $GLOBALS['csp_allowed_domains'] = ['form-action' => []];
        try {
            $html = $this->render(self::ADMIN_USER_ID, true);
        } finally {
            $GLOBALS['csp_allowed_domains'] = $saved;
        }
        $byId = array_column($this->widgetProps($html)['links'], null, 'id');

        // TC-OPN-009: <form> を書き出さない（csrf-magic がトークンを埋め込むため）
        $this->assertStringNotContainsStringIgnoringCase('<form', $html);
        $this->assertSame([
            'id' => $get,
            'title' => self::PREFIX . 'get',
            'description' => '',
            'method' => 'GET',
            'url' => 'https://kintai.example.com/login?lang=ja',
            'params' => [],
            'openMode' => 'tab',
            'width' => null,
            'height' => null,
            'noreferrer' => true,
            'blocked' => false,
            'initial' => 'S',
            'color' => $get % 8,
        ], $byId[$get]);
        $this->assertSame([
            'method' => 'POST',
            'url' => 'https://kintai.example.com/login?lang=ja',
            'params' => [['name' => 'uid', 'value' => (string) self::ADMIN_USER_ID]],
            'openMode' => 'window',
            'width' => 800,
            'height' => 600,
            'noreferrer' => false,
            // 送り先を CSP で許可していないため、ウィジェットは開かずに通知する
            'blocked' => true,
        ], array_intersect_key(
            $byId[$post],
            array_flip(['method', 'url', 'params', 'openMode', 'width', 'height', 'noreferrer', 'blocked'])
        ));
    }

    public function test_TC_OPN_030_属性の値を正しくエスケープする(): void
    {
        $id = $this->createLink([
            'title' => self::PREFIX . '<script>alert(1)</script>',
            'description' => '<img src=x onerror=alert(2)>',
            'url' => 'https://a.example.com/?q="x"&r=\'y\'<z>',
            'params' => [['name' => 'p', 'value_type' => 'fixed', 'value' => "a'b\"<c>&d"]],
        ]);

        $html = $this->render(self::ADMIN_USER_ID, true);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        // 属性の値を取り出して JSON を読むと、元の文字列に戻る（属性を抜け出していない）
        $link = array_column($this->widgetProps($html)['links'], null, 'id')[$id];
        $this->assertSame(self::PREFIX . '<script>alert(1)</script>', $link['title']);
        $this->assertSame('<img src=x onerror=alert(2)>', $link['description']);
        $this->assertSame('https://a.example.com/?q="x"&r=\'y\'<z>&p=a%27b%22%3Cc%3E%26d', $link['url']);
    }

    public function test_TC_SET_012_ウィジェットが見つからないlinkidなら空で出す(): void
    {
        $values = ['module' => 'Home', 'view' => 'ShowWidget', 'name' => 'SharedLinks', 'linkid' => 999999, 'content' => 'data'];
        ob_start();
        (new Home_SharedLinks_Dashboard())->process(new Vtiger_Request($values, $values));
        $html = (string) ob_get_clean();

        $this->assertSame([], $this->widgetProps($html)['links']);
    }
}
