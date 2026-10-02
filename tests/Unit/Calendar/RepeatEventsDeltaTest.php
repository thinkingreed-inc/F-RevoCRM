<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Calendar;

use Calendar_RepeatEvents;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/modules/Calendar/RepeatEvents.php';

/**
 * 繰り返し活動の更新で「どのフィールドを他の回へコピーするか」を決める差分計算。
 *
 * 従来は VTEntityDelta が保持する保存前データを使っていたが、保存後に動く
 * ワークフロー（VTUpdateFieldsTask）が vtiger.entity.beforesave を再発火して
 * 保存前データを保存後の値で上書きするため、差分が空になり他の回へ何も
 * 反映されなかった。保存前に自前で控えたスナップショットから差分を出す。
 */
final class RepeatEventsDeltaTest extends TestCase
{
    public function testChangedFieldIsInDelta(): void
    {
        $delta = Calendar_RepeatEvents::computeDeltaFromSnapshot(
            ['subject' => 'test', 'time_start' => '10:00:00'],
            ['subject' => 'test', 'time_start' => '11:00:00']
        );

        self::assertArrayHasKey('time_start', $delta);
        self::assertSame('10:00:00', $delta['time_start']['oldValue']);
        self::assertSame('11:00:00', $delta['time_start']['currentValue']);
    }

    public function testUnchangedFieldIsNotInDelta(): void
    {
        $delta = Calendar_RepeatEvents::computeDeltaFromSnapshot(
            ['subject' => 'test', 'time_start' => '10:00:00'],
            ['subject' => 'test', 'time_start' => '11:00:00']
        );

        self::assertArrayNotHasKey('subject', $delta);
    }

    /**
     * 保存前スナップショットを取れなかった場合は、値のあるフィールドをすべて
     * 変更扱いにする。VTEntityDelta が保存前データを持たないときと同じ挙動
     */
    public function testEmptySnapshotMarksEveryFilledFieldAsChanged(): void
    {
        $delta = Calendar_RepeatEvents::computeDeltaFromSnapshot(
            [],
            ['subject' => 'test', 'time_start' => '11:00:00', 'description' => '']
        );

        self::assertArrayHasKey('subject', $delta);
        self::assertArrayHasKey('time_start', $delta);
        self::assertArrayNotHasKey('description', $delta);
    }

    /**
     * 改行コードの違いだけの値は変更とみなさない（VTEntityDelta と同じ比較）
     */
    public function testNewlineOnlyDifferenceIsNotAChange(): void
    {
        $delta = Calendar_RepeatEvents::computeDeltaFromSnapshot(
            ['description' => "a\r\nb"],
            ['description' => "a\nb"]
        );

        self::assertArrayNotHasKey('description', $delta);
    }

    /**
     * 保存後のデータに無いフィールドは対象外。コピー元は保存後の値であり、
     * スナップショットにしか無いフィールドを持ち出すと他の回に古い値が移る
     */
    public function testFieldMissingFromCurrentDataIsIgnored(): void
    {
        $delta = Calendar_RepeatEvents::computeDeltaFromSnapshot(
            ['subject' => 'test', 'removed_field' => 'x'],
            ['subject' => 'test']
        );

        self::assertArrayNotHasKey('removed_field', $delta);
    }

    public function testNormalizeSnapshotKeepsPlainArray(): void
    {
        self::assertSame(
            ['subject' => 'test'],
            Calendar_RepeatEvents::normalizeSnapshot(['subject' => 'test'])
        );
    }

    /**
     * CRMEntity の column_fields は TrackableObject のことがある。
     * 差分計算では素の配列として扱う
     */
    public function testNormalizeSnapshotUnwrapsColumnFieldsObject(): void
    {
        $columnFields = new class () {
            /**
             * @return array<string, string>
             */
            public function getColumnFields(): array
            {
                return ['subject' => 'test', 'time_start' => '10:00:00'];
            }
        };

        self::assertSame(
            ['subject' => 'test', 'time_start' => '10:00:00'],
            Calendar_RepeatEvents::normalizeSnapshot($columnFields)
        );
    }

    /**
     * スナップショットを取れなかった場合は null。
     * 差分計算側が「保存前データなし」として扱えるようにする
     */
    public function testNormalizeSnapshotReturnsNullForUnusableValue(): void
    {
        self::assertNull(Calendar_RepeatEvents::normalizeSnapshot(null));
        self::assertNull(Calendar_RepeatEvents::normalizeSnapshot(false));
        self::assertNull(Calendar_RepeatEvents::normalizeSnapshot('x'));
    }

    /**
     * 空だった値に入力された場合も変更として扱う
     */
    public function testFilledFromEmptyIsAChange(): void
    {
        $delta = Calendar_RepeatEvents::computeDeltaFromSnapshot(
            ['location' => ''],
            ['location' => '会議室A']
        );

        self::assertArrayHasKey('location', $delta);
        self::assertSame('会議室A', $delta['location']['currentValue']);
    }
}
