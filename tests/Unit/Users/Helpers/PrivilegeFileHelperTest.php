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

namespace Tests\Unit\Users\Helpers;

use PHPUnit\Framework\TestCase;
use Users_PrivilegeFile_Helper;

require_once dirname(__DIR__, 4) . '/modules/Users/helpers/PrivilegeFile.php';

/**
 * 権限ファイル（user_privileges_*.php / sharing_privileges_*.php）の書き込みが
 * 読み込み側から見て原子的であることを確認する。
 * 書き込み途中の空ファイルを読んだリクエストがユーザー情報を空で保存する不具合（#1896）の回帰テスト。
 */
final class PrivilegeFileHelperTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/privilege_file_helper_test_' . bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        chmod($this->dir, 0755);
        foreach ($this->listFiles() as $file) {
            unlink($this->dir . '/' . $file);
        }
        rmdir($this->dir);
    }

    public function test_内容を書き込み一時ファイルを残さない(): void
    {
        $path = $this->dir . '/user_privileges_5.php';

        $this->assertTrue(Users_PrivilegeFile_Helper::write($path, "<?php\n\$is_admin=false;\n?>"));

        $this->assertSame("<?php\n\$is_admin=false;\n?>", file_get_contents($path));
        $this->assertSame(['user_privileges_5.php'], $this->listFiles());
    }

    public function test_既存ファイルの権限を引き継ぐ(): void
    {
        $path = $this->dir . '/user_privileges_5.php';
        file_put_contents($path, 'old');
        chmod($path, 0644);

        Users_PrivilegeFile_Helper::write($path, 'new');

        clearstatcache();
        $this->assertSame(0644, fileperms($path) & 0777);
    }

    public function test_既存ファイルを切り詰めずに置き換える(): void
    {
        $path = $this->dir . '/user_privileges_5.php';
        file_put_contents($path, 'old');
        $oldInode = fileinode($path);

        Users_PrivilegeFile_Helper::write($path, 'new');

        clearstatcache();
        $this->assertNotSame($oldInode, fileinode($path), '同じファイルを切り詰めて書くと読み込み側に空の瞬間が生じる');
        $this->assertSame('new', file_get_contents($path));
    }

    public function test_新規作成時はumaskに従った権限で作る(): void
    {
        $path = $this->dir . '/user_privileges_5.php';
        $umask = umask(0022);

        try {
            Users_PrivilegeFile_Helper::write($path, 'new');
        } finally {
            umask($umask);
        }

        clearstatcache();
        $this->assertSame(0644, fileperms($path) & 0777);
    }

    public function test_書き込めないディレクトリでは失敗し一時ファイルを他所に残さない(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root はディレクトリの書き込み権限を無視するため');
        }
        chmod($this->dir, 0555);
        $path = $this->dir . '/user_privileges_5.php';
        $before = glob(sys_get_temp_dir() . '/user_privileges_5.php.tmp*') ?: [];

        $this->assertFalse(@Users_PrivilegeFile_Helper::write($path, 'new'));

        $this->assertFileDoesNotExist($path);
        $this->assertSame($before, glob(sys_get_temp_dir() . '/user_privileges_5.php.tmp*') ?: []);
    }

    public function test_一時ファイルが別ディレクトリに作られる場合は書き込まない(): void
    {
        $path = $this->dir . '/missing/user_privileges_5.php';
        $before = glob(sys_get_temp_dir() . '/user_privileges_5.php.tmp*') ?: [];

        $this->assertFalse(@Users_PrivilegeFile_Helper::write($path, 'new'));

        $this->assertSame($before, glob(sys_get_temp_dir() . '/user_privileges_5.php.tmp*') ?: []);
    }

    public function test_書き込み中に並行して読んでも空や書きかけの内容を読まない(): void
    {
        $path = $this->dir . '/user_privileges_5.php';
        $content = "<?php\n\$user_info=" . var_export(['user_name' => str_repeat('x', 65536)], true) . ";\n?>";
        Users_PrivilegeFile_Helper::write($path, $content);

        $helper = dirname(__DIR__, 4) . '/modules/Users/helpers/PrivilegeFile.php';
        // 書き込みを始めてから合図を出し、読み込みは合図を受けてから計測する（起動の遅い環境で競合時間が縮まないように）
        $writer = sprintf(
            'require %s; $writes = 0; $end = microtime(true) + 1.0;'
            . ' while (microtime(true) < $end) { Users_PrivilegeFile_Helper::write(%s, %s); if ($writes++ === 0) { echo "ready\n"; fflush(STDOUT); } }'
            . ' echo $writes;',
            var_export($helper, true),
            var_export($path, true),
            var_export($content, true)
        );
        $process = proc_open([PHP_BINARY, '-r', $writer], [1 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        $this->assertSame("ready\n", fgets($pipes[1]));

        $reads = 0;
        $broken = 0;
        $end = microtime(true) + 0.9;
        while (microtime(true) < $end) {
            clearstatcache();
            if (@file_get_contents($path) !== $content) {
                $broken++;
            }
            $reads++;
        }
        $writes = (int) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($process);

        $this->assertGreaterThan(1, $writes, '書き込み側が動いていない');
        $this->assertGreaterThan(0, $reads);
        $this->assertSame(0, $broken, "{$reads} 回中 {$broken} 回、空または書きかけの内容を読んだ");
    }

    /**
     * @return list<string>
     */
    private function listFiles(): array
    {
        return array_values(array_diff(scandir($this->dir) ?: [], ['.', '..']));
    }
}
