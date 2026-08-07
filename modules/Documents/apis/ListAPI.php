<?php
require_once 'modules/Documents/utils/ComplianceChecker.php';

class Documents_ListAPI_Api extends Vtiger_Api_Controller {

	/** 1ページあたりの最大件数 */
	const MAX_PAGE_LIMIT = 100;

	/** LIKE 検索のエスケープ文字（バックスラッシュは sql_mode の影響を受けるため使わない） */
	const LIKE_ESCAPE_CHAR = '!';

	/** 既定のソートカラム */
	const DEFAULT_SORT_COLUMN = 'vtiger_crmentity.modifiedtime';

	/**
	 * ソートに使える項目とカラムの対応
	 * ここに無い項目は既定にフォールバックする（SQL に値を連結しないため）
	 */
	public static function getAllowedSortColumns() {
		return array(
			'notes_title' => 'vtiger_notes.title',
			'title' => 'vtiger_notes.title',
			'filename' => 'vtiger_notes.filename',
			'filesize' => 'vtiger_notes.filesize',
			'modifiedtime' => 'vtiger_crmentity.modifiedtime',
			'createdtime' => 'vtiger_crmentity.createdtime',
			'assigned_user_id' => 'vtiger_crmentity.smownerid',
			'filedownloadcount' => 'vtiger_notes.filedownloadcount',
		);
	}

	/**
	 * ソート項目をカラム名に変換する（ホワイトリスト外は既定）
	 *
	 * @param string $sortBy
	 * @return string
	 */
	public static function resolveSortColumn($sortBy) {
		$allowed = self::getAllowedSortColumns();
		return isset($allowed[$sortBy]) ? $allowed[$sortBy] : self::DEFAULT_SORT_COLUMN;
	}

	/**
	 * ソート順を ASC / DESC に正規化する
	 *
	 * @param string $sortOrder
	 * @return string
	 */
	public static function normalizeSortOrder($sortOrder) {
		return (strtoupper((string) $sortOrder) === 'ASC') ? 'ASC' : 'DESC';
	}

	/**
	 * ページ番号を正規化する（1未満は1）
	 *
	 * @param mixed $page
	 * @return int
	 */
	public static function normalizePage($page) {
		$page = (int) $page;
		return ($page < 1) ? 1 : $page;
	}

	/**
	 * 1ページの件数を正規化する
	 *
	 * 指定が無い・不正なら既定の20件、上限を超える指定は上限に丸める。
	 *
	 * @param mixed $pageLimit
	 * @return int
	 */
	public static function normalizePageLimit($pageLimit) {
		$pageLimit = (int) $pageLimit;
		if ($pageLimit < 1) {
			return 20;
		}
		return ($pageLimit > self::MAX_PAGE_LIMIT) ? self::MAX_PAGE_LIMIT : $pageLimit;
	}

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	protected function processApi(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if ($mode === 'columns') {
			$result = $this->getAvailableColumns($request);
			return $this->sendSuccess($result);
		}

		$result = $this->getDocumentsList($request);
		return $this->sendSuccess($result);
	}

	private function getDocumentsList(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = 'Documents';
		$currentUser = Users_Record_Model::getCurrentUserModel();

		// パラメータ取得
		$page = (int) $request->get('page', 1);
		$pageLimit = (int) $request->get('pageLimit', 20);
		$page = self::normalizePage($page);
		$pageLimit = self::normalizePageLimit($pageLimit);
		$startIndex = ($page - 1) * $pageLimit;

		$folderId = $request->get('folder_id');
		$searchKeyword = $request->get('search_keyword');
		$sortBy = $request->get('sort_by', 'modifiedtime');
		$sortOrder = $request->get('sort_order', 'DESC');
		$filterType = $request->get('filter_type'); // 'starred', 'recent'
		$parentModule = $request->get('parent_module');
		$parentId = $request->get('parent_id');
		// 関連付け候補の絞り込み（指定した親レコードに紐づいていないドキュメントのみ）
		$excludeParentId = (int) $request->get('exclude_parent_id');
		$activeOnly = $request->get('active_only');

		// 電帳法フィルターパラメータ
		$complianceFilter = $request->get('compliance_filter');
		$documentCategory = $request->get('document_category');
		$preservationType = $request->get('preservation_type');
		$complianceStatus = $request->get('compliance_status');
		$hasRelatedRecord = $request->get('has_related_record');
		$inputDeadlineStatus = $request->get('input_deadline_status');

		$sortColumn = self::resolveSortColumn($sortBy);
		$sortOrder = self::normalizeSortOrder($sortOrder);

		$userId = $currentUser->getId();

		// ベースクエリ（vtiger_crmentity_user_field: 既存のスター機能テーブル）
		$baseQuery = "FROM vtiger_notes
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
			LEFT JOIN vtiger_attachmentsfolder ON vtiger_attachmentsfolder.folderid = vtiger_notes.folderid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_notescf ON vtiger_notescf.notesid = vtiger_notes.notesid
			LEFT JOIN vtiger_crmentity_user_field ON vtiger_crmentity_user_field.recordid = vtiger_notes.notesid AND vtiger_crmentity_user_field.userid = " . intval($userId);

		// 関連レコードフィルタ（親レコードに紐づくドキュメントのみ取得）
		if (!empty($parentId)) {
			$baseQuery .= " INNER JOIN vtiger_senotesrel ON vtiger_senotesrel.notesid = vtiger_notes.notesid";
		}

		$where = " WHERE vtiger_crmentity.deleted = 0";
		$params = array();

		if (!empty($parentId)) {
			$where .= " AND vtiger_senotesrel.crmid = ?";
			$params[] = (int) $parentId;
		}

		// 関連付け候補の絞り込み（既に紐づいているドキュメントを除く）
		if ($excludeParentId > 0) {
			$where .= " AND NOT EXISTS (
				SELECT 1 FROM vtiger_senotesrel excl
				WHERE excl.notesid = vtiger_notes.notesid AND excl.crmid = ?
			)";
			$params[] = $excludeParentId;
		}

		// 有効なドキュメントのみ（関連付け候補の一覧で使用する）
		if ($activeOnly === 'true' || $activeOnly === '1') {
			$where .= " AND vtiger_notes.filestatus = 1";
		}

		// フォルダフィルタ（選択したフォルダのみ。子フォルダのドキュメントは含めない）
		if (!empty($folderId) && $folderId !== 'all') {
			$where .= " AND vtiger_notes.folderid = ?";
			$params[] = (int) $folderId;
		}

		// スター付きフィルタ
		if ($filterType === 'starred') {
			$where .= " AND vtiger_crmentity_user_field.starred = '1'";
		}

		// 全文検索（indexed_content + title + filename の OR条件）
		// 入力に含まれる % や _ はワイルドカードではなく文字として扱う
		if (!empty($searchKeyword)) {
			$keyword = '%' . self::escapeLikeValue($searchKeyword) . '%';
			$escape = " ESCAPE '" . self::LIKE_ESCAPE_CHAR . "'";
			$where .= " AND (vtiger_notes.title LIKE ?" . $escape
				. " OR vtiger_notes.filename LIKE ?" . $escape
				. " OR vtiger_notes.indexed_content LIKE ?" . $escape . ")";
			$params[] = $keyword;
			$params[] = $keyword;
			$params[] = $keyword;
		}

		// 電帳法フィルター
		if ($complianceFilter) {
			$where .= " AND " . Documents_ComplianceChecker::TARGET_SQL_CONDITION;
		}
		if (!empty($documentCategory)) {
			$where .= " AND vtiger_notes.document_category = ?";
			$params[] = $documentCategory;
		}
		if (!empty($preservationType)) {
			$where .= " AND vtiger_notes.preservation_type = ?";
			$params[] = $preservationType;
		}
		if (!empty($complianceStatus)) {
			$where .= " AND vtiger_notes.compliance_status = ?";
			$params[] = $complianceStatus;
		}
		if (!empty($inputDeadlineStatus)) {
			$where .= " AND vtiger_notes.input_deadline_status = ?";
			$params[] = $inputDeadlineStatus;
		}
		// 未関連のみ（電帳法対象かつ取引レコードに関連付けなし）
		if ($hasRelatedRecord === 'false' || $hasRelatedRecord === '0') {
			$where .= " AND " . Documents_ComplianceChecker::TARGET_SQL_CONDITION . "
				AND NOT EXISTS (
					SELECT 1 FROM vtiger_senotesrel snr
					INNER JOIN vtiger_crmentity ce ON ce.crmid = snr.crmid AND ce.deleted = 0
					WHERE snr.notesid = vtiger_notes.notesid
				)";
		}

		// フォルダ権限チェック（非管理者の場合）
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser()) {
			require_once 'include/utils/GetUserGroups.php';
			$userGroups = new GetUserGroups();
			$userGroups->getAllUserGroups($currentUser->getId());
			$groupIds = $userGroups->user_groups;

			$userRoleId = '';
			$roleResult = $db->pquery("SELECT roleid FROM vtiger_user2role WHERE userid = ?", array($userId));
			if ($roleResult !== false && $db->num_rows($roleResult) > 0) {
				$userRoleId = $db->query_result($roleResult, 0, 'roleid');
			}

			$fpConditions = array(
				"(fp.target_type = 'everyone')",
				"(fp.target_type = 'user' AND fp.target_id = ?)",
				"(fp.target_type = 'role' AND fp.target_id = ?)",
			);
			$fpParams = array($userId, $userRoleId);

			if (!empty($groupIds)) {
				$groupPlaceholders = implode(',', array_fill(0, count($groupIds), '?'));
				$fpConditions[] = "(fp.target_type = 'group' AND fp.target_id IN ($groupPlaceholders))";
				$fpParams = array_merge($fpParams, $groupIds);
			}

			$fpWhere = implode(' OR ', $fpConditions);
			$where .= " AND EXISTS (SELECT 1 FROM vtiger_folder_permissions fp WHERE fp.folderid = vtiger_notes.folderid AND ($fpWhere))";
			$params = array_merge($params, $fpParams);
		}

		// 件数取得
		$countQuery = "SELECT COUNT(*) AS total " . $baseQuery . $where;
		$countResult = $db->pquery($countQuery, $params);
		if ($countResult === false) {
			throw new Exception('Failed to execute count query');
		}
		$total = (int) $db->query_result($countResult, 0, 'total');

		// カスタムフィールド定義を取得
		$customFieldDefs = array();
		$moduleModel = Vtiger_Module_Model::getInstance('Documents');
		$allFields = $moduleModel->getFields();
		foreach ($allFields as $fieldName => $fieldModel) {
			if ($fieldModel->isCustomField() && $fieldModel->get('table') === 'vtiger_notes') {
				$customFieldDefs[$fieldName] = $fieldModel->get('column');
			}
		}

		// カスタムフィールドのSELECT句を動的に構築
		$customFieldSelect = '';
		foreach ($customFieldDefs as $fieldName => $columnName) {
			$customFieldSelect .= ", vtiger_notes." . $columnName;
		}

		// データ取得
		$selectQuery = "SELECT vtiger_notes.notesid, vtiger_notes.title, vtiger_notes.filename,
				vtiger_notes.filetype, vtiger_notes.filesize, vtiger_notes.filelocationtype,
				vtiger_notes.folderid, vtiger_notes.filedownloadcount, vtiger_notes.filestatus,
				vtiger_notes.fileversion, vtiger_notes.notecontent, vtiger_notes.note_no,
				vtiger_notes.document_category, vtiger_notes.preservation_type,
				vtiger_notes.compliance_status, vtiger_notes.compliance_notes,
				vtiger_notes.input_deadline, vtiger_notes.input_deadline_status,
				vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime,
				vtiger_crmentity.modifiedby,
				vtiger_attachmentsfolder.foldername,
				CASE WHEN vtiger_users.id IS NOT NULL
					THEN CONCAT(vtiger_users.last_name, ' ', vtiger_users.first_name)
					ELSE vtiger_groups.groupname
				END AS assigned_user_name,
				CASE WHEN vtiger_crmentity_user_field.starred = '1' THEN 1 ELSE 0 END AS starred
				$customFieldSelect
			" . $baseQuery . $where;

		$selectQuery .= " ORDER BY $sortColumn $sortOrder";
		$selectQuery .= " LIMIT " . (int) $startIndex . "," . (int) $pageLimit;

		$result = $db->pquery($selectQuery, $params);
		if ($result === false) {
			throw new Exception('Failed to execute list query');
		}

		$records = array();
		$numRows = $db->num_rows($result);
		for ($i = 0; $i < $numRows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$recordId = $row['notesid'];

			// ダウンロードURL生成
			$downloadUrl = '';
			if ($row['filelocationtype'] === 'I' && !empty($row['filename']) && $row['filestatus']) {
				$fileDetails = $this->getFileDetailsForRecord($db, $recordId);
				if (!empty($fileDetails)) {
					$downloadUrl = 'index.php?module=Documents&action=DownloadFile&record=' . $recordId . '&fileid=' . $fileDetails['attachmentsid'] . '&name=' . urlencode($fileDetails['name']);
				}
			}

			// 電帳法データ
			$complianceData = null;
			if (!empty($row['document_category'])) {
				$complianceData = array(
					'document_category' => $row['document_category'],
					'preservation_type' => $row['preservation_type'],
					'compliance_status' => $row['compliance_status'],
					// 不適合理由は翻訳キーで保存されているため、表示用に翻訳する
					'compliance_notes' => Documents_ComplianceChecker::translateNotes(
						decode_html($row['compliance_notes']), false),
					'input_deadline' => $row['input_deadline'],
					'input_deadline_status' => $row['input_deadline_status'],
				);
			}

			// 動的フィールド値を取得
			$dynamicFields = array();
			foreach ($customFieldDefs as $fieldName => $columnName) {
				$dynamicFields[$fieldName] = isset($row[$columnName]) ? $row[$columnName] : null;
			}

			$records[] = array(
				'id' => $recordId,
				'title' => decode_html($row['title']),
				'filename' => decode_html($row['filename']),
				'filetype' => $row['filetype'],
				'filesize' => (int) $row['filesize'],
				'filelocationtype' => $row['filelocationtype'],
				'folderid' => (int) $row['folderid'],
				'foldername' => decode_html($row['foldername']),
				'assigned_user_id' => $row['smownerid'],
				'assigned_user_name' => decode_html($row['assigned_user_name']),
				'modifiedtime' => $row['modifiedtime'],
				'createdtime' => $row['createdtime'],
				'filedownloadcount' => (int) $row['filedownloadcount'],
				'filestatus' => (int) $row['filestatus'],
				'fileversion' => $row['fileversion'],
				'starred' => (int)$row['starred'] === 1,
				'notecontent' => decode_html($row['notecontent']),
				'note_no' => $row['note_no'],
				'download_url' => $downloadUrl,
				'compliance' => $complianceData,
				'dynamic_fields' => $dynamicFields,
			);
		}

		return array(
			'records' => $records,
			'total' => $total,
			'page' => $page,
			'pageLimit' => $pageLimit,
		);
	}

	/**
	 * 利用可能なカラム一覧を返す（リスト表示のカラム設定用）
	 */
	private function getAvailableColumns(Vtiger_Request $request) {
		$moduleModel = Vtiger_Module_Model::getInstance('Documents');
		$allFields = $moduleModel->getFields();
		$columns = array();

		foreach ($allFields as $fieldName => $fieldModel) {
			if (!$fieldModel->isViewable()) {
				continue;
			}
			$columns[] = array(
				'name' => $fieldName,
				'label' => vtranslate($fieldModel->get('label'), 'Documents'),
				'field_type' => $fieldModel->getFieldDataType(),
				'uitype' => $fieldModel->get('uitype'),
				'table' => $fieldModel->get('table'),
				'column' => $fieldModel->get('column'),
				'is_custom' => $fieldModel->isCustomField(),
				'is_mandatory' => $fieldModel->isMandatory(),
			);
		}

		return array(
			'columns' => $columns,
		);
	}

	/**
	 * LIKE 検索の値をエスケープする
	 *
	 * エスケープ文字自身 → % → _ の順に置換する（二重エスケープを避ける）。
	 *
	 * @param string $value
	 * @return string
	 */
	private static function escapeLikeValue($value) {
		$escape = self::LIKE_ESCAPE_CHAR;
		return str_replace(
			array($escape, '%', '_'),
			array($escape . $escape, $escape . '%', $escape . '_'),
			(string) $value
		);
	}

	private function getFileDetailsForRecord($db, $recordId) {
		$result = $db->pquery(
			"SELECT vtiger_attachments.attachmentsid, vtiger_attachments.name
			FROM vtiger_attachments
			INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
			WHERE vtiger_seattachmentsrel.crmid = ?",
			array($recordId)
		);
		if ($db->num_rows($result) > 0) {
			return $db->query_result_rowdata($result, 0);
		}
		return null;
	}
}
