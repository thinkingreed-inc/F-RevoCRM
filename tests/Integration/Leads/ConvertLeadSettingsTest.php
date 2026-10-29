<?php

declare(strict_types=1);

namespace Tests\Integration\Leads;

use InvalidArgumentException;
use Leads_ConvertLeadSettings_Model;
use Tests\Support\LeadConvertTestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/LeadConvertTestCase.php';

/**
 * リード昇格画面の設定（表示項目・昇格時デフォルト値・判定条件）の保存と取得
 */
final class ConvertLeadSettingsTest extends LeadConvertTestCase
{
    public function test_表示項目は指定した順で保存され重複は1つにまとまる(): void
    {
        Leads_ConvertLeadSettings_Model::saveLayout('Contacts', ['email', 'lastname', 'email', 'phone']);

        self::assertSame(['email', 'lastname', 'phone'], Leads_ConvertLeadSettings_Model::getLayout('Contacts'));
    }

    public function test_昇格時デフォルト値は空文字を保存しない(): void
    {
        Leads_ConvertLeadSettings_Model::saveDefaults('Potentials', ['sales_stage' => 'Prospecting', 'amount' => '']);

        self::assertSame(['sales_stage' => 'Prospecting'], Leads_ConvertLeadSettings_Model::getDefaults('Potentials'));
    }

    public function test_判定条件は条件ごとの項目の組で保存され空の条件は捨てる(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['email'], [], ['lastname', 'firstname', 'account_id']]);

        self::assertSame(
            [['email'], ['lastname', 'firstname', 'account_id']],
            Leads_ConvertLeadSettings_Model::getMatchRules('Contacts')
        );
    }

    public function test_案件には判定条件を持てない(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Leads_ConvertLeadSettings_Model::getMatchRules('Potentials');
    }

    public function test_昇格先以外のモジュールは拒否する(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Leads_ConvertLeadSettings_Model::saveLayout('Leads', ['lastname']);
    }
}
