<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_TagCloud_Action extends Vtiger_Mass_Action {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
		$this->exposeMethod('saveTags');
		$this->exposeMethod('update');
		$this->exposeMethod('remove');
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function saves a tag for a record
	 * @param Vtiger_Request $request
	 */
	public function save(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();

		$tagModel = new Vtiger_Tag_Model();
		$tagModel->set('userid', $currentUser->id);
		$tagModel->set('record', $request->get('record'));
		$tagModel->set('tagname', decode_html($request->get('tagname')));
		$tagModel->set('module', $request->getModule());
		$tagModel->save();

		$taggedInfo = Vtiger_Tag_Model::getAll($currentUser->id, $request->getModule(), $request->get('record'));
		$response = new Vtiger_Response();
		$response->setResult($taggedInfo);
		$response->emit($taggedInfo);
	}

	/**
	 * Function deleted a tag
	 * @param Vtiger_Request $request
	 */
	public function delete(Vtiger_Request $request) {
		$tagModel = new Vtiger_Tag_Model();
		$tagModel->set('record', $request->get('record'));
		$tagModel->set('tag_id', $request->get('tag_id'));
		$tagModel->delete();
	}

	/**
	 * Function returns list of tage for the record
	 * @param Vtiger_Request $request
	 */
	public function getTags(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$record = $request->get('record');
		$module = $request->getModule();
		$tags = Vtiger_Tag_Model::getAll($currentUser->id, $module, $record);

		$response = new Vtiger_Response();
		$response->emit($tags);
	}

	public function saveTags(Vtiger_Request $request) {
		$module = $request->get('module');
		$parent = $request->get('addedFrom');

		if($request->has('selected_ids')) {
			$recordIds = $this->getRecordsListFromRequest($request);
		}else{
			$recordIds = array($request->get('record'));
		}

		if($parent && $parent == 'Settings'){
			$recordIds = array();
		}

		$tagsList = $request->get('tagsList');
		$newTags = $tagsList['new'];
		if(empty($newTags)) {
			$newTags = array();
		}
		$existingTags = $tagsList['existing'];
		if(empty($existingTags)) {
			$existingTags = array();
		}
		$deletedTags = $tagsList['deleted'];
		if(empty($deletedTags)) {
			$deletedTags = array();
		}
		$newTagType = $request->get('newTagType');
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser->getId();
		if(!is_array($existingTags)) {
			$existingTags = array();
		}

		$result = array();
		foreach($newTags as $tagName) {
			if(empty($tagName)) continue;
			if(!self::checkTagExistence(array('tagId' => '', 'tagName' => $tagName, 'visibility' => ''))) {
				$result['successfullSaveMessage'] = 'JS_TAG_SAVED_EXCLUDING_DUPLICATES';
				continue;
			}
			$tagModel = new Vtiger_Tag_Model();
			$tagModel->set('tag', $tagName)->setType($newTagType);
			$tagId = $tagModel->create();
			array_push($existingTags, $tagId);
			self::updateCachedDBTags(array('tagId' => $tagId, 'tagName' => $tagName, 'visibility' => $newTagType, 'owner' => $userId));
			$result['new'][$tagId] = array('name'=> decode_html($tagName), 'type' => $newTagType);
		}
		$existingTags = array_unique($existingTags);

		foreach($recordIds as $recordId) {
			if(!empty($recordId)){
				Vtiger_Tag_Model::saveForRecord($recordId, $existingTags, $userId, $module);
				Vtiger_Tag_Model::deleteForRecord($recordId, $deletedTags, $userId, $module);
			}
		}


		$allAccessibleTags =  Vtiger_Tag_Model::getAllAccessible($userId, $module, $recordId);
		foreach ($allAccessibleTags as $tagModel) {
			$result['tags'][] = array('name'=> decode_html($tagModel->getName()), 'type'=>$tagModel->getType(),'id' => $tagModel->getId());
		}
		$allAccessibleTagCount = php7_count($allAccessibleTags);
		$result['moreTagCount'] = $allAccessibleTagCount - Vtiger_Tag_Model::NUM_OF_TAGS_DETAIL;
		$result['deleted'] = $deletedTags;

		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function update(Vtiger_Request $request) {
		$module = $request->get('module');
		$tagId = $request->get('id');
		$tagName = $request->get('name');
		$visibility = $request->get('visibility');
		$currentUser = Users_Record_Model::getCurrentUserModel();

		$response = new Vtiger_Response();
		try{
			$tagModel = Vtiger_Tag_Model::getInstanceById($tagId);
			if(!self::checkTagExistence(array('tagId' => $tagId, 'tagName' => $tagName, 'visibility' => $visibility))) {
				throw new Exception(vtranslate('LBL_SAME_TAG_EXISTS', $module, $tagName));
			}
			if($tagModel->getType() == Vtiger_Tag_Model::PUBLIC_TYPE && $visibility == Vtiger_Tag_Model::PRIVATE_TYPE) {
				//TODO : check if there are no other records tagged by other users 
			   if(Vtiger_Tag_Model::checkIfOtherUsersUsedTag($tagId, $currentUser->getId())) {
				   throw new Exception(vtranslate('LBL_CANT_MOVE_FROM_PUBLIC_TO_PRIVATE'));
			   } 
			}
			$tagModel->setName($tagName)->setType($visibility);
			$tagModel->update();
			$result = array();
			$result['name'] = $tagName;
			$result['type'] = $visibility;

			$response->setResult($result);
		}catch(Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function remove(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$tagId = $request->get('tag_id');
		if( Vtiger_Tag_Model::checkIfOtherUsersUsedTag($tagId, $currentUser->getId())) {
			throw new Exception(vtranslate('LBL_CANNOT_DELETE_TAG'));
		}
		$tagModel = new Vtiger_Tag_Model();
		$tagModel->setId($tagId);

		$response = new Vtiger_Response();
		try{
			$tagModel->remove();
			$response->setResult(array('success' => true));
		}catch(Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	/*
	新規作成・再編集・インポート時の確認チャート(TRUE: 作成許可)
	1. vtiger_freetagsに同名のタグがない -> TRUE
	2. vtiger_freetagsに同名のタグがある -> 同名タグがpublicである  -> FALSE※
	3.                　　　　　　　　　 -> 同名タグがprivateである -> ownerが自身 → FALSE
	4.                　　　　　　　　　 -> 　　　　　       　　　 -> ownerが自身でない → TRUE
	※ 再編集時にprivateからpublicへ変更する場合は, 運用負荷の観点からFALSEとせずにownerの判定(3)へ進める
	*/
	public function checkTagExistence($TagValue){
		// tagデータをキャッシュに登録
		$adb = PearDatabase::getInstance();
		$DBTagsValue = Vtiger_Cache::get('DBTags', 'DBTagsValue');
		if(!$DBTagsValue){
			$result = $adb->pquery("SELECT id,tag,visibility,owner FROM vtiger_freetags;", array());
			$DBTagsValue = array();
			for ($i = 0; $i < $adb->num_rows($result); $i++){
				$DBTagsValue[$i] = array(
					'id' => $adb->query_result($result, $i, 'id'),
					'tag' => $adb->query_result($result, $i, 'tag'),
					'owner' => $adb->query_result($result, $i, 'owner'),
					'visibility' => $adb->query_result($result, $i, 'visibility')
				);
			}
			Vtiger_Cache::set('DBTags', 'DBTagsValue', $DBTagsValue);
		}

		$tagName = $TagValue['tagName'];
		$tagId = $TagValue['tagId'];
		$previousVisibility = '';
		$OtherTagsWithSameName = array_filter($DBTagsValue, function($item) use ($tagName, $tagId, &$previousVisibility) {
			if($item['id'] == $tagId){
				$previousVisibility = $item['visibility'];
				return false;
			}
			return $item['tag'] == $tagName;
		});
		$OtherTagsWithSameName = array_values($OtherTagsWithSameName);

		// 確認チャート1 ~ 4に従い, 新規作成・再編集・インポートの許可判定を行う
		if(empty($OtherTagsWithSameName)){ // 1
			return true;
		}
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser->getId();
		for($i = 0; $i < count($OtherTagsWithSameName); $i++){
			$OtherTagValue = $OtherTagsWithSameName[$i];
			if($OtherTagValue['visibility'] == 'public'){ // 2
				if($previousVisibility == 'private' && $TagValue['visibility'] == 'public'){ // 再編集時の例外判定
					continue;
				}else{
					return false;
				}
			}
			if($OtherTagValue['owner'] == $userId){ // 3
				return false;
			}
		}
		return true; // 4
	}

	// タグが複数登録される場合に備え, キャッシュを更新する
	public function updateCachedDBTags($TagValue){
		$DBTagsValue = Vtiger_Cache::get('DBTags', 'DBTagsValue');
		$DBTagsValue[] = array(
			'id' => $TagValue['tagId'],
			'tag' => $TagValue['tagName'],
			'owner' => $TagValue['owner'],
			'visibility' => $TagValue['visibility']
		);
		Vtiger_Cache::set('DBTags', 'DBTagsValue', $DBTagsValue);
	}
}
