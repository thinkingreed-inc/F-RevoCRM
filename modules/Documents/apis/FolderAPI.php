<?php

class Documents_FolderAPI_Api extends Vtiger_Api_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('tree');
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
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

		$folders = array();
		$numRows = $db->num_rows($result);
		for ($i = 0; $i < $numRows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$fid = (int) $row['folderid'];
			$folders[] = array(
				'id' => $fid,
				'name' => decode_html($row['foldername']),
				'description' => decode_html($row['description']),
				'parent_id' => (int) $row['parent_folderid'],
				'sequence' => (int) $row['sequence'],
				'count' => isset($folderCounts[$fid]) ? $folderCounts[$fid] : 0,
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

	function validateRequest(Vtiger_Request $request) {
		$mode = $request->getMode();
		if ($mode === 'tree') {
			$request->validateReadAccess();
		} else {
			$request->validateWriteAccess();
		}
	}
}
