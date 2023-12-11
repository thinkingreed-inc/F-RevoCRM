<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class HelpDesk_TicketsByStatus_Dashboard extends Vtiger_IndexAjax_View {
 
    function getSearchParams($value,$assignedto = '',$dates) {
        $listSearchParams = array();
        $conditions = array(array('ticketstatus','e',decode_html(urlencode(escapeSlashes($value)))));
        if($assignedto != '') array_push($conditions,array('assigned_user_id','e',  decode_html(urlencode(escapeSlashes(getUserFullName($assignedto))))));
        if(!empty($dates)){
            array_push($conditions,array('createdtime','bw',$dates['start'].' 00:00:00,'.$dates['end'].' 23:59:59'));
        }
        $listSearchParams[] = $conditions;
        return '&search_params='. json_encode($listSearchParams);
    }

	public function process(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$data = $request->get('data');
		
		$dates = $request->get('createdtime');
		
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$data = $moduleModel->getTicketsByStatus($request->get('owner'), $dates);

        $listViewUrl = $moduleModel->getListViewUrlWithAllFilter();
        for($i = 0;$i<php7_count($data);$i++){
            $data[$i]["links"] = $listViewUrl.$this->getSearchParams($data[$i][2],$request->get('owner'), $request->get('dateFilter')).'&nolistcache=1';
        }
		$widget = Vtiger_Widget_Model::getInstance($linkId, $currentUser->getId());

		//Include special script and css needed for this widget

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);

		$accessibleUsers = $currentUser->getAccessibleUsersForModule($moduleName);
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);

		$content = $request->get('content');
		if(!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/TicketsByStatus.tpl', $moduleName);
		}
	}
}
