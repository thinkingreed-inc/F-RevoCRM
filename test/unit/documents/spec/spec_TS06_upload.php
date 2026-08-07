<?php
/**
 * TS-06 / TS-09 アップロードのサイズ計算・ダウンロードヘッダー・テキスト抽出 自動テスト
 *
 * 対応する仕様書:
 *   docs/tests/Documents/TS-06_分割アップロード.md
 *   docs/tests/Documents/TS-09_テキスト抽出とプレビュー.md
 *
 * Usage:
 *   php test/unit/documents/spec/spec_TS06_upload.php
 */

require_once dirname(__FILE__) . '/bootstrap.php';
require_once 'modules/Documents/utils/ChunkUploadStore.php';
require_once 'modules/Documents/utils/TextExtractor.php';
require_once 'modules/Documents/utils/FileHasher.php';

echo "=== TS-06 / TS-09 アップロード・ファイル処理 ===\n";

$tmpDir = sys_get_temp_dir() . '/spec_documents_' . getmypid();
@mkdir($tmpDir, 0777, true);
SpecRunner::addCleanup(function () use ($tmpDir) {
    foreach (glob($tmpDir . '/*') as $file) { @unlink($file); }
    @rmdir($tmpDir);
});

// ---------------------------------------------------------------- サイズ計算
SpecRunner::section('TS-06 4.1 サイズ計算（BV-1〜BV-4 / Q-13）');

/**
 * 実効上限を差し替えて chunk size を求める
 * （getEffectiveMaxUploadSizeInBytes は ini に依存するため、同じ計算式を検証する）
 */
class SpecChunkSize extends Documents_Module_Model {
    public static $limit = 0;
    public static function getEffectiveMaxUploadSizeInBytes() { return self::$limit; }
}

$expected = array(
    0 => 8388608,
    65536 => 52428,
    100000 => 65536,
    262144 => 196608,
    327680 => 65536,
    2097152 => 1835008,
    8388608 => 8126464,
    2147483648 => 2147221504,
);
foreach ($expected as $limit => $want) {
    SpecChunkSize::$limit = $limit;
    $actual = SpecChunkSize::getChunkSizeInBytes();
    SpecRunner::assertSame('TC-CU-002', "上限 {$limit} → チャンク {$want}", $want, $actual);
    if ($limit > 0) {
        SpecRunner::assertTrue('TC-CU-009b', "上限 {$limit}: チャンクが上限未満",
            $actual < $limit, "chunk={$actual} limit={$limit}");
    }
}

// ラベル
$labels = array(0 => '', -1 => '', 1024 => '1 KB', 1048576 => '1 MB',
    2097152 => '2 MB', 1073741824 => '1 GB', 2147483648 => '2 GB');
foreach ($labels as $bytes => $want) {
    SpecRunner::assertSame('TC-CU-006', "{$bytes} バイトの表示",
        $want, Documents_Module_Model::getEffectiveMaxUploadSizeLabel($bytes));
}

// 分割アップロードの上限（config 由来）
$configured = Documents_Module_Model::getChunkUploadMaxSizeInBytes();
SpecRunner::assertTrue('TC-CU-007', '1ファイルの上限が正の値', $configured > 0, (string) $configured);

// ---------------------------------------------------------------- 一時ファイル
SpecRunner::section('TS-06 4.2 分割アップロードの一時ファイル（DT-1 / DT-2）');

$userId = 1;
$content = str_repeat('0123456789', 200);// 2000バイト
$size = strlen($content);

$created = Documents_ChunkUploadStore::create('spec.txt', 'text/plain', $size, $userId);
$uploadId = $created['upload_id'];
SpecRunner::addCleanup(function () use ($uploadId) { Documents_ChunkUploadStore::delete($uploadId); });
SpecRunner::assertTrue('TC-CU-010', 'upload_id が32桁の16進',
    (bool) preg_match('/^[0-9a-f]{32}$/', $uploadId), $uploadId);

/** チャンクを一時ファイルにして追記する */
function specAppendChunk($uploadId, $index, $data, $userId, $tmpDir) {
    $path = $tmpDir . '/chunk_' . $index;
    file_put_contents($path, $data);
    $result = Documents_ChunkUploadStore::appendChunk($uploadId, $index, $path, $userId);
    unlink($path);
    return $result;
}

$half = (int) ($size / 2);
$r1 = specAppendChunk($uploadId, 0, substr($content, 0, $half), $userId, $tmpDir);
SpecRunner::assertSame('TC-CU-011', '1つ目の受信バイト数', $half, $r1['received_bytes']);
SpecRunner::assertFalse('TC-CU-011', '途中では completed=false', $r1['completed']);
$r2 = specAppendChunk($uploadId, 1, substr($content, $half), $userId, $tmpDir);
SpecRunner::assertSame('TC-CU-011', '全部受信すると completed=true', true, $r2['completed']);

$file = Documents_ChunkUploadStore::getAssembledFile($uploadId, $userId);
SpecRunner::assertSame('TC-CU-012', '結合後のサイズが一致', $size, $file['size']);
SpecRunner::assertSame('TC-CU-012', '結合後の内容がバイト一致', $content, file_get_contents($file['tmp_name']));
SpecRunner::assertSame('TC-CU-012', 'error は UPLOAD_ERR_OK', UPLOAD_ERR_OK, $file['error']);

Documents_ChunkUploadStore::delete($uploadId);
SpecRunner::assertFalse('TC-CU-013', '削除後はファイルが残らない', file_exists($file['tmp_name']));
SpecRunner::assertNotThrows('TC-CU-018', '存在しないIDの削除は何もしない',
    function () { Documents_ChunkUploadStore::delete('0123456789abcdef0123456789abcdef'); });

// 異常系
SpecRunner::assertThrows('TC-CU-020', 'サイズ0は例外',
    function () { return Documents_ChunkUploadStore::create('a.txt', 'text/plain', 0, 1); });
SpecRunner::assertThrows('TC-CU-020', 'サイズが負数は例外',
    function () { return Documents_ChunkUploadStore::create('a.txt', 'text/plain', -1, 1); });
SpecRunner::assertThrows('TC-CU-021', '上限超過は例外',
    function () { return Documents_ChunkUploadStore::create(
        'a.txt', 'text/plain', Documents_Module_Model::getChunkUploadMaxSizeInBytes() + 1, 1); });

// パストラバーサル・形式不正
foreach (array('../../etc', 'ABCDEF0123456789ABCDEF0123456789', '0123456789abcdef', '') as $badId) {
    SpecRunner::assertThrows('TC-CU-030', "不正な upload_id '{$badId}' は例外",
        function () use ($badId, $tmpDir) {
            return specAppendChunk($badId, 0, 'x', 1, $tmpDir);
        });
}

// 他ユーザーからは触れない
$created = Documents_ChunkUploadStore::create('spec2.txt', 'text/plain', 100, 1);
$otherId = $created['upload_id'];
SpecRunner::addCleanup(function () use ($otherId) { Documents_ChunkUploadStore::delete($otherId); });
SpecRunner::assertThrows('TC-CU-031', '他ユーザーは追記できない',
    function () use ($otherId, $tmpDir) { return specAppendChunk($otherId, 0, 'x', 999, $tmpDir); });
SpecRunner::assertThrows('TC-CU-031', '他ユーザーは結合結果を取得できない',
    function () use ($otherId) { return Documents_ChunkUploadStore::getAssembledFile($otherId, 999); });

// 順序・サイズ超過
SpecRunner::assertThrows('TC-CU-032', '順番が違うチャンクは例外',
    function () use ($otherId, $tmpDir) { return specAppendChunk($otherId, 1, 'x', 1, $tmpDir); });
specAppendChunk($otherId, 0, str_repeat('a', 50), 1, $tmpDir);
SpecRunner::assertThrows('TC-CU-032b', '同じ index の再送は例外',
    function () use ($otherId, $tmpDir) { return specAppendChunk($otherId, 0, 'x', 1, $tmpDir); });
SpecRunner::assertThrows('TC-CU-033', '宣言サイズを超える追記は例外',
    function () use ($otherId, $tmpDir) {
        return specAppendChunk($otherId, 1, str_repeat('a', 100), 1, $tmpDir);
    });
SpecRunner::assertThrows('TC-CU-034', '未完了の結合取得は例外',
    function () use ($otherId) { return Documents_ChunkUploadStore::getAssembledFile($otherId, 1); });
Documents_ChunkUploadStore::delete($otherId);

// 1バイトのファイル
$created = Documents_ChunkUploadStore::create('one.txt', 'text/plain', 1, 1);
$oneId = $created['upload_id'];
specAppendChunk($oneId, 0, 'x', 1, $tmpDir);
$file = Documents_ChunkUploadStore::getAssembledFile($oneId, 1);
SpecRunner::assertSame('TC-CU-014', '1バイトのファイルを結合できる', 'x', file_get_contents($file['tmp_name']));
Documents_ChunkUploadStore::delete($oneId);

// ---------------------------------------------------------------- ハッシュ
SpecRunner::section('TS-05 4.2 ファイルハッシュ（BV-5）');

$emptyFile = $tmpDir . '/empty.bin';
file_put_contents($emptyFile, '');
SpecRunner::assertSame('TC-FH-003', '0バイトのファイルもハッシュを計算できる',
    'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    Documents_FileHasher::calculateHash($emptyFile));

$fileA = $tmpDir . '/a.bin';
$fileB = $tmpDir . '/b.bin';
file_put_contents($fileA, 'hello');
file_put_contents($fileB, 'hello');
SpecRunner::assertSame('TC-FH-004', '同じ内容なら同じハッシュ',
    Documents_FileHasher::calculateHash($fileA), Documents_FileHasher::calculateHash($fileB));
file_put_contents($fileB, 'hellO');
SpecRunner::assertTrue('TC-FH-005', '1バイト違えばハッシュが変わる',
    Documents_FileHasher::calculateHash($fileA) !== Documents_FileHasher::calculateHash($fileB));
SpecRunner::assertSame('TC-FH-002', 'ハッシュは64桁',
    64, strlen(Documents_FileHasher::calculateHash($fileA)));
SpecRunner::assertFalse('TC-FH-012', '存在しないファイルは false',
    Documents_FileHasher::calculateHash($tmpDir . '/nothing.bin'));

// ---------------------------------------------------------------- テキスト抽出
SpecRunner::section('TS-09 4.1 テキスト抽出（DT-1 / BV-1 / Q-21）');

$txt = $tmpDir . '/sample.txt';
file_put_contents($txt, "契約書  \n\n  サンプル\t本文");
SpecRunner::assertSame('TC-TX-005', 'テキストを抽出して正規化する',
    '契約書 サンプル 本文', Documents_TextExtractor::extract($txt, 'text/plain', 'sample.txt'));

$html = $tmpDir . '/sample.html';
file_put_contents($html, '<html><body><p>本文テキスト</p></body></html>');
SpecRunner::assertSame('TC-TX-005b', 'HTMLはタグを除去する',
    '本文テキスト', Documents_TextExtractor::extract($html, 'text/html', 'sample.html'));

SpecRunner::assertSame('TC-TX-006', '未対応形式は null',
    null, Documents_TextExtractor::extract($txt, 'application/zip', 'sample.zip'));
SpecRunner::assertSame('TC-TX-020', '存在しないファイルは null',
    null, Documents_TextExtractor::extract($tmpDir . '/nothing.txt', 'text/plain', 'nothing.txt'));

$long = $tmpDir . '/long.txt';
file_put_contents($long, str_repeat('あ', 1000100));
$extracted = Documents_TextExtractor::extract($long, 'text/plain', 'long.txt');
SpecRunner::assertTrue('TC-TX-009', '1,000,000文字で切り詰める',
    mb_strlen($extracted) <= 1000000, '実際: ' . mb_strlen($extracted));

$emptyTxt = $tmpDir . '/empty.txt';
file_put_contents($emptyTxt, '');
SpecRunner::assertSame('TC-TX-010', '空ファイルは空文字（null ではない）',
    '', Documents_TextExtractor::extract($emptyTxt, 'text/plain', 'empty.txt'));

$broken = $tmpDir . '/broken.docx';
file_put_contents($broken, 'this is not a zip');
SpecRunner::assertSame('TC-TX-021', '壊れたZIPは null（例外にしない）',
    null, Documents_TextExtractor::extract($broken, '', 'broken.docx'));

// 連番が飛んだ XLSX / PPTX（Q-21）
if (class_exists('ZipArchive')) {
    $xlsx = $tmpDir . '/gap.xlsx';
    $zip = new ZipArchive();
    $zip->open($xlsx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('xl/worksheets/sheet1.xml', '<w><t>SHEET_ONE</t></w>');
    $zip->addFromString('xl/worksheets/sheet3.xml', '<w><t>SHEET_THREE</t></w>');
    $zip->close();
    $text = Documents_TextExtractor::extract($xlsx, '', 'gap.xlsx');
    SpecRunner::assertTrue('TC-TX-011b', 'sheet1 を抽出', strpos($text, 'SHEET_ONE') !== false, $text);
    SpecRunner::assertTrue('TC-TX-011b', 'sheet3 も抽出（連番が飛んでも打ち切らない）',
        strpos($text, 'SHEET_THREE') !== false, $text);

    $pptx = $tmpDir . '/gap.pptx';
    $zip = new ZipArchive();
    $zip->open($pptx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('ppt/slides/slide1.xml', '<p><t>SLIDE_ONE</t></p>');
    $zip->addFromString('ppt/slides/slide5.xml', '<p><t>SLIDE_FIVE</t></p>');
    $zip->close();
    $text = Documents_TextExtractor::extract($pptx, '', 'gap.pptx');
    SpecRunner::assertTrue('TC-TX-012b', 'slide1 を抽出', strpos($text, 'SLIDE_ONE') !== false, $text);
    SpecRunner::assertTrue('TC-TX-012b', 'slide5 も抽出（連番が飛んでも打ち切らない）',
        strpos($text, 'SLIDE_FIVE') !== false, $text);

    // 25シート（旧実装の20シート上限を超える）
    $many = $tmpDir . '/many.xlsx';
    $zip = new ZipArchive();
    $zip->open($many, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    for ($i = 1; $i <= 25; $i++) {
        $zip->addFromString("xl/worksheets/sheet{$i}.xml", "<w><t>SHEET_{$i}_MARK</t></w>");
    }
    $zip->close();
    $text = Documents_TextExtractor::extract($many, '', 'many.xlsx');
    SpecRunner::assertTrue('TC-TX-011', '25シート目まで抽出する',
        strpos($text, 'SHEET_25_MARK') !== false, mb_substr($text, 0, 120));
} else {
    SpecRunner::report('TC-TX-011', 'ZipArchive が無いためスキップ', true);
}

// ---------------------------------------------------------------- ダウンロードヘッダー
SpecRunner::section('TS-09 4.3 ダウンロード（BV-4 / BV-5 / Q-23）');

SpecRunner::assertFalse('TC-DL2-008', '存在しないパスは false',
    Documents_Record_Model::streamFile($tmpDir . '/nothing.bin', 'x.bin'));

/**
 * streamFile が出すヘッダーを取得する（CLI では headers_list() が使えないため
 * 別プロセスの CLI サーバではなく、生成規則を同じ実装で確認する）
 */
$reflection = new ReflectionClass('Documents_Record_Model');
$method = $reflection->getMethod('buildContentDisposition');
$method->setAccessible(true);

$header = $method->invoke(null, 'report.pdf');
SpecRunner::assertSame('TC-DL2-006', '通常のファイル名',
    'filename="report.pdf"; filename*=UTF-8\'\'report.pdf', $header);

$header = $method->invoke(null, 'a"b.pdf');
SpecRunner::assertTrue('TC-DL2-006b', '二重引用符を除去する',
    strpos($header, 'filename="ab.pdf"') === 0, $header);

$header = $method->invoke(null, "a\r\nX-Evil: 1.pdf");
SpecRunner::assertTrue('TC-DL2-006c', '改行を除去する（ヘッダーインジェクション防止）',
    strpos($header, "\r") === false && strpos($header, "\n") === false, $header);

$header = $method->invoke(null, 'テスト資料.pdf');
SpecRunner::assertTrue('TC-DL2-006', 'マルチバイトは filename* に percent-encoding',
    strpos($header, "filename*=UTF-8''%E3%83%86") !== false, $header);

$header = $method->invoke(null, '"\\');
SpecRunner::assertTrue('TC-DL2-006d', '除去後に空ならフォールバックする',
    strpos($header, 'filename="download"') === 0, $header);

$sanitize = $reflection->getMethod('sanitizeHeaderValue');
$sanitize->setAccessible(true);
$sanitized = $sanitize->invoke(null, "text/plain\r\nX-Evil: 1");
SpecRunner::assertTrue('TC-DL2-006c', 'MIMEタイプから改行を除去する（別ヘッダーを作らせない）',
    strpos($sanitized, "\r") === false && strpos($sanitized, "\n") === false, $sanitized);

/**
 * streamFile は出力バッファを閉じてから送出するため、同一プロセスでは
 * ob_get_clean() で受け取れない。別プロセスで実行して標準出力を確認する。
 */
function specRunStreamFile($path, $downloadName, $tmpDir) {
    $probe = $tmpDir . '/stream_probe.php';
    $root = getcwd();
    file_put_contents($probe, '<?php' . "\n"
        . 'chdir(' . var_export($root, true) . ');' . "\n"
        . "require_once 'config.php';\n"
        . "require_once 'include/utils/utils.php';\n"
        . "require_once 'include/database/PearDatabase.php';\n"
        . "vimport('includes.runtime.Globals');\n"
        . "vimport('includes.runtime.BaseModel');\n"
        . "require_once 'modules/Documents/models/Record.php';\n"
        . '$ok = Documents_Record_Model::streamFile('
        . var_export($path, true) . ', ' . var_export($downloadName, true) . ');' . "\n"
        . 'file_put_contents("php://stderr", $ok ? "RETURN:true" : "RETURN:false");' . "\n");

    $descriptors = array(1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
    $process = proc_open(PHP_BINARY . ' ' . escapeshellarg($probe), $descriptors, $pipes);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    unlink($probe);
    return array('stdout' => $stdout, 'returned' => strpos($stderr, 'RETURN:true') !== false);
}

$streamFile = $tmpDir . '/stream.bin';
file_put_contents($streamFile, str_repeat('x', 1024));
$run = specRunStreamFile($streamFile, 'stream.bin', $tmpDir);
SpecRunner::assertTrue('TC-DL2-003', 'ファイルを出力して true を返す', $run['returned']);
SpecRunner::assertSame('TC-DL2-003', '内容が欠けない（1024バイト）', 1024, strlen($run['stdout']));
SpecRunner::assertSame('TC-DL2-003', '内容が一致する', str_repeat('x', 1024), $run['stdout']);

file_put_contents($streamFile, '');
$run = specRunStreamFile($streamFile, 'empty.bin', $tmpDir);
SpecRunner::assertTrue('TC-DL2-004', '0バイトでも true を返す', $run['returned']);
SpecRunner::assertSame('TC-DL2-004', '0バイトのファイルは空を出力（エラーにしない）', '', $run['stdout']);

$run = specRunStreamFile($tmpDir . '/nothing.bin', 'x.bin', $tmpDir);
SpecRunner::assertFalse('TC-DL2-008', '存在しないパスは何も出力せず false',
    $run['returned'], $run['stdout']);
SpecRunner::assertSame('TC-DL2-008', '存在しないパスでは出力しない', '', $run['stdout']);

SpecRunner::cleanup();
exit(SpecRunner::summarize('TS-06 / TS-09'));
