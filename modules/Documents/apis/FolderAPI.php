<?php

class Documents_FolderAPI_Api extends Vtiger_Api_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('tree');
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
		$this->exposeMethod('getPermissions');
		$this->exposeMethod('savePermissions');
		$this->exposeMethod('getPermissionTargets');
	}

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	protected function processApi(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			return $this->invokeExposedMethod($mode, $request);
		} else {
			return $this->tree($request);
		}
	}

	public function tree(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();

		// フォルダ一覧取得
		$result = $db->pquery(
			"SELECT f.folderid, f.foldername, f.description, f.createdby, f.sequence,
				COALESCE(f.parent_folderid, 0) AS parent_folderid
			FROM vtiger_attachmentsfolder f
			ORDER BY f.sequence ASC, f.foldername ASC",
			array()
		);
		if ($result === false) {
			throw new Exception('Failed to fetch folders');
		}

		// 各フォルダのドキュメント件数を取得
		$countResult = $db->pquery(
			"SELECT vtiger_notes.folderid, COUNT(*) AS doc_count
			FROM vtiger_notes
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
			WHERE vtiger_crmentity.deleted = 0
			GROUP BY vtiger_notes.folderid",
			array()
		);
		$folderCounts = array();
		if ($countResult !== false) {
			$countRows = $db->num_rows($countResult);
			for ($i = 0; $i < $countRows; $i++) {
				$fid = $db->query_result($countResult, $i, 'folderid');
				$cnt = $db->query_result($countResult, $i, 'doc_count');
				$folderCounts[(int)$fid] = (int)$cnt;
			}
		}

		// 全ドキュメント数
		$totalResult = $db->pquery(
			"SELECT COUNT(*) AS total FROM vtiger_notes
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
			WHERE vtiger_crmentity.deleted = 0",
			array()
		);
		$totalCount = 0;
		if ($totalResult !== false) {
			$totalCount = (int) $db->query_result($totalResult, 0, 'total');
		}

		$starredCount = 0;

		// 現在のユーザーの権限情報を取得
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$isAdmin = $currentUser->isAdminUser();
		$userId = $currentUser->getId();
		$userRoleId = $currentUser->get('roleid');
		$userGroupIds = $this->getUserGroupIds($userId);

		$folders = array();
		$numRows = $db->num_rows($result);
		for ($i = 0; $i < $numRows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$fid = (int) $row['folderid'];

			// 管理者は全フォルダ参照可能。一般ユーザーは権限チェック
			// 編集権限があれば参照も可能
			if (!$isAdmin
				&& !$this->hasPermission($db, $fid, 'view', $userId, $userRoleId, $userGroupIds)
				&& !$this->hasPermission($db, $fid, 'edit', $userId, $userRoleId, $userGroupIds)) {
				continue;
			}

			$canEdit = $isAdmin || $this->hasPermission($db, $fid, 'edit', $userId, $userRoleId, $userGroupIds);

			$folders[] = array(
				'id' => $fid,
				'name' => decode_html($row['foldername']),
				'description' => decode_html($row['description']),
				'parent_id' => (int) $row['parent_folderid'],
				'sequence' => (int) $row['sequence'],
				'count' => isset($folderCounts[$fid]) ? $folderCounts[$fid] : 0,
				'can_edit' => $canEdit,
			);
		}

		return $this->sendSuccess(array(
			'folders' => $folders,
			'totalCount' => $totalCount,
			'starredCount' => $starredCount,
		));
	}

	public function save($request) {
		$moduleName = $request->getModule();
		$folderName = $request->get('foldername');
		$folderDesc = $request->get('folderdesc');
		$parentFolderId = (int) $request->get('parent_folderid', 0);
		$saveMode = $request->get('savemode');

		if (empty($folderName)) {
			throw new Exception(vtranslate('LBL_FOLDER_NAME_REQUIRED', $moduleName));
		}

		$folderModel = Documents_Folder_Model::getInstance();
		if ($saveMode === 'edit') {
			$folderId = $request->get('folderid');
			$folderModel = Documents_Folder_Model::getInstanceById($folderId);
			$folderModel->set('mode', 'edit');
		}

		$folderModel->set('foldername', $folderName);
		$folderModel->set('description', $folderDesc);

		if ($folderModel->checkDuplicate()) {
			throw new AppException(vtranslate('LBL_FOLDER_EXISTS', $moduleName));
		}

		$folderModel->save();

		// parent_folderidの更新
		$db = PearDatabase::getInstance();
		$db->pquery(
			"UPDATE vtiger_attachmentsfolder SET parent_folderid = ? WHERE folderid = ?",
			array($parentFolderId, $folderModel->getId())
		);

		// 新規作成時: デフォルト権限（全員: 編集可能）を設定
		// 編集権限があれば参照も可能なため、editのみで十分
		if ($saveMode !== 'edit') {
			$newFolderId = $folderModel->getId();
			$db->pquery("INSERT IGNORE INTO vtiger_folder_permissions (folderid, permission_type, target_type, target_id) VALUES (?, 'edit', 'everyone', NULL)", array($newFolderId));
		}

		return $this->sendSuccess(array(
			'success' => true,
			'message' => vtranslate('LBL_FOLDER_SAVED', $moduleName),
			'folder' => array(
				'id' => $folderModel->getId(),
				'name' => $folderModel->getName(),
				'description' => $folderModel->getDescription(),
				'parent_id' => $parentFolderId,
			),
		));
	}

	public function delete($request) {
		$moduleName = $request->getModule();
		$folderId = $request->get('folderid');

		if (empty($folderId)) {
			throw new Exception('Folder ID is required');
		}

		$folderModel = Documents_Folder_Model::getInstanceById($folderId);

		// サブフォルダがあるか確認
		$db = PearDatabase::getInstance();
		$childResult = $db->pquery(
			"SELECT COUNT(*) AS cnt FROM vtiger_attachmentsfolder WHERE parent_folderid = ?",
			array($folderId)
		);
		if ($childResult !== false && (int) $db->query_result($childResult, 0, 'cnt') > 0) {
			throw new Exception(vtranslate('LBL_FOLDER_HAS_SUBFOLDERS', $moduleName));
		}

		if ($folderModel->hasDocuments()) {
			throw new Exception(vtranslate('LBL_FOLDER_HAS_DOCUMENTS', $moduleName));
		}

		$folderModel->delete();

		return $this->sendSuccess(array(
			'success' => true,
			'message' => vtranslate('LBL_FOLDER_DELETED', $moduleName),
		));
	}

	/**
	 * フォルダの権限設定を取得する
	 */
	public function getPermissions($request) {
		$db = PearDatabase::getInstance();
		$folderId = (int) $request->get('folderid');
		if (empty($folderId)) {
			throw new Exception('Folder ID is required');
		}

		$result = $db->pquery(
			"SELECT fp.*,
				CASE fp.target_type
					WHEN 'user' THEN CONCAT(u.last_name, ' ', u.first_name)
					WHEN 'role' THEN r.rolename
					WHEN 'group' THEN g.groupname
					ELSE NULL
				END AS target_name
			FROM vtiger_folder_permissions fp
			LEFT JOIN vtiger_users u ON fp.target_type = 'user' AND fp.target_id = u.id
			LEFT JOIN vtiger_role r ON fp.target_type = 'role' AND fp.target_id = r.roleid
			LEFT JOIN vtiger_groups g ON fp.target_type = 'group' AND fp.target_id = g.groupid
			WHERE fp.folderid = ?
			ORDER BY fp.permission_type, fp.target_type, fp.target_id",
			array($folderId)
		);

		$permissions = array();
		if ($result !== false) {
			$numRows = $db->num_rows($result);
			for ($i = 0; $i < $numRows; $i++) {
				$row = $db->query_result_rowdata($result, $i);
				$targetName = $row['target_name'] ? decode_html($row['target_name']) : null;
				// 役割名を翻訳
				if ($row['target_type'] === 'role' && $targetName) {
					$targetName = vtranslate($targetName, 'Roles');
				}
				$permissions[] = array(
					'permission_id' => (int) $row['permission_id'],
					'permission_type' => $row['permission_type'],
					'target_type' => $row['target_type'],
					'target_id' => $row['target_id'] !== null ? $row['target_id'] : null,
					'target_name' => $targetName,
				);
			}
		}

		return $this->sendSuccess(array('permissions' => $permissions));
	}

	/**
	 * フォルダの権限設定を保存する（全件置換方式）
	 */
	public function savePermissions($request) {
		$db = PearDatabase::getInstance();
		$folderId = (int) $request->get('folderid');
		if (empty($folderId)) {
			throw new Exception('Folder ID is required');
		}

		// 管理者のみ
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new Exception('Admin permission required');
		}

		$permissionsJson = $request->get('permissions');
		$permissions = json_decode($permissionsJson, true);
		if (!is_array($permissions)) {
			throw new Exception('Invalid permissions data');
		}

		// 既存権限を削除
		$db->pquery("DELETE FROM vtiger_folder_permissions WHERE folderid = ?", array($folderId));

		// 新しい権限を挿入
		$validTypes = array('view', 'edit');
		$validTargets = array('everyone', 'user', 'role', 'group');
		$inserted = 0;

		foreach ($permissions as $perm) {
			$permType = isset($perm['permission_type']) ? $perm['permission_type'] : '';
			$targetType = isset($perm['target_type']) ? $perm['target_type'] : '';
			$targetId = isset($perm['target_id']) ? $perm['target_id'] : null;

			if (!in_array($permType, $validTypes) || !in_array($targetType, $validTargets)) {
				continue;
			}

			if ($targetType === 'everyone') {
				$targetId = null;
			} else if (empty($targetId)) {
				continue;
			}

			$db->pquery(
				"INSERT IGNORE INTO vtiger_folder_permissions (folderid, permission_type, target_type, target_id) VALUES (?, ?, ?, ?)",
				array($folderId, $permType, $targetType, $targetId)
			);
			$inserted++;
		}

		return $this->sendSuccess(array(
			'success' => true,
			'inserted' => $inserted,
		));
	}

	/**
	 * 権限付与先の候補を取得する（ユーザー/役割/グループ一覧）
	 */
	public function getPermissionTargets($request) {
		$db = PearDatabase::getInstance();

		// ユーザー一覧
		$users = array();
		$uResult = $db->pquery(
			"SELECT id, user_name, first_name, last_name FROM vtiger_users WHERE status = 'Active' AND deleted = 0 ORDER BY last_name, first_name",
			array()
		);
		if ($uResult !== false) {
			for ($i = 0; $i < $db->num_rows($uResult); $i++) {
				$users[] = array(
					'id' => (int) $db->query_result($uResult, $i, 'id'),
					'name' => decode_html($db->query_result($uResult, $i, 'last_name') . ' ' . $db->query_result($uResult, $i, 'first_name')),
				);
			}
		}

		// 役割一覧
		$roles = array();
		$rResult = $db->pquery(
			"SELECT roleid, rolename, depth FROM vtiger_role ORDER BY parentrole",
			array()
		);
		if ($rResult !== false) {
			for ($i = 0; $i < $db->num_rows($rResult); $i++) {
				$depth = (int) $db->query_result($rResult, $i, 'depth');
				$prefix = str_repeat('　', $depth);
				$rawName = decode_html($db->query_result($rResult, $i, 'rolename'));
				$roles[] = array(
					'id' => $db->query_result($rResult, $i, 'roleid'),
					'name' => $prefix . vtranslate($rawName, 'Roles'),
				);
			}
		}

		// グループ一覧
		$groups = array();
		$gResult = $db->pquery(
			"SELECT groupid, groupname FROM vtiger_groups ORDER BY groupname",
			array()
		);
		if ($gResult !== false) {
			for ($i = 0; $i < $db->num_rows($gResult); $i++) {
				$groups[] = array(
					'id' => (int) $db->query_result($gResult, $i, 'groupid'),
					'name' => decode_html($db->query_result($gResult, $i, 'groupname')),
				);
			}
		}

		return $this->sendSuccess(array(
			'users' => $users,
			'roles' => $roles,
			'groups' => $groups,
		));
	}

	// ─── 権限チェックヘルパー ───

	/**
	 * 指定ユーザーがフォルダに対して指定権限を持つかチェック
	 */
	private function hasPermission($db, $folderId, $permissionType, $userId, $roleId, $groupIds) {
		// everyone権限チェック
		$evResult = $db->pquery(
			"SELECT 1 FROM vtiger_folder_permissions WHERE folderid = ? AND permission_type = ? AND target_type = 'everyone'",
			array($folderId, $permissionType)
		);
		if ($evResult !== false && $db->num_rows($evResult) > 0) return true;

		// ユーザー個別権限
		$uResult = $db->pquery(
			"SELECT 1 FROM vtiger_folder_permissions WHERE folderid = ? AND permission_type = ? AND target_type = 'user' AND target_id = ?",
			array($folderId, $permissionType, $userId)
		);
		if ($uResult !== false && $db->num_rows($uResult) > 0) return true;

		// ロール権限
		if (!empty($roleId)) {
			$rResult = $db->pquery(
				"SELECT 1 FROM vtiger_folder_permissions WHERE folderid = ? AND permission_type = ? AND target_type = 'role' AND target_id = ?",
				array($folderId, $permissionType, $roleId)
			);
			if ($rResult !== false && $db->num_rows($rResult) > 0) return true;
		}

		// グループ権限
		if (!empty($groupIds)) {
			$placeholders = implode(',', array_fill(0, count($groupIds), '?'));
			$gResult = $db->pquery(
				"SELECT 1 FROM vtiger_folder_permissions WHERE folderid = ? AND permission_type = ? AND target_type = 'group' AND target_id IN ($placeholders)",
				array_merge(array($folderId, $permissionType), $groupIds)
			);
			if ($gResult !== false && $db->num_rows($gResult) > 0) return true;
		}

		return false;
	}

	/**
	 * ユーザーの所属グループIDを取得
	 */
	private function getUserGroupIds($userId) {
		$db = PearDatabase::getInstance();
		require_once 'include/utils/GetUserGroups.php';
		$userGroups = new GetUserGroups();
		$userGroups->getAllUserGroups($userId);
		return $userGroups->user_groups;
	}

	function validateRequest(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (in_array($mode, array('tree', 'getPermissions', 'getPermissionTargets'))) {
			$request->validateReadAccess();
		} else {
			$request->validateWriteAccess();
		}
	}
}
