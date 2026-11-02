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
	 * また、詳細画面でenable_recurringがOFFの場合はenable_recurring自身もAjax編集不可とし、
	 * 周期などの必須項目と合わせて編集画面で有効にする
	 * （一覧画面など受注ごとの状態が無い場合は従来どおり編集可。保存時に SalesOrder_SaveAjax_Action で確認する）
	 * 状態は SalesOrder_DetailRecordStructure_Model が項目モデルに付ける。項目モデルはモジュール内で共有されるため、
	 * 詳細画面の構造を組み立てた後の同じリクエスト内では、一覧画面向けの判定に使わない前提
	 * @return boolean
	 */
	public function isAjaxEditable() {
		if ($this->block
			&& $this->block->label === 'Recurring Invoice Information'
			&& !$this->get('enableRecurring')) {
			if ($this->getName() !== 'enable_recurring') {
				return false;
			}
			if (property_exists($this, 'enableRecurring')) {
				return false;
			}
		}
		return parent::isAjaxEditable();
	}
}
