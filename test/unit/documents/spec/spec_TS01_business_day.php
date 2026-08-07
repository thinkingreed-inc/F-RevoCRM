<?php
/**
 * TS-01 営業日・休祝日の共通基盤 自動テスト
 *
 * 対応する仕様書: docs/tests/Documents/TS-01_営業日・休祝日.md
 *
 * Usage:
 *   php test/unit/documents/spec/spec_TS01_business_day.php
 */

require_once dirname(__FILE__) . '/bootstrap.php';
require_once 'include/utils/BusinessDay.php';
require_once 'include/utils/JapaneseHolidays.php';

echo "=== TS-01 営業日・休祝日 ===\n";

$savedWeekly = FR_BusinessDay::getWeeklyHolidays();
SpecRunner::addCleanup(function () use ($savedWeekly) {
    FR_BusinessDay::setWeeklyHolidays($savedWeekly);
    specCleanHolidays();
});

specCleanHolidays();
specSetWeeklyHolidays(array(0, 6));// 土日

// ---------------------------------------------------------------- 4.1 休日判定
SpecRunner::section('4.1 休日判定（DT-1）');

SpecRunner::assertTrue('TC-BD-001', '2035-01-01(月) は営業日', FR_BusinessDay::isBusinessDay('2035-01-01'));
SpecRunner::assertTrue('TC-BD-002', '2035-01-06(土) は休日', FR_BusinessDay::isHoliday('2035-01-06'));
SpecRunner::assertTrue('TC-BD-003', '2035-01-07(日) は休日', FR_BusinessDay::isHoliday('2035-01-07'));

specAddHoliday('2035-01-02', 'holiday');
SpecRunner::assertTrue('TC-BD-004', 'マスタの holiday は休日', FR_BusinessDay::isHoliday('2035-01-02'));
SpecRunner::assertFalse('TC-BD-005', 'マスタの holiday は営業日でない', FR_BusinessDay::isBusinessDay('2035-01-02'));

specAddHoliday('2035-01-06', 'workday');
SpecRunner::assertTrue('TC-BD-006', '土曜の workday は営業日（週休より優先）',
    FR_BusinessDay::isBusinessDay('2035-01-06'));
specAddHoliday('2035-01-05', 'workday');
SpecRunner::assertTrue('TC-BD-007', '平日の workday は営業日のまま',
    FR_BusinessDay::isBusinessDay('2035-01-05'));

// キャッシュ
specAddHoliday('2035-01-09', 'holiday');
FR_BusinessDay::clearCache();
SpecRunner::assertTrue('TC-BD-009', 'clearCache 後は登録が反映される',
    FR_BusinessDay::isHoliday('2035-01-09'));

// 未入力・不正日付
SpecRunner::assertFalse('TC-BD-013', "空文字は false", FR_BusinessDay::isHoliday(''));
SpecRunner::assertFalse('TC-BD-013', 'null は false', FR_BusinessDay::isHoliday(null));
SpecRunner::assertFalse('TC-BD-013', '0000-00-00 は false', FR_BusinessDay::isHoliday('0000-00-00'));
SpecRunner::assertThrows('TC-BD-013b', "'abc' は例外",
    function () { return FR_BusinessDay::isHoliday('abc'); }, 'InvalidArgumentException');
SpecRunner::assertThrows('TC-BD-013b', '2026-02-30 は例外（繰り上げない）',
    function () { return FR_BusinessDay::isHoliday('2026-02-30'); }, 'InvalidArgumentException');
SpecRunner::assertThrows('TC-BD-013b', '2035-13-01 は例外',
    function () { return FR_BusinessDay::isHoliday('2035-13-01'); }, 'InvalidArgumentException');
SpecRunner::assertThrows('TC-BD-014', 'isWeeklyHoliday(abc) は例外',
    function () { return FR_BusinessDay::isWeeklyHoliday('abc'); }, 'InvalidArgumentException');
SpecRunner::assertFalse('TC-BD-014b', 'isWeeklyHoliday("") は false',
    FR_BusinessDay::isWeeklyHoliday(''));

specCleanHolidays();

// ---------------------------------------------------------------- 4.2 週休設定
SpecRunner::section('4.2 週休設定（DT-2 / BV-1）');

specSetWeeklyHolidays(array(0, 6));
SpecRunner::assertSame('TC-BD-020', '設定した週休が読み出せる', array(0, 6), FR_BusinessDay::getWeeklyHolidays());
SpecRunner::assertSame('TC-BD-023', '範囲外・非数値・重複を除いて昇順',
    array(0, 6), FR_BusinessDay::normalizeWeekdays(array(-1, 0, 6, 7, 'x', 6)));
SpecRunner::assertSame('TC-BD-024', '空文字は週休なし', array(), FR_BusinessDay::normalizeWeekdays(''));
SpecRunner::assertSame('TC-BD-025', 'カンマ区切りを昇順に整える',
    array(0, 6), FR_BusinessDay::normalizeWeekdays('6,0'));

specSetWeeklyHolidays(array());
SpecRunner::assertFalse('TC-BD-026', '週休なしなら土曜も営業日', FR_BusinessDay::isHoliday('2035-01-06'));

specSetWeeklyHolidays(array(0, 1, 2, 3, 4, 5, 6));
SpecRunner::assertSame('TC-BD-027', '全曜日が週休なら addBusinessDays は null',
    null, FR_BusinessDay::addBusinessDays('2035-01-01', 1));

specSetWeeklyHolidays(array(0));
SpecRunner::assertSame('TC-BD-028', '2回保存しても設定行は1行', 1, (function () {
    FR_BusinessDay::setWeeklyHolidays(array(0));
    $db = PearDatabase::getInstance();
    $result = $db->pquery(
        "SELECT COUNT(*) AS c FROM vtiger_holiday_settings WHERE name = ?", array('weekly_holidays'));
    return (int) $db->query_result($result, 0, 'c');
})());
SpecRunner::assertSame('TC-BD-029', '保存直後にキャッシュも更新される',
    array(0), FR_BusinessDay::getWeeklyHolidays());

specSetWeeklyHolidays(array(0, 6));

// ---------------------------------------------------------------- 4.3 営業日計算
SpecRunner::section('4.3 営業日計算（BV-2）');

SpecRunner::assertSame('TC-BD-040', '2035-01-01 の7営業日後',
    '2035-01-10', FR_BusinessDay::addBusinessDays('2035-01-01', 7));
SpecRunner::assertSame('TC-BD-041', '2035-01-05(金) の7営業日後',
    '2035-01-16', FR_BusinessDay::addBusinessDays('2035-01-05', 7));
SpecRunner::assertSame('TC-BD-042', '1営業日後',
    '2035-01-02', FR_BusinessDay::addBusinessDays('2035-01-01', 1));
SpecRunner::assertSame('TC-BD-043', '0営業日後は起算日のまま（休日でも）',
    '2035-01-06', FR_BusinessDay::addBusinessDays('2035-01-06', 0));
SpecRunner::assertSame('TC-BD-044', '-1営業日は前の営業日',
    '2035-01-05', FR_BusinessDay::addBusinessDays('2035-01-08', -1));

specAddHoliday('2035-01-02', 'holiday');
SpecRunner::assertSame('TC-BD-045', 'マスタの休日を飛ばす',
    '2035-01-03', FR_BusinessDay::addBusinessDays('2035-01-01', 1));
specCleanHolidays();

SpecRunner::assertSame('TC-BD-046', '未入力は null', null, FR_BusinessDay::addBusinessDays('', 7));
SpecRunner::assertThrows('TC-BD-046b', '不正な日付は例外',
    function () { return FR_BusinessDay::addBusinessDays('abc', 7); }, 'InvalidArgumentException');
SpecRunner::assertThrows('TC-BD-066', '2026-02-30 + 0営業日 は例外',
    function () { return FR_BusinessDay::addBusinessDays('2026-02-30', 0); }, 'InvalidArgumentException');

SpecRunner::assertSame('TC-BD-050', '土曜の次の営業日は月曜',
    '2035-01-08', FR_BusinessDay::nextBusinessDay('2035-01-06'));
SpecRunner::assertSame('TC-BD-051', '営業日なら当日を返す',
    '2035-01-05', FR_BusinessDay::nextBusinessDay('2035-01-05'));
SpecRunner::assertSame('TC-BD-052', '未入力は null', null, FR_BusinessDay::nextBusinessDay('0000-00-00'));
SpecRunner::assertThrows('TC-BD-052b', '不正な日付は例外',
    function () { return FR_BusinessDay::nextBusinessDay('2026-02-30'); }, 'InvalidArgumentException');

// ---------------------------------------------------------------- 営業日数
SpecRunner::section('4.3 営業日数（BV-4 / Q-03）');

SpecRunner::assertSame('TC-BD-060', '月〜金は5営業日',
    5, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-05'));
SpecRunner::assertSame('TC-BD-061', '同日（営業日）は1',
    1, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-01'));
SpecRunner::assertSame('TC-BD-062', '土日だけなら0',
    0, FR_BusinessDay::countBusinessDays('2035-01-06', '2035-01-07'));
SpecRunner::assertSame('TC-BD-063', '逆順でも同じ結果',
    5, FR_BusinessDay::countBusinessDays('2035-01-05', '2035-01-01'));
SpecRunner::assertSame('TC-BD-064', '未入力は0',
    0, FR_BusinessDay::countBusinessDays('', '2035-01-05'));
SpecRunner::assertThrows('TC-BD-064b', '不正な日付は例外',
    function () { return FR_BusinessDay::countBusinessDays('abc', '2035-01-05'); },
    'InvalidArgumentException');

// 1日ずつ数えた結果と一致するか（打ち切られていないことの確認）
function specCountNaive($from, $to) {
    $count = 0;
    $current = $from;
    while ($current <= $to) {
        if (FR_BusinessDay::isBusinessDay($current)) $count++;
        $current = date('Y-m-d', strtotime($current . ' +1 day'));
    }
    return $count;
}
$actual = FR_BusinessDay::countBusinessDays('2030-01-01', '2045-01-01');
SpecRunner::assertSame('TC-BD-065', '5480日（15年）でも打ち切らない',
    specCountNaive('2030-01-01', '2045-01-01'), $actual);
SpecRunner::assertTrue('TC-BD-065', '旧実装の上限(3651日)より多く数えている', $actual > 2600,
    "実際: {$actual}");

specSetWeeklyHolidays(array());
SpecRunner::assertSame('TC-BD-065b', '週休なしの1年は365日',
    365, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-12-31'));
specSetWeeklyHolidays(array(0, 6));
SpecRunner::assertSame('TC-BD-065c', '土日休みの2035年は261営業日',
    261, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-12-31'));

specAddHoliday('2035-01-02', 'holiday');
SpecRunner::assertSame('TC-BD-065d', '平日の休日で1日減る',
    4, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-05'));
specCleanHolidays();

specAddHoliday('2035-01-06', 'workday');
SpecRunner::assertSame('TC-BD-065e', '土曜の workday で1日増える',
    6, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-07'));
specCleanHolidays();

specAddHoliday('2035-01-06', 'holiday');
SpecRunner::assertSame('TC-BD-065f', '土曜の holiday は二重に引かない',
    5, FR_BusinessDay::countBusinessDays('2035-01-01', '2035-01-07'));
specCleanHolidays();

$start = microtime(true);
FR_BusinessDay::countBusinessDays('2000-01-01', '2030-12-31');
$elapsed = microtime(true) - $start;
SpecRunner::assertTrue('TC-BD-065g', sprintf('30年分の集計が1秒未満（%.3f秒）', $elapsed), $elapsed < 1.0);

// ---------------------------------------------------------------- 4.4 マスタ参照
SpecRunner::section('4.4 マスタ参照');

specAddHoliday('2035-03-01', 'holiday');
specAddHoliday('2035-03-02', 'workday');
specAddHoliday('2035-03-03', 'holiday');
$rows = FR_BusinessDay::getRegisteredDays('2035-01-01', '2035-12-31');
SpecRunner::assertSame('TC-BD-070', '登録した3件を取得', 3, count($rows));
SpecRunner::assertSame('TC-BD-070', '日付昇順', '2035-03-01', $rows[0]['holiday_date']);
SpecRunner::assertSame('TC-BD-071', 'workday だけ絞り込める',
    1, count(FR_BusinessDay::getRegisteredDays('2035-01-01', '2035-12-31', 'workday')));
SpecRunner::assertSame('TC-BD-072', '該当なしは空配列',
    array(), FR_BusinessDay::getRegisteredDays('2036-01-01', '2036-12-31'));
SpecRunner::assertSame('TC-BD-073', '未入力は空配列',
    array(), FR_BusinessDay::getRegisteredDays('', '2035-12-31'));
SpecRunner::assertThrows('TC-BD-073b', '不正な日付は例外',
    function () { return FR_BusinessDay::getRegisteredDays('2026-02-30', '2035-12-31'); },
    'InvalidArgumentException');

specCleanHolidays();
specAddHoliday('2035-05-01', 'holiday', SPEC_PREFIX . '<b>祝&"日</b>');
$rows = FR_BusinessDay::getRegisteredDays('2035-05-01', '2035-05-01');
SpecRunner::assertSame('TC-BD-075', '名称がHTMLエンティティのまま返らない',
    SPEC_PREFIX . '<b>祝&"日</b>', $rows[0]['holiday_name']);
specCleanHolidays();

// ---------------------------------------------------------------- 4.5 祝日算出
SpecRunner::section('4.5 国民の祝日の算出（DT-3 / BV-5）');

$h2026 = FR_JapaneseHolidays::forYear(2026);
SpecRunner::assertSame('TC-JH-001', '2026年は18件', 18, count($h2026));
SpecRunner::assertSame('TC-JH-002', '元日', '元日', $h2026['2026-01-01']);
SpecRunner::assertSame('TC-JH-003', '成人の日は1月第2月曜(1/12)', '成人の日', $h2026['2026-01-12']);
SpecRunner::assertSame('TC-JH-004', '海の日は7月第3月曜(7/20)', '海の日', $h2026['2026-07-20']);
SpecRunner::assertSame('TC-JH-005', '敬老の日(9/21)', '敬老の日', $h2026['2026-09-21']);
SpecRunner::assertSame('TC-JH-005', 'スポーツの日(10/12)', 'スポーツの日', $h2026['2026-10-12']);
SpecRunner::assertSame('TC-JH-006', '春分の日(3/20)', '春分の日', $h2026['2026-03-20']);
SpecRunner::assertSame('TC-JH-006', '秋分の日(9/23)', '秋分の日', $h2026['2026-09-23']);
SpecRunner::assertSame('TC-JH-010', '5/3(日)の振替は5/6（5/4・5/5を飛ばす）',
    '振替休日', $h2026['2026-05-06']);
SpecRunner::assertSame('TC-JH-011', '9/22は国民の休日', '国民の休日', $h2026['2026-09-22']);

$h2027 = FR_JapaneseHolidays::forYear(2027);
SpecRunner::assertFalse('TC-JH-012', '2027-09-21は祝日でない', isset($h2027['2027-09-21']));
SpecRunner::assertFalse('TC-JH-012', '2027-09-22は祝日でない', isset($h2027['2027-09-22']));
SpecRunner::assertSame('TC-JH-013', '2027-03-21(日)の振替は3/22', '振替休日', $h2027['2027-03-22']);

$h2020 = FR_JapaneseHolidays::forYear(2020);
SpecRunner::assertSame('TC-JH-014', '2020年は18件（特例年）', 18, count($h2020));
SpecRunner::assertSame('TC-JH-014', '2020 海の日は7/23', '海の日', $h2020['2020-07-23']);
SpecRunner::assertSame('TC-JH-014', '2020 スポーツの日は7/24', 'スポーツの日', $h2020['2020-07-24']);
SpecRunner::assertSame('TC-JH-014', '2020 山の日は8/10', '山の日', $h2020['2020-08-10']);
SpecRunner::assertFalse('TC-JH-015', '2020 通常の海の日(7/20)は無い', isset($h2020['2020-07-20']));
SpecRunner::assertFalse('TC-JH-015', '2020 通常の山の日(8/11)は無い', isset($h2020['2020-08-11']));

$h2021 = FR_JapaneseHolidays::forYear(2021);
SpecRunner::assertSame('TC-JH-016', '2021年は17件（特例年）', 17, count($h2021));
SpecRunner::assertSame('TC-JH-016', '2021 海の日は7/22', '海の日', $h2021['2021-07-22']);
SpecRunner::assertSame('TC-JH-016', '2021 山の日は8/8', '山の日', $h2021['2021-08-08']);
SpecRunner::assertSame('TC-JH-016', '2021 8/9は振替休日', '振替休日', $h2021['2021-08-09']);

SpecRunner::assertSame('TC-JH-017', '対応開始年は2020', 2020, FR_JapaneseHolidays::SUPPORTED_FROM_YEAR);
SpecRunner::assertSame('TC-JH-018', '2回呼んでも同じ結果',
    FR_JapaneseHolidays::forYear(2026), FR_JapaneseHolidays::forYear(2026));

// ---------------------------------------------------------------- 4.6 CSV解析
SpecRunner::section('4.6 内閣府公表CSVの解析（DT-4）');

$header = "国民の祝日・休日月日,国民の祝日・休日名称\n";
$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026/1/1,元日\n");
SpecRunner::assertSame('TC-JH-020', 'ヘッダーを無視して1件', 1, count($parsed));
SpecRunner::assertSame('TC-JH-020', '日付を Y-m-d に整える', '元日', $parsed['2026-01-01']);

$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "\n\n2026/1/1,元日\n\n");
SpecRunner::assertSame('TC-JH-021', '空行を無視', 1, count($parsed));
$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026/1/1\n2026/1/2,振替休日\n");
SpecRunner::assertSame('TC-JH-022', 'カラム不足の行を無視', 1, count($parsed));
$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026/1/1,\n2026/1/2,振替休日\n");
SpecRunner::assertSame('TC-JH-023', '名称が空の行を無視', 1, count($parsed));
$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026-01-01,元日\n");
SpecRunner::assertSame('TC-JH-024', 'ハイフン区切りも受理', 1, count($parsed));

$sjis = mb_convert_encoding($header . "2026/1/1,元日\n", 'SJIS-win', 'UTF-8');
$parsed = FR_JapaneseHolidays::parseOfficialCsv($sjis);
SpecRunner::assertSame('TC-JH-025', 'Shift_JIS を UTF-8 に変換', '元日', $parsed['2026-01-01']);

$parsed = FR_JapaneseHolidays::parseOfficialCsv("\xEF\xBB\xBF" . $header . "2026/1/1,元日\n");
SpecRunner::assertSame('TC-JH-026', 'BOM を除去して解析', 1, count($parsed));

$csv = $header . "2026/5/3,憲法記念日\n2026/5/4,みどりの日\n2026/5/5,こどもの日\n2026/5/6,休日\n";
$parsed = FR_JapaneseHolidays::parseOfficialCsv($csv);
SpecRunner::assertSame('TC-JH-027', '前日が祝日の「休日」は振替休日', '振替休日', $parsed['2026-05-06']);

$csv = $header . "2026/9/21,敬老の日\n2026/9/22,休日\n2026/9/23,秋分の日\n";
$parsed = FR_JapaneseHolidays::parseOfficialCsv($csv);
SpecRunner::assertSame('TC-JH-028', '前後が祝日の「休日」は国民の休日', '国民の休日', $parsed['2026-09-22']);

$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026/6/1,休日\n");
SpecRunner::assertSame('TC-JH-029', '前後に祝日が無ければ名称そのまま', '休日', $parsed['2026-06-01']);

$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026/5/3,憲法記念日\n2026/1/1,元日\n");
SpecRunner::assertSame('TC-JH-030', '日付昇順で返る',
    array('2026-01-01', '2026-05-03'), array_keys($parsed));

SpecRunner::assertThrows('TC-JH-031', '空のCSVは例外',
    function () { return FR_JapaneseHolidays::parseOfficialCsv(''); });
SpecRunner::assertThrows('TC-JH-031', '空白のみのCSVは例外',
    function () { return FR_JapaneseHolidays::parseOfficialCsv('   '); });
SpecRunner::assertThrows('TC-JH-032', '有効行が無いCSVは例外',
    function () { return FR_JapaneseHolidays::parseOfficialCsv("a,b\nc,d\n"); });
SpecRunner::assertThrows('TC-JH-033', 'HTMLは例外',
    function () { return FR_JapaneseHolidays::parseOfficialCsv('<html><body>404</body></html>'); });

$crlf = FR_JapaneseHolidays::parseOfficialCsv("h,h\r\n2026/1/1,元日\r\n");
$cr = FR_JapaneseHolidays::parseOfficialCsv("h,h\r2026/1/1,元日\r");
SpecRunner::assertSame('TC-JH-034', '改行コードが違っても同じ結果', $crlf, $cr);

$parsed = FR_JapaneseHolidays::parseOfficialCsv($header . "2026/1/1,元日\n2026/1/1,別名\n");
SpecRunner::assertSame('TC-JH-035', '同じ日付は後勝ちで1件', '別名', $parsed['2026-01-01']);

SpecRunner::cleanup();
exit(SpecRunner::summarize('TS-01'));
