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

namespace Tests\Unit\Vtiger\Actions;

use PHPUnit\Framework\TestCase;

$massPDFExportTestRoot = dirname(__DIR__, 4);

require_once $massPDFExportTestRoot . '/includes/runtime/Controller.php';
require_once $massPDFExportTestRoot . '/modules/Vtiger/actions/Mass.php';
require_once $massPDFExportTestRoot . '/modules/Vtiger/actions/MassPDFExport.php';

/**
 * 一覧からの PDF 一括出力で、PDF_helper::createPDF() の戻り値から PDF の内容を取り出す処理の検証（thinkingreed-inc#1984）。
 *
 * createPDF() を一括出力（$isMassExport = true）で呼ぶと、
 *   TCPDF の場合          : PDF の内容（文字列）
 *   Headless Chrome の場合 : 「レコードID => PDF ファイルのパス」の配列
 * が返る。どちらでも zip に詰める PDF の内容を取り出せることを確かめる。
 */
final class MassPDFExportTest extends TestCase
{
    /** @var string[] テストで作ったファイル */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->files = [];
        parent::tearDown();
    }

    private function makePDFFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'masspdf');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    public function test_TCPDFの戻り値はそのまま返す(): void
    {
        $this->assertSame('%PDF-1.7 tcpdf', \Vtiger_MassPDFExport_Action::toPDFContent('%PDF-1.7 tcpdf'));
    }

    public function test_戻り値がない場合は空文字を返す(): void
    {
        $this->assertSame('', \Vtiger_MassPDFExport_Action::toPDFContent(null));
    }

    public function test_HeadlessChromeの戻り値はファイルの内容を返してファイルを削除する(): void
    {
        $path = $this->makePDFFile('%PDF-1.7 chrome');

        $this->assertSame('%PDF-1.7 chrome', \Vtiger_MassPDFExport_Action::toPDFContent([1290 => $path]));
        $this->assertFileDoesNotExist($path);
    }

    public function test_HeadlessChromeで複数ある場合は先頭の内容を返しファイルはすべて削除する(): void
    {
        $first = $this->makePDFFile('%PDF-1.7 first');
        $second = $this->makePDFFile('%PDF-1.7 second');

        $this->assertSame('%PDF-1.7 first', \Vtiger_MassPDFExport_Action::toPDFContent([1 => $first, 2 => $second]));
        $this->assertFileDoesNotExist($first);
        $this->assertFileDoesNotExist($second);
    }

    public function test_HeadlessChromeでファイルがない場合は空文字を返す(): void
    {
        $missing = sys_get_temp_dir() . '/masspdf_missing_' . uniqid('', true) . '.pdf';

        $this->assertSame('', \Vtiger_MassPDFExport_Action::toPDFContent([1290 => $missing]));
    }
}
