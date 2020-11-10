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

global $adb;

$sql = "UPDATE vtiger_field SET sequence = ? WHERE fieldname = ? AND tabid = (SELECT t.tabid FROM vtiger_tab t WHERE t.name = ?) ";
$adb->pquery($sql, array('1','bill_country','Accounts'));
$adb->pquery($sql, array('2','ship_country','Accounts'));
$adb->pquery($sql, array('3','bill_code','Accounts'));
$adb->pquery($sql, array('4','ship_code','Accounts'));
$adb->pquery($sql, array('5','bill_state','Accounts'));
$adb->pquery($sql, array('6','ship_state','Accounts'));
$adb->pquery($sql, array('7','bill_city','Accounts'));
$adb->pquery($sql, array('8','ship_city','Accounts'));
$adb->pquery($sql, array('9','bill_street','Accounts'));
$adb->pquery($sql, array('10','ship_street','Accounts'));
$adb->pquery($sql, array('11','bill_pobox','Accounts'));
$adb->pquery($sql, array('12','ship_pobox','Accounts'));

$adb->pquery($sql, array('1','mailingcountry','Contacts'));
$adb->pquery($sql, array('2','othercountry','Contacts'));
$adb->pquery($sql, array('3','mailingzip','Contacts'));
$adb->pquery($sql, array('4','otherzip','Contacts'));
$adb->pquery($sql, array('5','mailingstate','Contacts'));
$adb->pquery($sql, array('6','otherstate','Contacts'));
$adb->pquery($sql, array('7','mailingcity','Contacts'));
$adb->pquery($sql, array('8','othercity','Contacts'));
$adb->pquery($sql, array('9','mailingstreet','Contacts'));
$adb->pquery($sql, array('10','otherstreet','Contacts'));
$adb->pquery($sql, array('11','mailingpobox','Contacts'));
$adb->pquery($sql, array('12','otherpobox','Contacts'));

$adb->pquery($sql, array('1','country','Leads'));
$adb->pquery($sql, array('2','code','Leads'));
$adb->pquery($sql, array('3','state','Leads'));
$adb->pquery($sql, array('4','city','Leads'));
$adb->pquery($sql, array('5','lane','Leads'));
$adb->pquery($sql, array('6','pobox','Leads'));

$adb->pquery($sql, array('1','bill_country','Invoice'));
$adb->pquery($sql, array('2','ship_country','Invoice'));
$adb->pquery($sql, array('3','bill_code','Invoice'));
$adb->pquery($sql, array('4','ship_code','Invoice'));
$adb->pquery($sql, array('5','bill_state','Invoice'));
$adb->pquery($sql, array('6','ship_state','Invoice'));
$adb->pquery($sql, array('7','bill_city','Invoice'));
$adb->pquery($sql, array('8','ship_city','Invoice'));
$adb->pquery($sql, array('9','bill_street','Invoice'));
$adb->pquery($sql, array('10','ship_street','Invoice'));
$adb->pquery($sql, array('11','bill_pobox','Invoice'));
$adb->pquery($sql, array('12','ship_pobox','Invoice'));

$adb->pquery($sql, array('1','bill_country','SalesOrder'));
$adb->pquery($sql, array('2','ship_country','SalesOrder'));
$adb->pquery($sql, array('3','bill_code','SalesOrder'));
$adb->pquery($sql, array('4','ship_code','SalesOrder'));
$adb->pquery($sql, array('5','bill_state','SalesOrder'));
$adb->pquery($sql, array('6','ship_state','SalesOrder'));
$adb->pquery($sql, array('7','bill_city','SalesOrder'));
$adb->pquery($sql, array('8','ship_city','SalesOrder'));
$adb->pquery($sql, array('9','bill_street','SalesOrder'));
$adb->pquery($sql, array('10','ship_street','SalesOrder'));
$adb->pquery($sql, array('11','bill_pobox','SalesOrder'));
$adb->pquery($sql, array('12','ship_pobox','SalesOrder'));

$adb->pquery($sql, array('1','bill_country','PurchaseOrder'));
$adb->pquery($sql, array('2','ship_country','PurchaseOrder'));
$adb->pquery($sql, array('3','bill_code','PurchaseOrder'));
$adb->pquery($sql, array('4','ship_code','PurchaseOrder'));
$adb->pquery($sql, array('5','bill_state','PurchaseOrder'));
$adb->pquery($sql, array('6','ship_state','PurchaseOrder'));
$adb->pquery($sql, array('7','bill_city','PurchaseOrder'));
$adb->pquery($sql, array('8','ship_city','PurchaseOrder'));
$adb->pquery($sql, array('9','bill_street','PurchaseOrder'));
$adb->pquery($sql, array('10','ship_street','PurchaseOrder'));
$adb->pquery($sql, array('11','bill_pobox','PurchaseOrder'));
$adb->pquery($sql, array('12','ship_pobox','PurchaseOrder'));

$adb->pquery($sql, array('1','bill_country','Quotes'));
$adb->pquery($sql, array('2','ship_country','Quotes'));
$adb->pquery($sql, array('3','bill_code','Quotes'));
$adb->pquery($sql, array('4','ship_code','Quotes'));
$adb->pquery($sql, array('5','bill_state','Quotes'));
$adb->pquery($sql, array('6','ship_state','Quotes'));
$adb->pquery($sql, array('7','bill_city','Quotes'));
$adb->pquery($sql, array('8','ship_city','Quotes'));
$adb->pquery($sql, array('9','bill_street','Quotes'));
$adb->pquery($sql, array('10','ship_street','Quotes'));
$adb->pquery($sql, array('11','bill_pobox','Quotes'));
$adb->pquery($sql, array('12','ship_pobox','Quotes'));

$adb->pquery($sql, array('1','country','Vendors'));
$adb->pquery($sql, array('2','postalcode','Vendors'));
$adb->pquery($sql, array('3','state','Vendors'));
$adb->pquery($sql, array('4','city','Vendors'));
$adb->pquery($sql, array('5','street','Vendors'));
$adb->pquery($sql, array('6','pobox','Vendors'));

$adb->pquery($sql, array('1','address_country','Users'));
$adb->pquery($sql, array('2','address_postalcode','Users'));
$adb->pquery($sql, array('3','address_state','Users'));
$adb->pquery($sql, array('4','address_city','Users'));
$adb->pquery($sql, array('5','address_street','Users'));

// 国と私書箱の入力を非表示にする
$sql = "UPDATE vtiger_field SET presence = 1 WHERE fieldname = ? AND tabid = (SELECT t.tabid FROM vtiger_tab t WHERE t.name = ?) ";
$adb->pquery($sql, array('bill_country','Accounts'));
$adb->pquery($sql, array('ship_country','Accounts'));
$adb->pquery($sql, array('bill_pobox','Accounts'));
$adb->pquery($sql, array('ship_pobox','Accounts'));

$adb->pquery($sql, array('mailingcountry','Contacts'));
$adb->pquery($sql, array('othercountry','Contacts'));
$adb->pquery($sql, array('mailingpobox','Contacts'));
$adb->pquery($sql, array('otherpobox','Contacts'));

$adb->pquery($sql, array('country','Leads'));
$adb->pquery($sql, array('pobox','Leads'));

$adb->pquery($sql, array('bill_country','Invoice'));
$adb->pquery($sql, array('ship_country','Invoice'));
$adb->pquery($sql, array('bill_pobox','Invoice'));
$adb->pquery($sql, array('ship_pobox','Invoice'));

$adb->pquery($sql, array('bill_country','SalesOrder'));
$adb->pquery($sql, array('ship_country','SalesOrder'));
$adb->pquery($sql, array('bill_pobox','SalesOrder'));
$adb->pquery($sql, array('ship_pobox','SalesOrder'));

$adb->pquery($sql, array('bill_country','PurchaseOrder'));
$adb->pquery($sql, array('ship_country','PurchaseOrder'));
$adb->pquery($sql, array('bill_pobox','PurchaseOrder'));
$adb->pquery($sql, array('ship_pobox','PurchaseOrder'));

$adb->pquery($sql, array('bill_country','Quotes'));
$adb->pquery($sql, array('ship_country','Quotes'));
$adb->pquery($sql, array('bill_pobox','Quotes'));
$adb->pquery($sql, array('ship_pobox','Quotes'));

$adb->pquery($sql, array('country','Vendors'));
$adb->pquery($sql, array('pobox','Vendors'));



