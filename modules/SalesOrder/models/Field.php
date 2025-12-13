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
		if ($this->block
			&& $this->block->label === 'Recurring Invoice Information'
			&& $this->getName() !== 'enable_recurring'
			&& !$this->get('enableRecurring')) {
			return false;
		}
		return parent::isAjaxEditable();
	}
}
