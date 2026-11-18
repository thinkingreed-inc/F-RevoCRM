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
use Vtiger_PDF_TCPDF;

$defaultFontTestRoot = dirname(__DIR__, 4);
require_once $defaultFontTestRoot . '/vtlib/Vtiger/PDF/TCPDF.php';

/**
 * システム変数の取得を差し替え、呼ばれた回数を記録する。
 *
 * 本物は Settings_Parameters_Record_Model から DB を読むため差し替える。
 * 同名クラスのスタブ（tests/Support/ParametersApiTestSupport.php）と衝突しないよう、クラスのスタブは使わない。
 */
class ParameterStubbedTCPDF extends Vtiger_PDF_TCPDF
{
    public static ?string $parameterValue = null;

    public static int $fetchCalls = 0;

    protected static function fetchDefaultFontParameter()
    {
        self::$fetchCalls++;

        return self::$parameterValue;
    }
}

/**
 * システム変数 PDF_DEFAULT_FONT の値から PDF のフォントを決める処理（thinkingreed-inc#1901）
 */
class DefaultFontTest extends TestCase
{
    protected function setUp(): void
    {
        Vtiger_PDF_TCPDF::clearDefaultFontCache();
        ParameterStubbedTCPDF::$parameterValue = null;
        ParameterStubbedTCPDF::$fetchCalls = 0;
    }

    protected function tearDown(): void
    {
        Vtiger_PDF_TCPDF::clearDefaultFontCache();
    }

    public function testGetDefaultFontUsesParameterValue(): void
    {
        ParameterStubbedTCPDF::$parameterValue = 'dejavusans';

        $this->assertSame('dejavusans', ParameterStubbedTCPDF::getDefaultFont());
    }

    public function testGetDefaultFontFetchesParameterOnlyOnce(): void
    {
        ParameterStubbedTCPDF::$parameterValue = 'notexistfont';

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('ume-tgo4', ParameterStubbedTCPDF::getDefaultFont());
        }
        $this->assertSame(1, ParameterStubbedTCPDF::$fetchCalls);
    }

    public function testClearDefaultFontCacheFetchesParameterAgain(): void
    {
        ParameterStubbedTCPDF::$parameterValue = 'dejavusans';
        ParameterStubbedTCPDF::getDefaultFont();

        Vtiger_PDF_TCPDF::clearDefaultFontCache();
        ParameterStubbedTCPDF::$parameterValue = '';

        $this->assertSame('ume-tgo4', ParameterStubbedTCPDF::getDefaultFont());
        $this->assertSame(2, ParameterStubbedTCPDF::$fetchCalls);
    }

    public function testUsesSpecifiedFontWhenFontFileExists(): void
    {
        $this->assertSame('dejavusans', Vtiger_PDF_TCPDF::resolveFont('dejavusans'));
    }

    public function testNormalizesCaseAndSpaces(): void
    {
        $this->assertSame('dejavusans', Vtiger_PDF_TCPDF::resolveFont('  DejaVuSans '));
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function fallbackValues(): array
    {
        return [
            'null'               => [null],
            'empty'              => [''],
            'spaces only'        => ['   '],
            'font not found'     => ['notexistfont'],
            'path traversal'     => ['../../config.inc'],
            'directory separator' => ['fonts/dejavusans'],
        ];
    }

    #[DataProvider('fallbackValues')]
    public function testFallsBackToUmeTgo4(?string $value): void
    {
        $this->assertSame('ume-tgo4', Vtiger_PDF_TCPDF::resolveFont($value));
    }

    public function testRejectsPhpFileThatIsNotFontDefinition(): void
    {
        // uni2cid_*.php は CID フォント用の文字コード表で、フォントではない
        $this->assertFileExists(Vtiger_PDF_TCPDF::getFontDirectory() . 'uni2cid_aj16.php');
        $this->assertFalse(Vtiger_PDF_TCPDF::isAvailableFont('uni2cid_aj16'));
        $this->assertSame('ume-tgo4', Vtiger_PDF_TCPDF::resolveFont('uni2cid_aj16'));
    }

    public function testAvailableFontsListsBundledFonts(): void
    {
        $fonts = Vtiger_PDF_TCPDF::getAvailableFonts();

        $this->assertContains('ume-tgo4', $fonts);
        $this->assertContains('dejavusans', $fonts);
        $this->assertContains('cid0jp', $fonts);
        $this->assertNotContains('uni2cid_aj16', $fonts);
        // 太字・斜体用のファイルは TCPDF が自動で使うため、単独では選ばせない
        $this->assertNotContains('dejavusansb', $fonts);
        $this->assertNotContains('dejavusansbi', $fonts);
        $this->assertSame($fonts, array_values(array_unique($fonts)));
        $sorted = $fonts;
        sort($sorted);
        $this->assertSame($sorted, $fonts);
    }

    public function testFallbackFontIsBundled(): void
    {
        $this->assertTrue(Vtiger_PDF_TCPDF::isAvailableFont(Vtiger_PDF_TCPDF::FALLBACK_FONT));
    }
}
