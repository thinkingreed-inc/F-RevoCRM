<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Support;

use CRMEntity;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Users_Record_Model;
use Vtiger_Record_Model;
use Vtiger_Request;

/**
 * リード昇格（Web Components 版）の結合テストの共通基盤
 *
 * 本体の読み込み・実行ユーザーの切り替え・テストデータの後始末を引き受ける。
 * テストで作るレコードは名前に接頭辞 LCTEST_ を付け、tearDown で消す。
 * 昇格画面の設定テーブル（vtiger_convertlead_*）とマッピングはテストごとに退避・復元する。
 */
abstract class LeadConvertTestCase extends TestCase
{
    public const PREFIX = 'LCTEST_';

    public const ADMIN_USER_ID = 1;

    private const SETTINGS_TABLES = [
        'vtiger_convertlead_layout',
        'vtiger_convertlead_defaults',
        'vtiger_convertlead_matchrules',
        'vtiger_convertleadmapping',
    ];

    private static bool $initialized = false;

    protected PearDatabase $db;

    /** @var array<string, list<array<string, mixed>>> */
    private array $savedTables = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    private static function requireTestDatabase(): void
    {
        $db = null;
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT DATABASE() AS dbname', []);
            $connected = ($result !== false && $db->num_rows($result) === 1)
                ? (string) $db->query_result($result, 0, 'dbname')
                : '';
        } catch (\Throwable $e) {
            $connected = '';
        }
        if ($connected === '' || !$db instanceof PearDatabase) {
            self::markTestSkipped('テスト用DBに接続できないため飛ばします（tests/README.md のテスト用DBを参照）');
        }
        $result = $db->pquery("SHOW TABLES LIKE 'vtiger_convertlead_layout'", []);
        if ($result === false || $db->num_rows($result) === 0) {
            self::markTestSkipped('vtiger_convertlead_layout がありません。setup/migration を実行してください');
        }
    }

    private static function bootFrevoCrm(): void
    {
        if (self::$initialized) {
            return;
        }
        $root = dirname(__DIR__, 2);
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
        vimport('includes.exceptions.AppException');
        require_once $root . '/modules/Users/Users.php';
        require_once $root . '/include/Webservices/Utils.php';
        require_once $root . '/include/Webservices/ConvertLead.php';
        require_once $root . '/modules/Settings/Vtiger/models/Module.php';
        require_once $root . '/vtlib/Vtiger/Module.php';
        date_default_timezone_set('Asia/Tokyo');
        self::$initialized = true;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::ADMIN_USER_ID);
        foreach (self::SETTINGS_TABLES as $table) {
            $this->savedTables[$table] = $this->fetchAll("SELECT * FROM {$table}");
        }
        $this->cleanRecords();
    }

    protected function tearDown(): void
    {
        $this->loginAs(self::ADMIN_USER_ID);
        $this->cleanRecords();
        foreach ($this->savedTables as $table => $rows) {
            $this->db->pquery("DELETE FROM {$table}", []);
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $this->db->pquery(
                    "INSERT INTO {$table} (" . implode(',', $columns) . ') VALUES (' . generateQuestionMarks($columns) . ')',
                    array_values($row)
                );
            }
        }
        parent::tearDown();
    }

    protected function loginAs(int $userId): Users_Record_Model
    {
        $currentUser = CRMEntity::getInstance('Users');
        $currentUser->id = $userId;
        $currentUser->retrieve_entity_info($userId, 'Users');
        $currentUser->column_fields = (array) $currentUser->column_fields;
        $GLOBALS['current_user'] = $currentUser;

        $model = new Users_Record_Model();
        $model->setData($currentUser->column_fields);
        $model->setModule('Users');
        $model->setEntity($currentUser);
        foreach (get_object_vars($currentUser) as $key => $value) {
            if (!is_object($value)) {
                $model->$key = $value;
            }
        }
        Users_Record_Model::$currentUserModels[$userId] = $model;
        \Vtiger_Cache::$cacheEnable = false;

        return $model;
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function request(array $values): Vtiger_Request
    {
        return new Vtiger_Request($values, $values);
    }

    /**
     * API の応答（Vtiger_Response）から result を取り出す
     *
     * @return array<string, mixed>
     */
    protected function responseResult(object $response): array
    {
        $property = new \ReflectionProperty(get_class($response), 'result');
        $result = $property->getValue($response);

        return is_array($result) ? $result : [];
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function createRecord(string $module, array $fields): int
    {
        $recordModel = Vtiger_Record_Model::getCleanInstance($module);
        $recordModel->set('mode', '');
        $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        foreach ($fields as $key => $value) {
            $recordModel->set($key, $value);
        }
        $recordModel->save();

        return (int) $recordModel->getId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function createLead(string $suffix, array $fields = []): int
    {
        return $this->createRecord('Leads', array_merge([
            'lastname' => self::PREFIX . $suffix,
            'company' => self::PREFIX . 'Company ' . $suffix,
        ], $fields));
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function createAccount(string $name, array $fields = []): int
    {
        return $this->createRecord('Accounts', array_merge(['accountname' => self::PREFIX . $name], $fields));
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function createContact(string $lastname, array $fields = []): int
    {
        return $this->createRecord('Contacts', array_merge(['lastname' => self::PREFIX . $lastname], $fields));
    }

    /** レコードの 1 項目を本体のモデル経由で文字列で読む */
    protected function fieldValue(string $module, int $recordId, string $fieldName): string
    {
        /** @var mixed $value */
        $value = Vtiger_Record_Model::getInstanceById($recordId, $module)->get($fieldName);

        return is_scalar($value) ? (string) $value : '';
    }

    protected static function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    protected function isDeleted(int $crmId): bool
    {
        $result = $this->db->pquery('SELECT deleted FROM vtiger_crmentity WHERE crmid = ?', [$crmId]);
        if ($result === false || $this->db->num_rows($result) === 0) {
            return true;
        }

        return (int) $this->db->query_result($result, 0, 'deleted') === 1;
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $result = $this->db->pquery($sql, $params);
        $rows = [];
        if ($result === false) {
            return $rows;
        }
        $count = $this->db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $this->db->fetchByAssoc($result, $i, false);
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** 接頭辞付きのレコード（およびその昇格で作られたレコード）を完全に消す */
    protected function cleanRecords(): void
    {
        $result = $this->db->pquery(
            "SELECT crmid FROM vtiger_crmentity WHERE label LIKE ? AND setype IN ('Leads','Accounts','Contacts','Potentials')",
            [self::PREFIX . '%']
        );
        if ($result === false) {
            return;
        }
        $ids = [];
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $ids[] = (int) $this->db->query_result($result, $i, 'crmid');
        }
        if ($ids === []) {
            return;
        }
        $marks = generateQuestionMarks($ids);
        foreach ([
            'vtiger_leaddetails' => 'leadid', 'vtiger_leadaddress' => 'leadaddressid', 'vtiger_leadsubdetails' => 'leadsubscriptionid', 'vtiger_leadscf' => 'leadid',
            'vtiger_account' => 'accountid', 'vtiger_accountbillads' => 'accountaddressid', 'vtiger_accountshipads' => 'accountaddressid', 'vtiger_accountscf' => 'accountid',
            'vtiger_contactdetails' => 'contactid', 'vtiger_contactaddress' => 'contactaddressid', 'vtiger_contactsubdetails' => 'contactsubscriptionid', 'vtiger_contactscf' => 'contactid', 'vtiger_customerdetails' => 'customerid',
            'vtiger_potential' => 'potentialid', 'vtiger_potentialscf' => 'potentialid',
            'vtiger_contpotentialrel' => 'potentialid', 'vtiger_crmentityrel' => 'crmid', 'vtiger_crmentity' => 'crmid',
        ] as $table => $column) {
            $this->db->pquery("DELETE FROM {$table} WHERE {$column} IN ({$marks})", $ids);
        }
    }
}
