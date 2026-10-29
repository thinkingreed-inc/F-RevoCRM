<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use Migration20261028110000_MigrateUitype4ToAutonumber as LegacyMigration;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_AutoNumber;
use Vtiger_AutoNumberValue;

require_once dirname(__DIR__, 2) . '/Support/DocumentsTestCase.php';

/**
 * migrate_uitype4_to_autonumber マイグレーション
 *
 * 対象: setup/migration/scripts/20261028110000_migrate_uitype4_to_autonumber.php
 *
 * 顧客企業に連番項目（uitype 4）を 1 つ作り、vtiger_modentity_num の行を差し替えて実行し、
 * 設定の読み替え・停止中の扱い・列の拡張・再実行を確かめる。
 * 実行のたびに顧客企業の vtiger_modentity_num を退避し、最後に元へ戻す。
 */
final class MigrateUitype4ToAutonumberTest extends DocumentsTestCase
{
    private const MODULE = 'Accounts';

    /** @var list<array<string, mixed>> 退避した vtiger_modentity_num の行 */
    private array $savedNumRows = [];

    private int $fieldId = 0;

    private string $fieldName = '';

    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261028110000_migrate_uitype4_to_autonumber.php';
        if ($this->value("SELECT COUNT(*) AS v FROM vtiger_field WHERE uitype = 4") !== '0') {
            self::markTestSkipped('テスト用DBに連番項目（uitype 4）が残っている。先に移行を適用すること');
        }
        $result = $this->db->pquery('SELECT * FROM vtiger_modentity_num WHERE semodule = ?', [self::MODULE]);
        $this->savedNumRows = [];
        while (is_array($row = $this->db->fetchByAssoc($result, -1, false))) {
            $this->savedNumRows[] = $row;
        }
        $this->db->pquery('DELETE FROM vtiger_modentity_num WHERE semodule = ?', [self::MODULE]);

        $field = $this->addLegacyField();
        $this->fieldId = $field['fieldid'];
        $this->fieldName = $field['fieldname'];
    }

    protected function tearDown(): void
    {
        $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($this->fieldId);
        if ($fieldModel) {
            $fieldModel->delete();
        }
        $this->db->pquery('DELETE FROM vtiger_autonumber_config WHERE fieldid = ?', [$this->fieldId]);
        $this->db->pquery('DELETE FROM vtiger_autonumber_counter WHERE fieldid = ?', [$this->fieldId]);
        $this->db->pquery('DELETE FROM vtiger_modentity_num WHERE semodule = ?', [self::MODULE]);
        foreach ($this->savedNumRows as $row) {
            $columns = array_keys($row);
            $this->db->pquery(
                'INSERT INTO vtiger_modentity_num (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')',
                array_values($row)
            );
        }
        Vtiger_AutoNumber::clearCache();
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: int, 4: int}>
     */
    public static function conversions(): array
    {
        return [
            '接頭辞と 1 から' => ['POT', '1', 'POT{0}', 1, 0],
            '途中の番号から' => ['PO', '25', 'PO{0}', 25, 24],
            '先頭 0 は桁をそろえる' => ['INV', '0001', 'INV{0000}', 1, 0],
            '先頭 0 で途中から' => ['TMP', '0042', 'TMP{0000}', 42, 41],
            '接頭辞なし' => ['', '7', '{0}', 7, 6],
            '日本語の接頭辞' => ['見積-', '3', '見積-{0}', 3, 2],
        ];
    }

    #[DataProvider('conversions')]
    public function test_有効な連番の設定を自動採番の設定に読み替える(string $prefix, string $curId, string $format, int $start, int $current): void
    {
        $this->setNumbering($prefix, $curId, 1);

        $this->runMigration();

        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame($format, $config['format']);
        $this->assertSame($start, $config['start_number']);
        $this->assertSame('none', $config['reset_type']);
        $this->assertSame(1, $config['active']);
        $this->assertSame(
            (string) $current,
            $this->value('SELECT cur_value AS v FROM vtiger_autonumber_counter WHERE fieldid = ? AND scope_key = ?', [$this->fieldId, ''])
        );
        $this->assertSame('997', $this->value('SELECT uitype AS v FROM vtiger_field WHERE fieldid = ?', [$this->fieldId]));
    }

    public function test_移行後の最初の番号はこれまでの次の番号になる(): void
    {
        $this->setNumbering('NXT', '0008', 1);
        $this->runMigration();

        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $now = new \DateTimeImmutable('2030-01-02 03:04:05');
        $this->assertSame('NXT0008', Vtiger_AutoNumber::numberNewRecord(self::MODULE, $this->fieldName, [], $now));
        $this->assertSame('NXT0009', Vtiger_AutoNumber::numberNewRecord(self::MODULE, $this->fieldName, [], $now));
    }

    public function test_有効な行が無い項目は停止中の設定にする(): void
    {
        $this->setNumbering('OFF', '5', 0);

        $this->runMigration();

        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame(0, $config['active']);
        $this->assertSame('{0000}', $config['format']);
        $this->assertSame(1, $config['start_number']);
        $this->assertSame('997', $this->value('SELECT uitype AS v FROM vtiger_field WHERE fieldid = ?', [$this->fieldId]));
    }

    public function test_行がまったく無い項目も停止中の設定にする(): void
    {
        $this->runMigration();

        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame(0, $config['active']);
    }

    public function test_使えない文字は取り除いても移行は止まらない(): void
    {
        $this->setNumbering('A{B}<x>', '1', 1);

        $this->runMigration();

        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame('ABx{0}', $config['format']);
    }

    public function test_短い列は書式の上限の長さに広げる(): void
    {
        $this->db->query('ALTER TABLE vtiger_accountscf MODIFY `' . $this->fieldName . '` VARCHAR(20) DEFAULT NULL');
        $this->setNumbering('W', '1', 1);

        $this->runMigration();

        $this->assertSame('100', $this->value(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS v FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['vtiger_accountscf', $this->fieldName]
        ));
    }

    public function test_保存済みの値は変えない(): void
    {
        $accountId = (int) $this->value('SELECT accountid AS v FROM vtiger_accountscf ORDER BY accountid LIMIT 1');
        if ($accountId === 0) {
            self::markTestSkipped('テスト用DBに顧客企業が無い');
        }
        $this->db->pquery('UPDATE vtiger_accountscf SET `' . $this->fieldName . '` = ? WHERE accountid = ?', ['OLD-0003', $accountId]);
        $this->setNumbering('NEW', '4', 1);

        $this->runMigration();

        $this->assertSame('OLD-0003', $this->value('SELECT `' . $this->fieldName . '` AS v FROM vtiger_accountscf WHERE accountid = ?', [$accountId]));
    }

    public function test_2回実行しても何も変わらず既にある設定は上書きしない(): void
    {
        $this->setNumbering('ONE', '1', 1);
        $this->runMigration();
        Vtiger_AutoNumber::saveConfig($this->fieldId, ['format' => 'KEEP-{000}', 'reset_type' => 'year', 'start_number' => 9, 'base_date' => '', 'effective_from' => null, 'active' => 1]);
        $this->db->pquery('UPDATE vtiger_autonumber_counter SET cur_value = 77 WHERE fieldid = ?', [$this->fieldId]);

        // uitype 4 が残っていなければ何もしない
        $this->runMigration();
        Vtiger_AutoNumber::clearCache();
        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame('KEEP-{000}', $config['format']);

        // 設定が先にある項目を uitype 4 に戻して実行しても、設定と連番は上書きしない
        $this->db->pquery('UPDATE vtiger_field SET uitype = 4 WHERE fieldid = ?', [$this->fieldId]);
        $this->runMigration();
        Vtiger_AutoNumber::clearCache();
        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame('KEEP-{000}', $config['format']);
        $this->assertSame('77', $this->value('SELECT cur_value AS v FROM vtiger_autonumber_counter WHERE fieldid = ? AND scope_key = ?', [$this->fieldId, '']));
        $this->assertSame('997', $this->value('SELECT uitype AS v FROM vtiger_field WHERE fieldid = ?', [$this->fieldId]));
    }

    public function test_旧テーブルは消さない(): void
    {
        $this->setNumbering('KEEP', '2', 1);

        $this->runMigration();

        $this->assertSame('1', $this->value('SELECT COUNT(*) AS v FROM vtiger_modentity_num WHERE semodule = ?', [self::MODULE]));
    }

    private function runMigration(): void
    {
        Vtiger_AutoNumber::clearCache();
        ob_start();
        try {
            (new LegacyMigration())->process();
        } finally {
            ob_end_clean();
        }
        Vtiger_AutoNumber::clearCache();
    }

    private function setNumbering(string $prefix, string $curId, int $active): void
    {
        $this->db->pquery('DELETE FROM vtiger_modentity_num WHERE semodule = ?', [self::MODULE]);
        $numId = (int) $this->value('SELECT COALESCE(MAX(num_id), 0) + 1 AS v FROM vtiger_modentity_num');
        $this->db->pquery(
            'INSERT INTO vtiger_modentity_num (num_id, semodule, prefix, start_id, cur_id, active) VALUES (?, ?, ?, ?, ?, ?)',
            [$numId, self::MODULE, $prefix, $curId, $curId, $active]
        );
    }

    /**
     * @return array{fieldid: int, fieldname: string}
     */
    private function addLegacyField(): array
    {
        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName(self::MODULE);
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid(self::MODULE)]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');
        $fieldModel = $moduleModel->addField('Text', $blockId, [
            'fieldType' => 'Text', 'fieldLabel' => 'SPECTEST_旧連番', 'fieldLength' => '50', 'mandatory' => 'O', 'quickcreate' => 1,
            'masseditable' => 2, 'summaryfield' => 0, 'headerfield' => 0, 'fieldDefaultValue' => '',
        ]);
        $fieldId = (int) $fieldModel->getId();
        $this->db->pquery('UPDATE vtiger_field SET uitype = 4 WHERE fieldid = ?', [$fieldId]);
        return ['fieldid' => $fieldId, 'fieldname' => $fieldModel->getName()];
    }

    /**
     * @param list<mixed> $params
     */
    private function value(string $sql, array $params = []): string
    {
        $result = $this->db->pquery($sql, $params);
        if ($result === false || $this->db->num_rows($result) === 0) {
            return '';
        }
        return Vtiger_AutoNumberValue::string($this->db->query_result($result, 0, 'v'));
    }
}
