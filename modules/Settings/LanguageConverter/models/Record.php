<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_LanguageConverter_Record_Model extends Settings_Vtiger_Record_Model {

    /**
     * Function to get Id of this record instance
     * @return <Integer> id
     */
    public function getId() {
        return $this->get('id');
    }

    /*
     * Function to get Name of this record
     * @return <String>
     */
    public function getName() {
        return $this->get('name');
    }

    /**
     * Function to get module instance of this record
     * @return <type>
     */
    public function getModule() {
        return $this->module;
    }

    /**
     * Function to set module to this record instance
     * @param <Settings_LanguageConverter_Record_Model> $moduleModel
     * @return <Settings_LanguageConverter_Record_Model> record model
     */
    public function setModule($moduleModel) {
        $this->module = $moduleModel;
        return $this;
    }

    /**
     * Function to get display value of every field from this record
     * @param <String> $fieldName
     * @return <String>
     */
    public function getDisplayValue($fieldName) {
        $fieldValue = $this->get($fieldName);
        switch ($fieldName) {
        case 'modulename' :
            $fieldValue = vtranslate($fieldValue,  $this->module->getParentName().':'.$this->module->getName());
            break;
        default :
            break;
		}
		return $fieldValue;
    }

    public static function getInstanceById($id) {
        global $adb;
        
        $record = new self();
        if(empty($id)) {
            return $record;
        }

        $table = Settings_LanguageConverter_Module_Model::$TABLE_NAME;
        $result = $adb->pquery("SELECT id, modulename, before_string, after_string, language FROM $table WHERE id = ?", array($id));
        if($adb->num_rows($result) > 0) {
            $record->set("id", $adb->query_result($result, 0, "id"));
            $record->set("modulename" ,$adb->query_result($result, 0, "modulename"));
            $record->set("before_string", $adb->query_result($result, 0, "before_string"));
            $record->set("after_string", $adb->query_result($result, 0, "after_string"));
            $record->set("language", $adb->query_result($result, 0, "language"));
            $record->id = $record->get("id");
        }

        return $record;
    }

    public function delete() {
        global $adb;
        $table = Settings_LanguageConverter_Module_Model::$TABLE_NAME;

        $id = $this->getId();
        if(empty($id)) {
            throw new Exception("Invalid Request. Cannot delete rule.");
        }

        $adb->pquery("DELETE FROM $table WHERE id = ?", array($this->id));
    }

    public function save() {
        if(empty($this->id)) {
            $this->insert();
        } else {
            $this->update();
        }
        return $this->getId();
    }

    private function insert() {
        global $adb;
        $table = Settings_LanguageConverter_Module_Model::$TABLE_NAME;

        $adb->pquery("INSERT INTO $table(modulename, before_string, after_string, language) values (?, ?, ?, ?)",
        array($this->get("modulename"), $this->get("before_string"), $this->get("after_string"), $this->get("language"),));

        $result = $adb->query("SELECT MAX(id) as currentid FROM $table");
        if($adb->num_rows($result)) {
            $this->set("id", $adb->query_result($result, 0, "currentid"));
        }
    }

    private function update() {
        global $adb;
        $table = Settings_LanguageConverter_Module_Model::$TABLE_NAME;

        $adb->pquery("UPDATE $table SET modulename = ?, before_string = ?, after_string =?, language=? WHERE id = ?",
        array($this->get("modulename"), $this->get("before_string"), $this->get("after_string"), $this->get("language"), $this->getId()));
    }

    /*
     * Function to get Edit view url 
     */
    public function getEditViewUrl() {
        return 'module=LanguageConverter&parent=Settings&view=EditAjax&record='.$this->getId();
    }

    public function getRecordLinks() {
        $editLink = array(
            'linkurl' => "javascript:Settings_LanguageConverter_Js.triggerEdit(event, '".$this->getId()."')",
            'linklabel' => 'LBL_EDIT',
            'linkicon' => 'icon-pencil'
        );
        $editLinkInstance = Vtiger_Link_Model::getInstanceFromValues($editLink);
        
        $deleteLink = array(
            'linkurl' => "javascript:Settings_LanguageConverter_Js.triggerDelete(event,'".$this->getId()."')",
            'linklabel' => 'LBL_DELETE',
            'linkicon' => 'icon-trash'
        );
        $deleteLinkInstance = Vtiger_Link_Model::getInstanceFromValues($deleteLink);
        return array($editLinkInstance,$deleteLinkInstance);
    }
}

