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

namespace Tests\Integration\Calendar;

use Calendar_GetRecord_Api;
use Events_Record_Model;
use PearDatabase;
use PHPUnit\Framework\TestCase;

/**
 * 活動の GetRecord API — 参照項目の表示名・参照先モジュール・顧客担当者の展開
 *
 * カレンダーの編集・複製モーダルは GetRecord API の応答で項目を復元する。
 * 表示名を返さないと「ID: nn」と表示され、参照先モジュールを返さないと
 * 関連のモジュール選択が先頭（顧客企業）になる。また顧客担当者は複数登録できるが、
 * getData() は先頭 1 件しか持たないため、展開しないと開いて保存しただけで
 * 2 人目以降が消える。
 *
 * 検証用に投入したレコード（FIXTURE_IDS）だけを操作し、既存データには触らない。
 */
final class GetRecordApiReferenceDisplayTest extends TestCase
{
    /** 既存データと衝突しない ID 帯を使う */
    private const EVENT_ID = 990101;
    private const POTENTIAL_ID = 990102;
    private const CONTACT1_ID = 990103;
    private const CONTACT2_ID = 990104;
    private const DELETED_CONTACT_ID = 990105;

    /** 検証用に投入するレコードの ID。片付けはこの集合だけを対象にする */
    private const FIXTURE_IDS = [
        self::EVENT_ID,
        self::POTENTIAL_ID,
        self::CONTACT1_ID,
        self::CONTACT2_ID,
        self::DELETED_CONTACT_ID,
    ];

    private static bool $initialized = false;

    private ?PearDatabase $db = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::bootFrevoCrm();
    }

    /**
     * API と活動のレコードモデルを読み込む
     *
     * bootstrap.php が Vtiger_Loader のオートロードを外しているため、ここで戻す。
     */
    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 3);

        if (class_exists('Vtiger_Loader', false)) {
            spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        }

        require_once $root . '/include/utils/utils.php';
        require_once $root . '/include/database/PearDatabase.php';
        vimport('includes.runtime.Globals');
        vimport('includes.runtime.LanguageHandler');
        vimport('includes.runtime.BaseModel');
        vimport('includes.runtime.Controller');
        vimport('includes.http.Request');
        vimport('includes.http.Response');

        require_once $root . '/modules/Events/models/Record.php';
        require_once $root . '/modules/Calendar/apis/GetRecord.php';

        self::$initialized = true;
    }

    private function db(): PearDatabase
    {
        $db = $this->db;
        if (!$db instanceof PearDatabase) {
            $db = PearDatabase::getInstance();
            $this->db = $db;
        }

        return $db;
    }

    /**
     * テスト用DB が無い環境（CI など）では実行しない
     */
    private function skipUnlessDatabaseIsAvailable(): void
    {
        try {
            $result = $this->db()->pquery('SELECT 1 AS ok FROM vtiger_cntactivityrel LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_cntactivityrel がないため実行しない');
        }
    }

    protected function setUp(): void
    {
        $this->skipUnlessDatabaseIsAvailable();

        $this->cleanUp();

        $this->insertCrmEntity(self::EVENT_ID, 'Calendar', '検証用の活動');
        $this->insertCrmEntity(self::POTENTIAL_ID, 'Potentials', '検証用の案件');
        $this->insertContact(self::CONTACT1_ID, '山田 太郎');
        $this->insertContact(self::CONTACT2_ID, '佐藤;花子');
        $this->insertContact(self::DELETED_CONTACT_ID, '削除済み 担当者', 1);
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testReturnsLabelAndModuleForReferenceFields(): void
    {
        $displayValues = $this->getReferenceDisplayValues([
            'parent_id' => (string) self::POTENTIAL_ID,
            'contact_id' => (string) self::CONTACT1_ID,
        ]);

        $this->assertSame('検証用の案件', $displayValues['parent_id']);
        $this->assertSame('Potentials', $displayValues['parent_id_module']);
        $this->assertSame('山田 太郎', $displayValues['contact_id']);
        $this->assertSame('Contacts', $displayValues['contact_id_module']);
    }

    /**
     * 未設定・数値でない・存在しない参照は解決しない
     */
    public function testSkipsEmptyOrUnknownReferences(): void
    {
        $displayValues = $this->getReferenceDisplayValues([
            'parent_id' => '',
            'contact_id' => '999999999',
        ]);
        $this->assertSame([], $displayValues);

        $displayValues = $this->getReferenceDisplayValues(['parent_id' => 'abc']);
        $this->assertSame([], $displayValues);
    }

    /**
     * 顧客担当者を全件に展開し、ID と表示名を同じ順序・個数の ";" 区切りで返す。
     * 名前中の ";" は区切りと衝突しないよう全角にする。削除済みの担当者は含めない
     */
    public function testExpandsAllRelatedContacts(): void
    {
        $this->insertContactRelation(self::CONTACT1_ID);
        $this->insertContactRelation(self::CONTACT2_ID);
        $this->insertContactRelation(self::DELETED_CONTACT_ID);

        $data = ['contact_id' => (string) self::CONTACT1_ID];
        $names = $this->expandRelatedContacts($data);

        $ids = explode(';', $data['contact_id']);
        $labels = explode(';', $names);
        $this->assertCount(2, $ids);
        $this->assertCount(2, $labels);

        // ID と表示名は同じ index で対応する
        $byId = array_combine($ids, $labels);
        ksort($byId);
        $this->assertSame(
            [self::CONTACT1_ID => '山田 太郎', self::CONTACT2_ID => '佐藤；花子'],
            $byId
        );
    }

    /**
     * 顧客担当者がいなければ contact_id を書き換えない
     */
    public function testKeepsDataWhenNoContactsAreRelated(): void
    {
        $data = ['contact_id' => ''];
        $names = $this->expandRelatedContacts($data);

        $this->assertSame('', $names);
        $this->assertSame('', $data['contact_id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function getReferenceDisplayValues(array $data): array
    {
        $method = new \ReflectionMethod(Calendar_GetRecord_Api::class, 'getReferenceDisplayValues');

        $result = $method->invoke(new Calendar_GetRecord_Api(), $data);
        $this->assertIsArray($result);

        /** @var array<string, string> $result */
        return $result;
    }

    /**
     * @param array<string, string> $data 参照渡し
     */
    private function expandRelatedContacts(array &$data): string
    {
        $recordModel = new Events_Record_Model();
        $recordModel->setId(self::EVENT_ID);

        $method = new \ReflectionMethod(Calendar_GetRecord_Api::class, 'expandRelatedContacts');

        $names = $method->invokeArgs(new Calendar_GetRecord_Api(), [$recordModel, &$data]);
        $this->assertIsString($names);

        return $names;
    }

    private function insertCrmEntity(int $crmId, string $setype, string $label, int $deleted = 0): void
    {
        $this->db()->pquery(
            'INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, setype, createdtime, modifiedtime, deleted, label)'
            . ' VALUES (?, 1, 1, ?, NOW(), NOW(), ?, ?)',
            [$crmId, $setype, $deleted, $label]
        );
    }

    /**
     * vtiger_cntactivityrel が vtiger_contactdetails を外部キーで参照するため、
     * 顧客担当者は vtiger_contactdetails にも行を作る
     */
    private function insertContact(int $contactId, string $label, int $deleted = 0): void
    {
        $this->insertCrmEntity($contactId, 'Contacts', $label, $deleted);
        $this->db()->pquery(
            'INSERT INTO vtiger_contactdetails (contactid, contact_no, lastname) VALUES (?, ?, ?)',
            [$contactId, 'CONTEST' . $contactId, $label]
        );
    }

    private function insertContactRelation(int $contactId): void
    {
        $this->db()->pquery(
            'INSERT INTO vtiger_cntactivityrel (contactid, activityid) VALUES (?, ?)',
            [$contactId, self::EVENT_ID]
        );
    }

    private function cleanUp(): void
    {
        $placeholders = implode(',', array_fill(0, count(self::FIXTURE_IDS), '?'));
        $this->db()->pquery(
            "DELETE FROM vtiger_cntactivityrel WHERE activityid IN ($placeholders) OR contactid IN ($placeholders)",
            array_merge(self::FIXTURE_IDS, self::FIXTURE_IDS)
        );
        $this->db()->pquery("DELETE FROM vtiger_contactdetails WHERE contactid IN ($placeholders)", self::FIXTURE_IDS);
        $this->db()->pquery("DELETE FROM vtiger_crmentity WHERE crmid IN ($placeholders)", self::FIXTURE_IDS);
    }
}
