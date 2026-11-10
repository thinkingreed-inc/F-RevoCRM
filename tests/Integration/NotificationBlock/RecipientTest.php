<?php

declare(strict_types=1);

namespace Tests\Integration\NotificationBlock;

use NotificationBlock_Recipient_Model;
use Tests\Support\NotificationBlockTestCase;

require_once dirname(__DIR__, 2) . '/Support/NotificationBlockTestCase.php';

/**
 * 宛先の展開（仕様 動作定義 No.11、確認事項 Q9）
 */
final class RecipientTest extends NotificationBlockTestCase
{
    public function test_役割はその役割のユーザーだけで下位の役割を含まない(): void
    {
        $parent = $this->createRole('親');
        $child = $this->createRole('子', $parent);
        $parentUser = $this->createUser('親の人', 'parent@example.com', $parent);
        $this->createUser('子の人', 'child@example.com', $child);

        $expanded = NotificationBlock_Recipient_Model::expand([['type' => 'role', 'id' => $parent]]);

        $this->assertSame([$parentUser], array_column($expanded['recipients'], 'userid'));
    }

    public function test_グループはユーザー・役割・下位を含む役割・入れ子のグループを展開し_互いに入れ合っていても止まる(): void
    {
        $parent = $this->createRole('親');
        $child = $this->createRole('子', $parent);
        $other = $this->createRole('別');
        $direct = $this->createUser('直接', 'direct@example.com', $other);
        $parentUser = $this->createUser('親の人', 'parent@example.com', $parent);
        $childUser = $this->createUser('子の人', 'child@example.com', $child);
        $roleUser = $this->createUser('役割の人', 'role@example.com', $other);

        $inner = $this->createGroup('内側', [], [], [$parent]);
        $outer = $this->createGroup('外側', [$direct], [$other], [], [$inner]);
        // 内側から外側も入れて、互いに入れ合う形にする
        $this->addGroupToGroup($inner, $outer);

        $expanded = NotificationBlock_Recipient_Model::expand([['type' => 'group', 'id' => (string) $outer]]);
        $ids = array_column($expanded['recipients'], 'userid');
        sort($ids);
        $expected = [$direct, $parentUser, $childUser, $roleUser];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function test_無効・削除したユーザーとメールアドレスの無いユーザーは除き_除いた人数を数える(): void
    {
        $role = $this->createRole('役割');
        $active = $this->createUser('有効', 'active@example.com', $role);
        $inactive = $this->createUser('無効', 'inactive@example.com', $role, 'Inactive');
        $deleted = $this->createUser('削除', 'deleted@example.com', $role, 'Inactive', 1);
        $noEmail = $this->createUser('メールなし', '', $role);

        $expanded = NotificationBlock_Recipient_Model::expand([
            ['type' => 'user', 'id' => (string) $active],
            ['type' => 'user', 'id' => (string) $inactive],
            ['type' => 'user', 'id' => (string) $deleted],
            ['type' => 'user', 'id' => (string) $noEmail],
            // 完全に削除したユーザー（行が無い）は数えない
            ['type' => 'user', 'id' => '99999999'],
        ]);

        $this->assertSame([$active], array_column($expanded['recipients'], 'userid'));
        $this->assertSame(['disabled' => 2, 'no_email' => 1], $expanded['excluded']);
    }

    public function test_同じユーザーと同じメールアドレスは1通にまとめる(): void
    {
        $role = $this->createRole('役割');
        $user = $this->createUser('重複', 'dup@example.com', $role);
        $sameAddressA = $this->createUser('同じアドレスA', 'same@example.com', $role);
        $this->createUser('同じアドレスB', 'SAME@example.com', $role);
        $group = $this->createGroup('グループ', [$user]);

        $expanded = NotificationBlock_Recipient_Model::expand([
            ['type' => 'role', 'id' => $role],
            ['type' => 'group', 'id' => (string) $group],
            ['type' => 'user', 'id' => (string) $user],
        ]);
        $emails = array_map('strtolower', array_column($expanded['recipients'], 'email'));
        sort($emails);

        $this->assertSame(['dup@example.com', 'same@example.com'], $emails);
        $this->assertContains($sameAddressA, array_column($expanded['recipients'], 'userid'), 'どちらか 1 人に送る（先に出てきた人）');
    }
}
