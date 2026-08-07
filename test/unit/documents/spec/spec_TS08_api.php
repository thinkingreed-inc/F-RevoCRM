<?php
/**
 * TS-08 / TS-13 ドキュメントAPI・関連付け 自動テスト
 *
 * 対応する仕様書:
 *   docs/tests/Documents/TS-08_ドキュメントAPI.md
 *   docs/tests/Documents/TS-13_ドキュメント関連付け.md
 *
 * API は index.php を通さず、内部のクエリ組み立てと同じ条件を検証する
 * （HTTP 経由の検証は手動テストの範囲）。
 *
 * Usage:
 *   php test/unit/documents/spec/spec_TS08_api.php
 */

require_once dirname(__FILE__) . '/bootstrap.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';
require_once 'modules/Documents/utils/FolderPermission.php';

echo "=== TS-08 / TS-13 API ===\n";

$db = PearDatabase::getInstance();
SpecRunner::addCleanup(function () {
    specCleanDocuments();
    $db = PearDatabase::getInstance();
    $db->pquery("DELETE FROM vtiger_attachmentsfolder WHERE foldername LIKE ?", array(SPEC_PREFIX . '%'));
});
specCleanDocuments();
$db->pquery("DELETE FROM vtiger_attachmentsfolder WHERE foldername LIKE ?", array(SPEC_PREFIX . '%'));

// ---------------------------------------------------------------- ページング
SpecRunner::section('TS-08 4.1 ページングの正規化（DT-2 / BV-1 / Q-14）');

// 実装のメソッドをそのまま呼ぶ（ロジックを複製しない）
SpecRunner::assertSame('TC-AP-010', 'page=0 は1', 1, Documents_ListAPI_Api::normalizePage(0));
SpecRunner::assertSame('TC-AP-010', 'page=-1 は1', 1, Documents_ListAPI_Api::normalizePage(-1));
SpecRunner::assertSame('TC-AP-010', "page='abc' は1", 1, Documents_ListAPI_Api::normalizePage('abc'));
SpecRunner::assertSame('TC-AP-001', 'page=3 はそのまま', 3, Documents_ListAPI_Api::normalizePage(3));
SpecRunner::assertSame('TC-AP-011', 'pageLimit=0 は既定20', 20, Documents_ListAPI_Api::normalizePageLimit(0));
SpecRunner::assertSame('TC-AP-011', 'pageLimit=-1 は既定20', 20, Documents_ListAPI_Api::normalizePageLimit(-1));
SpecRunner::assertSame('TC-AP-011', 'pageLimit 未指定は既定20', 20, Documents_ListAPI_Api::normalizePageLimit(null));
SpecRunner::assertSame('TC-AP-001', 'pageLimit=1 はそのまま', 1, Documents_ListAPI_Api::normalizePageLimit(1));
SpecRunner::assertSame('TC-AP-013', 'pageLimit=100 は100（上限）', 100, Documents_ListAPI_Api::normalizePageLimit(100));
SpecRunner::assertSame('TC-AP-012', 'pageLimit=101 は100に丸める', 100, Documents_ListAPI_Api::normalizePageLimit(101));
SpecRunner::assertSame('TC-AP-012', 'pageLimit=10000 は100に丸める', 100, Documents_ListAPI_Api::normalizePageLimit(10000));

// ---------------------------------------------------------------- ソートの安全性
SpecRunner::section('TS-08 4.1 ソート項目のホワイトリスト（BV-2）');

SpecRunner::assertSame('TC-AP-014', '許可された項目はそのカラム',
    'vtiger_notes.filesize', Documents_ListAPI_Api::resolveSortColumn('filesize'));
SpecRunner::assertSame('TC-AP-014', 'title は notes.title',
    'vtiger_notes.title', Documents_ListAPI_Api::resolveSortColumn('title'));
SpecRunner::assertSame('TC-AP-015', 'SQL断片は既定にフォールバック',
    'vtiger_crmentity.modifiedtime',
    Documents_ListAPI_Api::resolveSortColumn('title; DROP TABLE vtiger_notes--'));
SpecRunner::assertSame('TC-AP-015', '未知の項目は既定',
    'vtiger_crmentity.modifiedtime', Documents_ListAPI_Api::resolveSortColumn('unknown'));
SpecRunner::assertSame('TC-AP-015', '空文字は既定',
    'vtiger_crmentity.modifiedtime', Documents_ListAPI_Api::resolveSortColumn(''));
SpecRunner::assertSame('TC-AP-014', 'sort_order=asc は ASC',
    'ASC', Documents_ListAPI_Api::normalizeSortOrder('asc'));
SpecRunner::assertSame('TC-AP-016', 'sort_order の不正値は DESC',
    'DESC', Documents_ListAPI_Api::normalizeSortOrder('; DELETE FROM x'));
SpecRunner::assertSame('TC-AP-016', 'sort_order 未指定は DESC',
    'DESC', Documents_ListAPI_Api::normalizeSortOrder(null));

// ---------------------------------------------------------------- LIKE エスケープ
SpecRunner::section('TS-08 4.1 検索のエスケープ（BV-3 / Q-15）');

$reflection = new ReflectionClass('Documents_ListAPI_Api');
$escape = $reflection->getMethod('escapeLikeValue');
$escape->setAccessible(true);
SpecRunner::assertSame('TC-AP-025', '% をエスケープ', '100!%', $escape->invoke(null, '100%'));
SpecRunner::assertSame('TC-AP-025b', '_ をエスケープ', 'a!_b', $escape->invoke(null, 'a_b'));
SpecRunner::assertSame('TC-AP-025c', 'エスケープ文字自身を二重化', '!!', $escape->invoke(null, '!'));
SpecRunner::assertSame('TC-AP-023', '通常の文字はそのまま', '契約書', $escape->invoke(null, '契約書'));
SpecRunner::assertSame('TC-AP-025', '複合', '50!%!_off', $escape->invoke(null, '50%_off'));

// 実際に SQL で確認する
$pctId = specCreateDocument('LIKE_50%OFF');
$plainId = specCreateDocument('LIKE_PLAIN');
$keyword = '%' . $escape->invoke(null, '%') . '%';
$result = $db->pquery(
    "SELECT notesid FROM vtiger_notes WHERE title LIKE ? ESCAPE '!' AND title LIKE ?",
    array($keyword, SPEC_PREFIX . '%'));
$hits = array();
for ($i = 0; $i < $db->num_rows($result); $i++) {
    $hits[] = (int) $db->query_result($result, $i, 'notesid');
}
SpecRunner::assertTrue('TC-AP-025', '「%」検索は % を含むものだけに一致',
    in_array($pctId, $hits, true) && !in_array($plainId, $hits, true), json_encode($hits));

// ---------------------------------------------------------------- 電帳法の抽出条件
SpecRunner::section('TS-08 / TS-05 電帳法フィルタの抽出条件（Q-09）');

$catNull = specCreateDocument('FILTER_NULL');
specUpdateNotes($catNull, array('document_category' => null));
$catEmpty = specCreateDocument('FILTER_EMPTY');
specUpdateNotes($catEmpty, array('document_category' => ''));
$catSet = specCreateDocument('FILTER_SET');
specUpdateNotes($catSet, array('document_category' => 'invoice'));

$result = $db->pquery(
    "SELECT vtiger_notes.notesid FROM vtiger_notes
     INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
     WHERE " . Documents_ComplianceChecker::TARGET_SQL_CONDITION . "
       AND vtiger_crmentity.deleted = 0 AND vtiger_notes.title LIKE ?",
    array(SPEC_PREFIX . 'FILTER_%'));
$ids = array();
for ($i = 0; $i < $db->num_rows($result); $i++) {
    $ids[] = (int) $db->query_result($result, $i, 'notesid');
}
SpecRunner::assertSame('TC-CC-021d', '電帳法フィルタは区分ありのみ', array($catSet), $ids);

// ---------------------------------------------------------------- フォルダ階層
SpecRunner::section('TS-08 4.3 フォルダ階層（BV-5 / Q-19 / Q-20）');

/** テスト用フォルダを作る */
function specCreateFolder($name, $parentId = 0) {
    $db = PearDatabase::getInstance();
    // getUniqueID のシーケンスは実データとずれることがあるため、確実な採番を使う
    $max = $db->pquery("SELECT COALESCE(MAX(folderid), 0) + 1 AS next FROM vtiger_attachmentsfolder", array());
    $folderId = (int) $db->query_result($max, 0, 'next');
    $db->pquery(
        "INSERT INTO vtiger_attachmentsfolder (folderid, foldername, description, createdby, sequence, parent_folderid)
         VALUES (?, ?, '', 1, 1, ?)",
        array($folderId, SPEC_PREFIX . $name, $parentId));
    return (int) $folderId;
}

$rootFolder = specCreateFolder('ROOT');
$childFolder = specCreateFolder('CHILD', $rootFolder);
$grandFolder = specCreateFolder('GRAND', $childFolder);

$api = new Documents_FolderAPI_Api();
$assertParent = new ReflectionMethod('Documents_FolderAPI_Api', 'assertValidParentFolder');
$assertParent->setAccessible(true);

SpecRunner::assertThrows('TC-AP-048c', '自分自身を親にできない',
    function () use ($assertParent, $api, $db, $rootFolder) {
        return $assertParent->invoke($api, $db, $rootFolder, $rootFolder, 'Documents');
    });
SpecRunner::assertThrows('TC-AP-048d', '子を親にできない',
    function () use ($assertParent, $api, $db, $rootFolder, $childFolder) {
        return $assertParent->invoke($api, $db, $rootFolder, $childFolder, 'Documents');
    });
SpecRunner::assertThrows('TC-AP-048e', '孫を親にできない',
    function () use ($assertParent, $api, $db, $rootFolder, $grandFolder) {
        return $assertParent->invoke($api, $db, $rootFolder, $grandFolder, 'Documents');
    });
SpecRunner::assertThrows('TC-AP-048f', '存在しないフォルダを親にできない',
    function () use ($assertParent, $api, $db, $rootFolder) {
        return $assertParent->invoke($api, $db, $rootFolder, 99999999, 'Documents');
    });
SpecRunner::assertNotThrows('TC-AP-048g', 'ルート(0)への変更は許可',
    function () use ($assertParent, $api, $db, $childFolder) {
        $assertParent->invoke($api, $db, $childFolder, 0, 'Documents');
    });
SpecRunner::assertNotThrows('TC-AP-048b', '正当な親子関係は許可',
    function () use ($assertParent, $api, $db, $grandFolder, $rootFolder) {
        $assertParent->invoke($api, $db, $grandFolder, $rootFolder, 'Documents');
    });
SpecRunner::assertNotThrows('TC-AP-048b', '新規作成（folderId=0）でも検証できる',
    function () use ($assertParent, $api, $db, $rootFolder) {
        $assertParent->invoke($api, $db, 0, $rootFolder, 'Documents');
    });

// パンくず（深さ制限なし・循環で止まる）
$detailApi = new Documents_DetailAPI_Api();
$getFolderPath = new ReflectionMethod('Documents_DetailAPI_Api', 'getFolderPath');
$getFolderPath->setAccessible(true);

$deep = specCreateFolder('DEEP0');
$deepIds = array($deep);
for ($i = 1; $i <= 14; $i++) {
    $deep = specCreateFolder('DEEP' . $i, $deep);
    $deepIds[] = $deep;
}
$path = $getFolderPath->invoke($detailApi, $db, $deep);
SpecRunner::assertSame('TC-AP-040c', '15階層でも打ち切らない', 15, count($path));
SpecRunner::assertSame('TC-AP-040c', 'パンくずの先頭は最上位', $deepIds[0], $path[0]['id']);

// 循環しても止まる
$loopA = specCreateFolder('LOOP_A');
$loopB = specCreateFolder('LOOP_B', $loopA);
$db->pquery("UPDATE vtiger_attachmentsfolder SET parent_folderid = ? WHERE folderid = ?",
    array($loopB, $loopA));
$start = microtime(true);
$path = $getFolderPath->invoke($detailApi, $db, $loopA);
$elapsed = microtime(true) - $start;
SpecRunner::assertTrue('TC-AP-040e', '循環しても停止する（無限ループにしない）',
    $elapsed < 3.0 && count($path) <= 2, "件数=" . count($path) . " 時間=" . round($elapsed, 3));

// ---------------------------------------------------------------- スター
SpecRunner::section('TS-08 4.2/4.3 スターの取得（Q-17 / Q-18）');

$starDoc = specCreateDocument('STAR_TARGET');
$db->pquery("DELETE FROM vtiger_crmentity_user_field WHERE recordid = ?", array($starDoc));
$db->pquery(
    "INSERT INTO vtiger_crmentity_user_field (recordid, userid, starred) VALUES (?, 1, '1')",
    array($starDoc));

// 詳細APIの実装（getDocumentDetail）を直接呼んで応答を確認する
$getDetail = new ReflectionMethod('Documents_DetailAPI_Api', 'getDocumentDetail');
$getDetail->setAccessible(true);
$detail = $getDetail->invoke($detailApi, $starDoc);
SpecRunner::assertSame('TC-AP-040d', '詳細APIのスターが実行ユーザーの値になる', true, $detail['starred']);
SpecRunner::assertSame('TC-AP-040d', '詳細APIがIDを返す', $starDoc, (int) $detail['id']);

$noStarDoc = specCreateDocument('STAR_NONE');
$detail = $getDetail->invoke($detailApi, $noStarDoc);
SpecRunner::assertSame('TC-AP-040f', 'スターを付けていなければ false', false, $detail['starred']);

// 他ユーザーのスターは自分の表示に影響しない
$db->pquery("DELETE FROM vtiger_crmentity_user_field WHERE recordid = ?", array($noStarDoc));
$db->pquery(
    "INSERT INTO vtiger_crmentity_user_field (recordid, userid, starred) VALUES (?, 999999, '1')",
    array($noStarDoc));
$detail = $getDetail->invoke($detailApi, $noStarDoc);
SpecRunner::assertSame('TC-AP-040f', '他ユーザーのスターは自分には出ない', false, $detail['starred']);
$db->pquery("DELETE FROM vtiger_crmentity_user_field WHERE recordid = ?", array($noStarDoc));

// スターを外すと詳細にも追従する
$db->pquery("UPDATE vtiger_crmentity_user_field SET starred = '0' WHERE recordid = ? AND userid = 1",
    array($starDoc));
$detail = $getDetail->invoke($detailApi, $starDoc);
SpecRunner::assertSame('TC-AP-040g', 'スターを外すと false になる', false, $detail['starred']);
$db->pquery("UPDATE vtiger_crmentity_user_field SET starred = '1' WHERE recordid = ? AND userid = 1",
    array($starDoc));

// フォルダツリーAPI（実装）を呼んでスター件数を確認する
$folderApi = new Documents_FolderAPI_Api();
$emptyRequest = new Vtiger_Request(array(), array());
$tree = $folderApi->tree($emptyRequest)->getResult();
SpecRunner::assertTrue('TC-AP-049', 'ツリーAPIがフォルダ一覧を返す', isset($tree['folders']));
SpecRunner::assertTrue('TC-AP-049', 'ツリーAPIが総件数を返す', isset($tree['totalCount']));
$starredCount = (int) $tree['starredCount'];
SpecRunner::assertTrue('TC-AP-049b', 'スター件数が実数で返る（0固定でない）',
    $starredCount >= 1, (string) $starredCount);

$db->pquery("UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?", array($starDoc));
$tree = $folderApi->tree($emptyRequest)->getResult();
SpecRunner::assertSame('TC-AP-049d', '削除済みはスター件数に含めない',
    $starredCount - 1, (int) $tree['starredCount']);
$db->pquery("UPDATE vtiger_crmentity SET deleted = 0 WHERE crmid = ?", array($starDoc));

$db->pquery("UPDATE vtiger_crmentity_user_field SET starred = '0' WHERE recordid = ? AND userid = 1",
    array($starDoc));
$tree = $folderApi->tree($emptyRequest)->getResult();
SpecRunner::assertSame('TC-AP-049c', 'スターを外すと件数が減る',
    $starredCount - 1, (int) $tree['starredCount']);
$db->pquery("UPDATE vtiger_crmentity_user_field SET starred = '1' WHERE recordid = ? AND userid = 1",
    array($starDoc));

// ---------------------------------------------------------------- 関連付け（TS-13）
SpecRunner::section('TS-13 関連付け（DT-2 / BV-1 / Q-29）');

$relationApi = new Documents_RelationAPI_Api();
$getRecordIds = new ReflectionMethod('Documents_RelationAPI_Api', 'getRecordIds');
$getRecordIds->setAccessible(true);

/** getRecordIds に渡すリクエストを組み立てる */
function specRequestWith($values) {
    $request = new Vtiger_Request($values, $values);
    return $request;
}

SpecRunner::assertSame('TC-RL-004', 'カンマ区切りを配列にする', array(1, 2, 3),
    $getRecordIds->invoke($relationApi, specRequestWith(array('records' => '1,2,3'))));
SpecRunner::assertSame('TC-RL-002', '配列をそのまま扱う', array(4, 5),
    $getRecordIds->invoke($relationApi, specRequestWith(array('records' => array(4, 5)))));
SpecRunner::assertSame('TC-RL-005', '重複を除く', array(7),
    $getRecordIds->invoke($relationApi, specRequestWith(array('records' => array(7, 7, 7)))));
SpecRunner::assertSame('TC-RL-032', '0・負数・非数値を除く', array(),
    $getRecordIds->invoke($relationApi, specRequestWith(array('records' => array(0, -1, 'abc')))));
SpecRunner::assertSame('TC-RL-032', '未指定は空配列', array(),
    $getRecordIds->invoke($relationApi, specRequestWith(array())));
SpecRunner::assertSame('TC-RL-004', 'record（単数形）へのフォールバック', array(9),
    $getRecordIds->invoke($relationApi, specRequestWith(array('record' => '9'))));

$many = range(1, 500);
SpecRunner::assertSame('TC-RL-006b', '500件でも切り捨てない', 500,
    count($getRecordIds->invoke($relationApi, specRequestWith(array('records' => $many)))));
$over = range(1, 201);
SpecRunner::assertSame('TC-RL-006', '201件目も含まれる', 201,
    count($getRecordIds->invoke($relationApi, specRequestWith(array('records' => $over)))));

// isDocument / isRelated
$isDocument = new ReflectionMethod('Documents_RelationAPI_Api', 'isDocument');
$isDocument->setAccessible(true);
$relDoc = specCreateDocument('REL_DOC');
SpecRunner::assertTrue('TC-RL-001', 'ドキュメントを判別できる', $isDocument->invoke($relationApi, $relDoc));
SpecRunner::assertFalse('TC-RL-011', '存在しないIDはドキュメントでない',
    $isDocument->invoke($relationApi, 99999999));
$db->pquery("UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?", array($relDoc));
SpecRunner::assertFalse('TC-RL-012', '削除済みはドキュメントでない',
    $isDocument->invoke($relationApi, $relDoc));
$db->pquery("UPDATE vtiger_crmentity SET deleted = 0 WHERE crmid = ?", array($relDoc));

$parentExists = new ReflectionMethod('Documents_RelationAPI_Api', 'parentExists');
$parentExists->setAccessible(true);
SpecRunner::assertFalse('TC-RL-033', '存在しない親レコードは false',
    $parentExists->invoke($relationApi, 'Accounts', 99999999));
SpecRunner::assertFalse('TC-RL-033', 'モジュールが一致しない親は false',
    $parentExists->invoke($relationApi, 'Accounts', $relDoc));

$isRelated = new ReflectionMethod('Documents_RelationAPI_Api', 'isRelated');
$isRelated->setAccessible(true);
// vtiger_senotesrel.crmid は vtiger_crmentity への外部キーのため実在するIDを使う
$parentDoc = specCreateDocument('REL_PARENT');
SpecRunner::assertFalse('TC-RL-022', '未関連は false', $isRelated->invoke($relationApi, $parentDoc, $relDoc));
$db->pquery("INSERT INTO vtiger_senotesrel (crmid, notesid) VALUES (?, ?)", array($parentDoc, $relDoc));
SpecRunner::assertTrue('TC-RL-003', '関連済みは true', $isRelated->invoke($relationApi, $parentDoc, $relDoc));
$db->pquery("DELETE FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?", array($parentDoc, $relDoc));

// 候補の絞り込み（exclude_parent_id / active_only）
SpecRunner::section('TS-13 4.4 候補の絞り込み（DT-3）');

$linked = specCreateDocument('CAND_LINKED');
$unlinked = specCreateDocument('CAND_UNLINKED');
$inactive = specCreateDocument('CAND_INACTIVE');
specUpdateNotes($inactive, array('filestatus' => 0));
$parentCrmId = specCreateDocument('CAND_PARENT');
$db->pquery("INSERT INTO vtiger_senotesrel (crmid, notesid) VALUES (?, ?)", array($parentCrmId, $linked));

$result = $db->pquery(
    "SELECT notesid FROM vtiger_notes
     WHERE title LIKE ?
       AND NOT EXISTS (SELECT 1 FROM vtiger_senotesrel excl
                       WHERE excl.notesid = vtiger_notes.notesid AND excl.crmid = ?)",
    array(SPEC_PREFIX . 'CAND_%', $parentCrmId));
$ids = array();
for ($i = 0; $i < $db->num_rows($result); $i++) {
    $ids[] = (int) $db->query_result($result, $i, 'notesid');
}
SpecRunner::assertFalse('TC-RL-041', '紐づけ済みは候補に出ない', in_array($linked, $ids, true));
SpecRunner::assertTrue('TC-RL-041', '未紐づけは候補に出る', in_array($unlinked, $ids, true));

$result = $db->pquery(
    "SELECT notesid FROM vtiger_notes WHERE title LIKE ? AND filestatus = 1",
    array(SPEC_PREFIX . 'CAND_%'));
$ids = array();
for ($i = 0; $i < $db->num_rows($result); $i++) {
    $ids[] = (int) $db->query_result($result, $i, 'notesid');
}
SpecRunner::assertFalse('TC-RL-042', 'active_only では無効なドキュメントを除く',
    in_array($inactive, $ids, true));

$db->pquery("DELETE FROM vtiger_senotesrel WHERE crmid = ?", array($parentCrmId));

SpecRunner::cleanup();
exit(SpecRunner::summarize('TS-08 / TS-13'));
