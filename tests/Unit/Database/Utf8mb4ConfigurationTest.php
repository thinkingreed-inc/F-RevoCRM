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
 *   9  インストール時にデータベースの既定 collation を揃える
 */
final class Utf8mb4ConfigurationTest extends TestCase
{
    /**
     * charset・collation の指定のうち utf8mb4 以外を拾う正規表現。
     *
     * 照合順序は末尾が _ci / _bin のものだけを対象にする。
     * `$isdb_default_utf8_charset` のような変数名や utf8_encode() を拾わないため。
     */
    private const LEGACY_CHARSET_PATTERN =
        '/(?:CHARSET\s*=\s*|SET\s+NAMES\s+|CHARACTER\s+SET\s+|COLLATE\s*=?\s*)utf8(?:mb3)?(?![0-9a-zA-Z_])'
        . '|\butf8(?:mb3)?_(?:general_ci|unicode_ci|bin|[a-z0-9]+_ci|[a-z0-9]+_bin)\b/i';

    /** 走査する対象（プロジェクト直下からの相対パス） */
    private const SCAN_DIRECTORIES = ['setup', 'modules', 'vtlib', 'include', 'includes', 'cron', 'e2e'];

    /**
     * 走査する拡張子。テーブルを作る記述は .php / .sql のほか .inc にもある。
     *
     * .xml は対象にしない。modules/<Module>/schema.xml と packages 配下の manifest.xml は
     * vtlib/Vtiger/PackageImport.php がモジュール導入時に書き出す成果物で、
     * テーブル定義の出どころは packages/vtiger/**\/*.zip の側にある。
     * zip が作るテーブルは、インストールの最後に走る変換マイグレーションで utf8mb4 になる
     * （modules/Install/views/Index.php が initSchemas() のあとに upgrade() を呼ぶ）。
     */
    private const SCAN_EXTENSIONS = 'php|sql|sh|inc';

    /**
     * 走査から外す場所。
     *
     * modules/Migration/schema は 6.x / 7.x からのアップグレード専用スクリプトで、
     * そこで作られたテーブルは本 issue の変換マイグレーションが後から utf8mb4 にする。
     * include/simplehtmldom は外部ライブラリ。
     */
    private const SCAN_EXCLUDES = [
        'modules/Migration/schema',
        'include/simplehtmldom',
        'e2e/node_modules',
    ];

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
            // テーブル定義の「CHARSET=utf8mb4」だけを見る。
            // CAST(... CHARACTER SET utf8mb4) のような式は照合順序を別に指定するため対象外。
            if (preg_match('/CHARSET\s*=\s*utf8mb4\b/i', $line) !== 1) {
                continue;
            }
            if (preg_match('/\butf8mb4_[a-z0-9_]+/i', $line) !== 1) {
                $found[] = ($index + 1) . ': ' . trim($line);
            }
        }

        return $found;
    }

    public function test_1_DB接続時のSET_NAMESがutf8mb4(): void
    {
        // SET NAMES utf8mb4 だけだと、MySQL 8 では接続の照合順序が utf8mb4_0900_ai_ci になる。
        // テーブル側（utf8mb4_general_ci）と食い違い、CAST した値との比較で
        // 「Illegal mix of collations」になるため、照合順序まで指定する。
        self::assertStringContainsString(
            '"SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"',
            $this->contents('include/database/PearDatabase.php'),
            '1 接続の文字セットと照合順序が utf8mb4 / utf8mb4_general_ci でなければ 4 バイト文字の欠落や照合順序の不一致が起きる'
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
            "'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci'",
            $this->contents('modules/Install/views/Index.php'),
            '3 初期データ投入時の接続も utf8mb4 / utf8mb4_general_ci でなければ投入値が欠落する'
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
    /**
     * 走査対象のファイルを返す（プロジェクト直下からの相対パス）。
     *
     * @return list<string>
     */
    private function scanTargetFiles(): array
    {
        $root = $this->projectRoot();
        $files = [];

        foreach (self::SCAN_DIRECTORIES as $directory) {
            $base = $root . '/' . $directory;
            if (!is_dir($base)) {
                continue;
            }

            /** @var iterable<string, \SplFileInfo> $iterator */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($root) + 1);
                if (preg_match('/\.(' . self::SCAN_EXTENSIONS . ')$/', $relative) !== 1) {
                    continue;
                }
                foreach (self::SCAN_EXCLUDES as $exclude) {
                    if (str_starts_with($relative, $exclude)) {
                        continue 2;
                    }
                }
                $files[] = $relative;
            }
        }

        sort($files);

        return $files;
    }

    public function test_7_本体に利用時のutf8mb3指定が残っていない(): void
    {
        $found = [];
        foreach ($this->scanTargetFiles() as $relative) {
            foreach ($this->legacyCharsetLines($relative) as $line) {
                $found[] = $relative . ' ' . $line;
            }
        }

        self::assertSame(
            [],
            $found,
            '7 utf8mb3 の charset / 照合順序が ' . count($found) . ' 件残っている: '
            . implode(' / ', array_slice($found, 0, 5))
        );
    }

    public function test_8_テーブル定義はcollationまで明示する(): void
    {
        $found = [];
        foreach ($this->scanTargetFiles() as $relative) {
            foreach ($this->collationMissingLines($relative) as $line) {
                $found[] = $relative . ' ' . $line;
            }
        }

        self::assertSame(
            [],
            $found,
            '8 collation を指定していないテーブル定義が ' . count($found) . ' 件ある: '
            . implode(' / ', array_slice($found, 0, 5))
        );
    }

    public function test_9_インストール時にデータベースの既定collationを揃える(): void
    {
        // 利用者があらかじめ作った DB にインストールする場合、インストーラは CREATE DATABASE を
        // 通らない。MySQL 8 の既定は utf8mb4_0900_ai_ci なので、スキーマ側で明示している
        // utf8mb4_general_ci の表と混在し、文字列を JOIN する箇所で落ちる。
        $contents = $this->contents('modules/Install/models/InitSchema.php');

        self::assertMatchesRegularExpression(
            '/ALTER DATABASE.+utf8mb4.+utf8mb4_general_ci/s',
            $contents,
            '9 インストール時にデータベースの既定 charset / collation を揃えていない'
        );
    }
}
