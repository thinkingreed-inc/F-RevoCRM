<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get the Display Name for the record
	 * @return <String> - Entity Display Name for the record
	 */
	function getDisplayName() {
		return Vtiger_Util_Helper::getRecordName($this->getId());
	}

	function getDownloadFileURL($attachmentId = false) {
		if ($this->get('filelocationtype') == 'I') {
			$fileDetails = $this->getFileDetails();
			return 'index.php?module='. $this->getModuleName() .'&action=DownloadFile&record='. $this->getId() .'&fileid='. $fileDetails['attachmentsid'].'&name='. $fileDetails['name'];
		} else {
			return $this->get('filename');
		}
	}

	function checkFileIntegrityURL() {
		return "javascript:Documents_Detail_Js.checkFileIntegrity('index.php?module=".$this->getModuleName()."&action=CheckFileIntegrity&record=".$this->getId()."')";
	}

	function checkFileIntegrity() {
		$recordId = $this->get('id');
		$downloadType = $this->get('filelocationtype');
		$returnValue = false;

		if ($downloadType == 'I') {
			$fileDetails = $this->getFileDetails();
			if (!empty ($fileDetails)) {
				$filePath = $fileDetails['path'];
                $storedFileName = $fileDetails['storedname'];

				$savedFile = $fileDetails['attachmentsid']."_".$storedFileName;

				if(fopen($filePath.$savedFile, "r")) {
					$returnValue = true;
				}
			}
		}
		return $returnValue;
	}

	function getFileDetails($attachmentId = false) {
		$db = PearDatabase::getInstance();
		$fileDetails = array();

		$result = $db->pquery("SELECT * FROM vtiger_attachments
							INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
							WHERE crmid = ?", array($this->get('id')));

		if($db->num_rows($result)) {
			$fileDetails = $db->query_result_rowdata($result);
		}
		return $fileDetails;
	}

	function downloadFile($attachmentId = false) {
		$fileDetails = $this->getFileDetails();
		$fileContent = false;

		if (!empty ($fileDetails)) {
			$filePath = $fileDetails['path'];
			$fileName = $fileDetails['name'];
            $storedFileName = $fileDetails['storedname'];

			if ($this->get('filelocationtype') == 'I') {
				$fileName = html_entity_decode($fileName, ENT_QUOTES, vglobal('default_charset'));
                if (!empty($fileName)) {
                    if(!empty($storedFileName)){
                        $savedFile = $fileDetails['attachmentsid']."_".$storedFileName;
                    }else if(is_null($storedFileName)){
                        $savedFile = $fileDetails['attachmentsid']."_".$fileName;
                    }
                    while(ob_get_level()) {
                        ob_end_clean();
                    }
                    $fileSize = filesize($filePath.$savedFile);
                    $fileSize = $fileSize + ($fileSize % 1024);

                    if (fopen($filePath.$savedFile, "r")) {
                        $fileContent = fread(fopen($filePath.$savedFile, "r"), $fileSize);

                        header("Content-type: ".$fileDetails['type']);
                        header("Pragma: public");
                        header("Cache-Control: private");
                        header("Content-Disposition: attachment; filename=\"$fileName\"");
                        header("Content-Description: PHP Generated Data");
                        header("Content-Encoding: none");
                    }
                }
			}
		}
		echo $fileContent;
	}

	function updateFileStatus() {
		$db = PearDatabase::getInstance();

		$db->pquery("UPDATE vtiger_notes SET filestatus = 0 WHERE notesid= ?", array($this->get('id')));
	}

	function updateDownloadCount() {
		$db = PearDatabase::getInstance();
		$notesId = $this->get('id');

		$result = $db->pquery("SELECT filedownloadcount FROM vtiger_notes WHERE notesid = ?", array($notesId));
		$downloadCount = $db->query_result($result, 0, 'filedownloadcount') + 1;

		$db->pquery("UPDATE vtiger_notes SET filedownloadcount = ? WHERE notesid = ?", array($downloadCount, $notesId));
	}

	function getDownloadCountUpdateUrl() {
		return "index.php?module=Documents&action=UpdateDownloadCount&record=".$this->getId();
	}
	
	function get($key) {
		$value = parent::get($key);
		if ($key === 'notecontent') {
			return decode_html($value);
		}
		return $value;
	}

	/**
	 * 電帳法対象ドキュメントかどうかを判定する
	 *
	 * @return bool
	 */
	function isComplianceTarget() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT document_category FROM vtiger_notes WHERE notesid = ?",
			array($this->getId())
		);
		if ($result === false || $db->num_rows($result) === 0) {
			return false;
		}
		$category = $db->query_result($result, 0, 'document_category');
		return !empty($category);
	}

	/**
	 * 電帳法対象ドキュメントの物理削除をブロックする
	 * ゴミ箱からの完全削除時に呼ばれる
	 *
	 * @return bool 物理削除可能な場合true
	 */
	function isDeletable() {
		// 電帳法対象ドキュメントは物理削除禁止
		if ($this->isComplianceTarget()) {
			return false;
		}
		return true;
	}

	/**
	 * 削除時に監査ログを記録する
	 */
	function logDeletion() {
		if (!$this->isComplianceTarget()) {
			return;
		}
		require_once 'modules/Documents/utils/AuditLogger.php';
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT * FROM vtiger_notes WHERE notesid = ?",
			array($this->getId())
		);
		$recordData = array();
		if ($result !== false && $db->num_rows($result) > 0) {
			$recordData = $db->query_result_rowdata($result, 0);
		}
		Documents_AuditLogger::logDelete($this->getId(), $recordData);
	}

	/**
	 * ハッシュ検証を実行する
	 *
	 * @return array ['valid' => bool, 'stored_hash' => string|null, 'current_hash' => string|null, 'message' => string]
	 */
	function verifyFileHash() {
		require_once 'modules/Documents/utils/FileHasher.php';
		return Documents_FileHasher::verifyHash($this->getId());
	}

}