<?php

declare(strict_types=1);

namespace Tests\Integration\NotificationBlock;

use NotificationBlock_Config_Model;
use NotificationBlock_History_Model;
use NotificationBlock_Notifier_Model;
use NotificationBlock_Target_Model;
use Tests\Support\NotificationBlockTestCase;

require_once dirname(__DIR__, 2) . '/Support/NotificationBlockTestCase.php';

/**
 * 通知の送信
 */
final class NotifierTest extends NotificationBlockTestCase
{
    /**
     * 案件を作り、通知先に指定したユーザーを入れる
     *
     * @param list<int> $userIds
     */
    private function recordWithUsers(string $name, array $userIds): int
    {
        $recordId = $this->createPotential($name);
        NotificationBlock_Target_Model::saveTargets($recordId, array_map(
            static fn (int $id): array => ['type' => 'user', 'id' => (string) $id],
            $userIds
        ));
        return $recordId;
    }

    /**
     * @param array<string, mixed> $history
     */
    private function historyIdOf(array $history): int
    {
        return \NotificationBlock_Util_Helper::toInt($history['historyid'] ?? 0);
    }

    public function test_宛先ごとに1通ずつ宛先の言語で送り_送信済みとして記録する(): void
    {
        $role = $this->createRole('役割');
        $ja = $this->createUser('日本語', 'ja@example.com', $role, 'Active', 0, 'ja_jp');
        $en = $this->createUser('英語', 'en@example.com', $role, 'Active', 0, 'en_us');
        $de = $this->createUser('ドイツ語', 'de@example.com', $role, 'Active', 0, 'de_de');
        $recordId = $this->recordWithUsers('送信', [$ja, $en, $de]);
        $adminEmail = $this->scalar('SELECT email1 FROM vtiger_users WHERE id = ?', [self::ADMIN_USER_ID]);

        $result = NotificationBlock_Notifier_Model::send($recordId, ['comment' => "確認をお願いします\n<b>至急</b>"]);

        $this->assertTrue($result['success']);
        $this->assertSame(NotificationBlock_Notifier_Model::CODE_SENT, $result['code']);
        $this->assertSame(3, $result['total']);
        $this->assertCount(3, $this->mailSender->sent);
        $byAddress = [];
        foreach ($this->mailSender->sent as $message) {
            $byAddress[$message['to_email']] = $message;
        }
        $this->assertStringStartsWith('【通知】', $byAddress['ja@example.com']['subject']);
        $this->assertStringContainsString(self::PREFIX . '送信', $byAddress['ja@example.com']['subject']);
        $this->assertStringStartsWith('[Notification]', $byAddress['en@example.com']['subject']);
        $this->assertStringStartsWith('[Notification]', $byAddress['de@example.com']['subject'], '日本語・英語以外は英語');
        $this->assertStringContainsString('record=' . $recordId, $byAddress['ja@example.com']['html']);
        $this->assertStringContainsString('&lt;b&gt;至急&lt;/b&gt;', $byAddress['ja@example.com']['html'], 'コメントは無害化して入れる');
        // 差出人：送信メールサーバーに差出人が無ければ送信者のアドレス。返信先は送信者
        $this->assertSame($adminEmail, $byAddress['ja@example.com']['from_email']);
        $this->assertSame($adminEmail, $byAddress['ja@example.com']['reply_to']);
        $this->assertSame(1, $this->mailSender->openCount);
        $this->assertSame(1, $this->mailSender->closeCount);

        $history = NotificationBlock_History_Model::getRecent($recordId)[0];
        $this->assertSame('sent', $history['display_status']);
        $this->assertSame('manual', $history['method']);
        $this->assertSame(3, $history['recipient_count']);
        $this->assertSame("確認をお願いします\n<b>至急</b>", $history['comment']);
        $this->assertSame(['sent', 'sent', 'sent'], array_column(NotificationBlock_History_Model::getRecipients($this->historyIdOf($history)), 'status'));
    }

    public function test_送信メールサーバーの差出人があれば差出人に使う(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $recordId = $this->recordWithUsers('差出人', [$user]);
        $this->mailSender->serverFromEmail = 'noreply@example.com';

        NotificationBlock_Notifier_Model::send($recordId);

        $this->assertSame('noreply@example.com', $this->mailSender->sent[0]['from_email']);
    }

    public function test_送信メールサーバーが無ければ送らず_失敗として理由を記録する(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $recordId = $this->recordWithUsers('サーバーなし', [$user]);
        $this->mailSender->configured = false;

        $result = NotificationBlock_Notifier_Model::send($recordId);

        $this->assertSame(NotificationBlock_Notifier_Model::CODE_NO_SERVER, $result['code']);
        $this->assertSame([], $this->mailSender->sent);
        $history = NotificationBlock_History_Model::getRecent($recordId)[0];
        $this->assertSame('failed', $history['display_status']);
        $this->assertNotSame('', $history['failure_reason']);
        $this->assertSame(['failed'], array_column(NotificationBlock_History_Model::getRecipients($this->historyIdOf($history)), 'status'));
    }

    public function test_一部に送れなければ一部失敗_全員に送れなければ失敗で_送れなかった理由を残す(): void
    {
        $role = $this->createRole('役割');
        $ok = $this->createUser('送れる', 'ok@example.com', $role);
        $ng = $this->createUser('送れない', 'ng@example.com', $role);
        $recordId = $this->recordWithUsers('一部失敗', [$ok, $ng]);
        $this->mailSender->failures['ng@example.com'] = '550 mailbox unavailable';

        $result = NotificationBlock_Notifier_Model::send($recordId);
        $this->assertSame(NotificationBlock_Notifier_Model::CODE_PARTIAL, $result['code']);
        $this->assertSame(1, $result['failed']);
        $recipients = NotificationBlock_History_Model::getRecipients($this->historyIdOf(NotificationBlock_History_Model::getRecent($recordId)[0]));
        $failed = array_values(array_filter($recipients, static fn (array $r): bool => $r['status'] === 'failed'));
        $this->assertSame('550 mailbox unavailable', $failed[0]['reason']);

        $this->mailSender->failures['ok@example.com'] = 'timeout';
        $this->assertSame(NotificationBlock_Notifier_Model::CODE_FAILED, NotificationBlock_Notifier_Model::send($recordId)['code']);
    }

    public function test_宛先が0人なら送らず送信履歴にも残さない(): void
    {
        $inactive = $this->createUser('無効', 'off@example.com', $this->createRole('役割'), 'Inactive');
        $recordId = $this->recordWithUsers('宛先なし', []);
        // 無効になったユーザーだけが通知先にいる
        $this->db->pquery('INSERT INTO vtiger_notificationblock_targets (crmid, target_type, target_id, sequence) VALUES (?, ?, ?, 0)', [$recordId, 'user', (string) $inactive]);

        $result = NotificationBlock_Notifier_Model::send($recordId);

        $this->assertFalse($result['success']);
        $this->assertSame(NotificationBlock_Notifier_Model::CODE_NO_RECIPIENTS, $result['code']);
        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_history WHERE crmid = ?', [$recordId]));
    }

    public function test_送信中の送信履歴があれば送らず_最後の記録から10分止まったものは中断とみなす(): void
    {
        $role = $this->createRole('役割');
        $user = $this->createUser('宛先', 'to@example.com', $role);
        $other = $this->createUser('もう 1 人', 'other@example.com', $role);
        $recordId = $this->recordWithUsers('送信中', [$user, $other]);
        $historyId = NotificationBlock_History_Model::create([
            'crmid' => $recordId, 'setype' => self::MODULE, 'sent_by' => self::ADMIN_USER_ID, 'sender_name' => 'admin',
            'method' => 'manual', 'method_label' => '', 'method_module' => '', 'comment' => '',
        ], [
            ['userid' => $user, 'name' => '宛先', 'email' => 'to@example.com'],
            ['userid' => $other, 'name' => 'もう 1 人', 'email' => 'other@example.com'],
        ]);
        NotificationBlock_History_Model::markRecipient($historyId, $user, true);

        $this->assertSame(NotificationBlock_Notifier_Model::CODE_SENDING, NotificationBlock_Notifier_Model::send($recordId)['code']);
        $this->assertSame('sending', NotificationBlock_History_Model::getRecent($recordId)[0]['display_status']);

        // 最後に記録してから 10 分を過ぎた
        $this->db->pquery('UPDATE vtiger_notificationblock_history SET last_progress_at = ? WHERE historyid = ?', [date('Y-m-d H:i:s', time() - 601), $historyId]);
        $this->assertFalse(NotificationBlock_History_Model::isSending($recordId));
        $this->assertSame('partial', NotificationBlock_History_Model::getById($historyId)['display_status'] ?? null);
        $this->assertSame(['sent', 'aborted'], array_column(NotificationBlock_History_Model::getRecipients($historyId), 'status'));

        $this->assertSame(NotificationBlock_Notifier_Model::CODE_SENT, NotificationBlock_Notifier_Model::send($recordId)['code']);
    }

    public function test_途中で止まったときは送れなかった宛先を未送信_中断にする(): void
    {
        $role = $this->createRole('役割');
        $first = $this->createUser('1 人目', 'first@example.com', $role);
        $second = $this->createUser('2 人目', 'second@example.com', $role);
        $recordId = $this->createPotential('中断');
        $historyId = NotificationBlock_History_Model::create([
            'crmid' => $recordId, 'setype' => self::MODULE, 'sent_by' => self::ADMIN_USER_ID, 'sender_name' => 'admin',
            'method' => 'manual', 'method_label' => '', 'method_module' => '', 'comment' => '',
        ], [
            ['userid' => $first, 'name' => '1 人目', 'email' => 'first@example.com'],
            ['userid' => $second, 'name' => '2 人目', 'email' => 'second@example.com'],
        ]);
        NotificationBlock_History_Model::markRecipient($historyId, $first, true);

        $summary = NotificationBlock_History_Model::finish($historyId);

        $this->assertSame('partial', $summary['status']);
        $this->assertSame(['sent', 'aborted'], array_column(NotificationBlock_History_Model::getRecipients($historyId), 'status'));
    }

    public function test_ごみ箱のレコードと通知ブロックを外したモジュールでは送らない(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $recordId = $this->recordWithUsers('送らない', [$user]);

        NotificationBlock_Config_Model::unregisterBlock($this->blockId);
        $this->assertSame(NotificationBlock_Notifier_Model::CODE_NO_BLOCK, NotificationBlock_Notifier_Model::send($recordId)['code']);

        NotificationBlock_Config_Model::register($this->tabId, $this->blockId, false);
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$recordId]);
        $this->assertSame(NotificationBlock_Notifier_Model::CODE_DELETED, NotificationBlock_Notifier_Model::send($recordId)['code']);
        $this->assertSame([], $this->mailSender->sent);
        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_history WHERE crmid = ?', [$recordId]));
    }

    public function test_ほかの機能からは件名と本文を渡せ_送信方法に呼び出し元の名前を記録する(): void
    {
        $role = $this->createRole('役割');
        $ja = $this->createUser('日本語', 'ja@example.com', $role, 'Active', 0, 'ja_jp');
        $en = $this->createUser('英語', 'en@example.com', $role, 'Active', 0, 'en_us');
        $recordId = $this->recordWithUsers('ほかの機能', [$ja, $en]);

        $result = NotificationBlock_Notifier_Model::send($recordId, [
            'method' => NotificationBlock_History_Model::METHOD_EXTERNAL,
            'method_label' => 'LBL_TASK_INFORMATION',
            'method_module' => 'Calendar',
            'check_permission' => false,
            'sender_id' => self::ADMIN_USER_ID,
            'subject' => ['ja_jp' => '【承認】{record_name}', 'en_us' => '[Approval] {record_name}'],
            'body' => '<p>{record_name} {account_no}</p>',
        ]);

        $this->assertSame(NotificationBlock_Notifier_Model::CODE_SENT, $result['code']);
        $subjects = array_column($this->mailSender->sent, 'subject', 'to_email');
        $this->assertSame('【承認】' . self::PREFIX . 'ほかの機能', $subjects['ja@example.com']);
        $this->assertSame('[Approval] ' . self::PREFIX . 'ほかの機能', $subjects['en@example.com']);
        $this->assertStringContainsString('{account_no}', $this->mailSender->sent[0]['html'], '使えない差し込みはそのまま');
        $history = NotificationBlock_History_Model::getRecent($recordId)[0];
        $this->assertSame('external', $history['method']);
        $this->assertSame(vtranslate('LBL_TASK_INFORMATION', 'Calendar'), $history['method_text']);
    }
}
