<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************/

if (defined('VTIGER_UPGRADE')) {
	global $current_user, $adb;
	$db = PearDatabase::getInstance();

	//課税計算の手数料額をnullから0円に変更
	$db->query("UPDATE vtiger_inventorycharges SET value = 0 WHERE value IS NULL;");

	//カレンダーのエクスポートでヘッダーの順番を変更(tabid = 9 && block = 19のsequenceを整頓)
	$query = "SELECT columnname,fieldid,sequence FROM vtiger_field WHERE tabid = 9 AND block = (SELECT block FROM vtiger_field WHERE tabid = 9 AND columnname = 'time_start') ORDER BY sequence;";
	$result = $db->query($query);
	$result_num = $db->num_rows($result);
	for ($j = 0; $j < $result_num; $j++) {
		$columnname = $db->query_result($result, $j,"columnname");
		$fieldid = intval($db->query_result($result, $j,"fieldid"));
		$sequence = intval($db->query_result($result, $j,"sequence"));
		$fieldData[$columnname] = array(
			"fieldid" => $fieldid,
			"sequence" => $sequence,
		);
	}
	if($fieldData["time_start"]["sequence"] == $fieldData["time_end"]["sequence"]){
		$i = 0;
		foreach($fieldData as $key => $value){
			$updateQuery = "UPDATE vtiger_field SET sequence = ".($i+1)."  WHERE fieldid = ".$value['fieldid']." AND tabid = 9;";
			$db->query($updateQuery);
			$i++;
		}
	}
}
