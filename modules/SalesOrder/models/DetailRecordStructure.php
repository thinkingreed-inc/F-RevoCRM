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
 * SalesOrder Detail View Record Structure Model
 */
class SalesOrder_DetailRecordStructure_Model extends Vtiger_DetailRecordStructure_Model {

	/**
	 * Function to get the values in stuctured format
	 * FieldModelにenableRecurringフラグをセットして、isAjaxEditable()で参照可能にする
	 * @return <array> - values in structure array('block'=>array(fieldinfo));
	 */
	public function getStructure() {
		if(!empty($this->structuredValues)) {
			return $this->structuredValues;
		}

		$values = parent::getStructure();
		$recordModel = $this->getRecord();
		if ($recordModel) {
			// 繰り返し請求の有効/無効フラグを各FieldModelにセット
			$enableRecurring = $recordModel->get('enable_recurring');
			$paymentDueType = $recordModel->get('payment_due_type');
			foreach ($values as $fields) {
				foreach ($fields as $fieldModel) {
					$fieldModel->set('enableRecurring', $enableRecurring);
					$fieldModel->set('paymentDueType', $paymentDueType);
				}
			}
		}

		// 支払い期限の指定方法・支払い月・支払い日は詳細画面に出さない。支払い期限に「翌月20日」などとまとめて表示する
		foreach ($values as $blockLabel => $fields) {
			unset($values[$blockLabel]['payment_due_type'], $values[$blockLabel]['payment_due_month'], $values[$blockLabel]['payment_due_day']);
		}
		$this->structuredValues = $values;
		return $values;
	}
}
