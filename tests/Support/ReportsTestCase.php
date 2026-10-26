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

namespace Tests\Support;

use CRMEntity;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use ReportRun;

/**
 * レポートの結合テストの共通基盤
 *
 * レポート定義（vtiger_report 等）とレコードをテスト用DBへ直接投入し、
 * ReportRun が組み立てる SQL とその実行結果を検証する。
 * 投入したデータは既存データと衝突しない ID 帯を使い、tearDown() でまとめて消す。
 */
abstract class ReportsTestCase extends TestCase
{
    /** 管理者ユーザーID */
    public const ADMIN_USER_ID = 1;

    /** テストデータの目印 */
    public const PREFIX = 'SPECTEST_';

    /** F-RevoCRM の初期化を1度だけ行うためのフラグ */
    private static bool $initialized = false;

    protected PearDatabase $db;

    /** @var list<int> 投入したレポートID */
    private array $reportIds = [];

    /** @var list<array{0: string, 1: string, 2: int}> 投入したレコード（テーブル名, ID列名, ID） */
    private array $records = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    /**
     * テスト用 DB につながらなければ、このクラスのテストをまるごと飛ばす
     *
     * CI（.github/workflows/phpunit.yml）は DB を立てずに実行するため、理由を添えて skip する。
     */
    private static function requireTestDatabase(): void
    {
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_tab', []);
            $count = ($result !== false && $db->num_rows($result) === 1)
                ? (int) $db->query_result($result, 0, 'cnt')
                : 0;
        } catch (\Throwable $e) {
            $count = 0;
        }
        if ($count === 0) {
            self::markTestSkipped(
                'テスト用DBに接続できないか初期データがないため飛ばします（tests/README.md のテスト用DBを参照）'
            );
        }
    }

    /**
     * F-RevoCRM 本体を読み込む
     *
     * tests/bootstrap.php が Vtiger_Loader のオートロードを外しているため、ここで戻す。
     */
    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 2);

        if (class_exists('Vtiger_Loader', false)) {
            spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        }

        require_once $root . '/include/utils/utils.php';
        require_once $root . '/include/database/PearDatabase.php';
        vimport('includes.runtime.Globals');
        vimport('includes.runtime.LanguageHandler');
        vimport('includes.runtime.BaseModel');
        vimport('includes.runtime.Controller');
        vimport('includes.http.Request');
        vimport('includes.http.Response');
        vimport('includes.exceptions.AppException');

        require_once $root . '/modules/Users/Users.php';
        require_once $root . '/vtlib/Vtiger/Module.php';
        require_once $root . '/modules/Reports/ReportRun.php';

        date_default_timezone_set('Asia/Tokyo');
        self::$initialized = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAsAdmin();
    }

    protected function tearDown(): void
    {
        foreach ($this->reportIds as $reportId) {
            $this->deleteReport($reportId);
        }
        foreach (array_reverse($this->records) as [$table, $idColumn, $id]) {
            $this->db->pquery("DELETE FROM $table WHERE $idColumn = ?", [$id]);
        }
        $this->reportIds = [];
        $this->records = [];
        parent::tearDown();
    }

    private function loginAsAdmin(): void
    {
        $currentUser = CRMEntity::getInstance('Users');
        $currentUser->id = self::ADMIN_USER_ID;
        $currentUser->retrieve_entity_info(self::ADMIN_USER_ID, 'Users');
        $GLOBALS['current_user'] = $currentUser;
    }

    // ---- レポート定義 ---------------------------------------------------

    /**
     * 表形式のレポート定義を投入する
     *
     * @param list<string> $columns 表示項目（vtiger_selectcolumn.columnname の形式）
     * @param list<array{columnname: string, comparator: string, value?: string}> $criteria 絞り込み条件（全て AND、グループ1）
     */
    protected function createReport(
        int $reportId,
        string $primaryModule,
        string $secondaryModule,
        string $joinColumn,
        array $columns,
        array $criteria = []
    ): void {
        $this->reportIds[] = $reportId;
        $this->deleteReport($reportId);

        $this->db->pquery('INSERT INTO vtiger_selectquery (queryid, startindex, numofobjects) VALUES (?, 0, 0)', [$reportId]);
        $this->db->pquery(
            'INSERT INTO vtiger_report (reportid, folderid, reportname, description, reporttype, queryid, state, customizable, category, owner, sharingtype)'
            . " VALUES (?, 1, ?, '', 'tabular', ?, 'CUSTOM', 1, 1, ?, 'Public')",
            [$reportId, self::PREFIX . $reportId, $reportId, self::ADMIN_USER_ID]
        );
        $this->db->pquery(
            'INSERT INTO vtiger_reportmodules (reportmodulesid, primarymodule, secondarymodules, join_column) VALUES (?, ?, ?, ?)',
            [$reportId, $primaryModule, $secondaryModule, $joinColumn]
        );
        foreach ($columns as $index => $column) {
            $this->db->pquery('INSERT INTO vtiger_selectcolumn (queryid, columnindex, columnname) VALUES (?, ?, ?)', [$reportId, $index, $column]);
        }
        foreach ($criteria as $index => $criterion) {
            $columnCondition = $index < count($criteria) - 1 ? 'and' : '';
            $this->db->pquery(
                'INSERT INTO vtiger_relcriteria (queryid, columnindex, columnname, comparator, value, groupid, column_condition) VALUES (?, ?, ?, ?, ?, 1, ?)',
                [$reportId, $index, $criterion['columnname'], $criterion['comparator'], $criterion['value'] ?? '', $columnCondition]
            );
        }
        if ($criteria !== []) {
            $this->db->pquery(
                "INSERT INTO vtiger_relcriteria_grouping (groupid, queryid, group_condition, condition_expression) VALUES (1, ?, '', ?)",
                [$reportId, implode(' and ', array_keys($criteria))]
            );
        }
    }

    private function deleteReport(int $reportId): void
    {
        foreach ([
            'vtiger_relcriteria_grouping' => 'queryid',
            'vtiger_relcriteria' => 'queryid',
            'vtiger_selectcolumn' => 'queryid',
            'vtiger_reportmodules' => 'reportmodulesid',
            'vtiger_report' => 'reportid',
            'vtiger_selectquery' => 'queryid',
        ] as $table => $idColumn) {
            $this->db->pquery("DELETE FROM $table WHERE $idColumn = ?", [$reportId]);
        }
    }

    /**
     * レポートの SQL を組み立てる
     *
     * @param string|false $runtimeFilterSql 詳細画面で抽出条件を変更した場合の条件（RunTimeAdvFilter の戻り値）
     */
    protected function buildReportSql(ReportRun $reportRun, int $reportId, $runtimeFilterSql = false): string
    {
        return (string) $reportRun->sGetSQLforReport($reportId, $runtimeFilterSql, 'HTML');
    }

    /**
     * SQL を実行し、全行を返す。SQL エラーはテスト失敗にする
     *
     * @return list<array<string, mixed>>
     */
    protected function fetchAll(string $sql): array
    {
        $dieOnError = $this->db->dieOnError;
        $this->db->dieOnError = false;
        try {
            $result = $this->db->pquery($sql, []);
        } finally {
            $this->db->dieOnError = $dieOnError;
        }
        self::assertNotFalse($result, 'レポートの SQL がエラーになった: ' . $this->db->database->ErrorMsg() . "\n" . $sql);

        $rows = [];
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $rows[] = $row;
        }

        return $rows;
    }

    // ---- レコード -------------------------------------------------------

    /**
     * vtiger_crmentity と基本テーブルにレコードを投入する
     *
     * #899 以降、基本情報は基本テーブル側が正のため、両方に同じ値を入れる。
     *
     * @param array<string, int|string|null> $baseColumns 基本テーブル固有の列
     */
    protected function createRecord(
        string $module,
        int $id,
        string $baseTable,
        string $baseIdColumn,
        array $baseColumns,
        int $ownerId = self::ADMIN_USER_ID,
        int $deleted = 0
    ): void {
        $now = date('Y-m-d H:i:s');
        $common = [
            'smcreatorid' => $ownerId,
            'smownerid' => $ownerId,
            'modifiedby' => $ownerId,
            'createdtime' => $now,
            'modifiedtime' => $now,
            'deleted' => $deleted,
            'label' => self::PREFIX . $id,
        ];
        $this->insertRow('vtiger_crmentity', 'crmid', $id, ['setype' => $module] + $common);
        $this->insertRow($baseTable, $baseIdColumn, $id, $baseColumns + $common);
    }

    /**
     * 1行投入し、後始末の対象に加える
     *
     * @param array<string, int|string|null> $columns
     */
    protected function insertRow(string $table, string $idColumn, int $id, array $columns): void
    {
        $this->db->pquery("DELETE FROM $table WHERE $idColumn = ?", [$id]);
        $columns = [$idColumn => $id] + $columns;
        $this->db->pquery(
            "INSERT INTO $table (" . implode(', ', array_keys($columns)) . ') VALUES (' . generateQuestionMarks($columns) . ')',
            array_values($columns)
        );
        $this->records[] = [$table, $idColumn, $id];
    }

    /** 顧客担当者を1件投入する */
    protected function createContact(int $id, string $lastname): void
    {
        $this->createRecord('Contacts', $id, 'vtiger_contactdetails', 'contactid', [
            'contact_no' => self::PREFIX . $id,
            'lastname' => $lastname,
        ]);
        $this->insertRow('vtiger_contactscf', 'contactid', $id, []);
    }
}
