<?php
/**
 * 休祝日マスタと営業日計算のテスト
 *
 * 検証内容:
 *   1. 国民の祝日の算出（振替休日・国民の休日・特例年）
 *   2. 休日判定（週休・マスタ・休日出勤日の特例）
 *   3. 営業日計算（N営業日後・営業日数・次の営業日）
 *   4. マスタのCRUD（重複日付の拒否を含む）
 *   5. 国民の祝日の一括登録（既存はスキップ）
 *   6. 内閣府公表データ（CSV）の解析
 *   7. 公表データの取り込み（変則年の是正・会社休日の保持・冪等性）
 *   8. 週休の設定（画面から変更した内容がDBに保存され判定に反映されること）
 *
 * Usage:
 *   php test_holidays.php
 */

chdir(dirname(__FILE__) . '/../../../');
// config.php 経由で読み込む（config.customize.php の設定を反映させるため）
require_once 'config.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
vimport('includes.runtime.Globals');
vimport('includes.runtime.LanguageHandler');
vimport('includes.runtime.BaseModel');
require_once 'modules/Users/Users.php';
require_once 'modules/Users/models/Record.php';
require_once 'modules/Settings/Vtiger/models/Module.php';
require_once 'modules/Settings/Vtiger/models/Record.php';
require_once 'modules/Settings/Holidays/models/Module.php';
require_once 'modules/Settings/Holidays/models/Record.php';
require_once 'include/utils/BusinessDay.php';
require_once 'include/utils/JapaneseHolidays.php';

$adb = PearDatabase::getInstance();

$failures = array();
function check($label, $condition, $detail = '') {
    global $failures;
    echo ($condition ? "  [OK]   " : "  [NG]   ") . $label . ($detail !== '' ? " : $detail" : '') . "\n";
    if (!$condition) $failures[] = $label;
}

/** テスト用に登録した休日を削除する */
function cleanupTestHolidays() {
    $adb = PearDatabase::getInstance();
    $adb->pquery("DELETE FROM vtiger_holidays WHERE holiday_name LIKE ?", array('HOLIDAYTEST%'));
    $adb->pquery("DELETE FROM vtiger_holidays WHERE YEAR(holiday_date) = ?", array(2035));
    FR_BusinessDay::clearCache();
}

echo "=== 休祝日マスタ・営業日計算テスト ===\n";
cleanupTestHolidays();

echo "\n1. 国民の祝日の算出\n";
$holidays2026 = FR_JapaneseHolidays::forYear(2026);
check('2026年の祝日を算出できる', count($holidays2026) === 18, count($holidays2026) . '件');
check('元日', isset($holidays2026['2026-01-01']));
check('成人の日は1月第2月曜（2026-01-12）', isset($holidays2026['2026-01-12']));
check('振替休日（2026-05-06 憲法記念日が日曜）', isset($holidays2026['2026-05-06'])
    && $holidays2026['2026-05-06'] === '振替休日');
check('国民の休日（2026-09-22）', isset($holidays2026['2026-09-22'])
    && $holidays2026['2026-09-22'] === '国民の休日');

$holidays2025 = FR_JapaneseHolidays::forYear(2025);
check('2025年の振替休日（2025-02-24 天皇誕生日が日曜）', isset($holidays2025['2025-02-24']));
check('2025年の振替休日（2025-11-24 勤労感謝の日が日曜）', isset($holidays2025['2025-11-24']));

$holidays2021 = FR_JapaneseHolidays::forYear(2021);
check('特例年2021の海の日（7/22）', isset($holidays2021['2021-07-22'])
    && $holidays2021['2021-07-22'] === '海の日');
check('特例年2021は通常日付の海の日を含まない', !isset($holidays2021['2021-07-19']));

echo "\n2. 休日判定\n";
check('週休は土日（初期値）', FR_BusinessDay::getWeeklyHolidays() === array(0, 6),
    implode(',', FR_BusinessDay::getWeeklyHolidays()));
check('土曜は休日', FR_BusinessDay::isHoliday('2026-08-08'));
check('平日は営業日', FR_BusinessDay::isBusinessDay('2026-08-06'));
check('マスタの祝日は休日（2026-01-01）', FR_BusinessDay::isHoliday('2026-01-01'));

// 休日出勤日（所定休日を営業日として扱う）
$workday = new Settings_Holidays_Record_Model();
$workday->set('holiday_date', '2026-08-08');
$workday->set('holiday_name', 'HOLIDAYTEST_休日出勤');
$workday->set('day_type', 'workday');
$workday->set('holiday_type', 'company');
$workdayId = $workday->save();
check('休日出勤日を登録すると土曜でも営業日になる', FR_BusinessDay::isBusinessDay('2026-08-08'));
Settings_Holidays_Module_Model::delete($workdayId);
check('削除すると元の判定に戻る', FR_BusinessDay::isHoliday('2026-08-08'));

echo "\n3. 営業日計算\n";
check('7営業日後（2026-08-06 → 2026-08-18。山の日をまたぐ）',
    FR_BusinessDay::addBusinessDays('2026-08-06', 7) === '2026-08-18',
    (string) FR_BusinessDay::addBusinessDays('2026-08-06', 7));
check('3営業日後（2026-09-18 → 2026-09-28。敬老/国民/秋分をまたぐ）',
    FR_BusinessDay::addBusinessDays('2026-09-18', 3) === '2026-09-28',
    (string) FR_BusinessDay::addBusinessDays('2026-09-18', 3));
check('2026年8月の営業日数は20日', FR_BusinessDay::countBusinessDays('2026-08-01', '2026-08-31') === 20,
    (string) FR_BusinessDay::countBusinessDays('2026-08-01', '2026-08-31'));
// 1/2・1/3 は国民の祝日ではないため、会社休日として登録しない限り営業日になる
check('次の営業日（2026-01-01 → 2026-01-02）',
    FR_BusinessDay::nextBusinessDay('2026-01-01') === '2026-01-02',
    (string) FR_BusinessDay::nextBusinessDay('2026-01-01'));
// 会社休日を登録すると翌営業日が変わる
$newYear = new Settings_Holidays_Record_Model();
$newYear->set('holiday_date', '2026-01-02');
$newYear->set('holiday_name', 'HOLIDAYTEST_年始休業');
$newYear->set('day_type', 'holiday');
$newYear->set('holiday_type', 'company');
$newYearId = $newYear->save();
check('会社休日を登録すると翌営業日が繰り越される（2026-01-05）',
    FR_BusinessDay::nextBusinessDay('2026-01-01') === '2026-01-05',
    (string) FR_BusinessDay::nextBusinessDay('2026-01-01'));
Settings_Holidays_Module_Model::delete($newYearId);
check('過去方向にも数えられる（-1営業日）',
    FR_BusinessDay::addBusinessDays('2026-08-06', -1) === '2026-08-05',
    (string) FR_BusinessDay::addBusinessDays('2026-08-06', -1));
check('不正な日付は null', FR_BusinessDay::addBusinessDays('', 3) === null);

echo "\n4. マスタのCRUD\n";
$record = new Settings_Holidays_Record_Model();
$record->set('holiday_date', '2035-06-15');
$record->set('holiday_name', 'HOLIDAYTEST_会社休日');
$record->set('day_type', 'holiday');
$record->set('holiday_type', 'company');
$record->set('description', 'テスト');
$recordId = $record->save();
check('登録できる', $recordId > 0, "id={$recordId}");
check('登録した日が休日になる', FR_BusinessDay::isHoliday('2035-06-15'));

$loaded = Settings_Holidays_Record_Model::getInstanceById($recordId);
check('取得できる', $loaded !== null && $loaded->get('holiday_name') === 'HOLIDAYTEST_会社休日');

$loaded->set('holiday_name', 'HOLIDAYTEST_会社休日（更新）');
$loaded->save();
$reloaded = Settings_Holidays_Record_Model::getInstanceById($recordId);
check('更新できる', $reloaded->get('holiday_name') === 'HOLIDAYTEST_会社休日（更新）');

// 重複日付
$duplicate = new Settings_Holidays_Record_Model();
$duplicate->set('holiday_date', '2035-06-15');
$duplicate->set('holiday_name', 'HOLIDAYTEST_重複');
$duplicated = false;
try {
    $duplicate->save();
} catch (Exception $e) {
    $duplicated = true;
}
check('同じ日付は登録できない', $duplicated);

// 入力チェック
foreach (array(
    array('date' => '', 'name' => 'HOLIDAYTEST_日付なし', 'label' => '日付が空だとエラー'),
    array('date' => '2035/06/16', 'name' => 'HOLIDAYTEST_書式違い', 'label' => '日付の書式違いはエラー'),
    array('date' => '2035-06-16', 'name' => '', 'label' => '名称が空だとエラー'),
) as $case) {
    $invalid = new Settings_Holidays_Record_Model();
    $invalid->set('holiday_date', $case['date']);
    $invalid->set('holiday_name', $case['name']);
    $rejected = false;
    try {
        $invalid->save();
    } catch (Exception $e) {
        $rejected = true;
    }
    check($case['label'], $rejected);
}

Settings_Holidays_Module_Model::delete($recordId);
check('削除できる', Settings_Holidays_Record_Model::getInstanceById($recordId) === null);
check('削除後は休日でなくなる', !FR_BusinessDay::isHoliday('2035-06-15'));

echo "\n5. 国民の祝日の一括登録\n";
$result = Settings_Holidays_Module_Model::generateNationalHolidays(2035);
check('一括登録できる', $result['registered'] > 0,
    "追加={$result['registered']} スキップ={$result['skipped']}");
$again = Settings_Holidays_Module_Model::generateNationalHolidays(2035);
check('再実行では既存をスキップする', $again['registered'] === 0 && $again['skipped'] > 0,
    "追加={$again['registered']} スキップ={$again['skipped']}");
check('登録した年が休日として判定される', FR_BusinessDay::isHoliday('2035-01-01'));

$unsupported = false;
try {
    Settings_Holidays_Module_Model::generateNationalHolidays(2019);
} catch (Exception $e) {
    $unsupported = true;
}
check('対応範囲外の年は拒否する（2019年）', $unsupported);

echo "\n6. 内閣府公表データの解析\n";
// 公表CSVと同じ形式（ヘッダー1行＋「YYYY/M/D,名称」、文字コードは Shift_JIS）
$officialCsvUtf8 = "国民の祝日・休日月日,国民の祝日・休日名称\n"
    . "2026/9/21,敬老の日\n"
    . "2026/9/22,休日\n"
    . "2026/9/23,秋分の日\n"
    . "2035/1/1,元日\n"
    . "2035/2/11,建国記念の日\n"
    . "2035/2/12,休日\n"
    . "2035/7/16,海の日\n";
$officialCsvSjis = mb_convert_encoding($officialCsvUtf8, 'SJIS-win', 'UTF-8');

$parsed = FR_JapaneseHolidays::parseOfficialCsv($officialCsvSjis);
check('Shift_JIS のCSVを解析できる', count($parsed) === 7, count($parsed) . '件');
check('ヘッダー行を取り込まない', !isset($parsed['0000-00-00']) && array_key_first($parsed) === '2026-09-21');
check('日付を Y-m-d に正規化する', isset($parsed['2035-07-16']));
check('名称を取り込む', isset($parsed['2035-01-01']) && $parsed['2035-01-01'] === '元日');
check('「休日」を振替休日と判別する（前日が祝日）',
    isset($parsed['2035-02-12']) && $parsed['2035-02-12'] === '振替休日',
    isset($parsed['2035-02-12']) ? $parsed['2035-02-12'] : '未取得');
check('「休日」を国民の休日と判別する（前後が祝日）',
    isset($parsed['2026-09-22']) && $parsed['2026-09-22'] === '国民の休日',
    isset($parsed['2026-09-22']) ? $parsed['2026-09-22'] : '未取得');
check('BOM付きUTF-8でも解析できる',
    count(FR_JapaneseHolidays::parseOfficialCsv("\xEF\xBB\xBF" . $officialCsvUtf8)) === 7);

foreach (array('' => '空のCSVは拒否する', "見出しのみ\n" => '日付行の無いCSVは拒否する') as $content => $label) {
    $rejected = false;
    try {
        FR_JapaneseHolidays::parseOfficialCsv($content);
    } catch (Exception $e) {
        $rejected = true;
    }
    check($label, $rejected);
}

echo "\n7. 公表データの取り込み（マスタへの反映）\n";
$adb = PearDatabase::getInstance();
// 5. で一括登録した2035年を消し、変則的な年（誤った祝日＋会社休日）を作る
cleanupTestHolidays();
$adb->pquery("INSERT INTO vtiger_holidays (holiday_date, holiday_name, day_type, holiday_type)
    VALUES (?, ?, ?, 'national')", array('2035-01-01', '元日', FR_BusinessDay::DAY_TYPE_HOLIDAY));
$adb->pquery("INSERT INTO vtiger_holidays (holiday_date, holiday_name, day_type, holiday_type)
    VALUES (?, ?, ?, 'national')", array('2035-07-20', '海の日', FR_BusinessDay::DAY_TYPE_HOLIDAY));
$adb->pquery("INSERT INTO vtiger_holidays (holiday_date, holiday_name, day_type, holiday_type)
    VALUES (?, ?, ?, 'company')", array('2035-12-30', 'HOLIDAYTEST_会社休日', FR_BusinessDay::DAY_TYPE_HOLIDAY));
FR_BusinessDay::clearCache();

$imported = Settings_Holidays_Module_Model::importOfficialHolidays($parsed, 2035);
check('公表データに無い祝日を削除する（誤った 2035-07-20 海の日）',
    $imported['removed'] === 1 && !FR_BusinessDay::isHoliday('2035-07-20'),
    "削除={$imported['removed']}");
check('公表データの祝日を追加する', $imported['added'] === 3, "追加={$imported['added']}");
check('既に一致している祝日は更新しない', $imported['updated'] === 0, "更新={$imported['updated']}");
check('年を指定した場合はその年だけ対象にする', $imported['years'] === array(2035),
    implode(',', $imported['years']));
check('特例移動の祝日が登録される（2035-07-16 海の日）', FR_BusinessDay::isHoliday('2035-07-16'));
check('振替休日として登録される（2035-02-12）', FR_BusinessDay::isHoliday('2035-02-12'));
check('会社休日は変更されない（2035-12-30）', FR_BusinessDay::isHoliday('2035-12-30'));

$companyRow = $adb->pquery(
    "SELECT holiday_type, holiday_name FROM vtiger_holidays WHERE holiday_date = ?", array('2035-12-30'));
check('会社休日の区分・名称が保持される',
    $adb->query_result($companyRow, 0, 'holiday_type') === 'company'
    && decode_html($adb->query_result($companyRow, 0, 'holiday_name')) === 'HOLIDAYTEST_会社休日');

$again = Settings_Holidays_Module_Model::importOfficialHolidays($parsed, 2035);
check('再取り込みでは変化しない（冪等）',
    $again['added'] === 0 && $again['updated'] === 0 && $again['removed'] === 0,
    "追加={$again['added']} 更新={$again['updated']} 削除={$again['removed']}");

// 名称が変わった場合は更新する
$adb->pquery("UPDATE vtiger_holidays SET holiday_name = ? WHERE holiday_date = ?",
    array('海の日（旧称）', '2035-07-16'));
$renamed = Settings_Holidays_Module_Model::importOfficialHolidays($parsed, 2035);
check('名称が異なる場合は更新する', $renamed['updated'] === 1, "更新={$renamed['updated']}");

$notIncluded = false;
try {
    Settings_Holidays_Module_Model::importOfficialHolidays($parsed, 2040);
} catch (Exception $e) {
    $notIncluded = true;
}
check('公表データに含まれない年を指定した場合はエラー', $notIncluded);

$emptyRejected = false;
try {
    Settings_Holidays_Module_Model::importOfficialHolidays(array());
} catch (Exception $e) {
    $emptyRejected = true;
}
check('空のデータは取り込まない', $emptyRejected);

echo "\n8. 週休の設定（画面から変更）\n";
$originalWeekly = FR_BusinessDay::getWeeklyHolidays();

FR_BusinessDay::setWeeklyHolidays(array(0));
FR_BusinessDay::clearCache();
check('週休を日曜のみに変更できる', FR_BusinessDay::getWeeklyHolidays() === array(0),
    implode(',', FR_BusinessDay::getWeeklyHolidays()));
check('土曜が営業日になる（2026-08-08）', FR_BusinessDay::isBusinessDay('2026-08-08'));
check('日曜は休日のまま（2026-08-09）', FR_BusinessDay::isHoliday('2026-08-09'));

// キャッシュを消さずに読み直しても保存後の値が返る（保存時にキャッシュを更新している）
check('保存後はキャッシュを消さなくても反映される',
    FR_BusinessDay::setWeeklyHolidays(array(1, 2)) === array(1, 2)
    && FR_BusinessDay::getWeeklyHolidays() === array(1, 2),
    implode(',', FR_BusinessDay::getWeeklyHolidays()));

FR_BusinessDay::setWeeklyHolidays(array());
FR_BusinessDay::clearCache();
check('週休なしにできる', FR_BusinessDay::getWeeklyHolidays() === array());
check('週休なしでは日曜も営業日（2026-08-09）', FR_BusinessDay::isBusinessDay('2026-08-09'));
check('週休なしでもマスタの祝日は休日（2026-01-01）', FR_BusinessDay::isHoliday('2026-01-01'));

// 重複・範囲外・文字列は取り除いて昇順に整える
FR_BusinessDay::setWeeklyHolidays(array(6, 0, 6, 9, -1, '3', 'x'));
FR_BusinessDay::clearCache();
check('重複・範囲外の値を除いて昇順に保存する',
    FR_BusinessDay::getWeeklyHolidays() === array(0, 3, 6),
    implode(',', FR_BusinessDay::getWeeklyHolidays()));

// DBに保存されていること（別プロセスからも同じ値が読める）
$stored = $adb->pquery("SELECT value FROM vtiger_holiday_settings WHERE name = ?",
    array('weekly_holidays'));
check('設定テーブルに保存される', $adb->query_result($stored, 0, 'value') === '0,3,6',
    $adb->query_result($stored, 0, 'value'));

FR_BusinessDay::setWeeklyHolidays($originalWeekly);
FR_BusinessDay::clearCache();
check('元の設定に戻せる', FR_BusinessDay::getWeeklyHolidays() === $originalWeekly,
    implode(',', FR_BusinessDay::getWeeklyHolidays()));

cleanupTestHolidays();
check('テストデータを削除', count(FR_BusinessDay::getRegisteredDays('2035-01-01', '2035-12-31')) === 0);

echo "\n=== 結果 ===\n";
if (empty($failures)) {
    echo "すべて成功\n";
    exit(0);
}
echo count($failures) . "件失敗: " . implode(' / ', $failures) . "\n";
exit(1);
