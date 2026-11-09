<?php

declare(strict_types=1);

namespace Tests\Integration\Leads;

use Leads_ConvertLeadMatcher_Model;
use Leads_ConvertLeadSettings_Model;
use Tests\Support\LeadConvertTestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/LeadConvertTestCase.php';

/**
 * 昇格済み判定（既存の企業・担当者を探す）
 */
final class ConvertLeadMatcherTest extends LeadConvertTestCase
{
    public function test_会社名が一致する企業が当たる(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Accounts', [['accountname']]);
        $accountId = $this->createAccount('Match Co');

        $match = Leads_ConvertLeadMatcher_Model::findMatches('Accounts', ['accountname' => ' ' . self::PREFIX . 'Match Co ']);

        self::assertNotNull($match);
        self::assertSame(['accountname'], $match['rule']);
        self::assertSame(1, $match['total']);
        self::assertSame($accountId, $match['records'][0]['id']);
        self::assertNotSame('', $match['ruleLabel']);
    }

    public function test_記号を含む会社名も一致する(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Accounts', [['accountname']]);
        $accountId = $this->createAccount('A&B "Holdings"');

        $match = Leads_ConvertLeadMatcher_Model::findMatches('Accounts', ['accountname' => self::PREFIX . 'A&B "Holdings"']);

        self::assertNotNull($match);
        self::assertSame($accountId, $match['records'][0]['id']);
    }

    public function test_条件同士はORで最初に当たった条件を返す(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['email'], ['lastname', 'firstname']]);
        $contactId = $this->createContact('Yamada', ['firstname' => 'Taro']);

        $match = Leads_ConvertLeadMatcher_Model::findMatches('Contacts', [
            'email' => 'nobody@example.com', 'lastname' => self::PREFIX . 'Yamada', 'firstname' => 'Taro',
        ]);

        self::assertNotNull($match);
        self::assertSame(['lastname', 'firstname'], $match['rule']);
        self::assertSame($contactId, $match['records'][0]['id']);
    }

    public function test_条件内の項目はANDで比べる(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['lastname', 'firstname']]);
        $this->createContact('Suzuki', ['firstname' => 'Ichiro']);

        $match = Leads_ConvertLeadMatcher_Model::findMatches('Contacts', [
            'lastname' => self::PREFIX . 'Suzuki', 'firstname' => 'Jiro',
        ]);

        self::assertNull($match);
    }

    public function test_値が空の項目を含む条件は調べない(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['email']]);
        $this->createContact('NoMail');

        self::assertNull(Leads_ConvertLeadMatcher_Model::findMatches('Contacts', ['email' => '']));
    }

    public function test_参照項目は既存レコードを選んでいればIDで比べる(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['lastname', 'account_id']]);
        $accountId = $this->createAccount('Ref Co');
        $otherAccountId = $this->createAccount('Ref Other');
        $contactId = $this->createContact('Tanaka', ['account_id' => $accountId]);
        $this->createContact('Tanaka', ['account_id' => $otherAccountId]);

        $match = Leads_ConvertLeadMatcher_Model::findMatches(
            'Contacts',
            ['lastname' => self::PREFIX . 'Tanaka', 'account_id' => 'ignored'],
            ['account_id' => $accountId]
        );

        self::assertNotNull($match);
        self::assertSame(1, $match['total']);
        self::assertSame($contactId, $match['records'][0]['id']);
        self::assertSame($accountId, $match['records'][0]['accountId']);
        self::assertSame(self::PREFIX . 'Ref Co', $match['records'][0]['accountLabel']);
    }

    public function test_参照項目は企業を新規作成するなら企業名で比べる(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['lastname', 'account_id']]);
        $accountId = $this->createAccount('Name Co');
        $contactId = $this->createContact('Sato', ['account_id' => $accountId]);

        $match = Leads_ConvertLeadMatcher_Model::findMatches('Contacts', [
            'lastname' => self::PREFIX . 'Sato', 'account_id' => self::PREFIX . 'Name Co',
        ]);

        self::assertNotNull($match);
        self::assertSame($contactId, $match['records'][0]['id']);
    }

    public function test_削除済みのレコードは当たらない(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Accounts', [['accountname']]);
        $accountId = $this->createAccount('Deleted Co');
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$accountId]);

        self::assertNull(Leads_ConvertLeadMatcher_Model::findMatches('Accounts', ['accountname' => self::PREFIX . 'Deleted Co']));
    }

    public function test_閲覧権限のないレコードは判定に出さない(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Contacts', [['email']]);
        $hiddenId = $this->createContact('Hidden', ['email' => 'same@example.com']);
        $visibleId = $this->createContact('Visible', ['email' => 'same@example.com']);

        $match = Leads_ConvertLeadMatcher_Model::findMatches(
            'Contacts',
            ['email' => 'same@example.com'],
            [],
            static fn (string $module, int $id): bool => $id !== $hiddenId
        );

        self::assertNotNull($match);
        self::assertSame([$visibleId], array_column($match['records'], 'id'));
        self::assertSame(1, $match['total']);
    }

    public function test_全件に閲覧権限が無ければ当たらない(): void
    {
        Leads_ConvertLeadSettings_Model::saveMatchRules('Accounts', [['accountname']]);
        $this->createAccount('Secret Co');

        self::assertNull(Leads_ConvertLeadMatcher_Model::findMatches(
            'Accounts',
            ['accountname' => self::PREFIX . 'Secret Co'],
            [],
            static fn (): bool => false
        ));
    }
}
