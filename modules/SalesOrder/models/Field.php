<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * SalesOrder Field Model Class
 */
class SalesOrder_Field_Model extends Vtiger_Field_Model {

	/**
	 * 繰り返し請求関連フィールドのAjax編集可否を制御
	 * enable_recurringがOFFの場合、Recurring Invoice Informationブロック内の
	 * フィールド（enable_recurring自身を除く）はAjax編集不可
	 * @return boolean
	 */
	public function isAjaxEditable() {
		// 「日付で指定」の支払い期限は支払い月・支払い日で決まるため、詳細画面では編集させない（編集画面で指定方法ごと変える）
		if ($this->getName() === 'payment_duration'
			&& SalesOrder_RecurringInvoiceSchedule_Model::isFixedDueDate($this->get('paymentDueType'))) {
			return false;
		}
		if ($this->block
			&& $this->block->label === 'Recurring Invoice Information'
			&& $this->getName() !== 'enable_recurring'
			&& !$this->get('enableRecurring')) {
			return false;
		}
		return parent::isAjaxEditable();
	}

	/**
	 * 支払い期限の指定方法が「日付で指定」のとき、支払い期限を「翌月20日」「当月末」のように表示する
	 * 詳細画面では指定方法・支払い月・支払い日を出さず、支払い期限だけで内容が分かるようにするため。
	 *
	 * @param mixed $value
	 * @param mixed $record
	 * @param mixed $recordInstance
	 * @param mixed $removeTags
	 * @return mixed
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $removeTags = false) {
		if ($this->getName() === 'payment_duration' && $recordInstance instanceof Vtiger_Record_Model) {
			// get() の PHPDoc（@return Value）が型として解釈されるため、値の型を明示する
			/** @var string|null $paymentDueType */
			$paymentDueType = $recordInstance->get('payment_due_type');
			if (SalesOrder_RecurringInvoiceSchedule_Model::isFixedDueDate($paymentDueType)) {
				/** @var string|null $paymentDueMonth */
				$paymentDueMonth = $recordInstance->get('payment_due_month');
				/** @var string|null $paymentDueDay */
				$paymentDueDay = $recordInstance->get('payment_due_day');
				return $this->getFixedDueDateLabel($paymentDueMonth, $paymentDueDay);
			}
		}
		return parent::getDisplayValue($value, $record, $recordInstance, $removeTags);
	}

	private function getFixedDueDateLabel(?string $paymentDueMonth, ?string $paymentDueDay): string {
		if (empty($paymentDueMonth) || empty($paymentDueDay)) {
			return '';
		}
		$moduleName = $this->getModuleName();
		$monthLabel = vtranslate($paymentDueMonth, $moduleName);
		if ($paymentDueDay === SalesOrder_RecurringInvoiceSchedule_Model::END_OF_MONTH) {
			return vsprintf(vtranslate('LBL_PAYMENT_DUE_END_OF_MONTH', $moduleName), array($monthLabel));
		}
		return vsprintf(vtranslate('LBL_PAYMENT_DUE_DAY_OF_MONTH', $moduleName), array($monthLabel, vtranslate($paymentDueDay, $moduleName)));
	}
}
