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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\CronTestSupport;
use Vtiger_Cron;

require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

/**
 * H. 状態表示（--status）— #1823
 *
 * 対象: vtigercron.php --status / FR_CronDispatcher::describe()
 *
 *   H1 各状態が正しく分類される
 *      IDLE / STARTING / NOSTART / DEAD / REMOTE / STALE / RUNNING / HUNG
 *   H2 要対応の状態（HUNG / DEAD / STALE / NOSTART）があれば終了コード 1 を返す
 */
final class StatusTest extends TestCase
{
    use CronTestSupport;

    private string $taskName = '';
    private string $host = '';

    public static function setUpBeforeClass(): void
    {
        self::loadCronClasses();
    }

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->prepareCronCurrentUser();
        $this->cleanUpCronTasks();
        $this->host = FR_CronDispatcher::getHostName();
        $this->taskName = $this->makeTask('Status', $this->fixtureHandler('noop1.service'));
    }

    protected function tearDown(): void
    {
        $this->cleanUpCron();
    }

    /** --status の出力から、このタスクの STATE 列を取り出す */
    private function extractState(string $output, string $taskName): ?string
    {
        foreach (explode("\n", $output) as $line) {
            if (strpos($line, $taskName) === 0) {
                $columns = preg_split('/\s+/', trim($line));

                return $columns[1] ?? null;
            }
        }

        return null;
    }

    /**
     * プロセスを起動しなくても作れる状態。
     *
     * @return array<string, array{0: string}>
     */
    public static function processlessStates(): array
    {
        return [
            'IDLE'     => ['IDLE'],
            'STARTING' => ['STARTING'],
            'NOSTART'  => ['NOSTART'],
            'DEAD'     => ['DEAD'],
            'REMOTE'   => ['REMOTE'],
            'STALE'    => ['STALE'],
        ];
    }

    /** @return array<string, int|string|null> */
    private function columnsFor(string $state, int $now): array
    {
        switch ($state) {
            case 'IDLE':
                return ['status' => Vtiger_Cron::$STATUS_ENABLED, 'owner_host' => $this->host,
                    'owner_pid' => 0, 'laststart' => $now - 10, 'lastend' => $now - 5,
                    'last_heartbeat' => 0];
            case 'STARTING':
                return ['status' => Vtiger_Cron::$STATUS_RUNNING, 'owner_host' => $this->host,
                    'owner_pid' => 0, 'laststart' => $now, 'lastend' => 0,
                    'last_heartbeat' => $now];
            case 'NOSTART':
                return ['status' => Vtiger_Cron::$STATUS_RUNNING, 'owner_host' => $this->host,
                    'owner_pid' => 0,
                    'laststart' => $now - (FR_CronDispatcher::CHILD_STARTUP_GRACE + 10),
                    'lastend' => 0, 'last_heartbeat' => $now];
            case 'DEAD':
                return ['status' => Vtiger_Cron::$STATUS_RUNNING, 'owner_host' => $this->host,
                    'owner_pid' => 4194303, 'laststart' => $now - 10, 'lastend' => 0,
                    'last_heartbeat' => $now];
            case 'REMOTE':
                return ['status' => Vtiger_Cron::$STATUS_RUNNING, 'owner_host' => self::OTHER_HOST,
                    'owner_pid' => 12345, 'laststart' => $now - 10, 'lastend' => 0,
                    'last_heartbeat' => $now];
            case 'STALE':
                return ['status' => Vtiger_Cron::$STATUS_RUNNING, 'owner_host' => self::OTHER_HOST,
                    'owner_pid' => 12345, 'retry_timeout' => 3600, 'laststart' => $now - 10,
                    'lastend' => 0,
                    'last_heartbeat' => $now - (FR_CronDispatcher::getHeartbeatTimeout() + 10)];
        }

        self::fail('未知の状態: ' . $state);
    }

    #[DataProvider('processlessStates')]
    public function test_H1_H2_状態と終了コード(string $expected): void
    {
        $this->setCols($this->taskName, $this->columnsFor($expected, $this->dbNow()));

        $run = $this->runCli('--status');

        self::assertSame(
            $expected,
            $this->extractState($run['output'], $this->taskName),
            'H1 ' . $expected . ' として表示される'
        );

        // 要対応の状態かどうかで終了コードが変わる
        $attention = in_array($expected, ['HUNG', 'DEAD', 'STALE', 'NOSTART'], true);
        self::assertSame(
            $attention ? 1 : 0,
            $run['status'],
            'H2 ' . $expected . ' のとき終了コードが ' . ($attention ? 1 : 0)
        );
    }

    public function test_H1_H2_RUNNING_と_HUNG(): void
    {
        $pid = $this->startFakeTaskProcess($this->taskName);
        if ($pid <= 0) {
            self::markTestSkipped('擬似プロセスを起動できなかった');
        }

        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'owner_pid'      => $pid,
            'retry_timeout'  => 3600,
            'laststart'      => $now - 5,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        $run = $this->runCli('--status');
        self::assertSame('RUNNING', $this->extractState($run['output'], $this->taskName), 'H1 RUNNING として表示される');
        self::assertSame(0, $run['status'], 'H2 RUNNING のとき終了コードが 0');

        $this->setCols($this->taskName, ['retry_timeout' => 10, 'laststart' => $now - 600]);

        $run = $this->runCli('--status');
        self::assertSame('HUNG', $this->extractState($run['output'], $this->taskName), 'H1 HUNG として表示される');
        self::assertSame(1, $run['status'], 'H2 HUNG のとき終了コードが 1');
    }

    public function test_H1_無効化されたタスクは一覧に出ない(): void
    {
        $this->setCols($this->taskName, ['status' => Vtiger_Cron::$STATUS_DISABLED]);

        $run = $this->runCli('--status');

        self::assertNull(
            $this->extractState($run['output'], $this->taskName),
            'H1 無効化されたタスクは一覧に出ない（DISABLED は単体指定時のみ）'
        );
    }
}
