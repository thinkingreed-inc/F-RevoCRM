<?php

/**
 * 通知メールを送る部分の窓口
 *
 * 実際の送信（NotificationBlock_SmtpMailSender_Model）と、テスト用の記録だけするものを差し替えられるようにする
 * （NotificationBlock_Notifier_Model::setMailSender()）。
 */
interface NotificationBlock_MailSender_Model
{
    /**
     * 送信メールサーバーが設定されているか
     */
    public function isConfigured(): bool;

    /**
     * 送信メールサーバーの「差出人」（未設定なら空）
     */
    public function getServerFromEmail(): string;

    /**
     * 何通か続けて送る前に呼ぶ（接続を使い回すため）
     */
    public function open(): void;

    /**
     * 1 通送る
     *
     * @param array{to_email: string, to_name: string, from_email: string, from_name: string, reply_to: string, subject: string, html: string, text: string} $message
     * @return array{success: bool, error: string}
     */
    public function send(array $message): array;

    /**
     * 送り終わったら呼ぶ
     */
    public function close(): void;
}
