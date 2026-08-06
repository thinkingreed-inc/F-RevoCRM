<?php
/**
 * 入力期限の自動計算テスト
 *
 * スキャナ保存の受領日から入力期限を計算し、期限状態を判定できることを検証する。
 * 営業日の判定は休祝日マスタ（vtiger_holidays）と週休の設定を使う。
 *
 * 検証内容:
 *   1. 設定の既定値
 *   2. 期限の計算（速やかに / 業務処理サイクル後速やかに）
 *   3. 期限状態の判定（期限内・期限間近・期限超過）
 *   4. レコードへの反映（保存経路・対象外になった場合の消去）
 *   5. 期限状態の一括更新（cron）
 *   6. 設定画面からの変更（方針・日数の保存と既存ドキュメントの再計算）
 *
 * Usage:
 *   php test_deadline.php
 */

chdir(dirname(__FILE__) . '/../../../');
// config.php 経由で読み込む（config.customize.php の設定を反映させるため）
require_once 'config.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
vimport('includes.runtime.Globals');
vimport('includes.runtime.LanguageHandler');
vimport('includes.runtime.BaseModel');
vimport('includes.runtime.Controller');
vimport('includes.http.Request');
require_once 'modules/Users/Users.php';
require_once 'modules/Users/models/Record.php';
require_once 'modules/Users/models/Module.php';
require_once 'modules/Documents/Documents.php';
require_once 'modules/Documents/models/Record.php';
require_once 'modules/Documents/models/Module.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'include/utils/BusinessDay.php';
require_once 'modules/Settings/Vtiger/models/Module.php';
require_once 'modules/Settings/Vtiger/models/Record.php';
require_once 'modules/Settings/Holidays/models/Module.php';
require_once 'modules/Settings/Holidays/models/Record.php';
require_once 'modules/Settings/DocumentsCompliance/models/Module.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/Webservices/Utils.php';

$adb = PearDatabase::getInstance();

// admin として実行
global $current_user;
$current_user = CRMEntity::getInstance('Users');
$current_user->id = 1;
$current_user->retrieve_entity_info(1, 'Users');
$current_user->column_fields = (array) $current_user->column_fields;
vglobal('current_user', $current_user);

$adminModel = new Users_Record_Model();
$adminModel->setData($current_user->column_fields);
$adminModel->setModule('Users');
$adminModel->setEntity($current_user);
foreach (get_object_vars($current_user) as $k => $v) {
    if (!is_object($v)) $adminModel->$k = $v;
}
Users_Record_Model::$currentUserModels[$current_user->id] = $adminModel;

$failures = array();
function check($label, $condition, $detail = '') {
    global $failures;
    echo ($condition ? "  [OK]   " : "  [NG]   ") . $label . ($detail !== '' ? " : $detail" : '') . "\n";
    if (!$condition) $failures[] = $label;
}

/**
 * テスト用ドキュメントを作成する（外部URL。ファイル操作を伴わない）
 */
function createTestDocument($titleSuffix) {
    $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
    $recordModel->set('mode', '');
    $recordModel->set('notes_title', 'DEADLINETEST_' . $titleSuffix);
    $recordModel->set('filelocationtype', 'E');
    $recordModel->set('filename', 'http://example.com/deadline-test');
    $recordModel->set('folderid', 1);
    $recordModel->set('filestatus', 1);
    $recordModel->set('assigned_user_id', 1);
    $recordModel->save();
    return $recordModel->getId();
}

/**
 * テストデータを削除する
 */
function cleanupTestDocuments() {
    $adb = PearDatabase::getInstance();
    $result = $adb->pquery(
        "SELECT notesid FROM vtiger_notes WHERE title LIKE ?", array('DEADLINETEST%'));
    for ($i = 0; $i < $adb->num_rows($result); $i++) {
        $notesId = (int) $adb->query_result($result, $i, 'notesid');
        $adb->pquery("DELETE FROM vtiger_notes_audit_log WHERE notesid = ?", array($notesId));
        $adb->pquery("DELETE FROM vtiger_notes_file_versions WHERE notesid = ?", array($notesId));
        $adb->pquery("DELETE FROM vtiger_notes WHERE notesid = ?", array($notesId));
        $adb->pquery("DELETE FROM vtiger_crmentity WHERE crmid = ?", array($notesId));
        $adb->pquery("DELETE FROM vtiger_senotesrel WHERE notesid = ?", array($notesId));
        $adb->pquery("DELETE FROM vtiger_modtracker_detail
            WHERE id IN (SELECT id FROM vtiger_modtracker_basic WHERE crmid = ?)", array($notesId));
        $adb->pquery("DELETE FROM vtiger_modtracker_basic WHERE crmid = ?", array($notesId));
    }
}

/**
 * 休祝日マスタのテストデータを削除する
 */
function cleanupTestHolidays() {
    $adb = PearDatabase::getInstance();
    $adb->pquery("DELETE FROM vtiger_holidays WHERE holiday_name LIKE ?", array('DEADLINETEST%'));
    FR_BusinessDay::clearCache();
}

echo "=== 入力期限の自動計算テスト ===\n";
cleanupTestDocuments();
cleanupTestHolidays();

echo "\n1. 設定の既定値\n";
check('方針は「速やかに」', Documents_DeadlineCalculator::getPolicy()
    === Documents_DeadlineCalculator::POLICY_PROMPT, Documents_DeadlineCalculator::getPolicy());
check('猶予は7営業日', Documents_DeadlineCalculator::getBusinessDays() === 7,
    (string) Documents_DeadlineCalculator::getBusinessDays());
check('業務処理サイクルは2か月', Documents_DeadlineCalculator::getCycleMonths() === 2,
    (string) Documents_DeadlineCalculator::getCycleMonths());
check('警告は3営業日前から', Documents_DeadlineCalculator::getWarningDays() === 3,
    (string) Documents_DeadlineCalculator::getWarningDays());

echo "\n2. 期限の計算\n";
// 2026-08-06(木) 受領 → 土日と山の日（8/11）を除く7営業日後は 2026-08-18(火)
$deadline = Documents_DeadlineCalculator::calculate('2026-08-06');
check('速やかに: 受領日から7営業日後（2026-08-06 → 2026-08-18）',
    $deadline === '2026-08-18', (string) $deadline);
check('起算日は含めない（受領日当日は期限にならない）', $deadline > '2026-08-06');
check('期限は営業日になる', FR_BusinessDay::isBusinessDay($deadline));

// 元日（2026-01-01）を挟むぶん期限が1日延びる
$newYear = Documents_DeadlineCalculator::calculate('2025-12-26');
check('祝日を挟むと期限が延びる（2025-12-26 → 2026-01-07）',
    $newYear === '2026-01-07', (string) $newYear);

// 会社休日を登録すると期限が延びる
$holiday = new Settings_Holidays_Record_Model();
$holiday->set('holiday_date', '2026-08-18');
$holiday->set('holiday_name', 'DEADLINETEST_会社休日');
$holiday->set('day_type', 'holiday');
$holiday->set('holiday_type', 'company');
$holidayId = $holiday->save();
FR_BusinessDay::clearCache();
check('会社休日を登録すると期限が翌営業日に繰り越される（2026-08-19）',
    Documents_DeadlineCalculator::calculate('2026-08-06') === '2026-08-19',
    (string) Documents_DeadlineCalculator::calculate('2026-08-06'));
Settings_Holidays_Module_Model::delete($holidayId);
FR_BusinessDay::clearCache();

// 業務処理サイクル（2か月）後に7営業日
$cycle = Documents_DeadlineCalculator::calculate('2026-08-06',
    Documents_DeadlineCalculator::POLICY_CYCLE);
check('サイクル後: 2か月後から7営業日後（2026-08-06 → 2026-10-16）',
    $cycle === '2026-10-16', (string) $cycle);
check('サイクル後の期限は「速やかに」より後になる', $cycle > $deadline);

// 月末は加算先の月末に丸める（12/31 + 2か月 = 2/28。あふれて 3/3 にならない）
$monthEnd = Documents_DeadlineCalculator::calculate('2026-12-31',
    Documents_DeadlineCalculator::POLICY_CYCLE);
check('月末をまたいでもあふれない（2026-12-31 + 2か月 = 2027-02-28 起算 → 2027-03-09）',
    $monthEnd === '2027-03-09', (string) $monthEnd);

foreach (array('', null, '0000-00-00', 'not-a-date') as $invalid) {
    check('不正な受領日は null（' . var_export($invalid, true) . '）',
        Documents_DeadlineCalculator::calculate($invalid) === null);
}

echo "\n3. 期限状態の判定\n";
check('期限を過ぎていれば期限超過',
    Documents_DeadlineCalculator::calculateStatus('2026-08-05', '2026-08-06')
    === Documents_DeadlineCalculator::STATUS_OVERDUE);
check('当日が期限なら期限間近',
    Documents_DeadlineCalculator::calculateStatus('2026-08-06', '2026-08-06')
    === Documents_DeadlineCalculator::STATUS_WARNING);
// 2026-08-06(木) 時点で 08-10(月) までは残り3営業日（6,7,10）→ 期限間近
check('残り3営業日は期限間近（2026-08-10）',
    Documents_DeadlineCalculator::calculateStatus('2026-08-10', '2026-08-06')
    === Documents_DeadlineCalculator::STATUS_WARNING);
// 8/11 は山の日のため、残り4営業日になるのは 8/12（6,7,10,12）
check('残り4営業日は期限内（2026-08-12）',
    Documents_DeadlineCalculator::calculateStatus('2026-08-12', '2026-08-06')
    === Documents_DeadlineCalculator::STATUS_WITHIN);
check('期限が無ければ null', Documents_DeadlineCalculator::calculateStatus(null) === null);

echo "\n4. レコードへの反映\n";
$recordId = createTestDocument('反映');
$adb->pquery("UPDATE vtiger_notes SET document_category = ?, preservation_type = ?, receipt_date = ?
    WHERE notesid = ?", array('invoice', 'scanner', '2026-08-06', $recordId));
$applied = Documents_DeadlineCalculator::recalculate($recordId);
check('受領日から期限が設定される', $applied['input_deadline'] === '2026-08-18',
    (string) $applied['input_deadline']);
check('期限状態も設定される', in_array($applied['input_deadline_status'],
    array('within', 'warning', 'overdue'), true), (string) $applied['input_deadline_status']);

$stored = $adb->pquery("SELECT input_deadline, input_deadline_status FROM vtiger_notes WHERE notesid = ?",
    array($recordId));
check('DBに保存される', $adb->query_result($stored, 0, 'input_deadline') === '2026-08-18',
    (string) $adb->query_result($stored, 0, 'input_deadline'));

// 電子取引に変更すると期限は消える（スキャナ保存だけが対象）
$adb->pquery("UPDATE vtiger_notes SET preservation_type = ? WHERE notesid = ?",
    array('electronic_transaction', $recordId));
$cleared = Documents_DeadlineCalculator::recalculate($recordId);
check('スキャナ保存でなくなると期限が消える', $cleared['input_deadline'] === null
    && $cleared['input_deadline_status'] === null);

// 保存経路（Documents_Record_Model::save）でも計算される
$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Documents');
$recordModel->set('mode', 'edit');
$recordModel->set('preservation_type', 'scanner');
$recordModel->set('receipt_date', '2026-08-06');
$recordModel->save();
$savedDeadline = $adb->query_result(
    $adb->pquery("SELECT input_deadline FROM vtiger_notes WHERE notesid = ?", array($recordId)),
    0, 'input_deadline');
check('保存すると期限が自動計算される', $savedDeadline === '2026-08-18', (string) $savedDeadline);

// 受領日を変えると期限も変わる
$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Documents');
$recordModel->set('mode', 'edit');
$recordModel->set('receipt_date', '2026-08-12');
$recordModel->save();
$changedDeadline = $adb->query_result(
    $adb->pquery("SELECT input_deadline FROM vtiger_notes WHERE notesid = ?", array($recordId)),
    0, 'input_deadline');
check('受領日を変更すると期限も変わる（2026-08-12 → 2026-08-21）',
    $changedDeadline === '2026-08-21', (string) $changedDeadline);

echo "\n5. 期限状態の一括更新（cron）\n";
// 期限を過去にしておき、状態が期限超過へ更新されることを確認する
$adb->pquery("UPDATE vtiger_notes SET input_deadline = ?, input_deadline_status = ? WHERE notesid = ?",
    array('2026-08-05', 'within', $recordId));
$batch = Documents_DeadlineCalculator::updateStatuses('2026-08-06');
check('対象件数を数える', $batch['checked'] >= 1, "checked={$batch['checked']}");
check('状態が更新される', $batch['updated'] >= 1, "updated={$batch['updated']}");
$batchStatus = $adb->query_result(
    $adb->pquery("SELECT input_deadline_status FROM vtiger_notes WHERE notesid = ?", array($recordId)),
    0, 'input_deadline_status');
check('期限超過になる', $batchStatus === 'overdue', (string) $batchStatus);

$again = Documents_DeadlineCalculator::updateStatuses('2026-08-06');
check('再実行では更新しない（変化がある行のみ更新）', $again['updated'] === 0,
    "updated={$again['updated']}");

echo "\n6. 設定画面からの変更\n";
$originalSettings = Documents_DeadlineCalculator::getSettings();

// 方針を「業務処理サイクル後速やかに」に変更すると期限が変わる
Settings_DocumentsCompliance_Module_Model::saveSettings(array(
    'policy' => Documents_DeadlineCalculator::POLICY_CYCLE,
    'business_days' => 7, 'cycle_months' => 2, 'warning_days' => 3));
check('方針を保存できる',
    Documents_DeadlineCalculator::getPolicy() === Documents_DeadlineCalculator::POLICY_CYCLE,
    Documents_DeadlineCalculator::getPolicy());
check('保存した方針で計算される（2026-08-06 → 2026-10-16）',
    Documents_DeadlineCalculator::calculate('2026-08-06') === '2026-10-16',
    (string) Documents_DeadlineCalculator::calculate('2026-08-06'));

// 営業日数・サイクルの変更も反映される
Settings_DocumentsCompliance_Module_Model::saveSettings(array(
    'policy' => Documents_DeadlineCalculator::POLICY_PROMPT,
    'business_days' => 3, 'cycle_months' => 1, 'warning_days' => 1));
check('営業日数の変更が反映される（2026-08-06 → 3営業日後 2026-08-12）',
    Documents_DeadlineCalculator::calculate('2026-08-06') === '2026-08-12',
    (string) Documents_DeadlineCalculator::calculate('2026-08-06'));
// 残り営業日は当日と期限日を含めて数えるため、警告日数1では当日期限のみ「期限間近」
check('警告日数の変更が反映される（当日期限のみ期限間近）',
    Documents_DeadlineCalculator::calculateStatus('2026-08-06', '2026-08-06')
    === Documents_DeadlineCalculator::STATUS_WARNING
    && Documents_DeadlineCalculator::calculateStatus('2026-08-07', '2026-08-06')
    === Documents_DeadlineCalculator::STATUS_WITHIN);

// 不正な値は保存しない
$invalidCases = array(
    array('policy' => 'unknown', 'business_days' => 7, 'cycle_months' => 2, 'warning_days' => 3),
    array('policy' => 'prompt', 'business_days' => 0, 'cycle_months' => 2, 'warning_days' => 3),
    array('policy' => 'prompt', 'business_days' => 999, 'cycle_months' => 2, 'warning_days' => 3),
    array('policy' => 'prompt', 'business_days' => 7, 'cycle_months' => 99, 'warning_days' => 3),
    array('policy' => 'prompt', 'business_days' => 7, 'cycle_months' => 2, 'warning_days' => 'x'),
);
$rejected = 0;
foreach ($invalidCases as $case) {
    try {
        Settings_DocumentsCompliance_Module_Model::saveSettings($case);
    } catch (Exception $e) {
        $rejected++;
    }
}
check('不正な値は保存を拒否する', $rejected === count($invalidCases), "{$rejected}/" . count($invalidCases));
check('拒否された場合は設定が変わらない',
    Documents_DeadlineCalculator::getBusinessDays() === 3,
    (string) Documents_DeadlineCalculator::getBusinessDays());

// 設定変更後の再計算（既存ドキュメントへの反映）
$recalcId = createTestDocument('再計算');
$adb->pquery("UPDATE vtiger_notes SET document_category = ?, preservation_type = ?, receipt_date = ?,
    input_deadline = ?, input_deadline_status = ? WHERE notesid = ?",
    array('invoice', 'scanner', '2026-08-06', '2026-08-18', 'within', $recalcId));
$recalcResult = Settings_DocumentsCompliance_Module_Model::recalculateAll();
check('再計算で対象件数を数える', $recalcResult['checked'] >= 1, "checked={$recalcResult['checked']}");
check('再計算で期限が変わった件数を数える', $recalcResult['updated'] >= 1, "updated={$recalcResult['updated']}");
$recalcDeadline = $adb->query_result(
    $adb->pquery("SELECT input_deadline FROM vtiger_notes WHERE notesid = ?", array($recalcId)),
    0, 'input_deadline');
check('既存ドキュメントの期限が現在の設定で計算される（2026-08-12）',
    $recalcDeadline === '2026-08-12', (string) $recalcDeadline);

$noChange = Settings_DocumentsCompliance_Module_Model::recalculateAll();
check('再実行では変更なし', $noChange['updated'] === 0, "updated={$noChange['updated']}");

// 計算例（設定画面の確認用）
$example = Settings_DocumentsCompliance_Module_Model::getExample('2026-08-06');
check('計算例を返す', $example['receipt_date'] === '2026-08-06'
    && $example['input_deadline'] === '2026-08-12', json_encode($example));

// 設定を元に戻す
Settings_DocumentsCompliance_Module_Model::saveSettings(array(
    'policy' => $originalSettings[Documents_DeadlineCalculator::SETTING_POLICY],
    'business_days' => $originalSettings[Documents_DeadlineCalculator::SETTING_BUSINESS_DAYS],
    'cycle_months' => $originalSettings[Documents_DeadlineCalculator::SETTING_CYCLE_MONTHS],
    'warning_days' => $originalSettings[Documents_DeadlineCalculator::SETTING_WARNING_DAYS]));
check('設定を元に戻せる', Documents_DeadlineCalculator::getSettings() === $originalSettings,
    json_encode(Documents_DeadlineCalculator::getSettings()));

cleanupTestDocuments();
cleanupTestHolidays();
check('テストデータを削除',
    $adb->num_rows($adb->pquery("SELECT notesid FROM vtiger_notes WHERE title LIKE ?",
        array('DEADLINETEST%'))) === 0);

echo "\n=== 結果 ===\n";
if (empty($failures)) {
    echo "すべて成功\n";
    exit(0);
}
echo count($failures) . "件失敗: " . implode(' / ', $failures) . "\n";
exit(1);
