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

namespace Tests\Unit\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * utf8mb4 の設定が各所に行き渡っているか — #77
 *
 * 絵文字や「𠮷」のような 4 バイト文字は utf8(utf8mb3) では保存できず、
 * 文字が切り捨てられたり「?」に置き換えられたりする。
 * 新規インストールした環境がその状態にならないよう、
 * 接続時の文字セット・DB 作成・初期スキーマ・テーブル生成のすべてを utf8mb4 に揃える。
 *
 *   1  DB 接続時の SET NAMES が utf8mb4
 *   2  インストーラの CREATE DATABASE が utf8mb4 / utf8mb4_general_ci
 *   3  インストーラの初期データ投入時の SET NAMES が utf8mb4
 *   4  カスタム項目テーブル生成時の charset が utf8mb4
 *   5  マイグレーション雛形の charset が utf8mb4
 *   6  初期データ SQL に utf8mb3 指定が残っていない
 *   7  テーブルを作る PHP に utf8mb3 指定が残っていない
 *   8  テーブル定義は collation まで明示する（MySQL 8 の既定 utf8mb4_0900_ai_ci との混在を防ぐ）
 */
final class Utf8mb4ConfigurationTest extends TestCase
{
    /**
     * charset・collation の指定のうち utf8mb4 以外を拾う正規表現。
     * utf8mb4_general_ci は「utf8」の次が「_」ではないため collation 側にはかからない。
     */
    private const LEGACY_CHARSET_PATTERN =
        '/(?:CHARSET\s*=\s*|SET\s+NAMES\s+|CHARACTER\s+SET\s+|COLLATE\s*=?\s*)utf8(?:mb3)?(?![0-9a-z])'
        . '|\butf8(?:mb3)?_[a-z0-9_]+/i';

    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private function contents(string $relativePath): string
    {
        $path = $this->projectRoot() . '/' . $relativePath;
        self::assertFileExists($path, $relativePath . ' が見つからない');

        return (string) file_get_contents($path);
    }

    /**
     * utf8mb4 以外の charset 指定を「行番号: 行」の一覧にして返す。
     *
     * @return list<string>
     */
    private function legacyCharsetLines(string $relativePath): array
    {
        $found = [];
        foreach (explode("\n", $this->contents($relativePath)) as $index => $line) {
            if (preg_match(self::LEGACY_CHARSET_PATTERN, $line) === 1) {
                $found[] = ($index + 1) . ': ' . trim($line);
            }
        }

        return $found;
    }

    /**
     * charset だけ指定して collation を省いている行を「行番号: 行」の一覧にして返す。
     *
     * MySQL 8 は charset だけ指定されたテーブルに utf8mb4 の既定 collation
     * （utf8mb4_0900_ai_ci）を当てる。F-RevoCRM は vtiger 互換のため
     * utf8mb4_general_ci を使うので、明示しないと DB とテーブルで collation がずれ、
     * 文字列を JOIN する箇所で照合順序の不一致エラーになる。
     *
     * @return list<string>
     */
    private function collationMissingLines(string $relativePath): array
    {
        $found = [];
        foreach (explode("\n", $this->contents($relativePath)) as $index => $line) {
            if (stripos($line, 'CHARSET=utf8mb4') === false) {
                continue;
            }
            if (stripos($line, 'utf8mb4_general_ci') === false) {
                $found[] = ($index + 1) . ': ' . trim($line);
            }
        }

        return $found;
    }

    public function test_1_DB接続時のSET_NAMESがutf8mb4(): void
    {
        self::assertStringContainsString(
            '"SET NAMES utf8mb4"',
            $this->contents('include/database/PearDatabase.php'),
            '1 接続のたびに実行する SET NAMES が utf8mb4 でなければ 4 バイト文字が欠落する'
        );
    }

    public function test_2_インストーラのCREATE_DATABASEがutf8mb4(): void
    {
        $contents = $this->contents('modules/Install/models/Utils.php');

        self::assertStringContainsString(
            'DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_general_ci',
            $contents,
            '2 新規作成する DB の既定 charset が utf8mb4 でなければ以降に作るテーブルも utf8mb3 になる'
        );
        self::assertSame([], $this->legacyCharsetLines('modules/Install/models/Utils.php'), '2 utf8mb3 指定が残っている');
    }

    public function test_3_インストーラの初期データ投入時のSET_NAMESがutf8mb4(): void
    {
        self::assertStringContainsString(
            "'SET NAMES utf8mb4'",
            $this->contents('modules/Install/views/Index.php'),
            '3 初期データ投入時の接続も utf8mb4 でなければ投入値が欠落する'
        );
    }

    public function test_4_カスタム項目テーブル生成時のcharsetがutf8mb4(): void
    {
        self::assertSame(
            [],
            $this->legacyCharsetLines('vtlib/Vtiger/Utils.php'),
            '4 vtlib がテーブルを作る際の charset が utf8mb3 だとカスタム項目に 4 バイト文字を保存できない'
        );
    }

    public function test_5_マイグレーション雛形のcharsetがutf8mb4(): void
    {
        self::assertSame(
            [],
            $this->legacyCharsetLines('setup/migration/generate_migration.php'),
            '5 雛形が utf8mb3 のままだと今後作るマイグレーションが utf8mb3 テーブルを増やす'
        );
    }

    public function test_6_初期データSQLにutf8mb3指定が残っていない(): void
    {
        $found = $this->legacyCharsetLines('setup/sql/dump_firstinstall.sql');

        self::assertSame(
            [],
            $found,
            '6 初期スキーマに utf8mb3 指定が ' . count($found) . ' 件残っている: '
            . implode(' / ', array_slice($found, 0, 5))
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tableCreatingPhpFiles(): array
    {
        return [
            '二要素認証のセットアップ' => ['setup/scripts/82_Add_TwoFactorAuth.php'],
            '言語コンバータ' => ['modules/Settings/LanguageConverter/models/Module.php'],
        ];
    }

    #[DataProvider('tableCreatingPhpFiles')]
    public function test_7_テーブルを作るPHPにutf8mb3指定が残っていない(string $relativePath): void
    {
        self::assertSame(
            [],
            $this->legacyCharsetLines($relativePath),
            '7 ' . $relativePath . ' が utf8mb3 のテーブルを作る'
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tableDefinitionFiles(): array
    {
        return [
            '初期データ SQL' => ['setup/sql/dump_firstinstall.sql'],
            'vtlib のテーブル生成' => ['vtlib/Vtiger/Utils.php'],
            'マイグレーション雛形' => ['setup/migration/generate_migration.php'],
            '二要素認証のセットアップ' => ['setup/scripts/82_Add_TwoFactorAuth.php'],
            '言語コンバータ' => ['modules/Settings/LanguageConverter/models/Module.php'],
        ];
    }

    #[DataProvider('tableDefinitionFiles')]
    public function test_8_テーブル定義はcollationまで明示する(string $relativePath): void
    {
        $found = $this->collationMissingLines($relativePath);

        self::assertSame(
            [],
            $found,
            '8 ' . $relativePath . ' に collation 未指定のテーブル定義が ' . count($found) . ' 件ある: '
            . implode(' / ', array_slice($found, 0, 5))
        );
    }
}
