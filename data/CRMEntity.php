<?php
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************
 * $Header: /advent/projects/wesat/vtiger_crm/vtigercrm/data/CRMEntity.php,v 1.16 2005/04/29 04:21:31 mickie Exp $
 * Description:  Defines the base class for all data entities used throughout the
 * application.  The base class including its methods and variables is designed to
 * be overloaded with module-specific methods and variables particular to the
 * module's base entity class.
 ********************************************************************************/

include_once('config.php');
require_once('include/logging.php');
require_once('data/Tracker.php');
require_once('include/utils/utils.php');
require_once('include/utils/UserInfoUtil.php');
require_once("include/Zend/Json.php");
require_once 'include/RelatedListView.php';

class CRMEntity {

	var $ownedby;
	var $recordSource = 'CRM';

	/**
	 * Detect if we are in bulk save mode, where some features can be turned-off
	 * to improve performance.
	 */
	static function isBulkSaveMode() {
		global $VTIGER_BULK_SAVE_MODE;
		if (isset($VTIGER_BULK_SAVE_MODE) && $VTIGER_BULK_SAVE_MODE) {
			return true;
		}
		return false;
	}

	static function getInstance($module) {
		$modName = $module;
		if ($module == 'Calendar' || $module == 'Events') {
			$module = 'Calendar';
			$modName = 'Activity';
		}
		// File access security check
		if (!class_exists($modName)) {
			checkFileAccessForInclusion("modules/$module/$modName.php");
			require_once("modules/$module/$modName.php");
		}
		$focus = new $modName();
		$focus->moduleName = $module;
		$focus->column_fields = new TrackableObject();
		$focus->column_fields = getColumnFields($module);
		if (method_exists($focus, 'initialize')) $focus->initialize();
		return $focus;
	}

	/**
	 * Function which indicates whether to chagne the modified time or not while saving
	 */
	static function isTimeStampNoChangeMode(){
		global $VTIGER_TIMESTAMP_NO_CHANGE_MODE;
		if (isset($VTIGER_TIMESTAMP_NO_CHANGE_MODE) && $VTIGER_TIMESTAMP_NO_CHANGE_MODE) {
			return true;
		}
		return false;
	}

	/**
	 * Function which will used to initialize object properties 
	 */
	function initialize() {
		$moduleName = $this->moduleName;
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if($moduleModel && !$moduleModel->isEntityModule()) {
			return;
		}

		$userSpecificTableIgnoredModules = array('SMSNotifier', 'PBXManager', 'ModComments');
		if(in_array($moduleName, $userSpecificTableIgnoredModules)) return;

		$userSpecificTable = Vtiger_Functions::getUserSpecificTableName($moduleName);
		if(!in_array($userSpecificTable, $this->tab_name)) {
			$this->tab_name[] = $userSpecificTable;
			$this->tab_name_index [$userSpecificTable] = 'recordid';
		}
	}

	function saveentity($module, $fileid = '') {
		global $current_user, $adb; //$adb added by raju for mass mailing
		$insertion_mode = $this->mode;

		$columnFields = $this->column_fields;
		$anyValue = false;
		foreach ($columnFields as $value) {
			if(!empty($value)) {
				$anyValue = true;
				break;
			}
		}
		if(!$anyValue) {
			die("<center>" .getTranslatedString('LBL_MANDATORY_FIELD_MISSING')."</center>");
		}

		// added to support files transformation for file upload fields like uitype 69, 
		if(!empty($_FILES) && count($_FILES)) {
			$_FILES = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
		}

		$this->db->startTransaction();
		foreach ($this->tab_name as $table_name) {
			if ($table_name == "vtiger_crmentity") {
				$this->insertIntoCrmEntity($module, $fileid);
			} else {
				$this->insertIntoEntityTable($table_name, $module, $fileid);
			}
		}
		$columnFields->restartTracking();
		//Calling the Module specific save code
		$this->save_module($module);

		$this->db->completeTransaction();

		// vtlib customization: Hook provide to enable generic module relation.
		if ($_REQUEST['createmode'] == 'link' && !$_REQUEST['__linkcreated']) {
			$_REQUEST['__linkcreated'] = true;
			$for_module = vtlib_purify($_REQUEST['return_module']);
			$for_crmid = vtlib_purify($_REQUEST['return_id']);
			$with_module = $module;
			$with_crmid = $this->id;

			$on_focus = CRMEntity::getInstance($for_module);

			if ($for_module && $for_crmid && $with_module && $with_crmid) {
				relateEntities($on_focus, $for_module, $for_crmid, $with_module, $with_crmid);
			}
		}
		// END
	}

	/**
	 * This function is used to upload the attachment in the server and save that attachment information in db.
	 * @param int $id  - entity id to which the file to be uploaded
	 * @param string $module  - the current module name
	 * @param array $file_details  - array which contains the file information(name, type, size, tmp_name and error)
	 * return void
	 */
	function uploadAndSaveFile($id, $module, $file_details, $attachmentType='Attachment') {
		global $log;
		$log->debug("Entering into uploadAndSaveFile($id,$module,$file_details) method.");

		global $adb, $current_user;
		global $upload_badext;

		$date_var = date("Y-m-d H:i:s");

		//to get the owner id
		$ownerid = $this->column_fields['assigned_user_id'];
		if (!isset($ownerid) || $ownerid == '')
			$ownerid = $current_user->id;

		if (isset($file_details['original_name']) && $file_details['original_name'] != null) {
			$file_name = $file_details['original_name'];
		} else {
			$file_name = $file_details['name'];
		}

		// Check 1
		$save_file = 'true';
		//only images are allowed for Image Attachmenttype
		$mimeType = vtlib_mime_content_type($file_details['tmp_name']);
		$mimeTypeContents = explode('/', $mimeType);
		// For contacts and products we are sending attachmentType as value
		if ($attachmentType == 'Image' || ($file_details['size'] && $mimeTypeContents[0] == 'image')) {
			$save_file = validateImageFile($file_details);
		}
                $log->debug("File Validation status in Check1 save_file => $save_file");
		if ($save_file == 'false') {
			return false;
		}

		// Check 2
		$save_file = 'true';
		//only images are allowed for these modules
		if ($module == 'Contacts' || $module == 'Products') {
			$save_file = validateImageFile($file_details);
		}
                $log->debug("File Validation status in Check2 save_file => $save_file");
		$binFile = sanitizeUploadFileName($file_name, $upload_badext);

		$current_id = $adb->getUniqueID("vtiger_crmentity");

		$filename = ltrim(basename(" " . $binFile)); //allowed filename like UTF-8 characters
		$filetype = $file_details['type'];
		$filetmp_name = $file_details['tmp_name'];

		//get the file path inwhich folder we want to upload the file
		$upload_file_path = decideFilePath();

		// upload the file in server
        $encryptFileName = Vtiger_Util_Helper::getEncryptedFileName($binFile);
		$upload_status = copy($filetmp_name, $upload_file_path . $current_id . "_" . $encryptFileName);
		// temporary file will be deleted at the end of request
                $log->debug("Upload status of file => $upload_status");
		if ($save_file == 'true' && $upload_status == 'true') {
			if($attachmentType != 'Image' && $this->mode == 'edit') {
				//Only one Attachment per entity delete previous attachments
				$res = $adb->pquery('SELECT vtiger_seattachmentsrel.attachmentsid FROM vtiger_seattachmentsrel 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_seattachmentsrel.attachmentsid AND vtiger_crmentity.setype = ? 
									WHERE vtiger_seattachmentsrel.crmid = ?',array($module.' Attachment',$id));
				$oldAttachmentIds = array();
				for($attachItr = 0;$attachItr < $adb->num_rows($res);$attachItr++) {
					$oldAttachmentIds[] = $adb->query_result($res,$attachItr,'attachmentsid');
				}
				if(count($oldAttachmentIds)) {
					$adb->pquery('DELETE FROM vtiger_seattachmentsrel WHERE attachmentsid IN ('.generateQuestionMarks($oldAttachmentIds).')',$oldAttachmentIds);
					//TODO : revisit to delete actual file and attachment entry,as we need to see the deleted file in the history when its changed
					//$adb->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid IN ('.generateQuestionMarks($oldAttachmentIds).')',$oldAttachmentIds);
					//$adb->pquery('DELETE FROM vtiger_crmentity WHERE crmid IN ('.generateQuestionMarks($oldAttachmentIds).')',$oldAttachmentIds);
				}
			}
			//Add entry to crmentity
			$sql1 = "INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) VALUES (?, ?, ?, ?, ?, ?, ?)";
			$params1 = array($current_id, $current_user->id, $ownerid, $module." ".$attachmentType, $this->column_fields['description'], $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
			$adb->pquery($sql1, $params1);
			//Add entry to attachments
			$sql2 = "INSERT INTO vtiger_attachments(attachmentsid, name, description, type, path, storedname) values(?, ?, ?, ?, ?, ?)";
			$params2 = array($current_id, $filename, $this->column_fields['description'], $filetype, $upload_file_path, $encryptFileName);
			$adb->pquery($sql2, $params2);
			//Add relation
			$sql3 = 'INSERT INTO vtiger_seattachmentsrel VALUES(?,?)';
			$params3 = array($id, $current_id);
			$adb->pquery($sql3, $params3);
                        $log->debug("File uploaded successfully with id => $current_id");
			return $current_id;
		} else {
			//failed to upload file
                    $log->debug('File upload failed');
			return false;
		}
	}

	/** Function to insert values in the vtiger_crmentity for the specified module
	 * @param $module -- module:: Type varchar
	 */
	function insertIntoCrmEntity($module, $fileid = '') {
		global $adb;
		global $current_user;
		global $log;

		if ($fileid != '') {
			$this->id = $fileid;
			$this->mode = 'edit';
		}

		$date_var = date("Y-m-d H:i:s");

		$ownerid = $this->column_fields['assigned_user_id'];
		$groupid = $this->column_fields['group_id'];

		if (empty($groupid))
			$groupid = 0;


		if (empty($ownerid)) {
			$ownerid = $current_user->id;
		}

		if ($module == 'Events') {
			$module = 'Calendar';
		}
		if ($this->mode == 'edit') {
			$description_val = from_html($this->column_fields['description'], ($insertion_mode == 'edit') ? true : false);

			$tabid = getTabid($module);
			$modified_date_var = $adb->formatDate($date_var, true);
			if(self::isTimeStampNoChangeMode()) {
				if(!empty($this->column_fields['modifiedtime'])) {
					$modified_date_var = $adb->formatDate($this->column_fields['modifiedtime'], true);
				}
			}

			$acl = Vtiger_AccessControl::loadUserPrivileges($current_user->id);
			if ($acl->is_admin == true || $acl->profileGlobalPermission[1] == 0 || $acl->profileGlobalPermission[2] == 0 || $this->isWorkFlowFieldUpdate) {
				$sql = "update vtiger_crmentity set smownerid=?, smgroupid=?,modifiedby=?,description=?, modifiedtime=? where crmid=?";
				$params = array($ownerid, $groupid, $current_user->id, $description_val, $adb->formatDate($date_var, true), $this->id);
			} else {
				$profileList = getCurrentUserProfileList();
				$perm_qry = "SELECT columnname FROM vtiger_field INNER JOIN vtiger_profile2field ON vtiger_profile2field.fieldid = vtiger_field.fieldid INNER JOIN vtiger_def_org_field ON vtiger_def_org_field.fieldid = vtiger_field.fieldid WHERE vtiger_field.tabid = ? AND vtiger_profile2field.visible = 0 AND vtiger_profile2field.readonly = 0 AND vtiger_profile2field.profileid IN (" . generateQuestionMarks($profileList) . ") AND vtiger_def_org_field.visible = 0 and vtiger_field.tablename='vtiger_crmentity' and vtiger_field.displaytype in (1,3) and vtiger_field.presence in (0,2);";
				$perm_result = $adb->pquery($perm_qry, array($tabid, $profileList));
				$perm_rows = $adb->num_rows($perm_result);
				for ($i = 0; $i < $perm_rows; $i++) {
					$columname[] = $adb->query_result($perm_result, $i, "columnname");
				}
				if (is_array($columname) && in_array("description", $columname)) {
					$sql = "update vtiger_crmentity set smownerid=?, smgroupid=?, modifiedby=?,description=?, modifiedtime=? where crmid=?";
					$params = array($ownerid, $groupid, $current_user->id, $description_val, $adb->formatDate($date_var, true), $this->id);
				} else {
					$sql = "update vtiger_crmentity set smownerid=?, smgroupid=?,modifiedby=?, modifiedtime=? where crmid=?";
					$params = array($ownerid, $groupid, $current_user->id, $adb->formatDate($date_var, true), $this->id);
				}
			}
			$adb->pquery($sql, $params);
			$this->column_fields['modifiedtime'] =  $modified_date_var;
			$this->column_fields['modifiedby'] = $current_user->id;
		} else {
			//if this is the create mode and the group allocation is chosen, then do the following
			$current_id = $adb->getUniqueID("vtiger_crmentity");
			$_REQUEST['currentid'] = $current_id;
			if ($current_user->id == '')
				$current_user->id = 0;


			// Customization
			$created_date_var = $adb->formatDate($date_var, true);
			$modified_date_var = $adb->formatDate($date_var, true);

			// Preserve the timestamp
			if (self::isBulkSaveMode()) {
				if (!empty($this->column_fields['createdtime']))
					$created_date_var = $adb->formatDate($this->column_fields['createdtime'], true);
				//NOTE : modifiedtime ignored to support vtws_sync API track changes.
			}
			// END

			if ($this->column_fields['source'] != null && $this->column_fields['source'] != " ") {
				$source = strtoupper($this->column_fields['source']);
			} else {
				$source = strtoupper($this->recordSource);
			}

			$description_val = from_html($this->column_fields['description'], ($insertion_mode == 'edit') ? true : false);
			$sql = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,smgroupid,setype,description,modifiedby,createdtime,modifiedtime,source) values(?,?,?,?,?,?,?,?,?,?)";
			$params = array($current_id, $current_user->id, $ownerid, $groupid, $module, $description_val, $current_user->id, $created_date_var, $modified_date_var,$source);
			$adb->pquery($sql, $params);

			$this->column_fields['createdtime'] = $created_date_var;
			$this->column_fields['modifiedtime'] = $modified_date_var;
			$this->column_fields['modifiedby'] = $current_user->id;
			//$this->column_fields['created_user_id'] = $current_user->id;
			$this->id = $current_id;
		}
	}

	// Function which returns the value based on result type (array / ADODB ResultSet)
	private function resolve_query_result_value($result, $index, $columnname) {
		global $adb;
		if (is_array($result))
			return $result[$index][$columnname];
		else
			return $adb->query_result($result, $index, $columnname);
	}

	/** Function to insert values in the specifed table for the specified module
	 * @param $table_name -- table name:: Type varchar
	 * @param $module -- module:: Type varchar
	 */
	function insertIntoEntityTable($table_name, $module, $fileid = '') {
		global $log;
		global $current_user, $app_strings;
		$log->info("function insertIntoEntityTable " . $module . ' vtiger_table name ' . $table_name);
		global $adb;
		$insertion_mode = $this->mode;
        $table_name = Vtiger_Util_Helper::validateStringForSql($table_name);
        
		//Checkin whether an entry is already is present in the vtiger_table to update
		if ($insertion_mode == 'edit') {
			$tablekey = $this->tab_name_index[$table_name];
			// Make selection on the primary key of the module table to check.
			$check_query = "select $tablekey from $table_name where $tablekey=?";
			$check_params = array($this->id);
			if (Vtiger_Functions::isUserSpecificFieldTable($table_name, $module)) {
				$check_query .= ' AND userid=?';
				array_push($check_params, $current_user->id);
			}
			$check_result = $adb->pquery($check_query, $check_params);

			$num_rows = $adb->num_rows($check_result);

			if ($num_rows <= 0) {
				$insertion_mode = '';
			}
		}

		$tabid = getTabid($module);
		if ($module == 'Calendar' && $this->column_fields["activitytype"] != null && $this->column_fields["activitytype"] != 'Task') {
			$tabid = getTabid('Events');
		}
		if ($insertion_mode == 'edit') {
			$update = array();
			$update_params = array();
			$updateColumnNames = array();
			$updateFieldNameColumnNameMap = array();
			$acl = Vtiger_AccessControl::loadUserPrivileges($current_user->id);
			if ($acl->is_admin == true || $acl->profileGlobalPermission[1] == 0 || $acl->profileGlobalPermission[2] == 0 || $this->isWorkFlowFieldUpdate) {
				$sql = "select fieldname,columnname,uitype,generatedtype,
										typeofdata from vtiger_field where tabid in (" . generateQuestionMarks($tabid) . ") and tablename=? and displaytype in (1,3,6) and presence in (0,2) group by columnname";
				$params = array($tabid, $table_name);
			} else {
				$profileList = getCurrentUserProfileList();

				if (count($profileList) > 0) {
					$sql = "SELECT vtiger_field.fieldname,vtiger_field.columnname,vtiger_field.uitype,vtiger_field.generatedtype,vtiger_field.typeofdata FROM vtiger_field
						INNER JOIN vtiger_profile2field
						ON vtiger_profile2field.fieldid = vtiger_field.fieldid
						INNER JOIN vtiger_def_org_field
						ON vtiger_def_org_field.fieldid = vtiger_field.fieldid
						WHERE vtiger_field.tabid = ?
						AND vtiger_profile2field.visible = 0 AND vtiger_profile2field.readonly = 0
						AND vtiger_profile2field.profileid IN (" . generateQuestionMarks($profileList) . ")
						AND vtiger_def_org_field.visible = 0 and vtiger_field.tablename=? and vtiger_field.displaytype in (1,3,6) and vtiger_field.presence in (0,2) group by columnname";

					$params = array($tabid, $profileList, $table_name);
				} else {
					$sql = "SELECT vtiger_field.fieldname,vtiger_field.columnname,vtiger_field.uitype,vtiger_field.generatedtype,vtiger_field.typeofdata FROM vtiger_field
						INNER JOIN vtiger_profile2field
						ON vtiger_profile2field.fieldid = vtiger_field.fieldid
						INNER JOIN vtiger_def_org_field
						ON vtiger_def_org_field.fieldid = vtiger_field.fieldid
						WHERE vtiger_field.tabid = ?
						AND vtiger_profile2field.visible = 0 AND vtiger_profile2field.readonly = 0
						AND vtiger_def_org_field.visible = 0 and vtiger_field.tablename=? and vtiger_field.displaytype in (1,3,6) and vtiger_field.presence in (0,2) group by columnname";

					$params = array($tabid, $table_name);
				}
			}
		} else {
			$table_index_column = $this->tab_name_index[$table_name];
			if ($table_index_column == 'id' && $table_name == 'vtiger_users') {
				$currentuser_id = $adb->getUniqueID("vtiger_users");
				$this->id = $currentuser_id;
			}
			$column = array($table_index_column);
			$value = array($this->id);
			if (Vtiger_Functions::isUserSpecificFieldTable($table_name, $module)) {
				array_push($column, 'userid');
				array_push($value, $current_user->id);
			}
			$sql = "select fieldname,columnname,uitype,generatedtype,typeofdata from vtiger_field where tabid=? and tablename=? and displaytype in (1,3,4,6) and vtiger_field.presence in (0,2)";
			$params = array($tabid, $table_name);
		}

		// Attempt to re-use the quer-result to avoid reading for every save operation
		// TODO Need careful analysis on impact ... MEMORY requirement might be more
		static $_privatecache = array();

		$cachekey = "{$insertion_mode}-" . implode(',', $params);

		if (!isset($_privatecache[$cachekey])) {
			$result = $adb->pquery($sql, $params);
			$noofrows = $adb->num_rows($result);

			if (CRMEntity::isBulkSaveMode()) {
				$cacheresult = array();
				for ($i = 0; $i < $noofrows; ++$i) {
					$cacheresult[] = $adb->fetch_array($result);
				}
				$_privatecache[$cachekey] = $cacheresult;
			}
		} else { // Useful when doing bulk save
			$result = $_privatecache[$cachekey];
			$noofrows = count($result);
		}

		for ($i = 0; $i < $noofrows; $i++) {

			$fieldname = $this->resolve_query_result_value($result, $i, "fieldname");
			$columname = $this->resolve_query_result_value($result, $i, "columnname");
			$uitype = $this->resolve_query_result_value($result, $i, "uitype");
			$generatedtype = $this->resolve_query_result_value($result, $i, "generatedtype");
			$typeofdata = $this->resolve_query_result_value($result, $i, "typeofdata");
			$skipUpdateForField = false;
			$typeofdata_array = explode("~", $typeofdata);
			$datatype = $typeofdata_array[0];

			$ajaxSave = false;
			if (($_REQUEST['file'] == 'DetailViewAjax' && $_REQUEST['ajxaction'] == 'DETAILVIEW'
						&& isset($_REQUEST["fldName"]) && $_REQUEST["fldName"] != $fieldname)
					|| ($_REQUEST['action'] == 'MassEditSave' && !isset($_REQUEST[$fieldname."_mass_edit_check"]))) {
				$ajaxSave = true;
			}

			if ($uitype == 4 && $insertion_mode != 'edit') {
				$fldvalue = '';
				// Bulk Save Mode: Avoid generation of module sequence number, take care later.
				if (!CRMEntity::isBulkSaveMode())
					$fldvalue = $this->setModuleSeqNumber("increment", $module);
				$this->column_fields[$fieldname] = $fldvalue;
			}
			if (isset($this->column_fields[$fieldname])) {
				if ($uitype == 56) {
					if ($this->column_fields[$fieldname] === 'on' || $this->column_fields[$fieldname] == 1) {
						$fldvalue = '1';
					} else {
						$fldvalue = '0';
					}
				} elseif ($uitype == 15 || $uitype == 16) {

					if ($this->column_fields[$fieldname] == $app_strings['LBL_NOT_ACCESSIBLE']) {

						//If the value in the request is Not Accessible for a picklist, the existing value will be replaced instead of Not Accessible value.
						$sql = "select $columname from  $table_name where " . $this->tab_name_index[$table_name] . "=?";
						$res = $adb->pquery($sql, array($this->id));
						$pick_val = $adb->query_result($res, 0, $columname);
						$fldvalue = $pick_val;
					} else {
						$fldvalue = $this->column_fields[$fieldname];
					}
				} elseif ($uitype == 33) {
					if (is_array($this->column_fields[$fieldname])) {
						$field_list = implode(' |##| ', $this->column_fields[$fieldname]);
					} else {
						$field_list = $this->column_fields[$fieldname];
					}
					$fldvalue = $field_list;
				} elseif ($uitype == 5 || $uitype == 6 || $uitype == 23) {
					//Added to avoid function call getDBInsertDateValue in ajax save
					if (isset($current_user->date_format) && !$ajaxSave) {
						$fldvalue = getValidDBInsertDateTimeValue($this->column_fields[$fieldname]);
					} else {
						$fldvalue = $this->column_fields[$fieldname];
					}
				} elseif ($uitype == 7) {
					//strip out the spaces and commas in numbers if given ie., in amounts there may be ,
					$fldvalue = str_replace(",", "", $this->column_fields[$fieldname]); //trim($this->column_fields[$fieldname],",");
					if (in_array($datatype, array('N', 'NN'))) {
						$fldvalue = CurrencyField::convertToDBFormat($this->column_fields[$fieldname], $current_user, true);
					}
				} elseif ($uitype == 1 && in_array($datatype, array('N', 'NN')) && in_array($fieldname, array('qty_per_unit','qtyinstock','salescommission','exciseduty'))) {
					$fldvalue = CurrencyField::convertToDBFormat($this->column_fields[$fieldname], $current_user, true);
				} elseif ($uitype == 26) {
					if (empty($this->column_fields[$fieldname])) {
						$fldvalue = 1; //the documents will stored in default folder
					} else {
						$fldvalue = $this->column_fields[$fieldname];
					}
				} elseif ($uitype == 28) {
					if ($this->column_fields[$fieldname] == null) {
						$fileQuery = $adb->pquery("SELECT filename from vtiger_notes WHERE notesid = ?", array($this->id));
						$fldvalue = null;
						if (isset($fileQuery)) {
							$rowCount = $adb->num_rows($fileQuery);
							if ($rowCount > 0) {
								$fldvalue = decode_html($adb->query_result($fileQuery, 0, 'filename'));
							}
						}
					} else {
						$fldvalue = decode_html($this->column_fields[$fieldname]);
					}
				} elseif ($uitype == 8) {
					$this->column_fields[$fieldname] = rtrim($this->column_fields[$fieldname], ',');
					$ids = explode(',', $this->column_fields[$fieldname]);
					$json = new Zend_Json();
					$fldvalue = $json->encode($ids);
				} elseif ($uitype == 12) {

					// Bulk Sae Mode: Consider the FROM email address as specified, if not lookup
					$fldvalue = $this->column_fields[$fieldname];

					if (empty($fldvalue)) {
						$query = "SELECT email1 FROM vtiger_users WHERE id = ?";
						$res = $adb->pquery($query, array($current_user->id));
						$rows = $adb->num_rows($res);
						if ($rows > 0) {
							$fldvalue = $adb->query_result($res, 0, 'email1');
						}
					}
					// END
				} elseif ($uitype == 72 && !$ajaxSave) {
					// Some of the currency fields like Unit Price, Totoal , Sub-total - doesn't need currency conversion during save
					$fldvalue = CurrencyField::convertToDBFormat($this->column_fields[$fieldname], null, true);
				} elseif ($uitype == 71 && !$ajaxSave) {
					$fldvalue = CurrencyField::convertToDBFormat($this->column_fields[$fieldname]);
				} elseif ($uitype == 69) {
					$fldvalue = $this->column_fields[$fieldname];
					if(count($_FILES)) {
						$IMG_FILES = $_FILES[$fieldname];
						if($_REQUEST['action'] == 'MassSave' || $_REQUEST['action'] == 'MassEditSave') {
							if($IMG_FILES[0]['error'] == 0) {
								$oldImageAttachmentIds = array();
								$oldAttachmentsRes = $adb->pquery('SELECT vtiger_seattachmentsrel.attachmentsid FROM vtiger_seattachmentsrel 
									INNER JOIN vtiger_attachments ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid AND setype = ? 
									WHERE vtiger_seattachmentsrel.crmid = ?',array($module.' Image',$this->id));
								for($itr = 0;$itr < $adb->num_rows($oldAttachmentsRes);$itr++) {
									$oldImageAttachmentIds[] = $adb->query_result($oldAttachmentsRes,$itr,'attachmentsid');
								}
								if(count($oldImageAttachmentIds)) {
									$adb->pquery('DELETE FROM vtiger_seattachmentsrel WHERE attachmentsid IN ('.generateQuestionMarks($oldImageAttachmentIds).')',$oldImageAttachmentIds);
									$adb->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid IN ('.generateQuestionMarks($oldImageAttachmentIds).')',$oldImageAttachmentIds);
									$adb->pquery('DELETE FROM vtiger_crmentity WHERE crmid IN ('.generateQuestionMarks($oldImageAttachmentIds).')',$oldImageAttachmentIds);
								}
							}
						}
						$uploadedFileNames = array();
						if(count($IMG_FILES)){
							foreach($IMG_FILES as $fileIndex => $file) {
								if($file['error'] == 0 && $file['name'] != '' && $file['size'] > 0) {
									if($_REQUEST[$fileIndex.'_hidden'] != '')
										$file['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
									else {
										$file['original_name'] = stripslashes($file['name']);
									}
									$file['original_name'] = str_replace('"','',$file['original_name']);
									$attachmentId = $this->uploadAndSaveFile($this->id,$module,$file,'Image');
									if($attachmentId) {
										$uploadedFileNames[] = $file['name'];
									}
								}
							}
						}
						if(count($uploadedFileNames)) {
							$fldvalue = implode(',',$uploadedFileNames);
						} else {
							$skipUpdateForField = true;
						}
					}
					if (($insertion_mode == 'edit' && $skipUpdateForField == false) || $_REQUEST['imgDeleted']) {
						$skipUpdateForField = false;
						$uploadedFileNames = array();
						$getImageNamesSql = 'SELECT name FROM vtiger_seattachmentsrel INNER JOIN vtiger_attachments ON
						vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid LEFT JOIN '.$table_name.' ON '.
						$table_name.'.'.$tablekey.' = vtiger_seattachmentsrel.crmid WHERE vtiger_seattachmentsrel.crmid = ?';
						$imageNamesRes = $adb->pquery($getImageNamesSql,array($this->id));
						$numOfRows = $adb->num_rows($imageNamesRes);
						for($imgItr = 0;$imgItr < $numOfRows;$imgItr++) {
							$imageName = $adb->query_result($imageNamesRes,$imgItr,'name');
							array_push($uploadedFileNames, decode_html($imageName));
						}
						$fldvalue = implode(',',$uploadedFileNames);
					}
				}elseif ($uitype == 61 && count($_FILES)) {
					if($module == "ModComments") {
						$UPLOADED_FILES = $_FILES[$fieldname];
						foreach($UPLOADED_FILES as $fileIndex => $file) {
							if($file['error'] == 0 && $file['name'] != '' && $file['size'] > 0) {
								if($_REQUEST[$fileindex.'_hidden'] != '') {
									$file['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
								} else {
									$file['original_name'] = stripslashes($file['name']);
								}
								$file['original_name'] = str_replace('"','',$file['original_name']);
								$attachmentId = $this->uploadAndSaveFile($this->id,$module,$file);
								if($attachmentId) {
									$uploadedFileNames[] = $attachmentId;
								}
							} 
						}
						if(count($uploadedFileNames)) {
							$fldvalue = implode(',',$uploadedFileNames);
						} else {
							$skipUpdateForField = true;
						}
					} else {
						$file = $_FILES[$fieldname];
						if($file['error'] == 0 && $file['name'] != '' && $file['size'] > 0) {
							$attachmentId = $this->uploadAndSaveFile($this->id,$module,$file);
							if($attachmentId) $fldvalue = $attachmentId;
						} else {
							$skipUpdateForField = true;
						}
					}
				} else {
					$fldvalue = $this->column_fields[$fieldname];
				}
				if ($uitype != 33 && $uitype != 8)
					$fldvalue = from_html($fldvalue, ($insertion_mode == 'edit') ? true : false);
			} else {
				$fldvalue = '';
			}
			if ($fldvalue == '') {
				$fldvalue = $this->get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype);
			}

			if ($insertion_mode == 'edit') {
				if ($uitype != 4 && !$skipUpdateForField) {
					array_push($update, $columname . "=?");
					array_push($update_params, $fldvalue);
					array_push($updateColumnNames, $columname);
					$updateFieldNameColumnNameMap[$fieldname]=$columname;
				}
			} else {
				array_push($column, $columname);
				array_push($value, $fldvalue);
			}
		}

		if ($insertion_mode == 'edit') {
			//Track the update and update only when needed - vikas
			$updateFieldValues = @array_combine($updateColumnNames, $update_params);
			$changedFields =  $this->column_fields->getChanged();
			if(count($changedFields) > 0) {
				$update = array();
				$update_params = array();
				foreach($changedFields as $field) {
					$fieldColumn = $updateFieldNameColumnNameMap[$field];
					if(@array_key_exists($fieldColumn, $updateFieldValues)) {
						array_push($update, $fieldColumn.'=?');
						array_push($update_params, $updateFieldValues[$fieldColumn]);
					}
				}
				if (count($update) > 0) {
					$sql1 = "UPDATE $table_name SET " . implode(",", $update) . " WHERE " . $this->tab_name_index[$table_name] . "=?";
					array_push($update_params, $this->id);
					if(Vtiger_Functions::isUserSpecificFieldTable($table_name, $module)){
						$sql1 .= ' AND userid = ?';
						array_push($update_params, $current_user->id);
					}
					$adb->pquery($sql1, $update_params);
				}
			}
		} else {
			$sql1 = "insert into $table_name(" . implode(",", $column) . ") values(" . generateQuestionMarks($value) . ")";
			$adb->pquery($sql1, $value);
		}
	}

	/** Function to delete a record in the specifed table
	 * @param $table_name -- table name:: Type varchar
	 * The function will delete a record .The id is obtained from the class variable $this->id and the columnname got from $this->tab_name_index[$table_name]
	 */
	function deleteRelation($table_name) {
		global $adb;
        $table_name = Vtiger_Util_Helper::validateStringForSql($table_name);
		$check_query = "select * from $table_name where " . $this->tab_name_index[$table_name] . "=?";
		$check_result = $adb->pquery($check_query, array($this->id));
		$num_rows = $adb->num_rows($check_result);

		if ($num_rows == 1) {
			$del_query = "DELETE from $table_name where " . $this->tab_name_index[$table_name] . "=?";
			$adb->pquery($del_query, array($this->id));
		}
	}

	/** Function to attachment filename of the given entity
	 * @param $notesid -- crmid:: Type Integer
	 * The function will get the attachmentsid for the given entityid from vtiger_seattachmentsrel table and get the attachmentsname from vtiger_attachments table
	 * returns the 'filename'
	 */
	function getOldFileName($notesid) {
		global $log;
		$log->info("in getOldFileName  " . $notesid);
		global $adb;
		$query1 = "select * from vtiger_seattachmentsrel where crmid=?";
		$result = $adb->pquery($query1, array($notesid));
		$noofrows = $adb->num_rows($result);
		if ($noofrows != 0)
			$attachmentid = $adb->query_result($result, 0, 'attachmentsid');
		if ($attachmentid != '') {
			$query2 = "select * from vtiger_attachments where attachmentsid=?";
			$filename = $adb->query_result($adb->pquery($query2, array($attachmentid)), 0, 'name');
		}
		return $filename;
	}

	/**
	 * Function returns the column alias for a field
	 * @param <Array> $fieldinfo - field information
	 * @return <String> field value
	 */
	protected function createColumnAliasForField($fieldinfo) {
		return strtolower($fieldinfo['tablename'] . $fieldinfo['fieldname']);
	}

	/**
	 * Retrieve record information of the module
	 * @param <Integer> $record - crmid of record
	 * @param <String> $module - module name
	 */
	function retrieve_entity_info($record, $module, $allowDeleted = false) {
		global $adb, $log, $app_strings, $current_user;

		// INNER JOIN is desirable if all dependent table has entries for the record.
		// LEFT JOIN is desired if the dependent tables does not have entry.
		$join_type = 'LEFT JOIN';

		// Tables which has multiple rows for the same record
		// will be skipped in record retrieve - need to be taken care separately.
		$multirow_tables = NULL;
		if (isset($this->multirow_tables)) {
			$multirow_tables = $this->multirow_tables;
		} else {
			$multirow_tables = array(
				'vtiger_campaignrelstatus',
				'vtiger_attachments',
				//'vtiger_inventoryproductrel',
				//'vtiger_cntactivityrel',
				'vtiger_email_track'
			);
		}

		// Lookup module field cache
		if($module == 'Calendar' || $module == 'Events') {
			getColumnFields('Calendar');
			$cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module('Events');
			$cachedCalendarFields = VTCacheUtils::lookupFieldInfo_Module('Calendar');
			$cachedModuleFields = array_merge($cachedEventsFields, $cachedCalendarFields);
		} else {
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		}
		if ($cachedModuleFields === false) {
			// Pull fields and cache for further use
			$tabid = getTabid($module);

			$sql0 = "SELECT fieldname, fieldid, fieldlabel, columnname, tablename, uitype, typeofdata,presence FROM vtiger_field WHERE tabid=?";
			// NOTE: Need to skip in-active fields which we will be done later.
			$result0 = $adb->pquery($sql0, array($tabid));
			if ($adb->num_rows($result0)) {
				while ($resultrow = $adb->fetch_array($result0)) {
					// Update cache
					VTCacheUtils::updateFieldInfo(
						$tabid, $resultrow['fieldname'], $resultrow['fieldid'], $resultrow['fieldlabel'], $resultrow['columnname'], $resultrow['tablename'], $resultrow['uitype'], $resultrow['typeofdata'], $resultrow['presence']
					);
				}
				// Get only active field information
				$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
			}
		}

		if ($cachedModuleFields) {
			$column_clause = '';
			$from_clause   = '';
			$where_clause  = '';
			$limit_clause  = ' LIMIT 1'; // to eliminate multi-records due to table joins.

			$params = array();
			$required_tables = $this->tab_name_index; // copies-on-write

			foreach ($cachedModuleFields as $fieldinfo) {
				if (in_array($fieldinfo['tablename'], $multirow_tables)) {
					continue;
				}
				// Added to avoid picking shipping tax fields for Inventory modules, the shipping tax detail are stored in vtiger_inventoryshippingrel
				// table, but in vtiger_field table we have set tablename as vtiger_inventoryproductrel.
				if(($module == 'Invoice' || $module == 'Quotes' || $module == 'SalesOrder' || $module == 'PurchaseOrder')
						&& stripos($fieldinfo['columnname'], 'shtax') !== false) {
					continue;
				}

				// Alias prefixed with tablename+fieldname to avoid duplicate column name across tables
				// fieldname are always assumed to be unique for a module
				$column_clause .=  $fieldinfo['tablename'].'.'.$fieldinfo['columnname'].' AS '.$this->createColumnAliasForField($fieldinfo).',';
			}
			$column_clause .= 'vtiger_crmentity.deleted, vtiger_crmentity.label';

			if (isset($required_tables['vtiger_crmentity'])) {
				$from_clause  = ' vtiger_crmentity';
				unset($required_tables['vtiger_crmentity']);
				foreach ($required_tables as $tablename => $tableindex) {
					if (in_array($tablename, $multirow_tables)) {
						// Avoid multirow table joins.
						continue;
					}
					$joinCondition = "($tablename.$tableindex = vtiger_crmentity.crmid ";
					if($current_user && Vtiger_Functions::isUserSpecificFieldTable($tablename, $module)) {
						$joinCondition .= " AND $tablename.userid = ".$current_user->id;
					}
					$joinCondition .= " )";
					$from_clause .= sprintf(' %s %s ON %s', $join_type,
						$tablename, $joinCondition);
				}
			}

			$where_clause .= ' vtiger_crmentity.crmid=?';
			$params[] = $record;

			$sql = sprintf('SELECT %s FROM %s WHERE %s %s', $column_clause, $from_clause, $where_clause, $limit_clause);

			$result = $adb->pquery($sql, $params);
			// initialize the object
			$this->column_fields = new TrackableObject();

			if (!$result || $adb->num_rows($result) < 1) {
				throw new Exception($app_strings['LBL_RECORD_NOT_FOUND'], -1);
			} else {
				$resultrow = $adb->query_result_rowdata($result);
				if (!$allowDeleted) {
					if (!empty($resultrow['deleted'])) {
						throw new Exception($app_strings['LBL_RECORD_DELETE'], 1);
					}
				}
				if(!empty($resultrow['label'])){
					$this->column_fields['label'] = $resultrow['label'];
				} else {
					// added to compute label needed in event handlers
					$entityFields = Vtiger_Functions::getEntityModuleInfo($module);
					if(!empty($entityFields['fieldname'])) {
						$entityFieldNames  = explode(',', $entityFields['fieldname']);
						if(count($entityFieldNames) > 1) {
							 $this->column_fields['label'] = $resultrow[$entityFields['tablename'].$entityFieldNames[0]].' '.$resultrow[$entityFields['tablename'].$entityFieldNames[1]];
						} else {
							$this->column_fields['label'] = $resultrow[$entityFields['tablename'].$entityFieldNames[0]];
						}
					}
				}
				foreach ($cachedModuleFields as $fieldinfo) {
					$fieldvalue = '';
					$fieldkey = $this->createColumnAliasForField($fieldinfo);
					//Note : value is retrieved with a tablename+fieldname as we are using alias while building query
					if (isset($resultrow[$fieldkey])) {
						$fieldvalue = $resultrow[$fieldkey];
					}
					$this->column_fields[$fieldinfo['fieldname']] = $fieldvalue;
				}
			}
		}
        
        //adding tags for vtws_retieve
        $tagsList = Vtiger_Tag_Model::getAllAccessible($current_user->id, $module, $record);
        $tags = array();
        foreach($tagsList as $tag) {
            $tags[] = $tag->getName();
        }
        $this->column_fields['tags'] = (count($tags) > 0) ? implode(',',$tags) : '';

		$this->column_fields['record_id'] = $record;
        $this->id = $record;
		$this->column_fields['record_module'] = $module;
		$this->column_fields->startTracking();
	}

	/** Function to saves the values in all the tables mentioned in the class variable $tab_name for the specified module
	 * @param $module -- module:: Type varchar
	 */
	function save($module_name, $fileid = '') {
		global $log,$adb;
		$log->debug("module name is " . $module_name);

		//Event triggering code
		require_once("include/events/include.inc");

		//In Bulk mode stop triggering events
		if(!self::isBulkSaveMode()) {
			$em = new VTEventsManager($adb);
			// Initialize Event trigger cache
			$em->initTriggerCache();
			$entityData = VTEntityData::fromCRMEntity($this);

			$em->triggerEvent("vtiger.entity.beforesave.modifiable", $entityData);
			$em->triggerEvent("vtiger.entity.beforesave", $entityData);
			$em->triggerEvent("vtiger.entity.beforesave.final", $entityData);
        }
		//Event triggering code ends

		//GS Save entity being called with the modulename as parameter
		$this->saveentity($module_name, $fileid);

		if($em) {
			//Event triggering code
			$em->triggerEvent("vtiger.entity.aftersave", $entityData);
			$em->triggerEvent("vtiger.entity.aftersave.final", $entityData);
			//Event triggering code ends
		}
	}

	function process_list_query($query, $row_offset, $limit = -1, $max_per_page = -1) {
		global $list_max_entries_per_page;
		$this->log->debug("process_list_query: " . $query);
		if (!empty($limit) && $limit != -1) {
			$result = & $this->db->limitQuery($query, $row_offset + 0, $limit, true, "Error retrieving $this->object_name list: ");
		} else {
			$result = & $this->db->query($query, true, "Error retrieving $this->object_name list: ");
		}

		$list = Array();
		if ($max_per_page == -1) {
			$max_per_page = $list_max_entries_per_page;
		}
		$rows_found = $this->db->getRowCount($result);

		$this->log->debug("Found $rows_found " . $this->object_name . "s");

		$previous_offset = $row_offset - $max_per_page;
		$next_offset = $row_offset + $max_per_page;

		if ($rows_found != 0) {

			// We have some data.

			for ($index = $row_offset, $row = $this->db->fetchByAssoc($result, $index); $row && ($index < $row_offset + $max_per_page || $max_per_page == -99); $index++, $row = $this->db->fetchByAssoc($result, $index)) {


				foreach ($this->list_fields as $entry) {

					foreach ($entry as $key => $field) { // this will be cycled only once
						if (isset($row[$field])) {
							$this->column_fields[$this->list_fields_names[$key]] = $row[$field];


							$this->log->debug("$this->object_name({$row['id']}): " . $field . " = " . $this->$field);
						} else {
							$this->column_fields[$this->list_fields_names[$key]] = "";
						}
					}
				}


				//$this->db->println("here is the bug");


				$list[] = clone($this); //added by Richie to support PHP5
			}
		}

		$response = Array();
		$response['list'] = $list;
		$response['row_count'] = $rows_found;
		$response['next_offset'] = $next_offset;
		$response['previous_offset'] = $previous_offset;

		return $response;
	}

	function process_full_list_query($query) {
		$this->log->debug("CRMEntity:process_full_list_query");
		$result = & $this->db->query($query, false);
		//$this->log->debug("CRMEntity:process_full_list_query: result is ".$result);


		if ($this->db->getRowCount($result) > 0) {

			//	$this->db->println("process_full mid=".$this->table_index." mname=".$this->module_name);
			// We have some data.
			while ($row = $this->db->fetchByAssoc($result)) {
				$rowid = $row[$this->table_index];

				if (isset($rowid))
					$this->retrieve_entity_info($rowid, $this->module_name);
				else
					$this->db->println("rowid not set unable to retrieve");



				//clone function added to resolvoe PHP5 compatibility issue in Dashboards
				//If we do not use clone, while using PHP5, the memory address remains fixed but the
				//data gets overridden hence all the rows that come in bear the same value. This in turn
//provides a wrong display of the Dashboard graphs. The data is erroneously shown for a specific month alone
//Added by Richie
				$list[] = clone($this); //added by Richie to support PHP5
			}
		}

		if (isset($list))
			return $list;
		else
			return null;
	}

	/** This function should be overridden in each module.  It marks an item as deleted.
	 * If it is not overridden, then marking this type of item is not allowed
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function mark_deleted($id) {
		global $current_user;
		$date_var = date("Y-m-d H:i:s");
		$query = "UPDATE vtiger_crmentity set deleted=1,modifiedtime=?,modifiedby=? where crmid=?";
		$this->db->pquery($query, array($this->db->formatDate($date_var, true), $current_user->id, $id), true, "Error marking record deleted: ");
	}

	function retrieve_by_string_fields($fields_array, $encode = true) {
		$where_clause = $this->get_where($fields_array);

		$query = "SELECT * FROM $this->table_name $where_clause";
		$this->log->debug("Retrieve $this->object_name: " . $query);
		$result = & $this->db->requireSingleResult($query, true, "Retrieving record $where_clause:");
		if (empty($result)) {
			return null;
		}

		$row = $this->db->fetchByAssoc($result, -1, $encode);

		foreach ($this->column_fields as $field) {
			if (isset($row[$field])) {
				$this->$field = $row[$field];
			}
		}
		return $this;
	}

	// this method is called during an import before inserting a bean
	// define an associative array called $special_fields
	// the keys are user defined, and don't directly map to the bean's vtiger_fields
	// the value is the method name within that bean that will do extra
	// processing for that vtiger_field. example: 'full_name'=>'get_names_from_full_name'

	function process_special_fields() {
		foreach ($this->special_functions as $func_name) {
			if (method_exists($this, $func_name)) {
				$this->$func_name();
			}
		}
	}

	/**
	 * Function to check if the custom vtiger_field vtiger_table exists
	 * return true or false
	 */
	function checkIfCustomTableExists($tablename) {
		global $adb;
		$query = "select * from " . $adb->sql_escape_string($tablename);
		$result = $this->db->pquery($query, array());
		$testrow = $this->db->num_fields($result);
		if ($testrow > 1) {
			$exists = true;
		} else {
			$exists = false;
		}
		return $exists;
	}

	/**
	 * function to construct the query to fetch the custom vtiger_fields
	 * return the query to fetch the custom vtiger_fields
	 */
	function constructCustomQueryAddendum($tablename, $module) {
		global $adb;
		$tabid = getTabid($module);
		$sql1 = "select columnname,fieldlabel from vtiger_field where generatedtype=2 and tabid=? and vtiger_field.presence in (0,2)";
		$result = $adb->pquery($sql1, array($tabid));
		$numRows = $adb->num_rows($result);
		$sql3 = "select ";
		for ($i = 0; $i < $numRows; $i++) {
			$columnName = $adb->query_result($result, $i, "columnname");
			$fieldlabel = $adb->query_result($result, $i, "fieldlabel");
			//construct query as below
			if ($i == 0) {
				$sql3 .= $tablename . "." . $columnName . " '" . $fieldlabel . "'";
			} else {
				$sql3 .= ", " . $tablename . "." . $columnName . " '" . $fieldlabel . "'";
			}
		}
		if ($numRows > 0) {
			$sql3 = $sql3 . ',';
		}
		return $sql3;
	}

	/**
	 * This function returns a full (ie non-paged) list of the current object type.
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function get_full_list($order_by = "", $where = "") {
		$this->log->debug("get_full_list:  order_by = '$order_by' and where = '$where'");
		$query = $this->create_list_query($order_by, $where);
		return $this->process_full_list_query($query);
	}

	/**
	 * Track the viewing of a detail record.  This leverages get_summary_text() which is object specific
	 * params $user_id - The user that is viewing the record.
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function track_view($user_id, $current_module, $id = '') {
		$this->log->debug("About to call vtiger_tracker (user_id, module_name, item_id)($user_id, $current_module, $this->id)");

		$tracker = new Tracker();
		$tracker->track_view($user_id, $current_module, $id, '');
	}

	/**
	 * Function to get the column value of a field when the field value is empty ''
	 * @param $columnname -- Column name for the field
	 * @param $fldvalue -- Input value for the field taken from the User
	 * @param $fieldname -- Name of the Field
	 * @param $uitype -- UI type of the field
	 * @return Column value of the field.
	 */
	function get_column_value($columnname, $fldvalue, $fieldname, $uitype, $datatype = '') {
		global $log;
		$log->debug("Entering function get_column_value ($columnname, $fldvalue, $fieldname, $uitype, $datatype='')");

		// Added for the fields of uitype '57' which has datatype mismatch in crmentity table and particular entity table
		if ($uitype == 57 && $fldvalue == '') {
			return 0;
		}
		if (is_uitype($uitype, "_date_") && $fldvalue == '' || $uitype == '14') {
			return null;
		}
		if ($datatype == 'I' || $datatype == 'N' || $datatype == 'NN') {
			return 0;
		}
		$log->debug("Exiting function get_column_value");
		return $fldvalue;
	}

	/**
	 * Function to make change to column fields, depending on the current user's accessibility for the fields
	 */
	function apply_field_security($moduleName = '') {
		global $current_user, $currentModule;

		if($moduleName == '') {
			$moduleName = $currentModule;
		}
		require_once('include/utils/UserInfoUtil.php');
		foreach ($this->column_fields as $fieldname => $fieldvalue) {
			$reset_value = false;
			if (getFieldVisibilityPermission($moduleName, $current_user->id, $fieldname) != '0')
				$reset_value = true;

			if ($fieldname == "record_id" || $fieldname == "record_module")
				$reset_value = false;

			/*
			  if (isset($this->additional_column_fields) && in_array($fieldname, $this->additional_column_fields) == true)
			  $reset_value = false;
			 */

			if ($reset_value == true)
				$this->column_fields[$fieldname] = "";
		}
	}

	/**
	 * Function invoked during export of module record value.
	 */
	function transform_export_value($key, $value) {
		// NOTE: The sub-class can override this function as required.
		return $value;
	}

	/**
	 * Function to initialize the importable fields array, based on the User's accessibility to the fields
	 */
	function initImportableFields($module) {
		global $current_user, $adb;
		require_once('include/utils/UserInfoUtil.php');

		$skip_uitypes = array('4'); // uitype 4 is for Mod numbers
		// Look at cache if the fields information is available.
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);

		if ($cachedModuleFields === false) {
			getColumnFields($module); // This API will initialize the cache as well
			// We will succeed now due to above function call
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		}

		$colf = Array();

		if ($cachedModuleFields) {
			foreach ($cachedModuleFields as $fieldinfo) {
				// Skip non-supported fields
				if (in_array($fieldinfo['uitype'], $skip_uitypes)) {
					continue;
				} else {
					$colf[$fieldinfo['fieldname']] = $fieldinfo['uitype'];
				}
			}
		}

		foreach ($colf as $key => $value) {
			if (getFieldVisibilityPermission($module, $current_user->id, $key, 'readwrite') == '0')
				$this->importable_fields[$key] = $value;
		}
	}

	/** Function to initialize the required fields array for that particular module */
	function initRequiredFields($module) {
		global $adb;

		$tabid = getTabId($module);
		$sql = "select * from vtiger_field where tabid= ? and typeofdata like '%M%' and uitype not in ('53','70') and vtiger_field.presence in (0,2)";
		$result = $adb->pquery($sql, array($tabid));
		$numRows = $adb->num_rows($result);
		for ($i = 0; $i < $numRows; $i++) {
			$fieldName = $adb->query_result($result, $i, "fieldname");
			$this->required_fields[$fieldName] = 1;
		}
	}

	/** Function to delete an entity with given Id */
	function trash($module, $id) {
		global $log, $current_user, $adb;

		if(!self::isBulkSaveMode()) {
			require_once("include/events/include.inc");
			$em = new VTEventsManager($adb);

			// Initialize Event trigger cache
			$em->initTriggerCache();

			$entityData = VTEntityData::fromEntityId($adb, $id);

			$em->triggerEvent("vtiger.entity.beforedelete", $entityData);
		}
		$this->mark_deleted($id);
		$this->unlinkDependencies($module, $id);

		require_once('libraries/freetag/freetag.class.php');
		$freetag = new freetag();
		$freetag->delete_all_object_tags_for_user($current_user->id, $id);

		$sql_recentviewed = 'DELETE FROM vtiger_tracker WHERE user_id = ? AND item_id = ?';
		$this->db->pquery($sql_recentviewed, array($current_user->id, $id));

		if($em){
			$em->triggerEvent("vtiger.entity.afterdelete", $entityData);
		}
	}

	/** Function to unlink all the dependent entities of the given Entity by Id */
	function unlinkDependencies($module, $id) {
		global $log;

		$fieldRes = $this->db->pquery('SELECT tabid, tablename, columnname FROM vtiger_field WHERE fieldid IN (
			SELECT fieldid FROM vtiger_fieldmodulerel WHERE relmodule=?)', array($module));
		$numOfFields = $this->db->num_rows($fieldRes);
		for ($i = 0; $i < $numOfFields; $i++) {
			$tabId = $this->db->query_result($fieldRes, $i, 'tabid');
			$tableName = $this->db->query_result($fieldRes, $i, 'tablename');
			$columnName = $this->db->query_result($fieldRes, $i, 'columnname');

			$relatedModule = vtlib_getModuleNameById($tabId);
			$focusObj = CRMEntity::getInstance($relatedModule);

			//Backup Field Relations for the deleted entity
			$targetTableColumn = $focusObj->tab_name_index[$tableName];

			$relQuery = "SELECT $targetTableColumn FROM $tableName WHERE $columnName=?";
			$relResult = $this->db->pquery($relQuery, array($id));
			$numOfRelRecords = $this->db->num_rows($relResult);
			if ($numOfRelRecords > 0) {
				$recordIdsList = array();
				for ($k = 0; $k < $numOfRelRecords; $k++) {
					$recordIdsList[] = $this->db->query_result($relResult, $k, $focusObj->table_index);
				}
				if(count($recordIdsList) > 0) {
					$params = array($id, RB_RECORD_UPDATED, $tableName, $columnName, $focusObj->table_index, implode(",", $recordIdsList));
					$this->db->pquery('INSERT INTO vtiger_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
				}
			}
		}
	}

	/** Function to unlink an entity with given Id from another entity */
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log, $currentModule;
		if($return_module == 'Documents') {
			$sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} else {
			$query = 'DELETE FROM vtiger_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($query, $params);

			$fieldRes = $this->db->pquery('SELECT tabid, tablename, columnname FROM vtiger_field WHERE fieldid IN (SELECT fieldid FROM vtiger_fieldmodulerel WHERE module=? AND relmodule=?)', array($currentModule, $return_module));
			$numOfFields = $this->db->num_rows($fieldRes);
			for ($i = 0; $i < $numOfFields; $i++) {
				$tabId = $this->db->query_result($fieldRes, $i, 'tabid');
				$tableName = $this->db->query_result($fieldRes, $i, 'tablename');
				$columnName = $this->db->query_result($fieldRes, $i, 'columnname');

				$relatedModule = vtlib_getModuleNameById($tabId);
				$focusObj = CRMEntity::getInstance($relatedModule);

				$updateQuery = "UPDATE $tableName SET $columnName=? WHERE $columnName=? AND $focusObj->table_index=?";
				$updateParams = array(null, $return_id, $id);
				$this->db->pquery($updateQuery, $updateParams);
			}
		}
	}

	/** Function to restore a deleted record of specified module with given crmid
	 * @param $module -- module name:: Type varchar
	 * @param $entity_ids -- list of crmids :: Array
	 */
	function restore($module, $id) {
		global $current_user, $adb;

		$this->db->println("TRANS restore starts $module");
		$this->db->startTransaction();

		//Event triggering code
		require_once("include/events/include.inc");
		global $adb;
		$em = new VTEventsManager($adb);

		// Initialize Event trigger cache
		$em->initTriggerCache();

		$this->id = $id;
		$entityData = VTEntityData::getInstanceByDeletedEntityId($adb, $id, $module);
		$em->triggerEvent("vtiger.entity.beforerestore", $entityData);

		$date_var = date("Y-m-d H:i:s");
		$query = 'UPDATE vtiger_crmentity SET deleted=0,modifiedtime=?,modifiedby=? WHERE crmid = ?';
		$this->db->pquery($query, array($this->db->formatDate($date_var, true), $current_user->id, $id), true, "Error restoring records :");
		//Restore related entities/records
		$this->restoreRelatedRecords($module, $id);

		//Event triggering code
		$em->triggerEvent("vtiger.entity.afterrestore", $entityData);
		//Event triggering code ends

		$this->db->completeTransaction();
		$this->db->println("TRANS restore ends");
	}

	/** Function to restore all the related records of a given record by id */
	function restoreRelatedRecords($module, $record) {

		$result = $this->db->pquery('SELECT * FROM vtiger_relatedlists_rb WHERE entityid = ?', array($record));
		$numRows = $this->db->num_rows($result);
		for ($i = 0; $i < $numRows; $i++) {
			$action = $this->db->query_result($result, $i, "action");
			$rel_table = $this->db->query_result($result, $i, "rel_table");
			$rel_column = $this->db->query_result($result, $i, "rel_column");
			$ref_column = $this->db->query_result($result, $i, "ref_column");
			$related_crm_ids = $this->db->query_result($result, $i, "related_crm_ids");

			if (strtoupper($action) == RB_RECORD_UPDATED) {
				$related_ids = explode(",", $related_crm_ids);
				if ($rel_table == 'vtiger_crmentity' && $rel_column == 'deleted') {
					$sql = "UPDATE $rel_table set $rel_column = 0 WHERE $ref_column IN (" . generateQuestionMarks($related_ids) . ")";
					$this->db->pquery($sql, array($related_ids));
				} else {
					$sql = "UPDATE $rel_table set $rel_column = ? WHERE $rel_column = 0 AND $ref_column IN (" . generateQuestionMarks($related_ids) . ")";
					$this->db->pquery($sql, array($record, $related_ids));
				}
			} elseif (strtoupper($action) == RB_RECORD_DELETED) {
				if ($rel_table == 'vtiger_seproductsrel') {
					$sql = "INSERT INTO $rel_table($rel_column, $ref_column, 'setype') VALUES (?,?,?)";
					$this->db->pquery($sql, array($record, $related_crm_ids, getSalesEntityType($related_crm_ids)));
				} else {
					$sql = "INSERT INTO $rel_table($rel_column, $ref_column) VALUES (?,?)";
					$this->db->pquery($sql, array($record, $related_crm_ids));
				}
			}
		}

		//Clean up the the backup data also after restoring
		$this->db->pquery('DELETE FROM vtiger_relatedlists_rb WHERE entityid = ?', array($record));
	}

	/**
	 * Function to initialize the sortby fields array
	 */
	function initSortByField($module) {
		global $adb, $log;
		$log->debug("Entering function initSortByField ($module)");
		// Define the columnname's and uitype's which needs to be excluded
		$exclude_columns = Array('parent_id', 'quoteid', 'vendorid', 'access_count');
		$exclude_uitypes = Array();

		$tabid = getTabId($module);
		if ($module == 'Calendar') {
			$tabid = array('9', '16');
		}
		$sql = "SELECT columnname FROM vtiger_field " .
				" WHERE (fieldname not like '%\_id' OR fieldname in ('assigned_user_id'))" .
				" AND tabid in (" . generateQuestionMarks($tabid) . ") and vtiger_field.presence in (0,2)";
		$params = array($tabid);
		if (count($exclude_columns) > 0) {
			$sql .= " AND columnname NOT IN (" . generateQuestionMarks($exclude_columns) . ")";
			array_push($params, $exclude_columns);
		}
		if (count($exclude_uitypes) > 0) {
			$sql .= " AND uitype NOT IN (" . generateQuestionMarks($exclude_uitypes) . ")";
			array_push($params, $exclude_uitypes);
		}
		$result = $adb->pquery($sql, $params);
		$num_rows = $adb->num_rows($result);
		for ($i = 0; $i < $num_rows; $i++) {
			$columnname = $adb->query_result($result, $i, 'columnname');
			if (in_array($columnname, $this->sortby_fields))
				continue;
			else
				$this->sortby_fields[] = $columnname;
		}
		if ($tabid == 21 or $tabid == 22)
			$this->sortby_fields[] = 'crmid';
		$log->debug("Exiting initSortByField");
	}

	/* Function to set the Sequence string and sequence number starting value */

	function setModuleSeqNumber($mode, $module, $req_str = '', $req_no = '') {
		global $adb;
		//when we configure the invoice number in Settings this will be used
		if ($mode == "configure" && $req_no != '') {
			$check = $adb->pquery("select cur_id from vtiger_modentity_num where semodule=? and prefix = ?", array($module, $req_str));
			if ($adb->num_rows($check) == 0) {
				$numid = $adb->getUniqueId("vtiger_modentity_num");
				$active = $adb->pquery("select num_id from vtiger_modentity_num where semodule=? and active=1", array($module));
				$adb->pquery("UPDATE vtiger_modentity_num SET active=0 where num_id=?", array($adb->query_result($active, 0, 'num_id')));

				$adb->pquery("INSERT into vtiger_modentity_num values(?,?,?,?,?,?)", array($numid, $module, $req_str, $req_no, $req_no, 1));
				return true;
			} else if ($adb->num_rows($check) != 0) {
				$num_check = $adb->query_result($check, 0, 'cur_id');
				if ($req_no < $num_check) {
					return false;
				} else {
					$adb->pquery("UPDATE vtiger_modentity_num SET active=0 where active=1 and semodule=?", array($module));
					$adb->pquery("UPDATE vtiger_modentity_num SET cur_id=?, active = 1 where prefix=? and semodule=?", array($req_no, $req_str, $module));
					return true;
				}
			}
		} else if ($mode == "increment") {
			//when we save new invoice we will increment the invoice id and write
			$check = $adb->pquery("select cur_id,prefix from vtiger_modentity_num where semodule=? and active = 1", array($module));
			$prefix = $adb->query_result($check, 0, 'prefix');
			$curid = $adb->query_result($check, 0, 'cur_id');
			$prev_inv_no = $prefix . $curid;
			$strip = strlen($curid) - strlen($curid + 1);
			if ($strip < 0)
				$strip = 0;
			$temp = str_repeat("0", $strip);
			$req_no.= $temp . ($curid + 1);
			$adb->pquery("UPDATE vtiger_modentity_num SET cur_id=? where cur_id=? and active=1 AND semodule=?", array($req_no, $curid, $module));
			return decode_html($prev_inv_no);
		}
	}

	// END

	/* Function to check if module sequence numbering is configured for the given module or not */
	function isModuleSequenceConfigured($module) {
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery('SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active = 1', array($module));
		if ($result && $adb->num_rows($result) > 0) {
			return true;
		}
		return false;
	}

	/* Function to get the next module sequence number for a given module */

	function getModuleSeqInfo($module) {
		global $adb;
		$check = $adb->pquery("select cur_id,prefix from vtiger_modentity_num where semodule=? and active = 1", array($module));
		$prefix = $adb->query_result($check, 0, 'prefix');
		$curid = $adb->query_result($check, 0, 'cur_id');
		return array($prefix, $curid);
	}

	// END

	/* Function to check if the mod number already exits */
	function checkModuleSeqNumber($table, $column, $no) {
		global $adb;
        $table = Vtiger_Util_Helper::validateStringForSql($table);
        $column = Vtiger_Util_Helper::validateStringForSql($column);
		$result = $adb->pquery("select " . $adb->sql_escape_string($column) .
				" from " . $adb->sql_escape_string($table) .
				" where " . $adb->sql_escape_string($column) . " = ?", array($no));

		$num_rows = $adb->num_rows($result);

		if ($num_rows > 0)
			return true;
		else
			return false;
	}

	// END

	function updateMissingSeqNumber($module) {
		global $log, $adb;
		$log->debug("Entered updateMissingSeqNumber function");

		vtlib_setup_modulevars($module, $this);

		if (!$this->isModuleSequenceConfigured($module))
			return;

		$tabid = getTabid($module);
		$fieldinfo = $adb->pquery("SELECT tablename, columnname FROM vtiger_field WHERE tabid = ? AND uitype = 4", Array($tabid));

		$returninfo = Array();

		if ($fieldinfo && $adb->num_rows($fieldinfo)) {
			// TODO: We assume the following for module sequencing field
			// 1. There will be only field per module
			// 2. This field is linked to module base table column
			$fld_table = $adb->query_result($fieldinfo, 0, 'tablename');
			$fld_column = $adb->query_result($fieldinfo, 0, 'columnname');

			if ($fld_table == $this->table_name) {
				$records = $adb->pquery("SELECT $this->table_index AS recordid FROM $this->table_name " .
						"WHERE $fld_column = '' OR $fld_column is NULL", array());

				if ($records && $adb->num_rows($records)) {
					$returninfo['totalrecords'] = $adb->num_rows($records);
					$returninfo['updatedrecords'] = 0;

					$modseqinfo = $this->getModuleSeqInfo($module);
					$prefix = $modseqinfo[0];
					$cur_id = $modseqinfo[1];

					$old_cur_id = $cur_id;
					while ($recordinfo = $adb->fetch_array($records)) {
						$value = "$prefix" . "$cur_id";
						$adb->pquery("UPDATE $fld_table SET $fld_column = ? WHERE $this->table_index = ?", Array($value, $recordinfo['recordid']));
						$cur_id = $this->getSequnceNumber($cur_id);
						$returninfo['updatedrecords'] = $returninfo['updatedrecords'] + 1;
					}
					if ($old_cur_id != $cur_id) {
						$adb->pquery("UPDATE vtiger_modentity_num set cur_id=? where semodule=? and active=1", Array($cur_id, $module));
					}
				}
			} else {
				$log->fatal("Updating Missing Sequence Number FAILED! REASON: Field table and module table mismatching.");
			}
		}
		return $returninfo;
	}
    
    function getSequnceNumber($curid){
        $strip = strlen($curid) - strlen($curid + 1);
        if ($strip < 0)
                $strip = 0;
        $temp = str_repeat("0", $strip);
        $req_no = $temp . ($curid + 1);
        return $req_no;
    }

	/* Generic function to get attachments in the related list of a given module */

	function get_attachments($id, $cur_tab_id, $rel_tab_id, $actions = false) {

		global $currentModule, $app_strings, $singlepane_view;
		$this_module = $currentModule;
		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($related_module, $other);

		$singular_modname = vtlib_toSingular($related_module);
		$button = '';
		if ($actions) {
			if (is_string($actions))
				$actions = explode(',', strtoupper($actions));
			if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_SELECT') . " " . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . " " . getTranslatedString($related_module) . "'>&nbsp;";
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				$button .= "<input type='hidden' name='createmode' id='createmode' value='link' />" .
						"<input title='" . getTranslatedString('LBL_ADD_NEW') . " " . getTranslatedString($singular_modname) . "' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
						" value='" . getTranslatedString('LBL_ADD_NEW') . " " . getTranslatedString($singular_modname) . "'>&nbsp;";
			}
		}

		// To make the edit or del link actions to return back to same view.
		if ($singlepane_view == 'true')
			$returnset = "&return_module=$this_module&return_action=DetailView&return_id=$id";
		else
			$returnset = "&return_module=$this_module&return_action=CallRelatedList&return_id=$id";

		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name',
														'first_name'=>'vtiger_users.first_name',), 'Users');
		$query = "select case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name," .
				"'Documents' ActivityType,vtiger_attachments.type  FileType,crm2.modifiedtime lastmodified,vtiger_crmentity.modifiedtime,
				vtiger_seattachmentsrel.attachmentsid attachmentsid, vtiger_crmentity.smownerid smownerid, vtiger_notes.notesid crmid,
				vtiger_notes.notecontent description,vtiger_notes.*
				from vtiger_notes
				inner join vtiger_senotesrel on vtiger_senotesrel.notesid= vtiger_notes.notesid
				left join vtiger_notescf ON vtiger_notescf.notesid= vtiger_notes.notesid
				inner join vtiger_crmentity on vtiger_crmentity.crmid= vtiger_notes.notesid and vtiger_crmentity.deleted=0
				inner join vtiger_crmentity crm2 on crm2.crmid=vtiger_senotesrel.crmid
				LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				left join vtiger_seattachmentsrel  on vtiger_seattachmentsrel.crmid =vtiger_notes.notesid
				left join vtiger_attachments on vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
				left join vtiger_users on vtiger_crmentity.smownerid= vtiger_users.id
				where crm2.crmid=" . $id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null)
			$return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}

	/**
	 * For Record View Notification
	 */
	function isViewed($crmid = false) {
		if (!$crmid) {
			$crmid = $this->id;
		}
		if ($crmid) {
			global $adb;
			$result = $adb->pquery("SELECT viewedtime,modifiedtime,smcreatorid,smownerid,modifiedby FROM vtiger_crmentity WHERE crmid=?", Array($crmid));
			$resinfo = $adb->fetch_array($result);

			$lastviewed = $resinfo['viewedtime'];
			$modifiedon = $resinfo['modifiedtime'];
			$smownerid = $resinfo['smownerid'];
			$smcreatorid = $resinfo['smcreatorid'];
			$modifiedby = $resinfo['modifiedby'];

			if ($modifiedby == '0' && ($smownerid == $smcreatorid)) {
				/** When module record is created * */
				return true;
			} else if ($smownerid == $modifiedby) {
				/** Owner and Modifier as same. * */
				return true;
			} else if ($lastviewed && $modifiedon) {
				/** Lastviewed and Modified time is available. */
				if ($this->__timediff($modifiedon, $lastviewed) > 0)
					return true;
			}
		}
		return false;
	}

	function __timediff($d1, $d2) {
		list($t1_1, $t1_2) = explode(' ', $d1);
		list($t1_y, $t1_m, $t1_d) = explode('-', $t1_1);
		list($t1_h, $t1_i, $t1_s) = explode(':', $t1_2);

		$t1 = mktime($t1_h, $t1_i, $t1_s, $t1_m, $t1_d, $t1_y);

		list($t2_1, $t2_2) = explode(' ', $d2);
		list($t2_y, $t2_m, $t2_d) = explode('-', $t2_1);
		list($t2_h, $t2_i, $t2_s) = explode(':', $t2_2);

		$t2 = mktime($t2_h, $t2_i, $t2_s, $t2_m, $t2_d, $t2_y);

		if ($t1 == $t2)
			return 0;
		return $t2 - $t1;
	}

	function markAsViewed($userid) {
		global $adb;
		$adb->pquery("UPDATE vtiger_crmentity set viewedtime=? WHERE crmid=? AND smownerid=?", Array(date('Y-m-d H:i:s', time()), $this->id, $userid));
	}

	/**
	 * Save the related module record information. Triggered from CRMEntity->saveentity method or updateRelations.php
	 * @param String This module name
	 * @param Integer This module record number
	 * @param String Related module name
	 * @param mixed Integer or Array of related module record number
	 */
	function save_related_module($module, $crmid, $with_module, $with_crmid) {
		global $adb;
		if (!is_array($with_crmid))
			$with_crmid = Array($with_crmid);
		foreach ($with_crmid as $relcrmid) {

			if ($with_module == 'Documents') {
				$checkpresence = $adb->pquery("SELECT crmid FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?", Array($crmid, $relcrmid));
				// Relation already exists? No need to add again
				if ($checkpresence && $adb->num_rows($checkpresence))
					continue;

				$adb->pquery("INSERT INTO vtiger_senotesrel(crmid, notesid) VALUES(?,?)", array($crmid, $relcrmid));
			} else {
				$checkpresence = $adb->pquery("SELECT crmid FROM vtiger_crmentityrel WHERE
					crmid = ? AND module = ? AND relcrmid = ? AND relmodule = ?", Array($crmid, $module, $relcrmid, $with_module));
				// Relation already exists? No need to add again
				if ($checkpresence && $adb->num_rows($checkpresence))
					continue;

				$adb->pquery("INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?,?,?,?)", Array($crmid, $module, $relcrmid, $with_module));
			}
		}
	}

	/**
	 * Delete the related module record information. Triggered from updateRelations.php
	 * @param String This module name
	 * @param Integer This module record number
	 * @param String Related module name
	 * @param mixed Integer or Array of related module record number
	 */
	function delete_related_module($module, $crmid, $with_module, $with_crmid) {
		global $adb;
		if (!is_array($with_crmid))
			$with_crmid = Array($with_crmid);
		foreach ($with_crmid as $relcrmid) {

			if ($with_module == 'Documents') {
				$adb->pquery("DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?", Array($crmid, $relcrmid));
			} else {
				$adb->pquery("DELETE FROM vtiger_crmentityrel WHERE (crmid=? AND module=? AND relcrmid=? AND relmodule=?) OR (relcrmid=? AND relmodule=? AND crmid=? AND module=?)",
					Array($crmid, $module, $relcrmid, $with_module,$crmid, $module, $relcrmid, $with_module));
			}
		}
	}

	/**
	 * Default (generic) function to handle the related list for the module.
	 * NOTE: Vtiger_Module::setRelatedList sets reference to this function in vtiger_relatedlists table
	 * if function name is not explicitly specified.
	 */
	function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions = false) {

		global $currentModule, $app_strings, $singlepane_view;

		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$singular_modname = 'SINGLE_' . $related_module;

		$button = '';
		if ($actions) {
			if (is_string($actions))
				$actions = explode(',', strtoupper($actions));
			if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
				$button .= "<input title='" . getTranslatedString('LBL_SELECT') . " " . getTranslatedString($related_module) . "' class='crmbutton small edit' " .
						" type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\"" .
						" value='" . getTranslatedString('LBL_SELECT') . " " . getTranslatedString($related_module, $related_module) . "'>&nbsp;";
			}
			if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
				$button .= "<input type='hidden' name='createmode' id='createmode' value='link' />" .
						"<input title='" . getTranslatedString('LBL_ADD_NEW') . " " . getTranslatedString($singular_modname) . "' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
						" value='" . getTranslatedString('LBL_ADD_NEW') . " " . getTranslatedString($singular_modname, $related_module) . "'>&nbsp;";
			}
		}

		// To make the edit or del link actions to return back to same view.
		if ($singlepane_view == 'true')
			$returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";
		else
			$returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$query = "SELECT vtiger_crmentity.*, $other->table_name.*";

		$userNameSql = getSqlForNameInDisplayFormat(array('last_name'=>'vtiger_users.last_name',
														'first_name' => 'vtiger_users.first_name'), 'Users');
		$query .= ", CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name";

		$more_relation = '';
		if (!empty($other->related_tables)) {
			foreach ($other->related_tables as $tname => $relmap) {
				$query .= ", $tname.*";

				// Setup the default JOIN conditions if not specified
				if (empty($relmap[1]))
					$relmap[1] = $other->table_name;
				if (empty($relmap[2]))
					$relmap[2] = $relmap[0];
				$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
			}
		}

		$query .= " FROM $other->table_name";
		$query .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $other->table_name.$other->table_index";
		$query .= " INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid)";
		$query .= $more_relation;
		$query .= " LEFT  JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
		$query .= " LEFT  JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " WHERE vtiger_crmentity.deleted = 0 AND (vtiger_crmentityrel.crmid = $id OR vtiger_crmentityrel.relcrmid = $id)";
		if($related_module == 'Leads') {
			$query .= " AND vtiger_leaddetails.converted=0 ";
		}

		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null)
			$return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}


	// フィールドからの関連
	function get_fieldrelated_list($id, $cur_tab_id, $rel_tab_id, $actions = false) {
		global $currentModule, $app_strings, $singlepane_view;

		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$singular_modname = 'SINGLE_' . $related_module;

		$button = '';
		if ($actions) {
		if (is_string($actions))
		$actions = explode(',', strtoupper($actions));
		if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
		$button .= "<input title='" . getTranslatedString('LBL_SELECT') . " " . getTranslatedString($related_module) . "' class='crmbutton small edit' " .
		" type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\"" .
		" value='" . getTranslatedString('LBL_SELECT') . " " . getTranslatedString($related_module, $related_module) . "'>&nbsp;";
		}
		if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
		$button .= "<input type='hidden' name='createmode' id='createmode' value='link' />" .
		"<input title='" . getTranslatedString('LBL_ADD_NEW') . " " . getTranslatedString($singular_modname) . "' class='crmbutton small create'" .
		" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
		" value='" . getTranslatedString('LBL_ADD_NEW') . " " . getTranslatedString($singular_modname, $related_module) . "'>&nbsp;";
		}
		}

		// To make the edit or del link actions to return back to same view.
		if ($singlepane_view == 'true')
		$returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";
		else
		$returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$query = "SELECT vtiger_crmentity.*, $other->table_name.*";

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>'vtiger_users.first_name',
		'last_name' => 'vtiger_users.last_name'), 'Users');
		$query .= ", CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name";

		$more_relation = '';
		if (!empty($other->related_tables)) {
		foreach ($other->related_tables as $tname => $relmap) {
		$query .= ", $tname.*";

		// Setup the default JOIN conditions if not specified
		if (empty($relmap[1]))
		$relmap[1] = $other->table_name;
		if (empty($relmap[2]))
		$relmap[2] = $relmap[0];
		$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
		}
		}

		$query .= " FROM $other->table_name";
		$query .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $other->table_name.$other->table_index";
		//	$query .= " INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid)";
		$query .= $more_relation;
		$query .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " WHERE vtiger_crmentity.deleted = 0 AND $other->table_name.$this->table_index = $id";
		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

		if ($return_value == null)
		$return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	/**
	 * Default (generic) function to handle the dependents list for the module.
	 * NOTE: UI type '10' is used to stored the references to other modules for a given record.
	 * These dependent records can be retrieved through this function.
	 * For eg: A trouble ticket can be related to an Account or a Contact.
	 * From a given Contact/Account if we need to fetch all such dependent trouble tickets, get_dependents_list function can be used.
	 */
	function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $relationId) {
		global $currentModule, $app_strings, $singlepane_view, $current_user;

		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$singular_modname = 'SINGLE_' . $related_module;

		$button = '';

		// To make the edit or del link actions to return back to same view.
		if ($singlepane_view == 'true')
			$returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";
		else
			$returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$return_value = null;
		$relationFieldSql = "SELECT relationfieldid FROM vtiger_relatedlists WHERE relation_id=?";
		$result = $this->db->pquery($relationFieldSql,array($relationId));
		$num_rows = $this->db->num_rows($result);
		$relationFieldId = null;
		if($num_rows > 0) {
			$relationFieldId = $this->db->query_result($result,0,'relationfieldid');
		}
		if(empty($relationFieldId)) {
			$dependentFieldSql = $this->db->pquery("SELECT tabid, fieldname, columnname,tablename FROM vtiger_field WHERE uitype='10' AND" .
					" fieldid IN (SELECT fieldid FROM vtiger_fieldmodulerel WHERE relmodule=? AND module=?)", array($currentModule, $related_module));
		} else {
			$dependentFieldSql = $this->db->pquery("SELECT tabid, fieldname, columnname,tablename FROM vtiger_field WHERE uitype='10' AND" .
					" fieldid IN (SELECT relationfieldid FROM vtiger_relatedlists WHERE relation_id=?)", array($relationId));
		}
		$numOfFields = $this->db->num_rows($dependentFieldSql);

		if ($numOfFields > 0) {
			$dependentColumn = $this->db->query_result($dependentFieldSql, 0, 'columnname');
			$dependentField = $this->db->query_result($dependentFieldSql, 0, 'fieldname');
			$dependentTableName = $this->db->query_result($dependentFieldSql, 0, 'tablename');

			$button .= '<input type="hidden" name="' . $dependentColumn . '" id="' . $dependentColumn . '" value="' . $id . '">';
			$button .= '<input type="hidden" name="' . $dependentColumn . '_type" id="' . $dependentColumn . '_type" value="' . $currentModule . '">';

			$query = "SELECT vtiger_crmentity.*, $other->table_name.*";

			$userNameSql = getSqlForNameInDisplayFormat(array('last_name'=>'vtiger_users.last_name',
														'first_name' => 'vtiger_users.first_name'), 'Users');
			$query .= ", CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name";

			$more_relation = '';
			if (!empty($other->related_tables)) {
				foreach ($other->related_tables as $tname => $relmap) {
					$query .= ", $tname.*";

					// Setup the default JOIN conditions if not specified
					if (empty($relmap[1]))
						$relmap[1] = $other->table_name;
					if (empty($relmap[2]))
						$relmap[2] = $relmap[0];
					$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
				}
			}

			$query .= " FROM $other->table_name";
			$query .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $other->table_name.$other->table_index";
			$query .= $more_relation;
			$query .= " INNER JOIN $this->table_name AS $this->table_name$this->moduleName ON $this->table_name$this->moduleName.$this->table_index = $dependentTableName.$dependentColumn";			
			$query .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
			$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

			$query .= " WHERE vtiger_crmentity.deleted = 0 AND $this->table_name$this->moduleName.$this->table_index = $id";
			if($related_module == 'Leads') {
				$query .= " AND vtiger_leaddetails.converted=0 ";
			}
			$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);
		}
		if ($return_value == null)
			$return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb, $log;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");
		foreach ($transferEntityIds as $transferId) {

			// Pick the records related to the entity to be transfered, but do not pick the once which are already related to the current entity.
			$relatedRecords = $adb->pquery("SELECT relcrmid, relmodule FROM vtiger_crmentityrel WHERE crmid=? AND module=?" .
					" AND relcrmid NOT IN (SELECT relcrmid FROM vtiger_crmentityrel WHERE crmid=? AND module=?)", array($transferId, $module, $entityId, $module));
			$numOfRecords = $adb->num_rows($relatedRecords);
			for ($i = 0; $i < $numOfRecords; $i++) {
				$relcrmid = $adb->query_result($relatedRecords, $i, 'relcrmid');
				$relmodule = $adb->query_result($relatedRecords, $i, 'relmodule');
				$adb->pquery("UPDATE vtiger_crmentityrel SET crmid=? WHERE relcrmid=? AND relmodule=? AND crmid=? AND module=?", array($entityId, $relcrmid, $relmodule, $transferId, $module));
			}

			// Pick the records to which the entity to be transfered is related, but do not pick the once to which current entity is already related.
			$parentRecords = $adb->pquery("SELECT crmid, module FROM vtiger_crmentityrel WHERE relcrmid=? AND relmodule=?" .
					" AND crmid NOT IN (SELECT crmid FROM vtiger_crmentityrel WHERE relcrmid=? AND relmodule=?)", array($transferId, $module, $entityId, $module));
			$numOfRecords = $adb->num_rows($parentRecords);
			for ($i = 0; $i < $numOfRecords; $i++) {
				$parcrmid = $adb->query_result($parentRecords, $i, 'crmid');
				$parmodule = $adb->query_result($parentRecords, $i, 'module');
				$adb->pquery("UPDATE vtiger_crmentityrel SET relcrmid=? WHERE crmid=? AND module=? AND relcrmid=? AND relmodule=?", array($entityId, $parcrmid, $parmodule, $transferId, $module));
			}
			$adb->pquery("UPDATE vtiger_modcomments SET related_to = ? WHERE related_to = ?", array($entityId, $transferId));
		}

		//関連するテーブル、フィールドを抽出
		$result = $adb->query("SELECT
			module
			, tablename
			, fieldname 
		FROM
			vtiger_fieldmodulerel 
			INNER JOIN vtiger_field 
				ON vtiger_field.fieldid = vtiger_fieldmodulerel.fieldid 
		WHERE
			relmodule = '$module';
		");
		$rows = $adb->num_rows($result);
		$rel_table_arr = array();
		$tbl_field_arr = array();
		$entity_tbl_field_arr = array();
		for ($i=0; $i < $rows; $i++) {
			$modulename = $adb->query_result($result, $i, 'module');
			$tablename = $adb->query_result($result, $i, 'tablename');
			$fieldname = $adb->query_result($result, $i, 'fieldname');
			$rel_table_arr[$modulename] = $tablename;
			$moduleinstance = CRMEntity::getInstance($modulename);
			$primarycolumn = $moduleinstance->table_index;
			$tbl_field_arr[$tablename] = $primarycolumn;
			$entity_tbl_field_arr[$tablename] = $fieldname;
		}
		//関連する項目を置換する
		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the primary query part of a report for which generateReportsQuery Doesnt exist in module
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */

	function generateReportsQuery($module, $queryPlanner) {
		global $adb;
		$primary = CRMEntity::getInstance($module);

		vtlib_setup_modulevars($module, $primary);
		$moduletable = $primary->table_name;
		$moduleindex = $primary->table_index;
		$modulecftable = $primary->customFieldTable[0];
		$modulecfindex = $primary->customFieldTable[1];

		if (isset($modulecftable) && $queryPlanner->requireTable($modulecftable)) {
			$cfquery = "inner join $modulecftable as $modulecftable on $modulecftable.$modulecfindex=$moduletable.$moduleindex";
		} else {
			$cfquery = '';
		}

		$relquery = '';
		$matrix = $queryPlanner->newDependencyMatrix();

		$fields_query = $adb->pquery("SELECT vtiger_field.fieldname,vtiger_field.tablename,vtiger_field.fieldid from vtiger_field INNER JOIN vtiger_tab on vtiger_tab.name = ? WHERE vtiger_tab.tabid=vtiger_field.tabid AND vtiger_field.uitype IN (10) and vtiger_field.presence in (0,2)", array($module));

		if ($adb->num_rows($fields_query) > 0) {
			for ($i = 0; $i < $adb->num_rows($fields_query); $i++) {
				$field_name = $adb->query_result($fields_query, $i, 'fieldname');
				$field_id = $adb->query_result($fields_query, $i, 'fieldid');
				$tab_name = $adb->query_result($fields_query, $i, 'tablename');
				$ui10_modules_query = $adb->pquery("SELECT relmodule FROM vtiger_fieldmodulerel WHERE fieldid=?", array($field_id));

				if ($adb->num_rows($ui10_modules_query) > 0) {

					// Capture the forward table dependencies due to dynamic related-field
					$crmentityRelModuleFieldTable = "vtiger_crmentityRel$module$field_id";

					$crmentityRelModuleFieldTableDeps = array();
					for ($j = 0; $j < $adb->num_rows($ui10_modules_query); $j++) {
						$rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
						$rel_obj = CRMEntity::getInstance($rel_mod);
						vtlib_setup_modulevars($rel_mod, $rel_obj);

						$rel_tab_name = $rel_obj->table_name;
						$rel_tab_index = $rel_obj->table_index;
							$crmentityRelModuleFieldTableDeps[] = $rel_tab_name . "Rel$module$field_id";
					}

					$matrix->setDependency($crmentityRelModuleFieldTable, $crmentityRelModuleFieldTableDeps);
					$matrix->addDependency($tab_name, $crmentityRelModuleFieldTable);

					if ($queryPlanner->requireTable($crmentityRelModuleFieldTable, $matrix)) {
						$relquery.= " left join vtiger_crmentity as $crmentityRelModuleFieldTable on $crmentityRelModuleFieldTable.crmid = $tab_name.$field_name and vtiger_crmentityRel$module$field_id.deleted=0";
					}

					for ($j = 0; $j < $adb->num_rows($ui10_modules_query); $j++) {
						$rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
						$rel_obj = CRMEntity::getInstance($rel_mod);
						vtlib_setup_modulevars($rel_mod, $rel_obj);

						$rel_tab_name = $rel_obj->table_name;
						$rel_tab_index = $rel_obj->table_index;

						$rel_tab_name_rel_module_table_alias = $rel_tab_name . "Rel$module$field_id";

						if ($queryPlanner->requireTable($rel_tab_name_rel_module_table_alias)) {
							$relquery.= " left join $rel_tab_name as $rel_tab_name_rel_module_table_alias  on $rel_tab_name_rel_module_table_alias.$rel_tab_index = $crmentityRelModuleFieldTable.crmid";
						}
					}
				}
			}
		}

		$query = "from $moduletable inner join vtiger_crmentity on vtiger_crmentity.crmid=$moduletable.$moduleindex";

		// Add the pre-joined custom table query
		$query .= " "."$cfquery";

		if ($queryPlanner->requireTable('vtiger_groups'.$module)) {
			$query .= " left join vtiger_groups as vtiger_groups" . $module . " on vtiger_groups" . $module . ".groupid = vtiger_crmentity.smownerid";
		}

		if ($queryPlanner->requireTable('vtiger_users'.$module)) {
			$query .= " left join vtiger_users as vtiger_users" . $module . " on vtiger_users" . $module . ".id = vtiger_crmentity.smownerid";
		}
		if ($queryPlanner->requireTable('vtiger_lastModifiedBy'.$module)) {
			$query .= " left join vtiger_users as vtiger_lastModifiedBy" . $module . " on vtiger_lastModifiedBy" . $module . ".id = vtiger_crmentity.modifiedby";
		}
		if ($queryPlanner->requireTable('vtiger_createdby'.$module)) {
			$query .= " LEFT JOIN vtiger_users AS vtiger_createdby$module ON vtiger_createdby$module.id=vtiger_crmentity.smcreatorid";
		}
		// TODO Optimize the tables below based on requirement
		$query .= "	left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid";

		// Add the pre-joined relation table query
		$query .= " " . $relquery;

		return $query;
	}

	/*
	 * Function to get the secondary query part of a report for which generateReportsSecQuery Doesnt exist in module
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */

	function generateReportsSecQuery($module, $secmodule,$queryPlanner) {
		global $adb;
		$secondary = CRMEntity::getInstance($secmodule);

		vtlib_setup_modulevars($secmodule, $secondary);

		$tablename = $secondary->table_name;
		$tableindex = $secondary->table_index;
		$modulecftable = $secondary->customFieldTable[0];
		$modulecfindex = $secondary->customFieldTable[1];

		if (isset($modulecftable) && $queryPlanner->requireTable($modulecftable)) {
			$cfquery = "left join $modulecftable as $modulecftable on $modulecftable.$modulecfindex=$tablename.$tableindex";
		} else {
			$cfquery = '';
		}

		$relquery = '';
		$matrix = $queryPlanner->newDependencyMatrix();

		$fields_query = $adb->pquery("SELECT vtiger_field.fieldname,vtiger_field.tablename,vtiger_field.fieldid from vtiger_field INNER JOIN vtiger_tab on vtiger_tab.name = ? WHERE vtiger_tab.tabid=vtiger_field.tabid AND vtiger_field.uitype IN (10) and vtiger_field.presence in (0,2)", array($secmodule));

		if ($adb->num_rows($fields_query) > 0) {
			for ($i = 0; $i < $adb->num_rows($fields_query); $i++) {
				$field_name = $adb->query_result($fields_query, $i, 'fieldname');
				$field_id = $adb->query_result($fields_query, $i, 'fieldid');
				$tab_name = $adb->query_result($fields_query, $i, 'tablename');
				$ui10_modules_query = $adb->pquery("SELECT relmodule FROM vtiger_fieldmodulerel WHERE fieldid=?", array($field_id));

				if ($adb->num_rows($ui10_modules_query) > 0) {
					// Capture the forward table dependencies due to dynamic related-field
					$crmentityRelSecModuleTable = "vtiger_crmentityRel$secmodule$field_id";

					$crmentityRelSecModuleTableDeps = array();
					for ($j = 0; $j < $adb->num_rows($ui10_modules_query); $j++) {
						$rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
						$rel_obj = CRMEntity::getInstance($rel_mod);
						vtlib_setup_modulevars($rel_mod, $rel_obj);

						$rel_tab_name = $rel_obj->table_name;
						$rel_tab_index = $rel_obj->table_index;
						$crmentityRelSecModuleTableDeps[] = $rel_tab_name . "Rel$secmodule";
					}

					$matrix->setDependency($crmentityRelSecModuleTable, $crmentityRelSecModuleTableDeps);
					$matrix->addDependency($tab_name, $crmentityRelSecModuleTable);

					if ($queryPlanner->requireTable($crmentityRelSecModuleTable, $matrix)) {
						$relquery .= " left join vtiger_crmentity as $crmentityRelSecModuleTable on $crmentityRelSecModuleTable.crmid = $tab_name.$field_name and $crmentityRelSecModuleTable.deleted=0";
					}
					for ($j = 0; $j < $adb->num_rows($ui10_modules_query); $j++) {
						$rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
						$rel_obj = CRMEntity::getInstance($rel_mod);
						vtlib_setup_modulevars($rel_mod, $rel_obj);

						$rel_tab_name = $rel_obj->table_name;
						$rel_tab_index = $rel_obj->table_index;

						$rel_tab_name_rel_secmodule_table_alias = $rel_tab_name . "Rel$secmodule$field_id";
						if ($queryPlanner->requireTable($rel_tab_name_rel_secmodule_table_alias)) {
							$relquery .= " left join $rel_tab_name as $rel_tab_name_rel_secmodule_table_alias on $rel_tab_name_rel_secmodule_table_alias.$rel_tab_index = $crmentityRelSecModuleTable.crmid";
						}
					}
				}
			}
		}

		// Update forward table dependencies
		$matrix->setDependency("vtiger_crmentity$secmodule", array("vtiger_groups$secmodule", "vtiger_users$secmodule", "vtiger_lastModifiedBy$secmodule"));
		$matrix->addDependency($tablename, "vtiger_crmentity$secmodule");

		if (!$queryPlanner->requireTable($tablename, $matrix) && !$queryPlanner->requireTable($modulecftable)) {
			return '';
		}

		$query = $this->getRelationQuery($module, $secmodule, "$tablename", "$tableindex", $queryPlanner);

		if ($queryPlanner->requireTable("vtiger_crmentity$secmodule", $matrix)) {
			$query .= " left join vtiger_crmentity as vtiger_crmentity$secmodule on vtiger_crmentity$secmodule.crmid = $tablename.$tableindex AND vtiger_crmentity$secmodule.deleted=0";
		}

		// Add the pre-joined custom table query
		$query .= " ".$cfquery;

		if ($queryPlanner->requireTable("vtiger_groups$secmodule")) {
			$query .= " left join vtiger_groups as vtiger_groups" . $secmodule . " on vtiger_groups" . $secmodule . ".groupid = vtiger_crmentity$secmodule.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_users$secmodule")) {
			$query .= " left join vtiger_users as vtiger_users" . $secmodule . " on vtiger_users" . $secmodule . ".id = vtiger_crmentity$secmodule.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_lastModifiedBy$secmodule")) {
			$query .= " left join vtiger_users as vtiger_lastModifiedBy" . $secmodule . " on vtiger_lastModifiedBy" . $secmodule . ".id = vtiger_crmentity" . $secmodule . ".modifiedby";
		}
		if ($queryPlanner->requireTable('vtiger_createdby'.$secmodule)) {
			$query .= " LEFT JOIN vtiger_users AS vtiger_createdby$secmodule ON vtiger_createdby$secmodule.id=vtiger_crmentity.smcreatorid";
		}
		// Add the pre-joined relation table query
		$query .= " " . $relquery;

		return $query;
	}

	function getReportsUiType10Query($module, $queryPlanner){
		$adb = PearDatabase::getInstance();
		$relquery = '';
		$matrix = $queryPlanner->newDependencyMatrix();

		$params = array($module);
		if($module == "Calendar") {
			array_push($params,"Events");
		}

		$fields_query = $adb->pquery("SELECT vtiger_field.fieldname,vtiger_field.tablename,vtiger_field.fieldid from vtiger_field INNER JOIN vtiger_tab on vtiger_tab.name IN (".  generateQuestionMarks($params).") WHERE vtiger_tab.tabid=vtiger_field.tabid AND vtiger_field.uitype IN (10) AND vtiger_field.presence IN (0,2)", $params);

		if ($adb->num_rows($fields_query) > 0) {
			for ($i = 0; $i < $adb->num_rows($fields_query); $i++) {
				$field_name = $adb->query_result($fields_query, $i, 'fieldname');
				$field_id = $adb->query_result($fields_query, $i, 'fieldid');
				$tab_name = $adb->query_result($fields_query, $i, 'tablename');
				$ui10_modules_query = $adb->pquery("SELECT relmodule FROM vtiger_fieldmodulerel WHERE fieldid=?", array($field_id));

				if ($adb->num_rows($ui10_modules_query) > 0) {

					// Capture the forward table dependencies due to dynamic related-field
					$crmentityRelModuleFieldTable = "vtiger_crmentityRel$module$field_id";

					$crmentityRelModuleFieldTableDeps = array();
					$calendarFlag = false;
					for ($j = 0; $j < $adb->num_rows($ui10_modules_query); $j++) {
						$rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
						if(vtlib_isModuleActive($rel_mod)) {
							if($rel_mod == 'Calendar') {
								$calendarFlag = true;
							}
							if($calendarFlag && $rel_mod == 'Events') {
								continue;
							}
							$rel_obj = CRMEntity::getInstance($rel_mod);
							vtlib_setup_modulevars($rel_mod, $rel_obj);

							$rel_tab_name = $rel_obj->table_name;
							$rel_tab_index = $rel_obj->table_index;
							$crmentityRelModuleFieldTableDeps[] = $rel_tab_name . "Rel$module$field_id";
						}
					}

					$matrix->setDependency($crmentityRelModuleFieldTable, $crmentityRelModuleFieldTableDeps);
					$matrix->addDependency($tab_name, $crmentityRelModuleFieldTable);

					if ($queryPlanner->requireTable($crmentityRelModuleFieldTable, $matrix)) {
						// Usersを関連にした場合、vtiger_crmentityをJoinするとレコードがないため、vtiger_usersを一度JOINする
						if($rel_mod == "Users"){
							// vtiger_crmentityの代わりとして動かすため、
							//   - vtiger_users.id as `crmid` として振る舞わせる
							//   - deleted = 0となっていた箇所は、vtiger_users.status = 'Active'で判定する
							// [TODO] Usersの場合、通常のレコードとは異なり過去のユーザーも見せたいのであれば、この判定は無く必要がある
							$relquery.= " LEFT JOIN (select u.*, u.id as `crmid` from vtiger_users u) AS $crmentityRelModuleFieldTable ON $crmentityRelModuleFieldTable.id = $tab_name.$field_name AND vtiger_crmentityRel$module$field_id.status='Active'";
						}else{
							$relquery.= " LEFT JOIN vtiger_crmentity AS $crmentityRelModuleFieldTable ON $crmentityRelModuleFieldTable.crmid = $tab_name.$field_name AND vtiger_crmentityRel$module$field_id.deleted=0";
						}
					}

					$calendarFlag = false;
					for ($j = 0; $j < $adb->num_rows($ui10_modules_query); $j++) {
						$rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
						if(vtlib_isModuleActive($rel_mod)) {
							if($rel_mod == 'Calendar') {
								$calendarFlag = true;
							}
							if($calendarFlag && $rel_mod == 'Events') {
								continue;
							}
							$rel_obj = CRMEntity::getInstance($rel_mod);
							vtlib_setup_modulevars($rel_mod, $rel_obj);

							$rel_tab_name = $rel_obj->table_name;
							$rel_tab_index = $rel_obj->table_index;

							$rel_tab_name_rel_module_table_alias = $rel_tab_name . "Rel$module$field_id";

							if ($queryPlanner->requireTable($rel_tab_name_rel_module_table_alias)) {
								$relquery.= " LEFT JOIN $rel_tab_name AS $rel_tab_name_rel_module_table_alias ON $rel_tab_name_rel_module_table_alias.$rel_tab_index = $crmentityRelModuleFieldTable.crmid";
							}
						}
					}
				}
			}
		}
		return $relquery;
	}

	/*
	 * Function to get the security query part of a report
	 * @param - $module primary module name
	 * returns the query string formed on fetching the related data for report for security of the module
	 */

	function getListViewSecurityParameter($module) {
		$tabid = getTabid($module);
		global $current_user;
		if ($current_user) {
			require('user_privileges/user_privileges_' . $current_user->id . '.php');
			require('user_privileges/sharing_privileges_' . $current_user->id . '.php');
		}
		$sec_query = '';
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {
			$sec_query .= " and (vtiger_crmentity.smownerid in($current_user->id) or vtiger_crmentity.smownerid
					in (select vtiger_user2role.userid from vtiger_user2role
							inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid
							inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid
							where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%') or vtiger_crmentity.smownerid
					in(select shareduserid from vtiger_tmp_read_user_sharing_per
						where userid=" . $current_user->id . " and tabid=" . $tabid . ") or (";
			if (sizeof($current_user_groups) > 0) {
				$sec_query .= " vtiger_crmentity.smownerid in (" . implode(",", $current_user_groups) . ") or ";
			}
			$sec_query .= " vtiger_crmentity.smownerid in(select vtiger_tmp_read_group_sharing_per.sharedgroupid
						from vtiger_tmp_read_group_sharing_per where userid=" . $current_user->id . " and tabid=" . $tabid . "))) ";
		}
		return $sec_query;
	}

	/*
	 * Function to get the relation query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on relating the primary module and secondary module
	 */

	function getRelationQuery($module, $secmodule, $table_name, $column_name, $queryPlanner) {
		$tab = getRelationTables($module, $secmodule);

		foreach ($tab as $key => $value) {
			$tables[] = $key;
			$fields[] = $value;
		}
		$pritablename = $tables[0];
		$sectablename = $tables[1];
		$prifieldname = $fields[0][0];
		$secfieldname = $fields[0][1];
		$tmpname = $pritablename . 'tmp' . $secmodule;
		$condition = "";
		if (!empty($tables[1]) && !empty($fields[1])) {
			$condvalue = $tables[1] . "." . $fields[1];
			$condition = "$table_name.$prifieldname=$condvalue";
		} else {
			$condvalue = $table_name . "." . $column_name;
			$condition = "$pritablename.$secfieldname=$condvalue";
		}

		$selectColumns = "$table_name.*";

		// Look forward for temporary table usage as defined by the QueryPlanner
		$secQueryFrom = " FROM $table_name INNER JOIN vtiger_crmentity ON " .
				"vtiger_crmentity.crmid=$table_name.$column_name AND vtiger_crmentity.deleted=0 ";

		//The relation field exists in custom field . relation field added from layout editor
		if($pritablename != $table_name) {
			$modulecftable = $this->customFieldTable[0];
			$modulecfindex = $this->customFieldTable[1];

			if (isset($modulecftable)) {
				$columns = $this->db->getColumnNames($modulecftable);
				//remove the primary key since it will conflict with base table column name or else creating temporary table will fails for duplicate columns
				//eg : vtiger_potential has potentialid and vtiger_potentialscf has same potentialid
				unset($columns[array_search($modulecfindex,$columns)]);
				if(count($columns) > 0) {
					$cfSelectString = implode(',',$columns);
					$selectColumns .= ','.$cfSelectString;
				}
				$cfquery = "LEFT JOIN $modulecftable ON $modulecftable.$modulecfindex=$table_name.$column_name";
				$secQueryFrom .= $cfquery;
			}
		}

		$secQuery = 'SELECT '.$selectColumns.' '.$secQueryFrom;

		$secQueryTempTableQuery = $queryPlanner->registerTempTable($secQuery, array($column_name, $fields[1], $prifieldname),$secmodule);

		$query = '';
		if ($pritablename == 'vtiger_crmentityrel') {
			$tableName = $table_name;
			if($secmodule == "Emails") {
				$tableName .='Emails';
			}
			$condition = "($tableName.$column_name={$tmpname}.{$secfieldname} " .
					"OR $tableName.$column_name={$tmpname}.{$prifieldname})";
			$query = " left join vtiger_crmentityrel as $tmpname ON (($condvalue={$tmpname}.{$secfieldname} " .
					"OR $condvalue={$tmpname}.{$prifieldname})) AND ({$tmpname}.module='{$secmodule}' OR {$tmpname}.relmodule='{$secmodule}') ";
		} elseif (strripos($pritablename, 'rel') === (strlen($pritablename) - 3)) {
			$instance = self::getInstance($module);
			$sectableindex = $instance->tab_name_index[$sectablename];
			$condition = "$table_name.$column_name=$tmpname.$secfieldname";
			if($secmodule == "Emails"){
				$condition = $table_name.'Emails'.".$column_name=$tmpname.$secfieldname";
			}
			if($pritablename == 'vtiger_seactivityrel') {
				if($module == "Emails" || $secmodule == "Emails"){
					$tmpModule = "Emails";
				}else{
					$tmpModule = "Calendar";
				}
				$query = " left join $pritablename as $tmpname ON ($sectablename.$sectableindex=$tmpname.$prifieldname
					AND $tmpname.activityid IN (SELECT crmid FROM vtiger_crmentity WHERE setype='$tmpModule' AND deleted = 0))";
			} else if($pritablename == 'vtiger_senotesrel') {
					$query = " left join $pritablename as $tmpname ON ($sectablename.$sectableindex=$tmpname.$prifieldname
					AND $tmpname.notesid IN (SELECT crmid FROM vtiger_crmentity WHERE setype='Documents' AND deleted = 0))";
			} else if($pritablename == 'vtiger_inventoryproductrel' && ($module =="Products" || $module =="Services") && ($secmodule == "Invoice" || $secmodule == "SalesOrder" || $secmodule == "PurchaseOrder" || $secmodule == "Quotes")) {
				/** In vtiger_inventoryproductrel table, we'll have same product related to quotes/invoice/salesorder/purchaseorder
				 *  we need to check whether the product joining is related to secondary module selected or not to eliminate duplicates
				 */
				$query = " left join $pritablename as $tmpname ON ($sectablename.$sectableindex=$tmpname.$prifieldname AND $tmpname.id in 
						(select crmid from vtiger_crmentity where setype='$secmodule' and deleted=0))";
			} else if($pritablename == 'vtiger_cntactivityrel') {
				if($queryPlanner->requireTable('vtiger_cntactivityrel') && $secmodule == 'Contacts') {
					$tmpname = 'vtiger_cntactivityrel';
					$condition = "$table_name.$column_name=$tmpname.$secfieldname";
				} else {
					$query = " left join $pritablename as $tmpname ON ($sectablename.$sectableindex=$tmpname.$prifieldname)";
				}
			} else {
				$query = " LEFT JOIN $pritablename AS $tmpname ON ($sectablename.$sectableindex=$tmpname.$prifieldname)";
			}
			if($secmodule == 'Calendar'){
				$condition .= " AND $table_name.activitytype != 'Emails'";
			}else if($secmodule == 'Leads'){
				$condition .= " AND $table_name.converted = 0";
			}

		}else if($module == "Contacts" && $secmodule == "Potentials"){
			// To get all the Contacts from vtiger_contpotentialrel table
			$condition .= " OR $table_name.potentialid = vtiger_contpotentialrel.potentialid";
			$query .= " left join vtiger_contpotentialrel on  vtiger_contpotentialrel.contactid = vtiger_contactdetails.contactid";
		}else if($module == "Potentials" && $secmodule == "Contacts"){
			// To get all the Potentials from vtiger_contpotentialrel table
			$condition .= " OR $table_name.contactid = vtiger_contpotentialrel.contactid";
			$query .= " left join vtiger_contpotentialrel on vtiger_potential.potentialid = vtiger_contpotentialrel.potentialid";
		}
		if ($secmodule == "Emails") {
			$table_name .="Emails";
		}
		$query .= " left join $secQueryTempTableQuery as $table_name on {$condition}";
		return $query;
	}

	/** END * */

	/**
	 * This function handles the import for uitype 10 fieldtype
	 * @param string $module - the current module name
	 * @param string fieldname - the related to field name
	 */
	function add_related_to($module, $fieldname) {
		global $adb, $imported_ids, $current_user;

		$related_to = $this->column_fields[$fieldname];

		if (empty($related_to)) {
			return false;
		}

		//check if the field has module information; if not get the first module
		if (!strpos($related_to, "::::")) {
			$module = getFirstModule($module, $fieldname);
			$value = $related_to;
		} else {
			//check the module of the field
			$arr = array();
			$arr = explode("::::", $related_to);
			$module = $arr[0];
			$value = $arr[1];
		}

		$focus1 = CRMEntity::getInstance($module);

		$entityNameArr = getEntityField($module);
		$entityName = $entityNameArr['fieldname'];
		$query = "SELECT vtiger_crmentity.deleted, $focus1->table_name.*
					FROM $focus1->table_name
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=$focus1->table_name.$focus1->table_index
						where $entityName=? and vtiger_crmentity.deleted=0";
		$result = $adb->pquery($query, array($value));

		if (!isset($this->checkFlagArr[$module])) {
			$this->checkFlagArr[$module] = (isPermitted($module, 'EditView', '') == 'yes');
		}

		if ($adb->num_rows($result) > 0) {
			//record found
			$focus1->id = $adb->query_result($result, 0, $focus1->table_index);
		} elseif ($this->checkFlagArr[$module]) {
			//record not found; create it
			$focus1->column_fields[$focus1->list_link_field] = $value;
			$focus1->column_fields['assigned_user_id'] = $current_user->id;
			$focus1->column_fields['modified_user_id'] = $current_user->id;
			$focus1->save($module);

			$last_import = new UsersLastImport();
			$last_import->assigned_user_id = $current_user->id;
			$last_import->bean_type = $module;
			$last_import->bean_id = $focus1->id;
			$last_import->save();
		} else {
			//record not found and cannot create
			$this->column_fields[$fieldname] = "";
			return false;
		}
		if (!empty($focus1->id)) {
			$this->column_fields[$fieldname] = $focus1->id;
			return true;
		} else {
			$this->column_fields[$fieldname] = "";
			return false;
		}
	}

	/**
	 * To keep track of action of field filtering and avoiding doing more than once.
	 *
	 * @var Array
	 */
	protected $__inactive_fields_filtered = false;

	/**
	 * Filter in-active fields based on type
	 *
	 * @param String $module
	 */
	function filterInactiveFields($module) {
		if ($this->__inactive_fields_filtered) {
			return;
		}

		global $adb, $mod_strings;

		// Look for fields that has presence value NOT IN (0,2)
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module, array('1'));
		if ($cachedModuleFields === false) {
			// Initialize the fields calling suitable API
			getColumnFields($module);
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module, array('1'));
		}

		$hiddenFields = array();

		if ($cachedModuleFields) {
			foreach ($cachedModuleFields as $fieldinfo) {
				$fieldLabel = $fieldinfo['fieldlabel'];
				// NOTE: We should not translate the label to enable field diff based on it down
				$fieldName = $fieldinfo['fieldname'];
				$tableName = str_replace("vtiger_", "", $fieldinfo['tablename']);
				$hiddenFields[$fieldLabel] = array($tableName => $fieldName);
			}
		}

		if (isset($this->list_fields)) {
			$this->list_fields = array_diff_assoc($this->list_fields, $hiddenFields);
		}

		if (isset($this->search_fields)) {
			$this->search_fields = array_diff_assoc($this->search_fields, $hiddenFields);
		}

		// To avoid re-initializing everytime.
		$this->__inactive_fields_filtered = true;
	}

	/** END * */
	function buildSearchQueryForFieldTypes($uitypes, $value=false) {
		global $adb;

		if (!is_array($uitypes))
			$uitypes = array($uitypes);
		$module = $this->moduleName;

		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		if ($cachedModuleFields === false) {
			getColumnFields($module); // This API will initialize the cache as well
			// We will succeed now due to above function call
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		}
		if($module == 'Calendar' || $module == 'Events') {
		   $cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module('Events');
		   $cachedCalendarFields = VTCacheUtils::lookupFieldInfo_Module('Calendar');
		   $cachedModuleFields = array_merge($cachedEventsFields, $cachedCalendarFields);
	   }

		$lookuptables = array();
		$lookupcolumns = array();
		foreach ($cachedModuleFields as $fieldinfo) {
			if (in_array($fieldinfo['uitype'], $uitypes)) {
				$lookuptables[] = $fieldinfo['tablename'];
				$lookupcolumns[] = $fieldinfo['columnname'];
			}
		}

		$entityfields = getEntityField($module);
		$querycolumnnames = implode(',', $lookupcolumns);
		$entitycolumnnames = $entityfields['fieldname'];
		$query = "select crmid as id, $querycolumnnames, $entitycolumnnames as name ";
		$query .= " FROM $this->table_name ";
		$query .=" INNER JOIN vtiger_crmentity ON $this->table_name.$this->table_index = vtiger_crmentity.crmid AND deleted = 0 ";

		//remove the base table
		$LookupTable = array_unique($lookuptables);
		$indexes = array_keys($LookupTable, $this->table_name);
		if (!empty($indexes)) {
			foreach ($indexes as $index) {
				unset($LookupTable[$index]);
			}
		}
		foreach ($LookupTable as $tablename) {
			$query .= " INNER JOIN $tablename
						on $this->table_name.$this->table_index = $tablename." . $this->tab_name_index[$tablename];
		}
		if (!empty($lookupcolumns) && $value !== false) {
			$query .=" WHERE ";
			$i = 0;
			$columnCount = count($lookupcolumns);
			foreach ($lookupcolumns as $columnname) {
				if (!empty($columnname)) {
					if ($i == 0 || $i == ($columnCount))
						$query .= sprintf("%s = '%s'", $columnname, $value);
					else
						$query .= sprintf(" OR %s = '%s'", $columnname, $value);
					$i++;
				}
			}
		}
		return $query;
	}

	/**
	 *
	 * @param String $tableName
	 * @return String
	 */
	public function getJoinClause($tableName) {
		if (strripos($tableName, 'rel') === (strlen($tableName) - 3)) {
			return 'LEFT JOIN';
		}  else if (Vtiger_Functions::isUserSpecificFieldTable($tableName, $this->moduleName)) {
			return 'LEFT JOIN';
		}
		else {
			return 'INNER JOIN';
		}
	}

	/**
	 *
	 * @param <type> $module
	 * @param <type> $user
	 * @param <type> $parentRole
	 * @param <type> $userGroups
	 */
	function getNonAdminAccessQuery($module, $user, $parentRole, $userGroups) {
		$query = $this->getNonAdminUserAccessQuery($user, $parentRole, $userGroups);
		if (!empty($module)) {
			$moduleAccessQuery = $this->getNonAdminModuleAccessQuery($module, $user);
			if (!empty($moduleAccessQuery)) {
				$query .= " UNION $moduleAccessQuery";
			}
		}
		return $query;
	}

	/**
	 *
	 * @param <type> $user
	 * @param <type> $parentRole
	 * @param <type> $userGroups
	 */
	function getNonAdminUserAccessQuery($user, $parentRole, $userGroups) {
		$query = "(SELECT $user->id as id) UNION (SELECT vtiger_user2role.userid AS userid FROM " .
				"vtiger_user2role INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid " .
				"INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid WHERE " .
				"vtiger_role.parentrole like '$parentRole::%')";
		if (count($userGroups) > 0) {
			$query .= $this->getNonAdminUserGroupAccessQuery($userGroups);
		}
		return $query;
	}

	/**
	 *Function to get all the users under groups
	 * @param <type> $userGroups
	 */
	function getNonAdminUserGroupAccessQuery($userGroups) {
		$query .= " UNION (SELECT groupid FROM vtiger_groups WHERE groupid IN (".implode(",", $userGroups)."))";
		return $query;
	}

	/**
	 *
	 * @param <type> $module
	 * @param <type> $user
	 */
	function getNonAdminModuleAccessQuery($module, $user) {
		require('user_privileges/sharing_privileges_' . $user->id . '.php');
		$tabId = getTabid($module);
		$sharingRuleInfoVariable = $module . '_share_read_permission';
		$sharingRuleInfo = $$sharingRuleInfoVariable;
		$sharedTabId = null;
		$query = '';
		if (!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
				count($sharingRuleInfo['GROUP']) > 0)) {
			$query = " (SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per " .
					"WHERE userid=$user->id AND tabid=$tabId) UNION (SELECT " .
					"vtiger_tmp_read_group_sharing_per.sharedgroupid FROM " .
					"vtiger_tmp_read_group_sharing_per WHERE userid=$user->id AND tabid=$tabId)";
		}
		return $query;
	}

	/**
	 *
	 * @param <type> $module
	 * @param <type> $user
	 * @param <type> $parentRole
	 * @param <type> $userGroups
	 */
	protected function setupTemporaryTable($tableName, $tabId, $user, $parentRole, $userGroups) {
		$module = null;
		if (!empty($tabId)) {
			$module = getTabModuleName($tabId);
		}
		$query = $this->getNonAdminAccessQuery($module, $user, $parentRole, $userGroups);
        $tableName = Vtiger_Util_Helper::validateStringForSql($tableName);
		$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore " .
				$query;
		$db = PearDatabase::getInstance();
		$result = $db->pquery($query, array());
		if (is_object($result)) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param String $module - module name for which query needs to be generated.
	 * @param Users $user - user for which query needs to be generated.
	 * @return String Access control Query for the user.
	 */
	function getNonAdminAccessControlQuery($module, $user, $scope = '') {
		require('user_privileges/user_privileges_' . $user->id . '.php');
		require('user_privileges/sharing_privileges_' . $user->id . '.php');
		$query = ' ';
		$tabId = getTabid($module);
		if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
				== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
			$tableName = 'vt_tmp_u' . $user->id;
			$sharingRuleInfoVariable = $module . '_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			$sharedTabId = null;
			if (!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
					count($sharingRuleInfo['GROUP']) > 0)) {
				$tableName = $tableName . '_t' . $tabId;
				$sharedTabId = $tabId;
			} elseif ($module == 'Calendar' || !empty($scope)) {
				$tableName .= '_t' . $tabId;
			}
			$this->setupTemporaryTable($tableName, $sharedTabId, $user, $current_user_parent_role_seq, $current_user_groups);
			// for secondary module we should join the records even if record is not there(primary module without related record)
				if($scope == ''){
					$query = " INNER JOIN $tableName $tableName$scope ON $tableName$scope.id = " .
							"vtiger_crmentity$scope.smownerid ";
				}else{
					$query = " INNER JOIN $tableName $tableName$scope ON $tableName$scope.id = " .
							"vtiger_crmentity$scope.smownerid OR vtiger_crmentity$scope.smownerid IS NULL";
				}
			}
		return $query;
	}

	public function listQueryNonAdminChange($query, $scope = '') {
		//make the module base table as left hand side table for the joins,
		//as mysql query optimizer puts crmentity on the left side and considerably slow down
		$query = preg_replace('/\s+/', ' ', $query);
		if (strripos($query, ' WHERE ') !== false) {
			vtlib_setup_modulevars($this->moduleName, $this);
			$query = str_ireplace(' where ', " WHERE $this->table_name.$this->table_index > 0  AND ", $query);
		}
		return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param String $secmodule - $secmodule secondary module name
	 * @return Array returns the array with table names and fieldnames storing relations
	 * between module and this module
	 */

	function setRelationTables($secmodule) {
		$rel_tables = array(
			"Documents" => array("vtiger_senotesrel" => array("crmid", "notesid"),
				$this->table_name => $this->table_index),
		);
		return $rel_tables[$secmodule];
	}

	/**
	 * Function to clear the fields which needs to be saved only once during the Save of the record
	 * For eg: Comments of HelpDesk should be saved only once during one save of a Trouble Ticket
	 */
	function clearSingletonSaveFields() {
		return;
	}

	/**
	 * Function to track when a new record is linked to a given record
	 */
	function trackLinkedInfo($module, $crmid, $with_module, $with_crmid) {
		global $current_user;
		$adb = PearDatabase::getInstance();
		$currentTime = date('Y-m-d H:i:s');

		$adb->pquery('UPDATE vtiger_crmentity SET modifiedtime = ?, modifiedby = ? WHERE crmid = ?', array($currentTime, $current_user->id, $crmid));

		// @Note: We should extend this to event handlers
		if(vtlib_isModuleActive('ModTracker')) {
			// Track the time the relation was added
			require_once 'modules/ModTracker/ModTracker.php';
			ModTracker::linkRelation($module, $crmid, $with_module, $with_crmid);
		}
	}

	/**
	 * Function to get sort order
	 * return string  $sorder    - sortorder string either 'ASC' or 'DESC'
	 */
	function getSortOrder() {
		global $log,$currentModule;
		$log->debug("Entering getSortOrder() method ...");
		if (isset($_REQUEST['sorder']))
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		else
			$sorder = (($_SESSION[$currentModule . '_Sort_Order'] != '') ? ($_SESSION[$currentModule . '_Sort_Order']) : ($this->default_sort_order));
		$log->debug("Exiting getSortOrder() method ...");
		return $sorder;
	}

	/**
	 * Function to get order by
	 * return string  $order_by    - fieldname(eg: 'accountname')
	 */
	function getOrderBy() {
		global $log, $currentModule;
		$log->debug("Entering getOrderBy() method ...");

		$use_default_order_by = '';
		if (PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}

		if (isset($_REQUEST['order_by']))
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		else
			$order_by = (($_SESSION[$currentModule.'_Order_By'] != '') ? ($_SESSION[$currentModule.'_Order_By']) : ($use_default_order_by));
		$log->debug("Exiting getOrderBy method ...");
		return $order_by;
	}

	// Mike Crowe Mod --------------------------------------------------------

	/**
	 * Function to Listview buttons
	 * return array  $list_buttons - for module (eg: 'Accounts')
	 */
	function getListButtons($app_strings) {
		$list_buttons = Array();

		if (isPermitted($currentModule, 'Delete', '') == 'yes')
			$list_buttons['del'] = $app_strings[LBL_MASS_DELETE];
		if (isPermitted($currentModule, 'EditView', '') == 'yes') {
			$list_buttons['mass_edit'] = $app_strings[LBL_MASS_EDIT];
			// Mass Edit could be used to change the owner as well!
			//$list_buttons['c_owner'] = $app_strings[LBL_CHANGE_OWNER];
		}
		return $list_buttons;
	}

	/**
	 * Function to track when a record is unlinked to a given record
	 */
	function trackUnLinkedInfo($module, $crmid, $with_module, $with_crmid) {
		global $current_user;
		$adb = PearDatabase::getInstance();
		$currentTime = date('Y-m-d H:i:s');

		$adb->pquery('UPDATE vtiger_crmentity SET modifiedtime = ?, modifiedby = ? WHERE crmid = ?', array($currentTime, $current_user->id, $crmid));

		// @Note: We should extend this to event handlers
		if(vtlib_isModuleActive('ModTracker')) {
			//Track the time the relation was deleted
			require_once 'modules/ModTracker/ModTracker.php';
			ModTracker::unLinkRelation($module, $crmid, $with_module, $with_crmid);
		}
	}

	/**
	 * Function which will give the basic query to find duplicates
	 * @param <String> $module
	 * @param <String> $tableColumns
	 * @param <String> $selectedColumns
	 * @param <Boolean> $ignoreEmpty
	 * @param <Array> $requiredTables
	 * @return string
	 */
	function getQueryForDuplicates($module, $tableColumns, $selectedColumns = '', $ignoreEmpty = false,$requiredTables = array()) {
		if(is_array($tableColumns)) {
			$tableColumnsString = implode(',', $tableColumns);
		}
		$selectClause = "SELECT " . $this->table_name . "." . $this->table_index . " AS recordid," . $tableColumnsString;

		// Select Custom Field Table Columns if present
		if (isset($this->customFieldTable))
			$query .= ", " . $this->customFieldTable[0] . ".* ";

		$fromClause = " FROM $this->table_name";

		$fromClause .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		if($this->tab_name) {
			foreach($this->tab_name as $tableName) {
				if($tableName != 'vtiger_crmentity' && $tableName != $this->table_name && $tableName != 'vtiger_inventoryproductrel' && in_array($tableName,$requiredTables)) {
					if($this->tab_name_index[$tableName]) {
						$fromClause .= " INNER JOIN " . $tableName . " ON " . $tableName . '.' . $this->tab_name_index[$tableName] .
							" = $this->table_name.$this->table_index";
					}
				}
			}
		}

		$whereClause = " WHERE vtiger_crmentity.deleted = 0";
		$whereClause .= $this->getListViewSecurityParameter($module);

		if($ignoreEmpty) {
			foreach($tableColumns as $tableColumn){
				$whereClause .= " AND ($tableColumn IS NOT NULL AND $tableColumn != '') ";
			}
		}

		if (isset($selectedColumns) && trim($selectedColumns) != '') {
			$sub_query = "SELECT $selectedColumns FROM $this->table_name AS t " .
					" INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t." . $this->table_index;
			// Consider custom table join as well.
			if (isset($this->customFieldTable)) {
				$sub_query .= " LEFT JOIN " . $this->customFieldTable[0] . " tcf ON tcf." . $this->customFieldTable[1] . " = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $selectedColumns HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $tableColumnsString $fromClause $whereClause GROUP BY $tableColumnsString HAVING COUNT(*)>1";
		}

		$i = 1;
		foreach($tableColumns as $tableColumn){
			$tableInfo = explode('.', $tableColumn);
			$duplicateCheckClause .= " ifnull($tableColumn,'null') = ifnull(temp.$tableInfo[1],'null')";
			if (count($tableColumns) != $i++) $duplicateCheckClause .= " AND ";
		}

		$query = $selectClause . $fromClause .
				" LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=" . $this->table_name . "." . $this->table_index .
				" INNER JOIN (" . $sub_query . ") AS temp ON " . $duplicateCheckClause .
				$whereClause .
				" ORDER BY $tableColumnsString," . $this->table_name . "." . $this->table_index . " ASC";
		return $query;
	}

	/**
	 * Function to get relation query for get_activities
	 */
	function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $currentModule;
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name','first_name' => 'vtiger_users.first_name',), 'Users');

		$query = "SELECT CASE WHEN (vtiger_users.user_name not like '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name,
					vtiger_crmentity.*, vtiger_activity.activitytype, vtiger_activity.subject, vtiger_activity.date_start, vtiger_activity.time_start,
					vtiger_activity.recurringtype, vtiger_activity.due_date, vtiger_activity.time_end, vtiger_activity.visibility, vtiger_seactivityrel.crmid AS parent_id,
					CASE WHEN (vtiger_activity.activitytype = 'Task') THEN (vtiger_activity.status) ELSE (vtiger_activity.eventstatus) END AS status
					FROM vtiger_activity
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
					LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
					LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid
					LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
					LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
						WHERE vtiger_crmentity.deleted = 0 AND vtiger_activity.activitytype <> 'Emails'
							AND vtiger_seactivityrel.crmid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, '', $query, '', '');
		if($return_value == null) $return_value = Array();
		return $return_value;
	}

	function get_comments($relatedRecordId = false) {
		$current_user = vglobal('current_user');
		$moduleName = $this->moduleName;
		if($moduleName != 'ModComments') {
			return false;
		}
		$queryGenerator = new EnhancedQueryGenerator($moduleName, $current_user);
		if(is_object($this->column_fields)) {
			$fields = $this->column_fields->getColumnFieldNames();
		} else if(is_array($this->column_fields)) {
			$fields = array_keys($this->column_fields);
		}
		array_push($fields, 'id');
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->getQuery();
		if($relatedRecordId){
			$query .= " AND related_to = ".$relatedRecordId." ORDER BY vtiger_crmentity.createdtime DESC";
		}
		return $query;
	}
}

class TrackableObject implements ArrayAccess, IteratorAggregate {
	private $storage;
	private $trackingEnabled = true;
	private $tracking;
	
	function __construct($value = array()) {
		$this->storage = $value;
	}

	function offsetExists($key) {
		return isset($this->storage[$key]);
	}

	function offsetSet($key, $value) {
		if($this->tracking && $this->trackingEnabled) {
			$olderValue = $this->offsetGet($key);
			// decode_html only expects string
			$olderValue = is_string($olderValue) ? decode_html($olderValue) : $olderValue ;
			//same logic is used in vtEntityDelta to check for delta
			if((empty($olderValue) && !empty($value)) || ($olderValue !== $value)) {
				$this->changed[] = $key;
			}
		}
		$this->storage[$key] = $value;
	}

	public function offsetUnset($key) {
		unset($this->storage[$key]);
	}

	public function offsetGet($key) {
		return isset($this->storage[$key]) ? $this->storage[$key] : null;
	}

	public function getIterator() {
		$iterator = new ArrayObject($this->storage);
		return $iterator->getIterator();
	}

	function getChanged() {
		return $this->changed;
	}

	function startTracking() {
		if($this->tracking && $this->trackingEnabled) return;
		$this->tracking = true;
		$this->changed = array();
	}

	function restartTracking() {
		$this->tracking = true;
		$this->startTracking();
	}

	function pauseTracking() {
		$this->tracking = false;
	}

	function resumeTracking() {
		if($this->trackingEnabled)
			$this->tracking = true;
	}

	function getColumnFields() {
		return $this->storage;
	}

	function getColumnFieldNames(){
		return array_keys($this->storage);
	}
}
