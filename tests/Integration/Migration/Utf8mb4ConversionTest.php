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
 *  10 テーブルの既定が utf8mb4 でも、列が utf8mb3 のまま残っていれば変換する
 *  11 変換で TEXT 系の列の型が広がらない（新規インストールとスキーマを揃える）
 *  12 execute() が成功すると台帳（com_vtiger_migrations）に記録される
 *  13 変換に失敗すると台帳に記録されず、直してから実行し直せる
 *
 * 注意: このマイグレーションは接続中の DB 全体を対象にする。
 * そのためこのテストを実行すると、テスト用 DB のテーブルがすべて utf8mb4 に変換される。
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
    private const MIXED_TABLE = 'frtest_utf8mb4_mixed';
    private const TEXT_TABLE = 'frtest_utf8mb4_text';

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
        $tables = [
            self::LEGACY_TABLE, self::MODERN_TABLE, self::COMPACT_TABLE,
            self::TOO_LONG_INDEX_TABLE, self::MIXED_TABLE, self::TEXT_TABLE,
        ];
        foreach ($tables as $table) {
            $this->db()->pquery("DROP TABLE IF EXISTS `{$table}`", []);
        }
    }

    /**
     * マイグレーションを実行する。process() は進捗を echo するため、
     * PHPUnit の出力検査に引っかからないよう捨てたうえで内容を返す。
     */
    private function runMigration(): string
    {
        // 基底クラスのコンストラクタは台帳テーブルが無いと作成メッセージを出力する。
        // PHPUnit の出力検査に引っかからないよう、生成もバッファの内側で行う。
        ob_start();
        try {
            $migration = new Utf8mb4Migration();
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

    /**
     * 列の文字セット・型・NULL 可否・コメントを返す。
     *
     * @return array{collation: string, type: string, nullable: string, comment: string}
     */
    private function columnInfo(string $table, string $column): array
    {
        $result = $this->db()->pquery(
            'SELECT COLLATION_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        $row = $this->db()->fetchByAssoc($result);

        return [
            'collation' => (string) ($row['collation_name'] ?? ''),
            'type'      => (string) ($row['column_type'] ?? ''),
            'nullable'  => (string) ($row['is_nullable'] ?? ''),
            'comment'   => (string) ($row['column_comment'] ?? ''),
        ];
    }

    public function test_10_テーブルが既にutf8mb4でも列がutf8mb3なら変換する(): void
    {
        // 「ALTER TABLE ... DEFAULT CHARACTER SET utf8mb4」だけを当てた状態を作る。
        // テーブルの既定は utf8mb4 になるが、既存の列は utf8mb3 のまま残り、
        // 4 バイト文字を保存できない。応急処置として打たれていることがある。
        $this->db()->pquery(
            'CREATE TABLE `' . self::MIXED_TABLE . '` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(100) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci',
            []
        );
        $this->db()->pquery(
            'ALTER TABLE `' . self::MIXED_TABLE . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
            []
        );
        self::assertSame('utf8mb4_general_ci', $this->collationOf(self::MIXED_TABLE), '10 前提: テーブルの既定は utf8mb4');
        self::assertSame('utf8mb3_general_ci', $this->columnInfo(self::MIXED_TABLE, 'label')['collation'], '10 前提: 列は utf8mb3');

        $this->runMigration();

        self::assertSame(
            'utf8mb4_general_ci',
            $this->columnInfo(self::MIXED_TABLE, 'label')['collation'],
            '10 列が utf8mb3 のまま残り、4 バイト文字を保存できない'
        );

        $this->db()->pquery('INSERT INTO `' . self::MIXED_TABLE . '` (label) VALUES (?)', [self::SURROGATE_KANJI]);
        $result = $this->db()->pquery('SELECT label FROM `' . self::MIXED_TABLE . '` ORDER BY id LIMIT 1', []);
        $row = $this->db()->fetchByAssoc($result);

        self::assertSame(self::SURROGATE_KANJI, $row['label'] ?? '', '10 変換後も 4 バイト文字を保存できない');
    }

    public function test_11_変換でTEXT系の列の型が広がらない(): void
    {
        // CONVERT TO は「バイト数を保つ」ため TEXT を MEDIUMTEXT へ、MEDIUMTEXT を LONGTEXT へ広げる。
        // そのままだと新規インストールした DB とアップグレードした DB でスキーマが食い違う。
        $this->db()->pquery(
            "CREATE TABLE `" . self::TEXT_TABLE . "` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                body TEXT,
                note MEDIUMTEXT NOT NULL COMMENT '備考',
                label VARCHAR(100) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",
            []
        );

        $this->runMigration();

        $body = $this->columnInfo(self::TEXT_TABLE, 'body');
        $note = $this->columnInfo(self::TEXT_TABLE, 'note');

        self::assertSame('text', $body['type'], '11 TEXT が MEDIUMTEXT に広がっている');
        self::assertSame('mediumtext', $note['type'], '11 MEDIUMTEXT が LONGTEXT に広がっている');
        self::assertSame('utf8mb4_general_ci', $body['collation'], '11 TEXT 列が utf8mb4 になっていない');
        self::assertSame('utf8mb4_general_ci', $note['collation'], '11 MEDIUMTEXT 列が utf8mb4 になっていない');
        self::assertSame('NO', $note['nullable'], '11 NOT NULL が失われている');
        self::assertSame('備考', $note['comment'], '11 列コメントが失われている');
        self::assertSame('varchar(100)', $this->columnInfo(self::TEXT_TABLE, 'label')['type'], '11 VARCHAR の型が変わっている');
    }

    /**
     * 台帳（com_vtiger_migrations）にこのマイグレーションが記録されているか。
     */
    private function isRecordedInLedger(): bool
    {
        $result = $this->db()->pquery(
            'SELECT migration_name FROM com_vtiger_migrations WHERE migration_name = ?',
            ['Migration20260917072813_ConvertAllTablesToUtf8mb4']
        );

        return $this->db()->fetchByAssoc($result) !== null;
    }

    private function forgetLedgerEntry(): void
    {
        $this->db()->pquery(
            'DELETE FROM com_vtiger_migrations WHERE migration_name = ?',
            ['Migration20260917072813_ConvertAllTablesToUtf8mb4']
        );
    }

    /**
     * process() ではなく execute() で動かす。台帳の記録とトランザクション制御まで通る。
     */
    private function runMigrationThroughExecute(): string
    {
        ob_start();
        try {
            $migration = new Utf8mb4Migration();
            $migration->execute();
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    public function test_12_executeが成功すると台帳に記録される(): void
    {
        $this->forgetLedgerEntry();
        $this->createLegacyTable();
        self::assertFalse($this->isRecordedInLedger(), '12 前提: 実行前は台帳に記録されていない');

        $this->runMigrationThroughExecute();

        self::assertTrue($this->isRecordedInLedger(), '12 成功したのに台帳へ記録されていない');
        self::assertSame('utf8mb4_general_ci', $this->collationOf(self::LEGACY_TABLE), '12 テーブルが変換されていない');
    }

    public function test_13_失敗すると台帳に記録されず実行し直せる(): void
    {
        $this->forgetLedgerEntry();
        $this->createLegacyTable();
        $this->db()->pquery(
            'CREATE TABLE `' . self::TOO_LONG_INDEX_TABLE . '` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(1000) NOT NULL,
                UNIQUE KEY uq_label (label)
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci',
            []
        );

        self::assertFalse($this->isRecordedInLedger(), '13 前提: 実行前は台帳に記録されていない');

        $thrown = false;
        try {
            $this->runMigrationThroughExecute();
        } catch (\Exception $e) {
            $thrown = true;
        }

        self::assertTrue($thrown, '13 変換できないテーブルがあるのに例外にならない');
        self::assertFalse($this->isRecordedInLedger(), '13 失敗したのに台帳へ記録され、実行し直せなくなる');
        // ALTER TABLE は暗黙のコミットを起こすため、成功した分は取り消されない。
        self::assertSame(
            'utf8mb4_general_ci',
            $this->collationOf(self::LEGACY_TABLE),
            '13 変換できたテーブルまで巻き戻っている'
        );
    }
}
