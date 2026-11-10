<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use PearDatabase;
use RecordVersion_Display_Model;
use RecordVersion_Label_Model;
use RecordVersion_Revision_Model;
use RecordVersion_Util_Helper;
use RecordVersion_Version_Model;
use Tests\Support\RecordVersionTestCase;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 過去の版に残す表示名の辞書（確認事項 Q38）
 *
 * テスト用の言語を vtiger_language に登録し、言語ファイルを languages/ の下に一時的に置く。
 */
final class LabelDictionaryTest extends RecordVersionTestCase
{
    /** 無効にした登録言語（訳がある） */
    private const INACTIVE_LANGUAGE = 'xx_rvtest';

    /** 言語ファイルが壊れている登録言語 */
    private const BROKEN_LANGUAGE = 'zz_rvtest';

    /** languages/ にフォルダがあるが登録されていない言語 */
    private const UNREGISTERED_LANGUAGE = 'fr_fr';

    protected function setUp(): void
    {
        parent::setUp();
        // 前回のテストが中断して（Ctrl+C・致命的エラーなど）tearDown が動かず残ったものを先に消す
        $this->removeTestLanguages();
        $this->enableVersioning();
        $this->assertSame('0', $this->scalar('SELECT COUNT(*) FROM vtiger_language WHERE prefix = ?', [self::UNREGISTERED_LANGUAGE]), 'フランス語は登録されていない前提');
        $this->addLanguage(self::INACTIVE_LANGUAGE, 0, [
            'Potentials.php' => "<?php\n\$languageStrings = ['Potential Name' => 'XX Potential Name'];\n",
        ]);
        $this->addLanguage(self::BROKEN_LANGUAGE, 1, [
            // 本家のドイツ語の言語ファイルと同じ種類のエラー（読むと TypeError になる）
            'Vtiger.php' => "<?php\n\$languageStrings = ['LBL_BROKEN' => 'a' + 'b'];\n",
        ]);
    }

    protected function tearDown(): void
    {
        try {
            $this->removeTestLanguages();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * テスト用の言語（vtiger_language の行と languages/ の下のフォルダ）を消す。無ければ何もしない
     */
    private function removeTestLanguages(): void
    {
        $prefixes = [self::INACTIVE_LANGUAGE, self::BROKEN_LANGUAGE];
        PearDatabase::getInstance()->pquery('DELETE FROM vtiger_language WHERE prefix IN (?, ?)', $prefixes);
        foreach ($prefixes as $prefix) {
            $dir = $this->languageDir($prefix);
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }

    private function languageDir(string $prefix): string
    {
        return dirname(__DIR__, 3) . '/languages/' . $prefix;
    }

    public function test_改版すると登録されている全言語の表示名を辞書にして過去の版1件に1つだけ持つ(): void
    {
        $recordId = $this->createPotential('辞書');
        $this->assertTrue(RecordVersion_Revision_Model::revise($recordId, '改版')['success']);
        $snapshot = $this->savedSnapshot($recordId);

        $this->assertSame(RecordVersion_Label_Model::FORMAT_DICTIONARY, $snapshot['format']);
        $languages = RecordVersion_Util_Helper::toList($snapshot['languages'] ?? []);
        $this->assertContains('ja_jp', $languages);
        $this->assertContains('en_us', $languages);
        $this->assertContains(self::INACTIVE_LANGUAGE, $languages, '無効にした言語も含める');
        $this->assertNotContains(self::UNREGISTERED_LANGUAGE, $languages, '登録されていない言語は含めない');

        $names = $this->dictionary($snapshot)[self::MODULE]['Potential Name'] ?? [];
        $this->assertSame(\Vtiger_Language_Handler::getLanguageTranslatedString('ja_jp', 'Potential Name', self::MODULE), $names['ja_jp'] ?? null);
        $this->assertSame(\Vtiger_Language_Handler::getLanguageTranslatedString('en_us', 'Potential Name', self::MODULE), $names['en_us'] ?? null);
        $this->assertSame('XX Potential Name', $names[self::INACTIVE_LANGUAGE] ?? null);
        $this->assertArrayNotHasKey(self::UNREGISTERED_LANGUAGE, $names);

        $amount = $this->dictionary($snapshot)[self::MODULE]['Amount'] ?? [];
        $this->assertArrayNotHasKey(self::INACTIVE_LANGUAGE, $amount, '訳が無い言語は入れない（既定の言語の訳を入れない）');

        $field = $this->snapshotFieldsByName($recordId)['potentialname'];
        $this->assertSame('Potential Name', $field['label'], '項目には翻訳前の名前だけを持つ');
        foreach (['label_ja', 'label_en', 'block_label_ja', 'block_label_en'] as $key) {
            $this->assertArrayNotHasKey($key, $field);
        }
        $blockLabel = RecordVersion_Util_Helper::toStr($field['block_label']);
        $this->assertArrayHasKey('ja_jp', $this->dictionary($snapshot)[self::MODULE][$blockLabel] ?? [], 'ブロック名も辞書に入れる');
    }

    public function test_読めない言語ファイルがあってもその言語だけ飛ばして改版する(): void
    {
        $recordId = $this->createPotential('壊れた言語');
        $result = RecordVersion_Revision_Model::revise($recordId, '改版');

        $this->assertTrue($result['success'], '改版は止まらない');
        $this->assertSame(2, $this->currentVersion($recordId));
        $snapshot = $this->savedSnapshot($recordId);
        $this->assertNotContains(self::BROKEN_LANGUAGE, RecordVersion_Util_Helper::toList($snapshot['languages'] ?? []));
        $this->assertContains('ja_jp', RecordVersion_Util_Helper::toList($snapshot['languages'] ?? []), 'ほかの言語は入る');
        foreach ($this->dictionary($snapshot) as $byName) {
            foreach ($byName as $byLanguage) {
                $this->assertArrayNotHasKey(self::BROKEN_LANGUAGE, $byLanguage);
            }
        }
    }

    public function test_改版した過去の版を見ている利用者の言語の表示名で出す(): void
    {
        $recordId = $this->createPotential('言語ごと');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $snapshot = $this->savedSnapshot($recordId);
        $field = $this->snapshotFieldsByName($recordId)['potentialname'];
        // 当時あってその後削除した項目として出す（当時の表示名で出るのは削除された項目）
        $fields = [999999902 => ['fieldid' => 999999902] + $field];

        foreach (['ja_jp' => 'ja_jp', 'en_us' => 'en_us', self::INACTIVE_LANGUAGE => self::INACTIVE_LANGUAGE, self::UNREGISTERED_LANGUAGE => 'ja_jp'] as $viewer => $shownLanguage) {
            $display = new RecordVersion_Display_Model('¥', new RecordVersion_Label_Model($snapshot, $viewer, 'ja_jp'));
            $deleted = $display->buildFieldBlocks(self::MODULE, $fields)['deleted'];
            $this->assertSame(
                $this->dictionary($snapshot)[self::MODULE]['Potential Name'][$shownLanguage] ?? null,
                $deleted[0]['label'] ?? null,
                "{$viewer} の利用者"
            );
        }
    }

    /**
     * @param array<string, string> $files ファイル名 => 中身
     */
    private function addLanguage(string $prefix, int $active, array $files): void
    {
        $dir = $this->languageDir($prefix);
        mkdir($dir);
        foreach ($files as $name => $content) {
            file_put_contents($dir . '/' . $name, $content);
        }
        $id = (int) $this->scalar('SELECT COALESCE(MAX(id), 0) + 1000 FROM vtiger_language');
        $this->db->pquery(
            'INSERT INTO vtiger_language (id, name, prefix, label, lastupdated, isdefault, active) VALUES (?, ?, ?, ?, NOW(), 0, ?)',
            [$id, $prefix, $prefix, $prefix, $active]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function savedSnapshot(int $recordId): array
    {
        $version = RecordVersion_Version_Model::getByNumber($recordId, 1);
        $this->assertNotNull($version);
        return $version->getSnapshot();
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, array<string, array<string, string>>>
     */
    private function dictionary(array $snapshot): array
    {
        $labels = [];
        foreach (RecordVersion_Util_Helper::toArray($snapshot['labels'] ?? []) as $module => $byName) {
            foreach (RecordVersion_Util_Helper::toArray($byName) as $name => $byLanguage) {
                foreach (RecordVersion_Util_Helper::toArray($byLanguage) as $language => $label) {
                    $labels[(string) $module][(string) $name][(string) $language] = RecordVersion_Util_Helper::toStr($label);
                }
            }
        }
        return $labels;
    }
}
