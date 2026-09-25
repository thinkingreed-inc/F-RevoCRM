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

namespace Tests\Unit\Logging;

use Logger;
use PHPUnit\Framework\TestCase;

/**
 * ログファイルのパーミッション回帰検知テスト。
 *
 * libraries/log4php/Logger.php が Monolog の RotatingFileHandler に渡す
 * filePermission は 8 進数リテラルである必要がある。
 * 10 進数 666 を渡すと 8 進数 1232 (--w--wx-wT) となり読み取り不可になる。
 * 期待値の 0666 は、cron と Web で実行ユーザーが異なる環境でも追記できるようにするため。
 */
final class LoggerFilePermissionTest extends TestCase
{
    private string $tmpDir = '';

    private ?int $originalUmask = null;

    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3) . '/libraries/log4php/Logger.php';

        // umask に結果が左右されないようにする。
        // umask を効かせたままだと filePermission 指定漏れ (null) でも
        // 実行環境次第で 0644 になってしまい、回帰を検出できない。
        $this->originalUmask = umask(0);

        $this->tmpDir = sys_get_temp_dir() . '/frevo_logperm_' . uniqid('', true);
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
            unlink($this->tmpDir . '/' . $name);
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

    public function test_ログファイルが0666で作成される(): void
    {
        $logger = new Logger('TEST', [
            'File'            => $this->tmpDir . '/test.log',
            'MaxBackupIndex'  => 1,
            'level'           => 'DEBUG',
        ]);

        $logger->info('permission check');

        $created = glob($this->tmpDir . '/test_*.log') ?: [];
        $this->assertCount(1, $created, 'ログファイルが生成されていません');

        clearstatcache(true, $created[0]);
        $perms = fileperms($created[0]) & 07777;

        $this->assertSame(
            '0666',
            sprintf('%04o', $perms),
            'ログファイルのパーミッションが 0666 ではありません'
        );
    }
}
