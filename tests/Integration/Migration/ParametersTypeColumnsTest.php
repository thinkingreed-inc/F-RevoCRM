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
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * システム変数（vtiger_parameters）の type / secret カラム — #1908
 *
 * Settings_Parameters_Record_Model::save() は type / secret へ書き込む。
 * カラムが無い状態で save() を呼ぶと Unknown column になり、新規インストールが中断する。
 *
 *   1 新規インストール時に 03_Make_Parameters.php が作るテーブルに type / secret がある
 *   2 type / secret が無いテーブルから、システム変数を扱うマイグレーションをファイル名順に
 *     実行しても完走し、追加されたシステム変数に型が設定される
 *
 * 本物の vtiger_parameters は退避し、終了時に戻す。テスト用DB でのみ実行する。
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ParametersTypeColumnsTest extends TestCase
{
    private const BACKUP_TABLE = 'frtest_parameters_backup';

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
        // 分離した子プロセス側で読み込む。親プロセスにはスタブが居るため二重宣言になる。
        $root = dirname(__DIR__, 3);
        require_once $root . '/setup/migration/FRMigrationClass.php';
        // テスト bootstrap は Vtiger_Loader を外している。Settings_Parameters_Record_Model を
        // 読み込めるよう、この子プロセスでは登録し直す。
        spl_autoload_register(['Vtiger_Loader', 'autoLoad']);

        try {
            $result = $this->db()->pquery('SELECT DATABASE() AS db', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない');
        }

        // 前回の実行が異常終了して tearDown が走らなかった場合、退避した本物が残っている。
        // 消さずに先に戻してから退避し直す。
        if ($this->tableExists(self::BACKUP_TABLE)) {
            $this->db()->pquery('DROP TABLE IF EXISTS `vtiger_parameters`', []);
            $this->db()->pquery('RENAME TABLE `' . self::BACKUP_TABLE . '` TO `vtiger_parameters`', []);
        }
        if ($this->tableExists('vtiger_parameters')) {
            $this->db()->pquery('RENAME TABLE `vtiger_parameters` TO `' . self::BACKUP_TABLE . '`', []);
            $this->backedUp = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->db === null) {
            return;
        }
        if ($this->backedUp) {
            $this->db()->pquery('DROP TABLE IF EXISTS `vtiger_parameters`', []);
            $this->db()->pquery('RENAME TABLE `' . self::BACKUP_TABLE . '` TO `vtiger_parameters`', []);
        }
    }

    public function testMakeParametersScriptCreatesTypeAndSecretColumns(): void
    {
        $this->runSetupScript('setup/scripts/03_Make_Parameters.php');

        self::assertTrue($this->columnExists('type'), '新規作成したテーブルに type が無い');
        self::assertTrue($this->columnExists('secret'), '新規作成したテーブルに secret が無い');
    }

    public function testParameterMigrationsCompleteOnTableWithoutTypeAndSecret(): void
    {
        // #1469 より前の 03_Make_Parameters.php と同じスキーマ
        $this->db()->pquery(
            'CREATE TABLE `vtiger_parameters` (
                `id` int(19) NOT NULL AUTO_INCREMENT,
                `key` varchar(200) DEFAULT NULL,
                `value` text DEFAULT NULL,
                `description` text,
                PRIMARY KEY (`id`),
                KEY `key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
            []
        );

        foreach ($this->parameterMigrationFiles() as $file) {
            $this->runMigrationFile($file);
        }

        self::assertTrue($this->columnExists('type'), 'type カラムが追加されていない');
        self::assertTrue($this->columnExists('secret'), 'secret カラムが追加されていない');
        self::assertSame('integer', $this->typeOf('IMPORT_MAX_HISTORY_COUNT'));
        self::assertSame('boolean', $this->typeOf('SHOW_SCHEDULE_CONFIRM_FLAG'));
        self::assertSame('boolean', $this->typeOf('CALENDAR_REMEMBER_FEED_SELECTION'));
    }

    /**
     * システム変数を扱うマイグレーションを、run_migration.php と同じファイル名順で返す。
     *
     * @return list<string>
     */
    private function parameterMigrationFiles(): array
    {
        $files = glob(dirname(__DIR__, 3) . '/setup/migration/scripts/*.php');
        self::assertIsArray($files);
        sort($files);

        $targets = [];
        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            if (preg_match('/vtiger_parameters|Settings_Parameters_Record_Model/', $source) === 1) {
                $targets[] = $file;
            }
        }
        self::assertNotEmpty($targets);

        return $targets;
    }

    /**
     * マイグレーションを 1 本実行する。process() は進捗を echo するため捨てる。
     */
    private function runMigrationFile(string $file): void
    {
        $before = get_declared_classes();
        require_once $file;
        $declared = array_values(array_filter(
            array_diff(get_declared_classes(), $before),
            static fn (string $class): bool => is_subclass_of($class, 'FRMigrationClass')
        ));
        self::assertCount(1, $declared, basename($file) . ' からマイグレーションクラスを特定できない');

        ob_start();
        try {
            $migration = new $declared[0]();
            $migration->process();
        } finally {
            ob_end_clean();
        }
    }

    private function runSetupScript(string $relativePath): void
    {
        global $adb;
        $adb = $this->db();

        ob_start();
        try {
            include dirname(__DIR__, 3) . '/' . $relativePath;
        } finally {
            ob_end_clean();
        }
    }

    private function tableExists(string $table): bool
    {
        $result = $this->db()->pquery('SHOW TABLES LIKE ?', [$table]);

        return $this->db()->num_rows($result) > 0;
    }

    private function columnExists(string $column): bool
    {
        $result = $this->db()->pquery('SHOW COLUMNS FROM `vtiger_parameters` LIKE ?', [$column]);

        return $result !== false && $this->db()->num_rows($result) > 0;
    }

    private function typeOf(string $key): string
    {
        $result = $this->db()->pquery('SELECT `type` FROM `vtiger_parameters` WHERE `key` = ?', [$key]);
        self::assertNotFalse($result, "{$key} の type を取得できない");
        self::assertSame(1, $this->db()->num_rows($result), "{$key} が登録されていない");

        return (string) $this->db()->query_result($result, 0, 'type');
    }
}
