<?php
/**
 * ドキュメントの関連付け API
 *
 * レコードの関連リストから既存のドキュメントを紐づける／紐づけを外すために使用する。
 * 関連付け自体は Vtiger_Relation_Model を通して行うため、イベントハンドラや
 * 更新履歴（ModTracker）の記録は標準の関連付けと同じ扱いになる。
 *
 * Usage:
 *   POST mode=link&parent_module=Accounts&parent_id=123&records[]=456&records[]=789
 *   POST mode=unlink&parent_module=Accounts&parent_id=123&records[]=456
 */
require_once 'modules/Documents/utils/ComplianceChecker.php';
require_once 'modules/Documents/utils/FolderPermission.php';

class Documents_RelationAPI_Api extends Vtiger_Api_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		// 関連付けの操作は親レコードの詳細とドキュメントの詳細を見られることが前提
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		$permissions[] = array(
			'module_parameter' => 'parent_module',
			'record_parameter' => 'parent_id',
			'action' => 'DetailView',
		);
		return $permissions;
	}

	protected function processApi(Vtiger_Request $request) {
		$mode = $request->get('mode');
		switch ($mode) {
			case 'link':
				return $this->sendSuccess($this->updateRelation($request, true));
			case 'unlink':
				return $this->sendSuccess($this->updateRelation($request, false));
			default:
				$this->sendError('Invalid mode: ' . $mode, 400);
		}
	}

	/**
	 * 親レコードとドキュメントの関連付けを追加・解除する
	 *
	 * @param Vtiger_Request $request
	 * @param bool $link true なら関連付け、false なら解除
	 * @return array
	 * @throws Exception
	 */
	private function updateRelation(Vtiger_Request $request, $link) {
		$parentModule = $request->get('parent_module');
		$parentId = (int) $request->get('parent_id');
		$recordIds = $this->getRecordIds($request);

		if (empty($parentModule) || $parentId <= 0) {
			throw new Exception(vtranslate('LBL_RELATION_PARENT_REQUIRED', 'Documents'));
		}
		if (empty($recordIds)) {
			throw new Exception(vtranslate('LBL_RELATION_NO_RECORD_SELECTED', 'Documents'));
		}
		if (!$this->parentExists($parentModule, $parentId)) {
			throw new Exception(vtranslate('LBL_RELATION_PARENT_NOT_FOUND', 'Documents'));
		}

		$parentModuleModel = Vtiger_Module_Model::getInstance($parentModule);
		$documentsModuleModel = Vtiger_Module_Model::getInstance('Documents');
		if (empty($parentModuleModel) || empty($documentsModuleModel)) {
			throw new Exception(vtranslate('LBL_RELATION_PARENT_NOT_FOUND', 'Documents'));
		}
		$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $documentsModuleModel);
		if (empty($relationModel)) {
			throw new Exception(vtranslate('LBL_RELATION_NOT_AVAILABLE', 'Documents'));
		}

		$processed = 0;
		$skipped = 0;
		$denied = 0;
		foreach ($recordIds as $recordId) {
			if (!$this->isDocument($recordId)) {
				$skipped++;
				continue;
			}
			// 参照できないフォルダのドキュメントは紐づけ・解除の対象にしない
			if (!Documents_FolderPermission::canAccessDocument($recordId)) {
				$denied++;
				continue;
			}
			$related = $this->isRelated($parentId, $recordId);
			if ($link === $related) {
				// 既に関連付け済み（解除の場合は関連付けが無い）
				$skipped++;
				continue;
			}
			if ($link) {
				$relationModel->addRelation($parentId, $recordId);
			} else {
				$relationModel->deleteRelation($parentId, $recordId);
			}
			// 取引レコードへの関連付けは適合判定の条件のため、その場で再判定する
			$this->recheckCompliance($recordId);
			$processed++;
		}

		return array(
			'success' => true,
			'parent_module' => $parentModule,
			'parent_id' => $parentId,
			'linked' => $link ? $processed : 0,
			'unlinked' => $link ? 0 : $processed,
			'skipped' => $skipped,
			// 参照権限が無く対象外にした件数
			'denied' => $denied,
		);
	}

	/**
	 * 適合チェックをやり直す（電帳法対象のドキュメントのみ）
	 *
	 * 判定に失敗しても関連付け自体は成功として扱う。
	 *
	 * @param int $recordId
	 */
	private function recheckCompliance($recordId) {
		try {
			if (Documents_ComplianceChecker::isComplianceTarget($recordId)) {
				Documents_ComplianceChecker::check($recordId);
			}
		} catch (Exception $e) {
			global $log;
			if (isset($log) && is_object($log)) {
				$log->error("Documents compliance recheck failed for record {$recordId}: "
					. $e->getMessage());
			}
		}
	}

	/**
	 * リクエストからドキュメントIDの配列を取り出す
	 *
	 * records[]（配列）とカンマ区切りの records の両方を受け付ける。
	 * 指定されたIDは件数で切り捨てず、すべて処理する
	 * （黙って捨てると関連付け漏れに気付けないため）。
	 * 1リクエストで送れる件数は PHP の max_input_vars / post_max_size に従う。
	 *
	 * @param Vtiger_Request $request
	 * @return array 重複を除いたドキュメントIDの配列
	 */
	private function getRecordIds(Vtiger_Request $request) {
		$raw = $request->get('records');
		if (empty($raw)) {
			$raw = $request->get('record');
		}
		if (!is_array($raw)) {
			$raw = ($raw === null || $raw === '') ? array() : explode(',', (string) $raw);
		}

		$recordIds = array();
		foreach ($raw as $value) {
			$recordId = (int) trim((string) $value);
			if ($recordId > 0 && !in_array($recordId, $recordIds, true)) {
				$recordIds[] = $recordId;
			}
		}
		return $recordIds;
	}

	/**
	 * 親レコードが存在するかどうか
	 *
	 * @param string $parentModule
	 * @param int $parentId
	 * @return bool
	 */
	private function parentExists($parentModule, $parentId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND setype = ? AND deleted = 0",
			array($parentId, $parentModule)
		);
		return ($result !== false && $db->num_rows($result) > 0);
	}

	/**
	 * 対象がドキュメントかどうか（他モジュールのIDを渡されても関連付けしない）
	 *
	 * @param int $recordId
	 * @return bool
	 */
	private function isDocument($recordId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT crmid FROM vtiger_crmentity WHERE crmid = ? AND setype = 'Documents' AND deleted = 0",
			array($recordId)
		);
		return ($result !== false && $db->num_rows($result) > 0);
	}

	/**
	 * 既に関連付けされているかどうか
	 *
	 * @param int $parentId
	 * @param int $recordId
	 * @return bool
	 */
	private function isRelated($parentId, $recordId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT notesid FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?",
			array($parentId, $recordId)
		);
		return ($result !== false && $db->num_rows($result) > 0);
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
