<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Parameters_Record_Model extends Settings_Vtiger_Record_Model {
    private static $cache = array();

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
    public function getKey() {
        return $this->get('key');
    }

    public function getValue() {
        return $this->get('value');
    }

    public function getDescription() {
        return $this->get('description');
    }

    public function getName() {
        return $this->get('key');
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
     * @param <Settings_Parameters_Record_Model> $moduleModel
     * @return <Settings_Parameters_Record_Model> record model
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

        $result = $adb->pquery("SELECT
                                    `id`,
                                    `key`,
                                    `value`,
                                    `description`
                                FROM
                                    vtiger_parameters
                                WHERE
                                    `id` = ?
                                ORDER BY
                                    `key`
            ", array($id));

        if($adb->num_rows($result) > 0) {
            $record->set("id", $adb->query_result($result, 0, "id"));
            $record->set("key", $adb->query_result($result, 0, "key"));
            $record->set("value" ,$adb->query_result($result, 0, "value"));
            $record->set("description", $adb->query_result($result, 0, "description"));
            $record->id = $record->get("id");
        }

        return $record;
    }

    public static function getInstanceByKey($key) {
        global $adb;
        
        $record = new self();
        if(empty($key)) {
            return $record;
        }

        $result = $adb->pquery("SELECT
                                    `id`,
                                    `key`,
                                    `value`,
                                    `description`
                                FROM
                                    vtiger_parameters
                                WHERE
                                    `key` = ?
            ", array($key));

        if($adb->num_rows($result) > 0) {
            $record->set("id", $adb->query_result($result, 0, "id"));
            $record->set("key", $adb->query_result($result, 0, "key"));
            $record->set("value" ,$adb->query_result($result, 0, "value"));
            $record->set("description", $adb->query_result($result, 0, "description"));
            $record->id = $record->get("id");
        }

        return $record;
    }

    public function delete() {
        global $adb;
        $adb->pquery("DELETE FROM vtiger_parameters WHERE `id` = ?", array($this->id));
    }

    public function save() {
        global $adb;
        if(empty($this->id)) {
            if(!$this->exsitsKey()) {
                throw new Exception(vtranslate("LBL_DUPLICATE_KEY", 'Settings::Parameters'));
            }
            $adb->pquery("INSERT INTO vtiger_parameters(`key`, `value`, `description`) values(?, ?, ?)"
                , array($this->get('key'), $this->get('value'), $this->get('description')));

            $id = $adb->pquery("SELECT `id` FROM vtiger_parameters WHERE `key` = ?", array($this->get('key')));
            $this->set('id', $adb->query_result($id, 0, "id"));
        } else {
            if(!$this->canUpdate()) {
                throw new Exception(vtranslate("LBL_DUPLICATE_KEY", 'Settings::Parameters'));
            }
            $adb->pquery("UPDATE vtiger_parameters SET `key` = ?, `value` = ?, `description` = ? WHERE id = ?"
                , array($this->get('key'), $this->get('value'), $this->get('description'), $this->id));
        }
        return $this->getId();
    }

    private function exsitsKey() {
        global $adb;
        $result = $adb->pquery("SELECT `id` FROM vtiger_parameters WHERE `key` = ?", array($this->get('key')));
        if($adb->num_rows($result) > 0) {
            return false;
        }
        return true;
    }

    private function canUpdate() {
        global $adb;

        $result = $adb->pquery("SELECT `key` FROM vtiger_parameters WHERE `id` = ?", array($this->id));
        if($adb->num_rows($result) <= 0) {
            throw new Exception("Cannot Update, Update Record Not Found.");
        }

        // 更新前とキーが一致していれば更新可
        if ($this->get('key') == $adb->query_result($result, 0, "key")) {
            return true;
        }

        // 更新前とキーが不一致であれば存在しているか確認
        return $this->exsitsKey();
    }

    /*
     * Function to get Edit view url 
     */
    public function getEditViewUrl() {
        return 'module=Parameters&parent=Settings&view=EditAjax&record='.$this->getId();
    }

    public function getRecordLinks() {
        $editLink = array(
            'linkurl' => "javascript:Settings_Parameters_Js.triggerEdit(event, '".$this->getId()."')",
            'linklabel' => 'LBL_EDIT',
            'linkicon' => 'icon-pencil'
        );
        $editLinkInstance = Vtiger_Link_Model::getInstanceFromValues($editLink);
        
        $deleteLink = array(
            'linkurl' => "javascript:Settings_Parameters_Js.triggerDelete(event,'".$this->getId()."')",
            'linklabel' => 'LBL_DELETE',
            'linkicon' => 'icon-trash'
        );
        $deleteLinkInstance = Vtiger_Link_Model::getInstanceFromValues($deleteLink);
        return array($editLinkInstance,$deleteLinkInstance);
    }

    /**
     * パラメーターの値を取得する
     */
    public static function getParameterValue($key, $defaultValue=null) {
        global $adb;

        if(array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $result = $adb->pquery("SELECT `value` FROM vtiger_parameters WHERE `key` = ?",array($key));
        if($adb->num_rows($result) > 0) {
            $value = $adb->query_result($result, 0, "value");
        } else {
            // システム変数に設定されていないパラメーターを取得しようとした時、デフォルト値が入力されていればデフォルト値を返す
            if (!is_null($defaultValue)) {
                return $defaultValue;
            }
        }
        self::$cache[$key] = $value;
        return self::$cache[$key];
    }
}

