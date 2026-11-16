<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\SalesOrder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SalesOrder_RecurringInvoiceSchedule_Model;

require_once dirname(__DIR__, 3) . '/modules/SalesOrder/models/RecurringInvoiceSchedule.php';

/**
 * 繰り返し請求の請求日・支払期限の計算（#1525）
 */
final class RecurringInvoiceScheduleTest extends TestCase
{
    /**
     * @return array<string, array{?string, bool}>
     */
    public static function frequencies(): array
    {
        return [
            '毎月' => ['Monthly', true],
            '四半期毎' => ['Quarterly', true],
            '半期' => ['Half-Yearly', true],
            '毎年' => ['Yearly', true],
            '毎週' => ['Weekly', false],
            '毎日' => ['Daily', false],
            '未設定' => [null, false],
        ];
    }

    #[DataProvider('frequencies')]
    public function test_請求日を指定できるのは月単位の周期だけ(?string $frequency, bool $expected): void
    {
        $this->assertSame($expected, SalesOrder_RecurringInvoiceSchedule_Model::isInvoiceDayApplicable($frequency));
    }

    /**
     * @return array<string, array{string, ?string, ?string, string}>
     */
    public static function invoiceDays(): array
    {
        return [
            '指定した日にする' => ['2026-03-10', 'Monthly', 'Day 25', '2026-03-25'],
            'その月にない日は月末にする' => ['2026-02-10', 'Monthly', 'Day 31', '2026-02-28'],
            '30日も2月は月末にする' => ['2026-02-10', 'Monthly', 'Day 30', '2026-02-28'],
            'うるう年の2月' => ['2028-02-10', 'Monthly', 'Day 31', '2028-02-29'],
            '月末' => ['2026-04-10', 'Quarterly', 'End of Month', '2026-04-30'],
            '毎週は請求日を使わない' => ['2026-03-10', 'Weekly', 'Day 25', '2026-03-10'],
            '請求日が未設定' => ['2026-03-10', 'Monthly', null, '2026-03-10'],
            '請求日が不正な値' => ['2026-03-10', 'Monthly', 'Day 32', '2026-03-10'],
        ];
    }

    #[DataProvider('invoiceDays')]
    public function test_請求日に合わせる(string $date, ?string $frequency, ?string $invoiceDay, string $expected): void
    {
        $this->assertSame($expected, SalesOrder_RecurringInvoiceSchedule_Model::applyInvoiceDay($date, $frequency, $invoiceDay));
    }

    public function test_同じ月の請求日が開始時期より後ならその日にする(): void
    {
        $this->assertSame(
            '2026-03-25',
            SalesOrder_RecurringInvoiceSchedule_Model::alignNextInvoiceDate('2026-03-10', '2026-03-10', 'Monthly', 'Day 25')
        );
    }

    public function test_同じ月の請求日が開始時期より前なら翌月にする(): void
    {
        $this->assertSame(
            '2026-04-25',
            SalesOrder_RecurringInvoiceSchedule_Model::alignNextInvoiceDate('2026-03-26', '2026-03-26', 'Monthly', 'Day 25')
        );
    }

    public function test_翌月にするとき翌月にない日は月末にする(): void
    {
        $this->assertSame(
            '2026-02-28',
            SalesOrder_RecurringInvoiceSchedule_Model::alignNextInvoiceDate('2026-01-31', '2026-01-31', 'Monthly', 'Day 30')
        );
    }

    /**
     * @return array<string, array{string, ?string, ?string, ?string, ?string, string}>
     */
    public static function dueDates(): array
    {
        return [
            // 日数で指定
            '30日後' => ['2026-01-31', 'Days After', 'Net 30 days', null, null, '2026-03-02'],
            '指定方法が未設定なら日数で指定' => ['2026-03-25', null, 'Net 10 days', null, null, '2026-04-04'],
            '一覧にない日数は請求日' => ['2026-03-25', 'Days After', 'Net 90 days', null, null, '2026-03-25'],
            '支払い期限が未設定なら請求日' => ['2026-03-25', 'Days After', null, null, null, '2026-03-25'],
            // 日付で指定
            '翌月20日' => ['2026-03-25', 'Fixed Date', null, 'Next Month', 'Day 20', '2026-04-20'],
            '当月末' => ['2026-03-25', 'Fixed Date', null, 'Same Month', 'End of Month', '2026-03-31'],
            '当月で請求日より前なら翌月' => ['2026-03-25', 'Fixed Date', null, 'Same Month', 'Day 20', '2026-04-20'],
            '当月で請求日と同じ日' => ['2026-03-20', 'Fixed Date', null, 'Same Month', 'Day 20', '2026-03-20'],
            '翌々月末' => ['2026-03-25', 'Fixed Date', null, 'Two Months Later', 'End of Month', '2026-05-31'],
            '年をまたぐ' => ['2026-12-10', 'Fixed Date', null, 'Next Month', 'Day 05', '2027-01-05'],
            'その月にない日は月末' => ['2026-12-25', 'Fixed Date', null, 'Two Months Later', 'Day 31', '2027-02-28'],
            '支払い月が未設定なら当月' => ['2026-03-10', 'Fixed Date', null, null, 'Day 20', '2026-03-20'],
            '支払い日が未設定なら請求日' => ['2026-03-25', 'Fixed Date', null, 'Next Month', null, '2026-03-25'],
            '支払い日が不正なら請求日' => ['2026-03-25', 'Fixed Date', null, 'Next Month', 'Day 00', '2026-03-25'],
        ];
    }

    #[DataProvider('dueDates')]
    public function test_支払期限を求める(
        string $invoiceDate,
        ?string $paymentDueType,
        ?string $paymentDuration,
        ?string $paymentDueMonth,
        ?string $paymentDueDay,
        string $expected
    ): void {
        $this->assertSame(
            $expected,
            SalesOrder_RecurringInvoiceSchedule_Model::getDueDate($invoiceDate, $paymentDueType, $paymentDuration, $paymentDueMonth, $paymentDueDay)
        );
    }
}
