<?php

declare(strict_types=1);

namespace Tests\Integration\Import;

use Import_Data_Action;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_AutoNumber;
use Vtiger_AutoNumberValue;

require_once dirname(__DIR__, 2) . '/Support/DocumentsTestCase.php';

/**
 * インポート（modules/Import/actions/Data.php）での年月項目・自動採番項目
 *
 *   - 年月: YYYY-MM などの年月だけの値と月の途中の日付を月初日にして取り込む。解釈できない値は取り込まない
 *   - 自動採番: CSV に値があっても無視して書式どおりに採番し、更新では保存済みの番号を変えない
 *
 * 取り込みの 1 件分の処理（transformForImport / importRecord）を、インポート用のテーブルを使わずに呼ぶ。
 * テスト用の項目は顧客企業に作り、クラスの最後に削除する。
 */
final class YearMonthAutoNumberImportTest extends DocumentsTestCase
{
    /** @var array{fieldid: int, fieldname: string}|null */
    private static ?array $yearMonth = null;

    /** @var array{fieldid: int, fieldname: string}|null */
    private static ?array $autoNumber = null;

    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        require_once 'modules/Import/actions/Data.php';
        require_once 'modules/Import/helpers/Utils.php';
        $result = $this->db->pquery("SHOW TABLES LIKE 'vtiger_autonumber_config'", []);
        if ($result === false || $this->db->num_rows($result) === 0) {
            self::markTestSkipped('テスト用DBに自動採番のマイグレーションが適用されていない');
        }
        if (self::$yearMonth === null) {
            self::$yearMonth = $this->addField('YearMonth', 'SPECTEST_取込年月');
            self::$autoNumber = $this->addField('AutoNumber', 'SPECTEST_取込採番');
            Vtiger_AutoNumber::saveConfig(self::$autoNumber['fieldid'], ['format' => 'IMP-{000}'] + Vtiger_AutoNumber::defaultConfig());
        }
        Vtiger_AutoNumber::clearCache();
        $this->cleanAccounts();
        $this->db->pquery('DELETE FROM ' . Vtiger_AutoNumber::COUNTER_TABLE . ' WHERE fieldid = ?', [self::$autoNumber['fieldid'] ?? 0]);
    }

    protected function tearDown(): void
    {
        $this->cleanAccounts();
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        foreach ([self::$yearMonth, self::$autoNumber] as $field) {
            if ($field !== null) {
                $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($field['fieldid']);
                if ($fieldModel) {
                    $fieldModel->delete();
                }
            }
        }
        self::$yearMonth = null;
        self::$autoNumber = null;
        parent::tearDownAfterClass();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function yearMonthValues(): array
    {
        return [
            'ハイフン区切りの年月' => ['2026-10', '2026-10-01'],
            'スラッシュ区切りの年月' => ['2026/10', '2026-10-01'],
            '日本語の年月' => ['2026年10月', '2026-10-01'],
            '月初日' => ['2026-03-01', '2026-03-01'],
            '月の途中の日付' => ['2026-10-28', '2026-10-01'],
            '1 桁の月' => ['2026-3', '2026-03-01'],
        ];
    }

    #[DataProvider('yearMonthValues')]
    public function test_年月は月初日にして取り込む(string $input, string $expected): void
    {
        $data = $this->transform([self::yearMonthName() => $input]);

        $this->assertNotNull($data);
        $this->assertSame($expected, $data[self::yearMonthName()]);
    }

    public function test_解釈できない年月は空にして取り込む(): void
    {
        foreach (['abc', '2026-13', '2026-00', '1999'] as $input) {
            $data = $this->transform([self::yearMonthName() => $input]);
            $this->assertNotNull($data, $input);
            $this->assertSame('', Vtiger_AutoNumberValue::string($data[self::yearMonthName()] ?? ''), $input . ' は取り込まない');
        }
    }

    public function test_空の年月は空のまま(): void
    {
        $data = $this->transform([self::yearMonthName() => '']);

        $this->assertNotNull($data);
        $this->assertSame('', Vtiger_AutoNumberValue::string($data[self::yearMonthName()] ?? ''));
    }

    public function test_年月つきの行を新規に取り込むと月初日で保存される(): void
    {
        $record = $this->importRecord([
            'accountname' => self::PREFIX . 'ym', self::yearMonthName() => '2026-10',
        ]);

        $this->assertSame('2026-10-01', $this->stored($record['id'], self::yearMonthName()));
    }

    public function test_更新の取り込みで年月を変えられる(): void
    {
        $created = $this->importRecord(['accountname' => self::PREFIX . 'ymu', self::yearMonthName() => '2026-10']);

        $this->importRecord(['id' => $created['wsid'], self::yearMonthName() => '2027-02'], 'revise');

        $this->assertSame('2027-02-01', $this->stored($created['id'], self::yearMonthName()));
    }

    public function test_自動採番はCSVの値を無視して書式どおりに付ける(): void
    {
        $first = $this->importRecord(['accountname' => self::PREFIX . 'n1', self::autoName() => 'HACK-1']);
        $second = $this->importRecord(['accountname' => self::PREFIX . 'n2']);

        $this->assertSame('IMP-001', $this->stored($first['id'], self::autoName()));
        $this->assertSame('IMP-002', $this->stored($second['id'], self::autoName()));
    }

    public function test_更新の取り込みでは自動採番の番号を変えない(): void
    {
        $created = $this->importRecord(['accountname' => self::PREFIX . 'keep']);
        $number = $this->stored($created['id'], self::autoName());
        $this->assertSame('IMP-001', $number);

        $this->importRecord(['id' => $created['wsid'], self::autoName() => 'CHANGED-9', 'website' => 'https://example.com'], 'revise');

        $this->assertSame($number, $this->stored($created['id'], self::autoName()));
        $this->assertSame('https://example.com', $this->stored($created['id'], 'website', 'vtiger_account'));
    }

    public function test_取り込んだ後に連番が続く(): void
    {
        $this->importRecord(['accountname' => self::PREFIX . 'c1']);
        $this->importRecord(['accountname' => self::PREFIX . 'c2']);
        $third = $this->saveAccount();

        $this->assertSame('IMP-003', $this->stored($third, self::autoName()));
    }

    // ---- 補助 -------------------------------------------------------------

    private static function yearMonthName(): string
    {
        return self::$yearMonth['fieldname'] ?? '';
    }

    private static function autoName(): string
    {
        return self::$autoNumber['fieldname'] ?? '';
    }

    private function importer(): Import_Data_Action
    {
        $user = $GLOBALS['current_user'];
        return new Import_Data_Action([
            'id' => 1, 'module' => 'Accounts', 'field_mapping' => [], 'merge_type' => 0, 'merge_fields' => [],
            'default_values' => [], 'lineitem_currency_id' => '', 'paging' => '',
        ], $user);
    }

    /**
     * @param array<string, string> $fieldData
     * @return array<string, mixed>|null
     */
    private function transform(array $fieldData): ?array
    {
        $user = $GLOBALS['current_user'];
        $meta = vtws_getModuleHandlerFromName('Accounts', $user)->getMeta();
        return $this->importer()->transformForImport($fieldData + ['accountname' => self::PREFIX . 'x', 'assigned_user_id' => '19x1'], $meta, [], false, false);
    }

    /**
     * 1 行を取り込む（transformForImport のあと importRecord）
     *
     * @param array<string, string> $fieldData
     * @return array{id: int, wsid: string}
     */
    private function importRecord(array $fieldData, string $operation = 'create'): array
    {
        $user = $GLOBALS['current_user'];
        $meta = vtws_getModuleHandlerFromName('Accounts', $user)->getMeta();
        $importer = $this->importer();
        $isUpdate = $operation !== 'create';
        $recordId = $fieldData['id'] ?? null;
        unset($fieldData['id']);
        $data = $importer->transformForImport($fieldData + ($isUpdate ? [] : ['assigned_user_id' => '19x1']), $meta, [], !$isUpdate, !$isUpdate);
        $this->assertNotNull($data, '取り込みの変換で落ちた');
        if ($isUpdate) {
            $data['id'] = $recordId;
        }
        $info = $importer->importRecord($data, $operation);
        $this->assertIsArray($info);
        $this->assertArrayHasKey('id', $info, '取り込みに失敗した');
        $wsid = (string) $info['id'];
        return ['id' => (int) explode('x', $wsid)[1], 'wsid' => $wsid];
    }

    private function saveAccount(): int
    {
        $recordModel = \Vtiger_Record_Model::getCleanInstance('Accounts');
        $recordModel->set('accountname', self::PREFIX . 'plain');
        $recordModel->set('assigned_user_id', self::ADMIN_USER_ID);
        $recordModel->save();
        return (int) $recordModel->getId();
    }

    private function stored(int $accountId, string $fieldName, string $table = 'vtiger_accountscf'): string
    {
        $result = $this->db->pquery("SELECT {$fieldName} AS v FROM {$table} WHERE accountid = ?", [$accountId]);
        return Vtiger_AutoNumberValue::string($this->db->query_result($result, 0, 'v'));
    }

    /**
     * @return array{fieldid: int, fieldname: string}
     */
    private function addField(string $type, string $label): array
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName('Accounts');
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid('Accounts')]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');
        $fieldModel = $moduleModel->addField($type, $blockId, [
            'fieldType' => $type, 'fieldLabel' => $label, 'fieldLength' => '50', 'mandatory' => 'O', 'quickcreate' => 1,
            'masseditable' => 2, 'summaryfield' => 0, 'headerfield' => 0, 'fieldDefaultValue' => '',
        ]);
        return ['fieldid' => (int) $fieldModel->getId(), 'fieldname' => $fieldModel->getName()];
    }

    private function cleanAccounts(): void
    {
        $result = $this->db->pquery('SELECT accountid FROM vtiger_account WHERE accountname LIKE ?', [self::PREFIX . '%']);
        for ($i = 0; $i < $this->db->num_rows($result); $i++) {
            $accountId = (int) $this->db->query_result($result, $i, 'accountid');
            foreach (['vtiger_accountscf' => 'accountid', 'vtiger_accountbillads' => 'accountaddressid',
                      'vtiger_accountshipads' => 'accountaddressid', 'vtiger_account' => 'accountid', 'vtiger_crmentity' => 'crmid'] as $table => $column) {
                $this->db->pquery("DELETE FROM {$table} WHERE {$column} = ?", [$accountId]);
            }
        }
        $this->db->pquery("DELETE FROM vtiger_crmentity WHERE setype = 'Accounts' AND label LIKE ?", [self::PREFIX . '%']);
    }
}
