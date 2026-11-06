<?php

/*+***********************************************************************************
 * 自動採番（uitype 997）の処理で、DB・リクエストの値（mixed）を型のある値にする
 *************************************************************************************/

final class Vtiger_AutoNumberValue
{
    /**
     * 文字として扱える値だけを文字にする（配列・オブジェクト・null は空文字）
     *
     * @param mixed $value
     */
    public static function string($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * 数として読める値だけを整数にする（読めなければ 0）
     *
     * @param mixed $value
     */
    public static function int($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * 文字か null（空文字・null は null）
     *
     * @param mixed $value
     */
    public static function nullableString($value): ?string
    {
        $string = self::string($value);
        return $string === '' ? null : $string;
    }
}
