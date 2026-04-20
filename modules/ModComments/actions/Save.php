<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ModComments_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$request->set('assigned_user_id', $currentUserModel->getId());
		$request->set('userid', $currentUserModel->getId());

		$recordModel = $this->saveRecord($request);
		$responseFieldsToSent = array('reasontoedit','commentcontent');
		$fieldModelList = $recordModel->getModule()->getFields();
		foreach ($responseFieldsToSent as $fieldName) {
            $fieldModel = $fieldModelList[$fieldName];
            $fieldValue = $recordModel->get($fieldName);
			// toSafeHTML(htmlentities)は使わず、vtlib_purify済みのHTMLをそのまま返す
			// SaveAjax.phpと同じ方針: バックエンドのHTMLPurifierサニタイズを信頼する
			$result[$fieldName] = $fieldModel->getDisplayValue($fieldValue);
		}

		$result['success'] = true;
		$result['modifiedtime'] = Vtiger_Util_Helper::formatDateDiffInStrings($recordModel->get('modifiedtime'));
		$result['modifiedtimetitle'] = Vtiger_Util_Helper::formatDateTimeIntoDayString($recordModel->get('modifiedtime'));

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {
		// 旧実装では getRaw() でcommentcontent/reasontoeditを取得し生HTMLをDBに保存していたが、
		// これはXSS脆弱性（ストレージ起因）を内包していた。
		// 現在は親クラスに委譲することで以下の多重防御が適用される:
		//   1. $request->get() 内の vtlib_purify() （1回目）
		//   2. RICH_TEXT_FIELDS ループ内の vtlib_purify(decode_html()) （2回目）
		// HTMLPurifier は冪等性を持つため2重適用によるコンテンツ破壊は発生しない。
		// decode_html() も HTMLPurifier 出力済みHTMLには実質無効。意図的な defense-in-depth 設計。
		$recordModel = parent::getRecordModelFromRequest($request);
		return $recordModel;
	}

}
