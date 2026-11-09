<?php

/**
 * 版管理（#1964）で使う小さな共通処理
 */
class RecordVersion_Util_Helper
{
    /**
     * DB や JSON から読んだ値を整数にする（数値でなければ 0）
     */
    public static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return 0;
    }

    /**
     * DB や JSON から読んだ値を文字列にする（文字列にできなければ空）
     */
    public static function toStr(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $array = [];
        foreach ($value as $key => $item) {
            $array[(string) $key] = $item;
        }
        return $array;
    }

    /**
     * @return array<int, mixed>
     */
    public static function toList(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * 結果の行を、PearDatabase の HTML エスケープを通さずに読む（JSON・入力した文字をそのまま扱うため）
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fetchRawRows(mixed $result): array
    {
        $rows = [];
        if (!($result instanceof ADORecordSet)) {
            return $rows;
        }
        while (!$result->EOF) {
            $row = [];
            foreach ((array) $result->fields as $key => $value) {
                if (!is_int($key)) {
                    $row[(string) $key] = $value;
                }
            }
            $rows[] = $row;
            $result->MoveNext();
        }
        return $rows;
    }

    /**
     * 結果の行を、PearDatabase の通常の読み方（HTML エスケープあり）で読む
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fetchRows(mixed $result): array
    {
        $db = PearDatabase::getInstance();
        $rows = [];
        if (!$result) {
            return $rows;
        }
        while ($row = $db->fetch_array($result)) {
            $rows[] = self::toArray($row);
        }
        return $rows;
    }
}
