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

namespace Tests\Unit\Migration;

use Migration20260825112920_SetupCronSchedulerStability as CronMigration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * setup_cron_scheduler_stability マイグレーション（config.inc.php への追記）— #1823
 *
 * 対象: setup/migration/scripts/20260825112920_setup_cron_scheduler_stability.php の applyTo()
 *
 * 実際の config.inc.php は触らず、一時ディレクトリへ作った複製に対して検証する。
 *
 *   1  設定が 1 つも書かれていないファイルには、扱う全キーを追記する（正常系）
 *   2  追記後のファイルが PHP として正しい
 *   3  追記位置は config.security.php の読み込みより前（ひな形と同じ並び）
 *   4  一部のキーだけが書かれている場合、書かれていないキーだけを追記する
 *   5  既に書かれているキーの値は変更しない（運用者が変えた値を壊さない）
 *   6  すべてのキーが書かれている場合は、ファイルを 1 バイトも変更しない
 *   7  コメントアウトされた記載も「記載済み」とみなす（説明を二重に書かない）
 *   8  複数サーバー向けの見出しは、該当するキーを書くときだけ出す
 *   9  同じキーが二重に書かれることはない（冪等性）
 *   10 追記した場合はバックアップを作る。変更しない場合は作らない（副作用）
 *   11 アンカー行が無いファイル（?> のみ／閉じタグ無し）にも追記できる（境界）
 *   12 ファイルが存在しない場合は例外を投げずスキップする（異常系）
 *
 * マイグレーションの基底クラス（setup/migration/FRMigrationClass.php）は
 * includes/runtime/LanguageHandler.php を実物で読み込む。tests/Support/ のスタブが
 * 同名クラスを定義するため同じプロセスに同居できない。独立したプロセスで動かし、
 * 読み込みも setUpBeforeClass 以降（＝子プロセス側）で行う。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CronSchedulerStabilityConfigTest extends TestCase
{
    /** マイグレーションが扱う設定キー */
    private const KEYS = [
        'cron_max_parallel', 'cron_serial_tasks', 'cron_default_retry_timeout',
        'cron_kill_timed_out', 'cron_log_retention_count', 'cron_heartbeat_timeout',
        'cron_host_name',
    ];

    private const SECTION_HEADER = 'アプリケーションサーバーが複数台ある構成向けの設定';

    private string $workDirectory = '';
    private int $sequence = 0;

    protected function setUp(): void
    {
        // 分離した子プロセス側で読み込む。setUpBeforeClass は親プロセスで動くため
        // ここで読まないと、スタブを持つ親で二重宣言になる。
        require_once dirname(__DIR__, 3)
            . '/setup/migration/scripts/20260825112920_setup_cron_scheduler_stability.php';

        $this->workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'frtest_cron_config_' . getmypid() . '_' . spl_object_id($this);
        if (!is_dir($this->workDirectory)) {
            mkdir($this->workDirectory, 0700, true);
        }
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->workDirectory)) {
            return;
        }
        foreach (glob($this->workDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->workDirectory);
    }

    /**
     * マイグレーションを実行する（ログは出力に混ぜない）。
     *
     * @return array<int, string> 追記したキー
     */
    private function apply(string $file): array
    {
        $migration = new CronMigration();
        ob_start();
        try {
            $added = $migration->applyTo($file);
        } finally {
            ob_end_clean();
        }

        return $added;
    }

    /** テスト用の config.inc.php を一時ディレクトリに作る */
    private function makeConfig(string $contents): string
    {
        $this->sequence++;
        $file = $this->workDirectory . DIRECTORY_SEPARATOR . 'config_' . $this->sequence . '.php';
        file_put_contents($file, $contents);
        @unlink($file . CronMigration::BACKUP_SUFFIX);

        return $file;
    }

    /** 実際の config.inc.php に近い、最小限の内容 */
    private function baseConfig(string $extra = ''): string
    {
        return "<?php\n"
            . "\$default_layout = 'v7';\n"
            . "\$maxListFieldsSelectionSize = 15;\n"
            . $extra
            . "\ninclude_once 'config.security.php';\n"
            . "?>\n";
    }

    /** 有効な代入（コメント行を除く）の件数 */
    private function countAssignments(string $contents, string $key): int
    {
        $count = 0;
        foreach (explode("\n", $contents) as $line) {
            $trimmed = ltrim($line);
            if (strpos($trimmed, '//') === 0 || strpos($trimmed, '#') === 0) {
                continue;
            }
            if (preg_match('/\$' . preg_quote($key, '/') . '\s*=/', $trimmed)) {
                $count++;
            }
        }

        return $count;
    }

    /** PHP として構文が通るか */
    private function isValidPhp(string $file): bool
    {
        $output = [];
        $status = 0;
        exec(sprintf(
            '%s -d xdebug.mode=off -l %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($file)
        ), $output, $status);

        return $status === 0;
    }

    public function test_1_2_3_10_設定が1つも無いファイルには全キーを追記する(): void
    {
        $file = $this->makeConfig($this->baseConfig());

        $added = $this->apply($file);

        self::assertSame(self::KEYS, $added, '1 扱う全キーが追記対象になる');

        $contents = (string) file_get_contents($file);
        foreach (self::KEYS as $key) {
            // cron_host_name はひな形と同じくコメントアウトした例として書き込む
            $expected = ($key === 'cron_host_name') ? 0 : 1;
            self::assertSame($expected, $this->countAssignments($contents, $key), $key . ' の有効な代入が ' . $expected . ' 件');
            self::assertStringContainsString('$' . $key, $contents, $key . ' が説明付きで記載される');
        }

        self::assertTrue($this->isValidPhp($file), '2 追記後も PHP として正しい');
        self::assertLessThan(
            strpos($contents, "include_once 'config.security.php';"),
            strpos($contents, '$cron_max_parallel'),
            '3 config.security.php の読み込みより前に追記される'
        );
        self::assertStringContainsString("\$default_layout = 'v7';", $contents, '元からあった設定は残る');
        self::assertFileExists($file . CronMigration::BACKUP_SUFFIX, '10 バックアップが作られる');
    }

    public function test_4_5_書かれていないキーだけを追記し既存の値は変えない(): void
    {
        // 運用者が 2 件だけ、しかも既定値とは違う値で設定している状況
        $existing = "\n// 既に運用者が設定している\n\$cron_max_parallel = 8;\n"
            . "\$cron_kill_timed_out = false;\n";
        $file = $this->makeConfig($this->baseConfig($existing));

        $added = $this->apply($file);

        self::assertSame(
            ['cron_serial_tasks', 'cron_default_retry_timeout', 'cron_log_retention_count',
                'cron_heartbeat_timeout', 'cron_host_name'],
            $added,
            '4 既に書かれている 2 件は追記対象から外れる'
        );

        $contents = (string) file_get_contents($file);
        self::assertSame(1, $this->countAssignments($contents, 'cron_max_parallel'), '4 cron_max_parallel が二重に書かれない');
        self::assertSame(1, $this->countAssignments($contents, 'cron_kill_timed_out'), '4 cron_kill_timed_out が二重に書かれない');
        self::assertStringContainsString('$cron_max_parallel = 8;', $contents, '5 運用者が設定した値（8）が変わらない');
        self::assertStringContainsString('$cron_kill_timed_out = false;', $contents, '5 運用者が設定した値（false）が変わらない');
        self::assertSame(1, $this->countAssignments($contents, 'cron_serial_tasks'), '4 書かれていなかったキーは追記される');
        self::assertSame(1, $this->countAssignments($contents, 'cron_heartbeat_timeout'), '4 書かれていなかったキーは追記される');
        self::assertTrue($this->isValidPhp($file), '2 追記後も PHP として正しい');
    }

    public function test_6_10_すべて書かれていればファイルを変更しない(): void
    {
        $all = "\n";
        foreach (self::KEYS as $key) {
            $all .= '$' . $key . " = 1;\n";
        }
        $file = $this->makeConfig($this->baseConfig($all));
        $before = (string) file_get_contents($file);

        $added = $this->apply($file);

        self::assertSame([], $added, '6 追記対象が 1 件も無い');
        self::assertSame($before, (string) file_get_contents($file), '6 ファイルが 1 バイトも変わらない');
        self::assertFileDoesNotExist($file . CronMigration::BACKUP_SUFFIX, '10 変更しないのでバックアップも作らない');
    }

    public function test_7_コメントアウトされた記載も記載済みとみなす(): void
    {
        $file = $this->makeConfig($this->baseConfig("\n// \$cron_host_name = 'app01';\n"));

        $added = $this->apply($file);

        self::assertNotContains('cron_host_name', $added, '7 コメントアウトされたキーは追記しない');

        $contents = (string) file_get_contents($file);
        self::assertSame(1, substr_count($contents, "\$cron_host_name = 'app01';"), '7 コメント行が二重にならない');
        self::assertSame(0, $this->countAssignments($contents, 'cron_host_name'), '7 コメントアウトのままにする（有効化しない）');
    }

    public function test_8_複数サーバー向けの見出しは必要なときだけ出す(): void
    {
        // 複数サーバー向けのキーが両方とも未記載 → 見出しを 1 回だけ出す
        $file = $this->makeConfig($this->baseConfig());
        $this->apply($file);
        self::assertSame(
            1,
            substr_count((string) file_get_contents($file), self::SECTION_HEADER),
            '8 該当キーが未記載なら見出しを 1 回出す'
        );

        // 複数サーバー向けのキーが両方とも記載済み → 見出しを出さない
        $file = $this->makeConfig($this->baseConfig("\n\$cron_heartbeat_timeout = 300;\n\$cron_host_name = 'app01';\n"));
        $this->apply($file);
        self::assertSame(
            0,
            substr_count((string) file_get_contents($file), self::SECTION_HEADER),
            '8 該当キーが記載済みなら見出しを出さない'
        );

        // 片方だけ未記載 → 見出しを出す
        $file = $this->makeConfig($this->baseConfig("\n\$cron_heartbeat_timeout = 300;\n"));
        $this->apply($file);
        self::assertSame(
            1,
            substr_count((string) file_get_contents($file), self::SECTION_HEADER),
            '8 片方が未記載なら見出しを出す'
        );
    }

    public function test_9_2回実行しても増えない(): void
    {
        $file = $this->makeConfig($this->baseConfig());
        $this->apply($file);
        $afterFirst = (string) file_get_contents($file);

        $added = $this->apply($file);

        self::assertSame([], $added, '9 2 回目は追記対象が無い');
        self::assertSame($afterFirst, (string) file_get_contents($file), '9 2 回目でファイルが変わらない');

        $contents = (string) file_get_contents($file);
        foreach (self::KEYS as $key) {
            $expected = ($key === 'cron_host_name') ? 0 : 1;
            self::assertSame($expected, $this->countAssignments($contents, $key), $key . ' の記載が増えない');
        }
        self::assertTrue($this->isValidPhp($file), '9 2 回目の後も PHP として正しい');
    }

    public function test_11_アンカー行が無いファイルにも追記できる(): void
    {
        $file = $this->makeConfig("<?php\n\$default_layout = 'v7';\n?>\n");
        $added = $this->apply($file);
        self::assertCount(count(self::KEYS), $added, '11 閉じタグのみ: 全件追記される');
        self::assertTrue($this->isValidPhp($file), '11 閉じタグのみ: PHP として正しい');
        $contents = (string) file_get_contents($file);
        self::assertLessThan(
            strrpos($contents, '?>'),
            strpos($contents, '$cron_max_parallel'),
            '11 閉じタグのみ: 閉じタグより前に追記される'
        );

        $file = $this->makeConfig("<?php\n\$default_layout = 'v7';\n");
        $added = $this->apply($file);
        self::assertCount(count(self::KEYS), $added, '11 閉じタグ無し: 全件追記される');
        self::assertTrue($this->isValidPhp($file), '11 閉じタグ無し: PHP として正しい');
    }

    public function test_12_ファイルが存在しない場合はスキップする(): void
    {
        $missing = $this->workDirectory . DIRECTORY_SEPARATOR . 'no_such_config.php';
        @unlink($missing);

        $added = $this->apply($missing);

        self::assertSame([], $added, '12 例外を投げずスキップする');
        self::assertFileDoesNotExist($missing, '12 ファイルを作らない');
    }
}
