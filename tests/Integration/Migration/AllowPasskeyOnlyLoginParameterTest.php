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

namespace Tests\Integration\Migration;

use PearDatabase;
use PHPUnit\Framework\TestCase;

/**
 * システム変数 ALLOW_PASSKEY_ONLY_LOGIN を追加するマイグレーション
 *
 * 本物の vtiger_parameters は退避し、終了時に戻す。テスト用DB でのみ実行する。
 */
final class AllowPasskeyOnlyLoginParameterTest extends TestCase
{
    private const KEY = 'ALLOW_PASSKEY_ONLY_LOGIN';
    private const BACKUP_TABLE = 'frtest_parameters_backup_passkey';
    private const MIGRATION_FILE = '/setup/migration/scripts/20261027000000_add_allow_passkey_only_login_parameter.php';

    private ?PearDatabase $db = null;

    private bool $backedUp = false;

    private function db(): PearDatabase
    {
        $db = $this->db;
        if (!$db instanceof PearDatabase) {
            $db = PearDatabase::getInstance();
            $this->db = $db;
        }

        return $db;
    }

    protected function setUp(): void
    {
        try {
            $result = $this->db()->pquery('SELECT DATABASE() AS db', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない');
        }

        // 前回の実行が異常終了して tearDown が走らなかった場合は、退避した本物を先に戻す。
        if ($this->tableExists(self::BACKUP_TABLE)) {
            $this->db()->pquery('DROP TABLE IF EXISTS `vtiger_parameters`', []);
            $this->db()->pquery('RENAME TABLE `' . self::BACKUP_TABLE . '` TO `vtiger_parameters`', []);
        }
        if (!$this->tableExists('vtiger_parameters')) {
            self::markTestSkipped('vtiger_parameters が無いため実行しない');
        }
        $this->db()->pquery('CREATE TABLE `' . self::BACKUP_TABLE . '` LIKE `vtiger_parameters`', []);
        $this->db()->pquery('INSERT INTO `' . self::BACKUP_TABLE . '` SELECT * FROM `vtiger_parameters`', []);
        $this->backedUp = true;

        $this->db()->pquery('DELETE FROM `vtiger_parameters` WHERE `key` = ?', [self::KEY]);
    }

    protected function tearDown(): void
    {
        if ($this->db === null || !$this->backedUp) {
            return;
        }
        $this->db()->pquery('DELETE FROM `vtiger_parameters`', []);
        $this->db()->pquery('INSERT INTO `vtiger_parameters` SELECT * FROM `' . self::BACKUP_TABLE . '`', []);
        $this->db()->pquery('DROP TABLE IF EXISTS `' . self::BACKUP_TABLE . '`', []);
    }

    public function test初期値falseのboolean型で追加される(): void
    {
        $this->runMigration();

        $rows = $this->rows();
        self::assertCount(1, $rows);
        self::assertSame('false', $rows[0]['value']);
        self::assertSame('boolean', $rows[0]['type']);
        self::assertSame(0, $rows[0]['secret']);
        self::assertStringContainsString('パスキーのみでのログイン', $rows[0]['description']);
    }

    public function test2回実行しても1行のまま(): void
    {
        $this->runMigration();
        $this->runMigration();

        self::assertCount(1, $this->rows());
    }

    public function test既に存在する場合は値を上書きしない(): void
    {
        $this->runMigration();
        $this->db()->pquery('UPDATE `vtiger_parameters` SET `value` = ? WHERE `key` = ?', ['true', self::KEY]);

        $this->runMigration();

        $rows = $this->rows();
        self::assertCount(1, $rows);
        self::assertSame('true', $rows[0]['value']);
        self::assertSame('boolean', $rows[0]['type']);
    }

    /**
     * @return list<array{value: string, type: string, secret: int, description: string}>
     */
    private function rows(): array
    {
        $result = $this->db()->pquery(
            'SELECT `value`, `type`, `secret`, `description` FROM `vtiger_parameters` WHERE `key` = ?',
            [self::KEY]
        );
        self::assertNotFalse($result);
        $rows = [];
        for ($i = 0; $i < $this->db()->num_rows($result); $i++) {
            $rows[] = [
                'value' => $this->column($result, $i, 'value'),
                'type' => $this->column($result, $i, 'type'),
                'secret' => (int) $this->column($result, $i, 'secret'),
                'description' => $this->column($result, $i, 'description'),
            ];
        }

        return $rows;
    }

    private function column(mixed $result, int $row, string $name): string
    {
        $value = $this->db()->query_result($result, $row, $name);

        return is_scalar($value) ? (string) $value : '';
    }

    private function runMigration(): void
    {
        global $adb, $default_language;
        $adb = $this->db();
        $default_language = 'ja_jp';

        $file = dirname(__DIR__, 3) . self::MIGRATION_FILE;
        self::assertFileExists($file);
        require_once dirname(__DIR__, 3) . '/setup/migration/FRMigrationClass.php';
        // テスト bootstrap は Vtiger_Loader を外している。Settings_Parameters_Record_Model を読み込めるよう登録する。
        spl_autoload_register(['Vtiger_Loader', 'autoLoad']);

        $before = get_declared_classes();
        require_once $file;
        $declared = array_values(array_filter(
            array_diff(get_declared_classes(), $before),
            static fn (string $class): bool => is_subclass_of($class, 'FRMigrationClass')
        ));
        if ($declared === []) {
            // 2 回目以降は require_once が何も宣言しない。既に読み込み済みのクラスを探す。
            $declared = array_values(array_filter(
                get_declared_classes(),
                static fn (string $class): bool => str_starts_with($class, 'Migration20261027000000_')
            ));
        }
        self::assertCount(1, $declared);

        ob_start();
        try {
            $migration = new $declared[0]();
            if (!$migration instanceof \FRMigrationClass) {
                self::fail('FRMigrationClass を継承していない');
            }
            $migration->process();
        } finally {
            ob_end_clean();
        }
    }

    private function tableExists(string $table): bool
    {
        $result = $this->db()->pquery('SHOW TABLES LIKE ?', [$table]);

        return $this->db()->num_rows($result) > 0;
    }
}
