<?php

/**
 * マイグレーション: add_recurring_invoice_schedule_fields
 * 生成日時: 20261102114131
 *
 * 繰り返し請求で「毎月25日に請求、翌月20日に支払い」のように指定できるようにし、支払い方法を登録できるようにする。
 *
 *   1. 受注に請求日（invoice_day）を追加する
 *      周期が月単位（毎月・四半期毎・半期・毎年）の既存の受注には、開始時期の日を請求日として設定する。
 *      これまで請求は開始時期の日に作られていたため、請求される日は変わらない。
 *   2. 受注に支払い期限の指定方法（payment_due_type）・支払い月（payment_due_month）・支払い日（payment_due_day）を追加する
 *      指定方法が「日数で指定」なら既存の支払い期限（payment_duration）、「日付で指定」なら支払い月・支払い日を使う。
 *      既存の受注は指定方法を「日数で指定」にする。
 *   3. 受注と請求書の基本情報に支払い方法（payment_method）を追加する
 *      繰り返し請求に限らない情報のため、繰り返し請求情報ではなく基本情報に置き、どちらも期限日の後ろに並べる。
 *      項目名を揃えて選択肢を共有する。受注から請求書を作るとき、同名の項目は値が引き継がれる。
 *   4. 繰り返し請求情報の項目を「期間 → 請求のルール → 支払いのルール」の順に並べる
 *
 * 既存の項目・選択肢はスキップするため、途中まで適用済みの環境でも実行できる。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261102114131_AddRecurringInvoiceScheduleFields extends FRMigrationClass
{
    public const BLOCK_LABEL = 'Recurring Invoice Information';

    /**
     * マイグレーションを実行する
     */
    public function process(): void
    {
        $moduleInstance = Vtiger_Module::getInstance('SalesOrder');
        $blockInstance = Vtiger_Block::getInstance(self::BLOCK_LABEL, $moduleInstance);

        $this->addInvoiceDayField($moduleInstance, $blockInstance);
        $this->addPaymentDueFields($moduleInstance, $blockInstance);
        $this->addPaymentMethodField($moduleInstance);
        $this->addInvoicePaymentMethodField();
        $this->setInvoiceDayFromStartPeriod();
        $this->arrangeRecurringFields($blockInstance->id);
    }

    /**
     * 請求日が未設定の既存の受注に、開始時期の日を請求日として設定する
     * 毎日・毎週の受注は請求日を使わないため対象外。
     */
    private function setInvoiceDayFromStartPeriod(): void
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "UPDATE vtiger_invoice_recurring_info
                SET invoice_day = CONCAT('Day ', LPAD(DAY(start_period), 2, '0'))
                WHERE (invoice_day IS NULL OR invoice_day = '')
                AND DAY(start_period) > 0
                AND recurring_frequency IN ('Monthly', 'Quarterly', 'Half-Yearly', 'Yearly')",
            []
        );
        $this->log('既存の受注に請求日を設定しました: ' . $db->getAffectedRowCount($result) . ' 件');
    }

    /**
     * 繰り返し請求情報の項目を並べ替える
     *
     *   繰り返し有効      | 自動作成時の請求書ステータス
     *   開始時期          | 終了時期
     *   周期              | 請求日
     *   支払い期限の指定方法  | 支払い期限
     *   支払い月          | 支払い日
     *
     * 編集画面は項目を左右 2 つずつ並べるため、この順に番号を振る。
     * 標準の項目が標準の順に並び、他の項目がないときだけ変更する（レイアウトエディタで変更された並びは変えない）。
     */
    private function arrangeRecurringFields(int $blockId): void
    {
        $standardOrder = [
            'enable_recurring', 'recurring_frequency', 'start_period', 'end_period',
            'payment_duration', 'invoicestatus', 'last_recurring_date',
        ];
        $newOrder = [
            'enable_recurring', 'invoicestatus',
            'start_period', 'end_period',
            'recurring_frequency', 'invoice_day',
            'payment_due_type', 'payment_duration',
            'payment_due_month', 'payment_due_day',
            'last_recurring_date',
        ];

        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT fieldname FROM vtiger_field WHERE block = ? ORDER BY sequence', [$blockId]);
        $currentOrder = [];
        while ($row = $db->fetchByAssoc($result)) {
            $currentOrder[] = $row['fieldname'];
        }
        $sameFields = count($currentOrder) === count($newOrder) && !array_diff($currentOrder, $newOrder);
        if (!$sameFields || array_values(array_intersect($currentOrder, $standardOrder)) !== $standardOrder) {
            $this->log('繰り返し請求情報の並びが標準と異なるため、並べ替えをスキップしました');
            return;
        }

        foreach ($newOrder as $index => $fieldName) {
            $db->pquery('UPDATE vtiger_field SET sequence = ? WHERE block = ? AND fieldname = ?', [$index + 1, $blockId, $fieldName]);
        }
    }

    private function addInvoiceDayField(Vtiger_Module $moduleInstance, Vtiger_Block $blockInstance): void
    {
        if (Vtiger_Field::getInstance('invoice_day', $moduleInstance)) {
            return;
        }
        $this->addPicklistField($blockInstance, 'vtiger_invoice_recurring_info', 'invoice_day', 'Invoice Day', $this->getDayValues());
        $this->placeFieldBefore($blockInstance->id, 'invoice_day', 'payment_duration');
    }

    /**
     * 支払い期限の指定方法・支払い月・支払い日を追加する
     * 並びは 指定方法 → 支払い期限 → 支払い月 → 支払い日 にする。
     * 標準の並びの環境では、最後に arrangeRecurringFields() で並べ直す。
     */
    private function addPaymentDueFields(Vtiger_Module $moduleInstance, Vtiger_Block $blockInstance): void
    {
        if (!Vtiger_Field::getInstance('payment_due_type', $moduleInstance)) {
            $this->addPicklistField(
                $blockInstance,
                'vtiger_invoice_recurring_info',
                'payment_due_type',
                'Payment Due Type',
                ['Days After', 'Fixed Date'],
                'Days After'
            );
            $this->placeFieldBefore($blockInstance->id, 'payment_due_type', 'payment_duration');

            // 既存の受注は「日数で指定」の支払い期限を使っているため、指定方法を合わせる
            $db = PearDatabase::getInstance();
            $db->pquery("UPDATE vtiger_invoice_recurring_info SET payment_due_type = 'Days After' WHERE payment_due_type IS NULL", []);
        }
        if (!Vtiger_Field::getInstance('payment_due_month', $moduleInstance)) {
            $this->addPicklistField(
                $blockInstance,
                'vtiger_invoice_recurring_info',
                'payment_due_month',
                'Payment Due Month',
                ['Same Month', 'Next Month', 'Two Months Later']
            );
            $this->placeFieldAfter($blockInstance->id, 'payment_due_month', 'payment_duration');
        }
        if (!Vtiger_Field::getInstance('payment_due_day', $moduleInstance)) {
            $this->addPicklistField($blockInstance, 'vtiger_invoice_recurring_info', 'payment_due_day', 'Payment Due Day', $this->getDayValues());
            $this->placeFieldAfter($blockInstance->id, 'payment_due_day', 'payment_due_month');
        }
    }

    /**
     * 請求日・支払い日の選択肢（Day 01 〜 Day 31、月末）
     *
     * @return list<string>
     */
    private function getDayValues(): array
    {
        $values = [];
        for ($day = 1; $day <= 31; $day++) {
            $values[] = sprintf('Day %02d', $day);
        }
        $values[] = 'End of Month';
        return $values;
    }

    /**
     * 受注の基本情報に支払い方法を追加し、期限日の後ろに並べる
     */
    private function addPaymentMethodField(Vtiger_Module $moduleInstance): void
    {
        if (Vtiger_Field::getInstance('payment_method', $moduleInstance)) {
            return;
        }
        $blockInstance = Vtiger_Block::getInstance('LBL_SO_INFORMATION', $moduleInstance);
        $values = ['Bank Transfer', 'Account Transfer', 'Credit Card', 'Cash', 'Other'];
        $this->addPicklistField($blockInstance, 'vtiger_salesorder', 'payment_method', 'Payment Method', $values);
        $this->placeFieldAfter($blockInstance->id, 'payment_method', 'duedate');
    }

    /**
     * 請求書の基本情報に支払い方法を追加し、期限日の後ろに並べる
     */
    private function addInvoicePaymentMethodField(): void
    {
        $moduleInstance = Vtiger_Module::getInstance('Invoice');
        if (Vtiger_Field::getInstance('payment_method', $moduleInstance)) {
            return;
        }
        $blockInstance = Vtiger_Block::getInstance('LBL_INVOICE_INFORMATION', $moduleInstance);
        $values = ['Bank Transfer', 'Account Transfer', 'Credit Card', 'Cash', 'Other'];
        $this->addPicklistField($blockInstance, 'vtiger_invoice', 'payment_method', 'Payment Method', $values);
        $this->placeFieldBefore($blockInstance->id, 'payment_method', 'vtiger_purchaseorder');
    }

    /**
     * 選択肢の項目を追加する
     * 同名の項目が他のモジュールにあり選択肢のテーブルが作られている場合は、選択肢を共有して追加しない。
     *
     * @param list<string> $values
     */
    private function addPicklistField(Vtiger_Block $blockInstance, string $table, string $name, string $label, array $values, ?string $defaultValue = null): void
    {
        $hasPicklistTable = Vtiger_Utils::CheckTable('vtiger_' . $name);

        $field = new Vtiger_Field();
        $field->name         = $name;
        $field->label        = $label;
        $field->table        = $table;
        $field->column       = $name;
        $field->columntype   = 'VARCHAR(100)';
        $field->uitype       = 16;
        $field->typeofdata   = 'V~O';
        $field->quickcreate  = 3;
        $field->masseditable = 0;
        if ($defaultValue !== null) {
            $field->defaultvalue = $defaultValue;
        }
        $blockInstance->addField($field);
        if (!$hasPicklistTable) {
            $field->setPicklistValues($values);
        }
        $this->log("フィールド {$name} を追加しました（{$table}）");
    }

    /**
     * 項目を指定した項目の前に並べる
     * 他の項目の順序は変えない（レイアウトエディタで変更されている場合があるため）。
     */
    private function placeFieldBefore(int $blockId, string $fieldName, string $nextFieldName): void
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT sequence FROM vtiger_field WHERE block = ? AND fieldname = ?', [$blockId, $nextFieldName]);
        $sequence = (int) $db->query_result($result, 0, 'sequence');

        $db->pquery(
            'UPDATE vtiger_field SET sequence = sequence + 1 WHERE block = ? AND sequence >= ? AND fieldname <> ?',
            [$blockId, $sequence, $fieldName]
        );
        $db->pquery('UPDATE vtiger_field SET sequence = ? WHERE block = ? AND fieldname = ?', [$sequence, $blockId, $fieldName]);
    }

    /**
     * 項目を指定した項目のすぐ後ろに並べる
     */
    private function placeFieldAfter(int $blockId, string $fieldName, string $previousFieldName): void
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT sequence FROM vtiger_field WHERE block = ? AND fieldname = ?', [$blockId, $previousFieldName]);
        $sequence = (int) $db->query_result($result, 0, 'sequence') + 1;

        $db->pquery(
            'UPDATE vtiger_field SET sequence = sequence + 1 WHERE block = ? AND sequence >= ? AND fieldname <> ?',
            [$blockId, $sequence, $fieldName]
        );
        $db->pquery('UPDATE vtiger_field SET sequence = ? WHERE block = ? AND fieldname = ?', [$sequence, $blockId, $fieldName]);
    }

}
