<?php
/**
 * TS-03 スキャナ保存の入力期限 自動テスト
 *
 * 対応する仕様書: docs/tests/Documents/TS-03_入力期限.md
 *
 * Usage:
 *   php test/unit/documents/spec/spec_TS03_deadline.php
 */

require_once dirname(__FILE__) . '/bootstrap.php';
require_once 'include/utils/BusinessDay.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'modules/Settings/DocumentsCompliance/models/Module.php';

echo "=== TS-03 入力期限 ===\n";

$savedWeekly = FR_BusinessDay::getWeeklyHolidays();
$savedSettings = specBackupDocumentsSettings();
SpecRunner::addCleanup(function () use ($savedWeekly, $savedSettings) {
    specRestoreDocumentsSettings($savedSettings);
    FR_BusinessDay::setWeeklyHolidays($savedWeekly);
    specCleanHolidays();
    specCleanDocuments();
});

specCleanDocuments();
specCleanHolidays();
specSetWeeklyHolidays(array(0, 6));

/** 既定の設定に戻す */
function specDefaultDeadlineSettings() {
    specSetDocumentsSetting('input_deadline_policy', 'prompt');
    specSetDocumentsSetting('input_deadline_business_days', '7');
    specSetDocumentsSetting('input_deadline_cycle_months', '2');
    specSetDocumentsSetting('input_deadline_warning_days', '3');
}
specDefaultDeadlineSettings();

// ---------------------------------------------------------------- 4.1 設定
SpecRunner::section('4.1 設定の既定値（BV-5 / BV-6）');

$db = PearDatabase::getInstance();
$db->pquery("DELETE FROM vtiger_documents_settings WHERE name LIKE 'input_deadline%'", array());
Documents_DeadlineCalculator::clearCache();
$settings = Documents_DeadlineCalculator::getSettings();
SpecRunner::assertSame('TC-DL-001', '方針の既定は prompt', 'prompt', $settings['input_deadline_policy']);
SpecRunner::assertSame('TC-DL-001', '営業日数の既定は7', 7, $settings['input_deadline_business_days']);
SpecRunner::assertSame('TC-DL-001', 'サイクル月数の既定は2', 2, $settings['input_deadline_cycle_months']);
SpecRunner::assertSame('TC-DL-001', '警告日数の既定は3', 3, $settings['input_deadline_warning_days']);

specSetDocumentsSetting('input_deadline_business_days', '0');
SpecRunner::assertSame('TC-DL-002', '0 は既定値へフォールバック', 7, Documents_DeadlineCalculator::getBusinessDays());
specSetDocumentsSetting('input_deadline_business_days', 'abc');
SpecRunner::assertSame('TC-DL-003', '非数値は既定値へフォールバック', 7, Documents_DeadlineCalculator::getBusinessDays());
specSetDocumentsSetting('input_deadline_business_days', '1');
SpecRunner::assertSame('TC-DL-004', '1 は下限として有効', 1, Documents_DeadlineCalculator::getBusinessDays());
specSetDocumentsSetting('input_deadline_policy', 'unknown');
SpecRunner::assertSame('TC-DL-005', '未知の方針は prompt', 'prompt', Documents_DeadlineCalculator::getPolicy());

specDefaultDeadlineSettings();
Documents_DeadlineCalculator::saveSettings(array('input_deadline_business_days' => 5));
SpecRunner::assertSame('TC-DL-007', '保存後はキャッシュが破棄され新しい値になる',
    5, Documents_DeadlineCalculator::getBusinessDays());
Documents_DeadlineCalculator::saveSettings(array('unknown_setting' => 'x'));
$result = $db->pquery("SELECT name FROM vtiger_documents_settings WHERE name = ?", array('unknown_setting'));
SpecRunner::assertSame('TC-DL-008', '許可外の設定名は無視される', 0, $db->num_rows($result));
specDefaultDeadlineSettings();

// ---------------------------------------------------------------- 4.2 期限の計算
SpecRunner::section('4.2 期限の計算（DT-1 / BV-1〜BV-3）');

SpecRunner::assertSame('TC-DL-010', '2035-01-01 受領 → 7営業日後',
    '2035-01-10', Documents_DeadlineCalculator::calculate('2035-01-01'));
SpecRunner::assertSame('TC-DL-011', 'cycle は +2か月後を起算日にする',
    '2035-03-12', Documents_DeadlineCalculator::calculate('2035-01-01', 'cycle'));
SpecRunner::assertSame('TC-DL-012', '金曜受領は週休を2回跨ぐ',
    '2035-01-16', Documents_DeadlineCalculator::calculate('2035-01-05'));
SpecRunner::assertSame('TC-DL-013', '土曜受領でも翌営業日から数える',
    '2035-01-16', Documents_DeadlineCalculator::calculate('2035-01-06'));

specAddHoliday('2035-01-02', 'holiday');
SpecRunner::assertSame('TC-DL-014', 'マスタの休日の分だけ後ろにずれる',
    '2035-01-11', Documents_DeadlineCalculator::calculate('2035-01-01'));
specCleanHolidays();

specSetWeeklyHolidays(array(0));
SpecRunner::assertSame('TC-DL-015', '週休が日曜のみなら土曜も営業日',
    '2035-01-09', Documents_DeadlineCalculator::calculate('2035-01-01'));
specSetWeeklyHolidays(array(0, 6));

specSetDocumentsSetting('input_deadline_cycle_months', '1');
SpecRunner::assertSame('TC-DL-016', '1/31 + 1か月 は 2/28 に丸める（起算日）',
    '2035-02-28', FR_BusinessDay::addBusinessDays(
        date('Y-m-d', strtotime('2035-02-28')), 0));
$deadlineFromJan31 = Documents_DeadlineCalculator::calculate('2035-01-31', 'cycle');
SpecRunner::assertSame('TC-DL-016', '1/31 受領（cycle 1か月）の期限',
    FR_BusinessDay::addBusinessDays('2035-02-28', 7), $deadlineFromJan31);
specSetDocumentsSetting('input_deadline_cycle_months', '2');
SpecRunner::assertSame('TC-DL-017', '12/31 + 2か月 は閏年の 2/29 に丸める',
    FR_BusinessDay::addBusinessDays('2036-02-29', 7),
    Documents_DeadlineCalculator::calculate('2035-12-31', 'cycle'));

specSetDocumentsSetting('input_deadline_business_days', '1');
SpecRunner::assertSame('TC-DL-018', '1営業日設定', '2035-01-02', Documents_DeadlineCalculator::calculate('2035-01-01'));
specSetDocumentsSetting('input_deadline_business_days', '60');
SpecRunner::assertTrue('TC-DL-019', '60営業日設定でも日付が返る',
    is_string(Documents_DeadlineCalculator::calculate('2035-01-01')));
specDefaultDeadlineSettings();

SpecRunner::assertSame('TC-DL-020', '空は null', null, Documents_DeadlineCalculator::calculate(''));
SpecRunner::assertSame('TC-DL-020', '0000-00-00 は null', null, Documents_DeadlineCalculator::calculate('0000-00-00'));
SpecRunner::assertSame('TC-DL-020', 'null は null', null, Documents_DeadlineCalculator::calculate(null));
SpecRunner::assertThrows('TC-DL-021', "'abc' は例外",
    function () { return Documents_DeadlineCalculator::calculate('abc'); }, 'InvalidArgumentException');
SpecRunner::assertThrows('TC-DL-021', '2026-02-30 は例外（繰り上げない）',
    function () { return Documents_DeadlineCalculator::calculate('2026-02-30'); }, 'InvalidArgumentException');

specSetWeeklyHolidays(array(0, 1, 2, 3, 4, 5, 6));
SpecRunner::assertSame('TC-DL-022', '全曜日が週休なら null（例外にしない）',
    null, Documents_DeadlineCalculator::calculate('2035-01-01'));
specSetWeeklyHolidays(array(0, 6));

// ---------------------------------------------------------------- 4.3 期限状態
SpecRunner::section('4.3 期限状態（DT-2 / BV-4）');

SpecRunner::assertSame('TC-DL-030', '空は null', null, Documents_DeadlineCalculator::calculateStatus(''));
SpecRunner::assertSame('TC-DL-030', '0000-00-00 は null',
    null, Documents_DeadlineCalculator::calculateStatus('0000-00-00'));
SpecRunner::assertThrows('TC-DL-030b', "'abc' は例外",
    function () { return Documents_DeadlineCalculator::calculateStatus('abc'); }, 'InvalidArgumentException');
SpecRunner::assertSame('TC-DL-031', '期限 < 基準日 は overdue',
    'overdue', Documents_DeadlineCalculator::calculateStatus('2035-01-07', '2035-01-08'));
SpecRunner::assertSame('TC-DL-032', '残り3営業日は warning（境界）',
    'warning', Documents_DeadlineCalculator::calculateStatus('2035-01-10', '2035-01-08'));
SpecRunner::assertSame('TC-DL-033', '残り4営業日は within（境界）',
    'within', Documents_DeadlineCalculator::calculateStatus('2035-01-11', '2035-01-08'));
SpecRunner::assertSame('TC-DL-034', '当日は warning',
    'warning', Documents_DeadlineCalculator::calculateStatus('2035-01-08', '2035-01-08'));
SpecRunner::assertSame('TC-DL-035', '期限日が休日でも当日は overdue にしない（S-07b）',
    'warning', Documents_DeadlineCalculator::calculateStatus('2035-01-06', '2035-01-06'));
SpecRunner::assertSame('TC-DL-035b', '期限日の翌日は overdue',
    'overdue', Documents_DeadlineCalculator::calculateStatus('2035-01-06', '2035-01-07'));

specSetDocumentsSetting('input_deadline_warning_days', '1');
SpecRunner::assertSame('TC-DL-036', '警告1営業日なら残り2営業日は within',
    'within', Documents_DeadlineCalculator::calculateStatus('2035-01-09', '2035-01-08'));
specSetDocumentsSetting('input_deadline_warning_days', '60');
SpecRunner::assertSame('TC-DL-037', '警告60営業日ならほぼ warning',
    'warning', Documents_DeadlineCalculator::calculateStatus('2035-01-31', '2035-01-08'));
specDefaultDeadlineSettings();

// ---------------------------------------------------------------- 4.4 レコード反映
SpecRunner::section('4.4 レコードへの反映（DT-3）');

SpecRunner::assertSame('TC-DL-040', 'notesId=0 は空',
    array('input_deadline' => null, 'input_deadline_status' => null),
    Documents_DeadlineCalculator::recalculate(0));
SpecRunner::assertSame('TC-DL-041', '存在しないIDは空',
    array('input_deadline' => null, 'input_deadline_status' => null),
    Documents_DeadlineCalculator::recalculate(99999999));

$docId = specCreateDocument('DL_SCANNER');
specUpdateNotes($docId, array(
    'document_category' => 'invoice',
    'preservation_type' => 'scanner',
    'receipt_date' => '2035-01-01',
));
$result = Documents_DeadlineCalculator::recalculate($docId);
SpecRunner::assertSame('TC-DL-042', 'スキャナ保存＋受領日で期限が入る', '2035-01-10', $result['input_deadline']);
SpecRunner::assertTrue('TC-DL-042', '期限状態も設定される', $result['input_deadline_status'] !== null);
$stored = $db->query_result($db->pquery(
    "SELECT input_deadline FROM vtiger_notes WHERE notesid = ?", array($docId)), 0, 'input_deadline');
SpecRunner::assertSame('TC-DL-042', 'DBにも反映される', '2035-01-10', $stored);

$again = Documents_DeadlineCalculator::recalculate($docId);
SpecRunner::assertSame('TC-DL-043', '再実行しても同じ値（冪等）', $result, $again);

specUpdateNotes($docId, array('preservation_type' => 'electronic_transaction'));
$result = Documents_DeadlineCalculator::recalculate($docId);
SpecRunner::assertSame('TC-DL-044', '対象外になったら期限を消す', null, $result['input_deadline']);
SpecRunner::assertSame('TC-DL-044', '状態も消す', null, $result['input_deadline_status']);

specUpdateNotes($docId, array('preservation_type' => 'scanner', 'receipt_date' => null));
$result = Documents_DeadlineCalculator::recalculate($docId);
SpecRunner::assertSame('TC-DL-045', '受領日が無くなったら期限を消す', null, $result['input_deadline']);

specUpdateNotes($docId, array('receipt_date' => '9999-99-99'));
SpecRunner::assertNotThrows('TC-DL-063c', '不正な受領日でも例外にしない',
    function () use ($docId) { Documents_DeadlineCalculator::recalculate($docId); });

// ---------------------------------------------------------------- 4.5 一括更新
SpecRunner::section('4.5 一括更新（DT-4）');

$targetId = specCreateDocument('DL_BATCH');
specUpdateNotes($targetId, array(
    'document_category' => 'invoice',
    'preservation_type' => 'scanner',
    'receipt_date' => '2035-01-01',
));
Documents_DeadlineCalculator::recalculate($targetId);

$result = Documents_DeadlineCalculator::updateStatuses('2035-01-20');
SpecRunner::assertTrue('TC-DL-060', '期限を過ぎたら overdue に更新される', $result['updated'] >= 1,
    json_encode($result));
$status = $db->query_result($db->pquery(
    "SELECT input_deadline_status FROM vtiger_notes WHERE notesid = ?", array($targetId)),
    0, 'input_deadline_status');
SpecRunner::assertSame('TC-DL-060', '対象ドキュメントが overdue', 'overdue', $status);

$again = Documents_DeadlineCalculator::updateStatuses('2035-01-20');
SpecRunner::assertSame('TC-DL-061', '同じ基準日での再実行は更新0件', 0, $again['updated']);

Documents_DeadlineCalculator::updateStatuses('2035-01-02');
$status = $db->query_result($db->pquery(
    "SELECT input_deadline_status FROM vtiger_notes WHERE notesid = ?", array($targetId)),
    0, 'input_deadline_status');
SpecRunner::assertSame('TC-DL-064', '基準日を戻すと within に遷移', 'within', $status);
Documents_DeadlineCalculator::updateStatuses('2035-01-08');
$status = $db->query_result($db->pquery(
    "SELECT input_deadline_status FROM vtiger_notes WHERE notesid = ?", array($targetId)),
    0, 'input_deadline_status');
SpecRunner::assertSame('TC-DL-064', '期限間近では warning', 'warning', $status);

// 対象外（電子取引・削除済み）は checked に含まれない
$outId = specCreateDocument('DL_OUT');
specUpdateNotes($outId, array(
    'preservation_type' => 'electronic_transaction', 'input_deadline' => '2035-01-10'));
$before = Documents_DeadlineCalculator::updateStatuses('2035-01-20');
specUpdateNotes($outId, array('preservation_type' => 'scanner'));
$after = Documents_DeadlineCalculator::updateStatuses('2035-01-20');
SpecRunner::assertTrue('TC-DL-062', '電子取引は checked の対象外',
    $after['checked'] > $before['checked'],
    "before={$before['checked']} after={$after['checked']}");

// 不正な期限が混ざっても止まらない
$brokenId = specCreateDocument('DL_BROKEN');
specUpdateNotes($brokenId, array('preservation_type' => 'scanner', 'input_deadline' => '9999-99-99'));
SpecRunner::assertNotThrows('TC-DL-063b', '不正な期限が混ざっても一括更新が完走する',
    function () { Documents_DeadlineCalculator::updateStatuses('2035-01-20'); });

// ---------------------------------------------------------------- 4.6 一括再計算
SpecRunner::section('4.6 既存ドキュメントの一括再計算');

$result = Documents_DeadlineCalculator::recalculateAll();
SpecRunner::assertTrue('TC-DL-070', 'スキャナ保存＋受領日ありが対象', $result['checked'] >= 1,
    json_encode($result));

specSetDocumentsSetting('input_deadline_policy', 'cycle');
$changed = Documents_DeadlineCalculator::recalculateAll();
SpecRunner::assertTrue('TC-DL-071', '方針変更で期限が更新される', $changed['updated'] >= 1,
    json_encode($changed));
$again = Documents_DeadlineCalculator::recalculateAll();
SpecRunner::assertSame('TC-DL-072', '再実行では更新0件（冪等）', 0, $again['updated']);
specDefaultDeadlineSettings();

// ---------------------------------------------------------------- 保存経路
SpecRunner::section('保存経路からの自動計算（S-01 / S-14）');

$saveId = specCreateDocument('DL_SAVE', array(
    'document_category' => 'invoice',
    'preservation_type' => 'scanner',
    'receipt_date' => '2035-01-01',
));
$stored = $db->query_result($db->pquery(
    "SELECT input_deadline FROM vtiger_notes WHERE notesid = ?", array($saveId)), 0, 'input_deadline');
SpecRunner::assertSame('TC-DL-046', '保存時に期限が自動設定される', '2035-01-10', $stored);

specSetWeeklyHolidays(array(0, 1, 2, 3, 4, 5, 6));
SpecRunner::assertNotThrows('TC-DL-048', '期限が計算できなくても保存は成功する',
    function () { specCreateDocument('DL_NOCALC', array(
        'preservation_type' => 'scanner', 'receipt_date' => '2035-01-01')); });
specSetWeeklyHolidays(array(0, 6));

SpecRunner::cleanup();
exit(SpecRunner::summarize('TS-03'));
