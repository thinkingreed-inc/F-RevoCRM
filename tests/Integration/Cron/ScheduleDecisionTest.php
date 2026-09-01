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

/**
 * D. 飢餓の回避（sortByUrgency）/ E. 実行判定と次回実行予定時刻 — #1823
 *
 * 対象: FR_CronDispatcher::sortByUrgency() / Vtiger_Cron::isRunnable() / markFinished()
 *
 *   D1 予定時刻からの遅れが大きい順に並ぶ
 *   D2 遅れが同じなら元の並び順を保つ（安定性）
 *   D3 next_run_at が未設定なら従来の相対計算で並べる（移行直後）
 *   E1 next_run_at が未来なら実行しない
 *   E2 next_run_at が過去なら実行する
 *   E3 next_run_at が未設定なら従来の相対判定にフォールバックする
 *   E4 無効化されたタスクは実行しない
 *   E5 完了時、次回実行予定時刻が周期のグリッドに乗り、遅れを持ち越さない
 */
final class ScheduleDecisionTest extends TestCase
{
    use CronTestSupport;

    public static function setUpBeforeClass(): void
    {
        self::loadCronClasses();
    }

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();
    }

    protected function tearDown(): void
    {
        $this->cleanUpCron();
    }

    public function test_D1_D2_遅れが大きい順に並び同着は入力順を保つ(): void
    {
        $now = $this->dbNow();
        $slight = $this->makeTask('UrgSlight', $this->fixtureHandler('noop1.service'));
        $heavy = $this->makeTask('UrgHeavy', $this->fixtureHandler('noop2.service'));
        $tie = $this->makeTask('UrgTie', $this->fixtureHandler('noop3.service'));

        // 遅れは予定時刻（next_run_at）からの経過で測る。
        // slight = 60 秒 / heavy = 3600 秒 / tie = 60 秒
        $this->setCols($slight, ['next_run_at' => $now - 60]);
        $this->setCols($heavy, ['next_run_at' => $now - 3600]);
        $this->setCols($tie, ['next_run_at' => $now - 60]);

        // 入力順は slight, heavy, tie
        $sorted = FR_CronDispatcher::sortByUrgency([
            $this->reload($slight),
            $this->reload($heavy),
            $this->reload($tie),
        ]);
        $names = array_map(static fn ($task): string => $task->getName(), $sorted);

        self::assertSame($heavy, $names[0], 'D1 予定時刻からの遅れが大きいタスクが先に並ぶ');
        self::assertSame([$slight, $tie], [$names[1], $names[2]], 'D2 遅れが同じなら元の並び順を保つ');
    }

    public function test_D3_next_run_at_未設定なら従来の相対計算で並べる(): void
    {
        $now = $this->dbNow();
        $slight = $this->makeTask('UrgSlight', $this->fixtureHandler('noop1.service'));
        $heavy = $this->makeTask('UrgHeavy', $this->fixtureHandler('noop2.service'));

        $this->setCols($slight, ['next_run_at' => 0, 'laststart' => $now - 960, 'lastend' => $now - 960]);
        $this->setCols($heavy, ['next_run_at' => 0, 'laststart' => $now - 4500, 'lastend' => $now - 4500]);

        $sorted = FR_CronDispatcher::sortByUrgency([$this->reload($slight), $this->reload($heavy)]);

        self::assertSame(
            $heavy,
            $sorted[0]->getName(),
            'D3 next_run_at 未設定なら従来の相対計算で並べる'
        );
    }

    public function test_E1_E2_next_run_at_で実行するかを決める(): void
    {
        $name = $this->makeTask('Sched', $this->fixtureHandler('noop1.service'));
        $now = $this->dbNow();

        $this->setCols($name, ['next_run_at' => $now + 600]);
        self::assertFalse($this->reload($name)->isRunnable(), 'E1 next_run_at が未来なら実行しない');

        $this->setCols($name, ['next_run_at' => $now - 1]);
        self::assertTrue($this->reload($name)->isRunnable(), 'E2 next_run_at が過去なら実行する');
    }

    public function test_E3_next_run_at_未設定なら従来の相対判定にフォールバックする(): void
    {
        $name = $this->makeTask('Sched', $this->fixtureHandler('noop1.service'));
        $now = $this->dbNow();

        $this->setCols($name, ['next_run_at' => 0, 'laststart' => $now - 100, 'lastend' => 0]);
        self::assertFalse(
            $this->reload($name)->isRunnable(),
            'E3 移行直後は従来の相対判定にフォールバックする（周期内）'
        );

        $this->setCols($name, ['laststart' => $now - 900]);
        self::assertTrue(
            $this->reload($name)->isRunnable(),
            'E3 移行直後は従来の相対判定にフォールバックする（周期経過）'
        );
    }

    public function test_E4_無効化されたタスクは実行しない(): void
    {
        $name = $this->makeTask('Sched', $this->fixtureHandler('noop1.service'));
        $this->setCols($name, ['status' => Vtiger_Cron::$STATUS_DISABLED, 'next_run_at' => 1]);

        self::assertFalse($this->reload($name)->isRunnable(), 'E4 無効化されたタスクは実行しない');
    }

    public function test_E5_完了後の次回予定がグリッドに乗り遅れを持ち越さない(): void
    {
        $name = $this->makeTask('Sched', $this->fixtureHandler('noop1.service'));
        $this->setCols($name, ['status' => Vtiger_Cron::$STATUS_RUNNING]);

        $this->reload($name)->markFinished();
        $nextRunAt = (int) $this->getCol($name, 'next_run_at');

        self::assertSame(0, $nextRunAt % 900, 'E5 完了後の next_run_at が 900 秒のグリッドに乗る');
        self::assertGreaterThan($this->dbNow(), $nextRunAt, 'E5 next_run_at が未来になる');
        self::assertLessThanOrEqual(
            900,
            $nextRunAt - $this->dbNow(),
            'E5 遅れを取り戻さない（1 周期以内に収まる）'
        );
    }
}
