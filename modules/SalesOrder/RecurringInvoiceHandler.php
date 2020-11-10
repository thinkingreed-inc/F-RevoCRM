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
	
	private function deleteRecurringInvoiceData()
	{
		$db = PearDatabase::getInstance();
		$query = "DELETE FROM vtiger_invoice_recurring_info WHERE salesorderid = ?";
		$db->pquery($query, [$this->entityData->getId()]);	
	}
}
