<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Users/Users.php';
require_once 'include/events/include.inc';
vimport('includes.runtime.LanguageHandler');
class Emails_Tracker_Handler {
    
    public function process($data = array()) {
        global $current_user;
        $current_user = Users::getActiveAdminUser();
        
        $type = $data['method'];
        if($type == 'click'){
            $this->clickHandler($data);
        } else if ($type == 'open'){
            $this->openHandler($data);
        }
    }
	
    protected function clickHandler($data = []) {
        $redirectUrl = rawurldecode($data['redirectUrl']);
        $redirectLinkName = rawurldecode($data['linkName']);
        if((strpos($_SERVER['HTTP_REFERER'], vglobal('site_URL')) !== false) || (empty($_SERVER['HTTP_REFERER']) && $_REQUEST['fromcrm'])) {
            if (!empty($redirectUrl)) {
                return Vtiger_Functions::redirectUrl($redirectUrl);
            }
            exit;
        }

        $parentId = $data['parentId'];
        $recordId = $data['record'];

        if ($parentId && $recordId) {
            $db = PearDatabase::getInstance();
            /* Currently,To track emails we insert a hidden image whose source will be tracking URL.
             * When email client loads that image, email open will be tracked. But some email client doesn't load images by default and email open will not be tracked.
             * If that email has some link and user clicks on that, link click will be tracked but email will still show as not read which is not correct.
             * If any link is clicked on the email and that is getting tracked, we need to check if the email is marked as Open or not and if not we need to mark it as Open on click action.             
             */
            $result = $db->pquery("SELECT 1 FROM vtiger_email_access WHERE crmid = ? AND mailid = ? ", array($parentId, $recordId));
            if (!$db->num_rows($result)) {
                $this->openHandler(array('record' => $recordId, 'parentId' => $parentId));
            }
            $recordModel = Emails_Record_Model::getInstanceById($recordId);
            $recordModel->trackClicks($parentId);
        }
		
        if(!empty($redirectUrl)) {
                return Vtiger_Functions::redirectUrl($redirectUrl);
        }
    }
    
    protected function openHandler($data = array()) {
        $recordId = $data['record'];
        $parentId = $data['parentId'];
        if($recordId && $parentId){
            if((strpos($_SERVER['HTTP_REFERER'], vglobal('site_URL')) !== false) || (empty($_SERVER['HTTP_REFERER']) && $_REQUEST['fromcrm'])) {
                // If a email is opened from CRM then we no need to track but need to be redirected
                Vtiger_ShortURL_Helper::sendTrackerImage();
                exit;
            }
            $recordModel = Emails_Record_Model::getInstanceById($recordId);
			
			//If email is opened in last 1 hr, not tracking email open again.
			if($recordModel->isEmailOpenedRecently($parentId)) {
				Vtiger_ShortURL_Helper::sendTrackerImage();
				exit;
			}
            $recordModel->updateTrackDetails($parentId);
            Vtiger_ShortURL_Helper::sendTrackerImage();
        }
    }
}