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
 * 繰り返し請求の請求日・支払期限の計算
 *
 * 受注の保存時（RecurringInvoiceHandler）と請求書の自動作成（RecurringInvoice.service）で
 * 同じ計算を使うためにここへまとめている。DB には触らない。
 */
class SalesOrder_RecurringInvoiceSchedule_Model
{
    /** 請求日の選択肢のうち、月末を表す値 */
    public const END_OF_MONTH = 'End of Month';

    /** 請求日を指定できる周期。日次・週次は「毎月◯日」と両立しない */
    private const MONTH_BASED_FREQUENCIES = ['monthly', 'quarterly', 'half-yearly', 'yearly'];

    /** 支払い期限の指定方法（payment_due_type）で、支払い月・支払い日を使う値 */
    public const PAYMENT_DUE_TYPE_FIXED_DATE = 'Fixed Date';

    /** 支払い期限（payment_duration）の「〜日後」の日数 */
    private const PAYMENT_DURATION_DAYS = [
        'net 01 day' => 1,
        'net 05 days' => 5,
        'net 07 days' => 7,
        'net 10 days' => 10,
        'net 15 days' => 15,
        'net 30 days' => 30,
        'net 45 days' => 45,
        'net 60 days' => 60,
    ];

    /** 支払い月（payment_due_month）と、請求月から数えた月数 */
    private const PAYMENT_DUE_MONTH_OFFSETS = [
        'Same Month' => 0,
        'Next Month' => 1,
        'Two Months Later' => 2,
    ];

    public static function isInvoiceDayApplicable(?string $frequency): bool
    {
        return in_array(strtolower(trim((string) $frequency)), self::MONTH_BASED_FREQUENCIES, true);
    }

    /**
     * 支払い期限の指定方法が「日付で指定」か
     * 未設定は「日数で指定」として扱う（指定方法を追加する前の受注のため）。
     */
    public static function isFixedDueDate(?string $paymentDueType): bool
    {
        return $paymentDueType === self::PAYMENT_DUE_TYPE_FIXED_DATE;
    }

    /**
     * 周期から求めた請求日を、指定された請求日に合わせる
     * 指定がない、または指定できない周期のときはそのまま返す。
     *
     * @param string $date Y-m-d
     * @return string Y-m-d
     */
    public static function applyInvoiceDay(string $date, ?string $frequency, ?string $invoiceDay): string
    {
        if ($invoiceDay === null || !self::isInvoiceDayApplicable($frequency) || !self::isValidDay($invoiceDay)) {
            return $date;
        }
        list($year, $month) = explode('-', $date);

        return self::getDateOnDay((int) $year, (int) $month, $invoiceDay);
    }

    /**
     * 受注の保存時に、次回請求日を指定された請求日に合わせる
     * 開始時期より前にはしない。同じ月の請求日が開始時期より前なら翌月にする。
     *
     * @param string $nextInvoiceDate Y-m-d
     * @param string $startDate       Y-m-d
     * @return string Y-m-d
     */
    public static function alignNextInvoiceDate(string $nextInvoiceDate, string $startDate, ?string $frequency, ?string $invoiceDay): string
    {
        $aligned = self::applyInvoiceDay($nextInvoiceDate, $frequency, $invoiceDay);
        // 請求日が変わったなら $invoiceDay は有効な値
        if ($invoiceDay === null || $aligned === $nextInvoiceDate || $aligned >= $startDate) {
            return $aligned;
        }
        list($year, $month) = explode('-', $aligned);

        return self::getDateOnDay((int) $year, (int) $month + 1, $invoiceDay);
    }

    /**
     * 支払期限を求める
     *
     * 指定方法が「日数で指定」なら請求日に支払い期限の日数を足す。
     * 指定方法が「日付で指定」なら請求月から支払い月だけ進めた月の支払い日にする。
     * それが請求日より前になるとき（25日の請求で「当月20日」など）は翌月にする。
     * 必要な値が未設定・不明なときは請求日を返す。
     *
     * @param string $invoiceDate Y-m-d
     * @return string Y-m-d
     */
    public static function getDueDate(
        string $invoiceDate,
        ?string $paymentDueType,
        ?string $paymentDuration,
        ?string $paymentDueMonth = '',
        ?string $paymentDueDay = ''
    ): string {
        list($year, $month, $day) = array_map('intval', explode('-', $invoiceDate));

        if (!self::isFixedDueDate($paymentDueType)) {
            $days = self::PAYMENT_DURATION_DAYS[strtolower(trim((string) $paymentDuration))] ?? 0;

            return date('Y-m-d', self::makeTime($year, $month, $day + $days));
        }
        if ($paymentDueDay === null || !self::isValidDay($paymentDueDay)) {
            return $invoiceDate;
        }

        $offset = self::PAYMENT_DUE_MONTH_OFFSETS[(string) $paymentDueMonth] ?? 0;
        $dueDate = self::getDateOnDay($year, $month + $offset, $paymentDueDay);
        if ($dueDate < $invoiceDate) {
            $dueDate = self::getDateOnDay($year, $month + $offset + 1, $paymentDueDay);
        }

        return $dueDate;
    }

    /**
     * 指定月の該当日を返す。その月にない日（2月31日など）は月末にする。
     * $month は 13 以上でもよい（翌年に繰り上がる）。
     */
    private static function getDateOnDay(int $year, int $month, string $daySetting): string
    {
        $firstDay = self::makeTime($year, $month, 1);
        $lastDay = (int) date('t', $firstDay);
        $day = $daySetting === self::END_OF_MONTH ? $lastDay : min(self::parseDay($daySetting), $lastDay);

        return date('Y-m-', $firstDay) . sprintf('%02d', $day);
    }

    /**
     * 年月日のタイムスタンプを返す。月・日は範囲外でもよい（mktime と同じく繰り上がる）
     */
    private static function makeTime(int $year, int $month, int $day): int
    {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        if ($timestamp === false) {
            throw new RuntimeException(sprintf('日付を計算できません: %d-%d-%d', $year, $month, $day));
        }

        return $timestamp;
    }

    private static function isValidDay(string $daySetting): bool
    {
        return $daySetting === self::END_OF_MONTH || self::parseDay($daySetting) > 0;
    }

    /** 'Day 20' → 20。日付として不正な値は 0 を返す */
    private static function parseDay(string $daySetting): int
    {
        if (preg_match('/^Day (\d{2})$/', $daySetting, $matches) !== 1) {
            return 0;
        }
        $day = (int) $matches[1];

        return $day >= 1 && $day <= 31 ? $day : 0;
    }
}
