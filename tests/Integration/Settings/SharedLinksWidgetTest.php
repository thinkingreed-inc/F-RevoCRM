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
 * テンプレート（Smarty）まで実際に通し、出力される HTML を確かめる。
 * テスト仕様書: docs/tests/SharedLinks/
 *   TS-02 公開範囲 / TS-04 リンクの開き方 / TS-07 ウィジェット
 */
final class SharedLinksWidgetTest extends SharedLinksTestCase
{
    private int $linkId;

    protected function setUp(): void
    {
        parent::setUp();
        $result = $this->db->pquery("SELECT linkid FROM vtiger_links WHERE linklabel = 'Shared Links'", []);
        $this->linkId = (int) $this->db->query_result($result, 0, 'linkid');
        $this->removeWidgetPlacements();
    }

    protected function tearDown(): void
    {
        $this->removeWidgetPlacements();
        parent::tearDown();
    }

    private function removeWidgetPlacements(): void
    {
        $this->db->pquery('DELETE FROM vtiger_module_dashboard_widgets WHERE linkid = ?', [$this->linkId]);
    }

    /** ユーザーの既定のダッシュボードのタブにウィジェットを置く（ShowWidget と同じ手順） */
    private function placeWidget(int $userId): Vtiger_Widget_Model
    {
        $tab = Vtiger_DashBoard_Model::getInstance('Home')->getUserDefaultTab($userId);
        $widget = new Vtiger_Widget_Model();
        $widget->set('linkid', $this->linkId);
        $widget->set('userid', $userId);
        $widget->set('tabid', $tab['id']);
        $widget->add();
        return $widget;
    }

    private function render(int $userId, bool $contentOnly = false): string
    {
        $this->loginAs($userId);
        $widget = $this->placeWidget($userId);
        $values = ['module' => 'Home', 'view' => 'ShowWidget', 'name' => 'SharedLinks', 'linkid' => $this->linkId];
        if ($contentOnly) {
            $values['content'] = 'data';
        }
        ob_start();
        try {
            (new Home_SharedLinks_Dashboard())->process(new Vtiger_Request($values, $values), $widget);
        } finally {
            $html = (string) ob_get_clean();
            $this->removeWidgetPlacements();
        }
        return $html;
    }

    /** 表示できるリンクがテストで作ったものだけであること（前提） */
    private function assumeOnlyTestLinks(): void
    {
        foreach (LinkModel::getAllLinks() as $link) {
            if (strpos($link['title'], self::PREFIX) !== 0) {
                $this->markTestSkipped('テスト用 DB にテスト以外の共有リンクがあるため飛ばします');
            }
        }
    }

    /**
     * @return list<\DOMElement>
     */
    private function anchors(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
        $anchors = [];
        foreach ($dom->getElementsByTagName('a') as $a) {
            if (str_contains(' ' . $a->getAttribute('class') . ' ', ' sharedLink ')) {
                $anchors[] = $a;
            }
        }
        return $anchors;
    }

    public function test_TC_WDG_001_002_ウィジェットを追加に出て追加済みのタブでは出ない(): void
    {
        $tab = Vtiger_DashBoard_Model::getInstance('Home')->getUserDefaultTab(self::ADMIN_USER_ID);
        $selectable = function () use ($tab): array {
            $dashboard = Vtiger_DashBoard_Model::getInstance('Home');
            $dashboard->set('tabid', $tab['id']);
            return array_map(fn ($w) => $w->get('linklabel'), $dashboard->getSelectableDashboard());
        };

        $this->assertContains('Shared Links', $selectable());
        $this->placeWidget(self::ADMIN_USER_ID);
        $this->assertNotContains('Shared Links', $selectable());
    }

    public function test_TC_WDG_003_014_見出しは共有リンクでフッターに更新と削除がある(): void
    {
        $html = $this->render(self::ADMIN_USER_ID);

        $this->assertMatchesRegularExpression('#<div class="dashboardTitle"[^>]*><b>共有リンク</b></div>#u', $html);
        $this->assertStringContainsString('name="drefresh"', $html);
        $this->assertStringContainsString('name="dclose"', $html);
    }

    public function test_TC_WDG_004_ダッシュボードでウィジェットのJSを読み込む(): void
    {
        $request = new Vtiger_Request(['module' => 'Home', 'view' => 'DashBoard']);
        $scripts = (new Home_DashBoard_View())->getHeaderScripts($request);

        $this->assertArrayHasKey('modules.Home.resources.SharedLinksWidget', $scripts);
        $src = $scripts['modules.Home.resources.SharedLinksWidget']->get('src');
        $this->assertFileExists(dirname(__DIR__, 3) . '/public/' . $src);
    }

    public function test_TC_WDG_010_TC_OPN_031_TC_OPN_033_リンクを並び順で出し説明の改行を保ちliを使わない(): void
    {
        $this->createLink(['title' => self::PREFIX . 'A', 'description' => "1行目\n2行目"]);
        $this->createLink(['title' => self::PREFIX . 'B']);
        $first = $this->createLink(['title' => self::PREFIX . 'C']);
        LinkModel::reorder(array_merge([$first], array_values(array_diff(array_column(LinkModel::getAllLinks(), 'id'), [$first]))));

        $html = $this->render(self::ADMIN_USER_ID, true);

        $titles = array_map(fn ($a) => trim($a->textContent), $this->anchors($html));
        $this->assertSame([self::PREFIX . 'C', self::PREFIX . 'A', self::PREFIX . 'B'], array_values(array_filter(
            $titles,
            fn ($t) => str_starts_with($t, self::PREFIX)
        )));
        $this->assertStringContainsString("white-space: pre-wrap;\">1行目\n2行目</div>", $html);
        // ウィジェット自体が gridster の <li> のため、中で <li> を使うと CSS が効いて崩れる
        $this->assertStringNotContainsStringIgnoringCase('<li', $html);
    }

    public function test_TC_WDG_011_0件なら文言と管理者には設定画面へのリンク(): void
    {
        $this->assumeOnlyTestLinks();

        $html = $this->render(self::ADMIN_USER_ID, true);

        $this->assertStringContainsString('共有リンクが登録されていません', $html);
        $this->assertStringContainsString('index.php?module=SharedLinks&parent=Settings&view=List', $html);
        $this->assertSame([], $this->anchors($html));
    }

    public function test_TC_WDG_012_管理者以外には設定画面へのリンクを出さない(): void
    {
        $this->assumeOnlyTestLinks();

        $html = $this->render($this->nonAdminUserId(), true);

        $this->assertStringContainsString('共有リンクが登録されていません', $html);
        $this->assertStringNotContainsString('module=SharedLinks&parent=Settings', $html);
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

    public function test_TC_OPN_009_015_POSTはformを書き出さずrelはリファラーの設定に従う(): void
    {
        $getOff = $this->createLink(['title' => self::PREFIX . 'get-off']);
        $getOn = $this->createLink(['title' => self::PREFIX . 'get-on', 'noreferrer' => true]);
        $post = $this->createLink([
            'title' => self::PREFIX . 'post',
            'method' => 'POST',
            'noreferrer' => true,
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
        $byId = [];
        foreach ($this->anchors($html) as $a) {
            $data = json_decode($a->getAttribute('data-shared-link'), true);
            $this->assertIsArray($data);
            $byId[$data['id']] = [$a, $data];
        }

        // TC-OPN-009: <form> を書き出さない（csrf-magic がトークンを埋め込むため）
        $this->assertStringNotContainsStringIgnoringCase('<form', $html);
        // TC-OPN-010 / 011
        [$a] = $byId[$getOff];
        $this->assertSame(['_blank', 'noopener'], [$a->getAttribute('target'), $a->getAttribute('rel')]);
        [$a] = $byId[$getOn];
        $this->assertSame(['_blank', 'noopener noreferrer'], [$a->getAttribute('target'), $a->getAttribute('rel')]);
        // POST は href を持たず、送信内容は data 属性で渡す
        [$a, $data] = $byId[$post];
        $this->assertSame('javascript:void(0);', $a->getAttribute('href'));
        $this->assertFalse($a->hasAttribute('target'));
        $this->assertSame([
            'id' => $post,
            'method' => 'POST',
            'url' => 'https://kintai.example.com/login?lang=ja',
            'params' => [['name' => 'uid', 'value' => (string) self::ADMIN_USER_ID]],
            'openMode' => 'window',
            'width' => 800,
            'height' => 600,
            'noreferrer' => true,
            // テスト用の設定では送り先を許可していないため、ウィジェットは開かずに通知する
            'blocked' => true,
        ], $data);
    }

    public function test_TC_OPN_030_タイトルや説明やURLや送信内容をエスケープする(): void
    {
        $id = $this->createLink([
            'title' => self::PREFIX . '<script>alert(1)</script>',
            'description' => '<img src=x onerror=alert(2)>',
            'url' => 'https://a.example.com/?q="x"&r=\'y\'<z>',
            'params' => [['name' => 'p', 'value_type' => 'fixed', 'value' => "a'b\"<c>&d"]],
        ]);
        $postId = $this->createLink([
            'title' => self::PREFIX . 'post',
            'method' => 'POST',
            'url' => 'https://a.example.com/post',
            'params' => [['name' => 'p', 'value_type' => 'fixed', 'value' => "a'b\"<c>&d</script>"]],
        ]);

        $html = $this->render(self::ADMIN_USER_ID, true);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $html);
        $byId = [];
        foreach ($this->anchors($html) as $a) {
            $data = json_decode($a->getAttribute('data-shared-link'), true);
            $byId[$data['id']] = [$a, $data];
        }
        // 属性の値を取り出すと元の文字列に戻る（属性を抜け出していない）
        [$a, $data] = $byId[$id];
        $expectedUrl = 'https://a.example.com/?q="x"&r=\'y\'<z>&p=a%27b%22%3Cc%3E%26d';
        $this->assertSame($expectedUrl, $a->getAttribute('href'));
        $this->assertSame($expectedUrl, $data['url']);
        [, $data] = $byId[$postId];
        $this->assertSame([['name' => 'p', 'value' => "a'b\"<c>&d</script>"]], $data['params']);
    }
}
