<?php

/**
 * 自動採番の同時採番テスト用の作業プロセス
 *
 * 使い方: php autonumber_allocate_worker.php <fieldid> <scope_key> <件数>
 * テスト用 DB に別の接続でつなぎ、連番を件数だけ払い出して、1 行 1 番号で標準出力に出す。
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/include/utils/utils.php';
require_once dirname(__DIR__, 2) . '/modules/Vtiger/utils/AutoNumber.php';

$arguments = array_pad(is_array($_SERVER['argv'] ?? null) ? array_values($_SERVER['argv']) : [], 4, '');
$fieldId = Vtiger_AutoNumberValue::int($arguments[1]);
$scopeKey = Vtiger_AutoNumberValue::string($arguments[2]);
$count = Vtiger_AutoNumberValue::int($arguments[3]);
for ($i = 0; $i < $count; $i++) {
    echo Vtiger_AutoNumber::allocate($fieldId, $scopeKey, 1), "\n";
}
