<?php

declare(strict_types=1);

namespace Tests\Integration\Vtiger\Uitypes;

use Tests\Support\DocumentsTestCase;
use Vtiger_AutoNumber;
use Vtiger_AutoNumberValue;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 4) . '/tests/Support/DocumentsTestCase.php';

/**
 * 連番項目（uitype 4）から自動採番項目（uitype 997）への移行後の動き
 *
 * マイグレーション 20261028110000_migrate_uitype4_to_autonumber が適用済みのテスト用 DB で、
 * 見積などの標準モジュールが、これまでと同じ「接頭辞 + 連番」で採番されることを確かめる。
 * リードを 2 件作って消す（連番は戻さない）。
 */
final class AutoNumberLegacyMigrationTest extends DocumentsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        if ($this->scalar("SELECT COUNT(*) FROM vtiger_field WHERE uitype = 4") !== '0') {
            self::markTestSkipped('テスト用DBに連番項目の移行（20261028110000）が適用されていない');
        }
        Vtiger_AutoNumber::clearCache();
    }

    public function test_標準モジュールの連番項目は自動採番項目になっている(): void
    {
        $this->assertSame('997', $this->scalar("SELECT uitype FROM vtiger_field WHERE tabid = ? AND fieldname = 'lead_no'", [getTabid('Leads')]));
        $configs = Vtiger_AutoNumber::getModuleConfigs('Leads');
        $this->assertArrayHasKey('lead_no', $configs);
        $this->assertSame('LEA{0}', $configs['lead_no']['format']);
        $this->assertSame('none', $configs['lead_no']['reset_type']);
        $this->assertSame(1, $configs['lead_no']['active']);
    }

    public function test_リードはこれまでどおり接頭辞と連番で採番される(): void
    {
        $first = $this->createLead('a');
        $second = $this->createLead('b');
        try {
            $this->assertMatchesRegularExpression('/\ALEA\d+\z/', $first['no']);
            $this->assertSame((int) substr($first['no'], 3) + 1, (int) substr($second['no'], 3), '連番は 1 ずつ進む');
        } finally {
            $this->db->pquery('DELETE FROM vtiger_leaddetails WHERE leadid IN (?, ?)', [$first['id'], $second['id']]);
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid IN (?, ?)', [$first['id'], $second['id']]);
        }
    }

    public function test_連番項目は編集できず保存済みの番号が保たれる(): void
    {
        $lead = $this->createLead('c');
        try {
            $recordModel = Vtiger_Record_Model::getInstanceById($lead['id'], 'Leads');
            $recordModel->set('mode', 'edit');
            $recordModel->set('lead_no', 'HACK1');
            $recordModel->set('company', 'SPECTEST_changed');
            $recordModel->save();
            $this->assertSame($lead['no'], $this->scalar('SELECT lead_no FROM vtiger_leaddetails WHERE leadid = ?', [$lead['id']]));
        } finally {
            $this->db->pquery('DELETE FROM vtiger_leaddetails WHERE leadid = ?', [$lead['id']]);
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$lead['id']]);
        }
    }

    /**
     * @return array{id: int, no: string}
     */
    private function createLead(string $suffix): array
    {
        $recordModel = Vtiger_Record_Model::getCleanInstance('Leads');
        $recordModel->set('lastname', self::PREFIX . 'lead' . $suffix);
        $recordModel->set('company', self::PREFIX . 'company');
        $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        $recordModel->save();
        $id = (int) $recordModel->getId();
        return ['id' => $id, 'no' => $this->scalar('SELECT lead_no FROM vtiger_leaddetails WHERE leadid = ?', [$id])];
    }

    /**
     * @param list<mixed> $params
     */
    private function scalar(string $sql, array $params = []): string
    {
        $result = $this->db->pquery($sql, $params);
        if ($result === false || $this->db->num_rows($result) === 0) {
            return '';
        }
        $row = $this->db->fetch_array($result);
        return Vtiger_AutoNumberValue::string(is_array($row) ? ($row[0] ?? '') : '');
    }
}
