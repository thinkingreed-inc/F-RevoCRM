<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_HolidayManager_Record_Model extends Settings_LanguageConverter_Record_Model {
    public static function getInstanceById($id) {
        global $adb;
        
        $record = new self();
        if(empty($id)) {
            return $record;
        }

        $table = Settings_HolidayManager_Module_Model::$TABLE_NAME;
        $result = $adb->pquery("SELECT id, holidayname, date FROM $table WHERE id = ?", array($id));
        if($adb->num_rows($result) > 0) {
            $record->set("id", $adb->query_result($result, 0, "id"));
            $record->set("holidayname" ,$adb->query_result($result, 0, "holidayname"));
            $record->set("date", $adb->query_result($result, 0, "date"));
            $record->id = $record->get("id");
        }

        return $record;
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
        $table = Settings_HolidayManager_Module_Model::$TABLE_NAME;
        $adb->pquery("INSERT INTO $table(holidayname, date) values (?, ?)",
        array($this->get("holidayname"), $this->get("date"),));

        $result = $adb->query("SELECT MAX(id) as currentid FROM $table");
        if($adb->num_rows($result)) {
            $this->set("id", $adb->query_result($result, 0, "currentid"));
        }
    }

    private function update() {
        global $adb;
        $table = Settings_HolidayManager_Module_Model::$TABLE_NAME;

        $adb->pquery("UPDATE $table SET holidayname = ?, date = ? WHERE id = ?",
        array($this->get("holidayname"), $this->get("date"),   $this->getId()));
    }

    public function delete() {
        global $adb;
        $table = Settings_HolidayManager_Module_Model::$TABLE_NAME;

        $id = $this->getId();
        if(empty($id)) {
            throw new Exception("Invalid Request. Cannot delete rule.");
        }

        $adb->pquery("DELETE FROM $table WHERE id = ?", array($this->id));
    }
    
    public function checkHolidays($year){
        global $adb;   
        
        $result = $adb->pquery("SELECT  year FROM vtiger_holiday WHERE year = ?", array($year));
        if(!empty($adb->query_result($result, 0, "year"))){
            return true;
        }else{
            self::getHolidays($year);
            return true;
        }
    }

    function getHolidays($year){
        $apiurl = 'https://holidays-jp.github.io/api/v1/'.$year.'/date.json';

        $db = PearDatabase::getInstance();
        $jsonholiday = mb_convert_encoding(file_get_contents($apiurl), 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        $jsonholiday = json_decode($jsonholiday);
        
        foreach($jsonholiday as $key => $value){
            $key = DateTimeField::convertToDBTimeZone($key);
            $key = $key->format('Y-m-d H:i:s');
            $query = "INSERT INTO vtiger_holiday (date, holidayname,year) VALUES(?,?,?)";
            $db->pquery($query,array($key,$value,$year));
        }

    }
    public function getRecordLinks() {
        $editLink = array(
            'linkurl' => "javascript:Settings_HolidayManager_Js.triggerEdit(event, '".$this->getId()."')",
            'linklabel' => 'LBL_EDIT',
            'linkicon' => 'icon-pencil'
        );
        $editLinkInstance = Vtiger_Link_Model::getInstanceFromValues($editLink);
        
        $deleteLink = array(
            'linkurl' => "javascript:Settings_HolidayManager_Js.triggerDelete(event,'".$this->getId()."')",
            'linklabel' => 'LBL_DELETE',
            'linkicon' => 'icon-trash'
        );
        $deleteLinkInstance = Vtiger_Link_Model::getInstanceFromValues($deleteLink);
        return array($editLinkInstance,$deleteLinkInstance);
    }
}