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
			WHERE vtiger_notes.notesid = ? AND vtiger_crmentity.deleted = 0",
			array($recordId)
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

		// 関連レコード
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
				$relatedRecords[] = array(
					'id' => $relRow['crmid'],
					'module' => $relRow['setype'],
					'label' => decode_html($relRow['label']),
				);
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
