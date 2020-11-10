<?php
/*
$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
*/

//ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_DEPRECATED);
//ini_set('display_errors', 1 );

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');

require_once('includes/runtime/BaseModel.php');
require_once('modules/Settings/Vtiger/models/Record.php');
require_once('modules/Settings/Roles/models/Record.php');
require_once('modules/Settings/Profiles/models/Record.php');

global $adb;

$roles = Settings_Roles_Record_Model::getAll();
$transRole = null;

foreach($roles as $r) {
    if($r->getId() == 'H1') {//Organaization ここは入ってこないかも。
        $r->set('rolename', '組織');
        $r->set('mode', 'edit');
        $r->save();
        continue;
    }
    else if($r->getId() == 'H2') {//CEO
        $r->set('rolename', '管理者');
        $r->set('parentrole', 'H1::H2');
        $r->set('depth', '1');
        $r->set('mode', 'edit');
        $r->save();
        $transRole = $r;
        continue;
    }
    else if($r->getId() == 'H3') {//Vice President
        $r->set('rolename', 'マネージャー');
        // $r->set('parentrole', 'H1::H3');
        // $r->set('depth', '1');
        $r->set('mode', 'edit');
        $r->save();
        $transRole = $r;
        continue;
    }
    else if($r->getId() == 'H4') {//Sales Manager
        $r->set('rolename', '一般');
        // $r->set('parentrole', 'H1::H4');
        // $r->set('depth', '1');
        $r->set('mode', 'edit');
        $r->save();
        $transRole = $r;
        continue;
    }
    else if($r->getId() == 'H5') {//Sales Person
        $r->set('rolename', 'パート・アルバイト');
        // $r->set('parentrole', 'H1::H5');
        // $r->set('depth', '1');
        $r->set('mode', 'edit');
        $r->save();
        $transRole = $r;
        continue;
    }
    else {
        $r->delete($transRole);
        continue;
    }
}

// $role = new Settings_Roles_Record_Model();
// $role->set('rolename', '法務部');
// $role->set('parentrole', 'H1::H6');
// $role->set('depth', '1');
// $role->set('allowassignedrecordsto', '1');
// $role->save();
