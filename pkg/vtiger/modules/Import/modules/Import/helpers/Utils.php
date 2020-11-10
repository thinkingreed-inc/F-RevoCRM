<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
//required for auto detecting file endings for files create in mac
ini_set("auto_detect_line_endings", true);

class Import_Utils_Helper {

	static $AUTO_MERGE_NONE = 0;
	static $AUTO_MERGE_IGNORE = 1;
	static $AUTO_MERGE_OVERWRITE = 2;
	static $AUTO_MERGE_MERGEFIELDS = 3;

	static $supportedFileEncoding = array('UTF-8'=>'UTF-8', 'ISO-8859-1'=>'ISO-8859-1');
	static $supportedDelimiters = array(','=>'comma', ';'=>'semicolon', '|'=> 'Pipe', '^'=>'Caret');
	static $supportedFileExtensions = array('csv','vcf');

	public function getSupportedFileExtensions() {
		return self::$supportedFileExtensions;
	}

	public function getSupportedFileEncoding() {
		return self::$supportedFileEncoding;
	}

	public function getSupportedDelimiters() {
		return self::$supportedDelimiters;
	}

	public static function getAutoMergeTypes($moduleName) {
		$mergeTypes = array(self::$AUTO_MERGE_IGNORE => 'Skip');
		if (Users_Privileges_Model::isPermitted($moduleName, 'EditView')) {
			$mergeTypes[self::$AUTO_MERGE_OVERWRITE]		= 'Overwrite';
			$mergeTypes[self::$AUTO_MERGE_MERGEFIELDS]	= 'Merge';
		}
		return $mergeTypes;
	}

	public static function getMaxUploadSize() {
		global $upload_maxsize;
		return $upload_maxsize;
	}

	public static function getImportDirectory() {
		global $import_dir;
		$importDir = dirname(__FILE__). '/../../../'.$import_dir;
		return $importDir;
	}

	public static function getImportFilePath($user) {
		$importDirectory = self::getImportDirectory();
		return $importDirectory. "IMPORT_".$user->id;
	}


	public static function getFileReaderInfo($type) {
		$configReader = new Import_Config_Model();
		$importTypeConfig = $configReader->get('importTypes');
		if(isset($importTypeConfig[$type])) {
			return $importTypeConfig[$type];
		}
		return null;
	}

	public static function getFileReader($request, $user) {
		$fileReaderInfo = self::getFileReaderInfo($request->get('type'));
		if(!empty($fileReaderInfo)) {
			require_once $fileReaderInfo['classpath'];
			$fileReader = new $fileReaderInfo['reader'] ($request, $user);
		} else {
			$fileReader = null;
		}
		return $fileReader;
	}

	public static function getDbTableName($user) {
		$configReader = new Import_Config_Model();
		$userImportTablePrefix = $configReader->get('userImportTablePrefix');

		$tableName = $userImportTablePrefix;
		if(method_exists($user, 'getId')){
			$tableName .= $user->getId();
		} else {
			$tableName .= $user->id;
		}
		return $tableName;
	}

	public static function showErrorPage($errorMessage, $errorDetails=false, $customActions=false) {
		$viewer = new Vtiger_Viewer();

		$viewer->assign('ERROR_MESSAGE', $errorMessage);
		$viewer->assign('ERROR_DETAILS', $errorDetails);
		$viewer->assign('CUSTOM_ACTIONS', $customActions);
		$viewer->assign('MODULE','Import');

		$viewer->view('ImportError.tpl', 'Import');
	}

	public static function showImportLockedError($lockInfo) {
		$moduleName = getTabModuleName($lockInfo['tabid']);
		$userName = getUserFullName($lockInfo['userid']);
		$errorMessage = sprintf("%s is importing %s. Please try after some time.",$userName, $moduleName);
		self::showErrorPage($errorMessage);
	}

	public static function showImportTableBlockedError($moduleName, $user) {

		$errorMessage = vtranslate('ERR_UNIMPORTED_RECORDS_EXIST', 'Import');
		$customActions = array('LBL_CLEAR_DATA' => "location.href='index.php?module={$moduleName}&view=Import&mode=clearCorruptedData'");

		self::showErrorPage($errorMessage, '', $customActions);
	}

	public static function isUserImportBlocked($user) {
		$adb = PearDatabase::getInstance();
		$tableName = self::getDbTableName($user);

		if(Vtiger_Utils::CheckTable($tableName)) {
			$result = $adb->pquery('SELECT 1 FROM '.$tableName.' WHERE status = ?',  array(Import_Data_Action::$IMPORT_RECORD_NONE));
			if($adb->num_rows($result) > 0) {
				return true;
			}
		}
		return false;
	}

	public static function clearUserImportInfo($user) {
		$adb = PearDatabase::getInstance();
		$tableName = self::getDbTableName($user);

		$adb->pquery('DROP TABLE IF EXISTS '.$tableName, array());
		Import_Lock_Action::unLock($user);
		Import_Queue_Action::removeForUser($user);
	}

	public static function getAssignedToUserList($module) {
		$cache = Vtiger_Cache::getInstance();
		if($cache->getUserList($module,$current_user->id)){
			return $cache->getUserList($module,$current_user->id);
		} else {
			$userList = get_user_array(FALSE, "Active", $current_user->id);
			$cache->setUserList($module,$userList,$current_user->id);
			return $userList;
		}
	}

	public static function getAssignedToGroupList($module) {
		$cache = Vtiger_Cache::getInstance();
		if($cache->getGroupList($module,$current_user->id)){
			return $cache->getGroupList($module,$current_user->id);
		} else {
			$groupList = get_group_array(FALSE, "Active", $current_user->id);
			$cache->setGroupList($module,$groupList,$current_user->id);
			return $groupList;
		}
	}

	public static function hasAssignPrivilege($moduleName, $assignToUserId) {
		$assignableUsersList = self::getAssignedToUserList($moduleName);
		if(array_key_exists($assignToUserId, $assignableUsersList)) {
			return true;
		}
		$assignableGroupsList = self::getAssignedToGroupList($moduleName);
		if(array_key_exists($assignToUserId, $assignableGroupsList)) {
			return true;
		}
		return false;
	}

	public static function validateFileUpload($request) {
		$current_user = Users_Record_Model::getCurrentUserModel();

		$uploadMaxSize = self::getMaxUploadSize();
		$importDirectory = self::getImportDirectory();
		$temporaryFileName = self::getImportFilePath($current_user);

		if($_FILES['import_file']['error']) {
			$request->set('error_message', self::fileUploadErrorMessage($_FILES['import_file']['error']));
			return false;
		}
		if(!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
			$request->set('error_message', vtranslate('LBL_FILE_UPLOAD_FAILED', 'Import'));
			return false;
		}
		if ($_FILES['import_file']['size'] > $uploadMaxSize) {
			$request->set('error_message', vtranslate('LBL_IMPORT_ERROR_LARGE_FILE', 'Import').
												 $uploadMaxSize.' '.vtranslate('LBL_IMPORT_CHANGE_UPLOAD_SIZE', 'Import'));
			return false;
		}
		if(!is_writable($importDirectory)) {
			$request->set('error_message', vtranslate('LBL_IMPORT_DIRECTORY_NOT_WRITABLE', 'Import'));
			return false;
		}

		if ($request->get('type') == "ics" || $request->get('type') == "vcf") {
			$fileCopied = move_uploaded_file($_FILES['import_file']['tmp_name'], $temporaryFileName);
		}else{
			$fileCopied = self::neutralizeAndMoveFile($_FILES['import_file']['tmp_name'], $temporaryFileName, $request->get('delimiter'));
		}
		if(!$fileCopied) {
			$request->set('error_message', vtranslate('LBL_IMPORT_FILE_COPY_FAILED', 'Import'));
			return false;
		}
		$fileReader = Import_Utils_Helper::getFileReader($request, $current_user);

		if($fileReader == null) {
			$request->set('error_message', vtranslate('LBL_INVALID_FILE', 'Import'));
			return false;
		}

		$hasHeader = $fileReader->hasHeader();
		$firstRow = $fileReader->getFirstRowData($hasHeader);
		if($firstRow === false) {
			$request->set('error_message', vtranslate('LBL_NO_ROWS_FOUND', 'Import'));
			return false;
		}
		return true;
	}

	/**
	 * To remove carriage return(\r) in end of every line and make the file neutral
	 * @param type $uploadedFileName
	 * @param type $temporaryFileName
	 * @return boolean
	 */
	public static function neutralizeAndMoveFile($uploadedFileName, $temporaryFileName, $delimiter = ','){
		$file_read = fopen($uploadedFileName,'r');
		$file_write = fopen($temporaryFileName,'w+');
		while($data = fgetcsv($file_read, 0, $delimiter)){
			fputcsv($file_write, $data, $delimiter);
		}
		fclose($file_read);
		fclose($file_write);
		return true;
	}

	static function fileUploadErrorMessage($error_code) {
		switch ($error_code) {
			case 1	:	$errorMessage = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case 2	:	$errorMessage = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case 3	:	$errorMessage = 'The uploaded file was only partially uploaded';
			case 4	:	$errorMessage = 'No file was uploaded';
			case 6	:	$errorMessage = 'Missing a temporary folder';
			case 7	:	$errorMessage = 'Failed to write file to disk';
			case 8	:	$errorMessage = 'File upload stopped by extension';
			default	:	$errorMessage = 'Unknown upload error';
		}
		return $errorMessage;
	}
}
