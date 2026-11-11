<?php

/**
 * 過去の版に残す表示名（項目名・ブロック名・関連名・税や手数料の名前）
 *
 * 作るとき：過去の版の内容から翻訳前の名前を集め、改版した時点でシステムに登録されている全言語
 * （vtiger_language の全部の行。無効の言語も含む）の訳を「表示名の辞書」にする。
 *   labels：{ モジュール名：{ 翻訳前の名前：{ 言語コード：表示名 } } }
 * モジュール名は翻訳したときの文脈のモジュール（項目・ブロック・税・手数料はそのレコードのモジュール、
 * 関連の名前と関連レコードの項目は関連先のモジュール）。訳が見つかった言語だけを入れる。
 *
 * 読むとき：見ている利用者の言語 → 既定の言語 → 英語 → 翻訳前の名前 の順で、最初に見つかったものを出す。
 * format 1 の古い過去の版（各所に label_ja・label_en を持つ形）は、日本語の利用者は日本語、それ以外は英語で出す。
 */
class RecordVersion_Label_Model
{
    /** 表示名の辞書を持つ形の版 */
    public const FORMAT_DICTIONARY = 2;

    private const ENGLISH = 'en_us';
    private const JAPANESE = 'ja_jp';

    private int $format;

    /** @var array<string, mixed> */
    private array $labels;

    /** @var string[] 表示名を探す言語の順 */
    private array $languageOrder;

    private bool $isJapanese;

    /**
     * @param array<string, mixed> $snapshot 過去の版の内容
     * @param string|null $viewerLanguage 見ている利用者の言語（null なら今の利用者の言語）
     * @param string|null $defaultLanguage 既定の言語（null なら config.inc.php の default_language）
     */
    public function __construct(array $snapshot, ?string $viewerLanguage = null, ?string $defaultLanguage = null)
    {
        $this->format = RecordVersion_Util_Helper::toInt($snapshot['format'] ?? 1);
        $this->labels = RecordVersion_Util_Helper::toArray($snapshot['labels'] ?? []);
        $viewer = $viewerLanguage ?? RecordVersion_Util_Helper::toStr(Vtiger_Language_Handler::getLanguage());
        $default = $defaultLanguage ?? RecordVersion_Util_Helper::toStr($GLOBALS['default_language'] ?? '');
        $this->languageOrder = array_values(array_unique(array_filter([$viewer, $default, self::ENGLISH], fn (string $l): bool => $l !== '')));
        $this->isJapanese = $viewer === self::JAPANESE;
    }

    /**
     * 保存した表示名を、見ている利用者の言語で返す
     *
     * @param array<string, mixed> $entry 項目・関連・税・手数料など、翻訳前の名前を持つもの
     * @param string $key 翻訳前の名前のキー（label・block_label・name）
     * @param string $moduleName 翻訳したときの文脈のモジュール
     */
    public function resolve(array $entry, string $key, string $moduleName): string
    {
        $raw = RecordVersion_Util_Helper::toStr($entry[$key] ?? '');
        $found = $this->format >= self::FORMAT_DICTIONARY
            ? $this->fromDictionary($moduleName, $raw)
            : RecordVersion_Util_Helper::toStr($entry[$key . ($this->isJapanese ? '_ja' : '_en')] ?? '');
        if ($found !== '' && $found !== RecordVersion_Config_Model::FIELD_LABEL) {
            return $found;
        }
        if ($raw === RecordVersion_Config_Model::FIELD_LABEL) {
            // 「版」項目の名前の訳が残っていないときは、今の訳で出す
            return RecordVersion_Util_Helper::toStr(vtranslate($raw, RecordVersion_Config_Model::MODULE_NAME));
        }
        return $raw;
    }

    private function fromDictionary(string $moduleName, string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        $byLanguage = RecordVersion_Util_Helper::toArray(RecordVersion_Util_Helper::toArray($this->labels[$moduleName] ?? [])[$raw] ?? []);
        foreach ($this->languageOrder as $language) {
            $label = RecordVersion_Util_Helper::toStr($byLanguage[$language] ?? '');
            if ($label !== '') {
                return $label;
            }
        }
        return '';
    }

    /**
     * 表示名の辞書に入れる言語（システムに登録されている全言語。無効の言語も含む）
     *
     * @return string[] 言語コード
     */
    public static function getTargetLanguages(): array
    {
        $languages = array_map('strval', array_keys(Vtiger_Language::getAll(true)));
        sort($languages);
        return $languages;
    }

    /**
     * 過去の版の内容から、辞書に入れる翻訳前の名前を集める
     *
     * @param array<string, mixed> $snapshot
     * @return array<string, array<string, true>> モジュール名 => 翻訳前の名前 => true
     */
    public static function collectNames(array $snapshot): array
    {
        $names = [];
        $moduleName = RecordVersion_Util_Helper::toStr($snapshot['module'] ?? '');
        self::collectFieldNames($names, $moduleName, RecordVersion_Util_Helper::toArray($snapshot['fields'] ?? []));

        $inventory = RecordVersion_Util_Helper::toArray($snapshot['inventory'] ?? []);
        foreach (RecordVersion_Util_Helper::toList($inventory['lines'] ?? []) as $line) {
            self::collectTaxNames($names, $moduleName, RecordVersion_Util_Helper::toArray($line)['taxes'] ?? []);
        }
        self::collectTaxNames($names, $moduleName, $inventory['group_taxes'] ?? []);
        foreach (RecordVersion_Util_Helper::toList($inventory['charges'] ?? []) as $charge) {
            $charge = RecordVersion_Util_Helper::toArray($charge);
            self::addName($names, $moduleName, $charge['name'] ?? '');
            self::collectTaxNames($names, $moduleName, $charge['taxes'] ?? []);
        }

        foreach (RecordVersion_Util_Helper::toList($snapshot['relations'] ?? []) as $relation) {
            $relation = RecordVersion_Util_Helper::toArray($relation);
            $relatedModule = RecordVersion_Util_Helper::toStr($relation['related_module'] ?? '');
            self::addName($names, $relatedModule, $relation['label'] ?? '');
            foreach (RecordVersion_Util_Helper::toList($relation['records'] ?? []) as $item) {
                self::collectFieldNames($names, $relatedModule, RecordVersion_Util_Helper::toArray(RecordVersion_Util_Helper::toArray($item)['fields'] ?? []));
            }
        }
        return $names;
    }

    /**
     * 表示名の辞書を作る
     *
     * 言語ファイルが壊れていて読めない言語は、その言語だけを飛ばす（改版は止めない）。
     *
     * @param array<string, array<string, true>> $names collectNames() で集めた名前
     * @param string[] $languages 言語コード
     * @return array{languages: string[], labels: array<string, array<string, array<string, string>>>} languages は辞書を作れた言語
     */
    public static function buildDictionary(array $names, array $languages): array
    {
        $labels = [];
        $usable = [];
        foreach ($languages as $language) {
            try {
                foreach ($names as $moduleName => $raws) {
                    foreach (array_keys($raws) as $raw) {
                        $translated = self::translate((string) $raw, (string) $moduleName, $language);
                        if ($translated !== null) {
                            $labels[(string) $moduleName][(string) $raw][$language] = $translated;
                        }
                    }
                }
                $usable[] = $language;
            } catch (Throwable $e) {
                // 読めない言語の訳は 1 つも入れない（途中まで入れた訳も外す）
                foreach ($labels as $moduleName => $byName) {
                    foreach (array_keys($byName) as $raw) {
                        unset($labels[$moduleName][$raw][$language]);
                        if (empty($labels[$moduleName][$raw])) {
                            unset($labels[$moduleName][$raw]);
                        }
                    }
                    if (empty($labels[$moduleName])) {
                        unset($labels[$moduleName]);
                    }
                }
            }
        }
        return ['languages' => $usable, 'labels' => $labels];
    }

    /**
     * その言語の訳（無ければ null。既定の言語の訳にはしない）
     */
    private static function translate(string $raw, string $moduleName, string $language): ?string
    {
        $translated = Vtiger_Language_Handler::getLanguageTranslatedString($language, decode_html($raw), $moduleName);
        if ($translated === null || $translated === '') {
            return null;
        }
        // 言語の変換（システム設定の文言の置き換え）を今の画面と同じにかける
        return RecordVersion_Util_Helper::toStr(Settings_LanguageConverter_Module_Model::convertTranslate($translated, $moduleName));
    }

    /**
     * @param array<string, array<string, true>> $names
     * @param array<int|string, mixed> $fields
     */
    private static function collectFieldNames(array &$names, string $moduleName, array $fields): void
    {
        foreach ($fields as $entry) {
            $entry = RecordVersion_Util_Helper::toArray($entry);
            self::addName($names, $moduleName, $entry['label'] ?? '');
            self::addName($names, $moduleName, $entry['block_label'] ?? '');
        }
    }

    /**
     * @param array<string, array<string, true>> $names
     */
    private static function collectTaxNames(array &$names, string $moduleName, mixed $taxes): void
    {
        foreach (RecordVersion_Util_Helper::toList($taxes) as $tax) {
            self::addName($names, $moduleName, RecordVersion_Util_Helper::toArray($tax)['label'] ?? '');
        }
    }

    /**
     * @param array<string, array<string, true>> $names
     */
    private static function addName(array &$names, string $moduleName, mixed $raw): void
    {
        $name = RecordVersion_Util_Helper::toStr($raw);
        if ($moduleName !== '' && $name !== '') {
            $names[$moduleName][$name] = true;
        }
    }
}
