<?php

/**
 * 通知メールの文面（モジュール・言語ごとの件名・本文、差し込み、既定の文面）
 *
 * 仕様 項目設定 4-4・通知メール 5-1・5-2：
 * - システム管理者がモジュールごとに、日本語・英語の件名・本文を設定できる。未設定なら既定の文面
 * - 差し込めるのは 6 つだけ。項目の値は差し込めない。5-2 に無い {…} はそのまま文字として送る
 * - 差し込む値は HTML として無害化して入れる。{record_url} は本文ではリンクにする
 * - 既定の文面では、コメントが空のときはコメントの行を出さない
 */
class NotificationBlock_Template_Model
{
    public const LANGUAGE_JA = 'ja_jp';
    public const LANGUAGE_EN = 'en_us';
    public const LANGUAGES = [self::LANGUAGE_JA, self::LANGUAGE_EN];

    public const SUBJECT_MAX_LENGTH = 255;

    /** 差し込み => 画面での名前（言語ファイル NotificationBlock.php のキー） */
    public const PLACEHOLDERS = [
        'sender_name' => 'LBL_PLACEHOLDER_SENDER_NAME',
        'module_name' => 'LBL_PLACEHOLDER_MODULE_NAME',
        'record_name' => 'LBL_PLACEHOLDER_RECORD_NAME',
        'record_url' => 'LBL_PLACEHOLDER_RECORD_URL',
        'comment' => 'LBL_PLACEHOLDER_COMMENT',
        'sent_at' => 'LBL_PLACEHOLDER_SENT_AT',
    ];

    /** 既定の本文の行（言語ファイルのキー）。コメントの行はコメントが空なら出さない */
    private const DEFAULT_BODY_LINES = [
        'LBL_DEFAULT_MAIL_INTRO',
        'LBL_DEFAULT_MAIL_RECORD',
        'LBL_DEFAULT_MAIL_LINK',
        'LBL_DEFAULT_MAIL_COMMENT',
        'LBL_DEFAULT_MAIL_SENT_AT',
    ];
    private const DEFAULT_COMMENT_LINE = 'LBL_DEFAULT_MAIL_COMMENT';

    /**
     * 利用者の言語設定から、使う文面の言語を決める（日本語・英語以外は英語。仕様 通知メール 5-1 No.5）
     */
    public static function resolveLanguage(string $userLanguage): string
    {
        return $userLanguage === self::LANGUAGE_JA ? self::LANGUAGE_JA : self::LANGUAGE_EN;
    }

    public static function getDefaultSubject(string $language): string
    {
        return self::translate('LBL_DEFAULT_MAIL_SUBJECT', $language);
    }

    public static function getDefaultBody(string $language, bool $withComment = true): string
    {
        $lines = [];
        foreach (self::DEFAULT_BODY_LINES as $key) {
            if (!$withComment && $key === self::DEFAULT_COMMENT_LINE) {
                continue;
            }
            $lines[] = NotificationBlock_Util_Helper::escape(self::translate($key, $language));
        }
        return '<p>' . implode('<br>', $lines) . '</p>';
    }

    /**
     * 保存してある文面（未設定の言語は含まない）
     *
     * @return array<string, array{subject: string, body: string}> 言語 => 文面
     */
    public static function getStored(int $tabId): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT language, subject, body FROM ' . NotificationBlock_Config_Model::TEMPLATES_TABLE . ' WHERE tabid = ?',
            [$tabId]
        );
        $stored = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $language = NotificationBlock_Util_Helper::toStr($row['language'] ?? '');
            if (!in_array($language, self::LANGUAGES, true)) {
                continue;
            }
            $stored[$language] = [
                'subject' => NotificationBlock_Util_Helper::toStr($row['subject'] ?? ''),
                'body' => NotificationBlock_Util_Helper::toStr($row['body'] ?? ''),
            ];
        }
        return $stored;
    }

    /**
     * 設定のダイアログに出す文面（保存済みの文面。未設定なら既定の文面）
     *
     * @return array<string, array{subject: string, body: string, is_default: bool, default_subject: string, default_body: string}>
     */
    public static function getForDialog(int $tabId): array
    {
        $stored = self::getStored($tabId);
        $templates = [];
        foreach (self::LANGUAGES as $language) {
            $defaultSubject = self::getDefaultSubject($language);
            $defaultBody = self::getDefaultBody($language);
            $templates[$language] = [
                'subject' => isset($stored[$language]) && $stored[$language]['subject'] !== '' ? $stored[$language]['subject'] : $defaultSubject,
                'body' => isset($stored[$language]) && $stored[$language]['body'] !== '' ? $stored[$language]['body'] : $defaultBody,
                'is_default' => !isset($stored[$language]),
                'default_subject' => $defaultSubject,
                'default_body' => $defaultBody,
            ];
        }
        return $templates;
    }

    /**
     * 文面を保存する
     *
     * 件名・本文の両方が空、または「既定の文面に戻す」を押した言語、または既定の文面と同じ言語は、
     * 未設定の状態に戻す（既定の文面を使う）。片方だけ空なら、その部分だけ既定の文面を使う。
     *
     * @param array<string, mixed> $templates 言語 => {subject, body, use_default}
     */
    public static function save(int $tabId, array $templates): void
    {
        $db = PearDatabase::getInstance();
        foreach (self::LANGUAGES as $language) {
            if (!array_key_exists($language, $templates)) {
                continue;
            }
            $template = NotificationBlock_Util_Helper::toArray($templates[$language]);
            $subject = self::normalizeSubject(NotificationBlock_Util_Helper::toStr($template['subject'] ?? ''));
            $body = self::sanitizeBody(NotificationBlock_Util_Helper::toStr($template['body'] ?? ''));
            $useDefault = NotificationBlock_Util_Helper::toStr($template['use_default'] ?? '') === '1';

            $db->pquery(
                'DELETE FROM ' . NotificationBlock_Config_Model::TEMPLATES_TABLE . ' WHERE tabid = ? AND language = ?',
                [$tabId, $language]
            );
            $isEmpty = $subject === '' && trim(strip_tags($body)) === '';
            $isSameAsDefault = $subject === self::getDefaultSubject($language) && $body === self::getDefaultBody($language);
            if ($useDefault || $isEmpty || $isSameAsDefault) {
                continue;
            }
            $db->pquery(
                'INSERT INTO ' . NotificationBlock_Config_Model::TEMPLATES_TABLE
                . ' (tabid, language, subject, body) VALUES (?, ?, ?, ?)',
                [$tabId, $language, $subject, $body]
            );
        }
    }

    /**
     * 宛先 1 人分のメールを作る
     *
     * @param array<string, string> $values 差し込む値（module_name は宛先の言語の表示名、sent_at は宛先の書式）
     * @param array<string, mixed>|null $override ほかの機能から渡された件名・本文（文字列、または 言語 => 文字列）
     * @return array{subject: string, html: string, text: string}
     */
    public static function render(int $tabId, string $language, array $values, ?array $override = null): array
    {
        $stored = self::getStored($tabId)[$language] ?? ['subject' => '', 'body' => ''];
        $subject = $stored['subject'];
        $body = $stored['body'];
        $comment = $values['comment'] ?? '';

        $overrideSubject = self::pickOverride($override['subject'] ?? null, $language);
        $overrideBody = self::pickOverride($override['body'] ?? null, $language);
        if ($overrideSubject !== '') {
            $subject = self::normalizeSubject($overrideSubject);
        }
        if ($overrideBody !== '') {
            $body = self::sanitizeBody($overrideBody);
        }
        if ($subject === '') {
            $subject = self::getDefaultSubject($language);
        }
        if (trim(strip_tags($body)) === '') {
            $body = self::getDefaultBody($language, $comment !== '');
        }

        $html = self::replaceInHtml($body, $values);
        return [
            'subject' => self::replaceInSubject($subject, $values),
            'html' => $html,
            'text' => self::htmlToText($html),
        ];
    }

    /**
     * 件名の差し込み（メールの件名は文字なので、値は HTML として扱わない。改行は空白にする）
     *
     * @param array<string, string> $values
     */
    public static function replaceInSubject(string $subject, array $values): string
    {
        $replaced = preg_replace_callback('/\{([a-z_]+)\}/', function (array $matches) use ($values): string {
            if (!array_key_exists($matches[1], self::PLACEHOLDERS)) {
                return $matches[0];
            }
            return $values[$matches[1]] ?? '';
        }, $subject);
        return trim((string) preg_replace('/\s+/u', ' ', (string) $replaced));
    }

    /**
     * 本文の差し込み
     *
     * - タグの中（href="{record_url}" など）は、無害化した値をそのまま入れる
     * - 文の中の {record_url} はリンクにする（すでにリンクの中なら URL の文字だけ）
     * - {comment} の改行は <br> にする
     *
     * @param array<string, string> $values
     */
    public static function replaceInHtml(string $html, array $values): string
    {
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return $html;
        }
        $inLink = false;
        $output = '';
        foreach ($parts as $part) {
            // PREG_SPLIT_NO_EMPTY なので空の部分は来ない
            if ($part[0] === '<') {
                if (preg_match('/^<a[\s>]/i', $part)) {
                    $inLink = true;
                } elseif (preg_match('/^<\/a\s*>/i', $part)) {
                    $inLink = false;
                }
                $output .= self::replaceInTag($part, $values);
                continue;
            }
            $output .= preg_replace_callback('/\{([a-z_]+)\}/', function (array $matches) use ($values, $inLink): string {
                $name = $matches[1];
                if (!array_key_exists($name, self::PLACEHOLDERS)) {
                    return $matches[0];
                }
                $value = $values[$name] ?? '';
                if ($name === 'record_url' && !$inLink && $value !== '') {
                    $escaped = NotificationBlock_Util_Helper::escape($value);
                    return '<a href="' . $escaped . '">' . $escaped . '</a>';
                }
                if ($name === 'comment') {
                    return nl2br(NotificationBlock_Util_Helper::escape($value), false);
                }
                return NotificationBlock_Util_Helper::escape($value);
            }, $part);
        }
        return $output;
    }

    /**
     * 本文（HTML）から、HTML を表示できないメールソフト向けの文を作る
     */
    public static function htmlToText(string $html): string
    {
        $text = (string) preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = (string) preg_replace('/<\/(p|div|li|h[1-6]|tr)>/i', "\n", $text);
        $text = (string) preg_replace('/<a\s[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is', '$2', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $text = (string) preg_replace("/[ \t]+\n/", "\n", $text);
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * 本文から script と、onclick などのイベントの属性を取り除く（ワークフローのメール送信と同じ処理）
     */
    public static function sanitizeBody(string $body): string
    {
        return trim(NotificationBlock_Util_Helper::toStr(Emails_Mailer_Model::getProcessedContent($body)));
    }

    public static function normalizeSubject(string $subject): string
    {
        $subject = trim((string) preg_replace('/[\r\n]+/', ' ', $subject));
        return mb_substr($subject, 0, self::SUBJECT_MAX_LENGTH);
    }

    /**
     * 差し込みの一覧（画面で選ぶための名前つき）
     *
     * @return array<int, array{code: string, label: string}>
     */
    public static function getPlaceholderList(): array
    {
        $list = [];
        foreach (self::PLACEHOLDERS as $name => $labelKey) {
            $list[] = [
                'code' => '{' . $name . '}',
                'label' => NotificationBlock_Util_Helper::toStr(vtranslate($labelKey, NotificationBlock_Config_Model::MODULE_NAME)),
            ];
        }
        return $list;
    }

    /**
     * タグの中の差し込みを、無害化した値に置き換える（エディターが {…} を %7B…%7D にしたものも含む）
     *
     * @param array<string, string> $values
     */
    private static function replaceInTag(string $tag, array $values): string
    {
        return (string) preg_replace_callback('/(?:\{|%7B)([a-z_]+)(?:\}|%7D)/i', function (array $matches) use ($values): string {
            $name = strtolower($matches[1]);
            if (!array_key_exists($name, self::PLACEHOLDERS)) {
                return $matches[0];
            }
            return NotificationBlock_Util_Helper::escape($values[$name] ?? '');
        }, $tag);
    }

    private static function pickOverride(mixed $value, string $language): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            foreach ([$language, self::LANGUAGE_EN, self::LANGUAGE_JA] as $candidate) {
                if (isset($value[$candidate]) && is_string($value[$candidate]) && $value[$candidate] !== '') {
                    return $value[$candidate];
                }
            }
        }
        return '';
    }

    private static function translate(string $key, string $language): string
    {
        return NotificationBlock_Util_Helper::toStr(
            Vtiger_Language_Handler::getTranslatedString($key, NotificationBlock_Config_Model::MODULE_NAME, $language)
        );
    }
}
