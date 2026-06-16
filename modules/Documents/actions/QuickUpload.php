<?php

class Documents_QuickUpload_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			$moduleName = 'Documents';
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$folderId = (int) $request->get('folderid', 1);
			$uploadedFiles = array();

			if (empty($_FILES) || !isset($_FILES['files'])) {
				throw new Exception('No files uploaded');
			}

			$files = $_FILES['files'];
			// 複数ファイル対応
			if (!is_array($files['name'])) {
				$files = array(
					'name' => array($files['name']),
					'type' => array($files['type']),
					'tmp_name' => array($files['tmp_name']),
					'error' => array($files['error']),
					'size' => array($files['size']),
				);
			}

			$fileCount = count($files['name']);
			if ($fileCount > 10) {
				throw new Exception('Maximum 10 files can be uploaded at once');
			}

			for ($i = 0; $i < $fileCount; $i++) {
				if ($files['error'][$i] !== UPLOAD_ERR_OK) {
					$uploadedFiles[] = array(
						'success' => false,
						'filename' => $files['name'][$i],
						'error' => $this->getUploadErrorMessage($files['error'][$i]),
					);
					continue;
				}

				$fileName = $files['name'][$i];
				$title = pathinfo($fileName, PATHINFO_FILENAME);

				// レコード作成
				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
				$recordModel->set('notes_title', $title);
				$recordModel->set('filename', $fileName);
				$recordModel->set('filelocationtype', 'I');
				$recordModel->set('filestatus', 1);
				$recordModel->set('filesize', $files['size'][$i]);
				$recordModel->set('filetype', $files['type'][$i]);
				$recordModel->set('folderid', $folderId);
				$recordModel->set('assigned_user_id', $currentUser->getId());
				$recordModel->set('mode', '');

				// ファイルを$_FILESに設定（save_module()が参照するため）
				$_FILES['filename'] = array(
					'name' => $files['name'][$i],
					'type' => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error' => $files['error'][$i],
					'size' => $files['size'][$i],
				);

				$recordModel->save();

				// 親レコードとの関連付け
				$parentModule = $request->get('parent_module');
				$parentId = $request->get('parent_id');
				if (!empty($parentModule) && !empty($parentId)) {
					$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $parentModule);
					$relationModel = Vtiger_Relation_Model::getInstance($parentRecordModel->getModule(), $recordModel->getModule());
					if ($relationModel) {
						$relationModel->addRelation($parentId, $recordModel->getId());
					}
				}

				$uploadedFiles[] = array(
					'success' => true,
					'id' => $recordModel->getId(),
					'title' => $title,
					'filename' => $fileName,
					'filesize' => $files['size'][$i],
					'filetype' => $files['type'][$i],
				);
			}

			$response->setResult(array(
				'success' => true,
				'files' => $uploadedFiles,
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	private function getUploadErrorMessage($errorCode) {
		switch ($errorCode) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'File size exceeds the maximum upload limit';
			case UPLOAD_ERR_PARTIAL:
				return 'File was only partially uploaded';
			case UPLOAD_ERR_NO_FILE:
				return 'No file was uploaded';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Missing temporary folder';
			case UPLOAD_ERR_CANT_WRITE:
				return 'Failed to write file to disk';
			default:
				return 'Unknown upload error';
		}
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
