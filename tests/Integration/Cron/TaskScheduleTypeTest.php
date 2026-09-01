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
 * I. 実行タイミングの種別がタスクの設定から使われるか — #1823
 *
 * 対象: Vtiger_Cron::computeNextRun() / isFixedTimeSchedule() / isDailySchedule()
 *       / getRunOnWeekdays() / markFinished()
 *
 * 計算そのものは tests/Unit/Cron/FixedTimeScheduleTest.php で確かめている。
 * ここは「DB に保存された指定が正しく読まれ、計算に渡るか」を見る。
 *
 *   I5  タスクの指定（interval / daily）に従って計算方法が切り替わる
 *   I6  完了時に翌日の同時刻へ進む
 *   I7  実行判定は next_run_at で行われる
 *   I8  毎週の指定がタスクから読まれる
 *   I11 毎月（月末）の指定がタスクから読まれる
 *   I14 必要な値が欠けている場合は周期実行にフォールバックする
 *   I15 曜日の複数指定がタスクから読まれる
 */
final class TaskScheduleTypeTest extends TestCase
{
    use CronTestSupport;

    private string $taskName = '';

    /** 基準時刻: 2026-08-25 10:07:33（火曜日） */
    private int $reference = 0;

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();
        $this->taskName = $this->makeTask('Daily', $this->fixtureHandler('noop1.service'));
        $this->reference = (int) mktime(10, 7, 33, 8, 25, 2026);
    }

    protected function tearDown(): void
    {
        $this->cleanUpCron();
    }

    public function test_I5_interval_指定なら周期のグリッドで計算する(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'  => Vtiger_Cron::SCHEDULE_INTERVAL,
            'run_at_minutes' => null,
        ]);
        $task = $this->reload($this->taskName);

        self::assertFalse($task->isFixedTimeSchedule(), 'I5 interval 指定なら時刻指定ではない');
        self::assertSame(
            Vtiger_Cron::computeNextRunAt(900, $this->reference),
            $task->computeNextRun($this->reference),
            'I5 周期実行なら周期のグリッドで計算する'
        );
    }

    public function test_I5_daily_指定なら指定時刻で計算する(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'  => Vtiger_Cron::SCHEDULE_DAILY,
            'run_at_minutes' => 3 * 60,
            'frequency'      => 86400,
        ]);
        $task = $this->reload($this->taskName);

        self::assertTrue($task->isDailySchedule(), 'I5 daily 指定なら毎日実行');
        self::assertSame(
            (int) mktime(3, 0, 0, 8, 26, 2026),
            $task->computeNextRun($this->reference),
            'I5 毎日実行なら指定時刻で計算する'
        );
    }

    public function test_I6_完了後の次回予定が指定時刻になる(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'schedule_type'  => Vtiger_Cron::SCHEDULE_DAILY,
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'run_at_minutes' => 3 * 60,
            'frequency'      => 86400,
            'laststart'      => $now - 10,
        ]);

        $this->reload($this->taskName)->markFinished();
        $nextRunAt = $this->getColInt($this->taskName, 'next_run_at');

        self::assertSame('03:00', date('H:i', $nextRunAt), 'I6 完了後の next_run_at が指定時刻（3:00）になる');
        self::assertGreaterThan($this->dbNow(), $nextRunAt, 'I6 next_run_at が未来になる');
        self::assertLessThanOrEqual(86400, $nextRunAt - $this->dbNow(), 'I6 遅れを取り戻さない（1 日以内に収まる）');
    }

    public function test_I7_実行判定は_next_run_at_で行われる(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'schedule_type'  => Vtiger_Cron::SCHEDULE_DAILY,
            'run_at_minutes' => 3 * 60,
            'frequency'      => 86400,
            'status'         => Vtiger_Cron::$STATUS_ENABLED,
            'next_run_at'    => $now + 600,
        ]);
        self::assertFalse($this->reload($this->taskName)->isRunnable(), 'I7 指定時刻前は実行しない');

        $this->setCols($this->taskName, ['next_run_at' => $now - 1]);
        self::assertTrue($this->reload($this->taskName)->isRunnable(), 'I7 指定時刻を過ぎたら実行する');
    }

    public function test_I8_毎週の指定がタスクから読まれる(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'    => Vtiger_Cron::SCHEDULE_WEEKLY,
            'run_at_minutes'   => 9 * 60,
            'run_on_weekdays'  => '5',
            'frequency'        => 604800,
        ]);

        self::assertSame(
            (int) mktime(9, 0, 0, 8, 28, 2026),
            $this->reload($this->taskName)->computeNextRun($this->reference),
            'I8 タスクの指定（毎週金曜 9:00）で計算される'
        );
    }

    public function test_I15_曜日の複数指定がタスクから読まれる(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'   => Vtiger_Cron::SCHEDULE_WEEKLY,
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => '1,3,5',
            'frequency'       => 604800,
        ]);

        self::assertSame(
            (int) mktime(9, 0, 0, 8, 26, 2026),
            $this->reload($this->taskName)->computeNextRun($this->reference),
            'I15 タスクの指定（毎週月・水・金 9:00）で計算される'
        );
        self::assertSame(
            [1, 3, 5],
            $this->reload($this->taskName)->getRunOnWeekdays(),
            'I15 タスクから曜日一覧を取り出せる'
        );
    }

    public function test_I11_毎月_月末_の指定がタスクから読まれる(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'   => Vtiger_Cron::SCHEDULE_MONTHLY,
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => null,
            'run_on_day'      => Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH,
            'frequency'       => 2592000,
        ]);

        self::assertSame(
            (int) mktime(9, 0, 0, 8, 31, 2026),
            $this->reload($this->taskName)->computeNextRun($this->reference),
            'I11 タスクの指定（毎月末 9:00）で計算される'
        );
    }

    public function test_I14_毎週なのに曜日が無ければ周期で計算する(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'   => Vtiger_Cron::SCHEDULE_WEEKLY,
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => null,
            'frequency'       => 900,
        ]);

        self::assertSame(
            Vtiger_Cron::computeNextRunAt(900, $this->reference),
            $this->reload($this->taskName)->computeNextRun($this->reference),
            'I14 毎週なのに曜日が無ければ周期で計算する'
        );
    }

    public function test_I14_毎月なのに日が無ければ周期で計算する(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'  => Vtiger_Cron::SCHEDULE_MONTHLY,
            'run_at_minutes' => 9 * 60,
            'run_on_day'     => null,
            'frequency'      => 900,
        ]);

        self::assertSame(
            Vtiger_Cron::computeNextRunAt(900, $this->reference),
            $this->reload($this->taskName)->computeNextRun($this->reference),
            'I14 毎月なのに日が無ければ周期で計算する'
        );
    }

    public function test_I14_毎日なのに時刻が無ければ周期で計算する(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'  => Vtiger_Cron::SCHEDULE_DAILY,
            'run_at_minutes' => null,
            'frequency'      => 900,
        ]);

        self::assertSame(
            Vtiger_Cron::computeNextRunAt(900, $this->reference),
            $this->reload($this->taskName)->computeNextRun($this->reference),
            'I14 毎日なのに時刻が無ければ周期で計算する'
        );
    }
}
