<?php
/**
 * ドキュメントの一括操作 API
 *
 * 一覧画面で選択した複数のドキュメントをまとめて処理する。
 *
 * Usage:
 *   POST module=Documents&api=BulkAction&mode=delete&records[]=1&records[]=2
 *     → ごみ箱へ移動する（完全削除はしない）
 *   POST module=Documents&api=BulkAction&mode=move&folderid=3&records[]=1&records[]=2
 *     → フォルダを移動する
 *
 * 参照できないドキュメント・権限の無いドキュメントは対象から除き、
 * 件数を返して利用者に伝える（黙って捨てない）。
 */
require_once 'modules/Documents/utils/FolderPermission.php';
require_once 'modules/Documents/utils/AuditLogger.php';

class Documents_BulkAction_Api extends Vtiger_Api_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	protected function processApi(Vtiger_Request $request) {
		$mode = $request->get('mode');
		switch ($mode) {
			case 'delete':
				return $this->sendSuccess($this->deleteRecords($request));
			case 'move':
				return $this->sendSuccess($this->moveRecords($request));
			default:
				$this->sendError('Invalid mode: ' . $mode, 400);
		}
	}

	/**
	 * 選択したドキュメントをごみ箱へ移動する
	 *
	 * 完全削除ではないため、電帳法対象のドキュメントも対象にできる。
	 * 削除の記録（監査ログ）は電帳法対象のドキュメントに残す。
	 *
	 * @param Vtiger_Request $request
	 * @return array ['deleted' => int, 'denied' => int, 'failed' => int, 'total' => int]
	 */
	private function deleteRecords(Vtiger_Request $request) {
		$recordIds = $this->getRecordIds($request);
		$deleted = 0;
		$denied = 0;
		$failed = 0;

		foreach ($recordIds as $recordId) {
			if (!$this->canDelete($recordId)) {
				$denied++;
				continue;
			}
			try {
				$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Documents');
				// 電帳法対象は削除時点の内容を監査ログに残す
				if (method_exists($recordModel, 'logDeletion')) {
					$recordModel->logDeletion();
				}
				$recordModel->delete();
				$deleted++;
			} catch (Exception $e) {
				$this->logError("Documents bulk delete failed for record {$recordId}", $e);
				$failed++;
			}
		}

		return array(
			'total' => count($recordIds),
			'deleted' => $deleted,
			'denied' => $denied,
			'failed' => $failed,
		);
	}

	/**
	 * 選択したドキュメントのフォルダを変更する
	 *
	 * 変更内容は各ドキュメントの変更履歴に残す。
	 *
	 * @param Vtiger_Request $request
	 * @return array ['moved' => int, 'denied' => int, 'skipped' => int, 'failed' => int, 'total' => int]
	 */
	private function moveRecords(Vtiger_Request $request) {
		$folderId = (int) $request->get('folderid');
		if ($folderId <= 0) {
			throw new Exception(vtranslate('LBL_BULK_FOLDER_REQUIRED', 'Documents'));
		}
		$this->assertFolderIsWritable($folderId);

		$db = PearDatabase::getInstance();
		$recordIds = $this->getRecordIds($request);
		$moved = 0;
		$denied = 0;
		$skipped = 0;
		$failed = 0;

		foreach ($recordIds as $recordId) {
			if (!$this->canEdit($recordId)) {
				$denied++;
				continue;
			}
			if ($this->getFolderIdOf($recordId) === $folderId) {
				// 既に移動先のフォルダにある
				$skipped++;
				continue;
			}
			try {
				$before = Documents_AuditLogger::snapshotFields($recordId);
				$db->pquery("UPDATE vtiger_notes SET folderid = ? WHERE notesid = ?",
					array($folderId, $recordId));
				$db->pquery("UPDATE vtiger_crmentity SET modifiedtime = ?, modifiedby = ? WHERE crmid = ?",
					array(date('Y-m-d H:i:s'), $this->getCurrentUserId(), $recordId));
				$after = Documents_AuditLogger::snapshotFields($recordId);
				Documents_AuditLogger::logFieldChanges($recordId, $before, $after);
				$moved++;
			} catch (Exception $e) {
				$this->logError("Documents bulk move failed for record {$recordId}", $e);
				$failed++;
			}
		}

		return array(
			'total' => count($recordIds),
			'moved' => $moved,
			'denied' => $denied,
			'skipped' => $skipped,
			'failed' => $failed,
			'folderid' => $folderId,
		);
	}

	/**
	 * リクエストからドキュメントIDの配列を取り出す
	 *
	 * records[]（配列）とカンマ区切りの records の両方を受け付ける。
	 * 件数で切り捨てず、指定されたものはすべて処理する。
	 *
	 * @param Vtiger_Request $request
	 * @return array 重複を除いたドキュメントIDの配列
	 * @throws Exception 1件も指定が無い場合
	 */
	private function getRecordIds(Vtiger_Request $request) {
		$raw = $request->get('records');
		if (!is_array($raw)) {
			$raw = ($raw === null || $raw === '') ? array() : explode(',', (string) $raw);
		}

		$recordIds = array();
		foreach ($raw as $value) {
			$recordId = (int) trim((string) $value);
			if ($recordId > 0 && !in_array($recordId, $recordIds, true) && $this->isDocument($recordId)) {
				$recordIds[] = $recordId;
			}
		}
		if (empty($recordIds)) {
			throw new Exception(vtranslate('LBL_BULK_NO_RECORD_SELECTED', 'Documents'));
		}
		return $recordIds;
	}

	/**
	 * 対象が存在するドキュメントかどうか
	 */
	private function isDocument($recordId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT crmid FROM vtiger_crmentity
			 WHERE crmid = ? AND setype = 'Documents' AND deleted = 0",
			array($recordId)
		);
		return ($result !== false && $db->num_rows($result) > 0);
	}

	/**
	 * ドキュメントのフォルダIDを返す
	 */
	private function getFolderIdOf($recordId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery("SELECT folderid FROM vtiger_notes WHERE notesid = ?", array($recordId));
		if ($result === false || $db->num_rows($result) === 0) {
			return 0;
		}
		return (int) $db->query_result($result, 0, 'folderid');
	}

	/**
	 * 削除できるか（フォルダを参照できて、削除権限がある）
	 */
	private function canDelete($recordId) {
		return Documents_FolderPermission::canAccessDocument($recordId)
			&& Users_Privileges_Model::isPermitted('Documents', 'Delete', $recordId);
	}

	/**
	 * 編集できるか（フォルダを参照できて、編集権限がある）
	 */
	private function canEdit($recordId) {
		return Documents_FolderPermission::canAccessDocument($recordId)
			&& Users_Privileges_Model::isPermitted('Documents', 'EditView', $recordId);
	}

	/**
	 * 移動先のフォルダが存在し、書き込めることを確認する
	 *
	 * @param int $folderId
	 * @throws Exception
	 */
	private function assertFolderIsWritable($folderId) {
		$db = PearDatabase::getInstance();
		$exists = $db->pquery(
			"SELECT folderid FROM vtiger_attachmentsfolder WHERE folderid = ?", array($folderId));
		if ($exists === false || $db->num_rows($exists) === 0) {
			throw new Exception(vtranslate('LBL_BULK_FOLDER_NOT_FOUND', 'Documents'));
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($currentUser->isAdminUser()) {
			return;
		}
		// 移動先フォルダに編集権限が必要
		if (!$this->hasFolderEditPermission($folderId, (int) $currentUser->getId())) {
			throw new Exception(vtranslate('LBL_BULK_FOLDER_DENIED', 'Documents'));
		}
	}

	/**
	 * フォルダの編集権限を持つか
	 */
	private function hasFolderEditPermission($folderId, $userId) {
		$db = PearDatabase::getInstance();

		$conditions = array(
			"(fp.target_type = 'everyone')",
			"(fp.target_type = 'user' AND fp.target_id = ?)",
		);
		$params = array($folderId, $userId);

		$roleResult = $db->pquery("SELECT roleid FROM vtiger_user2role WHERE userid = ?", array($userId));
		if ($roleResult !== false && $db->num_rows($roleResult) > 0) {
			$conditions[] = "(fp.target_type = 'role' AND fp.target_id = ?)";
			$params[] = $db->query_result($roleResult, 0, 'roleid');
		}

		require_once 'include/utils/GetUserGroups.php';
		$userGroups = new GetUserGroups();
		$userGroups->getAllUserGroups($userId);
		if (!empty($userGroups->user_groups)) {
			$placeholders = implode(',', array_fill(0, count($userGroups->user_groups), '?'));
			$conditions[] = "(fp.target_type = 'group' AND fp.target_id IN ($placeholders))";
			$params = array_merge($params, $userGroups->user_groups);
		}

		$result = $db->pquery(
			"SELECT 1 FROM vtiger_folder_permissions fp
			 WHERE fp.folderid = ? AND fp.permission_type = 'edit'
			   AND (" . implode(' OR ', $conditions) . ") LIMIT 1",
			$params
		);
		return ($result !== false && $db->num_rows($result) > 0);
	}

	/**
	 * 実行ユーザーIDを取得する
	 */
	private function getCurrentUserId() {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		return $currentUser ? (int) $currentUser->getId() : 0;
	}

	/**
	 * 1件の失敗で全体を止めないため、ログにだけ残す
	 */
	private function logError($message, $exception) {
		global $log;
		if (isset($log) && is_object($log)) {
			$log->error($message . ': ' . $exception->getMessage());
		}
	}

	function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
