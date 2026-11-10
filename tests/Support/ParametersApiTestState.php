<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Settings/Parameters の API テストから、スタブの挙動を制御するための状態。
 *
 * スタブ本体（ParametersApiTestSupport.php）は vtiger と同じクラス名を名乗るため
 * 静的解析からは本物の定義に見える。テスト専用の操作をスタブ側に生やすと
 * 「未定義メソッド」として検出されてしまうので、この独立したクラスへ寄せている。
 */
class ParametersApiTestState
{
    /** 現在ユーザーを管理者として扱うか */
    public static bool $isAdmin = true;

    /** 現在ユーザーの id（getId() が返す値） */
    public static int $userId = 1;

    /**
     * getInstanceById() が返すレコードの元データ
     * @var array<int, array<string, mixed>>
     */
    public static array $records = [];

    /**
     * save() が呼ばれたときのレコード内容
     * @var array<int, array<string, mixed>>
     */
    public static array $saved = [];

    /** save() で例外を投げさせるか（エラー応答の検証用） */
    public static bool $throwOnSave = false;

    public static function reset(): void
    {
        self::$records = [];
        self::$saved = [];
        self::$isAdmin = true;
        self::$userId = 1;
        self::$throwOnSave = false;
    }

    /**
     * 編集対象のレコードを用意する
     *
     * @param array<string, mixed> $record
     */
    public static function seed(array $record): void
    {
        self::$records[self::toId($record['id'] ?? null)] = $record;
    }

    /** save() で保存された値を取り出す */
    public static function savedValue(int $id, string $field): mixed
    {
        return self::$saved[$id][$field] ?? null;
    }

    /** save() が一度でも呼ばれたか */
    public static function hasSaved(): bool
    {
        return self::$saved !== [];
    }

    /** レコードID を配列キーに使える int へ正規化する */
    public static function toId(mixed $id): int
    {
        return is_numeric($id) ? (int)$id : 0;
    }
}
