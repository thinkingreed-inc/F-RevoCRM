<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

 require_once 'includes/runtime/BaseModel.php';

 class Vtiger_Runtime_Configs extends Vtiger_Base_Model {

    private static $instance = false;

    public static function getInstance() {
        if(self::$instance === false) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Function to fetch runtime connectors configured in config_override.php
     * @params $identifier - Connector identifier Ex: session
     * @params $default - Default connector class name.
     */
    public function getConnector($identifier, $default = '') {
        global $runtime_connectors;

        $connector = '';
        if(isset($runtime_connectors[$identifier])) {
            $connector = $runtime_connectors[$identifier];
        }

        if(empty($connector) && !empty($default)) {
            $connector = $default;
        }

        return $connector;
    }
    
    /**
     * Function to fetch the value for given key
     */
    public function getValidationRegex($key, $default = '') {
        global $validation_regex;
        
        $value = '';
        if(isset($validation_regex[$key])) {
            $value = $validation_regex[$key];
        }

        if(empty($value) && !empty($default)) {
            $value = $default;
        }
        return $value;
    }
 }