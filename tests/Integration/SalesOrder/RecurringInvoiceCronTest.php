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

namespace Tests\Integration\SalesOrder;

use DateTimeImmutable;
use PearDatabase;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/include/database/PearDatabase.php';

/**
 * 受注の繰り返し請求 cron — cron/modules/SalesOrder/RecurringInvoice.service
 *
 * 受注は通常の保存処理で作成し（別プロセス）、繰り返し請求の設定は
 * vtiger_invoice_recurring_info / vtiger_salesorder へ直接書き込んで状態を作る。
 * cron は本番と同じく vtigercron.php --service=RecurringInvoice で実行する。
 *
 * 件名が FIXTURE_PREFIX で始まる受注・取引先と、それらから作られた請求だけを操作し、
 * 既存データには触らない。
 */
final class RecurringInvoiceCronTest extends TestCase
{
    private const FIXTURE_PREFIX = 'FRTestRecurring_';

    /** cron が終わらない場合に打ち切るまでの秒数 */
    private const CRON_TIMEOUT_SECONDS = 120;

    private PearDatabase $db;

    private DateTimeImmutable $today;

    /** @var array<string, mixed> テスト前の RecurringInvoice タスクの実行状態（tearDown で戻す） */
    private array $originalTaskState = [];

    /** 直近の cron 実行の出力（失敗時のメッセージに含める） */
    private string $lastCronOutput = '';

    /** cron の実行可否・二重起動の判定に使う列。テストでは毎回「今すぐ実行できる」状態に戻す */
    private const TASK_STATE_COLUMNS = ['status', 'laststart', 'lastend', 'next_run_at', 'owner_host', 'owner_pid', 'last_heartbeat'];

    protected function setUp(): void
    {
        $this->db = PearDatabase::getInstance();
        try {
            $result = $this->db->pquery('SELECT 1 FROM vtiger_cron_task WHERE name = ?', ['RecurringInvoice']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false || $this->db->num_rows($result) === 0) {
            $this->markTestSkipped('テスト用DBに RecurringInvoice の cron タスクがないため実行しない');
        }

        $this->today = new DateTimeImmutable('today');
        $taskResult = $this->db->pquery(
            'SELECT ' . implode(', ', self::TASK_STATE_COLUMNS) . ' FROM vtiger_cron_task WHERE name = ?',
            ['RecurringInvoice']
        );
        $this->originalTaskState = $this->db->fetchByAssoc($taskResult, -1, false);
        $this->cleanFixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanFixtures();
        if ($this->originalTaskState !== []) {
            $this->updateTaskState($this->originalTaskState);
        }
    }

    /**
     * 周期・期間が正しい受注は、これまでどおりの日付で請求が作成され、次回請求日が進む。
     * （修正前後で結果が変わらないことの確認）
     */
    public function testValidRecurringOrdersAreInvoicedAsBefore(): void
    {
        [$weekly, $monthly, $yearly, $daily, $monthEnd, $endFuture, $endPast] = $this->createSalesOrders(7);

        $monthlyStart = $this->today->modify('first day of -2 month');
        $monthEndStart = $this->lastDayOf31DayMonthBefore($this->today->modify('first day of -3 month'));

        $this->setRecurring($weekly, 'Weekly', $this->daysAgo(15));
        $this->setRecurring($monthly, 'Monthly', $monthlyStart);
        $this->setRecurring($yearly, 'Yearly', $this->today->modify('-1 year'));
        $this->setRecurring($daily, 'Daily', $this->daysAgo(3));
        $this->setRecurring($monthEnd, 'Monthly', $monthEndStart);
        $this->setRecurring($endFuture, 'Monthly', $monthlyStart, $this->today->modify('+10 day'));
        $this->setRecurring($endPast, 'Monthly', $monthlyStart, $this->daysAgo(1));

        $this->runRecurringInvoiceCron();

        $this->assertInvoices($weekly, [$this->daysAgo(15), $this->daysAgo(8), $this->daysAgo(1)], $this->today->modify('+6 day'));
        $this->assertInvoices($monthly, $this->monthlyDates($monthlyStart), $this->today->modify('first day of +1 month'));
        $this->assertInvoices($yearly, [$this->today->modify('-1 year'), $this->today], $this->today->modify('+1 year'));
        $this->assertInvoices($daily, [$this->daysAgo(3), $this->daysAgo(2), $this->daysAgo(1), $this->today], $this->today->modify('+1 day'));
        $monthEndDates = $this->monthlyDates($monthEndStart);
        $this->assertInvoices($monthEnd, $monthEndDates, $this->monthEndNext($monthEndStart, count($monthEndDates)));
        $this->assertInvoices($endFuture, $this->monthlyDates($monthlyStart), $this->today->modify('first day of +1 month'));
        $this->assertInvoices($endPast, [], $monthlyStart);
    }

    /**
     * 周期が空・想定外の値の受注があっても cron は最後まで動き、後続の受注の請求が作成される。
     * 周期が不正な受注は請求を作らず、次回請求日も変えない。
     */
    public function testInvalidFrequencyDoesNotStopFollowingOrders(): void
    {
        [$before, $emptyFrequency, $noneFrequency, $after] = $this->createSalesOrders(4);

        $monthlyStart = $this->today->modify('first day of -2 month');
        $twoYearsAgo = $this->today->modify('-2 year');

        $this->setRecurring($before, 'Monthly', $monthlyStart);
        $this->setRecurring($emptyFrequency, null, $twoYearsAgo);
        $this->setRecurring($noneFrequency, '--None--', $twoYearsAgo);
        $this->setRecurring($after, 'Monthly', $monthlyStart);

        $run = $this->runRecurringInvoiceCron();

        $this->assertStringNotContainsString('Fatal error', $run['output']);
        $this->assertStringContainsString("SalesOrder {$emptyFrequency} is skipped: invalid recurring frequency", $run['output']);
        $this->assertStringContainsString("SalesOrder {$noneFrequency} is skipped: invalid recurring frequency '--None--'", $run['output']);
        $this->assertInvoices($before, $this->monthlyDates($monthlyStart), $this->today->modify('first day of +1 month'));
        $this->assertInvoices($emptyFrequency, [], $twoYearsAgo);
        $this->assertInvoices($noneFrequency, [], $twoYearsAgo);
        $this->assertInvoices($after, $this->monthlyDates($monthlyStart), $this->today->modify('first day of +1 month'));
    }

    /**
     * 次回請求日が当日の受注は、当日付けで請求が作成される。
     * 先に処理された別の受注の請求日が使われないこと。
     */
    public function testOrderDueTodayIsInvoicedWithTodaysDate(): void
    {
        [$weekly, $dueToday] = $this->createSalesOrders(2);

        $this->setRecurring($weekly, 'Weekly', $this->daysAgo(15));
        $this->setRecurring($dueToday, 'Monthly', $this->today);

        $this->runRecurringInvoiceCron();

        $this->assertInvoices($weekly, [$this->daysAgo(15), $this->daysAgo(8), $this->daysAgo(1)], $this->today->modify('+6 day'));
        $this->assertInvoices($dueToday, [$this->today], $this->monthlyDates($this->today, $this->today->modify('+1 month'))[1]);
    }

    /**
     * @return list<int> 作成した受注 ID（ID 昇順 = cron の処理順）
     */
    private function createSalesOrders(int $count): array
    {
        $command = sprintf(
            '%s -d xdebug.mode=off -f tests/Support/salesorder/create_salesorders.php -- %d %s 2>&1',
            escapeshellarg(PHP_BINARY),
            $count,
            escapeshellarg(self::FIXTURE_PREFIX)
        );
        $output = [];
        $status = 0;
        exec($command, $output, $status);
        $decoded = json_decode((string) end($output), true);
        $this->assertSame(0, $status, implode("\n", $output));
        $this->assertIsArray($decoded, implode("\n", $output));

        $this->assertIsArray($decoded['salesorders'] ?? null, implode("\n", $output));

        return array_values(array_map('intval', $decoded['salesorders']));
    }

    private function setRecurring(
        int $salesOrderId,
        ?string $frequency,
        DateTimeImmutable $start,
        ?DateTimeImmutable $end = null,
        bool $enabled = true
    ): void {
        $this->db->pquery('UPDATE vtiger_salesorder SET enable_recurring = ? WHERE salesorderid = ?', [$enabled ? 1 : 0, $salesOrderId]);
        $this->db->pquery('DELETE FROM vtiger_invoice_recurring_info WHERE salesorderid = ?', [$salesOrderId]);
        $this->db->pquery(
            'INSERT INTO vtiger_invoice_recurring_info
                (salesorderid, recurring_frequency, start_period, end_period, last_recurring_date, payment_duration, invoice_status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $salesOrderId,
                $frequency,
                $start->format('Y-m-d'),
                $end ? $end->format('Y-m-d') : null,
                $start->format('Y-m-d'),
                'Net 30 days',
                'AutoCreated',
            ]
        );
    }

    /**
     * @return array{output: string, status: int}
     */
    private function runRecurringInvoiceCron(): array
    {
        // 前回の実行時刻や実行中フラグが残っていると「まだ実行時刻ではない」として飛ばされる
        $this->updateTaskState([
            'status' => 1,
            'laststart' => null,
            'lastend' => null,
            'next_run_at' => 0,
            'owner_host' => null,
            'owner_pid' => 0,
            'last_heartbeat' => 0,
        ]);

        $command = sprintf(
            'timeout %d %s -d xdebug.mode=off -d memory_limit=256M -f tests/Support/salesorder/run_vtigercron_with_autoload.php -- --service=RecurringInvoice 2>&1',
            self::CRON_TIMEOUT_SECONDS,
            escapeshellarg(PHP_BINARY)
        );
        $output = [];
        $status = 0;
        exec($command, $output, $status);
        $this->lastCronOutput = implode("\n", $output);

        return ['output' => $this->lastCronOutput, 'status' => (int) $status];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function updateTaskState(array $state): void
    {
        $assignments = implode(', ', array_map(static fn (string $column): string => "{$column} = ?", array_keys($state)));
        $this->db->pquery(
            "UPDATE vtiger_cron_task SET {$assignments} WHERE name = ?",
            array_merge(array_values($state), ['RecurringInvoice'])
        );
    }

    /**
     * @param list<DateTimeImmutable> $expectedInvoiceDates
     */
    private function assertInvoices(int $salesOrderId, array $expectedInvoiceDates, DateTimeImmutable $expectedNextDate): void
    {
        $result = $this->db->pquery(
            'SELECT vtiger_invoice.invoicedate FROM vtiger_invoice
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_invoice.invoiceid AND vtiger_crmentity.deleted = 0
             WHERE vtiger_invoice.salesorderid = ? ORDER BY vtiger_invoice.invoicedate, vtiger_invoice.invoiceid',
            [$salesOrderId]
        );
        $actualDates = [];
        while ($row = $this->db->fetchByAssoc($result)) {
            $actualDates[] = $row['invoicedate'];
        }
        $expectedDates = array_map(static fn (DateTimeImmutable $date): string => $date->format('Y-m-d'), $expectedInvoiceDates);
        $this->assertSame($expectedDates, $actualDates, "受注 {$salesOrderId} の請求日\n--- cron の出力 ---\n{$this->lastCronOutput}");

        $nextDateResult = $this->db->pquery('SELECT last_recurring_date FROM vtiger_invoice_recurring_info WHERE salesorderid = ?', [$salesOrderId]);
        $nextDate = $this->db->query_result($nextDateResult, 0, 'last_recurring_date');
        $this->assertSame($expectedNextDate->format('Y-m-d'), $nextDate, "受注 {$salesOrderId} の次回請求日");
    }

    private function daysAgo(int $days): DateTimeImmutable
    {
        return $this->today->modify("-{$days} day");
    }

    /**
     * 開始日から 1 か月ごとの請求日（今日以前の分）。月末を超える日は月末日に寄せる。
     * $until を指定した場合は、その日までを返す。
     *
     * @return list<DateTimeImmutable>
     */
    private function monthlyDates(DateTimeImmutable $start, ?DateTimeImmutable $until = null): array
    {
        $until = $until ?? $this->today;
        $day = (int) $start->format('j');
        $dates = [];
        for ($k = 0; ; $k++) {
            $date = $this->monthlyDate($start, $k, $day);
            if ($date > $until) {
                return $dates;
            }
            $dates[] = $date;
        }
    }

    private function monthlyDate(DateTimeImmutable $start, int $months, int $day): DateTimeImmutable
    {
        $firstOfMonth = $start->modify('first day of this month')->modify("+{$months} month");
        $lastDay = (int) $firstOfMonth->format('t');

        return $firstOfMonth->setDate((int) $firstOfMonth->format('Y'), (int) $firstOfMonth->format('n'), min($day, $lastDay));
    }

    private function monthEndNext(DateTimeImmutable $start, int $invoiceCount): DateTimeImmutable
    {
        return $this->monthlyDate($start, $invoiceCount, (int) $start->format('j'));
    }

    /** 指定日以前で最も近い「31 日まである月」の末日 */
    private function lastDayOf31DayMonthBefore(DateTimeImmutable $date): DateTimeImmutable
    {
        $month = $date->modify('first day of this month');
        while ((int) $month->format('t') !== 31) {
            $month = $month->modify('-1 month');
        }

        return $month->modify('last day of this month');
    }

    /** このテストで作成した受注・取引先・請求を論理削除し、繰り返し請求の設定を消す */
    private function cleanFixtures(): void
    {
        $like = self::FIXTURE_PREFIX . '%';
        $this->db->pquery(
            'UPDATE vtiger_crmentity
             INNER JOIN vtiger_invoice ON vtiger_invoice.invoiceid = vtiger_crmentity.crmid
             INNER JOIN vtiger_salesorder ON vtiger_salesorder.salesorderid = vtiger_invoice.salesorderid
             SET vtiger_crmentity.deleted = 1
             WHERE vtiger_salesorder.subject LIKE ?',
            [$like]
        );
        $this->db->pquery(
            'DELETE vtiger_invoice_recurring_info FROM vtiger_invoice_recurring_info
             INNER JOIN vtiger_salesorder ON vtiger_salesorder.salesorderid = vtiger_invoice_recurring_info.salesorderid
             WHERE vtiger_salesorder.subject LIKE ?',
            [$like]
        );
        $this->db->pquery(
            'UPDATE vtiger_crmentity
             INNER JOIN vtiger_salesorder ON vtiger_salesorder.salesorderid = vtiger_crmentity.crmid
             SET vtiger_crmentity.deleted = 1
             WHERE vtiger_salesorder.subject LIKE ?',
            [$like]
        );
        $this->db->pquery(
            'UPDATE vtiger_crmentity
             INNER JOIN vtiger_account ON vtiger_account.accountid = vtiger_crmentity.crmid
             SET vtiger_crmentity.deleted = 1
             WHERE vtiger_account.accountname LIKE ?',
            [$like]
        );
    }
}
