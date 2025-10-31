<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Events Record Model Class
 */
class Events_Record_Model extends Calendar_Record_Model {
    
    protected $inviteesDetails;
    
    /**
	 * Function to get the Edit View url for the record
	 * @return <String> - Record Edit View Url
	 */
    //一覧表示から活動編集を行った際に、詳細画面に戻ってこれるようにrefererを追加
	public function getEditViewUrl() {
		$module = $this->getModule();
		return 'index.php?module=Calendar&view='.$module->getEditViewName().'&referer=Detail&record='.$this->getId();
	}

	/**
	 * Function to get the Delete Action url for the record
	 * @return <String> - Record Delete Action Url
	 */
	public function getDeleteUrl() {
		$module = $this->getModule();
		return 'index.php?module=Calendar&action='.$module->getDeleteActionName().'&record='.$this->getId();
	}

	/**
     * Funtion to get Duplicate Record Url
     * @return <String>
     */
    public function getDuplicateRecordUrl(){
        $module = $this->getModule();
		return 'index.php?module=Calendar&view='.$module->getEditViewName().'&record='.$this->getId().'&isDuplicate=true';

    }

    public function getRelatedToContactIdList() {
        $adb = PearDatabase::getInstance();
        $query = 'SELECT vtiger_cntactivityrel.contactid, vtiger_cntactivityrel.activityid, vtiger_crmentity.deleted FROM vtiger_cntactivityrel LEFT JOIN vtiger_crmentity ON vtiger_cntactivityrel.contactid = vtiger_crmentity.crmid WHERE vtiger_cntactivityrel.activityid=? AND vtiger_crmentity.deleted = 0';
        $result = $adb->pquery($query, array($this->getId()));
        $num_rows = $adb->num_rows($result);

        $contactIdList = array();
        for($i=0; $i<$num_rows; $i++) {
            $row = $adb->fetchByAssoc($result, $i);
            $contactIdList[$i] = $row['contactid'];
        }
        return $contactIdList;
    }

    public function getRelatedContactInfo() {
        $contactIdList = $this->getRelatedToContactIdList();
        $relatedContactInfo = array();
        foreach($contactIdList as $contactId) {
            $relatedContactInfo[] = array('name' => decode_html(Vtiger_Util_Helper::toSafeHTML(Vtiger_Util_Helper::getRecordName($contactId))) ,'id' => $contactId);
        }
        return $relatedContactInfo;
     }
     
     public function getRelatedContactInfoFromIds($eventIds){
         $adb = PearDatabase::getInstance();
        $query = 'SELECT vtiger_cntactivityrel.activityid as id, vtiger_cntactivityrel.contactid, vtiger_contactdetails.email FROM vtiger_cntactivityrel INNER JOIN vtiger_contactdetails
                  ON vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid  WHERE activityid in ('. generateQuestionMarks($eventIds) .')';
        $result = $adb->pquery($query, array($eventIds));
        $num_rows = $adb->num_rows($result);

        $contactInfo = array();
        for($i=0; $i<$num_rows; $i++) {
            $row = $adb->fetchByAssoc($result, $i);
            $contactInfo[$row['id']][] = array('name' => Vtiger_Util_Helper::toSafeHTML(Vtiger_Util_Helper::getRecordName($row['contactid'])),
                                    'email' => $row['email'], 'id' => $row['contactid']);
        }
        return $contactInfo;
     }
     
     /**
      * Funtion to get inviteed details for the event
      * @param <Int> $userId
      * @return <Array> - list with invitees and status details
      */
     public function getInviteesDetails($userId=FALSE) {
         if(!$this->inviteesDetails || $userId) {
            $adb = PearDatabase::getInstance();
            $sql = "SELECT vtiger_invitees.* FROM vtiger_invitees WHERE activityid=(SELECT invitee_parentid FROM vtiger_activity WHERE activityid = ?)";
            $sqlParams = array($this->getId());
            if($userId !== FALSE) {
                $sql .= " AND inviteeid = ?";
                $sqlParams[] = $userId;
            }
            $result = $adb->pquery($sql,$sqlParams);
            $inviteesDetails = array();

            $num_rows = $adb->num_rows($result);
            for($i=0; $i<$num_rows; $i++) {
                $inviteesDetails[$adb->query_result($result, $i,'inviteeid')] = $adb->query_result($result, $i,'status');
            }
            
            if(!$userId) {
                $this->inviteesDetails = $inviteesDetails;
            }
            return $inviteesDetails;
         }
         return $this->inviteesDetails;
     }
     
     /**
      * Function to get list of invitees id's
      * @return <Array> - List of invitees id's
      */
     public function getInvities() {
         return array_keys($this->getInviteesDetails());
     }
     
     /**
      * Function to update invitation status
      * @param <Int> $activityId
      * @param <Int> $userId
      * @param <String> $status
      */
     public function updateInvitationStatus($activityId,$userId,$status) {
         $adb = PearDatabase::getInstance();
         $sql = 'UPDATE vtiger_invitees SET status = ? WHERE activityid = ? AND inviteeid = ?';
         $adb->pquery($sql,array($status,$activityId,$userId));
         $this->inviteesDetails = NULL;
     }

     public function getInviteUserMailData() {
            $adb = PearDatabase::getInstance();

            $return_id = $this->getId();
            $cont_qry = "select * from vtiger_cntactivityrel where activityid=?";
            $cont_res = $adb->pquery($cont_qry, array($return_id));
            $noofrows = $adb->num_rows($cont_res);
            $cont_id = array();
            if($noofrows > 0) {
                for($i=0; $i<$noofrows; $i++) {
                    $cont_id[] = $adb->query_result($cont_res,$i,"contactid");
                }
            }
            $cont_name = '';
            foreach($cont_id as $key=>$id) {
                if($id != '') {
                    $contact_name = Vtiger_Util_Helper::getRecordName($id);
                    $cont_name .= $contact_name .', ';
                }
            }

			$parentId = $this->get('parent_id');
			$parentName = '';
			if($parentId != '') {
				$parentName = Vtiger_Util_Helper::getRecordName($parentId);
			}
			
            $cont_name  = trim($cont_name,', ');
            $mail_data = Array();
            $mail_data['user_id'] = $this->get('assigned_user_id');
            $mail_data['subject'] = $this->get('subject');
            $moduleName = $this->getModuleName();
            $mail_data['status'] = (($moduleName=='Calendar')?($this->get('taskstatus')):($this->get('eventstatus')));
            $mail_data['activity_mode'] = (($moduleName=='Calendar')?('Task'):('Events'));
            $mail_data['taskpriority'] = $this->get('taskpriority');
            $mail_data['relatedto'] = $parentName;
            $mail_data['contact_name'] = $cont_name;
            $mail_data['description'] = $this->get('description');
            $mail_data['assign_type'] = $this->get('assigntype');
            $mail_data['group_name'] = getGroupName($this->get('assigned_user_id'));
            $mail_data['mode'] = $this->get('mode');
            //TODO : remove dependency on request;
            $value = getaddEventPopupTime($_REQUEST['time_start'],$_REQUEST['time_end'],'24');
            $start_hour = $value['starthour'].':'.$value['startmin'].''.$value['startfmt'];
            if($_REQUEST['activity_mode']!='Task')
                $end_hour = $value['endhour'] .':'.$value['endmin'].''.$value['endfmt'];
            $startDate = new DateTimeField($_REQUEST['date_start']." ".$start_hour);
            $endDate = new DateTimeField($_REQUEST['due_date']." ".$end_hour);
            $mail_data['st_date_time'] = $startDate->getDBInsertDateTimeValue();
            $mail_data['end_date_time'] = $endDate->getDBInsertDateTimeValue();
            $mail_data['location']=$this->get('location');
            return $mail_data;
     }
}
