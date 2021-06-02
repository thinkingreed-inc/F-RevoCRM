<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/Vtiger/models/Field.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');

global $adb;

// vtiger_inviteesを見てactivityidとinviteeidを取得
$result = $adb->query("select * from vtiger_invitees");
// 取得した値を元に以下の関数を実行。
$rows = $adb->num_rows($result);
for ($i=0; $i < $rows; $i++) { 
	$activityid = $adb->query_result($result,$i,'activityid');
	$inviteeid = $adb->query_result($result,$i,'inviteeid');
	$id = getEventIdFromInvitee($activityid, $inviteeid);
	if(!$id) continue;

	$adb->query("update vtiger_activity set invitee_parentid=$activityid where (invitee_parentid is null or invitee_parentid = 0) and activityid=$id");
}

$adb->query("update vtiger_activity set invitee_parentid=activityid where (invitee_parentid is null or invitee_parentid = 0)");

function getEventIdFromInvitee($activityid, $userid) {
	global $adb;

	$result = $adb->pquery(
		"SELECT
			a1.activityid
		FROM
			vtiger_activity a1
			INNER JOIN vtiger_crmentity c1 ON c1.crmid = a1.activityid
		WHERE
			c1.deleted = 0
			AND c1.smownerid = ?
			AND exists(
				SELECT
					1
				FROM
					vtiger_activity a2
					INNER JOIN vtiger_crmentity c2 ON c2.crmid = a2.activityid
				WHERE
					a2.activityid = ?
					AND a1.subject = a2.subject
					AND a1.activitytype = a2.activitytype
					AND a1.date_start = a2.date_start
					AND a1.due_date = a2.due_date
					AND a1.time_start = a2.time_start
					AND a1.time_end = a2.time_end
					AND a1.eventstatus = a2.eventstatus
					AND a1.location = a2.location
					AND a1.recurringtype = a2.recurringtype
			)
	",array($userid, $activityid));

	if($adb->num_rows($result) > 0) {
		$id = $adb->query_result($result, 0, "activityid");
	}

	return $id;
}
