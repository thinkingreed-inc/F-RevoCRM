<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class VtigerModuleOperation extends WebserviceEntityOperation {
	protected $tabId;
	protected $isEntity = true;
	protected $partialDescribeFields = null;
	
	public function __construct($webserviceObject,$user,$adb,$log)
	{
		parent::__construct($webserviceObject,$user,$adb,$log);
		$this->meta = $this->getMetaInstance();
		$this->tabId = $this->meta->getTabId();
	}
	public function VtigerModuleOperation($webserviceObject,$user,$adb,$log){
		// PHP4-style constructor.
		// This will NOT be invoked, unless a sub-class that extends `foo` calls it.
		// In that case, call the new-style constructor to keep compatibility.
		self::__construct($webserviceObject,$user,$adb,$log);
	}
	
	protected function getMetaInstance(){
		if(empty(WebserviceEntityOperation::$metaCache[$this->webserviceObject->getEntityName()][$this->user->id])){
			WebserviceEntityOperation::$metaCache[$this->webserviceObject->getEntityName()][$this->user->id]  = new VtigerCRMObjectMeta($this->webserviceObject,$this->user);
		}
		return WebserviceEntityOperation::$metaCache[$this->webserviceObject->getEntityName()][$this->user->id];
	}
	
	public function create($elementType,$element){
		$crmObject = new VtigerCRMObject($elementType, false);
		
		$element = DataTransform::sanitizeForInsert($element,$this->meta);
		
		$error = $crmObject->create($element);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		
		$id = $crmObject->getObjectId();

		// Bulk Save Mode
		if(CRMEntity::isBulkSaveMode()) {		
			// Avoiding complete read, as during bulk save mode, $result['id'] is enough
			return array('id' => vtws_getId($this->meta->getEntityId(), $id) );
		}
		
		$error = $crmObject->read($id);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
				vtws_getWebserviceTranslatedString('LBL_'.
						WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		
		return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
	}
	
	public function retrieve($id){
		
		$ids = vtws_getIdComponents($id);
		$elemid = $ids[1];
		
		$crmObject = new VtigerCRMObject($this->tabId, true);
		$error = $crmObject->read($elemid);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		
		return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
	}
	
    public function relatedIds($id, $relatedModule, $relatedLabel, $relatedHandler=null) {
		$ids = vtws_getIdComponents($id);
        $sourceModule = $this->webserviceObject->getEntityName();		
        global $currentModule;
        $currentModule = $sourceModule;
		$sourceRecordModel = Vtiger_Record_Model::getInstanceById($ids[1], $sourceModule);
		$targetModel       = Vtiger_RelationListView_Model::getInstance($sourceRecordModel, $relatedModule, $relatedLabel);
        $sql = $targetModel->getRelationQuery();

        $relatedWebserviceObject = VtigerWebserviceObject::fromName($adb,$relatedModule);
        $relatedModuleWSId = $relatedWebserviceObject->getEntityId();

		// Rewrite query to pull only crmid transformed as webservice id.
        $sqlFromPart = substr($sql, stripos($sql, ' FROM ')+6);        
        $sql = sprintf("SELECT DISTINCT concat('%sx',vtiger_crmentity.crmid) as wsid FROM %s", $relatedModuleWSId, $sqlFromPart);
                
        $rs = $this->pearDB->pquery($sql, array());
        $relatedIds = array();
		while ($row = $this->pearDB->fetch_array($rs)) {
            $relatedIds[] = $row['wsid'];
		}
		return $relatedIds;
    }
	
	public function update($element){
		$ids = vtws_getIdComponents($element["id"]);
		$element = DataTransform::sanitizeForInsert($element,$this->meta);
		
		$crmObject = new VtigerCRMObject($this->tabId, true);
		$crmObject->setObjectId($ids[1]);
		$error = $crmObject->update($element);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		
		$id = $crmObject->getObjectId();
		
		$error = $crmObject->read($id);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
				vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		
		return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
	}
	
	public function revise($element){
		$ids = vtws_getIdComponents($element["id"]);
		$element = DataTransform::sanitizeForInsert($element,$this->meta);

		$crmObject = new VtigerCRMObject($this->tabId, true);
		$crmObject->setObjectId($ids[1]);
		$error = $crmObject->revise($element);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}

		$id = $crmObject->getObjectId();

		$error = $crmObject->read($id);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}

		return DataTransform::filterAndSanitize($crmObject->getFields(),$this->meta);
	}

	public function delete($id){
		$ids = vtws_getIdComponents($id);
		$elemid = $ids[1];
		
		$crmObject = new VtigerCRMObject($this->tabId, true);
		
		$error = $crmObject->delete($elemid);
		if(!$error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		return array("status"=>"successful");
	}
	
	public function query($q){
		
		$parser = new Parser($this->user, $q);
		$error = $parser->parse();
		
		if($error){
			return $parser->getError();
		}
		
		$mysql_query = $parser->getSql();
		$meta = $parser->getObjectMetaData();
		$this->pearDB->startTransaction();
		$result = $this->pearDB->pquery($mysql_query, array());
        $tableIdColumn = $meta->getIdColumn();
		$error = $this->pearDB->hasFailedTransaction();
		$this->pearDB->completeTransaction();
		
		if($error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}
		
		$noofrows = $this->pearDB->num_rows($result);
		$output = array();
		for($i=0; $i<$noofrows; $i++){
			$row = $this->pearDB->fetchByAssoc($result,$i);
			if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$row[$tableIdColumn])){
				continue;
			}
			if(strpos(explode("FROM",$mysql_query)[0], 'vtiger_inventoryproductrel') === false){
				// 明細行が含まれていない場合
				$output[$row[$tableIdColumn]] = DataTransform::sanitizeDataWithColumn($row,$meta);
			}else{
				// 明細行が含まれている場合
				$output[] = DataTransform::sanitizeDataWithColumn($row,$meta);
			}
		}
		
		$newOutput = array();
        if(php7_count($output)) {
            //Added check if tags was requested or not
            if(stripos($mysql_query, $meta->getEntityBaseTable().'.tags') !== false) $tags = Vtiger_Tag_Model::getAllAccessibleTags(array_keys($output));
            foreach($output as $id => $row1) {
                if(!empty($tags[$id])) $output[$id]['tags'] = $tags[$id];
                $newOutput[] = $output[$id];
	}
        }
		return $newOutput;
	}
	
	public function describe($elementType){
		$app_strings = VTWS_PreserveGlobal::getGlobal('app_strings');
		$current_user = vtws_preserveGlobal('current_user',$this->user);;
		
		$label = (isset($app_strings[$elementType]))? $app_strings[$elementType]:$elementType;
		$createable = (strcasecmp(isPermitted($elementType,EntityMeta::$CREATE),'yes')===0)? true:false;
		$updateable = (strcasecmp(isPermitted($elementType,EntityMeta::$UPDATE),'yes')===0)? true:false;
		$deleteable = $this->meta->hasDeleteAccess();
		$retrieveable = $this->meta->hasReadAccess();
		$fields = $this->getModuleFields();
		return array(	'label'			=> $label,
						'name'			=> $elementType,
						'createable'	=> $createable,
						'updateable'	=> $updateable,
						'deleteable'	=> $deleteable,
						'retrieveable'	=> $retrieveable,
						'fields'		=> $fields,
						'idPrefix'		=> $this->meta->getEntityId(),
						'isEntity'		=> $this->isEntity,
						'allowDuplicates'=>  $this->meta->isDuplicatesAllowed(),
						'labelFields'	=> $this->meta->getNameFields());
	}
	
	public function describePartial($elementType, $fields=null) {
		$this->partialDescribeFields = $fields;
		$result = $this->describe($elementType);
		$this->partialDescribeFields = null;
		return $result;
	}
	
	function getModuleFields(){
		
		$fields = array();
		$moduleFields = $this->meta->getModuleFields();
		foreach ($moduleFields as $fieldName=>$webserviceField) {
			if(((int)$webserviceField->getPresence()) == 1) {
				continue;
			}
			array_push($fields,$this->getDescribeFieldArray($webserviceField));
		}
		array_push($fields,$this->getIdField($this->meta->getObectIndexColumn()));
		
		return $fields;
	}
	
	function getDescribeFieldArray($webserviceField){
		$default_language = VTWS_PreserveGlobal::getGlobal('default_language');
		
		$fieldLabel = getTranslatedString($webserviceField->getFieldLabelKey(), $this->meta->getTabName());
		
		$typeDetails = array();
		if (!is_array($this->partialDescribeFields)) {
			$typeDetails = $this->getFieldTypeDetails($webserviceField);
		} else if (in_array($webserviceField->getFieldName(), $this->partialDescribeFields)) {
			$typeDetails = $this->getFieldTypeDetails($webserviceField);
		}
		
		//set type name, in the type details array.
		$typeDetails['name'] = $webserviceField->getFieldDataType();
		//Reference module List is missing in DescribePartial api response
		if($typeDetails['name'] === "reference") {
			$typeDetails['refersTo'] = $webserviceField->getReferenceList();
		}
		$editable = $this->isEditable($webserviceField);
		
		$describeArray = array(	'name'		=> $webserviceField->getFieldName(),
								'label'		=> $fieldLabel,
								'mandatory'	=> $webserviceField->isMandatory(),
								'type'		=> $typeDetails,
								'isunique'	=> $webserviceField->isUnique(),
								'nullable'	=> $webserviceField->isNullable(),
								'editable'	=> $editable);
		if($webserviceField->hasDefault()){
			$describeArray['default'] = $webserviceField->getDefault();
		}
		return $describeArray;
	}
	
	function getMeta(){
		return $this->meta;
	}
	
	function getField($fieldName){
		$moduleFields = $this->meta->getModuleFields();
		return $this->getDescribeFieldArray($moduleFields[$fieldName]);
	}
    
    /**
     * Function to get the file content
     * @param type $id
     * @return type
     * @throws WebServiceException
     */
    public function file_retrieve($crmid, $elementType, $attachmentId=false){
		$ids = vtws_getIdComponents($crmid);
		$crmid = $ids[1];
        $recordModel = Vtiger_Record_Model::getInstanceById($crmid, $elementType);
        if($attachmentId) {
            $attachmentDetails = $recordModel->getFileDetails($attachmentId);
        } else {
            $attachmentDetails = $recordModel->getFileDetails();
        }
        $fileDetails = array();
        if (!empty ($attachmentDetails)) {
            if(is_array(current(($attachmentDetails)))) {
                foreach ($attachmentDetails as $key => $attachment) {
                    $fileDetails[$key] = vtws_filedetails($attachment);
                }
            } else if(is_array($attachmentDetails)){
                $fileDetails[] = vtws_filedetails($attachmentDetails);
            }
        }
        return $fileDetails;
	}
	
}
?>
