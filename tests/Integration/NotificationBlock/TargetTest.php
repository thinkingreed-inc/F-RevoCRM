<?php

declare(strict_types=1);

namespace Tests\Integration\NotificationBlock;

use NotificationBlock_Target_Model;
use Tests\Support\NotificationBlockTestCase;

require_once dirname(__DIR__, 2) . '/Support/NotificationBlockTestCase.php';

/**
 * 通知先の保存と表示
 */
final class TargetTest extends NotificationBlockTestCase
{
    public function test_通知先は入力した順に保存し_種類と表示名を付けて出す(): void
    {
        $recordId = $this->createPotential('通知先');
        $role = $this->createRole('営業部長');
        $user = $this->createUser('太郎', 'taro@example.com', $role);
        $group = $this->createGroup('設計課');

        NotificationBlock_Target_Model::saveTargets($recordId, [
            ['type' => 'user', 'id' => (string) $user],
            ['type' => 'role', 'id' => $role],
            ['type' => 'group', 'id' => (string) $group],
            ['type' => 'unknown', 'id' => '1'],
            ['type' => 'user', 'id' => (string) $user],
        ]);
        $targets = NotificationBlock_Target_Model::getTargets($recordId);

        $this->assertSame(['user', 'role', 'group'], array_column($targets, 'type'));
        $this->assertSame([self::PREFIX . '太郎', self::PREFIX . '営業部長', self::PREFIX . '設計課'], array_column($targets, 'label'));
    }

    public function test_もう無い役割・グループ・ユーザーは表示せず_保存し直すと外れる(): void
    {
        $recordId = $this->createPotential('もう無い');
        $role = $this->createRole('消す役割');
        $kept = $this->createRole('残る役割');
        NotificationBlock_Target_Model::saveTargets($recordId, [
            ['type' => 'role', 'id' => $role],
            ['type' => 'role', 'id' => $kept],
        ]);
        $this->db->pquery('DELETE FROM vtiger_role WHERE roleid = ?', [$role]);

        $this->assertSame([$kept], array_column(NotificationBlock_Target_Model::getTargets($recordId), 'id'));
        $this->assertSame('2', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_targets WHERE crmid = ?', [$recordId]), 'データは消さない');

        NotificationBlock_Target_Model::saveTargets($recordId, NotificationBlock_Target_Model::getTargets($recordId));
        $this->assertSame('1', $this->scalar('SELECT COUNT(*) FROM vtiger_notificationblock_targets WHERE crmid = ?', [$recordId]));
    }

    public function test_無効にしたユーザーは無効の印付きで残り_新しくは選べず_複製では引き継がない(): void
    {
        $recordId = $this->createPotential('無効');
        $role = $this->createRole('役割');
        $user = $this->createUser('あとで無効', 'later@example.com', $role);
        $active = $this->createUser('有効', 'active@example.com', $role);
        $inactive = $this->createUser('最初から無効', 'inactive@example.com', $role, 'Inactive');
        NotificationBlock_Target_Model::saveTargets($recordId, [
            ['type' => 'user', 'id' => (string) $user],
            ['type' => 'user', 'id' => (string) $active],
        ]);
        $this->db->pquery("UPDATE vtiger_users SET status = 'Inactive' WHERE id = ?", [$user]);

        $targets = NotificationBlock_Target_Model::getTargets($recordId);
        $this->assertSame([true, false], array_column($targets, 'disabled'));

        // 無効のユーザーを新しく足しても保存しない（すでに入っていた無効のユーザーは残す）
        $saved = NotificationBlock_Target_Model::saveTargets($recordId, [
            ['type' => 'user', 'id' => (string) $user],
            ['type' => 'user', 'id' => (string) $active],
            ['type' => 'user', 'id' => (string) $inactive],
        ]);
        $this->assertSame([(string) $user, (string) $active], array_column($saved, 'id'));

        $this->assertSame([(string) $active], array_column(NotificationBlock_Target_Model::getActiveTargets($recordId), 'id'));
    }

    public function test_候補は名前で絞り込み_選んだものとユーザーを持たない最上位の役割と無効のユーザーを出さない(): void
    {
        $role = $this->createRole('候補の役割');
        $active = $this->createUser('候補の人', 'cand@example.com', $role);
        $chosen = $this->createUser('候補の人（選択済み）', 'chosen@example.com', $role);
        $this->createUser('候補の人（無効）', 'off@example.com', $role, 'Inactive');

        $users = NotificationBlock_Target_Model::searchCandidates('user', self::PREFIX . '候補の人', [(string) $chosen]);
        $this->assertSame([(string) $active], array_column($users['items'], 'id'));
        $this->assertFalse($users['more']);

        $roles = NotificationBlock_Target_Model::searchCandidates('role', '', []);
        $this->assertNotContains('H1', array_column($roles['items'], 'id'));
        $this->assertContains($role, array_column($roles['items'], 'id'));
    }
}
