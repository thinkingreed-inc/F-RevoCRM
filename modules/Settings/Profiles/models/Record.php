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
 * Profiles Record Model Class
 */
class Settings_Profiles_Record_Model extends Settings_Vtiger_Record_Model {

	const PROFILE_FIELD_INACTIVE = 0;
	const PROFILE_FIELD_READONLY = 1;
	const PROFILE_FIELD_READWRITE = 2;
	private static $fieldLockedUiTypes = array('70');

	/**
	 * Function to get the Id
	 * @return <Number> Profile Id
	 */
	public function getId() {
		return $this->get('profileid');
	}
	/**
	 * Function to get the Id
	 * @return <Number> Profile Id
	 */
	protected function setId($id) {
		$this->set('profileid', $id);
		return $this;
	}

	/**
	 * Function to get the Profile Name
	 * @return <String>
	 */
	public function getName() {
		return $this->get('profilename');
	}

	/**
	 * Function to get the description of the Profile
	 * @return <String>
	 */
	public function getDescription() {
		return $this->get('description');
	}

	/**
	 * Function to get the Edit View Url for the Profile
	 * @return <String>
	 */
	public function getEditViewUrl() {
		return '?module=Profiles&parent=Settings&view=Edit&record='.$this->getId();
	}

	/**
	 * Function to get the Edit View Url for the Profile
	 * @return <String>
	 */
	public function getDuplicateViewUrl() {
		return '?module=Profiles&parent=Settings&view=Edit&from_record='.$this->getId();
	}

	/**
	 * Function to get the Detail Action Url for the Profile
	 * @return <String>
	 */
	public function getDeleteAjaxUrl() {
		return '?module=Profiles&parent=Settings&action=DeleteAjax&record='.$this->getId();
	}

	/**
	 * Function to get the Delete Action Url for the current profile
	 * @return <String>
	 */
	public function getDeleteActionUrl() {
		return 'index.php?module=Profiles&parent=Settings&view=DeleteAjax&record='.$this->getId();
	}

	public function getGlobalPermissions() {
		$db = PearDatabase::getInstance();

		if(!$this->global_permissions) {
			$globalPermissions = array();
			$globalPermissions[Settings_Profiles_Module_Model::GLOBAL_ACTION_VIEW] =
				$globalPermissions[Settings_Profiles_Module_Model::GLOBAL_ACTION_EDIT] =
					Settings_Profiles_Module_Model::GLOBAL_ACTION_DEFAULT_VALUE;

			if($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2globalpermissions WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for($i=0; $i<$noOfRows; ++$i) {
					$actionId = $db->query_result($result, $i, 'globalactionid');
					$permissionId = $db->query_result($result, $i, 'globalactionpermission');
					$globalPermissions[$actionId] = $permissionId;
				}
			}
			$this->global_permissions = $globalPermissions;
		}
		return $this->global_permissions;
	}

	public function hasGlobalReadPermission() {
		$globalPermissions = $this->getGlobalPermissions();
		$viewAllPermission = $globalPermissions[Settings_Profiles_Module_Model::GLOBAL_ACTION_VIEW];
		if($viewAllPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasGlobalWritePermission() {
		$globalPermissions = $this->getGlobalPermissions();
		$editAllPermission = $globalPermissions[Settings_Profiles_Module_Model::GLOBAL_ACTION_EDIT];
		if($this->hasGlobalReadPermission() &&
				$editAllPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;

	}

	public function hasModulePermission($module) {
		$moduleModule = $this->getProfileTabModel($module);
		$modulePermissions = $moduleModule->get('permissions');
		$moduleAccessPermission = $modulePermissions['is_permitted'];
		if(isset($modulePermissions['is_permitted']) && $moduleAccessPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasModuleActionPermission($module, $action) {
		$actionId = false;
		if(is_object($action) && is_a($action, 'Vtiger_Action_Model')) {
			$actionId = $action->getId();
		} else {
			$action = Vtiger_Action_Model::getInstance($action);
			$actionId = $action->getId();
		}
		if(!$actionId) {
			return false;
		}

		$moduleModel = $this->getProfileTabModel($module);
		$modulePermissions = $moduleModel->get('permissions');
		$moduleAccessPermission = $modulePermissions['is_permitted'];
		if($moduleAccessPermission != Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return false;
		}
		$moduleActionPermissions = $modulePermissions['actions'];
		$moduleActionPermission = $moduleActionPermissions[$actionId];
		if(isset($moduleActionPermissions[$actionId]) && $moduleActionPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasModuleFieldPermission($module, $field) {
		$fieldModel = $this->getProfileTabFieldModel($module, $field);
		$fieldPermissions = $fieldModel->get('permissions');
		$fieldAccessPermission = $fieldPermissions['visible'];
		if($fieldModel->isViewEnabled() && $fieldAccessPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function hasModuleFieldWritePermission($module, $field) {
		$fieldModel = $this->getProfileTabFieldModel($module, $field);
		$fieldPermissions = $fieldModel->get('permissions');
		$fieldAccessPermission = $fieldPermissions['visible'];
		$fieldReadOnlyPermission = $fieldPermissions['readonly'];
		if($fieldModel->isEditEnabled()
				&& $fieldAccessPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE
				&& $fieldReadOnlyPermission == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
			return true;
		}
		return false;
	}

	public function getModuleFieldPermissionValue($module, $field) {
		if(!$this->hasModuleFieldPermission($module, $field)) {
			return self::PROFILE_FIELD_INACTIVE;
		} elseif($this->hasModuleFieldWritePermission($module, $field)) {
			// for line item fields as well, we should send read write 
			return self::PROFILE_FIELD_READWRITE;
		} else {
			return self::PROFILE_FIELD_READONLY;
		}
	}

	public function isModuleFieldLocked($module, $field) {
		$fieldModel = $this->getProfileTabFieldModel($module, $field);
        if(!$fieldModel->isEditable() || $fieldModel->isMandatory()
				|| in_array($fieldModel->get('uitype'),self::$fieldLockedUiTypes) || $fieldModel->hasCustomLock()) {
			return true;
		}
		return false;
	}

	public function getProfileTabModel($module) {
		$tabId = false;
		if(is_object($module) && is_a($module, 'Vtiger_Module_Model')) {
			$tabId = $module->getId();
		} else {
			$module = Vtiger_Module_Model::getInstance($module);
			$tabId = $module->getId();
		}
		if(!$tabId) {
			return false;
		}
		$allModulePermissions = $this->getModulePermissions();
		$moduleModel = $allModulePermissions[$tabId];
		return $moduleModel;
	}

	public function getProfileTabFieldModel($module, $field) {
		$profileTabModel = $this->getProfileTabModel($module);
		$fieldId = false;
		if(is_object($field) && is_a($field, 'Vtiger_Field_Model')) {
			$fieldId = $field->getId();
		} else {
			$field = Vtiger_Field_Model::getInstance($field, $profileTabModel);
			$fieldId = $field->getId();
		}
		if(!$fieldId) {
			return false;
		}
		$moduleFields = $profileTabModel->getFields();
		$fieldModel = $moduleFields[$field->getName()];
		return $fieldModel;
	}

	public function getProfileTabPermissions() {
		$db = PearDatabase::getInstance();

		if(!$this->profile_tab_permissions) {
			$profile2TabPermissions = array();
			if($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2tab WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for($i=0; $i<$noOfRows; ++$i) {
					$tabId = $db->query_result($result, $i, 'tabid');
					$permissionId = $db->query_result($result, $i, 'permissions');
					$profile2TabPermissions[$tabId] = $permissionId;
				}
			}
			$this->profile_tab_permissions = $profile2TabPermissions;
		}
		return $this->profile_tab_permissions;
	}

	public function getProfileTabFieldPermissions($tabId) {
		$db = PearDatabase::getInstance();

		if(!$this->profile_tab_field_permissions[$tabId]) {
			$profile2TabFieldPermissions = array();
			if($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2field WHERE profileid=? AND tabid=?';
				$params = array($this->getId(), $tabId);
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for($i=0; $i<$noOfRows; ++$i) {
					$fieldId = $db->query_result($result, $i, 'fieldid');
					$visible = $db->query_result($result, $i, 'visible');
					$readOnly = $db->query_result($result, $i, 'readonly');
					$profile2TabFieldPermissions[$fieldId]['visible'] = $visible;
					$profile2TabFieldPermissions[$fieldId]['readonly'] = $readOnly;
				}
			}
			$this->profile_tab_field_permissions[$tabId] = $profile2TabFieldPermissions;
		}
		return $this->profile_tab_field_permissions[$tabId];
	}

	public function getProfileActionPermissions() {
		$db = PearDatabase::getInstance();

		if(!$this->profile_action_permissions) {
			$profile2ActionPermissions = array();
			if($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2standardpermissions WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for($i=0; $i<$noOfRows; ++$i) {
					$tabId = $db->query_result($result, $i, 'tabid');
					$operation = $db->query_result($result, $i, 'operation');
					$permissionId = $db->query_result($result, $i, 'permissions');
					$profile2ActionPermissions[$tabId][$operation] = $permissionId;
				}
			}
			$this->profile_action_permissions = $profile2ActionPermissions;
			}
		return $this->profile_action_permissions;
	}

	public function getProfileUtilityPermissions() {
		$db = PearDatabase::getInstance();

		if(!$this->profile_utility_permissions) {
			$profile2UtilityPermissions = array();
			if($this->getId()) {
				$sql = 'SELECT * FROM vtiger_profile2utility WHERE profileid=?';
				$params = array($this->getId());
				$result = $db->pquery($sql, $params);
				$noOfRows = $db->num_rows($result);
				for($i=0; $i<$noOfRows; ++$i) {
					$tabId = $db->query_result($result, $i, 'tabid');
					$utility = $db->query_result($result, $i, 'activityid');
					$permissionId = $db->query_result($result, $i, 'permission');
					$profile2UtilityPermissions[$tabId][$utility] = $permissionId;
				}
			}
			$this->profile_utility_permissions = $profile2UtilityPermissions;
		}
		return $this->profile_utility_permissions;
	}

	public function getModulePermissions() {
		if(!$this->module_permissions) {
			$allModules = Vtiger_Module_Model::getAll(array(0), Settings_Profiles_Module_Model::getNonVisibleModulesList());
			$eventModule = Vtiger_Module_Model::getInstance('Events');
			$allModules[$eventModule->getId()] = $eventModule;
			$profileTabPermissions = $this->getProfileTabPermissions();
			$profileActionPermissions = $this->getProfileActionPermissions();
			$profileUtilityPermissions = $this->getProfileUtilityPermissions();
			$allTabActions = Vtiger_Action_Model::getAll(true);

			foreach($allModules as $id => $moduleModel) {
				$permissions = array();
				$permissions['is_permitted'] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
				if(isset($profileTabPermissions[$id])) {
					$permissions['is_permitted'] = $profileTabPermissions[$id];
				}
				$permissions['actions'] = array();
				foreach($allTabActions as $actionModel) {
					$actionId = $actionModel->getId();
					if(isset($profileActionPermissions[$id][$actionId])) {
						$permissions['actions'][$actionId] = $profileActionPermissions[$id][$actionId];
					} elseif(isset($profileUtilityPermissions[$id][$actionId])) {
						$permissions['actions'][$actionId] = $profileUtilityPermissions[$id][$actionId];
					} else {
						$permissions['actions'][$actionId] = Settings_Profiles_Module_Model::NOT_PERMITTED_VALUE;
					}
				}
				$moduleFields = $moduleModel->getFields();
				$allFieldPermissions = $this->getProfileTabFieldPermissions($id);
				foreach($moduleFields as $fieldName => $fieldModel) {
					$fieldPermissions = array();
					$fieldId = $fieldModel->getId();
					$fieldPermissions['visible'] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
					if(isset($allFieldPermissions[$fieldId]['visible'])) {
						$fieldPermissions['visible'] = $allFieldPermissions[$fieldId]['visible'];
					}
					$fieldPermissions['readonly'] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
					if(isset($allFieldPermissions[$fieldId]['readonly'])) {
						$fieldPermissions['readonly'] = $allFieldPermissions[$fieldId]['readonly'];
					}
					$fieldModel->set('permissions', $fieldPermissions);
				}
				$moduleModel->set('permissions', $permissions);
			}
			$this->module_permissions = $allModules;
		}
		return $this->module_permissions;
	}

	public function delete($transferToRecord) {
		$db = PearDatabase::getInstance();
		$profileId = $this->getId();
		$transferProfileId = $transferToRecord->getId();

		$db->pquery('DELETE FROM vtiger_profile2globalpermissions WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2tab WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2standardpermissions WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2utility WHERE profileid=?', array($profileId));
		$db->pquery('DELETE FROM vtiger_profile2field WHERE profileid=?', array($profileId));

		$checkSql = 'SELECT roleid, count(profileid) AS profilecount FROM vtiger_role2profile
							WHERE roleid IN (select roleid FROM vtiger_role2profile WHERE profileid=?) GROUP BY roleid';
		$checkParams = array($profileId);
		$checkResult = $db->pquery($checkSql, $checkParams);
		$noOfRoles = $db->num_rows($checkResult);
		for($i=0; $i<$noOfRoles; ++$i) {
			$roleId = $db->query_result($checkResult, $i, 'roleid');
			$profileCount = $db->query_result($checkResult, $i, 'profilecount');
			if($profileCount > 1) {
				$sql = 'DELETE FROM vtiger_role2profile WHERE roleid=? AND profileid=?';
				$params = array($roleId, $profileId);
			} else {
				$sql = 'UPDATE vtiger_role2profile SET profileid=? WHERE roleid=? AND profileid=?';
				$params = array($transferProfileId, $roleId, $profileId);
			}
			$db->pquery($sql, $params);
		}

		$db->pquery('DELETE FROM vtiger_profile WHERE profileid=?', array($profileId));
	}

	public function save() {
		$db = PearDatabase::getInstance();
		$modulePermissions = $this->getModulePermissions();

		$profileName = $this->get('profilename');
		$description = $this->get('description');
		$profilePermissions = $this->get('profile_permissions');
		$calendarModule = Vtiger_Module_Model::getInstance('Calendar');
		$eventModule = Vtiger_Module_Model::getInstance('Events');
		$eventFieldsPermissions = $profilePermissions[$eventModule->getId()]['fields'];
		$profilePermissions[$eventModule->getId()] = $profilePermissions[$calendarModule->getId()];
		$profilePermissions[$eventModule->getId()]['fields'] = $eventFieldsPermissions;

        $isProfileDirectlyRelatedToRole = 0;
		$isNewProfile = false;
        if($this->has('directly_related_to_role')){
            $isProfileDirectlyRelatedToRole = $this->get('directly_related_to_role');
        }
		$profileId = $this->getId();
		if(!$profileId) {
			$profileId = $db->getUniqueId('vtiger_profile');
			$this->setId($profileId);
			$sql = 'INSERT INTO vtiger_profile(profileid, profilename, description, directly_related_to_role) VALUES (?,?,?,?)';
			$params = array($profileId, $profileName, $description, $isProfileDirectlyRelatedToRole);
			$isNewProfile = true;
		} else {
			$sql = 'UPDATE vtiger_profile SET profilename=?, description=?, directly_related_to_role=? WHERE profileid=?';
			$params = array($profileName, $description, $isProfileDirectlyRelatedToRole, $profileId);

			$db->pquery('DELETE FROM vtiger_profile2globalpermissions WHERE profileid=?', array($profileId));
		}
		$db->pquery($sql, $params);

		$sql = 'INSERT INTO vtiger_profile2globalpermissions(profileid, globalactionid, globalactionpermission) VALUES (?,?,?)';
		$params = array($profileId, Settings_Profiles_Module_Model::GLOBAL_ACTION_VIEW, $this->tranformInputPermissionValue($this->get('viewall')));
		$db->pquery($sql, $params);

		$sql = 'INSERT INTO vtiger_profile2globalpermissions(profileid, globalactionid, globalactionpermission) VALUES (?,?,?)';
		$params = array($profileId, Settings_Profiles_Module_Model::GLOBAL_ACTION_EDIT, $this->tranformInputPermissionValue($this->get('editall')));
		$db->pquery($sql, $params);

		$allModuleModules = Vtiger_Module_Model::getAll(array(0), Settings_Profiles_Module_Model::getNonVisibleModulesList());
		$allModuleModules[$eventModule->getId()] = $eventModule;
		if(php7_count($allModuleModules) > 0) {
			$actionModels = Vtiger_Action_Model::getAll(true);
			foreach($allModuleModules as $tabId => $moduleModel) {
				if($moduleModel->isActive()) {
					$this->saveModulePermissions($moduleModel, $profilePermissions[$moduleModel->getId()]);
				} else {
					$permissions = array();
					$permissions['is_permitted'] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
					if($moduleModel->isEntityModule()) {
						$permissions['actions'] = array();
						foreach($actionModels as $actionModel) {
							if($actionModel->isModuleEnabled($moduleModel)) {
								$permissions['actions'][$actionModel->getId()] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
							}
						}
						$permissions['fields'] = array();
						$moduleFields = $moduleModel->getFields();
						foreach($moduleFields as $fieldModel) {
							if($fieldModel->isEditEnabled()) {
								$permissions['fields'][$fieldModel->getId()] = Settings_Profiles_Record_Model::PROFILE_FIELD_READWRITE;
							} elseif ($fieldModel->isViewEnabled()) {
								$permissions['fields'][$fieldModel->getId()] = Settings_Profiles_Record_Model::PROFILE_FIELD_READONLY;
							} else {
								$permissions['fields'][$fieldModel->getId()] = Settings_Profiles_Record_Model::PROFILE_FIELD_INACTIVE;
							}
						}
					}
					$this->saveModulePermissions($moduleModel, $permissions);
				}
			}
		}
		if($isNewProfile){
			$this->saveUserAccessbleFieldsIntoProfile2Field();
		}
		
        $this->recalculate();
        return $profileId;
	}

	protected function saveModulePermissions($moduleModel, $permissions) {
		$db = PearDatabase::getInstance();
		$profileId = $this->getId();
		$tabId = $moduleModel->getId();
		$profileActionPermissions = $this->getProfileActionPermissions();
		$profileActionPermissions = $profileActionPermissions[$tabId];

		$db->pquery('DELETE FROM vtiger_profile2tab WHERE profileid=? AND tabid=?', array($profileId, $tabId));

		$actionPermissions = array();
		$actionPermissions = $permissions['actions'];
		$actionEnabled = false;
		if($moduleModel->isEntityModule() || $moduleModel->isUtilityActionEnabled()) {
			if($actionPermissions || $moduleModel->isUtilityActionEnabled()) {
				$actionIdsList = Vtiger_Action_Model::$standardActions;
				unset($actionIdsList[3]);
				$availableActionIds = array_keys($actionIdsList);

				foreach ($availableActionIds as $actionId) {
					if ($actionId === 0) {
						//Save action permissions = Permissions of Create/Editview action
						$actionPermissions[$actionId] = $actionPermissions[1] || $actionPermissions[7];
					} else {
						$actionPermissions[$actionId] = $actionPermissions[$actionId];
					}
				}
                
				//Dividing on actions
				$actionsIdsList = $utilityIdsList = array();
				foreach($actionPermissions as $actionId => $permission) {
					if(isset(Vtiger_Action_Model::$standardActions[$actionId])) {
                        if($moduleModel->isUtilityActionEnabled() && !isset($permission)) {
                            $actionsIdsList[$actionId] = 'on'; // permission for non entity module should be true
                        } else {
                            $actionsIdsList[$actionId] = $permission;
                        }
					} else {
						$utilityIdsList[$actionId] = $permission;
					}
				}
                
                $utilityActions = $moduleModel->getUtilityActions();
                foreach ($utilityActions as $utilityActionId => $utilityActionName) {
                    if(!isset($utilityIdsList[$utilityActionId])) {
                        $utilityIdsList[$utilityActionId] = 'off';
                    }
                }
                
				//Update process
				if ($profileActionPermissions) {
					//Standard permissions
					$actionsUpdateQuery = 'UPDATE vtiger_profile2standardpermissions SET permissions = CASE ';
					foreach ($actionsIdsList as $actionId => $permission) {
						$permissionValue = $this->tranformInputPermissionValue($permission);
						if(isset(Vtiger_Action_Model::$standardActions[$actionId])) {
							if($permissionValue == Settings_Profiles_Module_Model::IS_PERMITTED_VALUE) {
								$actionEnabled = true;
							}
							$actionsUpdateQuery .= " WHEN operation = $actionId THEN $permissionValue ";
						}
					}
					$actionsUpdateQuery .= 'ELSE permissions END WHERE profileid = ? AND tabid = ?';
					if ($actionsIdsList) {
						$db->pquery($actionsUpdateQuery, array($profileId, $tabId));
					}
                    
					//Utility permissions
					$utilityUpdateQuery = 'UPDATE vtiger_profile2utility SET permission = CASE ';
					foreach($utilityIdsList as $actionId => $permission) {
						$permissionValue = $this->tranformInputPermissionValue($permission);
						$utilityUpdateQuery .= " WHEN activityid = $actionId THEN $permissionValue ";
					}
					if ($utilityIdsList) {
						$utilityUpdateQuery .= 'ELSE ? END WHERE profileid = ? AND tabid = ?';
						$db->pquery($utilityUpdateQuery, array(1, $profileId, $tabId));
					}
				} else {
					//Insert Process
					//Standard permissions
					$i = 0;
					$count = php7_count($actionsIdsList);
                    $actionParams = array();
					$actionsInsertQuery = 'INSERT INTO vtiger_profile2standardpermissions(profileid, tabid, operation, permissions) VALUES ';
					foreach ($actionsIdsList as $actionId => $permission) {
						$actionEnabled = true;
						$permissionValue = $this->tranformInputPermissionValue($permission);
						$actionsInsertQuery .= '(?, ?, ?, ?)';
                        array_push($actionParams, $profileId, $tabId, $actionId, $permissionValue);

						if ($i !== $count-1) {
							$actionsInsertQuery .= ', ';
						}
						$i++;
					}
					if ($actionsIdsList) {
						$db->pquery($actionsInsertQuery, $actionParams);
					}

					//Utility permissions
					$i = 0;
					$count = php7_count($utilityIdsList);
                    $utilityParams = array();
					$utilityInsertQuery = 'INSERT INTO vtiger_profile2utility(profileid, tabid, activityid, permission) VALUES ';
					foreach($utilityIdsList as $actionId => $permission) {
						$permissionValue = $this->tranformInputPermissionValue($permission);
						$utilityInsertQuery .= '(?, ?, ?, ?)';
                        array_push($utilityParams, $profileId, $tabId, $actionId, $permissionValue);

						if ($i !== $count-1) {
							$utilityInsertQuery .= ', ';
						}
						$i++;
					}
					if ($utilityIdsList) {
						$db->pquery($utilityInsertQuery, $utilityParams);
					}
				}
			}
		} else {
			$actionEnabled = true;
		}

		// Enable module permission in profile2tab table only if either its an extension module or the entity module has atleast 1 action enabled
		if($actionEnabled) {
			$isModulePermitted = $this->tranformInputPermissionValue($permissions['is_permitted']);
		} else {
			$isModulePermitted = Settings_Profiles_Module_Model::NOT_PERMITTED_VALUE;
		}
		$sql = 'INSERT INTO vtiger_profile2tab(profileid, tabid, permissions) VALUES (?,?,?)';
		$params = array($profileId, $tabId, $isModulePermitted);
		$db->pquery($sql, $params);

		$fieldPermissions = $permissions['fields'];
		if(is_array($fieldPermissions)) {
			foreach($fieldPermissions as $fieldId => $stateValue) {
				$db->pquery('DELETE FROM vtiger_profile2field WHERE profileid=? AND tabid=? AND fieldid=?',
								array($profileId, $tabId, $fieldId));
				if($stateValue == Settings_Profiles_Record_Model::PROFILE_FIELD_INACTIVE) {
					$visible = Settings_Profiles_Module_Model::FIELD_INACTIVE;
					$readOnly = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
				} elseif($stateValue == Settings_Profiles_Record_Model::PROFILE_FIELD_READONLY) {
					$visible = Settings_Profiles_Module_Model::FIELD_ACTIVE;
					$readOnly = Settings_Profiles_Module_Model::FIELD_READONLY;
				} else {
					$visible = Settings_Profiles_Module_Model::FIELD_ACTIVE;
					$readOnly = Settings_Profiles_Module_Model::FIELD_READWRITE;
				}
				$sql = 'INSERT INTO vtiger_profile2field(profileid, tabid, fieldid, visible, readonly) VALUES (?,?,?,?,?)';
				$params = array($profileId, $tabId, $fieldId, $visible, $readOnly);
				$db->pquery($sql, $params);
			}
		}
	}

	protected function tranformInputPermissionValue($value) {
		if($value == 'on' || $value == '1') {
			return Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
		} else {
			return Settings_Profiles_Module_Model::NOT_PERMITTED_VALUE;
		}
	}

	/**
	 * Function to get the list view actions for the record
	 * @return <Array> - Associate array of Vtiger_Link_Model instances
	 */
	public function getRecordLinks() {

		$links = array();

		$recordLinks = array(
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => $this->getEditViewUrl(),
				'linkicon' => 'icon-pencil'
			),
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DUPLICATE_RECORD',
				'linkurl' => $this->getDuplicateViewUrl(),
				'linkicon' => 'icon-share'
			),
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => "javascript:Settings_Vtiger_List_Js.triggerDelete(event,'".$this->getDeleteActionUrl()."')",
				'linkicon' => 'icon-trash'
			)
		);
		foreach($recordLinks as $recordLink) {
			$links[] = Vtiger_Link_Model::getInstanceFromValues($recordLink);
		}

		return $links;
	}

	public static function getInstanceFromQResult($result, $rowNo=0) {
		$db = PearDatabase::getInstance();
		$row = $db->query_result_rowdata($result, $rowNo);
		$profile = new self();
		return $profile->setData($row);
	}

	/**
	 * Function to get all the profiles linked to the given role
	 * @param <String> - $roleId
	 * @return <Array> - Array of Settings_Profiles_Record_Model instances
	 */
	public static function getAllByRole($roleId) {
		$db = PearDatabase::getInstance();

		$sql = 'SELECT vtiger_profile.*
					FROM vtiger_profile
					INNER JOIN
						vtiger_role2profile ON vtiger_profile.profileid = vtiger_role2profile.profileid
						AND
						vtiger_role2profile.roleid = ?';
		$params = array($roleId);
		$result = $db->pquery($sql, $params);
		$noOfProfiles = $db->num_rows($result);
		$profiles = array();
		for ($i=0; $i<$noOfProfiles; ++$i) {
			$profile = self::getInstanceFromQResult($result, $i);
			$profiles[$profile->getId()] = $profile;
		}
		return $profiles;
	}

	/**
	 * Function to get all the profiles
	 * @return <Array> - Array of Settings_Profiles_Record_Model instances
	 */
	public static function getAll() {
		$db = PearDatabase::getInstance();

		$sql = 'SELECT * FROM vtiger_profile';
		$params = array();
		$result = $db->pquery($sql, $params);
		$noOfProfiles = $db->num_rows($result);
		$profiles = array();
		for ($i=0; $i<$noOfProfiles; ++$i) {
			$profile = self::getInstanceFromQResult($result, $i);
			$profiles[$profile->getId()] = $profile;
		}
		return $profiles;
	}

	/**
	 * Function to get the instance of Profile model, given profile id
	 * @param <Integer> $profileId
	 * @return Settings_Profiles_Record_Model instance, if exists. Null otherwise
	 */
	public static function getInstanceById($profileId) {
		$db = PearDatabase::getInstance();

		$sql = 'SELECT * FROM vtiger_profile WHERE profileid = ?';
		$params = array($profileId);
		$result = $db->pquery($sql, $params);
		if($db->num_rows($result) > 0) {
			return self::getInstanceFromQResult($result);
		}
		return null;
	}

    public static function getInstanceByName($profileName , $checkOnlyDirectlyRelated=false, $excludedRecordId = array()) {
        $db = PearDatabase::getInstance();
        $query = 'SELECT * FROM vtiger_profile WHERE profilename=?';
        $params = array($profileName);
        if($checkOnlyDirectlyRelated) {
            $query .=' AND directly_related_to_role=1';
        }
		if(!empty($excludedRecordId)) {
           $query .= ' AND profileid NOT IN ('.generateQuestionMarks($excludedRecordId).')';
           $params = array_merge($params,$excludedRecordId);
       }
	   
        $result = $db->pquery($query, $params);
        if($db->num_rows($result)> 0 ){
            return self::getInstanceFromQResult($result);
        }
        return null;
    }

	/**
	 * Function to get the Detail Url for the current group
	 * @return <String>
	 */
    public function getDetailViewUrl() {
        return '?module=Profiles&parent=Settings&view=Detail&record=' . $this->getId();
    }

	/**
	 * Function to check whether the profiles is directly related to role
	 * @return Boolean
	 */
    public function isDirectlyRelated() {
		$isDirectlyRelated = $this->get('directly_related_to_role');
		if($isDirectlyRelated == 1){
			return true;
		} else {
			return false;
		}
    }

	/**
	 * Function recalculate the sharing rules
	 */
	public function recalculate() {
		set_time_limit(vglobal('php_max_execution_time'));
		require_once('modules/Users/CreateUserPrivilegeFile.php');

		$userIdsList = $this->getUsersList();
		if ($userIdsList) {
			foreach ($userIdsList as $userId) {
				createUserPrivilegesfile($userId);
			}
		}
	}

	/**
	 * Function to get Users list from this Profile
	 * @param <Boolean> $allUsers
	 * @return <Array> list of user ids
	 */
	public function getUsersList($allUsers = false) {
		$db = PearDatabase::getInstance();
		$params = array(0);
		$query = 'SELECT id FROM vtiger_users
					INNER JOIN vtiger_user2role ON vtiger_user2role.userid = vtiger_users.id
					INNER JOIN vtiger_role2profile ON vtiger_role2profile.roleid = vtiger_user2role.roleid
					WHERE vtiger_users.deleted = ?';

		if (!$allUsers) {
			$query .= ' AND vtiger_role2profile.profileid = ?';
			$params[] = $this->getId();
		}
		$result = $db->pquery($query, $params);
		$numOfRows = $db->num_rows($result);

		$userIdsList = array();
		for($i=0; $i<$numOfRows; $i++) {
			$userIdsList[] = $db->query_result($result, $i, 'id');
		}
		return $userIdsList;
	}
	
	/**
	 * Function to save user fields in vtiger_profile2field table
	 * We need user field values to generating the Email Templates variable valuues.
	 * @param type $profileId
	 */
	public function saveUserAccessbleFieldsIntoProfile2Field(){
		$profileId = $this->getId();
		if(!empty($profileId)){
			$db = PearDatabase::getInstance();
			$userRecordModel = Users_Record_Model::getCurrentUserModel();
			$module = $userRecordModel->getModuleName();
			$tabId = getTabid($module);
			$userModuleModel = Users_Module_Model::getInstance($module);
			$moduleFields = $userModuleModel->getFields();

			$userAccessbleFields = array();
			$skipFields = array(115,116,31,32);
			$allowedFields = array("roleid","currency_id","reports_to_id");
			foreach ($moduleFields as $fieldName => $fieldModel) {
				if(in_array($fieldName,$allowedFields) || $fieldModel->getFieldDataType() == 'string' || $fieldModel->getFieldDataType() == 'email' || $fieldModel->getFieldDataType() == 'phone') {
					if(!in_array($fieldModel->get('uitype'), $skipFields) && $fieldName != 'asterisk_extension'){
						$userAccessbleFields[$fieldModel->get('id')] .= $fieldName;
					}
				}
			}
            
			//Added user fields into vtiger_profile2field and vtiger_def_org_field
			//We are using this field information in Email Templates.
			foreach ($userAccessbleFields as $fieldId => $fieldName) {
				$insertQuery = 'INSERT INTO vtiger_profile2field VALUES(?,?,?,?,?)';
				$db->pquery($insertQuery, array($profileId, $tabId, $fieldId,  Settings_Profiles_Module_Model::FIELD_ACTIVE, Settings_Profiles_Module_Model::FIELD_READWRITE));
			}
			
			$sql = 'SELECT fieldid FROM vtiger_def_org_field WHERE tabid = ?';
			$result1 = $db->pquery($sql, array($tabId));
			$def_org_fields = array();
			for($j=0; $j<$db->num_rows($result1); $j++) {
				array_push($def_org_fields, $db->query_result($result1, $j, 'fieldid'));
			}
			foreach ($userAccessbleFields as $fieldId => $fieldName) {
				if(!in_array($fieldId, $def_org_fields)){
					$insertQuery = 'INSERT INTO vtiger_def_org_field VALUES(?,?,?,?)';
					$db->pquery($insertQuery, array($tabId,$fieldId,0,0));
				}
			}
		}
	}
}