<?php

declare(strict_types=1);

namespace Tests\Integration\Leads;

use ApiForbiddenException;
use Leads_ConvertLeadSettings_Model;
use Settings_Leads_ConvertSettings_Api;
use Tests\Support\LeadConvertTestCase;
use Users_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/LeadConvertTestCase.php';

/**
 * リードマッピング設定画面の API（Settings_Leads_ConvertSettings_Api）
 */
final class ConvertSettingsApiTest extends LeadConvertTestCase
{
    private Settings_Leads_ConvertSettings_Api $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = new Settings_Leads_ConvertSettings_Api();
    }

    private function fieldId(string $module, string $fieldName): int
    {
        $rows = $this->fetchAll(
            'SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?',
            [getTabid($module), $fieldName]
        );

        return self::intValue($rows[0]['fieldid']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mappingRows(): array
    {
        return $this->fetchAll('SELECT cfmid, leadfid, accountfid, contactfid, potentialfid, editable FROM vtiger_convertleadmapping ORDER BY cfmid');
    }

    public function test_管理者以外は拒否する(): void
    {
        $user = Users_Record_Model::getCurrentUserModel();
        $user->set('is_admin', 'off');

        $this->expectException(ApiForbiddenException::class);
        $this->api->checkPermission($this->request(['module' => 'Leads', 'parent' => 'Settings']));
    }

    public function test_情報には既存のマッピングとロック行が入る(): void
    {
        $info = $this->api->getInfo();

        /** @var list<array{id: int, editable: bool}> $mapping */
        $mapping = $info['mapping'];
        self::assertCount(count($this->mappingRows()), $mapping);
        self::assertContains(false, array_column($mapping, 'editable'));
        /** @var array<string, array<string, mixed>> $modules */
        $modules = $info['modules'];
        self::assertSame([['accountname']], $modules['Accounts']['matchRules']);
    }

    public function test_ロック行は保存で変わらない(): void
    {
        $before = $this->mappingRows();
        $locked = array_values(array_filter($before, static fn (array $row): bool => self::intValue($row['editable']) === 0))[0];

        $result = $this->api->saveMapping([[
            'id' => self::intValue($locked['cfmid']),
            'lead' => $this->fieldId('Leads', 'phone'),
            'Accounts' => $this->fieldId('Accounts', 'phone'),
            'Contacts' => 0,
            'Potentials' => 0,
            'deleted' => true,
        ]]);

        self::assertTrue($result['saved']);
        self::assertSame($before, $this->mappingRows());
    }

    public function test_型が違う項目へのマッピングは拒否する(): void
    {
        $before = $this->mappingRows();

        $result = $this->api->saveMapping([[
            'lead' => $this->fieldId('Leads', 'email'),
            'Accounts' => $this->fieldId('Accounts', 'employees'),
            'Contacts' => 0,
            'Potentials' => 0,
        ]]);

        self::assertFalse($result['saved']);
        self::assertSame($before, $this->mappingRows());
    }

    public function test_マッピングを追加して削除できる(): void
    {
        $result = $this->api->saveMapping([[
            'lead' => $this->fieldId('Leads', 'fax'),
            'Accounts' => $this->fieldId('Accounts', 'fax'),
            'Contacts' => $this->fieldId('Contacts', 'fax'),
            'Potentials' => 0,
        ]]);
        self::assertTrue($result['saved']);
        $added = $this->fetchAll('SELECT cfmid FROM vtiger_convertleadmapping WHERE leadfid = ? AND editable = 1', [$this->fieldId('Leads', 'fax')]);
        self::assertNotSame([], $added);

        $this->api->saveMapping([[
            'id' => self::intValue($added[0]['cfmid']), 'lead' => $this->fieldId('Leads', 'fax'),
            'Accounts' => 0, 'Contacts' => 0, 'Potentials' => 0, 'deleted' => true,
        ]]);
        self::assertSame([], $this->fetchAll('SELECT cfmid FROM vtiger_convertleadmapping WHERE cfmid = ?', [self::intValue($added[0]['cfmid'])]));
    }

    public function test_表示項目は昇格画面に出せる項目だけ保存できる(): void
    {
        self::assertFalse($this->api->saveLayout('Contacts', ['account_id'])['saved']);
        self::assertTrue($this->api->saveLayout('Contacts', ['email', 'lastname'])['saved']);
        self::assertSame(['email', 'lastname'], Leads_ConvertLeadSettings_Model::getLayout('Contacts'));
    }

    public function test_昇格時デフォルト値は型を検証する(): void
    {
        $result = $this->api->saveDefaults('Potentials', ['closingdate' => '2026/13/40', 'amount' => '100']);

        self::assertFalse($result['saved']);
        self::assertSame(['closingdate'], array_column($result['errors'], 'field'));

        self::assertTrue($this->api->saveDefaults('Potentials', ['closingdate' => '2030-03-31', 'sales_stage' => 'Prospecting'])['saved']);
        self::assertSame(
            ['closingdate' => '2030-03-31', 'sales_stage' => 'Prospecting'],
            Leads_ConvertLeadSettings_Model::getDefaults('Potentials')
        );
    }

    public function test_判定条件は担当者の顧客企業名も使える(): void
    {
        self::assertTrue($this->api->saveMatchRules('Contacts', [['email'], ['lastname', 'firstname', 'account_id']])['saved']);
        self::assertSame([['email'], ['lastname', 'firstname', 'account_id']], Leads_ConvertLeadSettings_Model::getMatchRules('Contacts'));
        self::assertFalse($this->api->saveMatchRules('Contacts', [['no_such_field']])['saved']);
    }
}
