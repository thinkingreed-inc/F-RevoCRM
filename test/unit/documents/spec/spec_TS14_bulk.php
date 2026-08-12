<?php
/**
 * TS-14 一括操作（削除・フォルダ移動） 自動テスト
 *
 * 対応する仕様書: docs/tests/Documents/TS-14_一括操作.md
 *
 * Usage:
 *   php test/unit/documents/spec/spec_TS14_bulk.php
 */

require_once dirname(__FILE__) . '/bootstrap.php';
require_once 'modules/Documents/utils/FolderPermission.php';
require_once 'modules/Documents/utils/AuditLogger.php';

echo "=== TS-14 一括操作 ===\n";

$db = PearDatabase::getInstance();
SpecRunner::addCleanup(function () {
    specCleanDocuments();
    $db = PearDatabase::getInstance();
    $db->pquery("DELETE FROM vtiger_attachmentsfolder WHERE foldername LIKE ?", array(SPEC_PREFIX . '%'));
});
specCleanDocuments();
$db->pquery("DELETE FROM vtiger_attachmentsfolder WHERE foldername LIKE ?", array(SPEC_PREFIX . '%'));

$api = new Documents_BulkAction_Api();

/** private メソッドを呼ぶ */
function bulkInvoke($api, $method, $request) {
    $m = new ReflectionMethod('Documents_BulkAction_Api', $method);
    $m->setAccessible(true);
    return $m->invoke($api, $request);
}

/** リクエストを組み立てる */
function bulkRequest($values) {
    return new Vtiger_Request($values, $values);
}

/** テスト用フォルダを作る */
function bulkCreateFolder($name) {
    $db = PearDatabase::getInstance();
    $next = $db->pquery(
        "SELECT COALESCE(MAX(folderid), 0) + 1 AS next FROM vtiger_attachmentsfolder", array());
    $folderId = (int) $db->query_result($next, 0, 'next');
    $db->pquery(
        "INSERT INTO vtiger_attachmentsfolder (folderid, foldername, description, createdby, sequence, parent_folderid)
         VALUES (?, ?, '', 1, 1, 0)", array($folderId, SPEC_PREFIX . $name));
    $db->pquery(
        "INSERT IGNORE INTO vtiger_folder_permissions (folderid, permission_type, target_type, target_id)
         VALUES (?, 'edit', 'everyone', NULL)", array($folderId));
    return $folderId;
}

/** ドキュメントの現在のフォルダを返す */
function bulkFolderOf($notesId) {
    $db = PearDatabase::getInstance();
    $r = $db->pquery("SELECT folderid FROM vtiger_notes WHERE notesid = ?", array($notesId));
    return (int) $db->query_result($r, 0, 'folderid');
}

/** 削除済みかどうか */
function bulkIsDeleted($notesId) {
    $db = PearDatabase::getInstance();
    $r = $db->pquery("SELECT deleted FROM vtiger_crmentity WHERE crmid = ?", array($notesId));
    if ($r === false || $db->num_rows($r) === 0) return null;
    return (int) $db->query_result($r, 0, 'deleted') === 1;
}

// ---------------------------------------------------------------- 対象の解釈
SpecRunner::section('4.1 対象の解釈（DT-1 / BV-1）');

$a = specCreateDocument('BULK_A');
$b = specCreateDocument('BULK_B');

SpecRunner::assertThrows('TC-BK-010', '対象が未指定なら例外',
    function () use ($api) { return bulkInvoke($api, 'deleteRecords', bulkRequest(array())); });
SpecRunner::assertThrows('TC-BK-010', '空文字なら例外',
    function () use ($api) {
        return bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => '')));
    });
SpecRunner::assertThrows('TC-BK-011', '存在しないIDだけなら例外',
    function () use ($api) {
        return bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array(99999999))));
    });
SpecRunner::assertThrows('TC-BK-011', 'ドキュメント以外のIDだけなら例外',
    function () use ($api) {
        $db = PearDatabase::getInstance();
        $r = $db->pquery("SELECT crmid FROM vtiger_crmentity WHERE setype != 'Documents' AND deleted = 0 LIMIT 1", array());
        $other = ($db->num_rows($r) > 0) ? (int) $db->query_result($r, 0, 'crmid') : 12345678;
        return bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($other))));
    });

// ---------------------------------------------------------------- 一括削除
SpecRunner::section('4.2 一括削除（DT-2）');

$result = bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($a, $b))));
SpecRunner::assertSame('TC-BK-001', '2件を削除する', 2, $result['deleted']);
SpecRunner::assertSame('TC-BK-001', '対象件数を返す', 2, $result['total']);
SpecRunner::assertSame('TC-BK-001', '権限で除外した件数は0', 0, $result['denied']);
SpecRunner::assertTrue('TC-BK-002', 'ごみ箱へ移動している（レコードは残る）', bulkIsDeleted($a) === true);
SpecRunner::assertTrue('TC-BK-002', '2件目も同様', bulkIsDeleted($b) === true);

$c = specCreateDocument('BULK_C');
$result = bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => "$c")));
SpecRunner::assertSame('TC-BK-003', 'カンマ区切り・単数でも処理できる', 1, $result['deleted']);

$d = specCreateDocument('BULK_D');
$result = bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($d, $d, $d))));
SpecRunner::assertSame('TC-BK-004', '重複を除いて1件として扱う', 1, $result['total']);

// 電帳法対象もごみ箱へは移動できる（完全削除は禁止のまま）
$compliance = specCreateDocument('BULK_COMPLIANCE');
specUpdateNotes($compliance, array('document_category' => 'invoice'));
$before = (int) $db->query_result($db->pquery(
    "SELECT COUNT(*) AS c FROM vtiger_notes_audit_log WHERE notesid = ? AND action_type = 'delete'",
    array($compliance)), 0, 'c');
$result = bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($compliance))));
SpecRunner::assertSame('TC-BK-005', '電帳法対象もごみ箱へ移動できる', 1, $result['deleted']);
$after = (int) $db->query_result($db->pquery(
    "SELECT COUNT(*) AS c FROM vtiger_notes_audit_log WHERE notesid = ? AND action_type = 'delete'",
    array($compliance)), 0, 'c');
SpecRunner::assertSame('TC-BK-006', '削除が監査ログに残る', $before + 1, $after);

// 完全削除の禁止は削除していないレコードで確認する（削除済みは読み出せないため）
$compliance2 = specCreateDocument('BULK_COMPLIANCE2');
specUpdateNotes($compliance2, array('document_category' => 'invoice'));
$model = Vtiger_Record_Model::getInstanceById($compliance2, 'Documents');
SpecRunner::assertFalse('TC-BK-007', '電帳法対象は完全削除できないまま', $model->isDeletable());
$plain = Vtiger_Record_Model::getInstanceById(specCreateDocument('BULK_PLAIN'), 'Documents');
SpecRunner::assertTrue('TC-BK-007', '電帳法対象外は完全削除できる', $plain->isDeletable());

// 削除済みは対象外
SpecRunner::assertThrows('TC-BK-008', '削除済みのIDだけなら例外（対象にしない）',
    function () use ($api, $a) {
        return bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($a))));
    });

// ---------------------------------------------------------------- 一括移動
SpecRunner::section('4.3 一括フォルダ移動（DT-3）');

$folderFrom = bulkCreateFolder('FROM');
$folderTo = bulkCreateFolder('TO');

$m1 = specCreateDocument('BULK_M1');
$m2 = specCreateDocument('BULK_M2');
specUpdateNotes($m1, array('folderid' => $folderFrom));
specUpdateNotes($m2, array('folderid' => $folderFrom));

$result = bulkInvoke($api, 'moveRecords',
    bulkRequest(array('records' => array($m1, $m2), 'folderid' => $folderTo)));
SpecRunner::assertSame('TC-BK-020', '2件を移動する', 2, $result['moved']);
SpecRunner::assertSame('TC-BK-020', '移動先を返す', $folderTo, $result['folderid']);
SpecRunner::assertSame('TC-BK-021', '1件目のフォルダが変わる', $folderTo, bulkFolderOf($m1));
SpecRunner::assertSame('TC-BK-021', '2件目も同様', $folderTo, bulkFolderOf($m2));

$result = bulkInvoke($api, 'moveRecords',
    bulkRequest(array('records' => array($m1), 'folderid' => $folderTo)));
SpecRunner::assertSame('TC-BK-022', '既に移動先にあるものはスキップ', 1, $result['skipped']);
SpecRunner::assertSame('TC-BK-022', 'スキップは移動に数えない', 0, $result['moved']);

// 変更履歴
$logs = Documents_AuditLogger::getAuditLog($m1, 1, 20);
$hasFolderChange = false;
foreach ($logs['records'] as $row) {
    if (!is_array($row['action_detail']) || empty($row['action_detail']['changes'])) continue;
    foreach ($row['action_detail']['changes'] as $change) {
        if ($change['field'] === 'folderid') $hasFolderChange = true;
    }
}
SpecRunner::assertTrue('TC-BK-023', 'フォルダの変更が変更履歴に残る', $hasFolderChange);

$moved = $db->query_result($db->pquery(
    "SELECT modifiedtime FROM vtiger_crmentity WHERE crmid = ?", array($m1)), 0, 'modifiedtime');
SpecRunner::assertTrue('TC-BK-024', '更新日時が記録される', !empty($moved) && $moved !== '0000-00-00 00:00:00');

// 移動先の検証
// 「未指定」と「存在しない」はメッセージを区別する
$expectRequired = vtranslate('LBL_BULK_FOLDER_REQUIRED', 'Documents');
$expectNotFound = vtranslate('LBL_BULK_FOLDER_NOT_FOUND', 'Documents');
$messageOf = function ($callback) {
    try { call_user_func($callback); return null; }
    catch (Exception $e) { return $e->getMessage(); }
};
SpecRunner::assertSame('TC-BK-030', '移動先が未指定なら「指定されていません」', $expectRequired,
    $messageOf(function () use ($api, $m1) {
        return bulkInvoke($api, 'moveRecords', bulkRequest(array('records' => array($m1))));
    }));
SpecRunner::assertSame('TC-BK-031', '存在しないフォルダは「見つかりません」', $expectNotFound,
    $messageOf(function () use ($api, $m1) {
        return bulkInvoke($api, 'moveRecords',
            bulkRequest(array('records' => array($m1), 'folderid' => 99999999)));
    }));
SpecRunner::assertThrows('TC-BK-030', '移動先が空文字なら例外',
    function () use ($api, $m1) {
        return bulkInvoke($api, 'moveRecords',
            bulkRequest(array('records' => array($m1), 'folderid' => '')));
    });
SpecRunner::assertThrows('TC-BK-030', '移動先が負数なら例外',
    function () use ($api, $m1) {
        return bulkInvoke($api, 'moveRecords',
            bulkRequest(array('records' => array($m1), 'folderid' => -1)));
    });
SpecRunner::assertThrows('TC-BK-030', '移動先が0なら例外',
    function () use ($api, $m1) {
        return bulkInvoke($api, 'moveRecords',
            bulkRequest(array('records' => array($m1), 'folderid' => 0)));
    });
SpecRunner::assertThrows('TC-BK-031', '存在しないフォルダなら例外',
    function () use ($api, $m1) {
        return bulkInvoke($api, 'moveRecords',
            bulkRequest(array('records' => array($m1), 'folderid' => 99999999)));
    });
SpecRunner::assertSame('TC-BK-031', '例外時にフォルダが変わらない', $folderTo, bulkFolderOf($m1));

// ---------------------------------------------------------------- 権限
SpecRunner::section('4.4 権限（DT-2 / DT-3）');

SpecRunner::assertTrue('TC-BK-040', '管理者は参照できる',
    Documents_FolderPermission::canAccessDocument($m2));

$canDelete = new ReflectionMethod('Documents_BulkAction_Api', 'canDelete');
$canDelete->setAccessible(true);
SpecRunner::assertFalse('TC-BK-041', '存在しないIDは削除対象にしない',
    $canDelete->invoke($api, 99999999));

// 参照できないドキュメントが削除されないこと（権限チェックが働いていること）を確かめる
$restrictedFolder = bulkCreateFolder('RESTRICTED');
$db->pquery("DELETE FROM vtiger_folder_permissions WHERE folderid = ?", array($restrictedFolder));
$restricted = specCreateDocument('BULK_RESTRICTED');
specUpdateNotes($restricted, array('folderid' => $restrictedFolder));
Documents_FolderPermission::clearCache();

// 一般ユーザー（権限行が無いフォルダは参照できない）に切り替える
$generalUserId = (function () {
    $db = PearDatabase::getInstance();
    $r = $db->pquery(
        "SELECT id FROM vtiger_users WHERE is_admin = 'off' AND status = 'Active' AND deleted = 0 LIMIT 1",
        array());
    return ($db->num_rows($r) > 0) ? (int) $db->query_result($r, 0, 'id') : null;
})();

if ($generalUserId !== null) {
    specLoginAsAdmin($generalUserId);// 一般ユーザーとして実行する
    Documents_FolderPermission::clearCache();
    SpecRunner::assertFalse('TC-BK-044', '一般ユーザーは権限の無いドキュメントを参照できない',
        Documents_FolderPermission::canAccessDocument($restricted));
    $result = null;
    try {
        $result = bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($restricted))));
    } catch (Exception $e) {
        $result = array('deleted' => 0, 'denied' => 1);
    }
    SpecRunner::assertSame('TC-BK-044', '権限の無いドキュメントは削除されない', 0, $result['deleted']);
    SpecRunner::assertTrue('TC-BK-044', '対象外として数える', $result['denied'] >= 1);
    SpecRunner::assertFalse('TC-BK-044', 'レコードが残っている', bulkIsDeleted($restricted));

    $moveResult = null;
    try {
        $moveResult = bulkInvoke($api, 'moveRecords',
            bulkRequest(array('records' => array($restricted), 'folderid' => $folderTo)));
    } catch (Exception $e) {
        $moveResult = array('moved' => 0, 'denied' => 1);
    }
    SpecRunner::assertSame('TC-BK-045', '権限の無いドキュメントは移動されない', 0, $moveResult['moved']);
    SpecRunner::assertSame('TC-BK-045', 'フォルダが変わっていない',
        $restrictedFolder, bulkFolderOf($restricted));

    specLoginAsAdmin();// 管理者に戻す
    Documents_FolderPermission::clearCache();
} else {
    SpecRunner::report('TC-BK-044', '一般ユーザーが存在しないためスキップ', true);
}

$canEdit = new ReflectionMethod('Documents_BulkAction_Api', 'canEdit');
$canEdit->setAccessible(true);
SpecRunner::assertFalse('TC-BK-042', '存在しないIDは編集対象にしない',
    $canEdit->invoke($api, 99999999));
SpecRunner::assertTrue('TC-BK-042', '管理者は編集できる', $canEdit->invoke($api, $m2));

$assertFolder = new ReflectionMethod('Documents_BulkAction_Api', 'assertFolderIsWritable');
$assertFolder->setAccessible(true);
SpecRunner::assertNotThrows('TC-BK-043', '管理者は権限行が無いフォルダにも移動できる',
    function () use ($assertFolder, $api, $db) {
        $folderId = bulkCreateFolder('NOPERM');
        $db->pquery("DELETE FROM vtiger_folder_permissions WHERE folderid = ?", array($folderId));
        $assertFolder->invoke($api, $folderId);
    });

// ---------------------------------------------------------------- 応答
SpecRunner::section('4.5 応答（S-06）');

$x = specCreateDocument('BULK_X');
$result = bulkInvoke($api, 'deleteRecords', bulkRequest(array('records' => array($x))));
foreach (array('total', 'deleted', 'denied', 'failed') as $key) {
    SpecRunner::assertTrue('TC-BK-050', "削除の応答に {$key} が含まれる", isset($result[$key]));
}
$y = specCreateDocument('BULK_Y');
$result = bulkInvoke($api, 'moveRecords',
    bulkRequest(array('records' => array($y), 'folderid' => $folderTo)));
foreach (array('total', 'moved', 'denied', 'skipped', 'failed', 'folderid') as $key) {
    SpecRunner::assertTrue('TC-BK-051', "移動の応答に {$key} が含まれる", isset($result[$key]));
}

// 件数の合計が指定件数と一致する
$z1 = specCreateDocument('BULK_Z1');
$z2 = specCreateDocument('BULK_Z2');
$result = bulkInvoke($api, 'moveRecords',
    bulkRequest(array('records' => array($z1, $z2), 'folderid' => $folderTo)));
SpecRunner::assertSame('TC-BK-052', '移動＋対象外＋失敗の合計が指定件数と一致',
    $result['total'], $result['moved'] + $result['denied'] + $result['skipped'] + $result['failed']);

SpecRunner::cleanup();
exit(SpecRunner::summarize('TS-14'));
