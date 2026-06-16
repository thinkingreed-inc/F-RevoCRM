<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_InRelation_View extends Vtiger_RelatedList_View {

	function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$parentId = $request->get('record');

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('PARENT_ID', $parentId);

		return $viewer->view('DocumentsRelatedList.tpl', 'Documents', 'true');
	}

}
