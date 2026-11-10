<?php

declare(strict_types=1);

namespace Tests\Integration\NotificationBlock;

use NotificationBlock_Config_Model;
use NotificationBlock_Display_Helper;
use NotificationBlock_History_Model;
use NotificationBlock_Notifier_Model;
use NotificationBlock_Target_Model;
use RecycleBin_Module_Model;
use Tests\Support\NotificationBlockTestCase;

require_once dirname(__DIR__, 2) . '/Support/NotificationBlockTestCase.php';

/**
 * 通知ブロックの置き方と、レコードの削除・ごみ箱（仕様 動作定義 No.13、表C の注記、概要 1-3、確認事項 Q19）
 */
final class LifecycleTest extends NotificationBlockTestCase
{
    public function test_ごみ箱から戻すと通知先と送信履歴がそのまま出て_完全に削除すると消える(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $recordId = $this->createPotential('ごみ箱');
        NotificationBlock_Target_Model::saveTargets($recordId, [['type' => 'user', 'id' => (string) $user]]);
        NotificationBlock_Notifier_Model::send($recordId);
        $historyId = \NotificationBlock_Util_Helper::toInt(NotificationBlock_History_Model::getRecent($recordId)[0]['historyid'] ?? 0);

        $record = \Vtiger_Record_Model::getInstanceById($recordId, self::MODULE);
        $record->delete();
        $this->assertCount(1, NotificationBlock_Target_Model::getTargets($recordId), 'ごみ箱に移しても通知先は残す');

        $recycleBin = new RecycleBin_Module_Model();
        // ごみ箱の「元に戻す」と同じ処理（RecycleBin_Module_Model::restore() が 1 件ずつ呼ぶもの）
        $focus = \CRMEntity::getInstance(self::MODULE);
        $focus->restore(self::MODULE, $recordId);
        $this->assertCount(1, NotificationBlock_Target_Model::getTargets($recordId));
        $this->assertCount(1, NotificationBlock_History_Model::getRecent($recordId));

        $record = \Vtiger_Record_Model::getInstanceById($recordId, self::MODULE);
        $record->delete();
        $recycleBin->deleteRecords([$recordId]);

        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_targets WHERE crmid = ?', [$recordId]));
        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_history WHERE crmid = ?', [$recordId]));
        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_recipients WHERE historyid = ?', [$historyId]));
    }

    public function test_通知ブロックを削除しても通知先と送信履歴とメール文面は残り_再び追加するとそのまま出る(): void
    {
        $user = $this->createUser('宛先', 'to@example.com', $this->createRole('役割'));
        $recordId = $this->createPotential('ブロック削除');
        NotificationBlock_Target_Model::saveTargets($recordId, [['type' => 'user', 'id' => (string) $user]]);
        \NotificationBlock_Template_Model::save($this->tabId, ['ja_jp' => ['subject' => '残る件名', 'body' => '<p>残る本文</p>', 'use_default' => '0']]);

        $this->db->pquery('DELETE FROM vtiger_blocks WHERE blockid = ?', [$this->blockId]);
        NotificationBlock_Config_Model::unregisterBlock($this->blockId);
        $this->assertFalse(NotificationBlock_Config_Model::hasBlock(self::MODULE));
        $this->assertSame('', NotificationBlock_Display_Helper::getPlacement(self::MODULE, ['LBL_OPPORTUNITY_INFORMATION' => ['x' => 1]]));

        $this->addNotificationBlock(false);
        $this->assertCount(1, NotificationBlock_Target_Model::getTargets($recordId));
        $this->assertSame('残る件名', \NotificationBlock_Template_Model::getStored($this->tabId)['ja_jp']['subject']);
    }

    public function test_項目設定で消したブロックの印が残っていても通知ブロックは無いとみなす(): void
    {
        $this->db->pquery('DELETE FROM vtiger_blocks WHERE blockid = ?', [$this->blockId]);
        NotificationBlock_Config_Model::clearCache();

        $this->assertFalse(NotificationBlock_Config_Model::hasBlock(self::MODULE));
        $this->assertFalse(NotificationBlock_Config_Model::isNotificationBlock($this->blockId));
    }

    public function test_置けるモジュールはエンティティで有効なものだけで_メールやカレンダーなどは置けない(): void
    {
        $this->assertTrue(NotificationBlock_Config_Model::isSupportedModule('Potentials'));
        $this->assertTrue(NotificationBlock_Config_Model::isSupportedModule('Quotes'));
        foreach (['Emails', 'Webmails', 'ModComments', 'Calendar', 'Events', 'Users', 'SMSNotifier', 'Home', 'NoSuchModule'] as $module) {
            $this->assertFalse(NotificationBlock_Config_Model::isSupportedModule($module), $module);
        }
    }

    public function test_通知ブロックは並んだブロックの中で置いた位置の次のブロックの前に出し_最後なら最後に出す(): void
    {
        $blocks = \NotificationBlock_Util_Helper::fetchRawRows($this->db->pquery(
            'SELECT blockid, blocklabel, sequence FROM vtiger_blocks WHERE tabid = ? AND blockid <> ? ORDER BY sequence',
            [$this->tabId, $this->blockId]
        ));
        $this->assertGreaterThanOrEqual(2, count($blocks));
        $first = \NotificationBlock_Util_Helper::toStr($blocks[0]['blocklabel'] ?? '');
        $second = \NotificationBlock_Util_Helper::toStr($blocks[1]['blocklabel'] ?? '');
        $firstSequence = \NotificationBlock_Util_Helper::toInt($blocks[0]['sequence'] ?? 0);
        $structure = [$first => ['a' => 1], $second => ['b' => 1]];

        $saved = \NotificationBlock_Util_Helper::fetchRawRows($this->db->pquery('SELECT blockid, sequence FROM vtiger_blocks WHERE tabid = ?', [$this->tabId]));
        try {
            // 1 つ目のブロックのすぐ後ろに置く
            $this->db->pquery('UPDATE vtiger_blocks SET sequence = sequence + 1 WHERE tabid = ? AND sequence > ?', [$this->tabId, $firstSequence]);
            $this->db->pquery('UPDATE vtiger_blocks SET sequence = ? WHERE blockid = ?', [$firstSequence + 1, $this->blockId]);
            $this->assertSame($second, NotificationBlock_Display_Helper::getPlacement(self::MODULE, $structure));

            // 2 つ目のブロックが画面に出ない（項目が無い）なら、その次に並ぶブロック、無ければ最後
            $this->assertSame(
                NotificationBlock_Display_Helper::POSITION_END,
                NotificationBlock_Display_Helper::getPlacement(self::MODULE, [$first => ['a' => 1], $second => []])
            );
        } finally {
            foreach ($saved as $row) {
                $this->db->pquery('UPDATE vtiger_blocks SET sequence = ? WHERE blockid = ?', [$row['sequence'], $row['blockid']]);
            }
        }
    }
}
