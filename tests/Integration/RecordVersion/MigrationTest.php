<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use Migration20261109162036_SetupRecordVersion;
use Tests\Support\RecordVersionTestCase;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';
require_once dirname(__DIR__, 3) . '/setup/migration/scripts/20261109162036_setup_record_version.php';

/**
 * 版管理のマイグレーション（テーブル定義書 Ver1.1 の vtiger_recordversion）
 */
final class MigrationTest extends RecordVersionTestCase
{
    private const TABLE = 'vtiger_recordversion';
    private const BACKUP = 'vtiger_recordversion_spectest_backup';

    public function test_新しく作るテーブルはvalid_fromとモジュール改版日時のインデックスを持つ(): void
    {
        $this->db->pquery('RENAME TABLE ' . self::TABLE . ' TO ' . self::BACKUP, []);
        try {
            $this->runMigration();
            $this->assertSame('NO', $this->columnNullable('valid_from'), 'valid_from は NOT NULL');
            $this->assertTrue($this->indexExists('idx_recordversion_setype_revised'));
            $this->assertFalse($this->indexExists('idx_recordversion_setype'));
            $this->assertSame('utf8mb4_general_ci', $this->scalar(
                'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [self::TABLE]
            ));
        } finally {
            $this->db->pquery('DROP TABLE IF EXISTS ' . self::TABLE, []);
            $this->db->pquery('RENAME TABLE ' . self::BACKUP . ' TO ' . self::TABLE, []);
        }
    }

    public function test_先に作った形のテーブルを列の追加と補完とインデックスの付け替えで合わせる_何度流しても同じ(): void
    {
        $this->enableVersioning();
        $recordId = $this->createPotential('マイグレーション');
        \RecordVersion_Revision_Model::revise($recordId, '1 回目');
        \RecordVersion_Revision_Model::revise($recordId, '2 回目');
        $createdTime = $this->scalar('SELECT createdtime FROM vtiger_crmentity WHERE crmid = ?', [$recordId]);
        $firstRevisedAt = $this->scalar('SELECT revised_at FROM ' . self::TABLE . ' WHERE crmid = ? AND version_no = 1', [$recordId]);

        // 先に作った形（valid_from 無し・setype だけのインデックス）に戻す
        $this->db->pquery('ALTER TABLE ' . self::TABLE . ' DROP COLUMN valid_from', []);
        $this->db->pquery('ALTER TABLE ' . self::TABLE . ' DROP INDEX idx_recordversion_setype_revised', []);
        $this->db->pquery('ALTER TABLE ' . self::TABLE . ' ADD INDEX idx_recordversion_setype (setype)', []);

        $this->runMigration();
        $this->runMigration();

        $this->assertSame('NO', $this->columnNullable('valid_from'));
        $this->assertTrue($this->indexExists('idx_recordversion_setype_revised'));
        $this->assertFalse($this->indexExists('idx_recordversion_setype'));
        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE valid_from IS NULL'));
        $this->assertSame($createdTime, $this->scalar('SELECT valid_from FROM ' . self::TABLE . ' WHERE crmid = ? AND version_no = 1', [$recordId]), '版 1 はレコードの作成日時');
        $this->assertSame($firstRevisedAt, $this->scalar('SELECT valid_from FROM ' . self::TABLE . ' WHERE crmid = ? AND version_no = 2', [$recordId]), '版 2 は版 1 を改版した日時');
    }

    private function runMigration(): void
    {
        ob_start();
        try {
            (new Migration20261109162036_SetupRecordVersion())->process();
        } finally {
            ob_end_clean();
        }
    }

    private function columnNullable(string $column): string
    {
        return $this->scalar(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [self::TABLE, $column]
        );
    }

    private function indexExists(string $index): bool
    {
        return $this->scalar(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [self::TABLE, $index]
        ) !== '0';
    }
}
