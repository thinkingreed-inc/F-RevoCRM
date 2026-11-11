<?php

declare(strict_types=1);

namespace Tests\Integration\NotificationBlock;

use NotificationBlock_Template_Model;
use Tests\Support\NotificationBlockTestCase;

require_once dirname(__DIR__, 2) . '/Support/NotificationBlockTestCase.php';

/**
 * メール文面の設定
 */
final class TemplateTest extends NotificationBlockTestCase
{
    /**
     * @return array<string, string>
     */
    private function values(string $comment): array
    {
        return [
            'sender_name' => '山田 太郎',
            'module_name' => '案件',
            'record_name' => '○○工事',
            'record_url' => 'https://crm.example.com/index.php?module=Potentials&view=Detail&record=1',
            'comment' => $comment,
            'sent_at' => '2026-11-10 12:00',
        ];
    }

    public function test_未設定なら既定の文面で送り_コメントが空ならコメントの行を出さない(): void
    {
        $mail = NotificationBlock_Template_Model::render($this->tabId, 'ja_jp', $this->values(''));
        $this->assertSame('【通知】案件：○○工事', $mail['subject']);
        $this->assertStringContainsString('山田 太郎 さんから 案件 の通知です。', $mail['html']);
        $this->assertStringContainsString('レコード：○○工事', $mail['html']);
        $this->assertStringNotContainsString('コメント：', $mail['html']);

        $mail = NotificationBlock_Template_Model::render($this->tabId, 'ja_jp', $this->values('よろしく'));
        $this->assertStringContainsString('コメント：よろしく', $mail['html']);

        $mail = NotificationBlock_Template_Model::render($this->tabId, 'en_us', $this->values(''));
        $this->assertSame('[Notification] 案件: ○○工事', $mail['subject']);
    }

    public function test_保存した文面を言語ごとに使い_片方が空ならその部分だけ既定の文面を使う(): void
    {
        NotificationBlock_Template_Model::save($this->tabId, [
            'ja_jp' => ['subject' => '【共有】{record_name}', 'body' => '<p>{sender_name}より：{comment}</p>', 'use_default' => '0'],
            'en_us' => ['subject' => '', 'body' => '<p>Custom {record_name}</p>', 'use_default' => '0'],
        ]);

        $ja = NotificationBlock_Template_Model::render($this->tabId, 'ja_jp', $this->values(''));
        $this->assertSame('【共有】○○工事', $ja['subject']);
        $this->assertSame('<p>山田 太郎より：</p>', $ja['html']);

        $en = NotificationBlock_Template_Model::render($this->tabId, 'en_us', $this->values(''));
        $this->assertSame('[Notification] 案件: ○○工事', $en['subject'], '件名が空なら既定の件名');
        $this->assertSame('<p>Custom ○○工事</p>', $en['html']);
    }

    public function test_既定の文面に戻すと未設定に戻り_空や既定と同じ文面も未設定として扱う(): void
    {
        NotificationBlock_Template_Model::save($this->tabId, [
            'ja_jp' => ['subject' => '件名', 'body' => '<p>本文</p>', 'use_default' => '0'],
            'en_us' => ['subject' => 'Subject', 'body' => '<p>Body</p>', 'use_default' => '0'],
        ]);
        $this->assertCount(2, NotificationBlock_Template_Model::getStored($this->tabId));

        NotificationBlock_Template_Model::save($this->tabId, [
            'ja_jp' => ['subject' => '件名', 'body' => '<p>本文</p>', 'use_default' => '1'],
            'en_us' => [
                'subject' => NotificationBlock_Template_Model::getDefaultSubject('en_us'),
                'body' => NotificationBlock_Template_Model::getDefaultBody('en_us'),
                'use_default' => '0',
            ],
        ]);
        $this->assertSame([], NotificationBlock_Template_Model::getStored($this->tabId));

        $dialog = NotificationBlock_Template_Model::getForDialog($this->tabId);
        $this->assertTrue($dialog['ja_jp']['is_default']);
        $this->assertSame(NotificationBlock_Template_Model::getDefaultSubject('ja_jp'), $dialog['ja_jp']['subject'], '未設定なら既定の文面を入れて出す');
    }

    public function test_本文からscriptとイベントの属性を取り除いて保存する(): void
    {
        NotificationBlock_Template_Model::save($this->tabId, [
            'ja_jp' => ['subject' => '件名', 'body' => '<p onclick="alert(1)">本文</p><script>alert(2)</script>', 'use_default' => '0'],
        ]);
        $body = NotificationBlock_Template_Model::getStored($this->tabId)['ja_jp']['body'];
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);
    }
}
