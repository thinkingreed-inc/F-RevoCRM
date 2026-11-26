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
use LoggerPropertyConfigurator;
use PHPUnit\Framework\TestCase;

/**
 * Logger::getLogger() が同じ名前の Logger を使い回すことの回帰検知テスト。
 *
 * 使い回さないと呼ばれるたびに RotatingFileHandler が作られ、ログを書いた時点で
 * ファイルが開かれる。Logger を持つオブジェクトが残り続けると、開いたファイルが
 * 1 つずつ増え、プロセスのファイル数の上限 (cron の既定は 1024) に達する。
 * (thinkingreed-inc/F-RevoCRM#1899: DEBUG のときインポートが約 1,000 件で止まる)
 */
final class LoggerInstanceReuseTest extends TestCase
{
    private string $tmpDir = '';

    /** @var array<string, mixed>|null setUp 前の設定。tearDown で戻す */
    private ?array $originalTypes = null;

    private string $loggerName = '';

    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3) . '/libraries/log4php/Logger.php';

        $this->tmpDir = sys_get_temp_dir() . '/frevo_logreuse_' . uniqid('', true);
        if (!mkdir($this->tmpDir, 0700, true) && !is_dir($this->tmpDir)) {
            $this->fail('一時ディレクトリを作成できませんでした: ' . $this->tmpDir);
        }

        // Logger はクラス内で名前ごとに保持されるため、テストごとに別の名前を使う
        $this->loggerName = 'REUSE_TEST_' . str_replace('.', '_', uniqid('', true));

        // log4php.properties を読み直さず、このテスト用の名前の設定だけを足す
        $configurator = LoggerPropertyConfigurator::getInstance();
        $this->originalTypes = is_array($configurator->types) ? $configurator->types : [];
        $types = $this->originalTypes;
        $types[$this->loggerName] = [
            'level'          => 'DEBUG',
            'MaxBackupIndex' => 1,
            'File'           => $this->tmpDir . '/reuse.log',
        ];
        $configurator->types = $types;
    }

    protected function tearDown(): void
    {
        if ($this->originalTypes !== null) {
            LoggerPropertyConfigurator::getInstance()->types = $this->originalTypes;
            $this->originalTypes = null;
        }

        if ($this->tmpDir !== '' && is_dir($this->tmpDir)) {
            foreach (scandir($this->tmpDir) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                unlink($this->tmpDir . '/' . $name);
            }
            rmdir($this->tmpDir);
        }

        parent::tearDown();
    }

    public function test_同じ名前で呼ぶと同じLoggerを返す(): void
    {
        $first = Logger::getLogger($this->loggerName);
        $second = Logger::getLogger($this->loggerName);

        $this->assertSame($first, $second, '同じ名前なのに別の Logger が作られています');
    }

    public function test_Loggerを保持したまま繰り返し呼んでも開いているファイルが増えない(): void
    {
        if (!is_dir('/proc/self/fd')) {
            $this->markTestSkipped('/proc/self/fd がない環境では開いているファイル数を数えられません');
        }

        // インポートで作成したレコード (Leads など) がコンストラクタで Logger を取得し、
        // debug を書いたまま残り続ける状況を再現する
        $holders = [];
        // ハンドラは最初の数回の書き込みのうちにログファイルを開く。開いた後の状態から数える
        for ($i = 0; $i < 3; $i++) {
            $logger = Logger::getLogger($this->loggerName);
            $logger->debug('warm up ' . $i);
            $holders[] = $logger;
        }
        $before = $this->countOpenFiles();

        for ($i = 0; $i < 50; $i++) {
            $logger = Logger::getLogger($this->loggerName);
            $logger->debug('record ' . $i);
            $holders[] = $logger;
        }

        $after = $this->countOpenFiles();

        $this->assertSame(
            $before,
            $after,
            sprintf('Logger を 50 回取得したら開いているファイルが %d 個増えました', $after - $before)
        );
    }

    private function countOpenFiles(): int
    {
        clearstatcache();

        return count(scandir('/proc/self/fd') ?: []) - 2;
    }
}
