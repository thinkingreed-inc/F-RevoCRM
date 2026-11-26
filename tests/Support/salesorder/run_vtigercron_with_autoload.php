<?php

/**
 * 実物の cron ハンドラ（.service）をテスト用DB で実行するための入口。
 *
 * tests/Support/cron/run_vtigercron.php はスタブのハンドラを前提にしており、
 * tests/bootstrap.php が外した Vtiger_Loader のオートロードを戻さない。
 * RecurringInvoice.service のように本体のクラス（CRMEntity → Vtiger_Module_Model 等）を
 * 使うハンドラはオートロードが無いと動かないため、ここで戻してから同じ入口へ渡す。
 *
 *   php tests/Support/salesorder/run_vtigercron_with_autoload.php -- --service=RecurringInvoice
 */

// bootstrap を関数の中で読み込む理由は run_vtigercron.php と同じ。
// 先に読み込んでおくことで、run_vtigercron.php 側の require_once では再実行されず、
// ここで戻したオートロードが外されない。
(static function (): void {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
})();

require_once dirname(__DIR__, 3) . '/includes/Loader.php';
spl_autoload_register(['Vtiger_Loader', 'autoLoad']);

require dirname(__DIR__) . '/cron/run_vtigercron.php';
