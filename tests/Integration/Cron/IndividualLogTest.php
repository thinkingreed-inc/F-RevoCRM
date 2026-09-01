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

namespace Tests\Integration\Cron;

use FR_CronDispatcher;
use PHPUnit\Framework\TestCase;
use Tests\Support\CronTestSupport;
use Vtiger_Cron;

require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

$cronTestRoot = dirname(__DIR__, 3);

require_once $cronTestRoot . '/include/database/PearDatabase.php';
require_once $cronTestRoot . '/include/utils/CommonUtils.php';
require_once $cronTestRoot . '/vtlib/Vtiger/Cron.php';
require_once $cronTestRoot . '/include/utils/CronDispatcher.php';

/**
 * L. 子プロセス以外の実行でも個別ログが残る — #1823
 *
 * 対象: vtigercron.php の vtigercron_start_task_logging() / stop_task_logging()
 *
 * 振り分けモードでは子プロセスの標準出力をシェルがログへリダイレクトするが、
 * 単体実行（--service=）や逐次実行はこのプロセスで直接実行するため、以前は
 * タスクごとのログが残らなかった。実行モードによらず同じ場所に残るようにしている。
 *
 *   L1 単体実行でタスクごとのログが作られる
 *   L2 ログの内容（ハンドラの出力・開始と終了・他タスクを混ぜない）
 *   L3 標準出力にも従来どおり出る
 *   L4 例外で終わった場合もログに残る（異常系）
 *   L5 同じ日に複数回実行すると追記される（上書きしない）
 *   L6 子プロセスモードでは二重に書かない（シェルのリダイレクトと重複しない）
 */
final class IndividualLogTest extends TestCase
{
    use CronTestSupport;

    private string $directory = '';

    /** @var array<int, string> テスト中に作ったログファイル */
    private array $written = [];

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();
        $this->directory = FR_CronDispatcher::getLogDirectory();
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->written as $file) {
            @unlink($file);
        }
        $this->written = [];
        $this->cleanUpCron();
    }

    /** 当日のログファイルのパス */
    private function todaysLogFile(string $taskName): string
    {
        $file = $this->directory . DIRECTORY_SEPARATOR
            . FR_CronDispatcher::getLogFileName($this->reload($taskName), date('Ymd'));
        $this->written[] = $file;

        return $file;
    }

    /** 実行対象にする */
    private function markDue(string $taskName): void
    {
        $this->setCols($taskName, [
            'next_run_at' => 1,
            'lastend'     => 0,
            'status'      => Vtiger_Cron::$STATUS_ENABLED,
        ]);
    }

    public function test_L1_L3_単体実行でタスクごとのログが作られる(): void
    {
        $name = $this->makeTask('SingleLog', $this->fixtureHandler('noop1.service'));
        $logFile = $this->todaysLogFile($name);
        @unlink($logFile);
        $this->markDue($name);

        $run = $this->runCli('--service=' . escapeshellarg($name));

        self::assertFileExists($logFile, 'L1 単体実行でタスクごとのログが作られる');

        $logged = (string) file_get_contents($logFile);
        self::assertStringContainsString('noop1 handler executed', $logged, 'L2 ログにハンドラの出力が含まれる');
        self::assertStringContainsString('[STARTS]', $logged, 'L2 ログに開始の記録が含まれる');
        self::assertStringContainsString('[ENDS]', $logged, 'L2 ログに終了の記録が含まれる');
        self::assertStringNotContainsString(',Instance,', $logged, 'L2 ログには他タスク（Instance 行）を混ぜない');
        self::assertStringContainsString('noop1 handler executed', $run['output'], 'L3 標準出力にも従来どおり出る');
    }

    public function test_L5_同じ日の再実行で追記される(): void
    {
        $name = $this->makeTask('SingleLog', $this->fixtureHandler('noop1.service'));
        $logFile = $this->todaysLogFile($name);
        @unlink($logFile);

        $this->markDue($name);
        $this->runCli('--service=' . escapeshellarg($name));
        $first = (string) file_get_contents($logFile);

        $this->markDue($name);
        $this->runCli('--service=' . escapeshellarg($name));
        $appended = (string) file_get_contents($logFile);

        self::assertSame(2, substr_count($appended, 'noop1 handler executed'), 'L5 同じ日に 2 回実行すると 2 回分が追記される');
        self::assertGreaterThan(strlen($first), strlen($appended), 'L5 1 回目の内容が残っている');
    }

    public function test_L4_例外で終わってもログが残る(): void
    {
        $failName = $this->makeTask('SingleLogFail', $this->fixtureHandler('thrower.service'));
        $failLog = $this->todaysLogFile($failName);
        @unlink($failLog);
        $this->markDue($failName);

        $this->runCli('--service=' . escapeshellarg($failName));

        self::assertFileExists($failLog, 'L4 例外で終わってもログが残る');
        self::assertStringContainsString(
            '[ERROR]',
            (string) file_get_contents($failLog),
            'L4 ログに失敗の記録が含まれる'
        );
    }

    public function test_L6_子プロセスモードでは二重に書かない(): void
    {
        $childName = $this->makeTask('ChildLog', $this->fixtureHandler('noop2.service'));
        $childLog = $this->todaysLogFile($childName);
        @unlink($childLog);

        FR_CronDispatcher::claim($this->reload($childName));

        // 子プロセスは親がリダイレクトする前提のため、ここでは出力を捨てて
        // PHP 側が二重に書き込まないことだけを見る
        $this->runCli('--child --service=' . escapeshellarg($childName));

        $childLogged = is_file($childLog) ? (string) file_get_contents($childLog) : '';
        self::assertSame(
            0,
            substr_count($childLogged, 'noop2 handler executed'),
            'L6 子プロセスモードでは PHP 側がログを書かない'
        );
    }
}
