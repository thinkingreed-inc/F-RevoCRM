<?php

/**
 * 繰り返し請求 cron のテスト用に、受注を通常の保存処理で作成する子プロセス。
 *
 * 受注の保存は vtiger 本体を丸ごと読み込む必要があり、PHPUnit のプロセス内で行うと
 * 本体側の警告やクラス定義がテストへ混ざるため、別プロセスで実行する。
 *
 *   php tests/Support/salesorder/create_salesorders.php <件数> <件名の接頭辞>
 *
 * 標準出力の最終行に、作成した受注 ID を作成順（ID 昇順）の JSON 配列で出力する。
 * 取引先も 1 件作成し、受注はすべてその取引先に紐付ける。
 */

// tests/bootstrap.php を関数の中で読み込む理由は run_vtigercron.php と同じ
// （ファイルスコープで読むと、本体が config.inc.php を読み直して開発用DB を指してしまう）
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

$count = (int) ($argv[1] ?? 0);
$prefix = (string) ($argv[2] ?? 'FRTestRecurring_');
if ($count < 1) {
    fwrite(STDERR, "usage: create_salesorders.php <count> <prefix>\n");
    exit(1);
}

// vglobal() も $GLOBALS へ入れるだけなので、ここでは直接入れる
$current_user = Users::getActiveAdminUser();
$GLOBALS['current_user'] = $current_user;

$account = Vtiger_Record_Model::getCleanInstance('Accounts');
$account->set('accountname', $prefix . 'Account');
$account->set('assigned_user_id', $current_user->id);
$account->set('mode', '');
$account->save();

$ids = [];
for ($i = 1; $i <= $count; $i++) {
    $salesOrder = Vtiger_Record_Model::getCleanInstance('SalesOrder');
    $salesOrder->set('subject', $prefix . $i);
    $salesOrder->set('account_id', $account->getId());
    $salesOrder->set('sostatus', 'Created');
    $salesOrder->set('invoicestatus', 'AutoCreated');
    $salesOrder->set('bill_street', 'test');
    $salesOrder->set('ship_street', 'test');
    $salesOrder->set('assigned_user_id', $current_user->id);
    $salesOrder->set('mode', '');
    $salesOrder->save();
    $ids[] = (int) $salesOrder->getId();
}

echo "\n" . json_encode(['account' => (int) $account->getId(), 'salesorders' => $ids]) . "\n";
