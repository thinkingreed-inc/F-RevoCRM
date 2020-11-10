<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once dirname(__FILE__) . '/ModCommentsCore.php';
include_once dirname(__FILE__) . '/models/Comments.php';

require_once 'include/utils/VtlibUtils.php';

class ModComments extends ModCommentsCore {

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		parent::vtlib_handler($modulename, $event_type);
		if ($event_type == 'module.postinstall') {
			self::addWidgetTo(array('Leads', 'Contacts', 'Accounts', 'Potentials', 'Project', 'ProjectTask'));
			global $adb;
			// Mark the module as Standard module
			$adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', array($modulename));

		} elseif ($event_type == 'module.postupdate') {
			self::addWidgetTo(array('Potentials'));
		}
	}

	/**
	 * Transfer the comment records from one parent record to another.
	 * @param CRMID Source parent record id
	 * @param CRMID Target parent record id
	 */
	static function transferRecords($currentParentId, $targetParentId) {
		global $adb;
		$adb->pquery("UPDATE vtiger_modcomments SET related_to=? WHERE related_to=?", array($targetParentId, $currentParentId));
	}

	/**
	 * Get widget instance by name
	 */
	static function getWidget($name) {
		if ($name == 'DetailViewBlockCommentWidget' &&
				isPermitted('ModComments', 'DetailView') == 'yes') {
			require_once dirname(__FILE__) . '/widgets/DetailViewBlockComment.php';
			return (new ModComments_DetailViewBlockCommentWidget());
		}
		return false;
	}

	/**
	 * Add widget to other module.
	 * @param unknown_type $moduleNames
	 * @return unknown_type
	 */
	static function addWidgetTo($moduleNames, $widgetType='DETAILVIEWWIDGET', $widgetName='DetailViewBlockCommentWidget') {
		if (empty($moduleNames)) return;

		include_once 'vtlib/Vtiger/Module.php';

		if (is_string($moduleNames)) $moduleNames = array($moduleNames);

		$modCommentsModule = Vtiger_Module::getInstance('ModComments');
		
		$commentWidgetModules = array();
		foreach($moduleNames as $moduleName) {
			$module = Vtiger_Module::getInstance($moduleName);
			if($module) {
				$module->addLink($widgetType, $widgetName, "block://ModComments:modules/ModComments/ModComments.php");
				$module->setRelatedList($modCommentsModule, 'ModComments', array(''), 'get_comments');
				$commentWidgetModules[] = $moduleName;
			}
		}
		if (count($commentWidgetModules) > 0) {
			$modCommentsModule->addLink('HEADERSCRIPT', 'ModCommentsCommonHeaderScript', 'modules/ModComments/ModCommentsCommon.js');
			$modCommentsRelatedToField = Vtiger_Field::getInstance('related_to', $modCommentsModule);
			$modCommentsRelatedToField->setRelatedModules($commentWidgetModules);
		}
	}

	/**
	 * Remove widget from other modules.
	 * @param unknown_type $moduleNames
	 * @param unknown_type $widgetType
	 * @param unknown_type $widgetName
	 * @return unknown_type
	 */
	static function removeWidgetFrom($moduleNames, $widgetType='DETAILVIEWWIDGET', $widgetName='DetailViewBlockCommentWidget') {
		if (empty($moduleNames)) return;

		include_once 'vtlib/Vtiger/Module.php';

		if (is_string($moduleNames)) $moduleNames = array($moduleNames);
		
		$modCommentsModule = Vtiger_Module::getInstance('ModComments');

		$commentWidgetModules = array();
		foreach($moduleNames as $moduleName) {
			$module = Vtiger_Module::getInstance($moduleName);
			if($module) {
				$module->deleteLink($widgetType, $widgetName, "block://ModComments:modules/ModComments/ModComments.php");
				$module->unsetRelatedList($modCommentsModule, 'ModComments', 'get_comments');
				$commentWidgetModules[] = $moduleName;
			}
		}
		if (count($commentWidgetModules) > 0) {
			$modCommentsRelatedToField = Vtiger_Field::getInstance('related_to', $modCommentsModule);
			$modCommentsRelatedToField->unsetRelatedModules($commentWidgetModules);
		}
	}

	/**
	 * Wrap this instance as a model
	 */
	function getAsCommentModel() {
		return new ModComments_CommentsModel($this->column_fields);
	}

	function getListButtons($app_strings) {
		$list_buttons = Array();
		return $list_buttons;
	}

	/**
	 * Function to copy the comments from parent record to the target record.
	 * @param type $currentParentId
	 * @param type $targetParentId
	 */
	public static function copyCommentsToRelatedRecord($currentParentId, $targetParentId) {
		$db = PearDatabase::getInstance();
		$relatedIdMap = array();
		$result = $db->pquery("SELECT *FROM vtiger_modcomments WHERE related_to=?", array($currentParentId));
		$count = $db->num_rows($result);

		for($i=0;$i<$count;$i++) {
			$commentId = $db->query_result($result, $i, 'modcommentsid');
			$commentContent = decode_html($db->query_result($result,$i,'commentcontent'));
			$parentComments = $db->query_result($result, $i, 'parent_comments');
			$customer = $db->query_result($result, $i, 'customer');
			$userId = $db->query_result($result, $i, 'userid');
			$reasonToEdit = $db->query_result($result, $i, 'reasontoedit');
			$fromMailConverter = $db->query_result($result, $i, 'from_mailconverter');
			$fromMailroom = $db->query_result($result, $i, 'from_mailroom');
			$isPrivate = $db->query_result($result, $i, 'is_private');
			$customer_Email = $db->query_result($result, $i, 'customer_email');
			$filename = $db->query_result($result, $i, 'filename');
			$related_email_id = $db->query_result($result, $i, 'related_email_id');

			if(!empty($parentComments)) {
				$parentComments = $relatedIdMap[$parentComments]; // should be mapped with copied comment
			}

			$crmEntityResult = $db->pquery('SELECT *FROM vtiger_crmentity where crmid = ?', array($commentId));
			$smcreatorId = $db->query_result($crmEntityResult, 0, 'smcreatorid');
			$smownerId = $db->query_result($crmEntityResult, 0, 'smownerid');
			$modifiedby = $db->query_result($crmEntityResult, 0, 'modifiedby');
			$setype = $db->query_result($crmEntityResult, 0, 'setype');
			$description = $db->query_result($crmEntityResult, 0, 'description');
			$createdTime = $db->query_result($crmEntityResult, 0, 'createdtime');
			$modifiedTime = $db->query_result($crmEntityResult, 0, 'modifiedtime');
			$viewedTime = $db->query_result($crmEntityResult, 0, 'viewedtime');
			$status = $db->query_result($crmEntityResult, 0, 'status');
			$version = $db->query_result($crmEntityResult, 0, 'version');
			$presence = $db->query_result($crmEntityResult, 0, 'presence');
			$deleted = $db->query_result($crmEntityResult, 0, 'deleted');
			$label = $db->query_result($crmEntityResult, 0, 'label');
			$source = $db->query_result($crmEntityResult, 0, 'source');
			$smgroupId = $db->query_result($crmEntityResult, 0, 'smgroupid');

			$commentCrmId = $db->getUniqueID('vtiger_crmentity');
			$crmentityParams = array($commentCrmId, $smcreatorId, 
						$smownerId, $modifiedby, $setype, $description, $createdTime, $modifiedTime, $viewedTime, $status, $version, $presence,
						$deleted, $label, $source, $smgroupId);
			$db->pquery('INSERT INTO vtiger_crmentity values('. generateQuestionMarks($crmentityParams) .')', $crmentityParams);

			$modcommentsParams = array($commentCrmId, $commentContent, 
						$targetParentId, $parentComments, $customer, $userId, $reasonToEdit, $fromMailConverter, $isPrivate,
						$customer_Email, $fromMailroom, $filename, $related_email_id);
			$db->pquery('INSERT INTO vtiger_modcomments values('. generateQuestionMarks($modcommentsParams) .')', $modcommentsParams);
			$relatedIdMap[$commentId] = $commentCrmId;
		}
	}

}
?>
