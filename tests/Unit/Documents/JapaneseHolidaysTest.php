<?php

namespace Tests\Unit\Documents;

use FR_JapaneseHolidays;
use Exception;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/LanguageHandlerStubs.php';
require_once dirname(__DIR__, 3) . '/include/utils/JapaneseHolidays.php';

/**
 * 国民の祝日の算出と内閣府公表CSVの解析
 *
 * 対応する仕様書: docs/tests/Documents/TS-01_営業日・休祝日.md
 *   4.5 国民の祝日の算出（DT-3 / BV-5）
 *   4.6 内閣府公表CSVの解析（DT-4）
 *
 * FR_JapaneseHolidays は DB を参照しないため Unit に置く。
 */
final class JapaneseHolidaysTest extends TestCase
{
    /** CSV のヘッダー行（内閣府公表ファイルと同じ） */
    private const CSV_HEADER = "国民の祝日・休日月日,国民の祝日・休日名称\n";

    // ---- 4.5 国民の祝日の算出 -------------------------------------------

    public function test_TC_JH_001_002_2026年の祝日を算出する(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2026);

        $this->assertCount(18, $holidays, 'TC-JH-001 2026年は18件');
        $this->assertSame('元日', $holidays['2026-01-01'], 'TC-JH-002 元日');
    }

    public function test_TC_JH_003_005_ハッピーマンデーの祝日(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2026);

        $this->assertSame('成人の日', $holidays['2026-01-12'], 'TC-JH-003 1月第2月曜');
        $this->assertSame('海の日', $holidays['2026-07-20'], 'TC-JH-004 7月第3月曜');
        $this->assertSame('敬老の日', $holidays['2026-09-21'], 'TC-JH-005 9月第3月曜');
        $this->assertSame('スポーツの日', $holidays['2026-10-12'], 'TC-JH-005 10月第2月曜');
    }

    public function test_TC_JH_006_春分の日と秋分の日(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2026);

        $this->assertSame('春分の日', $holidays['2026-03-20'], 'TC-JH-006 春分の日');
        $this->assertSame('秋分の日', $holidays['2026-09-23'], 'TC-JH-006 秋分の日');
    }

    public function test_TC_JH_010_日曜の祝日は連休を飛ばして振り替える(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2026);

        // 5/3(日)の振替は 5/4・5/5 が祝日のため 5/6 になる
        $this->assertSame('振替休日', $holidays['2026-05-06'], 'TC-JH-010');
    }

    public function test_TC_JH_011_012_祝日に挟まれた平日は国民の休日(): void
    {
        $holidays2026 = FR_JapaneseHolidays::forYear(2026);
        $this->assertSame('国民の休日', $holidays2026['2026-09-22'], 'TC-JH-011');

        // 2027年は敬老の日と秋分の日が離れるため国民の休日にならない
        $holidays2027 = FR_JapaneseHolidays::forYear(2027);
        $this->assertArrayNotHasKey('2027-09-21', $holidays2027, 'TC-JH-012');
        $this->assertArrayNotHasKey('2027-09-22', $holidays2027, 'TC-JH-012');
    }

    public function test_TC_JH_013_日曜の祝日の翌日が振替休日(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2027);

        $this->assertSame('振替休日', $holidays['2027-03-22'], 'TC-JH-013 3/21(日)の振替');
    }

    public function test_TC_JH_014_015_2020年は特例で祝日が移動する(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2020);

        $this->assertCount(18, $holidays, 'TC-JH-014 2020年は18件');
        $this->assertSame('海の日', $holidays['2020-07-23'], 'TC-JH-014');
        $this->assertSame('スポーツの日', $holidays['2020-07-24'], 'TC-JH-014');
        $this->assertSame('山の日', $holidays['2020-08-10'], 'TC-JH-014');
        $this->assertArrayNotHasKey('2020-07-20', $holidays, 'TC-JH-015 通常の海の日は無い');
        $this->assertArrayNotHasKey('2020-08-11', $holidays, 'TC-JH-015 通常の山の日は無い');
    }

    public function test_TC_JH_016_2021年も特例で祝日が移動する(): void
    {
        $holidays = FR_JapaneseHolidays::forYear(2021);

        $this->assertCount(17, $holidays, 'TC-JH-016 2021年は17件');
        $this->assertSame('海の日', $holidays['2021-07-22'], 'TC-JH-016');
        $this->assertSame('山の日', $holidays['2021-08-08'], 'TC-JH-016');
        $this->assertSame('振替休日', $holidays['2021-08-09'], 'TC-JH-016');
    }

    public function test_TC_JH_017_018_対応開始年と冪等性(): void
    {
        $this->assertSame(2020, FR_JapaneseHolidays::SUPPORTED_FROM_YEAR, 'TC-JH-017');
        $this->assertSame(
            FR_JapaneseHolidays::forYear(2026),
            FR_JapaneseHolidays::forYear(2026),
            'TC-JH-018 2回呼んでも同じ結果'
        );
    }

    // ---- 4.6 内閣府公表CSVの解析 ----------------------------------------

    public function test_TC_JH_020_ヘッダーを無視して日付を整える(): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(self::CSV_HEADER . "2026/1/1,元日\n");

        $this->assertCount(1, $parsed, 'TC-JH-020 ヘッダーを無視');
        $this->assertSame('元日', $parsed['2026-01-01'], 'TC-JH-020 Y-m-d に整える');
    }

    /**
     * 読み飛ばすべき行
     *
     * @return array<string,array{0:string,1:int}>
     */
    public static function 無視する行(): array
    {
        return [
            'TC-JH-021 空行' => ["\n\n2026/1/1,元日\n\n", 1],
            'TC-JH-022 カラム不足' => ["2026/1/1\n2026/1/2,振替休日\n", 1],
            'TC-JH-023 名称が空' => ["2026/1/1,\n2026/1/2,振替休日\n", 1],
        ];
    }

    /**
     * @dataProvider 無視する行
     */
    public function test_読み飛ばすべき行は解析結果に含めない(string $body, int $expected): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(self::CSV_HEADER . $body);

        $this->assertCount($expected, $parsed);
    }

    public function test_TC_JH_024_ハイフン区切りの日付も受理する(): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(self::CSV_HEADER . "2026-01-01,元日\n");

        $this->assertCount(1, $parsed, 'TC-JH-024');
    }

    public function test_TC_JH_025_ShiftJISをUTF8に変換する(): void
    {
        $sjis = mb_convert_encoding(self::CSV_HEADER . "2026/1/1,元日\n", 'SJIS-win', 'UTF-8');
        $parsed = FR_JapaneseHolidays::parseOfficialCsv($sjis);

        $this->assertSame('元日', $parsed['2026-01-01'], 'TC-JH-025');
    }

    public function test_TC_JH_026_BOMを除去して解析する(): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(
            "\xEF\xBB\xBF" . self::CSV_HEADER . "2026/1/1,元日\n"
        );

        $this->assertCount(1, $parsed, 'TC-JH-026');
    }

    public function test_TC_JH_027_前日が祝日の休日は振替休日にする(): void
    {
        $csv = self::CSV_HEADER
            . "2026/5/3,憲法記念日\n2026/5/4,みどりの日\n2026/5/5,こどもの日\n2026/5/6,休日\n";
        $parsed = FR_JapaneseHolidays::parseOfficialCsv($csv);

        $this->assertSame('振替休日', $parsed['2026-05-06'], 'TC-JH-027');
    }

    public function test_TC_JH_028_前後が祝日の休日は国民の休日にする(): void
    {
        $csv = self::CSV_HEADER . "2026/9/21,敬老の日\n2026/9/22,休日\n2026/9/23,秋分の日\n";
        $parsed = FR_JapaneseHolidays::parseOfficialCsv($csv);

        $this->assertSame('国民の休日', $parsed['2026-09-22'], 'TC-JH-028');
    }

    public function test_TC_JH_029_前後に祝日が無ければ名称をそのまま使う(): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(self::CSV_HEADER . "2026/6/1,休日\n");

        $this->assertSame('休日', $parsed['2026-06-01'], 'TC-JH-029');
    }

    public function test_TC_JH_030_日付昇順で返す(): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(
            self::CSV_HEADER . "2026/5/3,憲法記念日\n2026/1/1,元日\n"
        );

        $this->assertSame(['2026-01-01', '2026-05-03'], array_keys($parsed), 'TC-JH-030');
    }

    /**
     * 解析できない入力
     *
     * @return array<string,array{0:string}>
     */
    public static function 解析できないCSV(): array
    {
        return [
            'TC-JH-031 空' => [''],
            'TC-JH-031 空白のみ' => ['   '],
            'TC-JH-032 有効行が無い' => ["a,b\nc,d\n"],
            'TC-JH-033 HTML' => ['<html><body>404</body></html>'],
        ];
    }

    /**
     * @dataProvider 解析できないCSV
     */
    public function test_解析できないCSVは例外にする(string $csv): void
    {
        // 形式不正は InvalidArgumentException ではなく Exception で返る
        $this->expectException(Exception::class);
        FR_JapaneseHolidays::parseOfficialCsv($csv);
    }

    public function test_TC_JH_034_改行コードが違っても同じ結果になる(): void
    {
        $crlf = FR_JapaneseHolidays::parseOfficialCsv("h,h\r\n2026/1/1,元日\r\n");
        $cr = FR_JapaneseHolidays::parseOfficialCsv("h,h\r2026/1/1,元日\r");

        $this->assertSame($crlf, $cr, 'TC-JH-034');
    }

    public function test_TC_JH_035_同じ日付は後勝ちで1件にする(): void
    {
        $parsed = FR_JapaneseHolidays::parseOfficialCsv(
            self::CSV_HEADER . "2026/1/1,元日\n2026/1/1,別名\n"
        );

        $this->assertSame('別名', $parsed['2026-01-01'], 'TC-JH-035');
    }
}
