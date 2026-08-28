<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PDFTemplates_MassDelete_Action extends Vtiger_Mass_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

    public function checkPermission($request) {
        $moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if(!$moduleModel->isActive()){
            return false;
        }
        return true;
    }
    
	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		$recordModel = new PDFTemplates_Record_Model();
		$recordModel->setModule($moduleName);
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		// $systemTemplate は「選択レコードにシステムテンプレートが含まれていた」フラグ。
		// PHP 8 では未定義変数の参照が Warning（display_errors=on で JSON レスポンスを破壊）になるため
		// 必ず先に初期化する。
		$systemTemplate = false;

		if($selectedIds == 'all' && empty($excludedIds)){
			$recordModel->deleteAllRecords();
		}else{
			$recordIds = $this->getRecordsListFromRequest($request);
			if (is_array($recordIds)) {
				foreach($recordIds as $recordId) {
					$recordModel = PDFTemplates_Record_Model::getInstanceById($recordId);
					if($recordModel->isSystemTemplate()) {
						$systemTemplate = true;
					} else {
						$recordModel->delete();
					}
				}
			}
		}

		$response = new Vtiger_Response();
		if($systemTemplate) {
			 $response->setError('502', vtranslate('LBL_NO_PERMISSIONS_TO_DELETE_SYSTEM_TEMPLATE', $moduleName));
        } else {
			$response->setResult(array('module'=>$moduleName));
		}
		$response->emit();
	}

	/**
	 * 親 Vtiger_Mass_Action::getRecordsListFromRequest と同シグネチャでオーバーライドする。
	 *
	 * PHP 8 では子クラスのメソッドシグネチャを親と非互換に変更すると Fatal Error になる。
	 * 以前は第2引数 $recordModel を受け取っていたが、PHP 8 で互換性違反となり MassDelete
	 * アクションのロード自体が失敗し、ボタン押下時に何も削除されない不具合となっていた。
	 * 必要な ModuleModel はメソッド内で再構築する。
	 *
	 * @param Vtiger_Request $request
	 * @return array|null
	 */
	protected function getRecordsListFromRequest(Vtiger_Request $request) {
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		if(!empty($selectedIds) && $selectedIds != 'all') {
			if(php7_count($selectedIds) > 0) {
				return $selectedIds;
			}
		}
		if(!empty($excludedIds)){
			$moduleModel = Vtiger_Module_Model::getInstance('PDFTemplates');
			$recordIds = $moduleModel->getRecordIds($excludedIds);
			return $recordIds;
		}
		return null;
	}
}
