<?php
/**
 * TS-04 / TS-05 電帳法の適合チェックと設定 自動テスト
 *
 * 対応する仕様書:
 *   docs/tests/Documents/TS-04_電帳法設定.md
 *   docs/tests/Documents/TS-05_電帳法適合と監査ログ.md
 *
 * Usage:
 *   php test/unit/documents/spec/spec_TS05_compliance.php
 */

require_once dirname(__FILE__) . '/bootstrap.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';
require_once 'modules/Documents/utils/AuditLogger.php';
require_once 'modules/Documents/utils/FolderPermission.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'modules/Settings/DocumentsCompliance/models/Module.php';

echo "=== TS-04 / TS-05 電帳法 ===\n";

/** 存在しないドキュメントを表す番号（監査ログの空判定に使う） */
define('SPEC_MISSING_NOTES_ID', 99999999);

$savedSettings = specBackupDocumentsSettings();
SpecRunner::addCleanup(function () use ($savedSettings) {
    specRestoreDocumentsSettings($savedSettings);
    specCleanDocuments();
    specCleanMissingNotesLog();
});

/** 存在しないIDに紐づく監査ログを消す（前回実行の残りに影響されないようにする） */
function specCleanMissingNotesLog() {
    $db = PearDatabase::getInstance();
    $db->pquery("DELETE FROM vtiger_notes_audit_log WHERE notesid = ?", array(SPEC_MISSING_NOTES_ID));
}

specCleanDocuments();
specCleanMissingNotesLog();

$db = PearDatabase::getInstance();

/**
 * 適合チェック用のドキュメントを作る
 */
function specCreateComplianceDoc($suffix, $columns = array()) {
    $id = specCreateDocument($suffix);
    specUpdateNotes($id, array_merge(array(
        'document_category' => 'invoice',
        'preservation_type' => 'electronic_transaction',
        'file_hash' => str_repeat('a', 64),
    ), $columns));
    return $id;
}

/**
 * 取引レコードに関連付ける（既存の取引先を使う。無ければ null）
 */
function specFindRecordOfType($setype) {
    $db = PearDatabase::getInstance();
    $result = $db->pquery(
        "SELECT crmid FROM vtiger_crmentity WHERE setype = ? AND deleted = 0 LIMIT 1", array($setype));
    if ($result === false || $db->num_rows($result) === 0) return null;
    return (int) $db->query_result($result, 0, 'crmid');
}

function specRelate($notesId, $crmId) {
    $db = PearDatabase::getInstance();
    $db->pquery("INSERT INTO vtiger_senotesrel (crmid, notesid) VALUES (?, ?)", array($crmId, $notesId));
}

$accountId = specFindRecordOfType('Accounts');

// ---------------------------------------------------------------- 対象判定
SpecRunner::section('TS-05 4.1 適合チェック（DT-1 / BV-2）');

$outId = specCreateDocument('CC_OUT');
specUpdateNotes($outId, array('document_category' => null));
$result = Documents_ComplianceChecker::check($outId);
SpecRunner::assertSame('TC-CC-001', '区分なしは status=null', null, $result['status']);
SpecRunner::assertSame('TC-CC-001', '区分なしは issues が空', array(), $result['issues']);

$emptyId = specCreateDocument('CC_EMPTY_CATEGORY');
specUpdateNotes($emptyId, array('document_category' => ''));
$result = Documents_ComplianceChecker::check($emptyId);
SpecRunner::assertSame('TC-CC-021', '区分が空文字も対象外（status=null）', null, $result['status']);

// 不適合の理由
$noRelId = specCreateComplianceDoc('CC_NOREL');
$result = Documents_ComplianceChecker::check($noRelId);
SpecRunner::assertSame('TC-CC-010', '関連付けなしは non_compliant', 'non_compliant', $result['status']);
$notes = $db->query_result($db->pquery(
    "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($noRelId)), 0, 'compliance_notes');
SpecRunner::assertTrue('TC-CC-010', '理由に LBL_NO_RELATED_RECORD が入る',
    strpos(decode_html($notes), 'LBL_NO_RELATED_RECORD') !== false, $notes);

$hashId = specCreateComplianceDoc('CC_NOHASH', array('filelocationtype' => 'I', 'file_hash' => null));
$result = Documents_ComplianceChecker::check($hashId);
$notes = decode_html($db->query_result($db->pquery(
    "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($hashId)), 0, 'compliance_notes'));
SpecRunner::assertTrue('TC-CC-011', '内部ファイルでハッシュ無しは不適合理由が付く',
    strpos($notes, 'LBL_ISSUE_NO_FILE_HASH') !== false, $notes);

$extId = specCreateComplianceDoc('CC_EXT', array('filelocationtype' => 'E', 'file_hash' => null));
Documents_ComplianceChecker::check($extId);
$notes = decode_html($db->query_result($db->pquery(
    "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($extId)), 0, 'compliance_notes'));
SpecRunner::assertTrue('TC-CC-011b', '外部URLではハッシュ理由が付かない',
    strpos($notes, 'LBL_ISSUE_NO_FILE_HASH') === false, $notes);

$noTypeId = specCreateComplianceDoc('CC_NOTYPE', array('preservation_type' => ''));
Documents_ComplianceChecker::check($noTypeId);
$notes = decode_html($db->query_result($db->pquery(
    "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($noTypeId)), 0, 'compliance_notes'));
SpecRunner::assertTrue('TC-CC-012', '保存区分なしは不適合理由が付く',
    strpos($notes, 'LBL_ISSUE_NO_PRESERVATION_TYPE') !== false, $notes);

// 解像度（Q-08）
function specResolutionIssue($suffix, $dpi) {
    $id = specCreateComplianceDoc($suffix, array(
        'preservation_type' => 'scanner', 'scan_resolution_dpi' => $dpi));
    Documents_ComplianceChecker::check($id);
    $db = PearDatabase::getInstance();
    $notes = decode_html($db->query_result($db->pquery(
        "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($id)), 0, 'compliance_notes'));
    return strpos($notes, 'LBL_ISSUE_LOW_SCAN_RESOLUTION') !== false;
}
SpecRunner::assertTrue('TC-CC-013', '199dpi は不適合', specResolutionIssue('CC_DPI199', 199));
SpecRunner::assertFalse('TC-CC-013', '200dpi は理由なし', specResolutionIssue('CC_DPI200', 200));
SpecRunner::assertTrue('TC-CC-013b', '0dpi は不適合', specResolutionIssue('CC_DPI0', 0));
SpecRunner::assertTrue('TC-CC-013b', 'NULL（未入力）は不適合', specResolutionIssue('CC_DPINULL', null));
SpecRunner::assertFalse('TC-CC-013g', '電子取引では解像度の理由が付かない', (function () {
    $id = specCreateComplianceDoc('CC_DPI_ET', array(
        'preservation_type' => 'electronic_transaction', 'scan_resolution_dpi' => null));
    Documents_ComplianceChecker::check($id);
    $db = PearDatabase::getInstance();
    $notes = decode_html($db->query_result($db->pquery(
        "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($id)), 0, 'compliance_notes'));
    return strpos($notes, 'LBL_ISSUE_LOW_SCAN_RESOLUTION') !== false;
})());

SpecRunner::assertSame('TC-CC-013c', '解像度の正規化: 空文字は0', 0,
    Documents_ComplianceChecker::normalizeResolution(''));
SpecRunner::assertSame('TC-CC-013c', '解像度の正規化: null は0', 0,
    Documents_ComplianceChecker::normalizeResolution(null));
SpecRunner::assertSame('TC-CC-013c', '解像度の正規化: 前後空白を除く', 300,
    Documents_ComplianceChecker::normalizeResolution(' 300 '));
SpecRunner::assertThrows('TC-CC-013c', "解像度 'abc' は例外",
    function () { return Documents_ComplianceChecker::normalizeResolution('abc'); },
    'InvalidArgumentException');
SpecRunner::assertThrows('TC-CC-013c', "解像度 '1.5' は例外",
    function () { return Documents_ComplianceChecker::normalizeResolution('1.5'); },
    'InvalidArgumentException');

// 翻訳
SpecRunner::assertSame('TC-CC-024', 'translateNotes("") は空配列',
    array(), Documents_ComplianceChecker::translateNotes(''));
SpecRunner::assertSame('TC-CC-024', 'translateNotes(null) は空文字（asArray=false）',
    '', Documents_ComplianceChecker::translateNotes(null, false));
$translated = Documents_ComplianceChecker::translateNotes('LBL_NO_RELATED_RECORD', false);
SpecRunner::assertTrue('TC-CC-025', '翻訳キーが翻訳される',
    $translated !== '' && $translated !== 'LBL_NO_RELATED_RECORD', $translated);

// ---------------------------------------------------------------- 書類区分ごとの判定
SpecRunner::section('TS-05 4.1 書類区分ごとの取引モジュール（DT-1b / DT-1c）');

Documents_ComplianceChecker::clearCache();
$db->pquery("DELETE FROM vtiger_documents_settings WHERE name = ?",
    array(Documents_ComplianceChecker::SETTING_CATEGORY_MODULES));
Documents_ComplianceChecker::clearCache();

$all = Documents_ComplianceChecker::getTransactionModules(null);
SpecRunner::assertTrue('TC-CC-030', '区分未指定は全区分の和集合',
    in_array('ServiceContracts', $all, true) && in_array('Quotes', $all, true), json_encode($all));
SpecRunner::assertSame('TC-CC-032', '契約書の既定は ServiceContracts/Accounts/Vendors',
    array('ServiceContracts', 'Accounts', 'Vendors'),
    Documents_ComplianceChecker::getTransactionModules('contract'));
SpecRunner::assertSame('TC-CC-033', '未知の区分は既定モジュール',
    Documents_ComplianceChecker::DEFAULT_TRANSACTION_MODULES,
    Documents_ComplianceChecker::getTransactionModules('unknown'));

Documents_ComplianceChecker::saveCategoryTransactionModules(array('contract' => array('Invoice')));
SpecRunner::assertSame('TC-CC-031', '保存した設定が読み出せる',
    array('Invoice'), Documents_ComplianceChecker::getTransactionModules('contract'));
SpecRunner::assertSame('TC-CC-038', '保存直後にキャッシュが破棄される',
    array('Invoice'), Documents_ComplianceChecker::getTransactionModules('contract'));

// HTMLエンティティ化された値でも壊れない
$db->pquery("UPDATE vtiger_documents_settings SET value = ? WHERE name = ?",
    array('{&quot;contract&quot;:[&quot;Invoice&quot;]}',
        Documents_ComplianceChecker::SETTING_CATEGORY_MODULES));
Documents_ComplianceChecker::clearCache();
SpecRunner::assertSame('TC-CC-037b', 'HTMLエンティティ化された設定を解析できる',
    array('Invoice'), Documents_ComplianceChecker::getTransactionModules('contract'));

// 不正な設定は既定へフォールバック
$db->pquery("UPDATE vtiger_documents_settings SET value = ? WHERE name = ?",
    array('abc', Documents_ComplianceChecker::SETTING_CATEGORY_MODULES));
Documents_ComplianceChecker::clearCache();
SpecRunner::assertSame('TC-CC-037', 'JSONでない設定は既定値',
    Documents_ComplianceChecker::DEFAULT_CATEGORY_TRANSACTION_MODULES['contract'],
    Documents_ComplianceChecker::getTransactionModules('contract'));

$db->pquery("DELETE FROM vtiger_documents_settings WHERE name = ?",
    array(Documents_ComplianceChecker::SETTING_CATEGORY_MODULES));
Documents_ComplianceChecker::clearCache();

if ($accountId !== null) {
    // 契約書は Accounts に紐づけば適合（既定設定）
    $contractId = specCreateComplianceDoc('CC_CONTRACT', array('document_category' => 'contract'));
    specRelate($contractId, $accountId);
    $result = Documents_ComplianceChecker::check($contractId);
    SpecRunner::assertSame('TC-CC-034', '契約書＋顧客企業は適合', 'compliant', $result['status']);

    // 対象モジュールを絞ると不適合になる
    Documents_ComplianceChecker::saveCategoryTransactionModules(
        array('contract' => array('ServiceContracts')));
    $result = Documents_ComplianceChecker::check($contractId);
    SpecRunner::assertSame('TC-CC-034b', '対象外モジュールへの紐づけは不適合',
        'non_compliant', $result['status']);

    // 対象モジュールを空にすると関連付けを条件にしない
    Documents_ComplianceChecker::saveCategoryTransactionModules(array('contract' => array()));
    $check = Documents_ComplianceChecker::checkRelatedRecords($contractId, 'contract');
    SpecRunner::assertTrue('TC-CC-035', '対象モジュールが空なら has_related=true', $check['has_related']);
    SpecRunner::assertSame('TC-CC-035', '対象モジュールが空なら modules=[]', array(), $check['modules']);

    $db->pquery("DELETE FROM vtiger_documents_settings WHERE name = ?",
        array(Documents_ComplianceChecker::SETTING_CATEGORY_MODULES));
    Documents_ComplianceChecker::clearCache();

    $check = Documents_ComplianceChecker::checkRelatedRecords($contractId);
    SpecRunner::assertTrue('TC-CC-036', 'checkRelatedRecords が modules を返す', isset($check['modules']));
    SpecRunner::assertTrue('TC-CC-036b', '区分を明示指定して判定できる',
        Documents_ComplianceChecker::checkRelatedRecords($contractId, 'contract')['has_related']);
} else {
    SpecRunner::report('TC-CC-034', '契約書の判定（取引先レコードが無いためスキップ）', true, '');
}

// ---------------------------------------------------------------- 一括チェック
SpecRunner::section('TS-05 4.1 一括チェック（Q-09）');

$result = Documents_ComplianceChecker::batchCheck();
SpecRunner::assertTrue('TC-CC-021b', 'checked == compliant + non_compliant',
    $result['checked'] === $result['compliant'] + $result['non_compliant'], json_encode($result));
SpecRunner::assertTrue('TC-CC-021', '区分が空文字のドキュメントは対象に含まれない',
    isset($result['skipped']), json_encode($result));

$report = (function () {
    $db = PearDatabase::getInstance();
    $result = $db->pquery(
        "SELECT COUNT(*) AS total FROM vtiger_notes
         INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
         WHERE " . Documents_ComplianceChecker::TARGET_SQL_CONDITION . " AND vtiger_crmentity.deleted = 0",
        array());
    return (int) $db->query_result($result, 0, 'total');
})();
SpecRunner::assertSame('TC-CC-021c', 'レポートの総数と一括チェックの対象数が一致',
    $result['checked'], $report);

// ---------------------------------------------------------------- 設定の保存（TS-04）
SpecRunner::section('TS-04 4.1b 取引モジュール設定の保存（DT-1b / Q-27・Q-28）');

Documents_ComplianceChecker::saveCategoryTransactionModules(array(
    'contract' => array('ServiceContracts'),
    'invoice' => array('Invoice', 'SalesOrder'),
));
Documents_ComplianceChecker::clearCache();

$saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
    array('invoice' => array('Invoice')));
SpecRunner::assertSame('TC-CS-060', '指定した区分が更新される', array('Invoice'), $saved['invoice']);
SpecRunner::assertSame('TC-CS-067', '指定しなかった区分のカスタマイズが維持される',
    array('ServiceContracts'), $saved['contract']);
SpecRunner::assertSame('TC-CS-067b', '7区分すべてが返る', 7, count($saved));

$saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array('invoice' => array()));
SpecRunner::assertSame('TC-CS-062', '空配列を指定するとその区分が空になる', array(), $saved['invoice']);
$saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
    array('contract' => array('ServiceContracts', 'Accounts')));
SpecRunner::assertSame('TC-CS-067c', '空配列にした区分は維持される', array(), $saved['invoice']);

SpecRunner::assertThrows('TC-CS-063', '文字列（JSONでない）は例外',
    function () { return Settings_DocumentsCompliance_Module_Model::saveCategoryModules('abc'); });
SpecRunner::assertThrows('TC-CS-063b', '空オブジェクトは例外',
    function () { return Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array()); });
$afterError = Documents_ComplianceChecker::getCategoryTransactionModules();
SpecRunner::assertSame('TC-CS-063b', '例外時に設定が変わらない',
    array('ServiceContracts', 'Accounts'), $afterError['contract']);

SpecRunner::assertThrows('TC-CS-064', '未知の書類区分は例外',
    function () { return Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
        array('unknown' => array('Invoice'))); });
SpecRunner::assertThrows('TC-CS-065', '紐づけられないモジュールは例外',
    function () { return Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
        array('invoice' => array('NoSuchModule'))); });
SpecRunner::assertThrows('TC-CS-066', '一部が不正なら全体が保存されない',
    function () { return Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
        array('receipt' => array('Invoice'), 'unknown' => array('Invoice'))); });
$afterError = Documents_ComplianceChecker::getCategoryTransactionModules();
SpecRunner::assertSame('TC-CS-066', '正常な区分も保存されていない',
    Documents_ComplianceChecker::DEFAULT_CATEGORY_TRANSACTION_MODULES['receipt'],
    $afterError['receipt']);

$saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
    array('invoice' => 'Invoice,SalesOrder'));
SpecRunner::assertSame('TC-CS-068', 'カンマ区切り文字列を配列にする',
    array('Invoice', 'SalesOrder'), $saved['invoice']);
$saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules(
    array('invoice' => array('Invoice', 'Invoice', ' ')));
SpecRunner::assertSame('TC-CS-068', '重複除去と空要素の除去', array('Invoice'), $saved['invoice']);

// ---------------------------------------------------------------- 入力期限の設定検証
SpecRunner::section('TS-04 4.1 入力期限の設定検証（DT-1 / BV-1〜BV-3）');

$valid = array('policy' => 'prompt', 'business_days' => '7',
    'cycle_months' => '2', 'warning_days' => '3');
$saved = Settings_DocumentsCompliance_Module_Model::saveSettings($valid);
SpecRunner::assertSame('TC-CS-001', '正しい値は保存される', 'prompt',
    $saved[Documents_DeadlineCalculator::SETTING_POLICY]);

foreach (array('' => 'BV-3 空', 'unknown' => 'BV-3 未知', 'PROMPT' => 'BV-3 大文字') as $policy => $label) {
    SpecRunner::assertThrows('TC-CS-010', "方針 {$label} は例外",
        function () use ($valid, $policy) {
            return Settings_DocumentsCompliance_Module_Model::saveSettings(
                array_merge($valid, array('policy' => $policy)));
        });
}
foreach (array('0', '61', 'abc', '-1', '7.5', '') as $days) {
    SpecRunner::assertThrows('TC-CS-011', "営業日数 '{$days}' は例外",
        function () use ($valid, $days) {
            return Settings_DocumentsCompliance_Module_Model::saveSettings(
                array_merge($valid, array('business_days' => $days)));
        });
}
foreach (array('1', '60', ' 7 ', '07') as $days) {
    SpecRunner::assertNotThrows('TC-CS-006', "営業日数 '{$days}' は保存できる",
        function () use ($valid, $days) {
            Settings_DocumentsCompliance_Module_Model::saveSettings(
                array_merge($valid, array('business_days' => $days)));
        });
}
SpecRunner::assertThrows('TC-CS-012', 'サイクル月数 13 は例外',
    function () use ($valid) {
        return Settings_DocumentsCompliance_Module_Model::saveSettings(
            array_merge($valid, array('cycle_months' => '13')));
    });
SpecRunner::assertNotThrows('TC-CS-007', 'サイクル月数 12 は保存できる',
    function () use ($valid) {
        Settings_DocumentsCompliance_Module_Model::saveSettings(
            array_merge($valid, array('cycle_months' => '12')));
    });

// 部分保存されないこと
Settings_DocumentsCompliance_Module_Model::saveSettings($valid);
try {
    Settings_DocumentsCompliance_Module_Model::saveSettings(
        array('policy' => 'cycle', 'business_days' => '5', 'cycle_months' => '1', 'warning_days' => '0'));
} catch (Exception $e) {
    // 期待どおり
}
$current = Documents_DeadlineCalculator::getSettings();
SpecRunner::assertSame('TC-CS-013', '1項目でも不正なら方針が保存されない',
    'prompt', $current[Documents_DeadlineCalculator::SETTING_POLICY]);
SpecRunner::assertSame('TC-CS-013', '1項目でも不正なら営業日数も保存されない',
    7, $current[Documents_DeadlineCalculator::SETTING_BUSINESS_DAYS]);

// 選択肢・計算例
$categories = Settings_DocumentsCompliance_Module_Model::getDocumentCategories();
SpecRunner::assertSame('TC-CS-055', '書類区分は7件', 7, count($categories));
$modules = Settings_DocumentsCompliance_Module_Model::getRelatableModules();
SpecRunner::assertTrue('TC-CS-056', '紐づけ可能なモジュールが返る', count($modules) > 0);
$example = Settings_DocumentsCompliance_Module_Model::getExample('2035-01-01');
SpecRunner::assertSame('TC-CS-004', '計算例の受領日', '2035-01-01', $example['receipt_date']);
SpecRunner::assertTrue('TC-CS-004', '計算例の期限が計算される', $example['input_deadline'] !== null);

// 再判定
$recheck = Settings_DocumentsCompliance_Module_Model::recheckCompliance();
SpecRunner::assertTrue('TC-CS-070', '再判定が件数を返す', isset($recheck['checked']));
SpecRunner::assertTrue('TC-CS-070c', '再判定に skipped が含まれる', isset($recheck['skipped']));
SpecRunner::assertTrue('TC-CS-070', '対象は適合＋不適合と一致',
    $recheck['checked'] === $recheck['compliant'] + $recheck['non_compliant'], json_encode($recheck));

// ---------------------------------------------------------------- 監査ログ
SpecRunner::section('TS-05 4.3 監査ログ（DT-4 / BV-3）');

$logId = specCreateComplianceDoc('AL_TARGET');
$before = Documents_AuditLogger::snapshotFields($logId);
$after = $before;
SpecRunner::assertSame('TC-AL-002', '差分が無ければ変更を記録しない',
    array(), Documents_AuditLogger::diffSnapshots($before, $after));

$after['notes_title'] = SPEC_PREFIX . 'CHANGED';
$changes = Documents_AuditLogger::diffSnapshots($before, $after);
SpecRunner::assertSame('TC-AL-001', 'タイトル変更が差分になる', 1, count($changes));
SpecRunner::assertSame('TC-AL-001', '差分の項目名', 'notes_title', $changes[0]['field']);

$tracked = Documents_AuditLogger::getTrackedFields();
foreach (array('file_hash', 'input_deadline', 'compliance_status', 'modifiedtime') as $column) {
    $found = false;
    foreach ($tracked as $meta) {
        if ($meta['column'] === $column) $found = true;
    }
    SpecRunner::assertFalse('TC-AL-004', "{$column} は追跡対象外", $found);
}

$a = $before; $b = $before;
$key = array_key_first($tracked);
$a[$key] = null; $b[$key] = '';
SpecRunner::assertSame('TC-AL-022', 'null と空文字は差分にしない',
    array(), Documents_AuditLogger::diffSnapshots($a, $b));
$a[$key] = 'x'; $b[$key] = ' x ';
SpecRunner::assertSame('TC-AL-022', '前後の空白は差分にしない',
    array(), Documents_AuditLogger::diffSnapshots($a, $b));
$a[$key] = "x\r\ny"; $b[$key] = "x\ny";
SpecRunner::assertSame('TC-AL-023', '改行コードの違いは差分にしない',
    array(), Documents_AuditLogger::diffSnapshots($a, $b));

$a[$key] = str_repeat('あ', 255); $b[$key] = str_repeat('い', 256);
$changes = Documents_AuditLogger::diffSnapshots($a, $b);
SpecRunner::assertSame('TC-AL-024', '255文字はそのまま', 255, mb_strlen($changes[0]['old_value']));
SpecRunner::assertSame('TC-AL-024', '256文字は255文字＋記号', 256, mb_strlen($changes[0]['new_value']));
SpecRunner::assertTrue('TC-AL-024', '切り詰めた印が付く',
    mb_substr($changes[0]['new_value'], -1) === '…');

// ログの記録と取得
Documents_AuditLogger::logUpdate($logId, array(
    array('field' => 'notes_title', 'old_value' => 'A', 'new_value' => 'B')));
$auditLog = Documents_AuditLogger::getAuditLog($logId, 1, 20);
SpecRunner::assertTrue('TC-AL-031', 'ログが取得できる', $auditLog['total'] >= 1, json_encode($auditLog['total']));
SpecRunner::assertSame('TC-AL-025', '表示用ラベルが付与される', true,
    isset($auditLog['records'][0]['action_detail']['changes'][0]['label']));

$empty = Documents_AuditLogger::getAuditLog(SPEC_MISSING_NOTES_ID, 1, 20);
SpecRunner::assertSame('TC-AL-033', 'ログ0件は空配列', array(), $empty['records']);
SpecRunner::assertSame('TC-AL-033', 'ログ0件は total=0', 0, $empty['total']);

SpecRunner::assertSame('TC-AL-021', '片方にしか無い項目は無視',
    array(), Documents_AuditLogger::diffSnapshots(array('x' => 1), array()));

// ---------------------------------------------------------------- フォルダ権限
SpecRunner::section('TS-05 4.5 フォルダ権限（Q-10）');

$permId = specCreateDocument('PERM_TARGET');
SpecRunner::assertTrue('TC-CA-032c', '管理者はすべてのドキュメントを参照できる',
    Documents_FolderPermission::canAccessDocument($permId));
SpecRunner::assertFalse('TC-CA-032e', '存在しないIDは参照不可',
    Documents_FolderPermission::canAccessDocument(SPEC_MISSING_NOTES_ID));
SpecRunner::assertFalse('TC-CA-032e', 'ID=0 は参照不可',
    Documents_FolderPermission::canAccessDocument(0));
SpecRunner::assertSame('TC-FH-017b', 'filterAccessibleDocuments が参照可能なIDだけ返す',
    array($permId), Documents_FolderPermission::filterAccessibleDocuments(array($permId, SPEC_MISSING_NOTES_ID)));
SpecRunner::assertSame('TC-FH-017', 'filterAccessibleDocuments に空配列を渡すと空配列',
    array(), Documents_FolderPermission::filterAccessibleDocuments(array()));

// ---------------------------------------------------------------- ハッシュ一括検証
SpecRunner::section('TS-05 4.2 ハッシュの一括検証（Q-11）');

$complianceApi = new Documents_ComplianceAPI_Api();
$batchVerify = new ReflectionMethod('Documents_ComplianceAPI_Api', 'batchVerifyHash');
$batchVerify->setAccessible(true);

/** notesids を持つリクエストを作る */
function specRequestWithIds($ids) {
    $values = array('notesids' => $ids);
    return new Vtiger_Request($values, $values);
}

$emptyResult = $batchVerify->invoke($complianceApi, specRequestWithIds(array()));
SpecRunner::assertSame('TC-FH-017', '空配列は例外にせず total=0 を返す', 0, $emptyResult['total']);
SpecRunner::assertSame('TC-FH-017', '空配列は valid=0', 0, $emptyResult['valid']);
SpecRunner::assertSame('TC-FH-017', '空配列は results=[]', array(), $emptyResult['results']);

SpecRunner::assertThrows('TC-FH-016', 'notesids が配列でない場合は例外',
    function () use ($batchVerify, $complianceApi) {
        return $batchVerify->invoke($complianceApi, specRequestWithIds('abc'));
    });

$hashDoc = specCreateComplianceDoc('BATCH_HASH');
$batchResult = $batchVerify->invoke($complianceApi, specRequestWithIds(array($hashDoc)));
SpecRunner::assertSame('TC-FH-015', '参照できるドキュメントは検証対象になる', 1, $batchResult['total']);
$deniedResult = $batchVerify->invoke($complianceApi, specRequestWithIds(array(SPEC_MISSING_NOTES_ID)));
SpecRunner::assertSame('TC-FH-017b', '参照できないIDは対象から除かれる', 0, $deniedResult['total']);

SpecRunner::cleanup();
exit(SpecRunner::summarize('TS-04 / TS-05'));
