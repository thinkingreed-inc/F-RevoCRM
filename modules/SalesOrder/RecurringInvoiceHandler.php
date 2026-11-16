<?php
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/

require_once('include/utils/utils.php');
require_once('modules/SalesOrder/models/RecurringInvoiceSchedule.php');

class RecurringInvoiceHandler extends VTEventHandler {
	
	private $entityData;
	
	public function handleEvent($handlerType, $entityData) {
		$this->entityData = $entityData;
	
		if ($this->isSalesOrderModule()) {
			$this->handleRecurringInvoiceGeneration();
		}
	}
	
	private function handleRecurringInvoiceGeneration() {
		if ($this->isRecurringInvoiceEnabled()) {
			if (empty($this->getNextInvoiceDate()) || $this->isStartDateAfterNextInvoiceDate()) {
				$this->setNextInvoiceDateEqualsToStartDate();
			}
			$this->clearUnusedScheduleValues();
			$this->alignNextInvoiceDateToInvoiceDay();
		} else {
			$this->deleteRecurringInvoiceData();
		}
	}
	
	private function isStartDateAfterNextInvoiceDate()
	{
		$startPeriod = new DateTime($this->getStartDate());
		$nextInvoiceDate = new DateTime($this->getNextInvoiceDate());
		
		return $startPeriod > $nextInvoiceDate;
	}
	
	private function isSalesOrderModule() {
		return $this->entityData->getModuleName() == 'SalesOrder';
	}
	
	private function getStartDate()
	{
		$data = $this->entityData->getData();
		return DateTimeField::convertToDBFormat($data['start_period']);
	}
	
	private function getNextInvoiceDate() {
		$data = $this->entityData->getData();
		return $data['last_recurring_date'];
	}
	
	private function isRecurringInvoiceEnabled() {
		$data = $this->entityData->getData();
		return !empty($data['enable_recurring']);
	}
	
	private function setNextInvoiceDateEqualsToStartDate()
	{
		$db = PearDatabase::getInstance();
		$query = "UPDATE vtiger_invoice_recurring_info SET last_recurring_date = start_period WHERE salesorderid = ?";
		$db->pquery($query, [$this->entityData->getId()]);
	}
	
	/**
	 * 使わなくなった指定を消す
	 *   - 請求日を指定できない周期（毎週・毎日）なら請求日
	 *   - 支払い期限の指定方法が「日付で指定」なら支払い期限（日数）、「日数で指定」なら支払い月・支払い日
	 * 編集画面では無効化された項目が送信されないため、ここで消さないと古い値が詳細画面に残る。
	 */
	private function clearUnusedScheduleValues(): void
	{
		$db = PearDatabase::getInstance();
		$data = $this->entityData->getData();
		$id = $this->entityData->getId();
		if (!SalesOrder_RecurringInvoiceSchedule_Model::isInvoiceDayApplicable($data['recurring_frequency'] ?? '')) {
			$db->pquery("UPDATE vtiger_invoice_recurring_info SET invoice_day = NULL WHERE salesorderid = ?", [$id]);
		}
		if (SalesOrder_RecurringInvoiceSchedule_Model::isFixedDueDate($data['payment_due_type'] ?? '')) {
			$db->pquery("UPDATE vtiger_invoice_recurring_info SET payment_duration = NULL WHERE salesorderid = ?", [$id]);
		} else {
			$db->pquery("UPDATE vtiger_invoice_recurring_info SET payment_due_month = NULL, payment_due_day = NULL WHERE salesorderid = ?", [$id]);
		}
	}

	private function alignNextInvoiceDateToInvoiceDay(): void
	{
		$db = PearDatabase::getInstance();
		$result = $db->pquery("SELECT recurring_frequency, start_period, last_recurring_date, invoice_day FROM vtiger_invoice_recurring_info WHERE salesorderid = ?", [$this->entityData->getId()]);
		if ($db->num_rows($result) === 0) {
			return;
		}
		$nextInvoiceDate = $db->query_result($result, 0, 'last_recurring_date');
		if (empty($nextInvoiceDate)) {
			return;
		}
		$alignedDate = SalesOrder_RecurringInvoiceSchedule_Model::alignNextInvoiceDate(
			$nextInvoiceDate,
			(string) $db->query_result($result, 0, 'start_period'),
			$db->query_result($result, 0, 'recurring_frequency'),
			$db->query_result($result, 0, 'invoice_day')
		);
		if ($alignedDate !== $nextInvoiceDate) {
			$db->pquery("UPDATE vtiger_invoice_recurring_info SET last_recurring_date = ? WHERE salesorderid = ?", [$alignedDate, $this->entityData->getId()]);
		}
	}

	private function deleteRecurringInvoiceData()
	{
		$db = PearDatabase::getInstance();
		$query = "DELETE FROM vtiger_invoice_recurring_info WHERE salesorderid = ?";
		$db->pquery($query, [$this->entityData->getId()]);	
	}
}
