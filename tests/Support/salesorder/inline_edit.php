<?php

/**
 * 受注のインライン編集をテスト用DB で行う子プロセス。
 *
 * 受注の保存・詳細画面の構造は vtiger 本体を丸ごと読み込む必要があり、PHPUnit のプロセス内で
 * 行うと本体側の警告やクラス定義がテストへ混ざるため、別プロセスで実行する。
 *
 *   php tests/Support/salesorder/inline_edit.php save <受注ID> <項目名> <値>
 *     詳細画面・一覧画面のインライン編集と同じパラメータ（field / value）で
 *     SalesOrder_SaveAjax_Action::process() を呼び、応答（JSON）を出力する。
 *
 *   php tests/Support/salesorder/inline_edit.php listsave <受注ID> <項目名=値の JSON>
 *     一覧画面のインライン編集と同じく、field を付けずに入力欄のある列の値をまとめて送って
 *     SalesOrder_SaveAjax_Action::process() を呼び、応答（JSON）を出力する。
 *     入力欄の無い列（インライン編集できない項目）は List.js の jQuery.extend で落ちるため送られない。
 *
 *   php tests/Support/salesorder/inline_edit.php editable <受注ID>
 *     詳細画面の構造（SalesOrder_DetailRecordStructure_Model）を組み立て、
 *     繰り返し請求ブロックの項目ごとにインライン編集できるかを JSON で出力する。
 *     キー "list:enable_recurring" は一覧画面と同じく、受注の状態を持たない項目モデルでの結果。
 *
 * 結果は標準出力の最終行に出力する。
 */

// tests/bootstrap.php を関数の中で読み込む理由は tests/Support/cron/run_vtigercron.php と同じ
(static function (): void {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
})();

require_once 'includes/main/WebUI.php';

// tests/bootstrap.php が外した Vtiger_Loader のオートロードを、このプロセスでは戻す
spl_autoload_register(['Vtiger_Loader', 'autoLoad']);

$adb = PearDatabase::getInstance();
if (!preg_match('/_test$/', (string) $adb->dbName)) {
    fwrite(STDERR, "FATAL: テスト用DB 以外 ({$adb->dbName}) に接続しているため中止します。\n");
    exit(1);
}

$mode = (string) ($argv[1] ?? '');
$recordId = (int) ($argv[2] ?? 0);
if (!in_array($mode, ['save', 'listsave', 'editable'], true) || $recordId < 1) {
    fwrite(STDERR, "usage: inline_edit.php save <record> <field> <value> | listsave <record> <json> | editable <record>\n");
    exit(1);
}

$current_user = Users::getActiveAdminUser();
$GLOBALS['current_user'] = $current_user;

if ($mode === 'save' || $mode === 'listsave') {
    $base = ['module' => 'SalesOrder', 'action' => 'SaveAjax', 'record' => $recordId];
    if ($mode === 'save') {
        $values = $base + ['field' => (string) ($argv[3] ?? ''), 'value' => (string) ($argv[4] ?? '')];
    } else {
        $columns = json_decode((string) ($argv[3] ?? ''), true);
        if (!is_array($columns)) {
            fwrite(STDERR, "listsave: 項目名=値の JSON を指定してください\n");
            exit(1);
        }
        $values = $base + $columns;
    }
    $_REQUEST = $values;
    $request = new Vtiger_Request($values, $values);
    $action = new SalesOrder_SaveAjax_Action();
    ob_start();
    $action->process($request);
    $response = (string) ob_get_clean();
    // エラー応答は整形された複数行の JSON になるため、1 行にまとめ直して出力する
    echo "\n" . json_encode(json_decode($response, true), JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

// 一覧画面の項目モデルは受注ごとの状態を持たない。一覧画面と同じくモジュールから取得し、
// 詳細画面の構造（同じ項目モデルに状態を付ける）を組み立てる前に判定する
$result = [];
$listFieldModel = Vtiger_Module_Model::getInstance('SalesOrder')->getField('enable_recurring');
$result['list:enable_recurring'] = (bool) $listFieldModel->isAjaxEditable();

$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'SalesOrder');
$structure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
foreach ($structure->getStructure() as $blockFields) {
    foreach ($blockFields as $fieldName => $fieldModel) {
        if ($fieldModel->block && $fieldModel->block->label === 'Recurring Invoice Information') {
            $result[$fieldName] = (bool) $fieldModel->isAjaxEditable();
        }
    }
}

echo "\n" . json_encode($result) . "\n";
