<?php

declare(strict_types=1);

namespace Tests\Integration\Migration;

use Migration20261029100000_AddAutonumberRenumber as RenumberMigration;
use Tests\Support\DocumentsTestCase;

require_once dirname(__DIR__, 2) . '/Support/DocumentsTestCase.php';

/**
 * add_autonumber_renumber マイグレーション
 *
 * 対象: setup/migration/scripts/20261029100000_add_autonumber_renumber.php
 *
 *   - ジョブのテーブルに mode / cursor_created / cursor_id の列を足し、振り直しの記録のテーブルを作る
 *   - 2 回実行しても重複しない・既にあるジョブの行は書き換えない（mode は fill のまま）
 *
 * 基底クラスが本体を読み込むため、本体を起動する結合テストの共通基盤の上で動かす。
 */
final class AddAutonumberRenumberTest extends DocumentsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261029100000_add_autonumber_renumber.php';
        if ($this->rowCount("SHOW TABLES LIKE 'vtiger_autonumber_job'") === 0) {
            self::markTestSkipped('テスト用DBに自動採番のマイグレーションが適用されていない');
        }
    }

    public function test_列と記録のテーブルが用意され2回実行しても重複しない(): void
    {
        $this->runMigration();
        $this->runMigration();

        foreach (['mode', 'cursor_created', 'cursor_id'] as $column) {
            $this->assertSame(1, $this->rowCount("SHOW COLUMNS FROM vtiger_autonumber_job LIKE '{$column}'"), $column);
        }
        $this->assertSame(1, $this->rowCount("SHOW TABLES LIKE 'vtiger_autonumber_job_log'"));
        foreach (['jobid', 'crmid', 'old_number', 'new_number'] as $column) {
            $this->assertSame(1, $this->rowCount("SHOW COLUMNS FROM vtiger_autonumber_job_log LIKE '{$column}'"), $column);
        }
    }

    public function test_mode_の既定は_fill_で既にあるジョブの行は書き換わらない(): void
    {
        $this->db->pquery(
            "INSERT INTO vtiger_autonumber_job (fieldid, status, total, userid, created_at) VALUES (-9999, 'done', 7, 1, ?)",
            [date('Y-m-d H:i:s')]
        );
        try {
            $this->runMigration();

            $result = $this->db->pquery('SELECT mode, total, cursor_id FROM vtiger_autonumber_job WHERE fieldid = -9999', []);
            $this->assertSame('fill', (string) $this->db->query_result($result, 0, 'mode'));
            $this->assertSame('7', (string) $this->db->query_result($result, 0, 'total'));
            $this->assertSame('0', (string) $this->db->query_result($result, 0, 'cursor_id'));
        } finally {
            $this->db->pquery('DELETE FROM vtiger_autonumber_job WHERE fieldid = -9999', []);
        }
    }

    private function runMigration(): void
    {
        // 進行状況のログは標準出力に出るため、テストの出力にしない
        ob_start();
        try {
            (new RenumberMigration())->process();
        } finally {
            ob_end_clean();
        }
    }

    private function rowCount(string $sql): int
    {
        $result = $this->db->pquery($sql, []);
        return $result === false ? 0 : (int) $this->db->num_rows($result);
    }
}
