<?php

declare(strict_types=1);

namespace Tests\Unit\Leads;

use Leads_ConvertLeadValues_Model;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/modules/Leads/models/ConvertLeadValues.php';

/**
 * 昇格先に入る値の決め方（入力 ＞ マッピング ＞ 昇格時デフォルト ＞ 項目のデフォルト）
 */
final class ConvertLeadValuesTest extends TestCase
{
    public function test_入力がマッピングより優先される(): void
    {
        $values = Leads_ConvertLeadValues_Model::resolve(
            ['email' => 'lead@example.com'],
            ['email' => 'default@example.com'],
            [],
            ['email' => 'input@example.com']
        );

        self::assertSame('input@example.com', $values['email']);
    }

    public function test_入力の空文字はマッピングより優先される(): void
    {
        $values = Leads_ConvertLeadValues_Model::resolve(['phone' => '03-0000-0000'], [], [], ['phone' => '']);

        self::assertSame('', $values['phone']);
    }

    public function test_マッピングの値が空なら昇格時デフォルトを使う(): void
    {
        $values = Leads_ConvertLeadValues_Model::resolve(
            ['industry' => ''],
            ['industry' => 'Banking'],
            ['industry' => 'Apparel'],
            []
        );

        self::assertSame('Banking', $values['industry']);
    }

    public function test_昇格時デフォルトが無ければ項目のデフォルトを使う(): void
    {
        $values = Leads_ConvertLeadValues_Model::resolve([], [], ['sales_stage' => 'Prospecting'], []);

        self::assertSame('Prospecting', $values['sales_stage']);
    }

    public function test_マッピングの値は昇格時デフォルトより優先される(): void
    {
        $values = Leads_ConvertLeadValues_Model::resolve(['industry' => 'Banking'], ['industry' => 'Apparel'], [], []);

        self::assertSame('Banking', $values['industry']);
    }

    public function test_マッピングの0は空とみなさない(): void
    {
        $values = Leads_ConvertLeadValues_Model::resolve(['employees' => '0'], ['employees' => '10'], [], []);

        self::assertSame('0', $values['employees']);
    }
}
