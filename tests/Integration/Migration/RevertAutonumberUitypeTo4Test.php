<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use Migration20261112180000_RevertAutonumberUitypeTo4 as RevertMigration;
use Settings_LayoutEditor_Field_Model;
use Settings_LayoutEditor_Module_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_AutoNumber;
use Vtiger_AutoNumberValue;

require_once dirname(__DIR__, 2) . '/Support/DocumentsTestCase.php';

/**
 * revert_autonumber_uitype_to_4 マイグレーション
 *
 * 対象: setup/migration/scripts/20261112180000_revert_autonumber_uitype_to_4.php
 *
 * 自動採番を uitype 997 で作っていた環境のために、997 の項目を 4 に戻す。
 *   - uitype 997 の項目が uitype 4 になり、vtiger_ws_fieldtype の 997 が消える
 *   - 設定・連番・保存済みの値は変えない
 *   - 2 回実行しても何も変わらない
 *
 * 顧客企業に項目を 1 つ作り（uitype 997 の状態にして）実行する。項目は最後に削除する。
 */
final class RevertAutonumberUitypeTo4Test extends DocumentsTestCase
{
    private const MODULE = 'Accounts';

    private int $fieldId = 0;

    private string $fieldName = '';

    private bool $insertedWsFieldType = false;

    protected function setUp(): void
    {
        parent::setUp();
        require_once 'modules/Vtiger/utils/AutoNumber.php';
        require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261112180000_revert_autonumber_uitype_to_4.php';

        $moduleModel = Settings_LayoutEditor_Module_Model::getInstanceByName(self::MODULE);
        $result = $this->db->pquery('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [getTabid(self::MODULE)]);
        $blockId = (int) $this->db->query_result($result, 0, 'blockid');
        $fieldModel = $moduleModel->addField('AutoNumber', $blockId, [
            'fieldType' => 'AutoNumber', 'fieldLabel' => 'SPECTEST_旧997', 'fieldLength' => '50', 'mandatory' => 'O', 'quickcreate' => 1,
            'masseditable' => 2, 'summaryfield' => 0, 'headerfield' => 0, 'fieldDefaultValue' => '',
        ]);
        $this->fieldId = (int) $fieldModel->getId();
        $this->fieldName = $fieldModel->getName();
        Vtiger_AutoNumber::saveConfig($this->fieldId, ['format' => 'OLD-{000}', 'reset_type' => 'none', 'start_number' => 3, 'base_date' => '', 'effective_from' => null, 'active' => 1]);
        $this->db->pquery(
            'INSERT INTO vtiger_autonumber_counter (fieldid, scope_key, cur_value) VALUES (?, ?, 41) ON DUPLICATE KEY UPDATE cur_value = 41',
            [$this->fieldId, '']
        );

        // 自動採番を uitype 997 で作っていた環境の状態にする
        $this->db->pquery('UPDATE vtiger_field SET uitype = 997 WHERE fieldid = ?', [$this->fieldId]);
        $exists = $this->db->pquery("SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = '997'", []);
        $this->insertedWsFieldType = $this->db->num_rows($exists) === 0;
        if ($this->insertedWsFieldType) {
            $this->db->pquery("INSERT INTO vtiger_ws_fieldtype (uitype, fieldtype) VALUES ('997', 'string')", []);
        }
        Vtiger_AutoNumber::clearCache();
    }

    protected function tearDown(): void
    {
        $fieldModel = Settings_LayoutEditor_Field_Model::getInstance($this->fieldId);
        if ($fieldModel) {
            $fieldModel->delete();
        }
        $this->db->pquery('DELETE FROM vtiger_autonumber_config WHERE fieldid = ?', [$this->fieldId]);
        $this->db->pquery('DELETE FROM vtiger_autonumber_counter WHERE fieldid = ?', [$this->fieldId]);
        $this->db->pquery("DELETE FROM vtiger_ws_fieldtype WHERE uitype = '997'", []);
        Vtiger_AutoNumber::clearCache();
        parent::tearDown();
    }

    public function test_uitype_997_の項目が_4_に戻り登録も消える(): void
    {
        $this->runMigration();

        $this->assertSame('4', $this->value('SELECT uitype AS v FROM vtiger_field WHERE fieldid = ?', [$this->fieldId]));
        $this->assertSame('0', $this->value("SELECT COUNT(*) AS v FROM vtiger_field WHERE uitype = 997"));
        $this->assertSame('0', $this->value("SELECT COUNT(*) AS v FROM vtiger_ws_fieldtype WHERE uitype = '997'"));
    }

    public function test_設定と連番は変わらず戻した後もそのまま採番できる(): void
    {
        $this->runMigration();

        $config = Vtiger_AutoNumber::getConfig($this->fieldId);
        $this->assertNotNull($config);
        $this->assertSame('OLD-{000}', $config['format']);
        $this->assertSame(3, $config['start_number']);
        $this->assertSame('41', $this->value('SELECT cur_value AS v FROM vtiger_autonumber_counter WHERE fieldid = ? AND scope_key = ?', [$this->fieldId, '']));
        $this->assertArrayHasKey($this->fieldName, Vtiger_AutoNumber::getModuleConfigs(self::MODULE), '自動採番項目として扱われる');
        $now = new \DateTimeImmutable('2030-01-02 03:04:05');
        $this->assertSame('OLD-042', Vtiger_AutoNumber::numberNewRecord(self::MODULE, $this->fieldName, [], $now), '連番は続きから');
    }

    public function test_保存済みの値は変えない(): void
    {
        $accountId = (int) $this->value('SELECT accountid AS v FROM vtiger_accountscf ORDER BY accountid LIMIT 1');
        if ($accountId === 0) {
            self::markTestSkipped('テスト用DBに顧客企業が無い');
        }
        $this->db->pquery('UPDATE vtiger_accountscf SET `' . $this->fieldName . '` = ? WHERE accountid = ?', ['OLD-0007', $accountId]);

        $this->runMigration();

        $this->assertSame('OLD-0007', $this->value('SELECT `' . $this->fieldName . '` AS v FROM vtiger_accountscf WHERE accountid = ?', [$accountId]));
    }

    public function test_2回実行しても何も変わらない(): void
    {
        $this->runMigration();
        $this->runMigration();

        $this->assertSame('4', $this->value('SELECT uitype AS v FROM vtiger_field WHERE fieldid = ?', [$this->fieldId]));
        $this->assertSame('41', $this->value('SELECT cur_value AS v FROM vtiger_autonumber_counter WHERE fieldid = ? AND scope_key = ?', [$this->fieldId, '']));
    }

    private function runMigration(): void
    {
        Vtiger_AutoNumber::clearCache();
        ob_start();
        try {
            (new RevertMigration())->process();
        } finally {
            ob_end_clean();
        }
        Vtiger_AutoNumber::clearCache();
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
