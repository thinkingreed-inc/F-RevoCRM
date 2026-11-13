<?php

declare(strict_types=1);

namespace Tests\Integration\NotificationBlock;

use NotificationBlock_Flash_Model;
use NotificationBlock_History_Model;
use NotificationBlock_Target_Model;
use NotificationBlockHandler;
use Tests\Support\NotificationBlockTestCase;

require_once dirname(__DIR__, 2) . '/Support/NotificationBlockTestCase.php';

/**
 * 画面からの保存（作成・編集画面）と作成時の送信
 *
 * 作成・編集画面は、保存のときに notificationblock_form と notificationblock_targets を送る。
 * そのリクエストのままレコードを保存し、保存のイベントで通知先が保存されることを確かめる。
 */
final class HandlerTest extends NotificationBlockTestCase
{
    /**
     * @param list<int> $userIds
     */
    private function formRequest(array $userIds, string $record = ''): void
    {
        $_REQUEST = [
            'module' => self::MODULE,
            'action' => 'Save',
            'record' => $record,
            NotificationBlockHandler::FORM_MARKER => '1',
            NotificationBlockHandler::FORM_TARGETS => json_encode(array_map(
                static fn (int $id): array => ['type' => 'user', 'id' => (string) $id],
                $userIds
            )),
        ];
    }

    public function test_作成画面からの作成では通知先を保存し_作成時に送信がはいなら送って結果を移った先に渡す(): void
    {
        $this->setSendOnCreate(true);
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $this->formRequest([$user]);

        $recordId = $this->createPotential('作成時に送信');

        $this->assertSame([(string) $user], array_column(NotificationBlock_Target_Model::getTargets($recordId), 'id'));
        $this->assertSame(['to@example.com'], $this->mailSender->sentAddresses());
        $history = NotificationBlock_History_Model::getAll($recordId)[0];
        $this->assertSame('create', $history['method']);
        $this->assertSame('', $history['comment'], '作成時の送信はコメントなし');
        $flash = NotificationBlock_Flash_Model::take();
        $this->assertSame(vtranslate('LBL_RESULT_SENT', 'NotificationBlock', 1), $flash['message'] ?? null);
        $this->assertSame('success', $flash['type'] ?? null);
    }

    public function test_作成時に送信がいいえなら通知先を保存するだけで送らない(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $this->formRequest([$user]);

        $recordId = $this->createPotential('送らない');

        $this->assertCount(1, NotificationBlock_Target_Model::getTargets($recordId));
        $this->assertSame([], $this->mailSender->sent);
        $this->assertNull(NotificationBlock_Flash_Model::take());
    }

    public function test_通知先があるのに宛先が0人なら送らず_そのことを移った先に渡す(): void
    {
        $this->setSendOnCreate(true);
        $role = $this->createRole('メンバーが無効だけの役割');
        $this->createUser('無効', 'off@example.com', $role, 'Inactive');
        $_REQUEST = [
            'module' => self::MODULE,
            'record' => '',
            NotificationBlockHandler::FORM_MARKER => '1',
            NotificationBlockHandler::FORM_TARGETS => json_encode([['type' => 'role', 'id' => $role]]),
        ];

        $recordId = $this->createPotential('宛先0人');

        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_history WHERE crmid = ?', [$recordId]));
        $this->assertSame(vtranslate('LBL_RESULT_NO_RECIPIENTS_ON_CREATE', 'NotificationBlock'), NotificationBlock_Flash_Model::take()['message'] ?? null);
    }

    public function test_通知先が空なら作成時に送信がはいでも何も出さない(): void
    {
        $this->setSendOnCreate(true);
        $this->formRequest([]);

        $this->createPotential('通知先なし');

        $this->assertSame([], $this->mailSender->sent);
        $this->assertNull(NotificationBlock_Flash_Model::take());
    }

    public function test_編集画面の保存では通知先を保存し_送らない(): void
    {
        $this->setSendOnCreate(true);
        $recordId = $this->createPotential('編集');
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $this->formRequest([$user], (string) $recordId);

        $record = \Vtiger_Record_Model::getInstanceById($recordId, self::MODULE);
        $record->set('mode', 'edit');
        $record->save();

        $this->assertSame([(string) $user], array_column(NotificationBlock_Target_Model::getTargets($recordId), 'id'));
        $this->assertSame([], $this->mailSender->sent);
    }

    public function test_通知ブロックの入力欄が無い保存と_画面と違うモジュールのレコードの保存では通知先を変えない(): void
    {
        $this->setSendOnCreate(true);
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        // クイック作成・インポート・API・ワークフローなど（入力欄の値が無い）
        $_REQUEST = ['module' => self::MODULE, 'record' => ''];
        $quick = $this->createPotential('クイック作成');
        $this->assertSame([], NotificationBlock_Target_Model::getTargets($quick));

        // 画面は顧客企業の保存で、同じリクエストの中で案件が保存された（ワークフローなど）
        $this->formRequest([$user]);
        $_REQUEST['module'] = 'Accounts';
        $other = $this->createPotential('ほかのモジュール');
        $this->assertSame([], NotificationBlock_Target_Model::getTargets($other));
        $this->assertSame([], $this->mailSender->sent);
    }

    public function test_同じリクエストで2件目に保存したレコードには通知先を入れない(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $this->formRequest([$user]);

        $first = $this->createPotential('1 件目');
        $second = $this->createPotential('2 件目');

        $this->assertCount(1, NotificationBlock_Target_Model::getTargets($first));
        $this->assertSame([], NotificationBlock_Target_Model::getTargets($second));
    }
}
