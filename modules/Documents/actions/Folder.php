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

class Documents_Folder_Action extends Vtiger_Action_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}
	
	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	/**
	 * フォルダ単位の権限を確認する
	 *
	 * 標準の権限判定はモジュール単位までしか見ないため、既存フォルダを
	 * 変更・削除する場合は apis/FolderAPI と同じ判定を通す。
	 * 新規作成はフォルダを特定できないため、ここでは判定しない。
	 */
	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);

		$folderId = (int) $request->get('folderid');
		$isExistingFolder = ($request->getMode() === 'delete' || $request->get('savemode') === 'edit');
		if ($isExistingFolder && $folderId > 0
			&& !Documents_FolderPermission::canEditFolder($folderId)) {
			throw new AppException(vtranslate('LBL_FOLDER_EDIT_DENIED', 'Documents'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
		}
	}

	public function save($request) {
		$moduleName = $request->getModule();
		$folderName = $request->get('foldername');
		$folderDesc = $request->get('folderdesc');
		$result = array();

		if (!empty ($folderName)) {  
            $saveMode = $request->get('savemode');
            $folderModel = Documents_Folder_Model::getInstance();
            if($saveMode == 'edit') {
                $folderId = $request->get('folderid');
                $folderModel = Documents_Folder_Model::getInstanceById($folderId);
                $folderModel->set('mode','edit');                
            }
			
			$folderModel->set('foldername', $folderName);
			$folderModel->set('description', $folderDesc);

			if ($folderModel->checkDuplicate()) {
				throw new AppException(vtranslate('LBL_FOLDER_EXISTS', $moduleName));
				exit;
			}

			$folderModel->save();
			if ($saveMode != 'edit') {
				// 新UIの作成と同じく既定の権限（オーナー＝作成者 / 編集＝全員）を入れる。
				// 入れないと、権限行の無いフォルダとして作成者以外から見えなくなる
				Documents_FolderPermission::applyDefaultPermissions($folderModel->getId());
			}
			$result = array('success'=>true, 'message'=>vtranslate('LBL_FOLDER_SAVED', $moduleName), 'info'=>$folderModel->getInfoArray());

			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
		}
	}


	public function delete($request) {
		$moduleName = $request->getModule();
		$folderId = $request->get('folderid');
		$result = array();

		if (!empty ($folderId)) {
			$folderModel = Documents_Folder_Model::getInstanceById($folderId);
			if (!($folderModel->hasDocuments())) {
				$folderModel->delete();
				$result = array('success'=>true, 'message'=>vtranslate('LBL_FOLDER_DELETED', $moduleName));
			} else {
				$result = array('success'=>false, 'message'=>vtranslate('LBL_FOLDER_HAS_DOCUMENTS', $moduleName));
			}
		}

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
    
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }
}
