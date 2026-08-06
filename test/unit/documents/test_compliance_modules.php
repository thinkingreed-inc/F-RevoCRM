<?php
/**
 * 書類区分ごとの取引レコード判定テスト
 *
 * 電帳法の適合チェックで「取引レコードに関連付けされているか」を
 * 書類区分ごとに設定できることを検証する。
 *
 * 検証内容:
 *   1. 既定の判定基準（契約書は契約に紐づけば適合）
 *   2. 書類区分ごとの判定（対象モジュール以外に紐づけても不適合）
 *   3. 設定の保存と読み戻し（HTMLエンティティ化されても壊れない）
 *   4. 入力値の検証（不正な書類区分・モジュールは拒否）
 *
 * Usage:
 *   php test_compliance_modules.php
 */

chdir(dirname(__FILE__) . '/../../../');
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
require_once 'modules/Documents/utils/ComplianceChecker.php';
require_once 'modules/Settings/Vtiger/models/Module.php';
require_once 'modules/Settings/Vtiger/models/Record.php';
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
 * テスト用ドキュメントを作成する
 */
function createTestDocument($titleSuffix, $category, $preservationType = 'electronic_transaction') {
    $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
    $recordModel->set('mode', '');
    $recordModel->set('notes_title', 'CATTEST_' . $titleSuffix);
    $recordModel->set('filelocationtype', 'E');
    $recordModel->set('filename', 'http://example.com/category-test');
    $recordModel->set('folderid', 1);
    $recordModel->set('filestatus', 1);
    $recordModel->set('assigned_user_id', 1);
    $recordModel->save();

    $adb = PearDatabase::getInstance();
    $adb->pquery("UPDATE vtiger_notes SET document_category = ?, preservation_type = ? WHERE notesid = ?",
        array($category, $preservationType, $recordModel->getId()));
    return $recordModel->getId();
}

/**
 * テスト用の取引先を作成する
 */
function createTestAccount() {
    $recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
    $recordModel->set('mode', '');
    $recordModel->set('accountname', 'CATTEST_取引先');
    $recordModel->set('assigned_user_id', 1);
    $recordModel->save();
    return $recordModel->getId();
}

/**
 * テストデータを削除する
 */
function cleanupTestData() {
    $adb = PearDatabase::getInstance();
    $result = $adb->pquery(
        "SELECT crmid FROM vtiger_crmentity WHERE label LIKE ? AND setype IN ('Documents','Accounts')",
        array('CATTEST%')
    );
    for ($i = 0; $i < $adb->num_rows($result); $i++) {
        $crmId = (int) $adb->query_result($result, $i, 'crmid');
        $adb->pquery("DELETE FROM vtiger_senotesrel WHERE notesid = ? OR crmid = ?", array($crmId, $crmId));
        $adb->pquery("DELETE FROM vtiger_notes_audit_log WHERE notesid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_notes WHERE notesid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_account WHERE accountid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_accountbillads WHERE accountaddressid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_accountshipads WHERE accountaddressid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_accountscf WHERE accountid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_crmentity WHERE crmid = ?", array($crmId));
        $adb->pquery("DELETE FROM vtiger_modtracker_relations
            WHERE id IN (SELECT id FROM vtiger_modtracker_basic WHERE crmid = ?)", array($crmId));
        $adb->pquery("DELETE FROM vtiger_modtracker_detail
            WHERE id IN (SELECT id FROM vtiger_modtracker_basic WHERE crmid = ?)", array($crmId));
        $adb->pquery("DELETE FROM vtiger_modtracker_basic WHERE crmid = ?", array($crmId));
    }
}

echo "=== 書類区分ごとの取引レコード判定テスト ===\n";
cleanupTestData();
$originalModules = Documents_ComplianceChecker::getCategoryTransactionModules();

echo "\n1. 既定の判定基準\n";
$defaults = Documents_ComplianceChecker::DEFAULT_CATEGORY_TRANSACTION_MODULES;
check('契約書は契約（ServiceContracts）で適合になる',
    in_array('ServiceContracts', $defaults['contract'], true),
    implode(',', $defaults['contract']));
check('請求書は請求（Invoice）で適合になる',
    in_array('Invoice', $defaults['invoice'], true), implode(',', $defaults['invoice']));
check('請求書に契約は含まれない', !in_array('ServiceContracts', $defaults['invoice'], true));
check('書類区分を指定しない場合は全区分の和集合',
    count(Documents_ComplianceChecker::getTransactionModules()) >= count($defaults['contract']));
check('未知の書類区分は既定のモジュールで判定する',
    Documents_ComplianceChecker::getTransactionModules('unknown_category')
    === Documents_ComplianceChecker::DEFAULT_TRANSACTION_MODULES);

echo "\n2. 書類区分ごとの判定\n";
$accountId = createTestAccount();
$contractDoc = createTestDocument('契約書', 'contract');
$adb->pquery("INSERT INTO vtiger_senotesrel (crmid, notesid) VALUES (?, ?)",
    array($accountId, $contractDoc));

// 既定では契約書に顧客企業が含まれるため適合
$result = Documents_ComplianceChecker::check($contractDoc);
check('契約書を顧客企業に紐づけると適合（既定）', $result['status'] === 'compliant',
    $result['status'] . ' ' . implode(';', $result['issues']));

// 契約書は契約のみを対象にすると、顧客企業では不適合になる
Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array_merge($originalModules,
    array('contract' => array('ServiceContracts'))));
$result = Documents_ComplianceChecker::check($contractDoc);
check('対象モジュール以外に紐づけても不適合', $result['status'] === 'non_compliant',
    $result['status'] . ' ' . implode(';', $result['issues']));
check('不適合の理由は関連付け不足',
    strpos($adb->query_result($adb->pquery(
        "SELECT compliance_notes FROM vtiger_notes WHERE notesid = ?", array($contractDoc)),
        0, 'compliance_notes'), 'LBL_NO_RELATED_RECORD') !== false);

// 顧客企業を対象に戻すと適合になる
Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array_merge($originalModules,
    array('contract' => array('ServiceContracts', 'Accounts'))));
$result = Documents_ComplianceChecker::check($contractDoc);
check('対象モジュールに追加すると適合になる', $result['status'] === 'compliant',
    $result['status']);

// モジュールを1つも選ばない書類区分は関連付けを要求しない
$otherDoc = createTestDocument('関連なし', 'receipt');
Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array_merge($originalModules,
    array('receipt' => array())));
$result = Documents_ComplianceChecker::check($otherDoc);
check('対象モジュールが空なら関連付けなしでも適合', $result['status'] === 'compliant',
    $result['status'] . ' ' . implode(';', $result['issues']));

// 他の書類区分の設定には影響しない
$invoiceDoc = createTestDocument('請求書', 'invoice');
$result = Documents_ComplianceChecker::check($invoiceDoc);
check('別の書類区分は引き続き関連付けが必要', $result['status'] === 'non_compliant',
    $result['status']);

echo "\n3. 設定の保存と読み戻し\n";
$saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array_merge($originalModules,
    array('contract' => array('ServiceContracts', 'Vendors'))));
check('保存した内容が返る', $saved['contract'] === array('ServiceContracts', 'Vendors'),
    implode(',', $saved['contract']));
Documents_ComplianceChecker::clearCache();
$reloaded = Documents_ComplianceChecker::getCategoryTransactionModules();
check('読み戻しても壊れない（HTMLエンティティ化の考慮）',
    $reloaded['contract'] === array('ServiceContracts', 'Vendors'),
    implode(',', $reloaded['contract']));
$storedValue = $adb->query_result($adb->pquery(
    "SELECT value FROM vtiger_documents_settings WHERE name = ?",
    array(Documents_ComplianceChecker::SETTING_CATEGORY_MODULES)), 0, 'value');
check('JSONとして解析できる', is_array(json_decode(decode_html($storedValue), true)));

echo "\n4. 入力値の検証\n";
$invalidCases = array(
    '存在しない書類区分' => array('unknown' => array('Accounts')),
    'ドキュメントを紐づけられないモジュール' => array('contract' => array('Users')),
    '配列でない値' => 'not-json',
);
foreach ($invalidCases as $label => $case) {
    $rejected = false;
    try {
        Settings_DocumentsCompliance_Module_Model::saveCategoryModules($case);
    } catch (Exception $e) {
        $rejected = true;
    }
    check($label . 'は拒否する', $rejected);
}
check('重複したモジュールは1つにまとめる',
    Settings_DocumentsCompliance_Module_Model::saveCategoryModules(array_merge($originalModules,
        array('contract' => array('Accounts', 'Accounts', 'Vendors'))))['contract']
    === array('Accounts', 'Vendors'));

// 設定を元に戻す
Settings_DocumentsCompliance_Module_Model::saveCategoryModules($originalModules);
Documents_ComplianceChecker::clearCache();
check('設定を元に戻せる',
    Documents_ComplianceChecker::getCategoryTransactionModules() == $originalModules);

cleanupTestData();
check('テストデータを削除',
    $adb->num_rows($adb->pquery(
        "SELECT crmid FROM vtiger_crmentity WHERE label LIKE ? AND setype IN ('Documents','Accounts')",
        array('CATTEST%'))) === 0);

echo "\n=== 結果 ===\n";
if (empty($failures)) {
    echo "すべて成功\n";
    exit(0);
}
echo count($failures) . "件失敗: " . implode(' / ', $failures) . "\n";
exit(1);
