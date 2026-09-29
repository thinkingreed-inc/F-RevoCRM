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

namespace Tests\Integration\ModTracker;

use ModTracker_Record_Model;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Vtiger_Paging_Model;

require_once dirname(__DIR__, 2) . '/Support/ModTrackerTestStubs.php';

/**
 * 更新履歴の取得順とページング — ModTracker_Record_Model::getUpdates()
 *
 * 更新履歴を changedon だけで並べると、同じ秒に記録された作成と更新の順序が不定になり、
 * 作成が一覧の途中に混ざる（issue #1474 ②）。単調増加する id を副キーに加えて順序を固定する。
 *
 * あわせて、ページ番号とページ件数から決まる LIMIT の開始位置が、
 * 続きを読み込んだときに重複も欠落も生まないことを固定する（同 ①）。
 * 画面側（Detail.js）は「表示済み件数 ÷ 追加読み込み件数 + 1」で次のページ番号を決めるため、
 * 初回のページ件数が異なっても続きが連続することがこのテストの前提になる。
 *
 * 検証用に投入したレコード（CRM_ID の履歴）だけを操作し、既存データには触らない。
 */
final class RecentActivitiesOrderTest extends TestCase
{
    /** 履歴をぶら下げる対象レコード。既存データと衝突しない ID 帯を使う */
    private const CRM_ID = 991001;

    private const MODULE = 'Accounts';

    private const WHO_DID = 1;

    /** 投入する履歴の先頭 id。ここから連番で FIXTURE_COUNT 件を作る */
    private const FIRST_ID = 991101;

    private const FIXTURE_COUNT = 25;

    /** 先頭から3件は同一 changedon にする（作成と、同じ秒に入った更新2件） */
    private const SAME_TIMESTAMP_COUNT = 3;

    private const BASE_TIME = '2026-01-01 10:00:00';

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
            $result = $this->db()->pquery('SELECT 1 AS ok FROM vtiger_modtracker_basic LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_modtracker_basic がないため実行しない');
        }
    }

    protected function setUp(): void
    {
        $this->skipUnlessDatabaseIsAvailable();

        $this->cleanUp();

        // 先頭の SAME_TIMESTAMP_COUNT 件は同じ changedon。残りは 1 秒ずつ新しくする。
        // id が大きいほど新しい履歴になるよう並べる（作成が最も古い）。
        for ($offset = 0; $offset < self::FIXTURE_COUNT; $offset++) {
            $secondsFromBase = max(0, $offset - (self::SAME_TIMESTAMP_COUNT - 1));
            $this->insertHistory(
                self::FIRST_ID + $offset,
                date('Y-m-d H:i:s', strtotime(self::BASE_TIME) + $secondsFromBase),
                $offset === 0 ? ModTracker_Record_Model::CREATE : ModTracker_Record_Model::UPDATE
            );
        }
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    /**
     * 同じ changedon の履歴は id の降順になり、最も古い作成が末尾に来る
     */
    public function testOrdersHistoryByTimestampThenIdDescending(): void
    {
        $records = $this->getUpdates(1, self::FIXTURE_COUNT);

        $this->assertSame($this->expectedIdsDescending(), $this->idsOf($records));

        $oldest = $this->lastOf($records);
        $this->assertTrue($oldest->isCreate(), '一覧の末尾は作成の履歴になる');
        $this->assertSame(self::FIRST_ID, (int) $oldest->get('id'));
    }

    /**
     * 続きを読み込んだときに、重複も欠落も起きない
     *
     * 更新履歴タブは list_max_entries_per_page 件（既定20）を表示し、
     * 「その他」は 5 件ずつ追加で読み込む。表示済み 20 件の続きは page=5&limit=5 になる。
     */
    public function testLoadingMoreContinuesWithoutGapOrDuplicate(): void
    {
        $firstPage = $this->getUpdates(1, 20);
        $this->assertCount(20, $firstPage);

        // 表示済み 20 件 ÷ 追加読み込み 5 件 + 1 = 5 ページ目
        $secondPage = $this->getUpdates(5, 5);
        $this->assertCount(5, $secondPage);

        $loadedIds = array_merge($this->idsOf($firstPage), $this->idsOf($secondPage));

        $this->assertSame($this->expectedIdsDescending(), $loadedIds);
        $this->assertSame($loadedIds, array_values(array_unique($loadedIds)), '同じ履歴が二重に返らない');
    }

    /**
     * 初回のページ件数が違っても、同じ算出で続きが連続する
     *
     * list_max_entries_per_page を 10 に変更した環境（初回10件表示）を想定する。
     * 表示済み 10 件 ÷ 追加読み込み 5 件 + 1 = 3 ページ目が続きになる。
     */
    public function testLoadingMoreIsIndependentFromFirstPageSize(): void
    {
        $firstPage = $this->getUpdates(1, 10);
        $secondPage = $this->getUpdates(3, 5);

        $loadedIds = array_merge($this->idsOf($firstPage), $this->idsOf($secondPage));

        $this->assertSame(array_slice($this->expectedIdsDescending(), 0, 15), $loadedIds);
    }

    /**
     * @return array<int, ModTracker_Record_Model>
     */
    private function getUpdates(int $page, int $limit): array
    {
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', $page);
        $pagingModel->set('limit', $limit);

        return ModTracker_Record_Model::getUpdates(self::CRM_ID, $pagingModel, self::MODULE);
    }

    /**
     * @param array<int, ModTracker_Record_Model> $records
     */
    private function lastOf(array $records): ModTracker_Record_Model
    {
        $last = end($records);
        if (!$last instanceof ModTracker_Record_Model) {
            self::fail('更新履歴が1件も返らなかった');
        }

        return $last;
    }

    /**
     * @param array<int, ModTracker_Record_Model> $records
     *
     * @return array<int, int>
     */
    private function idsOf(array $records): array
    {
        return array_map(static fn ($record) => (int) $record->get('id'), array_values($records));
    }

    /**
     * 投入した履歴を新しい順に並べた id。changedon が同じ組も id の降順になる
     *
     * @return array<int, int>
     */
    private function expectedIdsDescending(): array
    {
        return array_map(
            static fn (int $offset): int => self::FIRST_ID + $offset,
            array_reverse(range(0, self::FIXTURE_COUNT - 1))
        );
    }

    private function insertHistory(int $id, string $changedOn, int $status): void
    {
        $this->db()->pquery(
            'INSERT INTO vtiger_modtracker_basic (id, crmid, module, whodid, changedon, status) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, self::CRM_ID, self::MODULE, self::WHO_DID, $changedOn, $status]
        );
    }

    private function cleanUp(): void
    {
        $this->db()->pquery('DELETE FROM vtiger_modtracker_basic WHERE crmid = ?', [self::CRM_ID]);
    }
}
