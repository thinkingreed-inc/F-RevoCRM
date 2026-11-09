<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use RecordVersion_Config_Model;
use RecordVersion_Revision_Model;
use RecordVersion_Setup_Model;
use Tests\Support\RecordVersionTestCase;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 版管理の設定（仕様 設定 4-1・4-2、表A）
 */
final class SetupTest extends RecordVersionTestCase
{
    public function test_はいにすると版が未設定のレコードだけ版1にしすでに版があるレコードはそのまま(): void
    {
        $this->disableVersioning();
        $unversioned = $this->createPotential('未設定');
        $versioned = $this->createPotential('版あり');
        $this->db->pquery('UPDATE vtiger_crmentity SET version = 3 WHERE crmid = ?', [$versioned]);

        $this->enableVersioning();

        $this->assertSame(1, $this->currentVersion($unversioned), '版が未設定（0）のレコードは版 1 になる');
        $this->assertSame(3, $this->currentVersion($versioned), 'すでに版があるレコードは版番号を変えない（いいえ→はい に戻したとき）');
    }

    public function test_版項目は一度だけ最初のブロックの最後に読み取り専用で追加する(): void
    {
        $this->enableVersioning();
        $this->enableVersioning();

        $result = $this->db->pquery(
            'SELECT f.block, f.sequence, f.displaytype, f.masseditable, f.generatedtype, f.tablename, f.columnname
             FROM vtiger_field f WHERE f.tabid = ? AND f.fieldname = ?',
            [$this->tabId, RecordVersion_Config_Model::FIELD_NAME]
        );
        $this->assertSame(1, $this->db->num_rows($result), '何度「はい」にしても「版」項目は 1 つ');
        $row = $this->db->fetch_array($result);
        $this->assertSame('vtiger_crmentity', $row['tablename']);
        $this->assertSame('version', $row['columnname']);
        $this->assertSame('2', (string) $row['displaytype'], '作成・編集画面には出さない（読み取り専用）');
        $this->assertSame('0', (string) $row['masseditable'], '一括編集できない');
        $this->assertSame('1', (string) $row['generatedtype'], '標準項目として登録する（項目設定から削除できない）');

        $firstBlock = (int) $this->scalar('SELECT blockid FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1', [$this->tabId]);
        $this->assertSame($firstBlock, (int) $row['block'], 'モジュールの最初のブロックに置く');
    }

    public function test_いいえにするとボタンを外し過去の版と版番号は残しはいに戻すと続きから(): void
    {
        $this->enableVersioning();
        $recordId = $this->createPotential('いいえ');
        $this->assertTrue(RecordVersion_Revision_Model::revise($recordId, '1 回目')['success']);
        $this->assertSame(2, $this->linkCount());

        $this->disableVersioning();
        $this->assertSame(0, $this->linkCount(), '「改版」「版履歴」のボタンを出さない');
        $this->assertSame(1, $this->versionCount($recordId), '過去の版は消えない');
        $this->assertSame(2, $this->currentVersion($recordId), '版番号は変わらない');
        $result = RecordVersion_Revision_Model::revise($recordId, '対象外');
        $this->assertFalse($result['success'], '版管理「いいえ」のモジュールでは改版しない');
        $this->assertSame(vtranslate('LBL_NOT_TARGET_MODULE', 'RecordVersion'), $result['message']);

        $this->enableVersioning();
        $this->assertSame(2, $this->linkCount());
        $this->assertSame(2, $this->currentVersion($recordId), 'はいに戻しても版番号は続きから');
        $this->assertSame(3, RecordVersion_Revision_Model::revise($recordId, '2 回目')['version_no']);
    }

    public function test_版に含める関連の候補にメール活動履歴などを出さない(): void
    {
        foreach (RecordVersion_Setup_Model::getRelationCandidates(self::MODULE) as $candidate) {
            $this->assertNotContains($candidate['related_module'], RecordVersion_Config_Model::EXCLUDED_RELATED_MODULES);
        }
        $result = $this->db->pquery(
            'SELECT relation_id, name FROM vtiger_relatedlists WHERE tabid = ?',
            [$this->tabId]
        );
        $candidates = RecordVersion_Setup_Model::getRelationCandidates(self::MODULE);
        while ($row = $this->db->fetch_array($result)) {
            if (in_array($row['name'], RecordVersion_Config_Model::NON_LIST_RELATION_FUNCTIONS, true)) {
                $this->assertArrayNotHasKey((int) $row['relation_id'], $candidates, 'レコードの一覧ではない関連（' . $row['name'] . '）は候補に出さない');
            }
        }
        $this->assertNotEmpty($candidates);
    }

    public function test_対象外のモジュールは対象にできるモジュールの一覧に出さない(): void
    {
        $modules = RecordVersion_Setup_Model::getSupportedModules();
        foreach (RecordVersion_Config_Model::EXCLUDED_MODULES as $excluded) {
            $this->assertNotContains($excluded, $modules);
        }
        $this->assertContains(self::MODULE, $modules);
    }

    private function linkCount(): int
    {
        return (int) $this->scalar(
            "SELECT COUNT(*) AS cnt FROM vtiger_links WHERE tabid = ? AND linktype = 'DETAILVIEWBASIC' AND handler_class = 'RecordVersion_LinkVisibility_Helper'",
            [$this->tabId]
        );
    }
}
