<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_LanguageConverter_Module_Model extends Settings_Vtiger_Module_Model{
	var $baseTable = 'vtiger_language_rules';
	var $baseIndex = 'id';
	var $listFields = array('id' => 'ID', 'modulename' => 'Module', 'before_string' => 'Before', 'after_string' => 'After', 'language' => 'Language',);
	var $nameFields = array('');
	var $name = 'LanguageConverter';

    private static $cache = array(
        // = array($moduleName => array('id' => $id, 'before' => $before, 'after' => $after))
    );
    private static $isLoaded = false;

    public static $TABLE_NAME = "vtiger_language_rules";

    /**
     * Function to get editable fields from this module
     * @return <Array> List of fieldNames
     */
    public function getEditableFieldsList() {
        return array('modulename', 'before_string', 'after_string');
    }

    private static function createTable() {
        global $adb;

        if(Vtiger_Utils::CheckTable(self::$TABLE_NAME)) {
            return ;
        }

        $table = self::$TABLE_NAME;

        $adb->query("CREATE TABLE ${table} (
            `id` int(19) NOT NULL AUTO_INCREMENT,
            `modulename` varchar(100) NOT NULL,
            `before_string` varchar(1000) NOT NULL,
            `after_string` varchar(1000) NOT NULL,
            `language` varchar(10) default 'all',
            `sequence` int(19),
            PRIMARY KEY (`id`)
           ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
    }

    public static function convertTranslate($str, $moduleName = null, $language = 'all') {
        if(!self::$isLoaded) {
            self::loadAll();
        }
        if(count(self::$cache) == 0) {
            return $str;
        }
        // 全モジュール共通の変換
        foreach(self::$cache['common'] as $commonLang) {
            if($commonLang['language'] != $language && $commonLang['language'] != 'all') {
                continue;
            }
            $str = preg_replace('/'.$commonLang['before'].'/', $commonLang['after'], $str);
        }
        // モジュール固有の変換
        if(!empty($moduleName) && !empty(self::$cache[$moduleName])) {
            foreach(self::$cache[$moduleName] as $moduleLang) {
                if($moduleLang['language'] != $language && $moduleLang['language'] != 'all') {
                    continue;
                }
                $str = preg_replace('/'.$moduleLang['before'].'/', $moduleLang['after'], $str);
            }
        }
        return $str;
    }

    private static function loadAll() {
        global $adb;
        $table = self::$TABLE_NAME;

        // Setup Wizard
        if(!file_exists('config.inc.php')) {
            self::$isLoaded = true;
            return;
        }

        self::createTable();

        $result = $adb->query("SELECT id, modulename, before_string, after_string, language FROM $table");

        for($i=0; $i<$adb->num_rows($result); $i++) {
            $id = $adb->query_result($result, $i, 'id');
            $module = $adb->query_result($result, $i, 'modulename');
            $before_string = $adb->query_result($result, $i, 'before_string');
            $after_string = $adb->query_result($result, $i, 'after_string');
            $language = $adb->query_result($result, $i, 'language');

            self::$cache[$module][] = array(
                'id' => $id,
                'before' => $before_string,
                'after' => $after_string,
                'language' => $language,
            );
        }
        self::$isLoaded = true;
    }

    public static function getCache() {
        if(!self::$isLoaded) {
            self::loadAll();
        }
        return self::$cache;
    }

    public function isPagingSupported() {
        return false;
    }

    /*
     * Function to get Create view url 
     */
    public function getCreateRecordUrl() {
        return "javascript:Settings_LanguageConverter_Js.triggerAdd(event)";
    }

}