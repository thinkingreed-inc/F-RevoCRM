<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Documents/utils/FolderPermission.php';

class Documents_MoveDocuments_Action extends Vtiger_Mass_Action {
	
	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}


	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		// 移動先フォルダに書き込めること（移動元は1件ずつ process() で見る）
		$folderId = (int) $request->get('folderid');
		if ($folderId > 0 && !Documents_FolderPermission::canEditFolder($folderId)) {
			throw new AppException(vtranslate('LBL_FOLDER_EDIT_DENIED', 'Documents'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$documentIdsList = $this->getRecordsListFromRequest($request);
		$folderId = $request->get('folderid');

		if (!empty ($documentIdsList)) {
			foreach ($documentIdsList as $documentId) {
				$documentModel = Vtiger_Record_Model::getInstanceById($documentId, $moduleName);
				// 参照のみのフォルダにあるものは動かさない。
				// 1件で全体を止めず、動かせなかったものとして数える
				if (Users_Privileges_Model::isPermitted($moduleName, 'EditView', $documentId)
						&& Documents_FolderPermission::canEditDocument($documentId)) {
					$documentModel->set('folderid', $folderId);
					$documentModel->set('mode', 'edit');
					$documentModel->save();
				} else {
					$documentsMoveDenied[] = $documentModel->getName();
				}
			}
		}
		if (empty ($documentsMoveDenied)) {
			$result = array('success'=>true, 'message'=>vtranslate('LBL_DOCUMENTS_MOVED_SUCCESSFULLY', $moduleName));
		} else {
			$result = array('success'=>false, 'message'=>vtranslate('LBL_DENIED_DOCUMENTS', $moduleName), 'LBL_RECORDS_LIST'=>$documentsMoveDenied);
		}

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}