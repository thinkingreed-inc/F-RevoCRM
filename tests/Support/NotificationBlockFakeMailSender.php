<?php

declare(strict_types=1);

namespace Tests\Support;

use NotificationBlock_MailSender_Model;

/**
 * 通知ブロックのテスト用：メールを実際には送らず、送ろうとした内容を記録する
 *
 * 送信メールサーバーの設定の有無、宛先ごとの失敗、差出人の設定を差し替えられる。
 */
final class NotificationBlockFakeMailSender implements NotificationBlock_MailSender_Model
{
    public bool $configured = true;
    public string $serverFromEmail = '';

    /** @var array<string, string> 失敗させる宛先のメールアドレス => エラー */
    public array $failures = [];

    /** @var list<array{to_email: string, to_name: string, from_email: string, from_name: string, reply_to: string, subject: string, html: string, text: string}> */
    public array $sent = [];

    public int $openCount = 0;
    public int $closeCount = 0;

    /** @var (callable(array<string, string>): void)|null 1 通送るたびに呼ぶ（途中で止まる場合のテスト用） */
    public $onSend = null;

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getServerFromEmail(): string
    {
        return $this->serverFromEmail;
    }

    public function open(): void
    {
        $this->openCount++;
    }

    public function send(array $message): array
    {
        if ($this->onSend !== null) {
            ($this->onSend)($message);
        }
        if (isset($this->failures[$message['to_email']])) {
            return ['success' => false, 'error' => $this->failures[$message['to_email']]];
        }
        $this->sent[] = $message;
        return ['success' => true, 'error' => ''];
    }

    public function close(): void
    {
        $this->closeCount++;
    }

    /**
     * @return list<string>
     */
    public function sentAddresses(): array
    {
        return array_map(static fn (array $message): string => $message['to_email'], $this->sent);
    }
}
