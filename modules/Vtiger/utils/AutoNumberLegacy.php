<?php

/*+***********************************************************************************
 * 連番項目（uitype 4）の設定を、自動採番（uitype 997）の設定に読み替える。DB に触れない処理だけを置く。
 *
 * uitype 4 は vtiger_modentity_num の「接頭辞 + 次に使う番号（cur_id）」で番号を作る。
 *   例: prefix=POT, cur_id=1  →  POT1, POT2, ...
 *   cur_id の先頭が 0 のときは桁数を保つ（0001 → 0002 → ... → 0999 → 1000）。
 * これは書式 `POT{0}` / `POT{0000}` と同じ振る舞い（桁があふれたら桁が増える）。
 *************************************************************************************/

require_once 'modules/Vtiger/utils/AutoNumber.php';

class Vtiger_AutoNumberLegacy
{
    /**
     * @param string $prefix vtiger_modentity_num.prefix
     * @param string $curId vtiger_modentity_num.cur_id（次に使う番号。先頭 0 の桁合わせを含む）
     * @return array{format: string, start_number: int, cur_value: int, warnings: list<string>}
     *   format: 自動採番の書式 / start_number: 開始番号 / cur_value: 最後に払い出した番号（counter の初期値）
     */
    public static function convert(string $prefix, string $curId): array
    {
        $warnings = [];
        $literal = self::sanitizePrefix($prefix);
        if ($literal !== $prefix) {
            $warnings[] = 'PREFIX_CHANGED';
        }

        $digits = self::seqDigits($curId);
        $next = ctype_digit($curId) ? self::toInt($curId) : 1;
        if ($next < 1) {
            $next = 1;
        }

        $seqToken = '{' . str_repeat('0', $digits) . '}';
        $maxLiteral = Vtiger_AutoNumberFormat::MAX_LENGTH - Vtiger_AutoNumberFormat::maxRenderedLength($seqToken);
        if (mb_strlen($literal) > $maxLiteral) {
            $literal = mb_substr($literal, 0, $maxLiteral);
            $warnings[] = 'PREFIX_TRUNCATED';
        }

        return [
            'format' => $literal . $seqToken,
            'start_number' => $next,
            'cur_value' => $next - 1,
            'warnings' => $warnings,
        ];
    }

    /**
     * 連番の桁数。先頭が 0 の番号（0001）はその桁数、そうでなければ 1（桁をそろえない）
     */
    public static function seqDigits(string $curId): int
    {
        if (!ctype_digit($curId) || $curId[0] !== '0') {
            return 1;
        }
        return min(strlen($curId), Vtiger_AutoNumberFormat::MAX_SEQ_DIGITS);
    }

    /**
     * 固定文字として使えない文字を取り除く（{ } と、許可されていない記号）
     */
    public static function sanitizePrefix(string $prefix): string
    {
        $prefix = html_entity_decode($prefix, ENT_QUOTES, 'UTF-8');
        $chars = preg_split('//u', $prefix, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return '';
        }
        $kept = array_filter($chars, static fn (string $char): bool => Vtiger_AutoNumberFormat::isValidLiteral($char));
        return implode('', $kept);
    }

    private static function toInt(string $digits): int
    {
        $value = (int) ltrim($digits, '0');
        return min($value, Vtiger_AutoNumber::MAX_NUMBER);
    }
}
