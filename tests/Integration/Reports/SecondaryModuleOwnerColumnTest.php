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
 * 関連モジュールの担当・最終更新者・登録者 — CRMEntity::generateReportsSecQuery()
 *
 * #899 で vtiger_crmentity の基本情報は各モジュールの基本テーブルへ移行した。
 * generateReportsSecQuery() を上書きしていないモジュール（プロジェクト等）を関連モジュールにし、
 * 担当を表示項目に含めると、存在しない vtiger_crmentity<Module> を参照して SQL エラーになっていた（issue #1914）。
 *
 * 担当・最終更新者・登録者が基本テーブルの値で表示されること、
 * vtiger_crmentity 側の値が食い違っていてもそちらは使わないこと、
 * 基本テーブルで削除済みのレコードは表示されないことを固定する。
 */
final class SecondaryModuleOwnerColumnTest extends ReportsTestCase
{
    private const PROJECT_REPORT_ID = 990201;

    private const POTENTIAL_REPORT_ID = 990202;

    private const CONTACT_ID = 990211;

    private const DELETED_PROJECT_CONTACT_ID = 990212;

    private const PROJECT_ID = 990221;

    private const DELETED_PROJECT_ID = 990222;

    private const POTENTIAL_ID = 990231;

    private const LASTNAME_COLUMN = 'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V';

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

        // 基本テーブルは管理者、vtiger_crmentity は別ユーザー・未削除にして食い違わせる
        $this->db->pquery(
            'UPDATE vtiger_crmentity SET smownerid = ?, modifiedby = ?, smcreatorid = ?, deleted = 0 WHERE crmid IN (?, ?)',
            [$this->otherUserId, $this->otherUserId, $this->otherUserId, self::PROJECT_ID, self::DELETED_PROJECT_ID]
        );
    }

    public function test_関連モジュールの担当_最終更新者_登録者が基本テーブルの値で表示される(): void
    {
        $this->createReport(
            self::PROJECT_REPORT_ID,
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

        $sql = $this->buildReportSql(ReportRun::getInstance(self::PROJECT_REPORT_ID), self::PROJECT_REPORT_ID);

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

        self::assertStringNotContainsString('vtiger_crmentityProject', $sql, '関連モジュールの担当等で vtiger_crmentity を参照しない');
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
