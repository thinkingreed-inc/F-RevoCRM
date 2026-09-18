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

namespace Tests\Integration\Calendar;

use Calendar_RepeatEvents;
use PearDatabase;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/modules/Calendar/RepeatEvents.php';

/**
 * 繰り返し活動の系列解決 — Calendar_RepeatEvents::resolveSeries()
 *
 * 繰り返し活動の更新は vtiger_activity_recurring_info に登録された系列を辿って行う。
 * 招待された参加者用にコピーされた活動（invitee_parentid が自分以外）はこの表に
 * 登録されないため、招待コピーを起点に更新すると系列を引けず、その回しか更新されない。
 * 論理削除済みの回が系列に残っていると、更新対象の突き合わせがずれて末尾の回に届かない。
 *
 * 検証用のレコード（FIRST_ID 以降）だけを操作し、既存データには触らない。
 */
final class RepeatEventsSeriesTest extends TestCase
{
    /** 検証用レコードの開始 ID。既存データと衝突しない範囲を使う */
    private const FIRST_ID = 990001;

    /** 系列の親（1 回目）*/
    private const PARENT_ID = 990001;

    /** 系列の 2 回目・3 回目 */
    private const SECOND_ID = 990003;
    private const THIRD_ID = 990005;

    /** 親の招待コピー（invitee_parentid = PARENT_ID）*/
    private const PARENT_INVITEE_ID = 990002;

    /** 2 回目の招待コピー（invitee_parentid = SECOND_ID）*/
    private const SECOND_INVITEE_ID = 990004;

    /** どの系列にも属さない単発の活動 */
    private const STANDALONE_ID = 990009;

    private ?PearDatabase $db = null;

    private function db(): PearDatabase
    {
        $db = $this->db;
        if (!$db instanceof PearDatabase) {
            $db = PearDatabase::getInstance();
            $this->db = $db;
        }

        return $db;
    }

    protected function setUp(): void
    {
        $this->cleanUp();

        // 3 回分の繰り返し活動。1 回目と 2 回目には招待コピーが 1 件ずつ付く
        $this->insertActivity(self::PARENT_ID, '2026-09-18', self::PARENT_ID);
        $this->insertActivity(self::PARENT_INVITEE_ID, '2026-09-18', self::PARENT_ID);
        $this->insertActivity(self::SECOND_ID, '2026-09-19', self::SECOND_ID);
        $this->insertActivity(self::SECOND_INVITEE_ID, '2026-09-19', self::SECOND_ID);
        $this->insertActivity(self::THIRD_ID, '2026-09-20', self::THIRD_ID);
        $this->insertActivity(self::STANDALONE_ID, '2026-09-21', self::STANDALONE_ID);

        foreach ([self::PARENT_ID, self::SECOND_ID, self::THIRD_ID] as $recurrenceId) {
            $this->insertRecurringInfo(self::PARENT_ID, $recurrenceId);
        }
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testResolvesSeriesFromParentRecord(): void
    {
        $series = Calendar_RepeatEvents::resolveSeries(self::PARENT_ID);

        $this->assertSame(self::PARENT_ID, $series['parentId']);
        $this->assertSame([self::PARENT_ID, self::SECOND_ID, self::THIRD_ID], $series['records']);
    }

    public function testResolvesSeriesFromChildRecord(): void
    {
        $series = Calendar_RepeatEvents::resolveSeries(self::SECOND_ID);

        $this->assertSame(self::PARENT_ID, $series['parentId']);
        $this->assertSame([self::PARENT_ID, self::SECOND_ID, self::THIRD_ID], $series['records']);
    }

    /**
     * 招待コピーは系列に登録されないため、invitee_parentid をたどって本体の系列を返す
     */
    public function testResolvesSeriesFromInviteeCopy(): void
    {
        $series = Calendar_RepeatEvents::resolveSeries(self::SECOND_INVITEE_ID);

        $this->assertSame(self::PARENT_ID, $series['parentId']);
        $this->assertSame([self::PARENT_ID, self::SECOND_ID, self::THIRD_ID], $series['records']);
    }

    /**
     * 論理削除済みの回が系列に残っていても、更新対象には含めない
     */
    public function testExcludesDeletedRecordsFromSeries(): void
    {
        $this->markDeleted(self::SECOND_ID);

        $series = Calendar_RepeatEvents::resolveSeries(self::PARENT_ID);

        $this->assertSame([self::PARENT_ID, self::THIRD_ID], $series['records']);
    }

    /**
     * 招待コピーを起点にしても、系列上の位置は招待元の回で判定する
     */
    public function testMapsInviteeCopyToItsSeriesRecord(): void
    {
        $series = Calendar_RepeatEvents::resolveSeries(self::SECOND_INVITEE_ID);

        $this->assertSame(self::SECOND_ID, $series['recordId']);
    }

    /**
     * 本体の回を起点にした場合は、そのまま自分自身が系列上の位置になる
     */
    public function testKeepsSeriesRecordForOwnRecord(): void
    {
        $series = Calendar_RepeatEvents::resolveSeries(self::SECOND_ID);

        $this->assertSame(self::SECOND_ID, $series['recordId']);
    }

    /**
     * 繰り返しでない活動では系列を返さない（新たな系列を作らせないため）
     */
    public function testReturnsEmptySeriesForStandaloneRecord(): void
    {
        $series = Calendar_RepeatEvents::resolveSeries(self::STANDALONE_ID);

        $this->assertNull($series['parentId']);
        $this->assertSame([], $series['records']);
    }

    /**
     * 削除した回は系列から取り除く。残りの回は系列に残す
     */
    public function testRemovesOnlyGivenRecordFromSeries(): void
    {
        Calendar_RepeatEvents::removeFromSeries(self::SECOND_ID);

        $this->assertSame([self::PARENT_ID, self::THIRD_ID], $this->seriesRecurrenceIds(self::PARENT_ID));
    }

    /**
     * 1 回目を削除しても、残りの回は同じ親の系列にとどまる
     */
    public function testKeepsRemainingRecordsWhenFirstOccurrenceIsRemoved(): void
    {
        Calendar_RepeatEvents::removeFromSeries(self::PARENT_ID);

        $this->assertSame([self::SECOND_ID, self::THIRD_ID], $this->seriesRecurrenceIds(self::PARENT_ID));
    }

    /**
     * @return list<int>
     */
    private function seriesRecurrenceIds(int $parentId): array
    {
        $db = $this->db();
        $result = $db->pquery('SELECT recurrenceid FROM vtiger_activity_recurring_info WHERE activityid = ? ORDER BY recurrenceid', [$parentId]);
        $ids = [];
        for ($i = 0; $i < $db->num_rows($result); $i++) {
            $ids[] = (int) $db->query_result($result, $i, 'recurrenceid');
        }

        return $ids;
    }

    private function insertActivity(int $id, string $dateStart, int $inviteeParentId): void
    {
        $db = $this->db();
        $db->pquery(
            'INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, modifiedby, setype, createdtime, modifiedtime, deleted)
             VALUES (?,?,?,?,?,NOW(),NOW(),0)',
            [$id, 1, 1, 1, 'Events']
        );
        $db->pquery(
            'INSERT INTO vtiger_activity (activityid, subject, activitytype, date_start, due_date, time_start, time_end, invitee_parentid, deleted)
             VALUES (?,?,?,?,?,?,?,?,0)',
            [$id, 'FRTestRepeat', 'Meeting', $dateStart, $dateStart, '10:00:00', '11:00:00', $inviteeParentId]
        );
    }

    private function insertRecurringInfo(int $activityId, int $recurrenceId): void
    {
        $this->db()->pquery(
            'INSERT INTO vtiger_activity_recurring_info (activityid, recurrenceid) VALUES (?,?)',
            [$activityId, $recurrenceId]
        );
    }

    private function markDeleted(int $id): void
    {
        $db = $this->db();
        $db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$id]);
        $db->pquery('UPDATE vtiger_activity SET deleted = 1 WHERE activityid = ?', [$id]);
    }

    private function cleanUp(): void
    {
        $db = $this->db();
        $db->pquery('DELETE FROM vtiger_activity_recurring_info WHERE activityid >= ? OR recurrenceid >= ?', [self::FIRST_ID, self::FIRST_ID]);
        $db->pquery('DELETE FROM vtiger_activity WHERE activityid >= ?', [self::FIRST_ID]);
        $db->pquery('DELETE FROM vtiger_crmentity WHERE crmid >= ?', [self::FIRST_ID]);
    }
}
