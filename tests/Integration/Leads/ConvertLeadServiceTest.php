<?php

declare(strict_types=1);

namespace Tests\Integration\Leads;

use Leads_ConvertLeadInfo_Api;
use Leads_ConvertLeadService_Model;
use Leads_ConvertLeadSettings_Model;
use Tests\Support\LeadConvertTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/LeadConvertTestCase.php';

/**
 * 昇格画面の情報（ConvertLeadInfo）と、検証つきの昇格（Leads_ConvertLeadService_Model::convert）
 */
final class ConvertLeadServiceTest extends LeadConvertTestCase
{
    private function service(int $leadId): Leads_ConvertLeadService_Model
    {
        return new Leads_ConvertLeadService_Model(Vtiger_Record_Model::getInstanceById($leadId, 'Leads'));
    }

    /**
     * @param array<string, array<string, mixed>> $modules
     * @return array<string, mixed>
     */
    private function convert(int $leadId, array $modules): array
    {
        foreach (['Accounts', 'Contacts', 'Potentials'] as $module) {
            $modules[$module] ??= ['mode' => 'none'];
        }

        return $this->service($leadId)->convert(['modules' => $modules, 'assignedUserId' => (string) self::ADMIN_USER_ID]);
    }

    /**
     * @param array<string, mixed> $result
     * @return list<string>
     */
    private function errorFields(array $result): array
    {
        $fields = [];
        /** @var list<array{module: string, field: string}> $errors */
        $errors = $result['errors'];
        foreach ($errors as $error) {
            $fields[] = $error['module'] . '.' . $error['field'];
        }

        return $fields;
    }

    public function test_画面情報に表示項目と判定結果が入る(): void
    {
        Leads_ConvertLeadSettings_Model::saveLayout('Contacts', ['lastname', 'email']);
        Leads_ConvertLeadSettings_Model::saveMatchRules('Accounts', [['accountname']]);
        $accountId = $this->createAccount('Info Co');
        $leadId = $this->createLead('Info', ['company' => self::PREFIX . 'Info Co', 'email' => 'info@example.com']);

        $response = (new Leads_ConvertLeadInfo_Api())->process($this->request(['module' => 'Leads', 'record' => $leadId]));
        $info = $this->responseResult($response);

        /** @var array<string, array<string, mixed>> $modules */
        $modules = $info['modules'];
        self::assertSame(['lastname', 'email'], $modules['Contacts']['fields']);
        self::assertSame('info@example.com', ((array) $modules['Contacts']['values'])['email']);
        /** @var list<string> $accountFields */
        $accountFields = $modules['Accounts']['fields'];
        self::assertContains('accountname', $accountFields);
        /** @var array{records: list<array{id: int}>} $match */
        $match = $modules['Accounts']['match'];
        self::assertSame($accountId, $match['records'][0]['id']);
        self::assertSame((string) self::ADMIN_USER_ID, $info['assignedUserId']);
    }

    public function test_必須項目は表示設定に無くても先頭に出る(): void
    {
        Leads_ConvertLeadSettings_Model::saveLayout('Contacts', ['email']);

        $fields = Leads_ConvertLeadService_Model::getDisplayedFields('Contacts');

        self::assertSame('lastname', $fields[0]);
        self::assertSame('email', $fields[count($fields) - 1]);
        self::assertNotContains('account_id', $fields);
    }

    public function test_非表示項目のエラーを返して昇格しない(): void
    {
        Leads_ConvertLeadSettings_Model::saveLayout('Contacts', ['lastname']);
        $leadId = $this->createLead('Hidden', ['email' => 'broken@']);

        $result = $this->convert($leadId, ['Contacts' => ['mode' => 'create', 'values' => ['lastname' => self::PREFIX . 'Hidden']]]);

        self::assertFalse($result['converted']);
        self::assertContains('Contacts.email', $this->errorFields($result));
        /** @var list<array<string, mixed>> $errors */
        $errors = $result['errors'];
        $emailError = array_values(array_filter($errors, static fn (array $e): bool => $e['field'] === 'email'))[0];
        self::assertTrue($emailError['hidden']);
        self::assertTrue($emailError['editable']);
        self::assertFalse(Leads_ConvertLeadService_Model::isConverted($leadId));
    }

    public function test_入力で直せば昇格できる(): void
    {
        Leads_ConvertLeadSettings_Model::saveLayout('Contacts', ['lastname']);
        $leadId = $this->createLead('Fix', ['email' => 'broken@']);

        $result = $this->convert($leadId, ['Contacts' => ['mode' => 'create', 'values' => [
            'lastname' => self::PREFIX . 'Fix', 'email' => 'fixed@example.com',
        ]]]);

        self::assertTrue($result['converted'], json_encode($result['errors'], JSON_UNESCAPED_UNICODE) ?: '');
        /** @var array<string, int> $ids */
        $ids = $result['entityIds'];
        self::assertSame('fixed@example.com', $this->fieldValue('Contacts', $ids['Contacts'], 'email'));
        self::assertSame("index.php?module=Contacts&view=Detail&record={$ids['Contacts']}", $result['redirectUrl']);
    }

    public function test_必須を空にしたら埋めずにエラーにする(): void
    {
        $leadId = $this->createLead('Empty');

        $result = $this->convert($leadId, ['Contacts' => ['mode' => 'create', 'values' => ['lastname' => '  ']]]);

        self::assertFalse($result['converted']);
        self::assertContains('Contacts.lastname', $this->errorFields($result));
    }

    public function test_企業と担当者のどちらも無ければ拒否する(): void
    {
        $result = $this->convert($this->createLead('None'), [
            'Potentials' => ['mode' => 'create', 'values' => ['potentialname' => self::PREFIX . 'P']],
        ]);

        self::assertFalse($result['converted']);
        self::assertContains('.', $this->errorFields($result));
    }

    public function test_閲覧権限のない既存IDや種類違いのIDは拒否する(): void
    {
        $contactId = $this->createContact('NotAccount');

        $result = $this->convert($this->createLead('Wrong'), [
            'Accounts' => ['mode' => 'existing', 'existingId' => $contactId],
        ]);

        self::assertFalse($result['converted']);
        self::assertContains('Accounts.', $this->errorFields($result));
    }

    public function test_既存企業に紐づけて担当者と案件を作る(): void
    {
        $accountId = $this->createAccount('Attach Co', ['industry' => 'Banking']);
        $leadId = $this->createLead('Attach', ['industry' => 'Apparel', 'email' => 'attach@example.com']);

        $result = $this->convert($leadId, [
            'Accounts' => ['mode' => 'existing', 'existingId' => $accountId],
            'Contacts' => ['mode' => 'create', 'values' => ['lastname' => self::PREFIX . 'Attach']],
            'Potentials' => ['mode' => 'create', 'values' => [
                'potentialname' => self::PREFIX . 'Attach Deal', 'closingdate' => '2030-12-31', 'sales_stage' => 'Prospecting',
            ]],
        ]);

        self::assertTrue($result['converted'], json_encode($result['errors'], JSON_UNESCAPED_UNICODE) ?: '');
        /** @var array<string, int> $ids */
        $ids = $result['entityIds'];
        self::assertSame($accountId, $ids['Accounts']);
        self::assertSame('Banking', $this->fieldValue('Accounts', $accountId, 'industry'));
        self::assertSame((string) $accountId, $this->fieldValue('Contacts', $ids['Contacts'], 'account_id'));
        self::assertSame((string) $accountId, $this->fieldValue('Potentials', $ids['Potentials'], 'related_to'));
        self::assertSame("index.php?module=Accounts&view=Detail&record={$accountId}", $result['redirectUrl']);
        self::assertTrue(Leads_ConvertLeadService_Model::isConverted($leadId));
    }

    public function test_重複禁止に当たると重複エラーを返す(): void
    {
        $this->createAccount('Dup Co');
        $leadId = $this->createLead('Dup', ['company' => self::PREFIX . 'Dup Co']);

        $result = $this->convert($leadId, [
            'Accounts' => ['mode' => 'create', 'values' => []],
            'Contacts' => ['mode' => 'create', 'values' => ['lastname' => self::PREFIX . 'Dup']],
        ]);

        self::assertFalse($result['converted']);
        self::assertSame(['.'], $this->errorFields($result));
    }

    public function test_既存レコードの概要は閲覧できるものだけ返す(): void
    {
        $accountId = $this->createAccount('Summary Co', ['phone' => '03-0000-1234']);
        $contactId = $this->createContact('Summary', ['account_id' => $accountId]);

        $summary = Leads_ConvertLeadService_Model::getRecordSummary('Contacts', $contactId);

        self::assertNotNull($summary);
        self::assertSame($accountId, $summary['accountId']);
        self::assertSame(self::PREFIX . 'Summary Co', $summary['accountLabel']);
        self::assertNull(Leads_ConvertLeadService_Model::getRecordSummary('Accounts', $contactId));
    }
}
