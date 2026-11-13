<?php

/**
 * 「送信メールサーバー」の設定で通知メールを送る
 *
 * 1 人ごとに 1 通送る（ほかの宛先のメールアドレスは見えない）。
 * 宛先が多いときの時間を抑えるため、メールサーバーへの接続は使い回す（SMTPKeepAlive）。
 * 送信はその場で行う（後回しのキューは使わない）。
 */
class NotificationBlock_SmtpMailSender_Model implements NotificationBlock_MailSender_Model
{
    private ?Emails_Mailer_Model $mailer = null;

    public function isConfigured(): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT server FROM vtiger_systems WHERE server_type = 'email'", []);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        return !empty($rows) && trim(NotificationBlock_Util_Helper::toStr($rows[0]['server'] ?? '')) !== '';
    }

    public function getServerFromEmail(): string
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT from_email_field FROM vtiger_systems WHERE server_type = 'email'", []);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        return empty($rows) ? '' : trim(NotificationBlock_Util_Helper::toStr($rows[0]['from_email_field'] ?? ''));
    }

    public function open(): void
    {
        $mailer = Emails_Mailer_Model::getInstance();
        $mailer->SMTPKeepAlive = true;
        $this->mailer = $mailer;
    }

    public function send(array $message): array
    {
        if ($this->mailer === null) {
            $this->open();
        }
        $mailer = $this->mailer;
        if ($mailer === null) {
            return ['success' => false, 'error' => 'Mailer is not available'];
        }
        $mailer->clearAllRecipients();
        $mailer->clearReplyTos();
        $mailer->clearCustomHeaders();
        $mailer->isHTML(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->From = $message['from_email'];
        $mailer->FromName = $message['from_name'];
        if ($message['reply_to'] !== '') {
            $mailer->addReplyTo($message['reply_to'], $message['from_name']);
        }
        $mailer->addAddress($message['to_email'], $message['to_name']);
        $mailer->Subject = $message['subject'];
        $mailer->Body = $message['html'];
        $mailer->AltBody = $message['text'];
        try {
            // Vtiger_Mailer::Send() の第 1 引数を true にすると、キューに入れずにその場で送る
            $sent = $mailer->Send(true);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
        if ($sent !== true) {
            return ['success' => false, 'error' => NotificationBlock_Util_Helper::toStr($mailer->getError())];
        }
        return ['success' => true, 'error' => ''];
    }

    public function close(): void
    {
        if ($this->mailer !== null) {
            $this->mailer->smtpClose();
            $this->mailer = null;
        }
    }
}
