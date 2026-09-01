<?php

/**
 * 実行権の獲得（FR_CronDispatcher::claim）を複数プロセスから同時に行うためのワーカー。
 *
 * 指定した時刻まで待ってから claim() を 1 回だけ試み、獲得できたかを標準出力へ 1 / 0 で返す。
 * 同じ時刻を指定した複数プロセスを同時に走らせ、成功がちょうど 1 つになることを確認する。
 *
 * 接続先はテスト用DB でなければならない。tests/bootstrap.php を通すことで、
 * テスト本体と同じ判定（末尾 _test の強制）を受けるようにしている。
 *
 * 引数: <タスク名> <claim を試みる時刻（UNIX時間・小数可）> [ホスト名]
 */

// bootstrap は必ず関数の中で読み込む。理由は run_vtigercron.php のコメントを参照。
(static function (): void {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
})();

$projectRoot = dirname(__DIR__, 3);
require_once $projectRoot . '/include/utils/CommonUtils.php';
require_once $projectRoot . '/vtlib/Vtiger/Cron.php';
require_once $projectRoot . '/include/utils/CronDispatcher.php';

if ($argc < 3) {
    fwrite(STDERR, "usage: claim_worker.php <task-name> <start-at> [host-name]\n");
    exit(2);
}

$taskName = $argv[1];
$startAt = (float) $argv[2];
if (isset($argv[3]) && $argv[3] !== '') {
    // 複数サーバーからの競合を再現する場合はホスト名を差し替える
    $GLOBALS['cron_host_name'] = $argv[3];
}

$cronTask = Vtiger_Cron::getInstance($taskName);
if ($cronTask === false) {
    fwrite(STDERR, "task not found: $taskName\n");
    exit(2);
}

// DB 接続の確立やクラスの読み込みで生じる差をここで吸収し、
// claim() の呼び出し自体が揃うようにする
while (microtime(true) < $startAt) {
    usleep(200);
}

echo FR_CronDispatcher::claim($cronTask) ? '1' : '0';
