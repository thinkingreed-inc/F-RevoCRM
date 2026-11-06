<?php

/*+***********************************************************************************
 * 自動採番の書式（uitype 997）
 *
 * 書式は固定文字とトークンからなる。DB に触れない処理だけをここに置く。
 *
 *   {YYYY} {YY} {MM} {DD}   基準日の年（4桁・2桁）・月・日
 *   {000}                   連番（0 の数だけゼロ埋め。桁があふれたら桁が増える）
 *   {項目名}                同じレコードの別項目の値
 *
 * 固定文字に使えるのは、文字・数字（日本語を含む）と - _ / . # ( ) と空白だけ。
 *************************************************************************************/

class Vtiger_AutoNumberFormat
{
    /** 書式・展開後の値の最大の長さ（vtiger_autonumber_config.format と、項目の列の長さ） */
    public const MAX_LENGTH = 100;

    /** 連番の桁数の上限 */
    public const MAX_SEQ_DIGITS = 9;

    /** 連番として展開される数の最大の桁数（INT の上限が 10 桁） */
    private const MAX_SEQ_RENDERED = 10;

    /** {項目名} の値として使える最大の長さ。超える値は空にする */
    public const MAX_FIELD_VALUE_LENGTH = 50;

    public const RESET_TYPES = ['none', 'year', 'month', 'day'];

    /** 書式のトークンの正規表現（括弧の中に括弧を含まない） */
    private const TOKEN_PATTERN = '/\{[^{}]*\}/u';

    /**
     * 書式を展開する
     *
     * @param string $format 検証済みの書式
     * @param DateTimeInterface $baseDate 基準日
     * @param int $seq 連番
     * @param array<string, string> $fieldValues 項目名 => 値（{項目名} に使う）
     */
    public static function render(string $format, DateTimeInterface $baseDate, int $seq, array $fieldValues = []): string
    {
        $result = preg_replace_callback(self::TOKEN_PATTERN, function (array $match) use ($baseDate, $seq, $fieldValues): string {
            $name = substr($match[0], 1, -1);
            switch (self::classifyToken($name)) {
                case 'year4':
                    return $baseDate->format('Y');
                case 'year2':
                    return $baseDate->format('y');
                case 'month':
                    return $baseDate->format('m');
                case 'day':
                    return $baseDate->format('d');
                case 'seq':
                    return str_pad((string) $seq, strlen($name), '0', STR_PAD_LEFT);
                case 'field':
                    return self::sanitizeFieldValue($fieldValues[$name] ?? '');
                default:
                    return '';
            }
        }, $format);
        return $result === null ? '' : $result;
    }

    /**
     * 連番のスコープを表すキー（リセットのたびに連番を 1 から数え直す単位）
     *
     * @param string $resetType none|year|month|day
     * @throws InvalidArgumentException リセットの種類が不明な場合
     */
    public static function scopeKey(string $resetType, DateTimeInterface $baseDate): string
    {
        switch ($resetType) {
            case 'none':
                return '';
            case 'year':
                return $baseDate->format('Y');
            case 'month':
                return $baseDate->format('Ym');
            case 'day':
                return $baseDate->format('Ymd');
        }
        throw new InvalidArgumentException('不明なリセットの種類: ' . $resetType);
    }

    /**
     * 書式とリセットの妥当性を調べる
     *
     * @return list<string> エラーのキー（無ければ空）。
     *   FORMAT_EMPTY / FORMAT_TOO_LONG / FORMAT_INVALID_CHAR / FORMAT_UNKNOWN_TOKEN /
     *   FORMAT_NO_SEQ / FORMAT_MULTI_SEQ / SEQ_DIGITS_RANGE /
     *   RESET_INVALID / RESET_NEEDS_YEAR / RESET_NEEDS_MONTH / RESET_NEEDS_DAY
     */
    public static function validate(string $format, string $resetType): array
    {
        if ($format === '') {
            return ['FORMAT_EMPTY'];
        }
        $errors = [];

        if (mb_strlen($format) > self::MAX_LENGTH || self::maxRenderedLength($format) > self::MAX_LENGTH) {
            $errors[] = 'FORMAT_TOO_LONG';
        }

        $literal = preg_replace(self::TOKEN_PATTERN, '', $format);
        $literal = $literal === null ? '' : $literal;
        $hasStrayBrace = strpbrk($literal, '{}') !== false;
        if (!self::isValidLiteral(str_replace(['{', '}'], '', $literal))) {
            $errors[] = 'FORMAT_INVALID_CHAR';
        }

        $kinds = [];
        $seqDigits = [];
        $hasUnknownToken = $hasStrayBrace;
        if (preg_match_all(self::TOKEN_PATTERN, $format, $matches) > 0) {
            foreach ($matches[0] as $token) {
                $name = substr($token, 1, -1);
                $kind = self::classifyToken($name);
                if ($kind === 'unknown') {
                    $hasUnknownToken = true;
                    continue;
                }
                $kinds[] = $kind;
                if ($kind === 'seq') {
                    $seqDigits[] = strlen($name);
                }
            }
        }
        if ($hasUnknownToken) {
            $errors[] = 'FORMAT_UNKNOWN_TOKEN';
        }

        if (count($seqDigits) === 0) {
            if (!$hasUnknownToken) {
                $errors[] = 'FORMAT_NO_SEQ';
            }
        } elseif (count($seqDigits) > 1) {
            $errors[] = 'FORMAT_MULTI_SEQ';
        } elseif ($seqDigits[0] > self::MAX_SEQ_DIGITS) {
            $errors[] = 'SEQ_DIGITS_RANGE';
        }

        if (!in_array($resetType, self::RESET_TYPES, true)) {
            $errors[] = 'RESET_INVALID';
        } else {
            $hasYear = in_array('year4', $kinds, true) || in_array('year2', $kinds, true);
            $hasMonth = in_array('month', $kinds, true);
            $hasDay = in_array('day', $kinds, true);
            if ($resetType !== 'none' && !$hasYear) {
                $errors[] = 'RESET_NEEDS_YEAR';
            }
            if (in_array($resetType, ['month', 'day'], true) && !$hasMonth) {
                $errors[] = 'RESET_NEEDS_MONTH';
            }
            if ($resetType === 'day' && !$hasDay) {
                $errors[] = 'RESET_NEEDS_DAY';
            }
        }

        return $errors;
    }

    /**
     * 書式に含まれる {項目名} の項目名（重複なし、出てきた順）
     *
     * @return list<string>
     */
    public static function getFieldTokens(string $format): array
    {
        $names = [];
        if (preg_match_all(self::TOKEN_PATTERN, $format, $matches) > 0) {
            foreach ($matches[0] as $token) {
                $name = substr($token, 1, -1);
                if (self::classifyToken($name) === 'field' && !in_array($name, $names, true)) {
                    $names[] = $name;
                }
            }
        }
        return $names;
    }

    /**
     * 固定文字として使える文字だけか
     */
    public static function isValidLiteral(string $text): bool
    {
        return preg_match('/\A[\p{L}\p{N}\p{M}\-_\/.#() ]*\z/u', $text) === 1;
    }

    /**
     * 展開後の最大の長さ（固定文字 + 年・月・日 + 連番（最大 10 桁）+ 項目の値の上限）
     * 項目の列に収まるかの検査に使う。不正な書式でも計算できる範囲で返す。
     */
    public static function maxRenderedLength(string $format): int
    {
        $length = 0;
        $literal = preg_replace_callback(self::TOKEN_PATTERN, function (array $match) use (&$length): string {
            $name = substr($match[0], 1, -1);
            switch (self::classifyToken($name)) {
                case 'year4':
                    $length += 4;
                    break;
                case 'year2':
                case 'month':
                case 'day':
                    $length += 2;
                    break;
                case 'seq':
                    $length += max(strlen($name), self::MAX_SEQ_RENDERED);
                    break;
                case 'field':
                    $length += self::MAX_FIELD_VALUE_LENGTH;
                    break;
            }
            return '';
        }, $format);
        return $length + mb_strlen($literal === null ? '' : $literal);
    }

    /**
     * トークンの種類
     *
     * @return string year4|year2|month|day|seq|field|unknown
     */
    private static function classifyToken(string $name): string
    {
        switch ($name) {
            case 'YYYY':
                return 'year4';
            case 'YY':
                return 'year2';
            case 'MM':
                return 'month';
            case 'DD':
                return 'day';
        }
        if (preg_match('/\A0+\z/', $name) === 1) {
            return 'seq';
        }
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $name) === 1) {
            return 'field';
        }
        return 'unknown';
    }

    /**
     * {項目名} に入れる値。使えない文字を含む・長すぎる値は空にする
     */
    private static function sanitizeFieldValue(string $value): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > self::MAX_FIELD_VALUE_LENGTH || !self::isValidLiteral($value)) {
            return '';
        }
        return $value;
    }
}
