<?php

declare(strict_types=1);

namespace Tests\Integration\Leads;

use Exception;
use Tests\Support\LeadConvertTestCase;
use Users_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/LeadConvertTestCase.php';

/**
 * vtws_convertlead に足した任意パラメータ（existingId / strict）と、既定の挙動が変わらないこと
 */
final class VtwsConvertLeadTest extends LeadConvertTestCase
{
    /**
     * @param array<string, array<string, mixed>> $entities
     * @return array<string, string>|null
     */
    private function convert(int $leadId, array $entities, bool $strict = false): ?array
    {
        $element = [
            'leadId' => vtws_getWebserviceEntityId('Leads', $leadId),
            'assignedTo' => vtws_getWebserviceEntityId('Users', self::ADMIN_USER_ID),
            'transferRelatedRecordsTo' => isset($entities['Contacts']) ? 'Contacts' : 'Accounts',
            'entities' => [],
        ];
        foreach ($entities as $module => $values) {
            $element['entities'][$module] = array_merge(['create' => true, 'name' => $module, 'source' => 'CRM'], $values);
        }
        if ($strict) {
            $element['strict'] = true;
        }

        return vtws_convertlead($element, $leadId, null, null, null, null, Users_Record_Model::getCurrentUserModel());
    }

    private function crmId(string $wsId): int
    {
        return (int) vtws_getIdComponents($wsId)[1];
    }

    public function test_既定では同じ会社名の企業を再利用する(): void
    {
        $accountId = $this->createAccount('Same');
        $leadId = $this->createLead('A', ['company' => self::PREFIX . 'Same']);

        $result = $this->convert($leadId, ['Accounts' => ['accountname' => self::PREFIX . 'Same']]);

        self::assertNotNull($result);
        self::assertSame($accountId, $this->crmId($result['Accounts']));
    }

    public function test_strictでは同じ会社名でも新規作成する(): void
    {
        $accountId = $this->createAccount('Same');
        $leadId = $this->createLead('A', ['company' => self::PREFIX . 'Same']);

        // 既定インストールの企業は会社名の重複禁止が有効なので、この確認のあいだだけ外す
        $GLOBALS['skipDuplicateCheck'] = true;
        try {
            $result = $this->convert($leadId, ['Accounts' => ['accountname' => self::PREFIX . 'Same']], true);
        } finally {
            $GLOBALS['skipDuplicateCheck'] = false;
        }

        self::assertNotNull($result);
        self::assertNotSame($accountId, $this->crmId($result['Accounts']));
    }

    public function test_strictで重複禁止に当たると重複エラーになり何も作らない(): void
    {
        $this->createAccount('Dup');
        $leadId = $this->createLead('A2', ['company' => self::PREFIX . 'Dup']);

        try {
            $this->convert($leadId, [
                'Contacts' => ['lastname' => self::PREFIX . 'A2'],
                'Accounts' => ['accountname' => self::PREFIX . 'Dup'],
            ], true);
            self::fail('重複禁止なのに昇格できた');
        } catch (\DuplicateException $e) {
            self::assertNotSame('', $e->getDuplicationMessage());
        }

        self::assertSame([], $this->fetchAll(
            "SELECT crmid FROM vtiger_crmentity WHERE setype = 'Contacts' AND deleted = 0 AND label LIKE ?",
            ['%' . self::PREFIX . 'A2%']
        ));
    }

    public function test_既存企業に紐づけても既存企業の値は変わらない(): void
    {
        $accountId = $this->createAccount('Existing', ['industry' => 'Banking', 'phone' => '03-1111-1111']);
        $leadId = $this->createLead('B', ['industry' => 'Apparel', 'phone' => '06-2222-2222']);

        $result = $this->convert($leadId, [
            'Accounts' => ['existingId' => vtws_getWebserviceEntityId('Accounts', $accountId)],
            'Contacts' => ['lastname' => self::PREFIX . 'B'],
        ], true);

        self::assertNotNull($result);
        self::assertSame($accountId, $this->crmId($result['Accounts']));
        self::assertSame('Banking', $this->fieldValue('Accounts', $accountId, 'industry'));
        self::assertSame('03-1111-1111', $this->fieldValue('Accounts', $accountId, 'phone'));
        self::assertSame((string) $accountId, $this->fieldValue('Contacts', $this->crmId($result['Contacts']), 'account_id'));
    }

    public function test_既存担当者の所属企業は空のときだけセットする(): void
    {
        $accountId = $this->createAccount('Target');
        $otherAccountId = $this->createAccount('Other');
        $freeContactId = $this->createContact('Free', ['email' => 'free@example.com']);
        $boundContactId = $this->createContact('Bound', ['account_id' => $otherAccountId]);
        $accountWs = vtws_getWebserviceEntityId('Accounts', $accountId);

        $this->convert($this->createLead('C1'), [
            'Accounts' => ['existingId' => $accountWs],
            'Contacts' => ['existingId' => vtws_getWebserviceEntityId('Contacts', $freeContactId)],
        ], true);
        $this->convert($this->createLead('C2'), [
            'Accounts' => ['existingId' => $accountWs],
            'Contacts' => ['existingId' => vtws_getWebserviceEntityId('Contacts', $boundContactId)],
        ], true);

        self::assertSame((string) $accountId, $this->fieldValue('Contacts', $freeContactId, 'account_id'));
        self::assertSame('free@example.com', $this->fieldValue('Contacts', $freeContactId, 'email'));
        self::assertSame((string) $otherAccountId, $this->fieldValue('Contacts', $boundContactId, 'account_id'));
    }

    public function test_既存担当者に紐づけると新しい案件はその担当者に紐づく(): void
    {
        $contactId = $this->createContact('ForPotential');

        $result = $this->convert($this->createLead('D'), [
            'Contacts' => ['existingId' => vtws_getWebserviceEntityId('Contacts', $contactId)],
            'Potentials' => [
                'potentialname' => self::PREFIX . 'Deal', 'closingdate' => '2030-01-01', 'sales_stage' => 'Prospecting',
            ],
        ], true);

        self::assertNotNull($result);
        self::assertSame($contactId, $this->crmId($result['Contacts']));
        self::assertSame((string) $contactId, $this->fieldValue('Potentials', $this->crmId($result['Potentials']), 'contact_id'));
    }

    public function test_strictで必須が空なら例外になり既存レコードは消さない(): void
    {
        $accountId = $this->createAccount('Keep');

        try {
            $this->convert($this->createLead('E'), [
                'Accounts' => ['existingId' => vtws_getWebserviceEntityId('Accounts', $accountId)],
                'Contacts' => ['lastname' => ''],
            ], true);
            self::fail('必須が空なのに昇格できた');
        } catch (Exception $e) {
            self::assertStringContainsString('lastname', $e->getMessage());
        }

        self::assertFalse($this->isDeleted($accountId));
    }

    public function test_strictで途中で失敗したら作成済みのレコードを消す(): void
    {
        $leadId = $this->createLead('F');

        try {
            $this->convert($leadId, [
                'Accounts' => ['accountname' => self::PREFIX . 'Rollback'],
                'Potentials' => ['potentialname' => '', 'closingdate' => '2030-01-01', 'sales_stage' => 'Prospecting'],
            ], true);
            self::fail('必須が空なのに昇格できた');
        } catch (Exception $e) {
            self::assertStringContainsString('potentialname', $e->getMessage());
        }

        $rows = $this->fetchAll(
            "SELECT crmid FROM vtiger_crmentity WHERE setype = 'Accounts' AND deleted = 0 AND label = ?",
            [self::PREFIX . 'Rollback']
        );
        self::assertSame([], $rows);
    }

    public function test_既定では必須が空でも従来どおり埋めて保存する(): void
    {
        $result = $this->convert($this->createLead('G', ['company' => '']), [
            'Contacts' => ['lastname' => self::PREFIX . 'G'],
            'Potentials' => ['potentialname' => '', 'closingdate' => '2030-01-01', 'sales_stage' => 'Prospecting'],
        ]);

        self::assertNotNull($result);
        $potentialId = $this->crmId($result['Potentials']);
        self::assertNotSame('', $this->fieldValue('Potentials', $potentialId, 'potentialname'));
        $this->db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', [self::PREFIX . 'G-potential', $potentialId]);
    }

    public function test_種類の違う既存IDは拒否する(): void
    {
        $contactId = $this->createContact('WrongType');

        $this->expectException(Exception::class);
        $this->convert($this->createLead('H'), [
            'Accounts' => ['existingId' => vtws_getWebserviceEntityId('Contacts', $contactId)],
        ], true);
    }

    public function test_途中で失敗したら既存担当者の所属企業も元に戻す(): void
    {
        $contactId = $this->createContact('NoAccount');

        try {
            $this->convert($this->createLead('R'), [
                'Accounts' => ['accountname' => self::PREFIX . 'Rollback2'],
                'Contacts' => ['existingId' => vtws_getWebserviceEntityId('Contacts', $contactId)],
                'Potentials' => ['potentialname' => '', 'closingdate' => '2030-01-01', 'sales_stage' => 'Prospecting'],
            ], true);
            self::fail('必須が空なのに昇格できた');
        } catch (Exception $e) {
            self::assertStringContainsString('potentialname', $e->getMessage());
        }

        // 所属企業が空のまま（空は '' または '0' で返る）
        self::assertContains($this->fieldValue('Contacts', $contactId, 'account_id'), ['', '0']);
        self::assertFalse($this->isDeleted($contactId));
    }
}
