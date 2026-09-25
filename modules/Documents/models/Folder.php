<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Folder_Model extends Vtiger_Base_Model {

	/**
	 * Function returns duplicate record status of the module
	 *
	 * 同名判定は同じ親フォルダの中だけで行う。
	 * 別階層の同名フォルダ（例: 2024/請求書 と 2025/請求書）は重複としない。
	 * 親は parent_folderid に設定された値を使う（未設定はルート = 0）。
	 *
	 * @return true if duplicate records exists else false
	 */
	public function checkDuplicate() {
		$db = PearDatabase::getInstance();
		$folderName = $this->getName();
		// 新規作成時は ID が無い。NULL のまま比較すると `folderid != NULL` が NULL となり
		// 1件も該当せず（＝重複を検知できず）同名フォルダが作れてしまうため 0 にする
		$folderId = (int) $this->getId();
		$parentFolderId = (int) $this->get('parent_folderid');
		//added folder id check to support folder edit feature
		$result = $db->pquery(
			"SELECT 1 FROM vtiger_attachmentsfolder
			WHERE foldername = ? AND COALESCE(parent_folderid, 0) = ? AND folderid != ?",
			array($folderName, $parentFolderId, $folderId)
		);
		$num_rows = $db->num_rows($result);
		if ($num_rows > 0) {
			return true;
		}
		return false;
	}

	/**
	 * 同じ親フォルダの下から同名フォルダを探す
	 *
	 * @param string $folderName
	 * @param int $parentFolderId 0 はルート
	 * @return int 見つかったフォルダID。無ければ 0
	 */
	public static function findByNameAndParent($folderName, $parentFolderId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT folderid FROM vtiger_attachmentsfolder
			WHERE foldername = ? AND COALESCE(parent_folderid, 0) = ?
			ORDER BY folderid ASC",
			array($folderName, (int) $parentFolderId)
		);
		if ($result === false || $db->num_rows($result) === 0) {
			return 0;
		}
		return (int) $db->query_result($result, 0, 'folderid');
	}

	/**
	 * Function returns whether documents are exist or not in that folder
	 * @return true if exists else false
	 */
	public function hasDocuments() {
		$db = PearDatabase::getInstance();
		$folderId = $this->getId();

		$result = $db->pquery("SELECT 1 FROM vtiger_notes
						INNER JOIN vtiger_attachmentsfolder ON vtiger_attachmentsfolder.folderid = vtiger_notes.folderid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
						WHERE vtiger_attachmentsfolder.folderid = ?
						AND vtiger_attachmentsfolder.foldername != 'Default'
						AND vtiger_crmentity.deleted = 0", array($folderId));
		$num_rows = $db->num_rows($result);
		if ($num_rows>0) {
			return true;
		}
		return false;
	}

	/**
	 * Function to add the new folder
	 * @return Documents_Folder_Model
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$folderName = $this->getName();
		$folderDesc = $this->get('description');

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserId = $currentUserModel->getId();

		if($this->get('mode') != 'edit') {
			$result = $db->pquery("SELECT max(sequence) AS max, max(folderid) AS max_folderid FROM vtiger_attachmentsfolder", array());
			$sequence = $db->query_result($result, 0, 'max') + 1;
			$folderId = $db->query_result($result,0,'max_folderid') + 1;
			$params = array($folderId,$folderName, $folderDesc, $currentUserId, $sequence);

			$result = $db->pquery("INSERT INTO vtiger_attachmentsfolder(folderid,foldername, description, createdby, sequence) VALUES(?, ?, ?, ?, ?)", $params);
			// 失敗しても呼び出し元は getId() で採番済みIDを使ってしまい、
			// 存在しないフォルダに対する権限行などが残るため、ここで止める
			if ($result === false) {
				throw new Exception('Failed to create folder');
			}

			$this->set('sequence', $sequence);
			$this->set('createdby', $currentUserId);
			$this->set('folderid',$folderId);
		} else {
			$db->pquery('UPDATE vtiger_attachmentsfolder SET foldername=?, description=? WHERE folderid=?', array($folderName, $folderDesc, $this->getId()));
		}

		return $this;
	}

	/**
	 * Function to delete existing folder
	 * @return Documents_Folder_Model
	 */
	public function delete() {
		$db = PearDatabase::getInstance();
		$folderId = $this->getId();
		$result = $db->pquery("DELETE FROM vtiger_attachmentsfolder WHERE folderid = ? AND foldername != 'Default'", array($folderId));
		return $this;
	}

	/**
	 * Function return an instance of Folder Model
	 * @return Documents_Folder_Model
	 */
	public static function getInstance() {
		return new self();
	}

	/**
	 * Function returns an instance of Folder Model
	 * @param foldername
	 * @return Documents_Folder_Model
	 */
	public static function getInstanceById($folderId) {
		$db = PearDatabase::getInstance();
		$folderModel = Documents_Folder_Model::getInstance();

		$result = $db->pquery("SELECT * FROM vtiger_attachmentsfolder WHERE folderid = ?", array($folderId));
		$num_rows = $db->num_rows($result);
		if ($num_rows > 0) {
			$values = $db->query_result_rowdata($result, 0);
			$folderModel->setData($values);
		}
		return $folderModel;
	}

	/**
	 * Function returns an instance of Folder Model
	 * @param <Array> row
	 * @return Documents_Folder_Model
	 */
	public static function getInstanceByArray($row) {
		$folderModel = Documents_Folder_Model::getInstance();
		return $folderModel->setData($row);
	}

	/**
	 * Function returns Folder's Delete url
	 * @return <String> - Delete Url
	 */
	public function getDeleteUrl() {
		$folderName = $this->getName();
		return "index.php?module=Documents&action=Folder&mode=delete&foldername=$folderName";
	}

	/**
	 * Function to get the id of the folder
	 * @return <Number>
	 */
	public function getId() {
		return $this->get('folderid');
	}

	/**
	 * Function to get the name of the folder
	 * @return <String>
	 */
	public function getName() {
		return $this->get('foldername');
	}

	/**
	 * Function to get the description of the folder
	 * @return <String>
	 */
	function getDescription() {
		return $this->get('description');
	}

	/**
	 * Function to get info array while saving a folder
	 * @return Array  info array
	 */
	public function getInfoArray() {
		return array(
			'folderName'=> $this->getName(),
			'folderid'	=> $this->getId(),
			'folderDesc'=> $this->getDescription()
		);
	}

}
?>
