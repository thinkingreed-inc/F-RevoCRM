<?php

declare(strict_types=1);

namespace Tests\Unit\NotificationBlock;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 通知ブロックの言語ファイルは、日本語と英語で同じキーを持つ（PR チェックリスト「言語対応（日・英）」）
 */
final class LanguageFilesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function files(): array
    {
        return [
            '通知ブロック' => ['NotificationBlock.php'],
            '通知ブロック（項目設定）' => ['Settings/NotificationBlock.php'],
        ];
    }

    #[DataProvider('files')]
    public function test_日本語と英語で同じキーを持つ(string $file): void
    {
        $ja = $this->load('ja_jp', $file);
        $en = $this->load('en_us', $file);
        $this->assertSame(array_keys($ja['languageStrings']), array_keys($en['languageStrings']));
        $this->assertSame(array_keys($ja['jsLanguageStrings']), array_keys($en['jsLanguageStrings']));
    }

    public function test_既定のメール文面は日本語と英語で同じ差し込みを使う(): void
    {
        $ja = $this->load('ja_jp', 'NotificationBlock.php')['languageStrings'];
        $en = $this->load('en_us', 'NotificationBlock.php')['languageStrings'];
        foreach (array_keys($ja) as $key) {
            if (!str_starts_with($key, 'LBL_DEFAULT_MAIL_')) {
                continue;
            }
            preg_match_all('/\{[a-z_]+\}/', $ja[$key], $jaPlaceholders);
            preg_match_all('/\{[a-z_]+\}/', $en[$key], $enPlaceholders);
            $this->assertSame($jaPlaceholders[0], $enPlaceholders[0], $key);
        }
    }

    public function test_通知ブロックの名前は共通の言語ファイルにある(): void
    {
        $this->assertSame('通知', $this->load('ja_jp', 'Vtiger.php')['languageStrings']['LBL_NOTIFICATION_BLOCK'] ?? null);
        $this->assertSame('Notification', $this->load('en_us', 'Vtiger.php')['languageStrings']['LBL_NOTIFICATION_BLOCK'] ?? null);
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
