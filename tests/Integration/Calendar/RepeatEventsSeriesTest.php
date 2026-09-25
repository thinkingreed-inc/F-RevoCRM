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
 * 検証用に投入したレコード（FIXTURE_IDS）だけを操作し、既存データには触らない。
 */
final class RepeatEventsSeriesTest extends TestCase
{
    /** 系列の親（1 回目）。既存データと衝突しない ID 帯を使う */
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

    /** 繰り返しに変更した直後の活動（系列は自分自身の 1 件だけ）*/
    private const NEW_SERIES_ID = 990011;

    /** 検証用に投入するレコードの ID。片付けはこの集合だけを対象にする */
    private const FIXTURE_IDS = [
        self::PARENT_ID,
        self::PARENT_INVITEE_ID,
        self::SECOND_ID,
        self::SECOND_INVITEE_ID,
        self::THIRD_ID,
        self::STANDALONE_ID,
        self::NEW_SERIES_ID,
    ];

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

    /**
     * テスト用DB が無い環境（CI など）では実行しない。
     * 接続できないまま進めると PearDatabase が false のまま mysqli に渡され、
     * スキップではなくエラーとして落ちるため、先に接続を確かめる
     */
    private function skipUnlessDatabaseIsAvailable(): void
    {
        try {
            $result = $this->db()->pquery('SELECT 1 AS ok FROM vtiger_activity LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_activity がないため実行しない');
        }
    }

    protected function setUp(): void
    {
        $this->skipUnlessDatabaseIsAvailable();

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
     * 繰り返しでない活動を繰り返しに変更した直後は、系列が自分自身の 1 件だけになる。
     * repeat() はこの形を「これから 2 回目以降を作る段階」と判定して処理を続けるため、
     * 系列が空でないこと・構成が自分自身 1 件であることを保証する
     */
    public function testReturnsSelfOnlySeriesForNewlyRecurringRecord(): void
    {
        $this->insertActivity(self::NEW_SERIES_ID, '2026-09-22', self::NEW_SERIES_ID);
        $this->insertRecurringInfo(self::NEW_SERIES_ID, self::NEW_SERIES_ID);

        $series = Calendar_RepeatEvents::resolveSeries(self::NEW_SERIES_ID);

        $this->assertSame(self::NEW_SERIES_ID, $series['parentId']);
        $this->assertSame(self::NEW_SERIES_ID, $series['recordId']);
        $this->assertSame([self::NEW_SERIES_ID], $series['records']);
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

    /**
     * 投入した検証用レコードだけを片付ける。
     * ID 範囲での一括削除にすると、テスト用 DB を本番・開発の複製から作った環境で
     * 無関係なデータを消すおそれがあるため、対象は FIXTURE_IDS に限定する
     */
    private function cleanUp(): void
    {
        $db = $this->db();
        $ids = self::FIXTURE_IDS;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $db->pquery(
            "DELETE FROM vtiger_activity_recurring_info WHERE activityid IN ($placeholders) OR recurrenceid IN ($placeholders)",
            array_merge($ids, $ids)
        );
        $db->pquery("DELETE FROM vtiger_activity WHERE activityid IN ($placeholders)", $ids);
        $db->pquery("DELETE FROM vtiger_crmentity WHERE crmid IN ($placeholders)", $ids);
    }
}
