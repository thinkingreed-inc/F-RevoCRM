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
 * Sharng Access Vtiger Module Model Class
 */
class Settings_SharingAccess_RuleMember_Model extends Vtiger_Base_Model {

	const RULE_MEMBER_TYPE_GROUPS = 'Groups';
	const RULE_MEMBER_TYPE_ROLES = 'Roles';
	const RULE_MEMBER_TYPE_ROLE_AND_SUBORDINATES = 'RoleAndSubordinates';

	/**
	 * Function to get the Qualified Id of the Group RuleMember
	 * @return <Number> Id
	 */
	public function getId() {
		return $this->get('id');
	}

	public function getIdComponents() {
		return explode(':', $$this->getId());
	}

	public function getType() {
		$idComponents = $this->getIdComponents();
		if($idComponents && php7_count($idComponents) > 0) {
			return $idComponents[0];
		}
		return false;
	}

	public function getMemberId() {
		$idComponents = $this->getIdComponents();
		if($idComponents && php7_count($idComponents) > 1) {
			return $idComponents[1];
		}
		return false;
	}

	/**
	 * Function to get the Group Name
	 * @return <String>
	 */
	public function getName() {
		return $this->get('name');
	}

	/**
	 * Function to get the Group Name
	 * @return <String>
	 */
	public function getQualifiedName() {
		return self::$ruleTypeLabel[$this->getType()].' - '.$this->get('name');
	}

	public static function getIdComponentsFromQualifiedId($id) {
		return explode(':', $id);
	}

	public static function getQualifiedId($type, $id) {
		return $type.':'.$id;
	}

	public static function getInstance($qualifiedId) {
		$db = PearDatabase::getInstance();

		$idComponents = self::getIdComponentsFromQualifiedId($qualifiedId);
		$type = $idComponents[0];
		$memberId = $idComponents[1];

		if($type == self::RULE_MEMBER_TYPE_GROUPS) {
			$sql = 'SELECT * FROM vtiger_groups WHERE groupid = ?';
			$params = array($memberId);
			$result = $db->pquery($sql, $params);

			if($db->num_rows($result)) {
				$row = $db->query_result_rowdata($result, 0);
				$qualifiedId = self::getQualifiedId(self::RULE_MEMBER_TYPE_GROUPS, $row['groupid']);
				$name = $row['groupname'];
				$rule = new self();
				return $rule->set('id', $qualifiedId)->set('name', $name);
			}
		}

		if($type == self::RULE_MEMBER_TYPE_ROLES) {
			$sql = 'SELECT * FROM vtiger_role WHERE roleid = ?';
			$params = array($memberId);
			$result = $db->pquery($sql, $params);

			if($db->num_rows($result)) {
				$row = $db->query_result_rowdata($result, 0);
				$qualifiedId = self::getQualifiedId(self::RULE_MEMBER_TYPE_ROLES, $row['roleid']);
				$name = $row['rolename'];
				$rule = new self();
				return $rule->set('id', $qualifiedId)->set('name', $name);
			}
		}

		if($type == self::RULE_MEMBER_TYPE_ROLE_AND_SUBORDINATES) {
			$sql = 'SELECT * FROM vtiger_role WHERE roleid = ?';
			$params = array($memberId);
			$result = $db->pquery($sql, $params);

			if($db->num_rows($result)) {
				$row = $db->query_result_rowdata($result, 0);
				$qualifiedId = self::getQualifiedId(self::RULE_MEMBER_TYPE_ROLE_AND_SUBORDINATES, $row['roleid']);
				$name = $row['rolename'];
				$rule = new self();
				return $rule->set('id', $qualifiedId)->set('name', $name);
			}
		}

		return false;
	}

	/**
	 * Function to get all the rule members
	 * @return <Array> - Array of Settings_SharingAccess_RuleMember_Model instances
	 */
	public static function getAll() {
		$rules = array();

		$allGroups = Settings_Groups_Record_Model::getAll();
		foreach($allGroups as $groupId => $groupModel) {
			$qualifiedId = self::getQualifiedId(self::RULE_MEMBER_TYPE_GROUPS, $groupId);
			$rule = new self();
			$rules[self::RULE_MEMBER_TYPE_GROUPS][$qualifiedId] = $rule->set('id', $qualifiedId)->set('name', $groupModel->getName());
		}

		$allRoles = Settings_Roles_Record_Model::getAll();
		foreach($allRoles as $roleId => $roleModel) {
			$qualifiedId = self::getQualifiedId(self::RULE_MEMBER_TYPE_ROLES, $roleId);
			$rule = new self();
			$rules[self::RULE_MEMBER_TYPE_ROLES][$qualifiedId] = $rule->set('id', $qualifiedId)->set('name', $roleModel->getName());

			$qualifiedId = self::getQualifiedId(self::RULE_MEMBER_TYPE_ROLE_AND_SUBORDINATES, $roleId);
			$rule = new self();
			$rules[self::RULE_MEMBER_TYPE_ROLE_AND_SUBORDINATES][$qualifiedId] = $rule->set('id', $qualifiedId)->set('name', $roleModel->getName());
		}

		return $rules;
	}

}
