<?php

namespace Tests\Unit\Vtlib\Vtiger;

use PHPUnit\Framework\TestCase;
use Vtiger_Functions;

require_once dirname(__DIR__, 4) . '/vtlib/Vtiger/Functions.php';

/**
 * 画像アップロードの検査（Vtiger_Functions::validateImage）
 *
 * HTML のイベント属性を取り除く処理は SVG（テキスト）向けのもの。
 * PNG などのバイナリにかけると、圧縮データが偶然 `onend=` などに一致したときに
 * 中身が書き換わって画像が壊れる。
 */
final class FunctionsValidateImageTest extends TestCase
{
    /** @var list<string> テストで作った一時ファイル */
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

    public function test_PNGの中身にイベント属性に似たバイト列があっても書き換えない(): void
    {
        // テキストチャンクにイベント属性に似た文字列を入れた PNG（実際の画像では圧縮データ中に偶然現れる）
        $path = $this->createPngWithText(' onclick="a" ');
        $before = (string) file_get_contents($path);

        $result = Vtiger_Functions::validateImage(['name' => 'sign.png', 'type' => 'image/png', 'tmp_name' => $path]);

        $this->assertTrue($result);
        $this->assertSame($before, (string) file_get_contents($path));
        $this->assertNotFalse(@imagecreatefromstring((string) file_get_contents($path)));
    }

    /**
     * 1x1 の PNG に tEXt チャンクを足したファイルを作る
     */
    private function createPngWithText(string $text): string
    {
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        $data = 'Comment' . "\0" . $text;
        $chunk = pack('N', strlen($data)) . 'tEXt' . $data . pack('N', crc32('tEXt' . $data));
        // シグネチャ（8 バイト）と IHDR（25 バイト）の直後に入れる
        $png = substr($png, 0, 33) . $chunk . substr($png, 33);

        return $this->createTempFile($png);
    }

    private function createTempFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;
        return $path;
    }
}
