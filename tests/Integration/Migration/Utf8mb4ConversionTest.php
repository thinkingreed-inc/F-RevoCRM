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

use Migration20260917072813_ConvertAllTablesToUtf8mb4 as Utf8mb4Migration;
use PearDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * convert_all_tables_to_utf8mb4 マイグレーション — #77
 *
 * 対象: setup/migration/scripts/20260917072813_convert_all_tables_to_utf8mb4.php
 *
 * utf8(utf8mb3) の既存 DB では絵文字や「𠮷」が保存できない（切り捨て・「?」化）。
 * 稼働中の環境を止めずに utf8mb4 へ寄せるためのマイグレーション。
 *
 *   1 utf8mb3 のテーブルが utf8mb4_general_ci に変換される
 *   2 変換後のテーブルに絵文字と「𠮷」を保存でき、そのまま取り出せる
 *   3 変換で既存データが壊れない
 *   4 既に utf8mb4 のテーブルは変換対象にしない（スキップ）
 *   5 データベースの既定 charset / collation も utf8mb4 になる
 *   6 ROW_FORMAT=COMPACT のテーブルは DYNAMIC にしてから変換する（インデックス長制限の回避）
 *   7 2 回実行しても結果が変わらない（冪等性）
 *   8 変換に失敗するテーブルがあれば例外を投げる（異常系）
 *   9 変換が失敗しても FOREIGN_KEY_CHECKS を元に戻す（後片付け）
 *
 * 検証用のテーブル（frtest_utf8mb4_ で始まる名前）だけを作って操作する。
 * マイグレーション自体は DB 全体を対象にするため、テスト用DB でのみ実行する。
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class Utf8mb4ConversionTest extends TestCase
{
    private const LEGACY_TABLE = 'frtest_utf8mb4_legacy';
    private const MODERN_TABLE = 'frtest_utf8mb4_modern';
    private const COMPACT_TABLE = 'frtest_utf8mb4_compact';
    private const TOO_LONG_INDEX_TABLE = 'frtest_utf8mb4_toolong';

    /** 4 バイト文字。絵文字とサロゲートペアの漢字（issue の「𠮷田」） */
    private const EMOJI = '絵文字😀テスト';
    private const SURROGATE_KANJI = '𠮷田';

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
        // 分離した子プロセス側で読み込む。親プロセスにはスタブが居るため二重宣言になる。
        $root = dirname(__DIR__, 3);
        require_once $root . '/setup/migration/scripts/20260917072813_convert_all_tables_to_utf8mb4.php';

        try {
            $result = $this->db()->pquery('SELECT DATABASE() AS db', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない');
        }

        $this->dropTestTables();
    }

    protected function tearDown(): void
    {
        $this->dropTestTables();
    }

    private function dropTestTables(): void
    {
        foreach ([self::LEGACY_TABLE, self::MODERN_TABLE, self::COMPACT_TABLE, self::TOO_LONG_INDEX_TABLE] as $table) {
            $this->db()->pquery("DROP TABLE IF EXISTS `{$table}`", []);
        }
    }

    /**
     * マイグレーションを実行する。process() は進捗を echo するため、
     * PHPUnit の出力検査に引っかからないよう捨てたうえで内容を返す。
     */
    private function runMigration(): string
    {
        $migration = new Utf8mb4Migration();

        ob_start();
        try {
            $migration->process();
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    private function collationOf(string $table): string
    {
        $result = $this->db()->pquery(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
        $row = $this->db()->fetchByAssoc($result);

        return (string) ($row['table_collation'] ?? '');
    }

    private function rowFormatOf(string $table): string
    {
        $result = $this->db()->pquery(
            'SELECT ROW_FORMAT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
        $row = $this->db()->fetchByAssoc($result);

        return (string) ($row['row_format'] ?? '');
    }

    private function createLegacyTable(): void
    {
        $this->db()->pquery(
            'CREATE TABLE `' . self::LEGACY_TABLE . '` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(255) DEFAULT NULL,
                INDEX idx_label (label)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci',
            []
        );
    }

    public function test_1_utf8mb3のテーブルがutf8mb4に変換される(): void
    {
        $this->createLegacyTable();
        self::assertSame('utf8mb3_general_ci', $this->collationOf(self::LEGACY_TABLE), '1 前提: 変換前は utf8mb3');

        $this->runMigration();

        self::assertSame(
            'utf8mb4_general_ci',
            $this->collationOf(self::LEGACY_TABLE),
            '1 utf8mb3 のテーブルが utf8mb4_general_ci にならない'
        );
    }

    public function test_2_変換後は絵文字とサロゲート文字を保存できる(): void
    {
        $this->createLegacyTable();
        $this->runMigration();

        $this->db()->pquery('INSERT INTO `' . self::LEGACY_TABLE . '` (label) VALUES (?), (?)', [self::EMOJI, self::SURROGATE_KANJI]);

        $result = $this->db()->pquery('SELECT label FROM `' . self::LEGACY_TABLE . '` ORDER BY id', []);
        $labels = [];
        while ($row = $this->db()->fetchByAssoc($result)) {
            $labels[] = $row['label'];
        }

        self::assertSame(
            [self::EMOJI, self::SURROGATE_KANJI],
            $labels,
            '2 4 バイト文字が切り捨てられたり「?」に置き換えられたりしている'
        );
    }

    public function test_3_変換で既存データが壊れない(): void
    {
        $this->createLegacyTable();
        $this->db()->pquery('INSERT INTO `' . self::LEGACY_TABLE . '` (label) VALUES (?)', ['日本語データ']);

        $this->runMigration();

        $result = $this->db()->pquery('SELECT label FROM `' . self::LEGACY_TABLE . '` ORDER BY id LIMIT 1', []);
        $row = $this->db()->fetchByAssoc($result);

        self::assertSame('日本語データ', $row['label'] ?? '', '3 変換前に入っていたデータが壊れている');
    }

    public function test_4_既にutf8mb4のテーブルはスキップする(): void
    {
        $this->db()->pquery(
            'CREATE TABLE `' . self::MODERN_TABLE . '` (
                id INT NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
            []
        );

        $output = $this->runMigration();

        self::assertSame('utf8mb4_general_ci', $this->collationOf(self::MODERN_TABLE), '4 既に utf8mb4 のテーブルの collation が変わった');
        self::assertStringNotContainsString(
            '変換完了: ' . self::MODERN_TABLE,
            $output,
            '4 既に utf8mb4 のテーブルを変換対象にしている'
        );
    }

    public function test_5_データベースの既定charsetがutf8mb4になる(): void
    {
        $this->runMigration();

        $result = $this->db()->pquery(
            'SELECT DEFAULT_CHARACTER_SET_NAME AS cs, DEFAULT_COLLATION_NAME AS co
             FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()',
            []
        );
        $row = $this->db()->fetchByAssoc($result);

        self::assertSame('utf8mb4', $row['cs'] ?? '', '5 データベースの既定 charset が utf8mb4 でない');
        self::assertSame('utf8mb4_general_ci', $row['co'] ?? '', '5 データベースの既定 collation が utf8mb4_general_ci でない');
    }

    public function test_6_COMPACTのテーブルはDYNAMICにしてから変換する(): void
    {
        // ROW_FORMAT=COMPACT はインデックスの接頭辞が 767 バイトまで。
        // VARCHAR(255) は utf8mb3 なら 765 バイトで収まるが utf8mb4 では 1020 バイトになり、
        // DYNAMIC にしないと変換時にインデックス長の上限を超える。
        $this->db()->pquery(
            'CREATE TABLE `' . self::COMPACT_TABLE . '` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(255) DEFAULT NULL,
                INDEX idx_label (label)
            ) ENGINE=InnoDB ROW_FORMAT=COMPACT DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci',
            []
        );
        self::assertSame('Compact', $this->rowFormatOf(self::COMPACT_TABLE), '6 前提: ROW_FORMAT=COMPACT で作られている');

        $this->runMigration();

        self::assertSame('utf8mb4_general_ci', $this->collationOf(self::COMPACT_TABLE), '6 COMPACT のテーブルを変換できていない');
        self::assertSame('Dynamic', $this->rowFormatOf(self::COMPACT_TABLE), '6 ROW_FORMAT が DYNAMIC になっていない');
    }

    public function test_7_2回実行しても結果が変わらない(): void
    {
        $this->createLegacyTable();

        $this->runMigration();
        $secondOutput = $this->runMigration();

        self::assertSame('utf8mb4_general_ci', $this->collationOf(self::LEGACY_TABLE), '7 2 回目の実行で collation が変わった');
        self::assertStringNotContainsString(
            '変換完了: ' . self::LEGACY_TABLE,
            $secondOutput,
            '7 2 回目でも変換対象にしている（冪等でない）'
        );
    }

    public function test_8_変換に失敗するテーブルがあれば例外を投げる(): void
    {
        // VARCHAR(1000) の一意キーは utf8mb3 なら 3000 バイトで作れるが、
        // utf8mb4 では 4000 バイトとなり DYNAMIC の上限 3072 バイトを超えるため変換できない。
        $this->db()->pquery(
            'CREATE TABLE `' . self::TOO_LONG_INDEX_TABLE . '` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(1000) NOT NULL,
                UNIQUE KEY uq_label (label)
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci',
            []
        );

        $this->expectException(\Exception::class);

        try {
            $this->runMigration();
        } finally {
            self::assertSame(
                'utf8mb3_general_ci',
                $this->collationOf(self::TOO_LONG_INDEX_TABLE),
                '8 前提: このテーブルは変換できない'
            );
        }
    }

    public function test_9_失敗してもFOREIGN_KEY_CHECKSを元に戻す(): void
    {
        $this->db()->pquery(
            'CREATE TABLE `' . self::TOO_LONG_INDEX_TABLE . '` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(1000) NOT NULL,
                UNIQUE KEY uq_label (label)
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci',
            []
        );

        try {
            $this->runMigration();
        } catch (\Exception $e) {
            // 例外は 8 で検証する。ここでは後片付けだけを見る。
        }

        $result = $this->db()->pquery('SELECT @@SESSION.foreign_key_checks AS fk', []);
        $row = $this->db()->fetchByAssoc($result);

        self::assertSame('1', (string) ($row['fk'] ?? ''), '9 外部キー制約の検査が無効のまま残っている');
    }
}
