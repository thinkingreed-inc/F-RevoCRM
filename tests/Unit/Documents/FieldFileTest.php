<?php

namespace Tests\Unit\Documents;

use Documents_FieldFile;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/tests/Support/FieldFileTestStubs.php';
require_once dirname(__DIR__, 3) . '/modules/Documents/utils/FieldFile.php';

/**
 * ファイルアップロード項目（uitype 998）の DB を使わない処理
 */
final class FieldFileTest extends TestCase
{
    /** テストで作った一時ファイル */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    public function test_項目の値をドキュメントIDとして解釈する(): void
    {
        $this->assertSame(12, Documents_FieldFile::normalizeDocumentId('12'));
        $this->assertSame(12, Documents_FieldFile::normalizeDocumentId(' 12 '));
        $this->assertSame(12, Documents_FieldFile::normalizeDocumentId(12));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId(''));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId('0'));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId(0));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId(-3));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId(null));
        // インライン編集で表示用のファイル名が送られても、別のドキュメントを指さない
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId('red.png'));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId('12abc'));
        $this->assertSame(0, Documents_FieldFile::normalizeDocumentId('1.5'));
    }

    public function test_サムネイルを作るのはSVGを除く画像だけ(): void
    {
        foreach (['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'IMAGE/PNG'] as $mime) {
            $this->assertTrue(Documents_FieldFile::isThumbnailMime($mime), $mime);
        }
        foreach (['image/svg+xml', 'application/pdf', 'text/plain', '', 'image/bmp'] as $mime) {
            $this->assertFalse(Documents_FieldFile::isThumbnailMime($mime), $mime);
        }
    }

    public function test_縮小後の大きさは長辺を上限に合わせ縦横比を保つ(): void
    {
        $this->assertSame([320, 160], Documents_FieldFile::fitSize(800, 400, 320));
        $this->assertSame([160, 320], Documents_FieldFile::fitSize(400, 800, 320));
        $this->assertSame([320, 320], Documents_FieldFile::fitSize(1000, 1000, 320));
        // 上限より小さい画像は拡大しない
        $this->assertSame([100, 50], Documents_FieldFile::fitSize(100, 50, 320));
        // 極端に細長い画像でも 0 にはしない
        $this->assertSame([320, 1], Documents_FieldFile::fitSize(10000, 1, 320));
    }

    public function test_PNG判定はファイルの中身で行う(): void
    {
        $png = $this->writeTemp('png', $this->pngBinary(4, 3));
        $this->assertTrue(Documents_FieldFile::isPngFile($png));

        // 拡張子だけ PNG のテキストは PNG とみなさない
        $fake = $this->writeTemp('png', 'not a png');
        $this->assertFalse(Documents_FieldFile::isPngFile($fake));

        // 先頭だけ PNG の署名で中身が壊れているものも通さない
        $broken = $this->writeTemp('png', "\x89PNG\r\n\x1a\n" . 'broken');
        $this->assertFalse(Documents_FieldFile::isPngFile($broken));

        $this->assertFalse(Documents_FieldFile::isPngFile(''));
        $this->assertFalse(Documents_FieldFile::isPngFile('/nonexistent/file.png'));
    }

    public function test_手書き項目にはPNG以外を登録できない(): void
    {
        $text = $this->writeTemp('png', 'not a png');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('LBL_FILE_FIELD_HANDWRITING_PNG_ONLY');
        Documents_FieldFile::assertHandwritingImage(['tmp_name' => $text]);
    }

    public function test_画像から縮小したPNGのdata_URIを作る(): void
    {
        if (!function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('GD が無い環境では縮小画像を作らない');
        }
        $png = $this->writeTemp('png', $this->pngBinary(800, 400));
        $thumbnail = Documents_FieldFile::buildThumbnail($png, 320);

        $this->assertNotNull($thumbnail);
        $this->assertSame(320, $thumbnail['width']);
        $this->assertSame(160, $thumbnail['height']);
        $this->assertSame('image/png', $thumbnail['mime']);
        $this->assertStringStartsWith('data:image/png;base64,', $thumbnail['data_uri']);

        $decoded = base64_decode(substr($thumbnail['data_uri'], strlen('data:image/png;base64,')), true);
        $this->assertNotFalse($decoded);
        $size = getimagesizefromstring($decoded);
        $this->assertSame([320, 160], [$size[0], $size[1]]);
    }

    public function test_画像でないファイルや存在しないファイルからはサムネイルを作らない(): void
    {
        $this->assertNull(Documents_FieldFile::buildThumbnail($this->writeTemp('txt', 'hello')));
        $svg = $this->writeTemp('svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->assertNull(Documents_FieldFile::buildThumbnail($svg));
        $this->assertNull(Documents_FieldFile::buildThumbnail('/nonexistent/file.png'));
        $this->assertNull(Documents_FieldFile::buildThumbnail(''));
    }

    public function test_画面に渡すファイル情報にはサーバー上のパスを含めない(): void
    {
        $info = Documents_FieldFile::toClientInfo([
            'id' => 5,
            'title' => 't',
            'filename' => 'a.png',
            'filetype' => 'image/png',
            'filesize' => 10,
            'is_image' => true,
            'download_url' => 'index.php?module=Documents&action=DownloadFile&record=5&fileid=6',
            'attachment_id' => 6,
            'path' => 'storage/2026/October/week4/6_abc.png',
        ]);

        $this->assertSame(['id', 'filename', 'filetype', 'filesize', 'is_image', 'download_url'], array_keys($info));
        $this->assertNull(Documents_FieldFile::toClientInfo(null));
    }

    private function writeTemp(string $extension, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fieldfile') . '.' . $extension;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    /** 単色の PNG を作る（GD に頼らずに作れるようにする） */
    private function pngBinary(int $width, int $height): string
    {
        $row = "\x00" . str_repeat("\xff\x00\x00\xff", $width);
        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        };
        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress(str_repeat($row, $height)))
            . $chunk('IEND', '');
    }
}
