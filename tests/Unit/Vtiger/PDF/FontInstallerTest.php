<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Vtiger\PDF;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vtiger_PDF_FontInstaller;
use Vtiger_PDF_FontInstallException;
use Vtiger_PDF_TCPDF;

$fontInstallerTestRoot = dirname(__DIR__, 4);
require_once $fontInstallerTestRoot . '/vtlib/Vtiger/PDF/FontInstaller.php';

/**
 * TrueType フォントを TCPDF 用に変換して置く処理（thinkingreed-inc#1901）
 *
 * 変換先はテストごとの一時ディレクトリにし、libraries/tcpdf/fonts/ には書き込まない。
 * 変換元にはリポジトリ同梱の TrueType フォント（OpenSans）を使う。
 */
class FontInstallerTest extends TestCase
{
    private const SOURCE_TTF = 'public/resources/fonts/OpenSans-Regular-webfont.ttf';

    private string $fontDir;

    private string $workDir;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/frevocrm_font_installer_test_' . bin2hex(random_bytes(4));
        $this->fontDir = $base . '/fonts';
        $this->workDir = $base . '/work';
        mkdir($this->fontDir, 0777, true);
        mkdir($this->workDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $base = dirname($this->fontDir);
        foreach ([$this->fontDir, $this->workDir] as $dir) {
            // ロックファイル（.font_install.lock）も消す
            foreach (array_merge((array)glob($dir . '/*'), (array)glob($dir . '/.*')) as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            if (is_dir($dir)) {
                chmod($dir, 0777);
                rmdir($dir);
            }
        }
        rmdir($base);
    }

    private function sourceTtf(): string
    {
        return dirname(__DIR__, 4) . '/' . self::SOURCE_TTF;
    }

    /** テスト用のファイルを作業ディレクトリに作る */
    private function makeFile(string $content): string
    {
        $path = $this->workDir . '/upload_' . bin2hex(random_bytes(4));
        file_put_contents($path, $content);

        return $path;
    }

    private function installer(): Vtiger_PDF_FontInstaller
    {
        return new Vtiger_PDF_FontInstaller($this->fontDir);
    }

    /** 例外の翻訳キーを返す */
    private function installError(string $source, string $fileName): string
    {
        try {
            $this->installer()->install($source, $fileName);
        } catch (Vtiger_PDF_FontInstallException $e) {
            return $e->getMessage();
        }
        $this->fail('Vtiger_PDF_FontInstallException が投げられること');
    }

    /** @return string[] フォントのディレクトリにあるファイル名 */
    private function fontFiles(): array
    {
        $files = array_map('basename', (array)glob($this->fontDir . '/*'));
        sort($files);

        return $files;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function fontNameProvider(): array
    {
        return [
            'Regular は外す'       => ['LINESeedJP-Regular.ttf', 'lineseedjp'],
            'Bold は b'            => ['LINESeedJP-Bold.ttf', 'lineseedjpb'],
            'Italic は i'          => ['NotoSans-BoldItalic.ttf', 'notosansbi'],
            '記号と空白は除く'      => ['My Font-1.ttf', 'myfont1'],
            '_ は残す'             => ['my_font.TTF', 'my_font'],
            '英数字がなければ空'    => ['フォント.ttf', ''],
        ];
    }

    #[DataProvider('fontNameProvider')]
    public function testFontNameFollowsTcpdfRule(string $fileName, string $expected): void
    {
        $this->assertSame($expected, Vtiger_PDF_FontInstaller::getFontNameFromFileName($fileName));
    }

    public function testInstallsTrueTypeFont(): void
    {
        $font = $this->installer()->install($this->sourceTtf(), 'Test-Font Regular.ttf');

        $this->assertSame('testfont', $font);
        $this->assertSame(['testfont.ctg.z', 'testfont.php', 'testfont.z'], $this->fontFiles());
        $this->assertTrue(Vtiger_PDF_TCPDF::isAvailableFont('testfont', $this->fontDir));
        $this->assertContains('testfont', Vtiger_PDF_TCPDF::getAvailableFonts($this->fontDir));
    }

    public function testInstalledFontCanBeUsedForPdf(): void
    {
        $font = $this->installer()->install($this->sourceTtf(), 'TestFont.ttf');

        $pdf = new \TCPDF();
        $pdf->AddFont($font, '', $this->fontDir . '/' . $font . '.php');
        $pdf->AddPage();
        $pdf->SetFont($font);
        $pdf->Write(0, 'Test');

        $this->assertMatchesRegularExpression('#/BaseFont /[A-Z]{6}\+OpenSans#', $pdf->Output('t.pdf', 'S'));
    }

    public function testDoesNotKeepUploadedFileInFontDirectory(): void
    {
        $this->installer()->install($this->sourceTtf(), 'TestFont.ttf');

        $this->assertSame([], glob($this->fontDir . '/*.ttf'));
    }

    public function testLocksInFontDirectory(): void
    {
        // 共有の一時ディレクトリに置くと、別のユーザーが作ったロックファイルを開けず追加できなくなる
        $this->installer()->install($this->sourceTtf(), 'TestFont.ttf');

        $this->assertFileExists($this->fontDir . '/' . Vtiger_PDF_FontInstaller::LOCK_FILE);
        $this->assertNotContains(Vtiger_PDF_FontInstaller::LOCK_FILE, Vtiger_PDF_TCPDF::getAvailableFonts($this->fontDir));
    }

    public function testRejectsExistingFontName(): void
    {
        // TCPDF は同名のフォントがあると変換せずにその名前を返すため、置き換えや取り違えに気づけない
        file_put_contents($this->fontDir . '/helvetica.php', "<?php\n\$type='core';\n");

        $this->assertSame('LBL_FONT_UPLOAD_ALREADY_EXISTS', $this->installError($this->sourceTtf(), 'Helvetica.ttf'));
        $this->assertSame("<?php\n\$type='core';\n", file_get_contents($this->fontDir . '/helvetica.php'));
    }

    public function testRejectsSameFontTwice(): void
    {
        $this->installer()->install($this->sourceTtf(), 'TestFont.ttf');

        $this->assertSame('LBL_FONT_UPLOAD_ALREADY_EXISTS', $this->installError($this->sourceTtf(), 'TestFont.ttf'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidExtensionProvider(): array
    {
        return [
            'OpenType' => ['Font.otf'],
            'WOFF'     => ['Font.woff'],
            'PHP'      => ['Font.php'],
            '拡張子なし' => ['Font'],
        ];
    }

    #[DataProvider('invalidExtensionProvider')]
    public function testRejectsNonTtfExtension(string $fileName): void
    {
        $this->assertSame('LBL_FONT_UPLOAD_INVALID_EXTENSION', $this->installError($this->sourceTtf(), $fileName));
        $this->assertSame([], $this->fontFiles());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidContentProvider(): array
    {
        return [
            'OpenType(CFF)'       => ['OTTO' . str_repeat("\0", 100)],
            'フォントコレクション' => ['ttcf' . str_repeat("\0", 100)],
            'PHP'                 => ['<?php echo 1;'],
            '空'                  => [''],
        ];
    }

    #[DataProvider('invalidContentProvider')]
    public function testRejectsNonTrueTypeContent(string $content): void
    {
        $this->assertSame('LBL_FONT_UPLOAD_INVALID_FILE', $this->installError($this->makeFile($content), 'Font.ttf'));
        $this->assertSame([], $this->fontFiles());
    }

    public function testRemovesPartialFilesWhenConversionFails(): void
    {
        // 先頭だけ TrueType で中身が壊れたファイル。TCPDF は警告を出しながら途中のファイルを残す
        $broken = $this->makeFile("\x00\x01\x00\x00" . str_repeat("\xAB", 2000));

        $this->assertSame('LBL_FONT_UPLOAD_INVALID_FILE', $this->installError($broken, 'Broken.ttf'));
        $this->assertSame([], $this->fontFiles());
    }

    public function testRejectsFileNameWithoutFontName(): void
    {
        $this->assertSame('LBL_FONT_UPLOAD_INVALID_NAME', $this->installError($this->sourceTtf(), 'フォント.ttf'));
    }

    public function testRejectsTooLargeFile(): void
    {
        $large = $this->makeFile('');
        $handle = fopen($large, 'r+');
        $this->assertNotFalse($handle);
        ftruncate($handle, Vtiger_PDF_FontInstaller::MAX_FILE_SIZE + 1);
        fclose($handle);

        $this->assertSame('LBL_FONT_UPLOAD_TOO_LARGE', $this->installError($large, 'Large.ttf'));
    }

    public function testRejectsWhenFontDirectoryIsNotWritable(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root では書き込み権限の確認ができないため');
        }
        chmod($this->fontDir, 0555);

        $this->assertSame('LBL_FONT_UPLOAD_DIR_NOT_WRITABLE', $this->installError($this->sourceTtf(), 'TestFont.ttf'));
    }
}
