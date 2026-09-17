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

use FRTestMigrationCacheProbe;
use PearDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vtiger_Cache;

/**
 * マイグレーション実行ごとの Vtiger_Cache クリア — #1429
 *
 * 対象: setup/migration/FRMigrationClass.php の execute()
 *
 * 一括実行では 1 プロセスで複数のマイグレーションが動くため、前のスクリプトが
 * キャッシュに載せたインスタンスを次のスクリプトが受け取らないことを確かめる。
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MigrationCacheClearTest extends TestCase
{
    private const MIGRATION_NAME = 'FRTestMigrationCacheProbe';

    private ?PearDatabase $db = null;

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
        // 分離した子プロセス側で読み込む。親プロセスにはスタブがあるため二重宣言になる。
        require_once dirname(__DIR__, 2) . '/Support/MigrationTestSupport.php';

        try {
            $result = $this->db()->pquery('SELECT 1 AS ok', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない');
        }

        $this->forgetMigrationRecord();
    }

    protected function tearDown(): void
    {
        $this->forgetMigrationRecord();
    }

    /** 検証用マイグレーションの実行記録を消し、何度でも実行できるようにする */
    private function forgetMigrationRecord(): void
    {
        try {
            $this->db()->pquery(
                'DELETE FROM com_vtiger_migrations WHERE migration_name = ?',
                [self::MIGRATION_NAME]
            );
        } catch (\Throwable $e) {
            // テーブル未作成なら消す記録も無い
        }
    }

    /** 検証用マイグレーションを実行する（ログは出力に混ぜない） */
    private function runProbeMigration(): FRTestMigrationCacheProbe
    {
        $migration = new FRTestMigrationCacheProbe();
        ob_start();
        try {
            $migration->execute();
        } finally {
            ob_get_clean();
        }

        return $migration;
    }

    public function test_前の処理が残した名前空間付きキャッシュを引き継がない(): void
    {
        Vtiger_Cache::set('module', 'FRTestMigrationModule', 'STALE_INSTANCE');

        $migration = $this->runProbeMigration();

        self::assertFalse($migration->seenNamespacedValue, 'マイグレーション開始時にキャッシュが空になっている');
    }

    public function test_前の処理が残した専用セッターのキャッシュを引き継がない(): void
    {
        Vtiger_Cache::getInstance()->setModuleName(987654, 'STALE_NAME');

        $migration = $this->runProbeMigration();

        self::assertFalse($migration->seenModuleName, 'マイグレーション開始時にキャッシュが空になっている');
    }
}
