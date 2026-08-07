<?php
/**
 * テスト仕様書（docs/tests/Documents）の自動テストを一括実行する
 *
 * Usage:
 *   php test/unit/documents/spec/run_all.php
 *
 * 終了コード: 失敗したスクリプト数（0 なら全て成功）
 */

$scripts = array(
    'spec_TS01_business_day.php' => 'TS-01 営業日・休祝日',
    'spec_TS03_deadline.php'     => 'TS-03 入力期限',
    'spec_TS05_compliance.php'   => 'TS-04/TS-05 電帳法・監査ログ',
    'spec_TS06_upload.php'       => 'TS-06/TS-09 アップロード・ファイル処理',
    'spec_TS08_api.php'          => 'TS-08/TS-13 API・関連付け',
);

$dir = dirname(__FILE__);
$failedScripts = 0;
$summaries = array();

foreach ($scripts as $script => $title) {
    echo "\n" . str_repeat('#', 62) . "\n# {$title}\n" . str_repeat('#', 62) . "\n";
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/' . $script);
    $output = array();
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    $text = implode("\n", $output);
    echo $text . "\n";

    if (preg_match('/: (\d+)件中 (\d+)件成功 \/ (\d+)件失敗/u', $text, $m)) {
        $summaries[$title] = array('total' => (int) $m[1], 'ok' => (int) $m[2], 'ng' => (int) $m[3]);
    } else {
        $summaries[$title] = array('total' => 0, 'ok' => 0, 'ng' => -1);
    }
    if ($exitCode !== 0) {
        $failedScripts++;
    }
}

echo "\n" . str_repeat('=', 62) . "\n";
echo "総合結果\n";
echo str_repeat('=', 62) . "\n";
$total = 0;
$ok = 0;
$ng = 0;
foreach ($summaries as $title => $s) {
    if ($s['ng'] < 0) {
        printf("  %-40s 実行エラー\n", $title);
        continue;
    }
    printf("  %-40s %3d件中 %3d件成功 / %d件失敗\n", $title, $s['total'], $s['ok'], $s['ng']);
    $total += $s['total'];
    $ok += $s['ok'];
    $ng += $s['ng'];
}
echo str_repeat('-', 62) . "\n";
printf("  %-40s %3d件中 %3d件成功 / %d件失敗\n", '合計', $total, $ok, $ng);
echo str_repeat('=', 62) . "\n";

exit($failedScripts);
