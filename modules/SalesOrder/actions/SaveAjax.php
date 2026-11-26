<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class SalesOrder_SaveAjax_Action extends Inventory_SaveAjax_Action {

	/**
	 * 繰り返し請求ブロック（Recurring Invoice Information）の項目
	 */
	private static $recurringFields = array('enable_recurring', 'recurring_frequency', 'start_period', 'end_period', 'payment_duration', 'invoicestatus');

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$enableRecurrence = false;
		if($request->get('field') == 'enable_recurring'){
			$enableRecurrence = true;
		}
		// 保存済みの繰り返し請求の設定（新規作成時は null）。変更があったかの判定に使う
		$storedRecurringValues = null;
		if(!empty($recordId)) {
			$storedRecurringValues = array();
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			$fieldModelList = $recordModel->getModule()->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				//For not converting createdtime and modified time to user format
				$uiType = $fieldModel->get('uitype');
				if ($uiType == 70) {
					$fieldValue = $recordModel->get($fieldName);
				} else {
					$fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));
				}
				if (in_array($fieldName, self::$recurringFields, true)) {
					$storedRecurringValues[$fieldName] = $fieldValue;
				}


				if ($request->has($fieldName)) {
					$fieldValue = $request->get($fieldName, null);
				} else if ($fieldName === $request->get('field')) {
					$fieldValue = $request->get('value');
				}

				/**
				 * If field is enable recurrence then we need to pass related fields of
				 * recurrence to save,because untill enable recurrence is checked,the 
				 * related field values wont get saved
				 */
				if($enableRecurrence){
					$requestFieldValue = $request->get($fieldName);
					if($requestFieldValue != ''){
						$fieldValue = $request->get($fieldName);
					}
				}

				$fieldDataType = $fieldModel->getFieldDataType();
                if($fieldValue){
                    $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue,$fieldModel);
                }
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		} else {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');

			$fieldModelList = $moduleModel->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if ($request->has($fieldName)) {
					$fieldValue = $request->get($fieldName, null);
				} else {
					$fieldValue = $fieldModel->getDefaultFieldValue();
				}
				$fieldDataType = $fieldModel->getFieldDataType();
                if($fieldValue){
                    $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue,$fieldModel);
                }
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
			} 
		}
		$this->validateRecurringInvoiceSettings($recordModel, $storedRecurringValues);
		return $recordModel;
	}

	/**
	 * 繰り返し請求を有効にする場合は、周期と開始日を必須とする
	 *
	 * 周期が空のまま有効になっていると、請求の自動作成（cron/modules/SalesOrder/RecurringInvoice.service）で
	 * 次回請求日を計算できない。インライン編集では「繰り返し有効」だけを保存できるため、ここで確認する。
	 * 繰り返し請求ブロックの値を変えない保存は妨げない（既に周期が空の受注でも、他の項目は編集できるようにする）。
	 * @param Vtiger_Record_Model $recordModel 保存する値を反映した受注
	 * @param array|null $storedRecurringValues 保存済みの繰り返し請求の設定（新規作成時は null）
	 * @throws AppException
	 */
	protected function validateRecurringInvoiceSettings($recordModel, $storedRecurringValues) {
		if (!$this->isRecurringEnabled($recordModel->get('enable_recurring'))) {
			return;
		}
		if ($storedRecurringValues !== null && !$this->isRecurringSettingChanged($recordModel, $storedRecurringValues)) {
			return;
		}
		if (trim((string) $recordModel->get('recurring_frequency')) === ''
			|| trim((string) $recordModel->get('start_period')) === '') {
			throw new AppException(vtranslate('LBL_RECURRING_FREQUENCY_AND_START_PERIOD_REQUIRED', 'SalesOrder'));
		}
	}

	/**
	 * 保存時（CRMEntity::insertIntoEntityTable）と同じ基準で「繰り返し有効」の ON/OFF を判定する
	 * @param mixed $value
	 * @return boolean
	 */
	private function isRecurringEnabled($value) {
		return $value === 'on' || $value == 1;
	}

	/**
	 * 繰り返し請求ブロックの値が保存済みから変わるか
	 * @param Vtiger_Record_Model $recordModel
	 * @param array $storedRecurringValues
	 * @return boolean
	 */
	private function isRecurringSettingChanged($recordModel, $storedRecurringValues) {
		foreach ($storedRecurringValues as $fieldName => $storedValue) {
			$newValue = $recordModel->get($fieldName);
			if ($fieldName === 'enable_recurring') {
				if ($this->isRecurringEnabled($newValue) !== $this->isRecurringEnabled($storedValue)) {
					return true;
				}
			} else if (trim((string) $newValue) !== trim((string) $storedValue)) {
				return true;
			}
		}
		return false;
	}
}
