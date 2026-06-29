<?php

class Documents_DetailAPI_Api extends Vtiger_Api_Controller {

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

		$result = $this->getDocumentDetail($recordId);
		return $this->sendSuccess($result);
	}

	private function getDocumentDetail($recordId) {
		$db = PearDatabase::getInstance();

		// フォルダ権限チェック（非管理者の場合）
		$folderPermWhere = '';
		$folderPermParams = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			$userId = $currentUser->getId();
			require_once 'include/utils/GetUserGroups.php';
			$userGroups = new GetUserGroups();
			$userGroups->getAllUserGroups($userId);
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
			$folderPermParams = array($userId, $userRoleId);

			if (!empty($groupIds)) {
				$groupPlaceholders = implode(',', array_fill(0, count($groupIds), '?'));
				$fpConditions[] = "(fp.target_type = 'group' AND fp.target_id IN ($groupPlaceholders))";
				$folderPermParams = array_merge($folderPermParams, $groupIds);
			}

			$fpWhere = implode(' OR ', $fpConditions);
			$folderPermWhere = " AND EXISTS (SELECT 1 FROM vtiger_folder_permissions fp WHERE fp.folderid = vtiger_notes.folderid AND ($fpWhere))";
		}

		$params = array_merge(array($recordId), $folderPermParams);
		$result = $db->pquery(
			"SELECT vtiger_notes.*, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime,
				vtiger_crmentity.createdtime, vtiger_crmentity.modifiedby,
				vtiger_crmentity.description AS crm_description,
				vtiger_attachmentsfolder.foldername,
				CASE WHEN vtiger_users.id IS NOT NULL
					THEN CONCAT(vtiger_users.last_name, ' ', vtiger_users.first_name)
					ELSE vtiger_groups.groupname
				END AS assigned_user_name,
				u2.last_name AS modified_by_lastname, u2.first_name AS modified_by_firstname
			FROM vtiger_notes
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
			LEFT JOIN vtiger_attachmentsfolder ON vtiger_attachmentsfolder.folderid = vtiger_notes.folderid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_users u2 ON u2.id = vtiger_crmentity.modifiedby
			WHERE vtiger_notes.notesid = ? AND vtiger_crmentity.deleted = 0" . $folderPermWhere,
			$params
		);

		if ($result === false || $db->num_rows($result) === 0) {
			throw new Exception('Document not found');
		}

		$row = $db->query_result_rowdata($result, 0);

		// ファイル詳細
		$fileDetails = array();
		$downloadUrl = '';
		$previewUrl = '';
		if ($row['filelocationtype'] === 'I' && !empty($row['filename'])) {
			$attachResult = $db->pquery(
				"SELECT vtiger_attachments.attachmentsid, vtiger_attachments.name, vtiger_attachments.type, vtiger_attachments.path, vtiger_attachments.storedname
				FROM vtiger_attachments
				INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
				WHERE vtiger_seattachmentsrel.crmid = ?",
				array($recordId)
			);
			if ($attachResult !== false && $db->num_rows($attachResult) > 0) {
				$fileDetails = $db->query_result_rowdata($attachResult, 0);
				$downloadUrl = 'index.php?module=Documents&action=DownloadFile&record=' . $recordId
					. '&fileid=' . $fileDetails['attachmentsid']
					. '&name=' . urlencode($fileDetails['name']);
				$previewUrl = 'index.php?module=Documents&view=FilePreview&record=' . $recordId;
			}
		}

		// 関連レコード（モジュール日本語名・取引サマリ付き）
		$relatedRecords = array();
		$relResult = $db->pquery(
			"SELECT vtiger_senotesrel.crmid, vtiger_crmentity.setype, vtiger_crmentity.label
			FROM vtiger_senotesrel
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_senotesrel.crmid
			WHERE vtiger_senotesrel.notesid = ? AND vtiger_crmentity.deleted = 0
			ORDER BY vtiger_crmentity.setype, vtiger_crmentity.label",
			array($recordId)
		);
		if ($relResult !== false) {
			$relRows = $db->num_rows($relResult);
			for ($i = 0; $i < $relRows; $i++) {
				$relRow = $db->query_result_rowdata($relResult, $i);
				$moduleName = $relRow['setype'];
				$moduleLabel = $this->getModuleLabel($moduleName);
				$summary = $this->getTransactionSummary($db, (int) $relRow['crmid'], $moduleName);
				$relatedRecords[] = array(
					'id' => $relRow['crmid'],
					'module' => $moduleName,
					'module_label' => $moduleLabel,
					'label' => decode_html($relRow['label']),
					'summary' => $summary,
				);
			}
		}

		// 電帳法データ
		$compliance = null;
		if (!empty($row['document_category'])) {
			$compliance = array(
				'document_category' => $row['document_category'],
				'preservation_type' => $row['preservation_type'],
				'file_hash_algorithm' => $row['file_hash_algorithm'],
				'file_hash' => $row['file_hash'],
				'scan_resolution_dpi' => $row['scan_resolution_dpi'] !== null ? (int) $row['scan_resolution_dpi'] : null,
				'scan_color_type' => $row['scan_color_type'],
				'original_paper_size' => $row['original_paper_size'],
				'scanned_by' => $row['scanned_by'] !== null ? (int) $row['scanned_by'] : null,
				'scanned_at' => $row['scanned_at'],
				'receipt_date' => $row['receipt_date'],
				'input_deadline' => $row['input_deadline'],
				'input_deadline_status' => $row['input_deadline_status'],
				'compliance_status' => $row['compliance_status'],
				'compliance_checked_at' => $row['compliance_checked_at'],
				'compliance_notes' => $row['compliance_notes'],
			);
		}

		// 動的フィールド（Vtiger_Fieldに登録されたカスタムフィールド）
		$dynamicFields = array();
		$moduleModel = Vtiger_Module_Model::getInstance('Documents');
		$allFields = $moduleModel->getFields();
		foreach ($allFields as $fieldName => $fieldModel) {
			if ($fieldModel->isCustomField() && $fieldModel->get('table') === 'vtiger_notes') {
				$columnName = $fieldModel->get('column');
				$value = isset($row[$columnName]) ? $row[$columnName] : null;
				$dynamicFields[$fieldName] = $value;

				// 参照フィールド（uitype=10等）の場合、表示名を解決
				if ($fieldModel->isReferenceField() && !empty($value) && intval($value) > 0) {
					try {
						$refId = intval($value);
						$refModuleName = getSalesEntityType($refId);
						if ($refModuleName) {
							$refLabel = Vtiger_Util_Helper::getRecordName($refId);
							$dynamicFields[$fieldName . '_display'] = $refLabel ? $refLabel : '';
							$dynamicFields[$fieldName . '_module'] = $refModuleName;
						}
					} catch (Exception $e) {
						// 参照レコードが見つからない場合は無視
					}
				}
			}
		}

		// 監査ログ（直近10件）— 全ドキュメント対象
		$auditLog = array();
		require_once 'modules/Documents/utils/AuditLogger.php';
		$auditResult = Documents_AuditLogger::getAuditLog($recordId, 1, 10);
		$auditLog = $auditResult['records'];

		// ファイルバージョン — 全ドキュメント対象
		$fileVersions = array();
		{
			$versionResult = $db->pquery(
				"SELECT fv.*, CONCAT(u.last_name, ' ', u.first_name) AS creator_name
				FROM vtiger_notes_file_versions fv
				LEFT JOIN vtiger_users u ON u.id = fv.created_by
				WHERE fv.notesid = ?
				ORDER BY fv.version_number DESC",
				array($recordId)
			);
			if ($versionResult !== false) {
				$vRows = $db->num_rows($versionResult);
				for ($v = 0; $v < $vRows; $v++) {
					$vRow = $db->query_result_rowdata($versionResult, $v);
					// バージョン別ダウンロードURL生成（DownloadVersionアクション経由）
					$versionNum = (int) $vRow['version_number'];
					$vDownloadUrl = 'index.php?module=Documents&action=DownloadVersion&record=' . $recordId
						. '&version=' . $versionNum;
					$fileVersions[] = array(
						'version_number' => (int) $vRow['version_number'],
						'file_hash' => $vRow['file_hash'],
						'file_size' => (int) $vRow['file_size'],
						'change_reason' => $vRow['change_reason'],
						'created_by' => (int) $vRow['created_by'],
						'creator_name' => decode_html($vRow['creator_name']),
						'created_at' => $vRow['created_at'],
						'is_current' => (int) $vRow['is_current'] === 1,
						'download_url' => $vDownloadUrl,
					);
				}
			}
		}

		// フォルダパス（パンくず用）
		$folderPath = $this->getFolderPath($db, (int) $row['folderid']);

		$modifiedByName = '';
		if (!empty($row['modified_by_lastname'])) {
			$modifiedByName = decode_html($row['modified_by_lastname'] . ' ' . $row['modified_by_firstname']);
		}

		return array(
			'id' => $recordId,
			'title' => decode_html($row['title']),
			'filename' => decode_html($row['filename']),
			'filetype' => $row['filetype'],
			'filesize' => (int) $row['filesize'],
			'filelocationtype' => $row['filelocationtype'],
			'folderid' => (int) $row['folderid'],
			'foldername' => decode_html($row['foldername']),
			'folder_path' => $folderPath,
			'assigned_user_id' => $row['smownerid'],
			'assigned_user_name' => decode_html($row['assigned_user_name']),
			'modifiedtime' => $row['modifiedtime'],
			'createdtime' => $row['createdtime'],
			'modified_by_name' => $modifiedByName,
			'filedownloadcount' => (int) $row['filedownloadcount'],
			'filestatus' => (int) $row['filestatus'],
			'fileversion' => $row['fileversion'],
			'starred' => false,
			'notecontent' => decode_html($row['notecontent']),
			'note_no' => $row['note_no'],
			'download_url' => $downloadUrl,
			'preview_url' => $previewUrl,
			'related_records' => $relatedRecords,
			'compliance' => $compliance,
			'dynamic_fields' => $dynamicFields,
			'audit_log' => $auditLog,
			'file_versions' => $fileVersions,
		);
	}

	/**
	 * モジュールの日本語ラベルを取得する
	 */
	private function getModuleLabel($moduleName) {
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if ($moduleModel) {
			return vtranslate($moduleName, $moduleName);
		}
		return $moduleName;
	}

	/**
	 * 取引系モジュールのサマリ情報（日付・金額）を取得する
	 */
	private function getTransactionSummary($db, $crmId, $moduleName) {
		$fieldMap = array(
			'SalesOrder' => array('date' => 'duedate', 'amount' => 'hdnGrandTotal'),
			'Invoice' => array('date' => 'invoicedate', 'amount' => 'hdnGrandTotal'),
			'PurchaseOrder' => array('date' => 'duedate', 'amount' => 'hdnGrandTotal'),
			'Quotes' => array('date' => 'validtill', 'amount' => 'hdnGrandTotal'),
		);

		if (!isset($fieldMap[$moduleName])) {
			return null;
		}

		$fields = $fieldMap[$moduleName];
		$tableMap = array(
			'SalesOrder' => 'vtiger_salesorder',
			'Invoice' => 'vtiger_invoice',
			'PurchaseOrder' => 'vtiger_purchaseorder',
			'Quotes' => 'vtiger_quotes',
		);
		$idMap = array(
			'SalesOrder' => 'salesorderid',
			'Invoice' => 'invoiceid',
			'PurchaseOrder' => 'purchaseorderid',
			'Quotes' => 'quoteid',
		);

		$table = $tableMap[$moduleName];
		$idCol = $idMap[$moduleName];

		$result = $db->pquery(
			"SELECT {$fields['date']} AS tx_date, {$fields['amount']} AS tx_amount
			FROM $table WHERE $idCol = ?",
			array($crmId)
		);

		if ($result === false || $db->num_rows($result) === 0) {
			return null;
		}

		$row = $db->query_result_rowdata($result, 0);
		return array(
			'date' => $row['tx_date'],
			'amount' => $row['tx_amount'],
			'currency_symbol' => '¥',
		);
	}

	private function getFolderPath($db, $folderId) {
		$path = array();
		$maxDepth = 10;
		$currentId = $folderId;

		while ($currentId > 0 && $maxDepth > 0) {
			$result = $db->pquery(
				"SELECT folderid, foldername, COALESCE(parent_folderid, 0) AS parent_folderid
				FROM vtiger_attachmentsfolder WHERE folderid = ?",
				array($currentId)
			);
			if ($result === false || $db->num_rows($result) === 0) break;

			$row = $db->query_result_rowdata($result, 0);
			array_unshift($path, array(
				'id' => (int) $row['folderid'],
				'name' => decode_html($row['foldername']),
			));
			$currentId = (int) $row['parent_folderid'];
			$maxDepth--;
		}
		return $path;
	}
}
