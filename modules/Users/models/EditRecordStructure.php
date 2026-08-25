<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_EditRecordStructure_Model extends Vtiger_EditRecordStructure_Model {

	/**
	 * Function to get the values in stuctured format
	 * @return <array> - values in structure array('block'=>array(fieldinfo));
	 */
	public function getStructure() {
		if(!empty($this->structuredValues)) {
			return $this->structuredValues;
		}

		$values = array();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$recordModel = $this->getRecord();
		$recordId = $recordModel->getId();
		$moduleModel = $this->getModule();
		$blockModelList = $moduleModel->getBlocks();
		foreach ($blockModelList as $blockLabel => $blockModel) {
			$fieldModelList = $blockModel->getFields();
			if ($fieldModelList) {
				$values[$blockLabel] = array();
				foreach($fieldModelList as $fieldName => $fieldModel) {
					if($fieldModel->get('uitype') == 115) {
						$fieldModel->set('editable', false);
					}
					if(empty($recordId) && ($fieldModel->get('uitype') == 99 || $fieldModel->get('uitype') == 106)) {
							$fieldModel->set('editable', true);
					}
					//Is Admin field is editable when the record user != current user
					if (in_array($fieldModel->get('uitype'), array(156)) && $currentUserModel->getId() !== $recordId) {
						$fieldModel->set('editable', true);
						if ($fieldModel->get('uitype') == 156) {
							$adminFieldValue = self::normalizeAdminFieldValue($recordModel->get($fieldName));
							$recordModel->set($fieldName, $adminFieldValue);
							// 項目モデルはプロセス内でキャッシュされるため、OFF のときは
							// 前レコードの値が残らないよう明示的に空値を設定する
							$fieldModel->set('fieldvalue', $adminFieldValue);
						}
					}
					if($fieldName == 'is_owner') {
					   $fieldModel->set('editable', false);
					} else if($fieldName == 'reports_to_id' && !$currentUserModel->isAdminUser()) {
					   continue;
					}
					if($fieldModel->isEditable() && $fieldName != 'is_owner') {
						if($recordModel->get($fieldName) != '') {
							$fieldModel->set('fieldvalue', $recordModel->get($fieldName));
						} else {
							$defaultValue = $fieldModel->getDefaultFieldValue();
							if(!empty($defaultValue) && !$recordId)
								$fieldModel->set('fieldvalue', $defaultValue);
						}

						if(!$recordId && $fieldModel->get('uitype') == 99) {
							$fieldModel->set('editable', true);
							$values[$blockLabel][$fieldName] = $fieldModel;
						} else if($fieldModel->get('uitype') != 99){
							$values[$blockLabel][$fieldName] = $fieldModel;
						}
					}
				}
			}
		}
		$this->structuredValues = $values;
		return $values;
	}

	/**
	 * システム管理者(is_admin, uitype 156)の値を、編集画面のチェックボックス表示用に正規化する。
	 *
	 * Boolean.tpl の checked 判定は
	 *   $fieldvalue == true && $fieldvalue != 'no' && $fieldvalue != vtranslate('LBL_NO')
	 * であり、真偽値 true を渡すと `true != 'no'` が false になり checked が出力されない。
	 * そのため ON は 'on' のまま、OFF は空文字を返す。
	 *
	 * @param mixed $value レコードが保持する is_admin の値
	 * @return string 'on'(チェック済み) または ''(未チェック)
	 */
	public static function normalizeAdminFieldValue($value) {
		return ($value === 'on') ? 'on' : '';
	}
}