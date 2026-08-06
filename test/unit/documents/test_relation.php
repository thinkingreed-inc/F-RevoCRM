<?php
/**
 * ドキュメントの関連付けテスト
 *
 * レコードの関連リストから既存のドキュメントを紐づける機能を検証する。
 *
 * 検証内容:
 *   1. 関連付け（紐づけ・重複・ドキュメント以外のID）
 *   2. 関連付けの解除
 *   3. 候補一覧の絞り込み（紐づけ済みを除外・無効なドキュメントを除外）
 *
 * Usage:
 *   php test_relation.php
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
function createTestDocument($titleSuffix, $fileStatus = 1) {
    $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
    $recordModel->set('mode', '');
    $recordModel->set('notes_title', 'RELTEST_' . $titleSuffix);
    $recordModel->set('filelocationtype', 'E');
    $recordModel->set('filename', 'http://example.com/relation-test');
    $recordModel->set('folderid', 1);
    $recordModel->set('filestatus', $fileStatus);
    $recordModel->set('assigned_user_id', 1);
    $recordModel->save();
    return $recordModel->getId();
}

/**
 * テスト用の取引先を作成する
 */
function createTestAccount() {
    $recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
    $recordModel->set('mode', '');
    $recordModel->set('accountname', 'RELTEST_取引先');
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
        "SELECT crmid FROM vtiger_crmentity WHERE label LIKE ? AND setype IN ('Documents', 'Accounts')",
        array('RELTEST%')
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

/**
 * 関連付けの有無を返す
 */
function isRelated($parentId, $notesId) {
    $adb = PearDatabase::getInstance();
    $result = $adb->pquery(
        "SELECT notesid FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?",
        array($parentId, $notesId)
    );
    return ($result !== false && $adb->num_rows($result) > 0);
}

echo "=== ドキュメントの関連付けテスト ===\n";
cleanupTestData();

$accountId = createTestAccount();
$documentId = createTestDocument('紐づけ対象');
$otherDocumentId = createTestDocument('別のドキュメント');
$inactiveDocumentId = createTestDocument('無効なドキュメント', 0);
check('テストデータを作成', $accountId > 0 && $documentId > 0,
    "account={$accountId} document={$documentId}");

$parentModuleModel = Vtiger_Module_Model::getInstance('Accounts');
$documentsModuleModel = Vtiger_Module_Model::getInstance('Documents');
$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $documentsModuleModel);

echo "\n1. 関連付け\n";
check('取引先とドキュメントの関連が取得できる', !empty($relationModel));
$relationModel->addRelation($accountId, $documentId);
check('関連付けできる', isRelated($accountId, $documentId));
check('関連付けは vtiger_senotesrel に登録される',
    $adb->num_rows($adb->pquery("SELECT notesid FROM vtiger_senotesrel WHERE crmid = ?",
        array($accountId))) === 1);

// 更新履歴（関連付け）が残る
$history = $adb->pquery(
    "SELECT status FROM vtiger_modtracker_basic WHERE crmid = ? ORDER BY id DESC LIMIT 1",
    array($accountId));
check('更新履歴に関連付けが記録される',
    (int) $adb->query_result($history, 0, 'status') === 4,
    (string) $adb->query_result($history, 0, 'status'));

echo "\n2. 関連付けの解除\n";
$relationModel->deleteRelation($accountId, $documentId);
check('関連付けを解除できる', !isRelated($accountId, $documentId));
$history = $adb->pquery(
    "SELECT status FROM vtiger_modtracker_basic WHERE crmid = ? ORDER BY id DESC LIMIT 1",
    array($accountId));
check('更新履歴に解除が記録される',
    (int) $adb->query_result($history, 0, 'status') === 5,
    (string) $adb->query_result($history, 0, 'status'));

echo "\n3. 候補一覧の絞り込み（ListAPI の exclude_parent_id / active_only）\n";
$relationModel->addRelation($accountId, $documentId);

/**
 * ListAPI と同じ条件で候補件数を数える
 */
function countCandidates($excludeParentId, $activeOnly) {
    $adb = PearDatabase::getInstance();
    $query = "SELECT COUNT(*) AS cnt FROM vtiger_notes
        INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
        WHERE vtiger_crmentity.deleted = 0 AND vtiger_notes.title LIKE 'RELTEST%'";
    $params = array();
    if ($excludeParentId > 0) {
        $query .= " AND NOT EXISTS (
            SELECT 1 FROM vtiger_senotesrel excl
            WHERE excl.notesid = vtiger_notes.notesid AND excl.crmid = ?)";
        $params[] = $excludeParentId;
    }
    if ($activeOnly) {
        $query .= " AND vtiger_notes.filestatus = 1";
    }
    $result = $adb->pquery($query, $params);
    return (int) $adb->query_result($result, 0, 'cnt');
}

check('絞り込みなしでは3件（有効2件＋無効1件）', countCandidates(0, false) === 3,
    (string) countCandidates(0, false));
check('紐づけ済みを除くと2件', countCandidates($accountId, false) === 2,
    (string) countCandidates($accountId, false));
check('無効なドキュメントも除くと1件', countCandidates($accountId, true) === 1,
    (string) countCandidates($accountId, true));
check('別のドキュメントは候補に残る', !isRelated($accountId, $otherDocumentId));

echo "\n4. 複数件の関連付け\n";
$relationModel->addRelation($accountId, $otherDocumentId);
check('2件目も関連付けできる', isRelated($accountId, $otherDocumentId));
check('紐づけ件数が2件になる',
    $adb->num_rows($adb->pquery("SELECT notesid FROM vtiger_senotesrel WHERE crmid = ?",
        array($accountId))) === 2);
check('すべて紐づけると候補が無くなる（有効なもののみ）', countCandidates($accountId, true) === 0,
    (string) countCandidates($accountId, true));

cleanupTestData();
check('テストデータを削除',
    $adb->num_rows($adb->pquery(
        "SELECT crmid FROM vtiger_crmentity WHERE label LIKE ? AND setype IN ('Documents','Accounts')",
        array('RELTEST%'))) === 0);
check('関連付けも削除される', !isRelated($accountId, $documentId));

echo "\n=== 結果 ===\n";
if (empty($failures)) {
    echo "すべて成功\n";
    exit(0);
}
echo count($failures) . "件失敗: " . implode(' / ', $failures) . "\n";
exit(1);
