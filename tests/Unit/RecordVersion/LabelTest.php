<?php

declare(strict_types=1);

namespace Tests\Unit\RecordVersion;

use PHPUnit\Framework\TestCase;
use RecordVersion_Label_Model;

require_once dirname(__DIR__, 3) . '/modules/RecordVersion/helpers/Util.php';
require_once dirname(__DIR__, 3) . '/modules/RecordVersion/models/Config.php';
require_once dirname(__DIR__, 3) . '/modules/RecordVersion/models/Label.php';

/**
 * 過去の版に残した表示名の出し方
 */
final class LabelTest extends TestCase
{
    /** 改版した時点の表示名の辞書（format 2） */
    private const DICTIONARY = [
        'format' => 2,
        'labels' => [
            'Potentials' => [
                'Amount' => ['en_us' => 'Amount', 'ja_jp' => '金額', 'fr_fr' => 'Montant'],
                'Related To' => ['en_us' => 'Related To', 'ja_jp' => '顧客企業名'],
                'English only' => ['en_us' => 'English only label'],
            ],
            'Quotes' => [
                'Related To' => ['en_us' => 'Related To', 'ja_jp' => '関連'],
            ],
        ],
    ];

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function viewerLanguages(): array
    {
        return [
            '日本語' => ['ja_jp', '金額'],
            '英語' => ['en_us', 'Amount'],
            'フランス語' => ['fr_fr', 'Montant'],
        ];
    }

    /**
     * @dataProvider viewerLanguages
     */
    public function test_format2は見ている利用者の言語の表示名で出す(string $viewer, string $expected): void
    {
        $labels = new RecordVersion_Label_Model(self::DICTIONARY, $viewer, 'ja_jp');
        $this->assertSame($expected, $labels->resolve(['label' => 'Amount'], 'label', 'Potentials'));
    }

    public function test_format2は翻訳したときのモジュールで引く(): void
    {
        $labels = new RecordVersion_Label_Model(self::DICTIONARY, 'ja_jp', 'ja_jp');
        $this->assertSame('顧客企業名', $labels->resolve(['label' => 'Related To'], 'label', 'Potentials'));
        $this->assertSame('関連', $labels->resolve(['label' => 'Related To'], 'label', 'Quotes'));
    }

    public function test_見ている利用者の言語に訳が無ければ既定の言語で出す(): void
    {
        $labels = new RecordVersion_Label_Model(self::DICTIONARY, 'fr_fr', 'ja_jp');
        $this->assertSame('顧客企業名', $labels->resolve(['label' => 'Related To'], 'label', 'Potentials'), '英語より既定の言語を先に使う');
    }

    public function test_既定の言語にも訳が無ければ英語で出す(): void
    {
        $labels = new RecordVersion_Label_Model(self::DICTIONARY, 'fr_fr', 'ja_jp');
        $this->assertSame('English only label', $labels->resolve(['label' => 'English only'], 'label', 'Potentials'));
    }

    public function test_どの言語にも訳が無ければ翻訳前の名前で出す(): void
    {
        $labels = new RecordVersion_Label_Model(self::DICTIONARY, 'fr_fr', 'de_de');
        $this->assertSame('SPECTEST 独自の項目', $labels->resolve(['label' => 'SPECTEST 独自の項目'], 'label', 'Potentials'));
        $this->assertSame('Related To', $labels->resolve(['label' => 'Related To'], 'label', 'Contacts'), '辞書に無いモジュール');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function oldFormats(): array
    {
        return [
            'format 1・日本語の利用者' => [['format' => 1], 'ja_jp', '当時の金額'],
            'format 1・英語の利用者' => [['format' => 1], 'en_us', 'Old Amount'],
            'format 1・ほかの言語の利用者は英語' => [['format' => 1], 'fr_fr', 'Old Amount'],
            'format が無い（いちばん古い形）' => [[], 'ja_jp', '当時の金額'],
        ];
    }

    /**
     * @dataProvider oldFormats
     * @param array<string, mixed> $snapshot
     */
    public function test_format1の古い過去の版は各所の日本語と英語の表示名で出す(array $snapshot, string $viewer, string $expected): void
    {
        $labels = new RecordVersion_Label_Model($snapshot, $viewer, 'ja_jp');
        $entry = ['label' => 'Amount', 'label_ja' => '当時の金額', 'label_en' => 'Old Amount'];
        $this->assertSame($expected, $labels->resolve($entry, 'label', 'Potentials'));
    }

    public function test_format1でも手数料の名前は_nameのキーで引く(): void
    {
        $labels = new RecordVersion_Label_Model(['format' => 1], 'ja_jp', 'ja_jp');
        $charge = ['name' => 'Shipping', 'name_ja' => '送料', 'name_en' => 'Shipping & Handling'];
        $this->assertSame('送料', $labels->resolve($charge, 'name', 'Quotes'));
    }

    public function test_辞書に入れる名前は翻訳したときのモジュールごとに集める(): void
    {
        $snapshot = [
            'module' => 'Quotes',
            'fields' => [
                '1' => ['label' => 'Subject', 'block_label' => 'LBL_QUOTE_INFORMATION'],
            ],
            'inventory' => [
                'lines' => [['taxes' => [['label' => 'VAT']]]],
                'group_taxes' => [['label' => 'Sales']],
                'charges' => [['name' => 'Shipping', 'taxes' => [['label' => 'Service']]]],
            ],
            'relations' => [
                [
                    'label' => 'Documents',
                    'related_module' => 'Documents',
                    'records' => [
                        ['fields' => ['2' => ['label' => 'Title', 'block_label' => 'LBL_NOTE_INFORMATION']]],
                        ['fields' => ['2' => ['label' => 'Title', 'block_label' => 'LBL_NOTE_INFORMATION']]],
                    ],
                ],
            ],
        ];
        $names = RecordVersion_Label_Model::collectNames($snapshot);
        $this->assertSame(['Subject', 'LBL_QUOTE_INFORMATION', 'VAT', 'Sales', 'Shipping', 'Service'], array_keys($names['Quotes']));
        $this->assertSame(['Documents', 'Title', 'LBL_NOTE_INFORMATION'], array_keys($names['Documents']), '関連の名前と関連レコードの項目は関連先のモジュール。同じ名前は 1 つにまとめる');
    }
}
