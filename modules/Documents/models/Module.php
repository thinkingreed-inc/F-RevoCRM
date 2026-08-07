<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Module_Model extends Vtiger_Module_Model {

	/**
	 * 実際にアップロード可能な最大サイズ（バイト）を返す
	 *
	 * vtiger の $upload_maxsize と PHP の upload_max_filesize / post_max_size の
	 * 最も小さい値が実効上限になる。
	 *
	 * @return int
	 */
	public static function getEffectiveMaxUploadSizeInBytes() {
		$limits = array();

		$vtigerLimit = (int) vglobal('upload_maxsize');
		if ($vtigerLimit > 0) {
			$limits[] = $vtigerLimit;
		}
		foreach (array('upload_max_filesize', 'post_max_size') as $iniKey) {
			$iniLimit = self::parseIniSize(ini_get($iniKey));
			if ($iniLimit > 0) {
				$limits[] = $iniLimit;
			}
		}

		return empty($limits) ? 0 : min($limits);
	}

	/**
	 * php.ini のサイズ表記（2M / 8K など）をバイトに変換する
	 *
	 * @param string $value
	 * @return int 0 は無制限または解釈不能
	 */
	private static function parseIniSize($value) {
		$value = trim((string) $value);
		if ($value === '' || $value === '-1') {
			return 0;
		}
		$unit = strtolower(substr($value, -1));
		$number = (int) $value;
		switch ($unit) {
			case 'g':
				return $number * 1024 * 1024 * 1024;
			case 'm':
				return $number * 1024 * 1024;
			case 'k':
				return $number * 1024;
			default:
				return $number;
		}
	}

	/** 分割アップロードの既定の上限（2GB） */
	const DEFAULT_CHUNK_UPLOAD_MAXSIZE = 2147483648;

	/** 1リクエストあたりの余裕（他のPOSTフィールドとmultipartのオーバーヘッド分） */
	const CHUNK_SIZE_MARGIN = 262144;

	/** 分割サイズを丸める単位（64KB） */
	const CHUNK_SIZE_UNIT = 65536;

	/**
	 * 最大アップロードサイズを表示用の文字列にする（例: 2 MB）
	 *
	 * @param int|null $bytes 省略時は1リクエストの実効上限
	 * @return string
	 */
	public static function getEffectiveMaxUploadSizeLabel($bytes = null) {
		if ($bytes === null) {
			$bytes = self::getEffectiveMaxUploadSizeInBytes();
		}
		if ($bytes <= 0) {
			return '';
		}
		if ($bytes >= 1024 * 1024 * 1024) {
			return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
		}
		if ($bytes >= 1024 * 1024) {
			return round($bytes / (1024 * 1024), 1) . ' MB';
		}
		return round($bytes / 1024) . ' KB';
	}

	/**
	 * 分割アップロード1回分のサイズ（バイト）を返す
	 *
	 * PHP の upload_max_filesize / post_max_size を超えないよう、
	 * 実効上限から余裕を引いた値を使う。
	 *
	 * @return int
	 */
	public static function getChunkSizeInBytes() {
		$limit = self::getEffectiveMaxUploadSizeInBytes();
		if ($limit <= 0) {
			// 上限なしの場合は 8MB 単位で送る
			return 8 * 1024 * 1024;
		}

		$chunkSize = $limit - self::CHUNK_SIZE_MARGIN;
		if ($chunkSize < self::CHUNK_SIZE_UNIT) {
			// マージンを引くと小さすぎる設定では上限の8割を使う
			// （multipart のオーバーヘッドがあるため、上限と同じ値にはしない）
			$chunkSize = (int) floor($limit * 0.8);
		}

		// 64KB 単位に切り下げる
		$rounded = (int) (floor($chunkSize / self::CHUNK_SIZE_UNIT) * self::CHUNK_SIZE_UNIT);
		if ($rounded > 0) {
			return $rounded;
		}
		// 上限が 64KB 未満の極端な設定。1リクエストの上限は必ず下回るようにする
		return max(1, (int) floor($limit * 0.8));
	}

	/**
	 * 分割アップロードで受け付ける1ファイルの最大サイズ（バイト）を返す
	 *
	 * config.customize.php の $documents_upload_maxsize で変更できる。
	 * 未設定の場合は 2GB。
	 *
	 * @return int
	 */
	public static function getChunkUploadMaxSizeInBytes() {
		$configured = vglobal('documents_upload_maxsize');
		if (!empty($configured) && (int) $configured > 0) {
			return (int) $configured;
		}
		return self::DEFAULT_CHUNK_UPLOAD_MAXSIZE;
	}

	/**
	 * Functions tells if the module supports workflow
	 * @return boolean
	 */
	public function isWorkflowSupported() {
		return true;
	}

	/**
	 * Function to check whether the module is summary view supported
	 * @return <Boolean> - true/false
	 */
	public function isSummaryViewSupported() {
		return false;
	}
	
	public function isExcelEditAllowed() {
		return false;
	}
	
	/**
	 * Function returns the url which gives Documents that have Internal file upload
	 * @return string
	 */
	public function getInternalDocumentsURL() {
		return 'view=Popup&module=Documents&src_module=Emails&src_field=composeEmail';
	}

	/**
	 * Function returns list of folders
	 * @return <Array> folder list
	 */
	public static function getAllFolders() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_attachmentsfolder ORDER BY sequence', array());

		$folderList = array();
		for($i=0; $i<$db->num_rows($result); $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$folderList[] = Documents_Folder_Model::getInstanceByArray($row);
		}
		return $folderList;
	}

	/**
	 * Function to get list view query for popup window
	 * @param <String> $sourceModule Parent module
	 * @param <String> $field parent fieldname
	 * @param <Integer> $record parent id
	 * @param <String> $listQuery
	 * @return <String> Listview Query
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, $listQuery) {
		if($sourceModule === 'Emails' && $field === 'composeEmail') {
			$condition = ' (( vtiger_notes.filelocationtype LIKE "%I%")) AND vtiger_notes.filename != "" AND vtiger_notes.filestatus = 1';
		} else {
            		$db = PearDatabase::getInstance();
			$condition = " vtiger_notes.notesid NOT IN (SELECT notesid FROM vtiger_senotesrel WHERE crmid = ?) AND vtiger_notes.filestatus = 1";
            		$condition = $db->convert2Sql($condition, array($record));
		}
		$pos = stripos($listQuery, 'where');
		if($pos) {
			$split = preg_split('/where/i', $listQuery);
			$overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
		} else {
			$overRideQuery = $listQuery. ' WHERE ' . $condition;
		}
		return $overRideQuery;
	}

	/**
	 * Funtion that returns fields that will be showed in the record selection popup
	 * @return <Array of fields>
	 */
	public function getPopupViewFieldsList() { 
		$popupFileds = $this->getSummaryViewFieldsList();
		$reqPopUpFields = array('File Status' => 'filestatus', 
								'File Size' => 'filesize', 
								'File Location Type' => 'filelocationtype'); 
		foreach ($reqPopUpFields as $fieldLabel => $fieldName) {
			$fieldModel = Vtiger_Field_Model::getInstance($fieldName,$this); 
			if ($fieldModel->getPermissions('readonly')) { 
				$popupFileds[$fieldName] = $fieldModel; 
			}
		}
		return array_keys($popupFileds); 
	}

	/**
	 * Function to get the url for add folder from list view of the module
	 * @return <string> - url
	 */
	public function getAddFolderUrl() {
		return 'index.php?module='.$this->getName().'&view=AddFolder';
	}
	
	/**
	 * Function to get Alphabet Search Field 
	 */
	public function getAlphabetSearchField(){
		return 'notes_title';
	}
	
	/**
     * Function that returns related list header fields that will be showed in the Related List View
     * @return <Array> returns related fields list.
     */
	public function getRelatedListFields() {
		$relatedListFields = parent::getRelatedListFields();
		
		//Adding filestatus, filelocationtype in the related list to be used for file download
		$relatedListFields['filestatus'] = 'filestatus';
		$relatedListFields['filelocationtype'] = 'filelocationtype';
		
		return $relatedListFields;
	}
    
    /**
	* Function is used to give links in the All menu bar
	*/
	public function getQuickMenuModels() {
		if($this->isEntityModule()) {
			$moduleName = $this->getName();
            
			$createPermission = Users_Privileges_Model::isPermitted($moduleName, 'CreateView');
            if($createPermission) {
                $basicListViewLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_INTERNAL_DOCUMENT_TYPE',
					'linkurl' => 'javascript:Vtiger_Header_Js.getQuickCreateFormForModule("index.php?module=Documents&view=EditAjax&type=I","Documents")',
					'linkicon' => ''
				);
                $basicListViewLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_EXTERNAL_DOCUMENT_TYPE',
					'linkurl' => 'javascript:Vtiger_Header_Js.getQuickCreateFormForModule("index.php?module=Documents&view=EditAjax&type=E")',
					'linkicon' => ''
				);
            }
           
		}
		if($basicListViewLinks) {
			foreach($basicListViewLinks as $basicListViewLink) {
				if(is_array($basicListViewLink)) {
					$links[] = Vtiger_Link_Model::getInstanceFromValues($basicListViewLink);
				} else if(is_a($basicListViewLink, 'Vtiger_Link_Model')) {
					$links[] = $basicListViewLink;
				}
			}
		}
		return $links;
	}
    
    /*
     * Function to get supported utility actions for a module
     */
    function getUtilityActionsNames() {
        return array('Export');
    }

	public function getConfigureRelatedListFields() {
		$showRelatedFieldModel = $this->getHeaderAndSummaryViewFieldsList();
		$relatedListFields = array();
        $defaultFields = array();
		if(php7_count($showRelatedFieldModel) > 0) {
			foreach ($showRelatedFieldModel as $key => $field) {
				$relatedListFields[$field->get('column')] = $field->get('name');
			}
            $defaultFields = array(
                'title' => 'notes_title',
                'filename' => 'filename'
            );
		}

		foreach($defaultFields as $columnName => $fieldName) {
			if(!array_key_exists($columnName, $relatedListFields)) {
				$relatedListFields[$columnName] = $fieldName;
			}
		}
		return $relatedListFields;
	}

	public function isFieldsDuplicateCheckAllowed() {
		return false;
	}
}
