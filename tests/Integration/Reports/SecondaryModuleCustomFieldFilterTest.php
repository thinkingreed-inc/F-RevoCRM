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
use Vtiger_Block;
use Vtiger_Field;
use Vtiger_Module;

require_once dirname(__DIR__, 2) . '/Support/ReportsTestCase.php';

/**
 * 関連モジュールのカスタム項目による絞り込み — ReportRun::generateAdvFilterSql()
 *
 * 関連モジュールの cf テーブル上の項目だけを絞り込み条件に使うと、QueryPlanner に cf テーブルしか
 * 登録されず、generateReportsSecQuery() が関連モジュール自体を結合しないため、
 * WHERE 句だけが cf テーブルを参照して SQL エラーになっていた（issue #969）。
 *
 * 主モジュール「顧客担当者」、関連モジュール「案件」のレポートで、案件のカスタム項目
 * 「空ではない」だけを条件にし、保存済みの条件・詳細画面での抽出条件変更の両方で
 * 該当する顧客担当者だけが返ることを固定する。
 */
final class SecondaryModuleCustomFieldFilterTest extends ReportsTestCase
{
    private const REPORT_ID = 990101;

    private const RUNTIME_REPORT_ID = 990102;

    private const STANDARD_FIELD_REPORT_ID = 990103;

    /** 条件に合う顧客担当者（案件のカスタム項目に値あり） */
    private const MATCHED_CONTACT_ID = 990111;

    /** 条件に合わない顧客担当者（案件のカスタム項目が空） */
    private const UNMATCHED_CONTACT_ID = 990112;

    private const MATCHED_POTENTIAL_ID = 990121;

    private const UNMATCHED_POTENTIAL_ID = 990122;

    private const CF_COLUMN = 'cf_spectest969';

    private const CF_LABEL = 'SPECTEST_CF969';

    private const LASTNAME_COLUMN = 'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V';

    private const CF_FILTER_COLUMN = 'vtiger_potentialscf:' . self::CF_COLUMN . ':Potentials_' . self::CF_LABEL . ':' . self::CF_COLUMN . ':V';

    private ?Vtiger_Field $customField = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCustomField();

        $this->createContact(self::MATCHED_CONTACT_ID, self::PREFIX . 'matched');
        $this->createContact(self::UNMATCHED_CONTACT_ID, self::PREFIX . 'unmatched');
        $this->createPotential(self::MATCHED_POTENTIAL_ID, self::MATCHED_CONTACT_ID, 'value');
        $this->createPotential(self::UNMATCHED_POTENTIAL_ID, self::UNMATCHED_CONTACT_ID, '');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->dropCustomField();
    }

    public function test_保存済みの条件で関連モジュールのカスタム項目だけを使っても該当レコードが返る(): void
    {
        $this->createReport(
            self::REPORT_ID,
            'Contacts',
            'Potentials',
            'Potentials::Potentials::contact_id',
            [self::LASTNAME_COLUMN],
            [['columnname' => self::CF_FILTER_COLUMN, 'comparator' => 'ny']]
        );

        $sql = $this->buildReportSql(ReportRun::getInstance(self::REPORT_ID), self::REPORT_ID);

        self::assertStringContainsString('as vtiger_potential on', $sql, '関連モジュールの基本テーブルが結合されていない');
        self::assertStringContainsString('left join vtiger_potentialscf', $sql, '関連モジュールの cf テーブルが結合されていない');
        $this->assertOnlyMatchedContact($this->fetchAll($sql));
    }

    public function test_詳細画面で抽出条件を変更して関連モジュールのカスタム項目だけを使っても該当レコードが返る(): void
    {
        $this->createReport(
            self::RUNTIME_REPORT_ID,
            'Contacts',
            'Potentials',
            'Potentials::Potentials::contact_id',
            [self::LASTNAME_COLUMN]
        );

        $reportRun = ReportRun::getInstance(self::RUNTIME_REPORT_ID);
        $filterSql = $reportRun->RunTimeAdvFilter(
            [['columnname' => self::CF_FILTER_COLUMN, 'comparator' => 'ny', 'value' => '', 'columncondition' => '', 'groupid' => 1]],
            [1 => ['groupcondition' => '']]
        );
        $sql = $this->buildReportSql($reportRun, self::RUNTIME_REPORT_ID, $filterSql);

        self::assertStringContainsString('left join vtiger_potentialscf', $sql, '関連モジュールの cf テーブルが結合されていない');
        $this->assertOnlyMatchedContact($this->fetchAll($sql));
    }

    public function test_関連モジュールの標準項目で絞り込む場合も該当レコードが返る(): void
    {
        $this->createReport(
            self::STANDARD_FIELD_REPORT_ID,
            'Contacts',
            'Potentials',
            'Potentials::Potentials::contact_id',
            [self::LASTNAME_COLUMN],
            [[
                'columnname' => 'vtiger_potential:potentialname:Potentials_Potential_Name:potentialname:V',
                'comparator' => 'e',
                'value' => self::PREFIX . self::MATCHED_POTENTIAL_ID,
            ]]
        );

        $sql = $this->buildReportSql(ReportRun::getInstance(self::STANDARD_FIELD_REPORT_ID), self::STANDARD_FIELD_REPORT_ID);

        $this->assertOnlyMatchedContact($this->fetchAll($sql));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function assertOnlyMatchedContact(array $rows): void
    {
        $lastnames = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $lastname = $row['contacts_last_name'] ?? null;
            if (is_string($lastname) && str_starts_with($lastname, self::PREFIX)) {
                $lastnames[] = $lastname;
            }
        }

        self::assertSame([self::PREFIX . 'matched'], $lastnames, '条件に合う顧客担当者だけが返ること');
    }

    private function createPotential(int $id, int $contactId, string $customValue): void
    {
        $this->createRecord('Potentials', $id, 'vtiger_potential', 'potentialid', [
            'potential_no' => self::PREFIX . $id,
            'potentialname' => self::PREFIX . $id,
            'contact_id' => $contactId,
        ]);
        $this->insertRow('vtiger_potentialscf', 'potentialid', $id, [self::CF_COLUMN => $customValue]);
    }

    /** 案件にテキストのカスタム項目を追加する（vtiger_potentialscf に列が増える） */
    private function createCustomField(): void
    {
        $this->dropCustomField();

        $module = Vtiger_Module::getInstance('Potentials');
        $block = Vtiger_Block::getInstance('LBL_CUSTOM_INFORMATION', $module);
        $field = new Vtiger_Field();
        $field->name = self::CF_COLUMN;
        $field->label = self::CF_LABEL;
        $field->table = 'vtiger_potentialscf';
        $field->column = self::CF_COLUMN;
        $field->columntype = 'VARCHAR(100)';
        $field->uitype = 1;
        $field->typeofdata = 'V~O';
        $block->addField($field);
        $this->customField = $field;
    }

    private function dropCustomField(): void
    {
        $module = Vtiger_Module::getInstance('Potentials');
        $field = $this->customField ?? Vtiger_Field::getInstance(self::CF_COLUMN, $module);
        if ($field instanceof Vtiger_Field) {
            $field->delete();
        }
        $this->customField = null;

        $result = $this->db->pquery("SHOW COLUMNS FROM vtiger_potentialscf LIKE '" . self::CF_COLUMN . "'", []);
        if ($result !== false && $this->db->num_rows($result) > 0) {
            $this->db->pquery('ALTER TABLE vtiger_potentialscf DROP COLUMN ' . self::CF_COLUMN, []);
        }
    }
}
