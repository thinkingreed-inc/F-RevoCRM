<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

//A collection of util functions for the workflow module

class VTWorkflowUtils {
	static $userStack;
	static $loggedInUser;

	function __construct() {
		global $current_user;
		if(empty(self::$userStack)) {
			self::$userStack = array();
		}
	}

	/**
	 * Check whether the given identifier is valid.
	 */
	function validIdentifier($identifier) {
		if (is_string($identifier)) {
			return preg_match("/^[a-zA-Z][a-zA-Z_0-9]+$/", $identifier);
		} else {
			return false;
		}
	}

	/**
	 * Push the admin user on to the user stack
	 * and make it the $current_user
	 *
	 */
	function adminUser() {
        $user = Users::getActiveAdminUser();
		global $current_user;
		if (empty(self::$userStack) || php7_count(self::$userStack) == 0) {
			self::$loggedInUser = $current_user;
		}
		array_push(self::$userStack, $current_user);
		$current_user = $user;
		return $user;
	}

	/**
	 * Push the logged in user on the user stack
	 * and make it the $current_user
	 */
	function loggedInUser() {
		$user = self::$loggedInUser;
		global $current_user;
		array_push(self::$userStack, $current_user);
		$current_user = $user;
		return $user;
	}

	/**
	 * Revert to the previous use on the user stack
	 */
	function revertUser() {
		global $current_user;
		if (php7_count(self::$userStack) != 0) {
			array_splice(self::$userStack, 1);
			$current_user = self::$userStack[0];
		} else {
			$current_user = null;
		}
		return $current_user;
	}

	/**
	 * Get the current user
	 */
	function currentUser() {
		return $current_user;
	}

	/**
	 * The the webservice entity type of an EntityData object
	 */
	function toWSModuleName($entityData) {
		$moduleName = $entityData->getModuleName();
		if ($moduleName == 'Activity') {
			$arr = array('Task' => 'Calendar', 'Emails' => 'Emails');
			$moduleName = $arr[getActivityType($entityData->getId())];
			if ($moduleName == null) {
				$moduleName = 'Events';
			}
		}
		return $moduleName;
	}

	/**
	 * Insert redirection script
	 */
	function redirectTo($to, $message) {
?>
		<script type="text/javascript" charset="utf-8">
			window.location="<?php echo $to ?>";
		</script>
		<a href="<?php echo $to ?>"><?php echo $message ?></a>
<?php
	}

	/**
	 * Check if the current user is admin
	 */
	function checkAdminAccess() {
		global $current_user;
		return strtolower($current_user->is_admin) === 'on';
	}

	/* function to check if the module has workflow
	 * @params :: $modulename - name of the module
	 */

	public static function checkModuleWorkflow($modulename) {
		$result = true;
		if (in_array($modulename, array('Emails', 'Faq', 'PBXManager', 'Users')) || !getTabid($modulename)) {
			$result = false;
		}
		return $result;
	}

	function vtGetModules($adb) {
		$modules_not_supported = array('Emails', 'PBXManager');
		$sql = "select distinct vtiger_field.tabid, name
			from vtiger_field
			inner join vtiger_tab
				on vtiger_field.tabid=vtiger_tab.tabid
			where vtiger_tab.name not in(" . generateQuestionMarks($modules_not_supported) . ") and vtiger_tab.isentitytype=1 and vtiger_tab.presence in (0,2) ";
		$it = new SqlResultIterator($adb, $adb->pquery($sql, array($modules_not_supported)));
		$modules = array();
		foreach ($it as $row) {
			$modules[] = $row->name;
		}
		return $modules;
	}
}