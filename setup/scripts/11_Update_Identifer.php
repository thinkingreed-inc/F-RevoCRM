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

// vtiger_entityname
$adb->query("UPDATE vtiger_entityname SET fieldname = 'lastname,firstname' WHERE modulename in ('Contacts','Leads')");
$adb->query("UPDATE vtiger_entityname SET fieldname = 'last_name,first_name' WHERE modulename in ('Users')");

// filter
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = -1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'All')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 2 WHERE columnname like '%firstname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'All')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'All')");

$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = -1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'Hot Leads')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 1 WHERE columnname like '%firstname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'Hot Leads')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 0 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'Hot Leads')");

$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = -1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'This Month Leads')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 1 WHERE columnname like '%firstname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'This Month Leads')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 0 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Leads' and viewname = 'This Month Leads')");

$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = -1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'All')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 2 WHERE columnname like '%firstname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'All')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'All')");

$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = -1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'Contacts Address')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 1 WHERE columnname like '%firstname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'Contacts Address')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 0 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'Contacts Address')");

$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = -1 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'Todays Birthday')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 1 WHERE columnname like '%firstname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'Todays Birthday')");
$adb->query("UPDATE vtiger_cvcolumnlist SET columnindex = 0 WHERE columnname like '%lastname%' AND cvid = (select cv.cvid from vtiger_customview cv where vtiger_cvcolumnlist.cvid = cv.cvid and entitytype='Contacts' and viewname = 'Todays Birthday')");

// field
$adb->query("UPDATE vtiger_field SET presence = 1 WHERE fieldname = 'salutationtype' and tabid in (select tabid from vtiger_tab where name in ('Contacts', 'Leads'))");
$adb->query("UPDATE vtiger_field SET sequence = 4, quickcreatesequence = 2 WHERE fieldname = 'firstname' and tabid = (select tabid from vtiger_tab where name = 'Contacts')");
$adb->query("UPDATE vtiger_field SET sequence = 2, quickcreatesequence = 1 WHERE fieldname = 'lastname' and tabid = (select tabid from vtiger_tab where name = 'Contacts')");

$adb->query("UPDATE vtiger_field SET sequence = 4, quickcreatesequence = 2 WHERE fieldname = 'firstname' and tabid = (select tabid from vtiger_tab where name = 'Leads')");
$adb->query("UPDATE vtiger_field SET sequence = 2, quickcreatesequence = 1 WHERE fieldname = 'lastname' and tabid = (select tabid from vtiger_tab where name = 'Leads')");

$adb->query("UPDATE vtiger_field SET sequence = 4, quickcreatesequence = null WHERE fieldname = 'first_name' and tabid = (select tabid from vtiger_tab where name = 'Users')");
$adb->query("UPDATE vtiger_field SET sequence = 3, quickcreatesequence = null WHERE fieldname = 'last_name' and tabid = (select tabid from vtiger_tab where name = 'Users')");
