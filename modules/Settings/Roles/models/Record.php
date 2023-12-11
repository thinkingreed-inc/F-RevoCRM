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
 * Roles Record Model Class
 */
class Settings_Roles_Record_Model extends Settings_Vtiger_Record_Model {

	/**
	 * Function to get the Id
	 * @return <Number> Role Id
	 */
	public function getId() {
		return $this->get('roleid');
	}

	/**
	 * Function to get the Role Name
	 * @return <String>
	 */
	public function getName() {
		return $this->get('rolename');
	}

	/**
	 * Function to get the depth of the role
	 * @return <Number>
	 */
	public function getDepth() {
		return $this->get('depth');
	}

	/**
	 * Function to get Parent Role hierarchy as a string
	 * @return <String>
	 */
	public function getParentRoleString() {
		return $this->get('parentrole');
	}

	/**
	 * Function to set the immediate parent role
	 * @return <Settings_Roles_Record_Model> instance
	 */
	public function setParent($parentRole) {
		$this->parent = $parentRole;
		return $this;
	}

	/**
	 * Function to get the immediate parent role
	 * @return <Settings_Roles_Record_Model> instance
	 */
	public function getParent() {
		if(!$this->parent) {
			$parentRoleString = $this->getParentRoleString();
			$parentComponents = explode('::', $parentRoleString);
			$noOfRoles = php7_count($parentComponents);
			// $currentRole = $parentComponents[$noOfRoles-1];
			if($noOfRoles > 1) {
				$this->parent = self::getInstanceById($parentComponents[$noOfRoles-2]);
			} else {
				$this->parent = null;
			}
		}
		return $this->parent;
	}

	/**
	 * Function to get the immediate children roles
	 * @return <Array> - List of Settings_Roles_Record_Model instances
	 */
	public function getChildren() {
		$db = PearDatabase::getInstance();
		if(!$this->children) {
			$parentRoleString = $this->getParentRoleString();
			$currentRoleDepth = $this->getDepth();

			$sql = 'SELECT * FROM vtiger_role WHERE parentrole LIKE ? AND depth = ?';
			$params = array($parentRoleString.'::%', $currentRoleDepth+1);
			$result = $db->pquery($sql, $params);
			$noOfRoles = $db->num_rows($result);
			$roles = array();
			for ($i=0; $i<$noOfRoles; ++$i) {
				$role = self::getInstanceFromQResult($result, $i);
				$roles[$role->getId()] = $role;
			}
			$this->children = $roles;
		}
		return $this->children;
	}
	
	public function getSameLevelRoles() {
		$db = PearDatabase::getInstance();
		if(!$this->children) {
			$parentRoles = getParentRole($this->getId());
			$currentRoleDepth = $this->getDepth();
			$parentRoleString = '';
			foreach ($parentRoles as $key => $role) {
				if(empty($parentRoleString)) $parentRoleString = $role;
				else $parentRoleString = $parentRoleString.'::'.$role;
			}
			$sql = 'SELECT * FROM vtiger_role WHERE parentrole LIKE ? AND depth = ?';
			$params = array($parentRoleString.'::%', $currentRoleDepth);
			$result = $db->pquery($sql, $params);
			$noOfRoles = $db->num_rows($result);
			$roles = array();
			for ($i=0; $i<$noOfRoles; ++$i) {
				$role = self::getInstanceFromQResult($result, $i);
				$roles[$role->getId()] = $role;
			}
			$this->children = $roles;
		}
		return $this->children;
	}

	/**
	 * Function to get all the children roles
	 * @return <Array> - List of Settings_Roles_Record_Model instances
	 */
	public function getAllChildren() {
		$db = PearDatabase::getInstance();

		$parentRoleString = $this->getParentRoleString();

		$sql = 'SELECT * FROM vtiger_role WHERE parentrole LIKE ? ORDER BY depth';
		$params = array($parentRoleString.'::%');
		$result = $db->pquery($sql, $params);
		$noOfRoles = $db->num_rows($result);
		$roles = array();
		for ($i=0; $i<$noOfRoles; ++$i) {
			$role = self::getInstanceFromQResult($result, $i);
			$roles[$role->getId()] = $role;
		}
		return $roles;
	}
    
	/**
	 * Function returns profiles related to the current role
	 * @return <Array> - profile ids
	 */
    public function getProfileIdList(){
        
        $db = PearDatabase::getInstance();
        $query = 'SELECT profileid FROM vtiger_role2profile WHERE roleid=?';
        
        $result = $db->pquery($query,array($this->getId()));
        $num_rows = $db->num_rows($result);
        
        $profilesList = array();
        for($i=0; $i<$num_rows; $i++) {
            $profilesList[] = $db->query_result($result,$i,'profileid');
        }
        return $profilesList;
    }
    
    /**
     * Function to get the profile id if profile is directly related to role
     * @return id
     */
    public function getDirectlyRelatedProfileId() {
        //TODO : see if you need cache the result
        $roleId = $this->getId();
        if(empty($roleId)) {
            return false;
        }
        
        $db = PearDatabase::getInstance();
        
        $query = 'SELECT directly_related_to_role, vtiger_profile.profileid FROM vtiger_role2profile 
                  INNER JOIN vtiger_profile ON vtiger_profile.profileid = vtiger_role2profile.profileid 
                  WHERE vtiger_role2profile.roleid=?';
        $params = array($this->getId());
        
        $result = $db->pquery($query,$params);
        
		if($db->num_rows($result) == 1 && $db->query_result($result,0,'directly_related_to_role') == '1'){
           return $db->query_result($result, 0, 'profileid');
        }
        return false;
    }

	/**
	 * Function to get the Edit View Url for the Role
	 * @return <String>
	 */
	public function getEditViewUrl() {
		return 'index.php?module=Roles&parent=Settings&view=Edit&record='.$this->getId();
	}

//	public function getListViewEditUrl() {
//		return '?module=Roles&parent=Settings&view=Edit&record='.$this->getId();
//	}

	/**
	 * Function to get the Create Child Role Url for the current role
	 * @return <String>
	 */
	public function getCreateChildUrl() {
		return '?module=Roles&parent=Settings&view=Edit&parent_roleid='.$this->getId();
	}

	/**
	 * Function to get the Delete Action Url for the current role
	 * @return <String>
	 */
	public function getDeleteActionUrl() {
		return '?module=Roles&parent=Settings&view=DeleteAjax&record='.$this->getId();
	}

	/**
	 * Function to get the Popup Window Url for the current role
	 * @return <String>
	 */
	public function getPopupWindowUrl() {
		return 'module=Roles&parent=Settings&view=Popup&src_record='.$this->getId();
	}

	/**
	 * Function to get all the profiles associated with the current role
	 * @return <Array> Settings_Profiles_Record_Model instances
	 */
	public function getProfiles() {
		if(!$this->profiles) {
			$this->profiles = Settings_Profiles_Record_Model::getAllByRole($this->getId());
		}
		return $this->profiles;
	}

	/**
	 * Function to add a child role to the current role
	 * @param <Settings_Roles_Record_Model> $role
	 * @return Settings_Roles_Record_Model instance
	 */
	public function addChildRole($role) {
		$role->setParent($this);
		$role->save();
		return $role;
	}

	/**
	 * Function to move the current role and all its children nodes to the new parent role
	 * @param <Settings_Roles_Record_Model> $newParentRole
	 */
	public function moveTo($newParentRole) {
		$currentDepth = $this->getDepth();
		$currentParentRoleString = $this->getParentRoleString();

		$newDepth = $newParentRole->getDepth() + 1;
		$newParentRoleString = $newParentRole->getParentRoleString() .'::'. $this->getId();

		$depthDifference = $newDepth - $currentDepth;
		$allChildren = $this->getAllChildren();

		$this->set('depth', $newDepth);
		$this->set('parentrole', $newParentRoleString);
		$this->set('allowassignedrecordsto', $this->get('allowassignedrecordsto'));
		$this->save();

		foreach($allChildren as $roleId => $roleModel) {
			$oldChildDepth = $roleModel->getDepth();
			$newChildDepth = $oldChildDepth + $depthDifference;

			$oldChildParentRoleString = $roleModel->getParentRoleString();
			$newChildParentRoleString = str_replace($currentParentRoleString, $newParentRoleString, $oldChildParentRoleString);

			$roleModel->set('depth', $newChildDepth);
			$roleModel->set('parentrole', $newChildParentRoleString);
			$roleModel->set('allowassignedrecordsto', $roleModel->get('allowassignedrecordsto'));
			$roleModel->save();
		}
	}

	/**
	 * Function to save the role
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$roleId = $this->getId();
		$mode = 'edit';

		if(empty($roleId)) {
			$mode = '';
			$roleIdNumber = $db->getUniqueId('vtiger_role');
			$roleId = 'H'.$roleIdNumber;
		}
		$parentRole = $this->getParent();
		if($parentRole != null) {
			$this->set('depth', $parentRole->getDepth()+1);
			$this->set('parentrole', $parentRole->getParentRoleString() .'::'. $roleId);
		}

		if($mode == 'edit') {
			$sql = 'UPDATE vtiger_role SET rolename=?, parentrole=?, depth=?, allowassignedrecordsto=? WHERE roleid=?';
			$params = array($this->getName(), $this->getParentRoleString(), $this->getDepth(), $this->get('allowassignedrecordsto'), $roleId);
			$db->pquery($sql, $params);
		} else {
			$sql = 'INSERT INTO vtiger_role(roleid, rolename, parentrole, depth, allowassignedrecordsto) VALUES (?,?,?,?,?)';
			$params = array($roleId, $this->getName(), $this->getParentRoleString(), $this->getDepth(), $this->get('allowassignedrecordsto'));
			$db->pquery($sql, $params);
			$picklist2RoleSQL = "INSERT INTO vtiger_role2picklist SELECT '".$roleId."',picklistvalueid,picklistid,sortid
					FROM vtiger_role2picklist WHERE roleid = ?";
			$db->pquery($picklist2RoleSQL, array($parentRole->getId()));
		}

		$profileIds = $this->get('profileIds');
		if(empty($profileIds)) {
			$profiles = $this->getProfiles();
			if(!empty($profiles) && php7_count($profiles) > 0) {
				$profileIds = array_keys($profiles);
			}
		}
		if(!empty($profileIds)) {
			$noOfProfiles = php7_count($profileIds);
			if($noOfProfiles > 0) {
				$db->pquery('DELETE FROM vtiger_role2profile WHERE roleid=?', array($roleId));

				$sql = 'INSERT INTO vtiger_role2profile(roleid, profileid) VALUES (?,?)';
				for($i=0; $i<$noOfProfiles; ++$i) {
					$params = array($roleId, $profileIds[$i]);
					$db->pquery($sql, $params);
				}
			}
		}
	}

	/**
	 * Function to delete the role
	 * @param <Settings_Roles_Record_Model> $transferToRole
	 */
	public function delete($transferToRole) {
		require_once('modules/Users/CreateUserPrivilegeFile.php');
		$db = PearDatabase::getInstance();
		$roleId = $this->getId();
		$transferRoleId = $transferToRole->getId();

		// get all the users tp recreate user_privileges files
		$usersResult = $db->pquery('SELECT userid FROM vtiger_user2role WHERE roleid = ?', array($roleId));
		$usersCount = $db->num_rows($usersResult);
		$users = array();
		for($i=0; $i<$usersCount; $i++) {
			$users[] = $db->query_result($usersResult, $i, 'userid');
		}
		
		$db->pquery('UPDATE vtiger_user2role SET roleid=? WHERE roleid=?', array($transferRoleId, $roleId));

		$db->pquery('DELETE FROM vtiger_role2profile WHERE roleid=?', array($roleId));
		$db->pquery('DELETE FROM vtiger_group2role WHERE roleid=?', array($roleId));
		$db->pquery('DELETE FROM vtiger_group2rs WHERE roleandsubid=?', array($roleId));

		//delete handling for sharing rules
		deleteRoleRelatedSharingRules($roleId);

		$db->pquery('DELETE FROM vtiger_role WHERE roleid=?', array($roleId));

		$allChildren = $this->getAllChildren();
		$transferParentRoleSequence = $transferToRole->getParentRoleString();
		$currentParentRoleSequence = $this->getParentRoleString();

		foreach($allChildren as $roleId => $roleModel) {
			$oldChildParentRoleString = $roleModel->getParentRoleString();
			$newChildParentRoleString = str_replace($currentParentRoleSequence, $transferParentRoleSequence, $oldChildParentRoleString);
			$newChildDepth = php7_count(explode('::', $newChildParentRoleString))-1;
			$roleModel->set('depth', $newChildDepth);
			$roleModel->set('parentrole', $newChildParentRoleString);
			$roleModel->save();
		}

		foreach($users as $userId) {
			createUserPrivilegesfile($userId);
			createUserSharingPrivilegesfile($userId);
		}
	}

	/**
	 * Function to get the list view actions for the record
	 * @return <Array> - Associate array of Vtiger_Link_Model instances
	 */
	public function getRecordLinks() {

		$links = array();
		if($this->getParent()) {
			$recordLinks = array(
				array(
					'linktype' => 'LISTVIEWRECORD',
					'linklabel' => 'LBL_EDIT_RECORD',
					'linkurl' => $this->getListViewEditUrl(),
					'linkicon' => 'icon-pencil'
				),
				array(
					'linktype' => 'LISTVIEWRECORD',
					'linklabel' => 'LBL_DELETE_RECORD',
					'linkurl' => $this->getDeleteActionUrl(),
					'linkicon' => 'icon-trash'
				)
			);
			foreach($recordLinks as $recordLink) {
				$links[] = Vtiger_Link_Model::getInstanceFromValues($recordLink);
			}
		}

		return $links;
	}

	/**
	 * Function to get the instance of Roles record model from query result
	 * @param <Object> $result
	 * @param <Number> $rowNo
	 * @return Settings_Roles_Record_Model instance
	 */
	public static function getInstanceFromQResult($result, $rowNo) {
		$db = PearDatabase::getInstance();
		$row = $db->query_result_rowdata($result, $rowNo);
		$role = new self();
		return $role->setData($row);
	}

	/**
	 * Function to get all the roles
	 * @param <Boolean> $baseRole
	 * @return <Array> list of Role models <Settings_Roles_Record_Model>
	 */
	public static function getAll($baseRole = false) {
		$db = PearDatabase::getInstance();
		$params = array();

		$sql = 'SELECT * FROM vtiger_role';
		if (!$baseRole) {
			$sql .= ' WHERE depth != ?';
			$params[] = 0;
		}
		$sql .= ' ORDER BY parentrole';

		$result = $db->pquery($sql, $params);
		$noOfRoles = $db->num_rows($result);

		$roles = array();
		for ($i=0; $i<$noOfRoles; ++$i) {
			$role = self::getInstanceFromQResult($result, $i);
			$roles[$role->getId()] = $role;
		}
		return $roles;
	}

	/**
	 * Function to get the instance of Role model, given role id
	 * @param <Integer> $roleId
	 * @return Settings_Roles_Record_Model instance, if exists. Null otherwise
	 */
	public static function getInstanceById($roleId) {
		$db = PearDatabase::getInstance();
        
        $instance = Vtiger_Cache::get('roleById',$roleId);
        if($instance){
            return $instance;
        }
        
        $sql = 'SELECT * FROM vtiger_role WHERE roleid = ?';
        $params = array($roleId);
        $result = $db->pquery($sql, $params);
        if($db->num_rows($result) > 0) {
            $instance =  self::getInstanceFromQResult($result, 0);
            Vtiger_Cache::set('roleById',$roleId,$instance);
            return $instance;
        }
		return null;
	}

	/**
	 * Function to get the instance of Base Role model
	 * @return Settings_Roles_Record_Model instance, if exists. Null otherwise
	 */
	public static function getBaseRole() {
		$db = PearDatabase::getInstance();

		$sql = 'SELECT * FROM vtiger_role WHERE depth=0 LIMIT 1';
		$params = array();
		$result = $db->pquery($sql, $params);
		if($db->num_rows($result) > 0) {
			return self::getInstanceFromQResult($result, 0);
		}
		return null;
	}
	
	/* Function to get the instance of the role by Name
    * @param type $name -- name of the role
    * @return null/role instance
    */
   public static function getInstanceByName($name, $excludedRecordId = array()) {
       $db = PearDatabase::getInstance();
       $sql = 'SELECT * FROM vtiger_role WHERE rolename=?';
       $params = array($name);
       if(!empty($excludedRecordId)){
           $sql.= ' AND roleid NOT IN ('.generateQuestionMarks($excludedRecordId).')';
           $params = array_merge($params,$excludedRecordId);
       }
       $result = $db->pquery($sql, $params);
       if($db->num_rows($result) > 0) {
		   return self::getInstanceFromQResult($result, 0);
	   }
	   return null;
   }

   /**
    * Function to get Users who are from this role
    * @return <Array> User record models list <Users_Record_Model>
    */
   public function getUsers() {
	   $db = PearDatabase::getInstance();
	   $result = $db->pquery('SELECT userid FROM vtiger_user2role WHERE roleid = ?', array($this->getId()));
	   $numOfRows = $db->num_rows($result);

	   $usersList = array();
	   for($i=0; $i<$numOfRows; $i++) {
		   $userId = $db->query_result($result, $i, 'userid');
		   $usersList[$userId] = Users_Record_Model::getInstanceById($userId, 'Users');
	   }
	   return $usersList;
   }

	/**
	* Function to get Users who are from this role
	* @return <Array> User record models list <Users_Record_Model>
	*/
	public function getUserNameList() {
		global $adb;
		$result = $adb->pquery("SELECT distinct u2r.userid, u.last_name, u.first_name FROM vtiger_user2role u2r inner join vtiger_users u on u.id = u2r.userid WHERE u.status = 'Active' and u.calendarsharedtype='public' and u2r.roleid = ?", array($this->getId()));
		$numOfRows = $adb->num_rows($result);
 
		$usersList = array();
		for($i=0; $i<$numOfRows; $i++) {
			$userId = $adb->query_result($result, $i, 'userid');
			$usersList[$userId] = $adb->query_result($result, $i, 'last_name').' '.$adb->query_result($result, $i, 'first_name');
		}
		return $usersList;
	}
}