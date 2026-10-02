<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_EditRecordStructure_Model extends Vtiger_EditRecordStructure_Model {

	/**
	 * 読み取り専用項目（displaytype=2）も構造に含めるかどうか
	 *
	 * 通常の編集画面では false。詳細画面向けに項目定義を取得する場合
	 * （Documents_GetFields_Api の view=detail）だけ true にする。
	 *
	 * @var bool
	 */
	private static $includeReadonlyFields = false;

	/**
	 * 読み取り専用項目を構造に含めるかどうかを切り替える
	 *
	 * @param bool $include
	 */
	public static function setIncludeReadonlyFields($include) {
		self::$includeReadonlyFields = (bool) $include;
	}

	/**
	 * Function to get the values in stuctured format
	 * @return <array> - values in structure array('block'=>array(fieldinfo));
	 */
	public function getStructure() {
		if(!empty($this->structuredValues)) {
			return $this->structuredValues;
		}

		$values = array();
		$recordModel = $this->getRecord();
		$recordExists = !empty($recordModel);
        $recordId = $recordModel->getId();
		$moduleModel = $this->getModule();
		$blockModelList = $moduleModel->getBlocks();
		foreach($blockModelList as $blockLabel=>$blockModel) {
			$fieldModelList = $blockModel->getFields();
			if (!empty ($fieldModelList)) {
				$values[$blockLabel] = array();
				foreach($fieldModelList as $fieldName=>$fieldModel) {
					if($fieldModel->isEditable()
							|| (self::$includeReadonlyFields && $fieldModel->isReadonlyEditView())) {
						$fieldValue = $recordModel->get($fieldName);

						if ((!$fieldValue && strlen($fieldValue) == 0) && !$recordId) {
							$fieldValue = $fieldModel->getDefaultFieldValue();
						}

						//By default the file status should be active while creating a Document record
						if ($fieldName === 'filestatus' && !$recordId) {
							$fieldValue = true;
						}

						if (strlen($fieldValue) > 0) {
							$fieldModel->set('fieldvalue', $fieldValue);
						}
						$values[$blockLabel][$fieldName] = $fieldModel;
					}
				}
			}
		}
		$this->structuredValues = $values;
		return $values;
	}
}
