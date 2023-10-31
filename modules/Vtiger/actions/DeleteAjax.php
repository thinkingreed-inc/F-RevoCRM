<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_DeleteAjax_Action extends Vtiger_Delete_Action {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if($commentId = $request->get('commentId')){
			$recordModel = ModComments_Record_Model::getInstanceById($commentId, $moduleName);
			$recordModel->delete();
			
			$response = new Vtiger_Response();
			$response->setResult(array('result'=>'true'));
		} else{
			$recordId = $request->get('record');

			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->delete();

			$cvId = $request->get('viewname');
			deleteRecordFromDetailViewNavigationRecords($recordId, $cvId, $moduleName);
			$response = new Vtiger_Response();
			$response->setResult(array('viewname'=>$cvId, 'module'=>$moduleName));		
		}
		$response->emit();
		}
	}
