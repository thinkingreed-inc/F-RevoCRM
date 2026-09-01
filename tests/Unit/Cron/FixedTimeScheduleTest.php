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

namespace Tests\Unit\Cron;

use PHPUnit\Framework\TestCase;
use Vtiger_Cron;

$fixedTimeScheduleTestRoot = dirname(__DIR__, 3);
require_once $fixedTimeScheduleTestRoot . '/vtlib/Vtiger/Cron.php';

/**
 * I. 決まった時刻の実行（毎日・毎週・毎月）と周期のグリッド — #1823
 *
 * 対象: Vtiger_Cron::computeNextRunAt() / computeNextDailyRunAt()
 *       / computeNextWeeklyRunAt() / computeNextMonthlyRunAt() / parseWeekdays()
 *
 * いずれも基準時刻を引数で受け取る純粋な計算なので DB を使わない。
 * 基準は 2026-08-25 10:07:33（火曜日）。
 */
final class FixedTimeScheduleTest extends TestCase
{
    /** 基準時刻: 2026-08-25 10:07:33（火曜日） */
    private function reference(): int
    {
        return (int) mktime(10, 7, 33, 8, 25, 2026);
    }

    // ------------------------------------------------------------ 毎日

    public function test_I1_指定時刻がまだ来ていなければ当日になる(): void
    {
        self::assertSame(
            (int) mktime(15, 30, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextDailyRunAt(15 * 60 + 30, $this->reference()),
            'I1 15:30 指定・10:07 時点 → 当日 15:30'
        );
    }

    public function test_I2_指定時刻を過ぎていれば翌日になる(): void
    {
        self::assertSame(
            (int) mktime(3, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextDailyRunAt(3 * 60, $this->reference()),
            'I2 03:00 指定・10:07 時点 → 翌日 03:00'
        );
        self::assertSame(
            (int) mktime(10, 7, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextDailyRunAt(10 * 60 + 7, (int) mktime(10, 7, 0, 8, 25, 2026)),
            'I2 境界: ちょうど指定時刻 → 翌日'
        );
    }

    public function test_I3_境界値_0時_23時59分_月末をまたぐ場合(): void
    {
        self::assertSame(
            (int) mktime(0, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextDailyRunAt(0, $this->reference()),
            'I3 00:00 指定 → 翌日 00:00'
        );
        self::assertSame(
            (int) mktime(23, 59, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextDailyRunAt(1439, $this->reference()),
            'I3 23:59 指定 → 当日 23:59'
        );
        self::assertSame(
            (int) mktime(1, 0, 0, 9, 1, 2026),
            Vtiger_Cron::computeNextDailyRunAt(60, (int) mktime(23, 0, 0, 8, 31, 2026)),
            'I3 月末をまたぐ（8/31 23:00 時点で 01:00 指定）→ 9/1 01:00'
        );
    }

    public function test_I4_範囲外の時刻は0時として扱う(): void
    {
        self::assertSame(
            (int) mktime(0, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextDailyRunAt(1440, $this->reference()),
            'I4 範囲外（1440）は 0 時として扱う'
        );
        self::assertSame(
            (int) mktime(0, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextDailyRunAt(-10, $this->reference()),
            'I4 範囲外（負）は 0 時として扱う'
        );
    }

    // ------------------------------------------------------------ 毎週

    public function test_I8_指定曜日の次の該当日になる(): void
    {
        $reference = $this->reference();

        self::assertSame(
            (int) mktime(9, 0, 0, 8, 28, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt(5, 9 * 60, $reference),
            'I8 火曜 10:07 時点で金曜 09:00 指定 → 同じ週の金曜'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 8, 31, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt(1, 9 * 60, $reference),
            'I8 火曜 10:07 時点で月曜 09:00 指定 → 翌週の月曜'
        );
        self::assertSame(
            (int) mktime(15, 0, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt(2, 15 * 60, $reference),
            'I8 境界: 当日（火曜）で時刻前 → 当日'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 9, 1, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt(2, 9 * 60, $reference),
            'I8 境界: 当日（火曜）で時刻後 → 翌週'
        );
    }

    public function test_I9_範囲外の曜日は日曜として扱う(): void
    {
        $reference = $this->reference();

        self::assertSame(
            Vtiger_Cron::computeNextWeeklyRunAt(0, 9 * 60, $reference),
            Vtiger_Cron::computeNextWeeklyRunAt(9, 9 * 60, $reference),
            'I9 範囲外の曜日は取り除かれ日曜として扱う'
        );
    }

    public function test_I15_曜日を複数指定できる(): void
    {
        $reference = $this->reference();

        self::assertSame(
            (int) mktime(9, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt([1, 3, 5], 9 * 60, $reference),
            'I15 月・水・金指定・火曜 10:07 時点 → 直近の水曜'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt('1,3,5', 9 * 60, $reference),
            'I15 カンマ区切りの文字列でも同じ結果になる'
        );
        self::assertSame(
            (int) mktime(15, 0, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt([2, 4], 15 * 60, $reference),
            'I15 当日（火曜）を含み時刻前なら当日'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 8, 27, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt([2, 4], 9 * 60, $reference),
            'I15 当日（火曜）を含むが時刻後なら次の指定曜日（木曜）'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 8, 26, 2026),
            Vtiger_Cron::computeNextWeeklyRunAt([0, 1, 2, 3, 4, 5, 6], 9 * 60, $reference),
            'I15 全曜日を指定すると翌日になる（毎日と同じ間隔）'
        );
    }

    public function test_I15_曜日の指定を解釈できる(): void
    {
        self::assertSame(
            [1, 3],
            Vtiger_Cron::parseWeekdays('3,1,3,9,,x,-2'),
            'I15 重複と範囲外を除いて解釈する'
        );
        self::assertSame([], Vtiger_Cron::parseWeekdays(''), 'I15 空の指定は空配列');
    }

    // ------------------------------------------------------------ 毎月

    public function test_I10_指定日の次の該当日になる(): void
    {
        $reference = $this->reference();

        self::assertSame(
            (int) mktime(9, 0, 0, 8, 28, 2026),
            Vtiger_Cron::computeNextMonthlyRunAt(28, 9 * 60, $reference),
            'I10 8/25 10:07 時点で 28 日 09:00 指定 → 当月 8/28'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 9, 10, 2026),
            Vtiger_Cron::computeNextMonthlyRunAt(10, 9 * 60, $reference),
            'I10 8/25 10:07 時点で 10 日 09:00 指定 → 翌月 9/10'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 9, 25, 2026),
            Vtiger_Cron::computeNextMonthlyRunAt(25, 9 * 60, $reference),
            'I10 境界: 当日で時刻後 → 翌月'
        );
    }

    public function test_I11_月末指定は月の長さに追従する(): void
    {
        self::assertSame(
            (int) mktime(9, 0, 0, 8, 31, 2026),
            Vtiger_Cron::computeNextMonthlyRunAt(
                Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH,
                9 * 60,
                $this->reference()
            ),
            'I11 月末指定（8 月）→ 8/31'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 2, 28, 2027),
            Vtiger_Cron::computeNextMonthlyRunAt(
                Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH,
                9 * 60,
                (int) mktime(10, 0, 0, 2, 1, 2027)
            ),
            'I11 月末指定（2027 年 2 月・平年）→ 2/28'
        );
        self::assertSame(
            (int) mktime(9, 0, 0, 2, 29, 2028),
            Vtiger_Cron::computeNextMonthlyRunAt(
                Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH,
                9 * 60,
                (int) mktime(10, 0, 0, 2, 1, 2028)
            ),
            'I11 月末指定（2028 年 2 月・うるう年）→ 2/29'
        );
    }

    public function test_I12_その月に無い日を指定したら末日になる(): void
    {
        self::assertSame(
            (int) mktime(9, 0, 0, 2, 28, 2027),
            Vtiger_Cron::computeNextMonthlyRunAt(31, 9 * 60, (int) mktime(10, 0, 0, 2, 1, 2027)),
            'I12 2 月に 31 日指定 → その月の末日（2/28）'
        );
    }

    public function test_I13_年をまたぐ場合(): void
    {
        self::assertSame(
            (int) mktime(9, 0, 0, 1, 5, 2027),
            Vtiger_Cron::computeNextMonthlyRunAt(5, 9 * 60, (int) mktime(10, 0, 0, 12, 20, 2026)),
            'I13 12/20 時点で 5 日指定 → 翌年 1/5'
        );
    }

    // -------------------------------------------------- 周期のグリッド

    /**
     * 実行時刻を固定のグリッドへ吸着させることで、実行が遅れてもその遅れが
     * 次回以降へ持ち越されないようにしている。
     */
    public function test_周期は1日を割り切れる場合グリッドに乗る(): void
    {
        // 10:07:33 時点で 15 分周期 → 10:15:00
        self::assertSame(
            (int) mktime(10, 15, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextRunAt(900, $this->reference()),
            '15 分周期は :00/:15/:30/:45 に揃う'
        );
        // 1 分周期 → 10:08:00
        self::assertSame(
            (int) mktime(10, 8, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextRunAt(60, $this->reference()),
            '1 分周期は毎分 0 秒に揃う'
        );
        // 12 時間周期 → 12:00:00
        self::assertSame(
            (int) mktime(12, 0, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextRunAt(43200, $this->reference()),
            '12 時間周期は 0:00 / 12:00 に揃う'
        );
    }

    public function test_遅れてもグリッドへ復帰し遅れを持ち越さない(): void
    {
        // 19:58 の回を取りこぼし、19:59:30 に完了した場合でも次回は 20:00:00
        $finished = (int) mktime(19, 59, 30, 8, 25, 2026);
        self::assertSame(
            (int) mktime(20, 0, 0, 8, 25, 2026),
            Vtiger_Cron::computeNextRunAt(60, $finished),
            '遅れて完了しても次のグリッドに戻る（完了時刻 + 周期にならない）'
        );
    }

    public function test_1日を割り切れない周期や1日超の周期は相対で決まる(): void
    {
        $reference = $this->reference();

        // 7 分は 1 日を割り切れないためグリッドを作れない
        self::assertSame(
            $reference + 420,
            Vtiger_Cron::computeNextRunAt(420, $reference),
            '割り切れない周期は基準時刻からの相対になる'
        );
        // 1 日を超える周期も相対
        self::assertSame(
            $reference + 604800,
            Vtiger_Cron::computeNextRunAt(604800, $reference),
            '1 日を超える周期は基準時刻からの相対になる'
        );
    }

    public function test_周期が0以下なら既定の15分として扱う(): void
    {
        self::assertSame(
            Vtiger_Cron::computeNextRunAt(900, $this->reference()),
            Vtiger_Cron::computeNextRunAt(0, $this->reference()),
            '0 は既定値（900 秒）として扱う'
        );
    }
}
