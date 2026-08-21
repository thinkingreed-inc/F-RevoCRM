<?php

class Documents_FolderAPI_Api extends Vtiger_Api_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('tree');
		$this->exposeMethod('save');
		$this->exposeMethod('ensurePath');
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

		// 現在のユーザーの権限情報を取得
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$isAdmin = $currentUser->isAdminUser();
		$userId = $currentUser->getId();
		$userRoleId = $currentUser->get('roleid');
		$userGroupIds = $this->getUserGroupIds($userId);

		// 実行ユーザーがスターを付けたドキュメント数
		$starredCount = 0;
		$starredResult = $db->pquery(
			"SELECT COUNT(*) AS cnt FROM vtiger_notes
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
			INNER JOIN vtiger_crmentity_user_field
				ON vtiger_crmentity_user_field.recordid = vtiger_notes.notesid
				AND vtiger_crmentity_user_field.userid = ?
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentity_user_field.starred = '1'",
			array($userId)
		);
		if ($starredResult !== false && $db->num_rows($starredResult) > 0) {
			$starredCount = (int) $db->query_result($starredResult, 0, 'cnt');
		}

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
			// 権限設定を編集できるかどうかは画面側で推測させず、サーバーの判定を渡す
			'is_admin' => (bool) $isAdmin,
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
		$targetFolderId = 0;
		if ($saveMode === 'edit') {
			$folderId = $request->get('folderid');
			$folderModel = Documents_Folder_Model::getInstanceById($folderId);
			$folderModel->set('mode', 'edit');
			$targetFolderId = (int) $folderId;
		}

		$folderModel->set('foldername', $folderName);
		$folderModel->set('description', $folderDesc);
		// 重複判定は同じ親フォルダの中だけで行うため、判定前に親を渡す
		$folderModel->set('parent_folderid', $parentFolderId);

		if ($folderModel->checkDuplicate()) {
			throw new AppException(vtranslate('LBL_FOLDER_EXISTS', $moduleName));
		}

		$db = PearDatabase::getInstance();
		// 親フォルダの指定を検証する（存在しない・自分自身・子孫を親にできない）
		// 名前だけ保存されて親が更新されない状態にならないよう、保存前に確認する
		$this->assertValidParentFolder($db, $targetFolderId, $parentFolderId, $moduleName);

		// モジュールの権限だけでは通してしまうため、フォルダ単位の編集権限を確認する
		if ($saveMode === 'edit') {
			$this->assertCanEditFolder($db, $targetFolderId);
		} else {
			// 新規作成は置き場所（親フォルダ）に対する編集権限で判断する
			$this->assertCanEditFolder($db, $parentFolderId);
		}

		$folderModel->save();

		// parent_folderidの更新
		$db->pquery(
			"UPDATE vtiger_attachmentsfolder SET parent_folderid = ? WHERE folderid = ?",
			array($parentFolderId, $folderModel->getId())
		);

		// 新規作成時: デフォルト権限（全員: 編集可能）を設定
		// 編集権限があれば参照も可能なため、editのみで十分
		if ($saveMode !== 'edit') {
			$this->addDefaultFolderPermission($db, $folderModel->getId());
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

	/** ensurePath で受け付けるフォルダ階層の深さ上限 */
	const MAX_PATH_DEPTH = 20;

	/** フォルダ名の最大文字数（vtiger_attachmentsfolder.foldername = varchar(200) に合わせる） */
	const MAX_FOLDER_NAME_LENGTH = 200;

	/**
	 * フォルダ階層を用意して、末端のフォルダIDを返す
	 *
	 * フォルダのドラッグ＆ドロップで使う。指定された親フォルダの下に path の各段を
	 * 上から順にたどり、同じ親に同名フォルダがあれば再利用し、無ければ作成する。
	 *
	 * @param Vtiger_Request $request path（JSON配列）, parent_folderid
	 * @return array folderid（末端）, created（新規作成したフォルダID）
	 */
	public function ensurePath($request) {
		$moduleName = $request->getModule();
		$parentFolderId = (int) $request->get('parent_folderid', 0);
		$segments = $this->parsePathSegments($request->get('path'), $moduleName);

		$db = PearDatabase::getInstance();
		if ($parentFolderId > 0) {
			// 起点のフォルダが存在することを確認する
			$this->assertValidParentFolder($db, 0, $parentFolderId, $moduleName);
		} else {
			$parentFolderId = 0;
		}
		$this->assertCanEditFolder($db, $parentFolderId);

		$currentParentId = $parentFolderId;
		$created = array();
		$path = array();
		foreach ($segments as $segment) {
			$existingId = Documents_Folder_Model::findByNameAndParent($segment, $currentParentId);
			if ($existingId > 0) {
				$currentParentId = $existingId;
				$path[] = array('id' => $existingId, 'name' => $segment);
				continue;
			}

			$folderModel = Documents_Folder_Model::getInstance();
			$folderModel->set('foldername', $segment);
			$folderModel->set('description', '');
			$folderModel->set('parent_folderid', $currentParentId);
			$folderModel->save();
			$newFolderId = (int) $folderModel->getId();

			$db->pquery(
				"UPDATE vtiger_attachmentsfolder SET parent_folderid = ? WHERE folderid = ?",
				array($currentParentId, $newFolderId)
			);
			// 新規作成時のデフォルト権限（全員: 編集可能）は save と同じ扱いにする
			$this->addDefaultFolderPermission($db, $newFolderId);

			$created[] = $newFolderId;
			$currentParentId = $newFolderId;
			$path[] = array('id' => $newFolderId, 'name' => $segment);
		}

		return $this->sendSuccess(array(
			'folderid' => $currentParentId,
			'created' => $created,
			'path' => $path,
		));
	}

	/**
	 * path パラメータをフォルダ名の配列に整える
	 *
	 * 空要素・`.`・`..` は取り除く（親をさかのぼる指定を作らせない）。
	 *
	 * @param mixed $rawPath JSON文字列または配列
	 * @param string $moduleName
	 * @return array フォルダ名の配列
	 * @throws Exception 深すぎる／名前が不正な場合
	 */
	private function parsePathSegments($rawPath, $moduleName) {
		$decoded = $rawPath;
		if (is_string($rawPath)) {
			$decoded = json_decode($rawPath, true);
			if (!is_array($decoded)) {
				// 単一のフォルダ名として扱う
				$decoded = array($rawPath);
			}
		}
		if (!is_array($decoded)) {
			throw new Exception(vtranslate('LBL_FOLDER_NAME_REQUIRED', $moduleName));
		}

		$segments = array();
		foreach ($decoded as $segment) {
			if (!is_string($segment) && !is_numeric($segment)) {
				continue;
			}
			$name = trim((string) $segment);
			if ($name === '' || $name === '.' || $name === '..') {
				continue;
			}
			// フォルダ名に区切り文字は含めない（1段ずつ受け取る）
			$name = str_replace(array('/', '\\'), '_', $name);
			// カラム長を超える名前は切り詰める（マルチバイトを壊さない）
			if (function_exists('mb_substr')) {
				$name = mb_substr($name, 0, self::MAX_FOLDER_NAME_LENGTH, 'UTF-8');
			} else {
				$name = substr($name, 0, self::MAX_FOLDER_NAME_LENGTH);
			}
			$segments[] = $name;
		}

		if (empty($segments)) {
			throw new Exception(vtranslate('LBL_FOLDER_NAME_REQUIRED', $moduleName));
		}
		if (count($segments) > self::MAX_PATH_DEPTH) {
			throw new Exception(vtranslate('LBL_FOLDER_PATH_TOO_DEEP', $moduleName));
		}
		return $segments;
	}

	public function delete($request) {
		$moduleName = $request->getModule();
		$folderId = $request->get('folderid');
		$this->assertCanEditFolder(PearDatabase::getInstance(), $folderId);

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

		// フォルダを消したら権限行も消す。残しておくと、
		// 採番（max(folderid)+1）でIDが再利用されたときに
		// 新しいフォルダが以前の権限を引き継いでしまう
		$db = PearDatabase::getInstance();
		$db->pquery(
			'DELETE FROM vtiger_folder_permissions WHERE folderid = ?',
			array((int) $folderId)
		);

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

		// 過去の不具合で target_id が NULL の行が重複していることがあるため、
		// 同じ内容の行はまとめて返す（UNIQUE 制約は NULL を別値として扱い重複を防げない）
		$result = $db->pquery(
			"SELECT MIN(fp.permission_id) AS permission_id, fp.permission_type, fp.target_type, fp.target_id,
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
			GROUP BY fp.permission_type, fp.target_type, fp.target_id, target_name
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

		// Vtiger_Request::get() は "[" / "{" で始まる値を自動でデコードして配列にして返すため、
		// 文字列で来た場合だけ json_decode する（配列を json_decode すると PHP 8 で TypeError になる）
		$permissions = $request->get('permissions');
		if (is_string($permissions)) {
			$permissions = json_decode($permissions, true);
		}
		if (!is_array($permissions)) {
			throw new Exception('Invalid permissions data');
		}

		// 既存権限を削除
		$db->pquery("DELETE FROM vtiger_folder_permissions WHERE folderid = ?", array($folderId));

		// 新しい権限を挿入
		$validTypes = array('view', 'edit');
		$validTargets = array('everyone', 'user', 'role', 'group');
		$inserted = 0;
		// 同じ内容が2回送られても1件にする（target_id が NULL の行は
		// UNIQUE 制約でも重複を防げないため、ここで弾く）
		$seen = array();

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

			$key = $permType . '|' . $targetType . '|' . (string) $targetId;
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;

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
		// 最上位の役割（Organization）は権限の付与先として意味がないため除外する
		$rResult = $db->pquery(
			"SELECT roleid, rolename, depth FROM vtiger_role WHERE depth > 0 ORDER BY parentrole",
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

	/**
	 * 親フォルダの指定が妥当かどうかを確認する
	 *
	 * 自分自身や自分の子孫を親にすると階層が循環し、ツリー表示やパンくずが
	 * 終わらなくなるため、保存前に弾く。
	 *
	 * @param PearDatabase $db
	 * @param int $folderId 対象フォルダ（新規作成時は 0）
	 * @param int $parentFolderId 指定された親フォルダ（0 はルート）
	 * @param string $moduleName
	 * @throws Exception 妥当でない場合
	 */
	private function assertValidParentFolder($db, $folderId, $parentFolderId, $moduleName) {
		if ($parentFolderId <= 0) {
			return;// ルート
		}
		if ($folderId > 0 && $parentFolderId === $folderId) {
			throw new Exception(vtranslate('LBL_FOLDER_PARENT_SELF', $moduleName));
		}

		$exists = $db->pquery(
			"SELECT folderid FROM vtiger_attachmentsfolder WHERE folderid = ?",
			array($parentFolderId)
		);
		if ($exists === false || $db->num_rows($exists) === 0) {
			throw new Exception(vtranslate('LBL_FOLDER_PARENT_NOT_FOUND', $moduleName));
		}

		// 指定された親から根までたどり、対象フォルダに行き当たれば循環する
		$currentId = $parentFolderId;
		$visited = array();
		while ($currentId > 0 && !isset($visited[$currentId])) {
			if ($currentId === $folderId) {
				throw new Exception(vtranslate('LBL_FOLDER_PARENT_CIRCULAR', $moduleName));
			}
			$visited[$currentId] = true;
			$result = $db->pquery(
				"SELECT COALESCE(parent_folderid, 0) AS parent_folderid
				FROM vtiger_attachmentsfolder WHERE folderid = ?",
				array($currentId)
			);
			if ($result === false || $db->num_rows($result) === 0) {
				return;
			}
			$currentId = (int) $db->query_result($result, 0, 'parent_folderid');
		}
	}

	// ─── 権限チェックヘルパー ───

	/**
	 * 対象フォルダを編集する権限があることを確認する
	 *
	 * モジュールの権限だけでは足りない（フォルダ単位の編集権限が必要）ため、
	 * 保存・削除・階層作成の前に必ず通す。
	 *
	 * @param PearDatabase $db
	 * @param int $folderId 0 はルート（フォルダ単位の制限なし）
	 * @throws Exception 権限が無い場合
	 */
	private function assertCanEditFolder($db, $folderId) {
		$folderId = (int) $folderId;
		if ($folderId <= 0) {
			return;// ルート直下は個別フォルダの権限対象外
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		if ($currentUser->isAdminUser()) {
			return;
		}

		$userId = $currentUser->getId();
		$userRoleId = $currentUser->get('roleid');
		$userGroupIds = $this->getUserGroupIds($userId);
		if (!$this->hasPermission($db, $folderId, 'edit', $userId, $userRoleId, $userGroupIds)) {
			throw new Exception(vtranslate('LBL_FOLDER_EDIT_DENIED', 'Documents'));
		}
	}

	/**
	 * 新規フォルダに既定の権限（全員: 編集可能）を1件だけ入れる
	 *
	 * target_id が NULL の行は UNIQUE 制約で重複を防げない（MySQL は NULL を別値として扱う）ため、
	 * 存在確認をしてから挿入する。
	 *
	 * @param PearDatabase $db
	 * @param int $folderId
	 */
	private function addDefaultFolderPermission($db, $folderId) {
		$exists = $db->pquery(
			"SELECT 1 FROM vtiger_folder_permissions
			WHERE folderid = ? AND permission_type = 'edit' AND target_type = 'everyone'",
			array($folderId)
		);
		if ($exists !== false && $db->num_rows($exists) > 0) {
			return;
		}
		$db->pquery(
			"INSERT INTO vtiger_folder_permissions (folderid, permission_type, target_type, target_id)
			VALUES (?, 'edit', 'everyone', NULL)",
			array($folderId)
		);
	}

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
