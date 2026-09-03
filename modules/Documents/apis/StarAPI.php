<?php

/**
 * Documents用スターAPI
 *
 * 既存の vtiger_crmentity_user_field テーブルを使用する。
 * Vtiger_SaveStar_Action と同じアーキテクチャ。
 */
class Documents_StarAPI_Api extends Vtiger_Api_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	protected function processApi(Vtiger_Request $request) {
		$recordId = $request->get('record');
		if (empty($recordId)) {
			$this->sendError('Record ID is required', 400);
		}

		$starred = $request->get('starred');
		$starredValue = ($starred === true || $starred === 'true' || $starred === '1') ? '1' : '0';

		$module = 'Documents';
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser->getId();

		// レコード存在確認
		$db = PearDatabase::getInstance();
		$checkResult = $db->pquery(
			"SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0 AND setype = ?",
			array($recordId, $module)
		);
		if ($checkResult === false || $db->num_rows($checkResult) === 0) {
			$this->sendError('Record not found', 404);
		}

		// vtiger_crmentity_user_field を更新（既存アーキテクチャに準拠）
		$focus = CRMEntity::getInstance($module);
		$focus->mode = "edit";
		$focus->id = $recordId;
		$focus->column_fields->startTracking();
		$focus->column_fields['starred'] = $starredValue;
		$userSpecificTable = Vtiger_Functions::getUserSpecificTableName($module);
		$focus->insertIntoEntityTable($userSpecificTable, $module);

		return $this->sendSuccess(array(
			'success' => true,
			'record' => $recordId,
			'starred' => $starredValue === '1',
		));
	}

	function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
