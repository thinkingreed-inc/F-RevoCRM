<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');

Vtiger_ShowFile_Helper::handle(vtlib_purify($_REQUEST['fid']), vtlib_purify($_REQUEST['key']));