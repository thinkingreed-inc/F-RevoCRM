<?php
/**
 * Copyright (c) 2004-2014 All Right Reserved, www.vtiger.com
 * Vtiger Proprietary License
 * The contents of this file cannot be modified or redistributed 
 * without explicit permission from Vtiger (www.vtiger.com).
 */

Class Settings_ModuleManager_LoaderSuggest {

	function vtiger_extensionloader_suggest() {
		$PHPVER = sprintf("%s.%s", PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
		$OSHWINFO = str_replace('Darwin', 'Mac', PHP_OS).'_'.php_uname('m');

		$WIN = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;

		$EXTFNAME = 'vtigerextn_loader';
		$EXTNFILE = $EXTFNAME.($WIN ? '.dll' : '.so');

		$DISTFILE = sprintf("%s_%s_%s.so", $EXTFNAME, $PHPVER, $OSHWINFO);
		$DISTFILEZIP = sprintf("%s_%s_%s-yyyymmdd.zip", $EXTFNAME, $PHPVER, $OSHWINFO);

		return array(
			'loader_zip' => $DISTFILEZIP,
			'loader_file' => $DISTFILE,
			'php_ini' => php_ini_loaded_file(),
			'extensions_dir' => ini_get('extension_dir')
		);
	}
}
