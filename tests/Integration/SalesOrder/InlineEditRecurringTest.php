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

use PearDatabase;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/include/database/PearDatabase.php';

/**
 * 受注のインライン編集と繰り返し請求 — SalesOrder_SaveAjax_Action / SalesOrder_Field_Model
 *
 * 繰り返し請求を有効にするには周期と開始日が要る（周期が空のままだと請求の自動作成ができない）。
 * 編集画面は入力チェックで防いでいるが、インライン編集（詳細画面・一覧画面）では
 * 「繰り返し有効」だけを保存できたため、周期が空のまま有効な受注ができていた。
 *
 * 受注の作成・インライン編集は別プロセス（tests/Support/salesorder/）で行い、
 * 件名が FIXTURE_PREFIX で始まる受注・取引先だけを操作する。
 */
final class InlineEditRecurringTest extends TestCase
{
    private const FIXTURE_PREFIX = 'FRTestInline_';

    private PearDatabase $db;

    protected function setUp(): void
    {
        $this->db = PearDatabase::getInstance();
        try {
            $result = $this->db->pquery('SELECT 1 FROM vtiger_tab WHERE name = ?', ['SalesOrder']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false || $this->db->num_rows($result) === 0) {
            $this->markTestSkipped('テスト用DBに受注モジュールがないため実行しない');
        }
        $this->cleanFixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanFixtures();
    }

    /** 繰り返しが無効な受注を、インライン編集で有効にすることはできない（周期が空のため） */
    public function testEnablingRecurringInlineIsRejectedWhileFrequencyIsEmpty(): void
    {
        [$salesOrderId] = $this->createSalesOrders(1);

        $response = $this->inlineSave($salesOrderId, 'enable_recurring', '1');

        $this->assertFalse($response['success'], json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');
        $error = $response['error'] ?? null;
        $this->assertIsArray($error);
        $message = $error['message'] ?? null;
        $this->assertIsString($message);
        $this->assertNotSame('', $message);
        $this->assertStringNotContainsString('LBL_', $message, '翻訳されたメッセージを返す');
        $this->assertSame('0', $this->salesOrderColumn($salesOrderId, 'enable_recurring'));
        $this->assertNull($this->recurringInfo($salesOrderId));
    }

    /** 周期と開始日がそろっていれば、繰り返し請求ブロックの項目をインライン編集で保存できる */
    public function testEditingRecurringFieldIsAllowedWhenFrequencyAndStartPeriodAreSet(): void
    {
        [$salesOrderId] = $this->createSalesOrders(1);
        $this->setRecurring($salesOrderId, true, 'Monthly', '2026-01-01');

        $response = $this->inlineSave($salesOrderId, 'recurring_frequency', 'Weekly');

        $this->assertTrue($response['success'], json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame('Weekly', $this->recurringInfo($salesOrderId)['recurring_frequency'] ?? null);
    }

    /** 有効な受注で周期を空にすることはできない */
    public function testClearingFrequencyWhileEnabledIsRejected(): void
    {
        [$salesOrderId] = $this->createSalesOrders(1);
        $this->setRecurring($salesOrderId, true, 'Monthly', '2026-01-01');

        $response = $this->inlineSave($salesOrderId, 'recurring_frequency', '');

        $this->assertFalse($response['success'], json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame('Monthly', $this->recurringInfo($salesOrderId)['recurring_frequency'] ?? null);
    }

    /**
     * 既に「有効・周期が空」になっている受注は、周期を入力して直せる。
     * 繰り返しを無効にすることもでき、繰り返し請求と関係ない項目の編集も妨げない。
     */
    public function testBrokenRecordCanBeRepairedDisabledOrEditedInline(): void
    {
        [$repair, $disable, $unrelated] = $this->createSalesOrders(3);
        foreach ([$repair, $disable, $unrelated] as $salesOrderId) {
            $this->setRecurring($salesOrderId, true, null, '2026-01-01');
        }

        $repaired = $this->inlineSave($repair, 'recurring_frequency', 'Monthly');
        $disabled = $this->inlineSave($disable, 'enable_recurring', '0');
        $edited = $this->inlineSave($unrelated, 'subject', self::FIXTURE_PREFIX . 'edited');

        $this->assertTrue($repaired['success'], json_encode($repaired, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame('Monthly', $this->recurringInfo($repair)['recurring_frequency'] ?? null);

        $this->assertTrue($disabled['success'], json_encode($disabled, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame('0', $this->salesOrderColumn($disable, 'enable_recurring'));
        $this->assertNull($this->recurringInfo($disable));

        $this->assertTrue($edited['success'], json_encode($edited, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame(self::FIXTURE_PREFIX . 'edited', $this->salesOrderColumn($unrelated, 'subject'));
    }

    /**
     * 詳細画面では、繰り返しが無効な受注の「繰り返し有効」をインライン編集できない（編集画面で設定する）。
     * 有効な受注は、無効にするためにインライン編集できる。
     * 一覧画面（受注ごとの状態を持たない）は従来どおり編集でき、保存時のチェックで防ぐ。
     */
    public function testEnableRecurringIsNotInlineEditableOnDetailViewWhileDisabled(): void
    {
        [$disabled, $enabled] = $this->createSalesOrders(2);
        $this->setRecurring($enabled, true, 'Monthly', '2026-01-01');

        $disabledEditable = $this->ajaxEditable($disabled);
        $enabledEditable = $this->ajaxEditable($enabled);

        $this->assertFalse($disabledEditable['enable_recurring']);
        $this->assertFalse($disabledEditable['recurring_frequency']);
        $this->assertTrue($disabledEditable['list:enable_recurring']);

        $this->assertTrue($enabledEditable['enable_recurring']);
        $this->assertTrue($enabledEditable['recurring_frequency']);
        $this->assertTrue($enabledEditable['start_period']);
    }

    /**
     * 一覧画面のインライン編集は field を付けず、入力欄のある列（件名・担当・「繰り返し有効」など）をまとめて送る。
     * 繰り返しが有効な受注で件名だけを編集しても、繰り返し請求の設定は変わらず保存できる。
     */
    public function testListInlineEditOnEnabledRecordKeepsRecurringSettings(): void
    {
        [$salesOrderId] = $this->createSalesOrders(1);
        $this->setRecurring($salesOrderId, true, 'Monthly', '2026-01-01');

        $response = $this->listInlineSave($salesOrderId, [
            'subject' => self::FIXTURE_PREFIX . 'list_edited',
            'assigned_user_id' => '1',
            'enable_recurring' => '1',
        ]);

        $this->assertTrue($response['success'], json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame(self::FIXTURE_PREFIX . 'list_edited', $this->salesOrderColumn($salesOrderId, 'subject'));
        $this->assertSame('1', $this->salesOrderColumn($salesOrderId, 'enable_recurring'));
        $recurringInfo = $this->recurringInfo($salesOrderId);
        $this->assertSame('AutoCreated', $recurringInfo['invoice_status'] ?? null);
        $this->assertSame('Monthly', $recurringInfo['recurring_frequency'] ?? null);
        $this->assertSame('2026-01-01', $recurringInfo['start_period'] ?? null);
        $this->assertSame('Net 30 days', $recurringInfo['payment_duration'] ?? null);
    }

    /** 既に「有効・周期が空」の受注でも、一覧画面で繰り返し請求と関係ない列は編集できる */
    public function testListInlineEditOnBrokenRecordIsAllowed(): void
    {
        [$salesOrderId] = $this->createSalesOrders(1);
        $this->setRecurring($salesOrderId, true, null, '2026-01-01');

        $response = $this->listInlineSave($salesOrderId, [
            'subject' => self::FIXTURE_PREFIX . 'list_broken_edited',
            'assigned_user_id' => '1',
            'enable_recurring' => '1',
        ]);

        $this->assertTrue($response['success'], json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame(self::FIXTURE_PREFIX . 'list_broken_edited', $this->salesOrderColumn($salesOrderId, 'subject'));
        $this->assertSame('2026-01-01', $this->recurringInfo($salesOrderId)['start_period'] ?? null);
    }

    /**
     * 周期も開始日も空の受注は、インライン編集では周期だけを入れても直せない（開始日が空のため）。
     * 編集画面で設定するか、繰り返しを無効にする。
     */
    public function testBrokenRecordWithoutStartPeriodCannotBeRepairedInline(): void
    {
        [$repair, $disable] = $this->createSalesOrders(2);
        foreach ([$repair, $disable] as $salesOrderId) {
            $this->setRecurring($salesOrderId, true, null, null);
        }

        $repaired = $this->inlineSave($repair, 'recurring_frequency', 'Monthly');
        $disabled = $this->inlineSave($disable, 'enable_recurring', '0');

        $this->assertFalse($repaired['success'], json_encode($repaired, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertNull($this->recurringInfo($repair)['recurring_frequency'] ?? null);
        $this->assertTrue($disabled['success'], json_encode($disabled, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertNull($this->recurringInfo($disable));
    }

    /** 一覧画面のインライン編集でも、周期が空のまま繰り返しを有効にすることはできない */
    public function testListInlineEnablingRecurringIsRejectedWhileFrequencyIsEmpty(): void
    {
        [$salesOrderId] = $this->createSalesOrders(1);

        $response = $this->listInlineSave($salesOrderId, [
            'subject' => self::FIXTURE_PREFIX . 'list_enabled',
            'assigned_user_id' => '1',
            'enable_recurring' => '1',
        ]);

        $this->assertFalse($response['success'], json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertSame('0', $this->salesOrderColumn($salesOrderId, 'enable_recurring'));
        $this->assertNull($this->recurringInfo($salesOrderId));
    }

    /**
     * @return list<int> 作成した受注 ID
     */
    private function createSalesOrders(int $count): array
    {
        $decoded = $this->runSupport(sprintf(
            'tests/Support/salesorder/create_salesorders.php -- %d %s',
            $count,
            escapeshellarg(self::FIXTURE_PREFIX)
        ));
        $this->assertIsArray($decoded['salesorders'] ?? null);

        return array_values(array_map('intval', $decoded['salesorders']));
    }

    /**
     * @return array<string, mixed> SaveAjax の応答
     */
    private function inlineSave(int $salesOrderId, string $field, string $value): array
    {
        return $this->runSupport(sprintf(
            'tests/Support/salesorder/inline_edit.php -- save %d %s %s',
            $salesOrderId,
            escapeshellarg($field),
            escapeshellarg($value)
        ));
    }

    /**
     * @param array<string, string> $columns 一覧の行の列 => 値
     * @return array<string, mixed> SaveAjax の応答
     */
    private function listInlineSave(int $salesOrderId, array $columns): array
    {
        return $this->runSupport(sprintf(
            'tests/Support/salesorder/inline_edit.php -- listsave %d %s',
            $salesOrderId,
            escapeshellarg((string) json_encode($columns))
        ));
    }

    /**
     * @return array<string, bool> 繰り返し請求ブロックの項目名 => インライン編集できるか
     */
    private function ajaxEditable(int $salesOrderId): array
    {
        $decoded = $this->runSupport(sprintf('tests/Support/salesorder/inline_edit.php -- editable %d', $salesOrderId));

        return array_map(static fn ($editable): bool => $editable === true, $decoded);
    }

    /**
     * 子プロセスを実行し、標準出力の最終行の JSON を返す。
     *
     * @return array<string, mixed>
     */
    private function runSupport(string $arguments): array
    {
        $command = sprintf('%s -d xdebug.mode=off -f %s 2>&1', escapeshellarg(PHP_BINARY), $arguments);
        $output = [];
        $status = 0;
        exec($command, $output, $status);
        $decoded = json_decode((string) end($output), true);
        $this->assertSame(0, $status, implode("\n", $output));
        $this->assertIsArray($decoded, implode("\n", $output));

        return $decoded;
    }

    /** 繰り返し請求の状態を直接作る（「有効・周期が空」はインライン編集の不具合で作られていた状態） */
    private function setRecurring(int $salesOrderId, bool $enabled, ?string $frequency, ?string $start): void
    {
        $this->db->pquery('UPDATE vtiger_salesorder SET enable_recurring = ? WHERE salesorderid = ?', [$enabled ? 1 : 0, $salesOrderId]);
        $this->db->pquery('DELETE FROM vtiger_invoice_recurring_info WHERE salesorderid = ?', [$salesOrderId]);
        $this->db->pquery(
            'INSERT INTO vtiger_invoice_recurring_info
                (salesorderid, recurring_frequency, start_period, end_period, last_recurring_date, payment_duration, invoice_status)
             VALUES (?, ?, ?, NULL, ?, ?, ?)',
            [$salesOrderId, $frequency, $start, $start, 'Net 30 days', 'AutoCreated']
        );
    }

    private function salesOrderColumn(int $salesOrderId, string $column): ?string
    {
        $result = $this->db->pquery("SELECT {$column} FROM vtiger_salesorder WHERE salesorderid = ?", [$salesOrderId]);
        $value = $this->db->query_result($result, 0, $column);

        return $value === null ? null : (string) $value;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recurringInfo(int $salesOrderId): ?array
    {
        $result = $this->db->pquery('SELECT * FROM vtiger_invoice_recurring_info WHERE salesorderid = ?', [$salesOrderId]);
        if ($this->db->num_rows($result) === 0) {
            return null;
        }

        return $this->db->fetchByAssoc($result, -1, false);
    }

    /** このテストで作成した受注・取引先を論理削除し、繰り返し請求の設定を消す */
    private function cleanFixtures(): void
    {
        $like = self::FIXTURE_PREFIX . '%';
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
