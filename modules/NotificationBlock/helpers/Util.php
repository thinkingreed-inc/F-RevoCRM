<?php

/**
 * 通知ブロック（#1965）で使う小さな共通処理
 */
class NotificationBlock_Util_Helper
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
     * 結果の行を、PearDatabase の HTML エスケープを通さずに読む（入力した文字をそのまま扱うため）
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
     * 今の日時（DB に保存する形。製品のほかの日時と同じく PHP の既定のタイムゾーン）
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * DB に保存した日時を、ログイン中の利用者のタイムゾーン・日付の書式で出す
     */
    public static function formatDateTime(string $dbDateTime): string
    {
        if ($dbDateTime === '') {
            return '';
        }
        return self::toStr(Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($dbDateTime));
    }

    /**
     * HTML の中に出す文字にする
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
