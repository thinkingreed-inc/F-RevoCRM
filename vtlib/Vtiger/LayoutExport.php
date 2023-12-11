<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once('vtlib/Vtiger/Package.php');

/**
 * Provides API to package vtiger CRM layout files.
 * @package vtlib
 */
class Vtiger_LayoutExport extends Vtiger_Package {
    const TABLENAME = 'vtiger_layout';

    /**
     * Constructor
     */
    function __construct() {
            parent::__construct();
    }

    /**
     * Generate unique id for insertion
     * @access private
     */
    static function __getUniqueId() {
            global $adb;
            return $adb->getUniqueID(self::TABLENAME);
    }

    /**
     * Initialize Export
     * @access private
     */
    function __initExport($layoutName, $moduleInstance = false) {
            // Security check to ensure file is withing the web folder.
            Vtiger_Utils::checkFileAccessForInclusion("layouts/$layoutName/skins/vtiger/style.less");

            $this->_export_modulexml_file = fopen($this->__getManifestFilePath(), 'w');
            $this->__write("<?xml version='1.0'?>\n");
    }

    /**
     * Export Module as a zip file.
     * @param Layout name to be export
     * @param Path Output directory path
     * @param String Zipfilename to use
     * @param Boolean True for sending the output as download
     */
    function export($layoutName, $todir='', $zipfilename='', $directDownload=false, $extra=false) {
            $this->__initExport($layoutName);

            // Call layout export function
            $this->export_Layout($layoutName);

            $this->__finishExport();

            // Export as Zip
            if($zipfilename == '') $zipfilename = "$layoutName-" . date('YmdHis') . ".zip";
            $zipfilename = "$this->_export_tmpdir/$zipfilename";

            $zip = new Vtiger_Zip($zipfilename);

            // Add manifest file
            $zip->addFile($this->__getManifestFilePath(), "manifest.xml");

            // Copy module directory
            $zip->copyDirectoryFromDisk("layouts/$layoutName");

            $zip->save();

            if($todir) {
                    copy($zipfilename, $todir);
            }
            
            if($directDownload) {
                    $zip->forceDownload($zipfilename);
                    unlink($zipfilename);
            }
            $this->__cleanupExport();
    }

    /**
     * Export Layout Handler
     * @access private
     */
    function export_Layout($layoutName) {
            global $adb;

            $sqlresult = $adb->pquery("SELECT * FROM vtiger_layout WHERE name = ?", array($layoutName));
            $layoutresultrow = $adb->fetch_array($sqlresult);

            $layoutname  = decode_html($layoutresultrow['name']);
            $layoutlabel = decode_html($layoutresultrow['label']);

            $this->openNode('module');
            $this->outputNode(date('Y-m-d H:i:s'),'exporttime');
            $this->outputNode($layoutname, 'name');
            $this->outputNode($layoutlabel, 'label');

            $this->outputNode('layout', 'type');

            // Export dependency information
            $this->export_Dependencies();

            $this->closeNode('module');
    }

    /**
     * Export vtiger dependencies
     * @access private
     */
    function export_Dependencies($moduleInstance = false) {
            global $vtiger_current_version, $adb;

            $vtigerMinVersion = $vtiger_current_version;
            $vtigerMaxVersion = false;

            $this->openNode('dependencies');
            $this->outputNode($vtigerMinVersion, 'vtiger_version');
            if($vtigerMaxVersion !== false)	$this->outputNode($vtigerMaxVersion, 'vtiger_max_version');
            $this->closeNode('dependencies');
    }


    /**
     * Initialize Layout Schema
     * @access private
     */
    static function __initSchema() {
            $hastable = Vtiger_Utils::CheckTable(self::TABLENAME);
            if(!$hastable) {
                    Vtiger_Utils::CreateTable(
                            self::TABLENAME,
                            '(id INT NOT NULL PRIMARY KEY,
                            name VARCHAR(50), label VARCHAR(30), lastupdated DATETIME, isdefault INT(1), active INT(1))',
                            true
                    );
                   
            }
    }
    
    /**
     * Register layout pack information.
     */
    static function register($name, $label='', $isdefault=false, $isactive=true, $overrideCore=false) {
            self::__initSchema();

            $prefix = trim($prefix);
            // We will not allow registering core layouts unless forced
            if(strtolower($name) == 'vlayout' && $overrideCore == false) return;

            $useisdefault = ($isdefault)? 1 : 0;
            $useisactive  = ($isactive)?  1 : 0;

            global $adb;
            $checkres = $adb->pquery('SELECT * FROM '.self::TABLENAME.' WHERE name=?', Array($name));
            $datetime = date('Y-m-d H:i:s');
            if($adb->num_rows($checkres)) {
                    $id = $adb->query_result($checkres, 0, 'id');
                    $adb->pquery('UPDATE '.self::TABLENAME.' set label=?, name=?, lastupdated=?, isdefault=?, active=? WHERE id=?',
                            Array($label, $name, $datetime, $useisdefault, $useisactive, $id));
            } else {
                    $uniqueid = self::__getUniqueId();
                    $adb->pquery('INSERT INTO '.self::TABLENAME.' (id,name,label,lastupdated,isdefault,active) VALUES(?,?,?,?,?,?)',
                            Array($uniqueid, $name, $label, $datetime, $useisdefault, $useisactive));
            }
            self::log("Registering Layout $name ... DONE");		
    }
    
    
        static function deregister($name) {
	     if(strtolower($name) == 'vlayout') return;

		 self::__initSchema();

		global $adb;
	    $adb->pquery('DELETE FROM '.self::TABLENAME.' WHERE name=?', Array($name));
		self::log("Deregistering Layout $name ... DONE");
	}

}
