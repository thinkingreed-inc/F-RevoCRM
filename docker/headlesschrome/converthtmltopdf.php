<?php
$filepath = $_POST['filepath'];
$export_mode = $_POST['export_mode'];
$add_pagenumber = $_POST['add_pagenumber'];
$logFilename = "headlesschrome" . date("Ymd") . ".log";
if ($filepath) {
    $output = null;
    $retval = null;
    $max_loop_count = 100; // 最大ループ数
    // 白紙事象の対応の為、PDFのサイズが10kb未満の場合は再度変換処理を行う。最大20回までとする。
    for ($i = 0; $i < $max_loop_count; $i++) {
        // htmlからPDF変換
        exec("/usr/bin/google-chrome --headless --no-sandbox --disable-setuid-sandbox --disable-software-rasterizer --disable-gpu --allow-file-access-from-files --virtual-time-budget=9999999 --run-all-compositor-stages-before-draw --print-to-pdf-no-header --print-to-pdf=" . $filepath . ".pdf" . " " . $filepath . ".html 2>&1", $output, $retval);
        if ($retval != 0) {
            error_log(date("Y/m/d H:i:s") . "." . substr(explode(".", (microtime(true) . ""))[1], 0, 3) . ", output:" . __LINE__ . print_r($output, true) . ", retval:" . $retval . PHP_EOL, 3, $logFilename);
        }
        // ファイルサイズが10kbより大きい場合はループを抜ける。10kb以下の場合は再度処理を行う。
        if (filesize($filepath . ".pdf") > 10240) {
            break;
        }
        error_log(date("Y/m/d H:i:s") . "." . substr(explode(".", (microtime(true) . ""))[1], 0, 3) . ", " . __LINE__ . ", loopcount:" . ($i + 1) . PHP_EOL, 3, $logFilename);
    }
    exec("chmod a+r " . $filepath . ".pdf 2>&1", $output, $retval);
    if ($retval != 0) {
        error_log(date("Y/m/d H:i:s") . "." . substr(explode(".", (microtime(true) . ""))[1], 0, 3) . ", output:" . __LINE__ . print_r($output, true) . ", retval:" . $retval . PHP_EOL, 3, $logFilename);
    }

    if($add_pagenumber == true){
        // ページ番号を付与する
        exec("/usr/bin/python3 /var/www/html/addPageNumberToPdf.py $filepath" . ".pdf 2>&1", $output, $retval);
        if ($retval != 0) {
            error_log(date("Y/m/d H:i:s") . "." . substr(explode(".", (microtime(true) . ""))[1], 0, 3) . ", output:" . __LINE__ . print_r($output, true) . ", retval:" . $retval . PHP_EOL, 3, $logFilename);
        }
    }
}