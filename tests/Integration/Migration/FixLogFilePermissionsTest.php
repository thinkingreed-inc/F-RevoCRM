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

use Migration20260917021741_FixLogFilePermissions as LogPermissionMigration;
use PearDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * fix_log_file_permissions マイグレーション（既存ログの権限修正）— #1850
 *
 * 対象: setup/migration/scripts/20260917021741_fix_log_file_permissions.php の applyTo()
 *
 * 実際の logs/ は触らず、一時ディレクトリに作ったログファイルに対して検証する。
 * マイグレーションの基底クラス（FRMigrationClass）はコンストラクタで
 * com_vtiger_migrations の有無を問い合わせるため、インスタンスを作るだけで DB が要る。
 * そのため Unit ではなく Integration に置く。
 *
 *   1  所有者が読めない .log を 0666 に直す（正常系）
 *   2  既に 0666 のファイルは変更しない（冪等性）
 *   3  所有者が読める .log には触らない（意図的な権限設定を壊さない）
 *   4  .log 以外のファイルには触らない（対象の限定）
 *   5  サブディレクトリには触らない（対象の限定）
 *   6  シンボリックリンクのリンク先には触らない（対象の限定）
 *   7  パスに glob のメタ文字を含んでも処理する
 *   8  ディレクトリが存在しない場合も例外を投げない（異常系）
 *
 * 基底クラスは includes/runtime/LanguageHandler.php を実物で読み込む。tests/Support/ の
 * スタブが同名クラスを定義するため同じプロセスに同居できない。独立したプロセスで動かし、
 * 読み込みも setUp（＝子プロセス側）で行う。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FixLogFilePermissionsTest extends TestCase
{
    private string $tmpDir = '';

    private ?int $originalUmask = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 分離した子プロセス側で読み込む。setUpBeforeClass は親プロセスで動くため
        // ここで読まないと、スタブを持つ親で二重宣言になる。
        require_once dirname(__DIR__, 3)
            . '/setup/migration/scripts/20260917021741_fix_log_file_permissions.php';

        // 基底クラスがコンストラクタで DB を触るため、接続できなければ実行しない
        try {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT 1 AS ok', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない');
        }

        // chmod の結果が umask に左右されないようにする。
        $this->originalUmask = umask(0);

        $this->tmpDir = sys_get_temp_dir() . '/frevo_logmig_' . uniqid('', true);
        if (!mkdir($this->tmpDir, 0700, true) && !is_dir($this->tmpDir)) {
            $this->fail('一時ディレクトリを作成できませんでした: ' . $this->tmpDir);
        }
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir === '' || !is_dir($this->tmpDir)) {
            $this->restoreUmask();
            parent::tearDown();

            return;
        }

        foreach (scandir($this->tmpDir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $this->tmpDir . '/' . $name;
            if (is_dir($path)) {
                // テストが後始末に失敗した場合の保険
                foreach (scandir($path) ?: [] as $child) {
                    if ($child !== '.' && $child !== '..') {
                        chmod($path . '/' . $child, 0600);
                        unlink($path . '/' . $child);
                    }
                }
                rmdir($path);
                continue;
            }
            chmod($path, 0600);
            unlink($path);
        }
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }

        $this->restoreUmask();

        parent::tearDown();
    }

    /** setUp で 0 にした umask を元に戻す（setUp が途中で終わった場合は何もしない） */
    private function restoreUmask(): void
    {
        if ($this->originalUmask !== null) {
            umask($this->originalUmask);
            $this->originalUmask = null;
        }
    }

    /** 指定したパーミッションでファイルを作る */
    private function createFile(string $name, int $mode): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, "dummy\n");
        chmod($path, $mode);

        return $path;
    }

    /** 現在のパーミッションを 4 桁 8 進数文字列で返す */
    private function permsOf(string $path): string
    {
        clearstatcache(true, $path);

        return sprintf('%04o', fileperms($path) & 07777);
    }

    /**
     * マイグレーションを実行し、出力されたログを返す（出力には混ぜない）。
     */
    private function apply(string $logDir): string
    {
        $migration = new LogPermissionMigration();
        ob_start();
        try {
            $migration->applyTo($logDir);

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function test_読み取り権限の無いログを0666に直す(): void
    {
        $broken = $this->createFile('vtigercrm_20260915.log', 01232);

        $this->apply($this->tmpDir);

        $this->assertSame('0666', $this->permsOf($broken));
    }

    public function test_既に0666のログは変更しない(): void
    {
        $ok = $this->createFile('platform_20260917.log', 0666);
        $before = $this->permsOf($ok);

        $output = $this->apply($this->tmpDir);

        $this->assertSame($before, $this->permsOf($ok));
        $this->assertStringContainsString('変更: 0, スキップ: 1', $output, 'chmod せずスキップする');
    }

    public function test_log以外のファイルには触らない(): void
    {
        // 拡張子が .log のファイルだけを対象にしていることの確認
        $other = $this->createFile('notes.txt', 0200);

        $this->apply($this->tmpDir);

        $this->assertSame('0200', $this->permsOf($other));
    }

    public function test_サブディレクトリには触らない(): void
    {
        $subDir = $this->tmpDir . '/cron';
        mkdir($subDir, 0700);
        $nested = $subDir . '/dispatch.log';
        file_put_contents($nested, "dummy\n");
        chmod($nested, 0200);

        $this->apply($this->tmpDir);

        $this->assertSame('0700', $this->permsOf($subDir), 'サブディレクトリ自体');
        $this->assertSame('0200', $this->permsOf($nested), 'サブディレクトリ配下のログ');

        unlink($nested);
        rmdir($subDir);
    }

    public function test_所有者が読めるログには触らない(): void
    {
        // 運用者が意図的に絞った権限（0600）を緩めてはならない
        $restricted = $this->createFile('mcp_audit.log', 0600);

        $this->apply($this->tmpDir);

        $this->assertSame('0600', $this->permsOf($restricted));
    }

    public function test_シンボリックリンクのリンク先には触らない(): void
    {
        // リンク越しの chmod はリンク先を書き換えてしまうため、リンクは対象外にする
        $target = $this->createFile('secret.dat', 0200);
        $link = $this->tmpDir . '/linked.log';
        if (!@symlink($target, $link)) {
            self::markTestSkipped('シンボリックリンクを作成できない環境のため実行しない');
        }

        try {
            $this->apply($this->tmpDir);

            $this->assertSame('0200', $this->permsOf($target), 'リンク先の権限を書き換えない');
        } finally {
            unlink($link);
        }
    }

    public function test_パスにglobのメタ文字を含んでも処理する(): void
    {
        // glob() はパス側の [ ] * ? もパターンとして解釈するため、
        // そのまま渡すと該当ディレクトリが 0 件になり黙って何もしない
        $dir = $this->tmpDir . '/logs[1]';
        mkdir($dir, 0700);
        $broken = $dir . '/vtigercrm_20260915.log';
        file_put_contents($broken, "dummy\n");
        chmod($broken, 01232);

        try {
            $this->apply($dir);

            $this->assertSame('0666', $this->permsOf($broken));
        } finally {
            chmod($broken, 0600);
            unlink($broken);
            rmdir($dir);
        }
    }

    public function test_ディレクトリが無くても例外を投げない(): void
    {
        $this->expectNotToPerformAssertions();

        $this->apply($this->tmpDir . '/not_exists');
    }
}
