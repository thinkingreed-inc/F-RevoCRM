<?php

declare(strict_types=1);

namespace Tests\Unit\RecordVersion;

use PHPUnit\Framework\TestCase;

/**
 * 版管理の言語ファイルは、日本語と英語で同じキーを持つ（PR チェックリスト「言語対応（日・英）」）
 */
final class LanguageFilesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function files(): array
    {
        return [
            '版管理' => ['RecordVersion.php'],
            '版管理（設定）' => ['Settings/RecordVersion.php'],
        ];
    }

    /**
     * @dataProvider files
     */
    public function test_日本語と英語で同じキーを持つ(string $file): void
    {
        $ja = $this->load('ja_jp', $file);
        $en = $this->load('en_us', $file);
        $this->assertSame(array_keys($ja['languageStrings']), array_keys($en['languageStrings']));
        $this->assertSame(array_keys($ja['jsLanguageStrings']), array_keys($en['jsLanguageStrings']));
    }

    /**
     * @return array{languageStrings: array<string, string>, jsLanguageStrings: array<string, string>}
     */
    private function load(string $language, string $file): array
    {
        $languageStrings = [];
        $jsLanguageStrings = [];
        require dirname(__DIR__, 3) . '/languages/' . $language . '/' . $file;
        return ['languageStrings' => $languageStrings, 'jsLanguageStrings' => $jsLanguageStrings];
    }
}
