<?php

namespace Tests\Integration\Documents;

use FR_BusinessDay;
use InvalidArgumentException;
use Tests\Support\DocumentsTestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * 営業日・休祝日の共通基盤
 *
 * 対応する仕様書: docs/tests/Documents/TS-01_営業日・休祝日.md
 *   4.1 休日判定（DT-1）
 *   4.2 週休設定（DT-2 / BV-1）
 *   4.3 営業日計算・営業日数（BV-2 / BV-4 / Q-03）
 *   4.4 マスタ参照
 *
 * 休祝日マスタと週休設定を DB から読むため Integration に置く。
 * 祝日の算出とCSV解析は DB を使わないため Tests\Unit\Documents\JapaneseHolidaysTest にある。
 */
final class BusinessDayTest extends DocumentsTestCase
{
    // ---- 4.1 休日判定（DT-1） -------------------------------------------

    public function test_TC_BD_001_003_週休の曜日で休日を判定する(): void
    {
        $this->assertTrue(FR_BusinessDay::isBusinessDay('2035-01-01'), 'TC-BD-001 月曜は営業日');
        $this->assertTrue(FR_BusinessDay::isHoliday('2035-01-06'), 'TC-BD-002 土曜は休日');
        $this->assertTrue(FR_BusinessDay::isHoliday('2035-01-07'), 'TC-BD-003 日曜は休日');
    }

    public function test_TC_BD_004_005_マスタのholidayは休日になる(): void
    {
        $this->addHoliday('2035-01-02', 'holiday');

        $this->assertTrue(FR_BusinessDay::isHoliday('2035-01-02'), 'TC-BD-004');
        $this->assertFalse(FR_BusinessDay::isBusinessDay('2035-01-02'), 'TC-BD-005');
    }

    public function test_TC_BD_006_007_マスタのworkdayは週休より優先する(): void
    {
        $this->addHoliday('2035-01-06', 'workday');
        $this->assertTrue(
            FR_BusinessDay::isBusinessDay('2035-01-06'),
            'TC-BD-006 土曜の workday は営業日'
        );

        $this->addHoliday('2035-01-05', 'workday');
        $this->assertTrue(
            FR_BusinessDay::isBusinessDay('2035-01-05'),
            'TC-BD-007 平日の workday は営業日のまま'
        );
    }

    public function test_TC_BD_009_clearCache後は登録が反映される(): void
    {
        // キャッシュを温めてから登録する（キャッシュ更新の確認）
        FR_BusinessDay::isHoliday('2035-01-09');
        $this->addHoliday('2035-01-09', 'holiday');
        FR_BusinessDay::clearCache();

        $this->assertTrue(FR_BusinessDay::isHoliday('2035-01-09'), 'TC-BD-009');
    }

    /**
     * 未入力として扱う値
     *
     * @return array<string,array{0:string|null}>
     */
    public static function 未入力の日付(): array
    {
        return [
            '空文字' => [''],
            'null' => [null],
            '0000-00-00' => ['0000-00-00'],
        ];
    }

    /**
     * @dataProvider 未入力の日付
     */
    public function test_TC_BD_013_未入力の日付はfalseを返す(?string $date): void
    {
        $this->assertFalse(FR_BusinessDay::isHoliday($date), 'TC-BD-013');
    }

    /**
     * 日付として解釈できない値
     *
     * @return array<string,array{0:string}>
     */
    public static function 不正な日付(): array
    {
        return [
            'abc' => ['abc'],
            '2026-02-30（存在しない日）' => ['2026-02-30'],
            '2035-13-01（存在しない月）' => ['2035-13-01'],
        ];
    }

    /**
     * @dataProvider 不正な日付
     */
    public function test_TC_BD_013b_不正な日付は例外にする(string $date): void
    {
        // 黙って繰り上げず例外にする（2026-02-30 が 3/2 にならない）
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::isHoliday($date);
    }

    public function test_TC_BD_014_isWeeklyHolidayの不正な日付は例外にする(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::isWeeklyHoliday('abc');
    }

    public function test_TC_BD_014b_isWeeklyHolidayの未入力はfalseを返す(): void
    {
        $this->assertFalse(FR_BusinessDay::isWeeklyHoliday(''), 'TC-BD-014b');
    }

    // ---- 4.2 週休設定（DT-2 / BV-1） ------------------------------------

    public function test_TC_BD_020_設定した週休が読み出せる(): void
    {
        $this->setWeeklyHolidays([0, 6]);

        $this->assertSame([0, 6], FR_BusinessDay::getWeeklyHolidays(), 'TC-BD-020');
    }

    public function test_TC_BD_023_025_週休の指定を整える(): void
    {
        $this->assertSame(
            [0, 6],
            FR_BusinessDay::normalizeWeekdays([-1, 0, 6, 7, 'x', 6]),
            'TC-BD-023 範囲外・非数値・重複を除いて昇順'
        );
        $this->assertSame([], FR_BusinessDay::normalizeWeekdays(''), 'TC-BD-024 空文字は週休なし');
        $this->assertSame(
            [0, 6],
            FR_BusinessDay::normalizeWeekdays('6,0'),
            'TC-BD-025 カンマ区切りを昇順に整える'
        );
    }

    public function test_TC_BD_026_週休なしなら土曜も営業日(): void
    {
        $this->setWeeklyHolidays([]);

        $this->assertFalse(FR_BusinessDay::isHoliday('2035-01-06'), 'TC-BD-026');
    }

    public function test_TC_BD_027_全曜日が週休ならnullを返す(): void
    {
        $this->setWeeklyHolidays([0, 1, 2, 3, 4, 5, 6]);

        // 無限ループにせず null で返す
        $this->assertNull(FR_BusinessDay::addBusinessDays('2035-01-01', 1), 'TC-BD-027');
    }

    public function test_TC_BD_028_029_2回保存しても設定行は1行のまま(): void
    {
        $this->setWeeklyHolidays([0]);
        FR_BusinessDay::setWeeklyHolidays([0]);

        $result = $this->db->pquery(
            'SELECT COUNT(*) AS c FROM vtiger_holiday_settings WHERE name = ?',
            ['weekly_holidays']
        );
        $this->assertSame(1, (int) $this->db->query_result($result, 0, 'c'), 'TC-BD-028');
        $this->assertSame(
            [0],
            FR_BusinessDay::getWeeklyHolidays(),
            'TC-BD-029 保存直後にキャッシュも更新される'
        );
    }

    // ---- 4.3 営業日計算（BV-2） -----------------------------------------

    /**
     * 営業日の加算
     *
     * @return array<string,array{0:string,1:int,2:string}>
     */
    public static function 営業日の加算(): array
    {
        return [
            'TC-BD-040 7営業日後' => ['2035-01-01', 7, '2035-01-10'],
            'TC-BD-041 金曜から7営業日後（週休を2回跨ぐ）' => ['2035-01-05', 7, '2035-01-16'],
            'TC-BD-042 1営業日後' => ['2035-01-01', 1, '2035-01-02'],
            'TC-BD-043 0営業日後は起算日のまま（休日でも）' => ['2035-01-06', 0, '2035-01-06'],
            'TC-BD-044 -1営業日は前の営業日' => ['2035-01-08', -1, '2035-01-05'],
        ];
    }

    /**
     * @dataProvider 営業日の加算
     */
    public function test_営業日を加算する(string $from, int $days, string $expected): void
    {
        $this->assertSame($expected, FR_BusinessDay::addBusinessDays($from, $days));
    }

    public function test_TC_BD_045_マスタの休日を飛ばして加算する(): void
    {
        $this->addHoliday('2035-01-02', 'holiday');

        $this->assertSame('2035-01-03', FR_BusinessDay::addBusinessDays('2035-01-01', 1), 'TC-BD-045');
    }

    public function test_TC_BD_046_未入力はnullを返す(): void
    {
        $this->assertNull(FR_BusinessDay::addBusinessDays('', 7), 'TC-BD-046');
    }

    public function test_TC_BD_046b_不正な日付は例外にする(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::addBusinessDays('abc', 7);
    }

    public function test_TC_BD_066_0営業日でも不正な日付は例外にする(): void
    {
        // 0営業日は起算日をそのまま返す経路だが、検証は省略しない
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::addBusinessDays('2026-02-30', 0);
    }

    public function test_TC_BD_050_051_次の営業日を返す(): void
    {
        $this->assertSame(
            '2035-01-08',
            FR_BusinessDay::nextBusinessDay('2035-01-06'),
            'TC-BD-050 土曜の次は月曜'
        );
        $this->assertSame(
            '2035-01-05',
            FR_BusinessDay::nextBusinessDay('2035-01-05'),
            'TC-BD-051 営業日なら当日'
        );
    }

    public function test_TC_BD_052_未入力はnullを返す(): void
    {
        $this->assertNull(FR_BusinessDay::nextBusinessDay('0000-00-00'), 'TC-BD-052');
    }

    public function test_TC_BD_052b_不正な日付は例外にする(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::nextBusinessDay('2026-02-30');
    }

    // ---- 4.3 営業日数（BV-4 / Q-03） ------------------------------------

    /**
     * 営業日数の集計
     *
     * @return array<string,array{0:string,1:string,2:int}>
     */
    public static function 営業日数(): array
    {
        return [
            'TC-BD-060 月〜金は5営業日' => ['2035-01-01', '2035-01-05', 5],
            'TC-BD-061 同日（営業日）は1' => ['2035-01-01', '2035-01-01', 1],
            'TC-BD-062 土日だけなら0' => ['2035-01-06', '2035-01-07', 0],
            'TC-BD-063 逆順でも同じ結果' => ['2035-01-05', '2035-01-01', 5],
            'TC-BD-064 未入力は0' => ['', '2035-01-05', 0],
        ];
    }

    /**
     * @dataProvider 営業日数
     */
    public function test_営業日数を数える(string $from, string $to, int $expected): void
    {
        $this->assertSame($expected, FR_BusinessDay::countBusinessDays($from, $to));
    }

    public function test_TC_BD_064b_不正な日付は例外にする(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::countBusinessDays('abc', '2035-01-05');
    }

    public function test_TC_BD_065_長期間でも打ち切らずに数える(): void
    {
        $actual = FR_BusinessDay::countBusinessDays('2030-01-01', '2045-01-01');

        $this->assertSame(
            $this->countBusinessDaysNaively('2030-01-01', '2045-01-01'),
            $actual,
            'TC-BD-065 1日ずつ数えた結果と一致する'
        );
        // 旧実装は3651日で打ち切っていた
        $this->assertGreaterThan(2600, $actual, 'TC-BD-065 旧実装の上限より多く数えている');
    }

    public function test_TC_BD_065b_週休なしの1年は365日(): void
    {
        $this->setWeeklyHolidays([]);

        $this->assertSame(365, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-12-31'), 'TC-BD-065b');
    }

    public function test_TC_BD_065c_土日休みの2035年は261営業日(): void
    {
        $this->assertSame(261, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-12-31'), 'TC-BD-065c');
    }

    public function test_TC_BD_065d_平日の休日で1日減る(): void
    {
        $this->addHoliday('2035-01-02', 'holiday');

        $this->assertSame(4, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-05'), 'TC-BD-065d');
    }

    public function test_TC_BD_065e_土曜のworkdayで1日増える(): void
    {
        $this->addHoliday('2035-01-06', 'workday');

        $this->assertSame(6, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-07'), 'TC-BD-065e');
    }

    public function test_TC_BD_065f_土曜のholidayは二重に引かない(): void
    {
        $this->addHoliday('2035-01-06', 'holiday');

        $this->assertSame(5, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-07'), 'TC-BD-065f');
    }

    public function test_TC_BD_065g_30年分の集計が1秒未満で終わる(): void
    {
        $start = microtime(true);
        FR_BusinessDay::countBusinessDays('2000-01-01', '2030-12-31');
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, sprintf('TC-BD-065g 実際: %.3f秒', $elapsed));
    }

    // ---- 4.4 マスタ参照 -------------------------------------------------

    public function test_TC_BD_070_071_登録した休祝日を取得する(): void
    {
        $this->addHoliday('2035-03-01', 'holiday');
        $this->addHoliday('2035-03-02', 'workday');
        $this->addHoliday('2035-03-03', 'holiday');

        $rows = FR_BusinessDay::getRegisteredDays('2035-01-01', '2035-12-31');
        $this->assertCount(3, $rows, 'TC-BD-070 登録した3件');
        $this->assertSame('2035-03-01', $rows[0]['holiday_date'], 'TC-BD-070 日付昇順');
        $this->assertCount(
            1,
            FR_BusinessDay::getRegisteredDays('2035-01-01', '2035-12-31', 'workday'),
            'TC-BD-071 種別で絞り込める'
        );
    }

    public function test_TC_BD_072_073_該当なしと未入力は空配列(): void
    {
        $this->assertSame(
            [],
            FR_BusinessDay::getRegisteredDays('2036-01-01', '2036-12-31'),
            'TC-BD-072 該当なし'
        );
        $this->assertSame(
            [],
            FR_BusinessDay::getRegisteredDays('', '2035-12-31'),
            'TC-BD-073 未入力'
        );
    }

    public function test_TC_BD_073b_不正な日付は例外にする(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FR_BusinessDay::getRegisteredDays('2026-02-30', '2035-12-31');
    }

    public function test_TC_BD_075_名称をHTMLエンティティのまま返さない(): void
    {
        $name = self::PREFIX . '<b>祝&"日</b>';
        $this->addHoliday('2035-05-01', 'holiday', $name);

        $rows = FR_BusinessDay::getRegisteredDays('2035-05-01', '2035-05-01');
        $this->assertSame($name, $rows[0]['holiday_name'], 'TC-BD-075');
    }

    /**
     * 1日ずつ数えた営業日数（集計が打ち切られていないことの確認用）
     */
    private function countBusinessDaysNaively(string $from, string $to): int
    {
        $count = 0;
        $current = $from;
        while ($current <= $to) {
            if (FR_BusinessDay::isBusinessDay($current)) {
                $count++;
            }
            $current = date('Y-m-d', strtotime($current . ' +1 day'));
        }

        return $count;
    }
}
