<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Assets_Detail_View extends Vtiger_Detail_View {

	function preProcess(Vtiger_Request $request, $display=true) {
		$viewer = $this->getViewer($request);
		$viewer->assign('NO_SUMMARY', true);
		parent::preProcess($request);
	}

}
