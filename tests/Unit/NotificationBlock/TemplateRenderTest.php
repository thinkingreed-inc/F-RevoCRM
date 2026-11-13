<?php

declare(strict_types=1);

namespace Tests\Unit\NotificationBlock;

use NotificationBlock_History_Model;
use NotificationBlock_Template_Model;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/modules/NotificationBlock/helpers/Util.php';
require_once dirname(__DIR__, 3) . '/modules/NotificationBlock/models/Template.php';
require_once dirname(__DIR__, 3) . '/modules/NotificationBlock/models/History.php';

/**
 * 通知メールの差し込みと、送信履歴の結果の決め方
 */
final class TemplateRenderTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function values(): array
    {
        return [
            'sender_name' => '山田 太郎',
            'module_name' => '案件',
            'record_name' => '○○工事 <b>&</b>',
            'record_url' => 'https://crm.example.com/index.php?module=Potentials&view=Detail&record=10',
            'comment' => "1 行目\n<script>alert(1)</script>",
            'sent_at' => '2026-11-10 12:00',
        ];
    }

    public function test_件名は差し込みを文字のまま入れ_改行は空白にする(): void
    {
        $subject = NotificationBlock_Template_Model::replaceInSubject('【通知】{module_name}：{record_name} {comment}', $this->values());
        $this->assertSame('【通知】案件：○○工事 <b>&</b> 1 行目 <script>alert(1)</script>', $subject);
    }

    public function test_使えない差し込みはそのまま文字として残す(): void
    {
        $this->assertSame('{account_no} 案件', NotificationBlock_Template_Model::replaceInSubject('{account_no} {module_name}', $this->values()));
        $this->assertSame('<p>{account_no}</p>', NotificationBlock_Template_Model::replaceInHtml('<p>{account_no}</p>', $this->values()));
    }

    public function test_本文の差し込みはHTMLとして無害化し_コメントの改行はbrにする(): void
    {
        $html = NotificationBlock_Template_Model::replaceInHtml('<p>{record_name}</p><p>{comment}</p>', $this->values());
        $this->assertStringContainsString('<p>○○工事 &lt;b&gt;&amp;&lt;/b&gt;</p>', $html);
        $this->assertStringContainsString('1 行目<br>' . "\n" . '&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_本文のレコードへのリンクはリンクにする(): void
    {
        $html = NotificationBlock_Template_Model::replaceInHtml('<p>リンク：{record_url}</p>', $this->values());
        $url = 'https://crm.example.com/index.php?module=Potentials&amp;view=Detail&amp;record=10';
        $this->assertSame('<p>リンク：<a href="' . $url . '">' . $url . '</a></p>', $html);
    }

    public function test_リンクの中やhrefの中のレコードへのリンクはURLだけ入れる(): void
    {
        $url = 'https://crm.example.com/index.php?module=Potentials&amp;view=Detail&amp;record=10';
        $html = NotificationBlock_Template_Model::replaceInHtml('<a href="{record_url}">{record_url}</a>', $this->values());
        $this->assertSame('<a href="' . $url . '">' . $url . '</a>', $html);
        // エディターが {} を %7B %7D にした場合も同じ
        $html = NotificationBlock_Template_Model::replaceInHtml('<a href="%7Brecord_url%7D">開く</a>', $this->values());
        $this->assertSame('<a href="' . $url . '">開く</a>', $html);
    }

    public function test_HTMLを表示できないメールソフト向けの文を作る(): void
    {
        $text = NotificationBlock_Template_Model::htmlToText('<p>山田 さんから<br>案件の通知です。</p><p>リンク：<a href="https://x.example">https://x.example</a></p>');
        $this->assertSame("山田 さんから\n案件の通知です。\nリンク：https://x.example", $text);
    }

    public function test_日本語と英語以外の利用者には英語の文面を使う(): void
    {
        $this->assertSame('ja_jp', NotificationBlock_Template_Model::resolveLanguage('ja_jp'));
        $this->assertSame('en_us', NotificationBlock_Template_Model::resolveLanguage('en_us'));
        $this->assertSame('en_us', NotificationBlock_Template_Model::resolveLanguage('de_de'));
        $this->assertSame('en_us', NotificationBlock_Template_Model::resolveLanguage(''));
    }

    public function test_件名は1行で255文字まで(): void
    {
        $this->assertSame('a b', NotificationBlock_Template_Model::normalizeSubject(" a\r\nb "));
        $this->assertSame(255, mb_strlen(NotificationBlock_Template_Model::normalizeSubject(str_repeat('あ', 300))));
    }

    public function test_送信履歴の結果は送れた人数で決める(): void
    {
        $this->assertSame('sent', NotificationBlock_History_Model::decideStatus(3, 3));
        $this->assertSame('partial', NotificationBlock_History_Model::decideStatus(1, 3));
        $this->assertSame('failed', NotificationBlock_History_Model::decideStatus(0, 3));
    }
}
