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

namespace Tests\Integration\Reports;

use ReportRun;
use Tests\Support\ReportsTestCase;

require_once dirname(__DIR__, 2) . '/Support/ReportsTestCase.php';

/**
 * 関連モジュールの基本情報（担当・最終更新者・登録者・作成日時・最終更新日時等） — CRMEntity::generateReportsSecQuery()
 *
 * #899 で vtiger_crmentity の基本情報は各モジュールの基本テーブルへ移行し、
 * 関連モジュールの vtiger_crmentity<Module> の結合も無くなった。
 * 一方、保存済みのレポート定義と ReportRun は vtiger_crmentity<Module> という別名を参照し続けるため、
 * generateReportsSecQuery() を上書きしていないモジュール（プロジェクト等）を関連モジュールにすると、
 * 担当・作成日時・最終更新日時・チェックボックス項目などを表示項目や条件に含めたレポートが
 * SQL エラーになっていた（issue #1914）。
 *
 * これらが基本テーブルの値で表示されること、vtiger_crmentity 側の値が食い違っていても
 * そちらは使わないこと、基本テーブルで削除済みのレコードは結合しないことを固定する。
 */
final class SecondaryModuleBaseInfoColumnTest extends ReportsTestCase
{
    private const OWNER_REPORT_ID = 990201;

    private const POTENTIAL_REPORT_ID = 990202;

    private const DATE_REPORT_ID = 990203;

    private const FILTER_REPORT_ID = 990204;

    private const CONTACT_ID = 990211;

    private const DELETED_PROJECT_CONTACT_ID = 990212;

    private const PROJECT_ID = 990221;

    private const DELETED_PROJECT_ID = 990222;

    private const POTENTIAL_ID = 990231;

    private const LASTNAME_COLUMN = 'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V';

    private const CREATED_TIME_COLUMN = 'vtiger_crmentityProject:createdtime:Project_Created_Time:createdtime:DT';

    /** 基本テーブル側の作成日時・最終更新日時 */
    private const BASE_CREATED_TIME = '2031-01-02 03:04:05';

    private const BASE_MODIFIED_TIME = '2031-02-03 04:05:06';

    /** vtiger_crmentity 側だけに入れる日時（基本テーブルとの食い違いを作る） */
    private const CRMENTITY_TIME = '2001-01-01 00:00:00';

    /** vtiger_crmentity 側だけに入れる別ユーザー（基本テーブルとの食い違いを作る） */
    private int $otherUserId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $result = $this->db->pquery("SELECT id FROM vtiger_users WHERE id <> ? AND status = 'Active' AND deleted = 0 ORDER BY id LIMIT 1", [self::ADMIN_USER_ID]);
        if ($result === false || $this->db->num_rows($result) === 0) {
            self::markTestSkipped('管理者以外の有効なユーザーがいないため実行しない');
        }
        $this->otherUserId = (int) $this->db->query_result($result, 0, 'id');

        $this->createContact(self::CONTACT_ID, self::PREFIX . 'owner');
        $this->createContact(self::DELETED_PROJECT_CONTACT_ID, self::PREFIX . 'deleted');
        $this->createProject(self::PROJECT_ID, self::CONTACT_ID, 0);
        $this->createProject(self::DELETED_PROJECT_ID, self::DELETED_PROJECT_CONTACT_ID, 1);

        $this->db->pquery(
            'UPDATE vtiger_project SET createdtime = ?, modifiedtime = ?, isconvertedfrompotential = 1 WHERE projectid = ?',
            [self::BASE_CREATED_TIME, self::BASE_MODIFIED_TIME, self::PROJECT_ID]
        );
        // 基本テーブルは管理者・上記の日時、vtiger_crmentity は別ユーザー・別日時・未削除にして食い違わせる
        $this->db->pquery(
            'UPDATE vtiger_crmentity SET smownerid = ?, modifiedby = ?, smcreatorid = ?, createdtime = ?, modifiedtime = ?, deleted = 0 WHERE crmid IN (?, ?)',
            [$this->otherUserId, $this->otherUserId, $this->otherUserId, self::CRMENTITY_TIME, self::CRMENTITY_TIME, self::PROJECT_ID, self::DELETED_PROJECT_ID]
        );
    }

    public function test_関連モジュールの担当_最終更新者_登録者が基本テーブルの値で表示される(): void
    {
        $this->createReport(
            self::OWNER_REPORT_ID,
            'Contacts',
            'Project',
            'Project::Project::linktoaccountscontacts',
            [
                self::LASTNAME_COLUMN,
                'vtiger_project:projectname:Project_Project_Name:projectname:V',
                'vtiger_usersProject:user_name:Project_Assigned_To:assigned_user_id:V',
                'vtiger_crmentityProject:modifiedby:Project_Last_Modified_By:modifiedby:V',
                'vtiger_crmentityProject:smcreatorid:Project_Created_By:creator:V',
            ]
        );

        $sql = $this->buildReportSql(ReportRun::getInstance(self::OWNER_REPORT_ID), self::OWNER_REPORT_ID);

        $rows = $this->rowsByLastname($this->fetchAll($sql));
        $admin = $this->userNames(self::ADMIN_USER_ID);
        $other = $this->userNames($this->otherUserId);

        $row = $rows[self::PREFIX . 'owner'] ?? [];
        self::assertSame(self::PREFIX . self::PROJECT_ID, $row['project_project_name'] ?? null, 'プロジェクトが結合されること');
        self::assertSame($admin['userlabel'], $row['project_assigned_to'] ?? null, '担当は基本テーブルの smownerid から表示する');
        foreach (['project_last_modified_by' => '最終更新者', 'project_created_by' => '登録者'] as $key => $label) {
            $value = $row[$key] ?? null;
            self::assertIsString($value, $label . 'が表示されること');
            self::assertStringContainsString($admin['last_name'], $value, $label . 'は基本テーブルの値から表示する');
            self::assertStringNotContainsString($other['last_name'], $value, $label . 'に vtiger_crmentity 側の値を使わない');
        }

        $deletedRow = $rows[self::PREFIX . 'deleted'] ?? [];
        self::assertArrayHasKey('project_project_name', $deletedRow, '削除済みプロジェクトの顧客担当者も主モジュールとしては表示される');
        self::assertEmpty($deletedRow['project_project_name'], '基本テーブルで削除済みのプロジェクトは結合しない');
        self::assertEmpty($deletedRow['project_assigned_to'], '基本テーブルで削除済みのプロジェクトの担当は表示しない');

        $this->assertDoesNotJoinCrmentity($sql);
    }

    public function test_関連モジュールの作成日時_最終更新日時_チェックボックス項目が基本テーブルの値で表示される(): void
    {
        $this->createReport(
            self::DATE_REPORT_ID,
            'Contacts',
            'Project',
            'Project::Project::linktoaccountscontacts',
            [
                self::LASTNAME_COLUMN,
                self::CREATED_TIME_COLUMN,
                'vtiger_crmentityProject:modifiedtime:Project_Modified_Time:modifiedtime:DT',
                'vtiger_project:isconvertedfrompotential:Project_Is_Converted_From_Opportunity:isconvertedfrompotential:C',
            ]
        );

        $sql = $this->buildReportSql(ReportRun::getInstance(self::DATE_REPORT_ID), self::DATE_REPORT_ID);
        $rows = $this->rowsByLastname($this->fetchAll($sql));

        $row = $rows[self::PREFIX . 'owner'] ?? [];
        self::assertSame(self::BASE_CREATED_TIME, $row['project_created_time'] ?? null, '作成日時は基本テーブルの値から表示する');
        self::assertSame(self::BASE_MODIFIED_TIME, $row['project_modified_time'] ?? null, '最終更新日時は基本テーブルの値から表示する');
        self::assertSame('yes', $row['project_is_converted_from_opportunity'] ?? null, 'チェックボックス項目が表示されること');

        $deletedRow = $rows[self::PREFIX . 'deleted'] ?? [];
        self::assertArrayHasKey('project_created_time', $deletedRow, '削除済みプロジェクトの顧客担当者も主モジュールとしては表示される');
        self::assertEmpty($deletedRow['project_created_time'], '基本テーブルで削除済みのプロジェクトの作成日時は表示しない');
        self::assertSame('-', $deletedRow['project_is_converted_from_opportunity'] ?? null, '関連レコードが無い場合のチェックボックス項目は「-」');

        $this->assertDoesNotJoinCrmentity($sql);
    }

    public function test_関連モジュールの作成日時を絞り込み条件に使える(): void
    {
        $this->createReport(
            self::FILTER_REPORT_ID,
            'Contacts',
            'Project',
            'Project::Project::linktoaccountscontacts',
            [self::LASTNAME_COLUMN],
            [['columnname' => self::CREATED_TIME_COLUMN, 'comparator' => 'ny']]
        );

        $sql = $this->buildReportSql(ReportRun::getInstance(self::FILTER_REPORT_ID), self::FILTER_REPORT_ID);
        $rows = $this->rowsByLastname($this->fetchAll($sql));

        self::assertSame(
            [self::PREFIX . 'owner'],
            array_keys($rows),
            '作成日時が空でないプロジェクトを持つ顧客担当者だけが返り、基本テーブルで削除済みのプロジェクトは対象外'
        );
        $this->assertDoesNotJoinCrmentity($sql);
    }

    public function test_generateReportsSecQueryを上書きしているモジュールの担当も表示される(): void
    {
        $this->createRecord('Potentials', self::POTENTIAL_ID, 'vtiger_potential', 'potentialid', [
            'potential_no' => self::PREFIX . self::POTENTIAL_ID,
            'potentialname' => self::PREFIX . self::POTENTIAL_ID,
            'contact_id' => self::CONTACT_ID,
        ]);
        $this->insertRow('vtiger_potentialscf', 'potentialid', self::POTENTIAL_ID, []);
        $this->createReport(
            self::POTENTIAL_REPORT_ID,
            'Contacts',
            'Potentials',
            'Potentials::Potentials::contact_id',
            [
                self::LASTNAME_COLUMN,
                'vtiger_usersPotentials:user_name:Potentials_Assigned_To:assigned_user_id:V',
            ]
        );

        $sql = $this->buildReportSql(ReportRun::getInstance(self::POTENTIAL_REPORT_ID), self::POTENTIAL_REPORT_ID);
        $rows = $this->rowsByLastname($this->fetchAll($sql));

        self::assertSame(
            $this->userNames(self::ADMIN_USER_ID)['userlabel'],
            $rows[self::PREFIX . 'owner']['potentials_assigned_to'] ?? null,
            '案件の担当が表示されること'
        );
    }

    /** 関連モジュール（プロジェクト）の基本情報のために vtiger_crmentity 自体を結合しないこと */
    private function assertDoesNotJoinCrmentity(string $sql): void
    {
        self::assertDoesNotMatchRegularExpression(
            '/join\s+vtiger_crmentity\s+as\s+vtiger_crmentityProject\b/i',
            $sql,
            '関連モジュールの基本情報は vtiger_crmentity ではなく基本テーブルから取得する'
        );
    }

    private function createProject(int $id, int $contactId, int $deleted): void
    {
        $this->createRecord('Project', $id, 'vtiger_project', 'projectid', [
            'project_no' => self::PREFIX . $id,
            'projectname' => self::PREFIX . $id,
            'linktoaccountscontacts' => $contactId,
        ], self::ADMIN_USER_ID, $deleted);
        $this->insertRow('vtiger_projectcf', 'projectid', $id, []);
    }

    /**
     * テストで投入した顧客担当者の行だけを、姓をキーにして返す
     *
     * @param list<array<string, mixed>> $rows
     * @return array<string, array<string, mixed>>
     */
    private function rowsByLastname(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $lastname = $row['contacts_last_name'] ?? null;
            if (is_string($lastname) && str_starts_with($lastname, self::PREFIX)) {
                self::assertArrayNotHasKey($lastname, $result, '顧客担当者が重複して返らないこと');
                $result[$lastname] = $row;
            }
        }

        return $result;
    }

    /**
     * @return array{userlabel: string, last_name: string}
     */
    private function userNames(int $userId): array
    {
        $result = $this->db->pquery('SELECT userlabel, last_name FROM vtiger_users WHERE id = ?', [$userId]);
        self::assertNotFalse($result);

        return [
            'userlabel' => (string) $this->db->query_result($result, 0, 'userlabel'),
            'last_name' => (string) $this->db->query_result($result, 0, 'last_name'),
        ];
    }
}
