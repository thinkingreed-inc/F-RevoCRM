<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_ForgotPassword extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		global $adb, $PORTAL_URL, $current_user;
		$userId = $this->getCurrentPortalUser();
		$user = new Users();
		$current_user = $user->retrieveCurrentUserInfoFromFile($userId);

		$response = new CustomerPortal_API_Response();
		$mailid = $request->get('email');
		$current_date = date("Y-m-d");
		$sql = 'SELECT * FROM vtiger_portalinfo
					INNER JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid=vtiger_portalinfo.id
					INNER JOIN vtiger_customerdetails ON vtiger_customerdetails.customerid=vtiger_portalinfo.id
					INNER JOIN vtiger_crmentity ON vtiger_portalinfo.id=vtiger_crmentity.crmid 
						WHERE vtiger_portalinfo.user_name = ? AND vtiger_crmentity.deleted= ?
						AND vtiger_customerdetails.support_start_date <= ?';

		$res = $adb->pquery($sql, array($mailid, '0', $current_date));
		$num_rows = $adb->num_rows($res);

		if ($num_rows > 0) {
			$isActive = $adb->query_result($res, 0, 'isactive');
			$support_end_date = $adb->query_result($res, 0, 'support_end_date');

			if ($isActive && ($support_end_date >= $current_date || $support_end_date == null )) {
				$moduleName = 'Contacts';
				global $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;
				$user_name = $adb->query_result($res, 0, 'user_name');
				$contactId = $adb->query_result($res, 0, 'id');

				if (!empty($adb->query_result($res, 0, 'cryptmode'))) {
					$password = makeRandomPassword();
					$enc_password = Vtiger_Functions::generateEncryptedPassword($password);

					$sql = 'UPDATE vtiger_portalinfo SET user_password=?, cryptmode=? WHERE id=?';
					$params = array($enc_password, 'CRYPT', $contactId);
					$adb->pquery($sql, $params);
				}

				$portalURL = vtranslate('Please ', $moduleName).'<a href="'.$PORTAL_URL.'" style="font-family:Arial, Helvetica, sans-serif;font-size:13px;">'.vtranslate('click here', $moduleName).'</a>';
				$contents = '<table><tr><td>
								<strong>Dear '.$adb->query_result($res, 0, 'firstname')." ".$adb->query_result($res, 0, 'lastname').'</strong><br></td></tr><tr>
								<td>'.vtranslate('Here is your self service portal login details:', $moduleName).'</td></tr><tr><td align="center"><br><table style="border:2px solid rgb(180,180,179);background-color:rgb(226,226,225);" cellspacing="0" cellpadding="10" border="0" width="75%"><tr>
								<td><br>'.vtranslate('User ID').' : <font color="#990000"><strong><a target="_blank">'.$user_name.'</a></strong></font></td></tr><tr>
								<td>'.vtranslate('Password').' : <font color="#990000"><strong>'.$password.'</strong></font></td></tr><tr>
								<td align="center"><strong>'.$portalURL.'</strong></td>
								</tr></table><br></td></tr><tr><td><strong>NOTE: </strong>'.vtranslate('We suggest you to change your password after logging in first time').'.<br>
							</td></tr></table>';

				$subject = 'Customer Portal Login Details';
				$subject = decode_html(getMergedDescription($subject, $contactId, $moduleName));

				$mailStatus = send_mail($moduleName, $user_name, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $contents, '', '', '', '', '', true);
				$ret_msg = vtranslate('LBL_MAIL_COULDNOT_SENT', 'HelpDesk');
				if ($mailStatus) {
					$ret_msg = vtranslate('LBL_MAIL_SENT', 'HelpDesk');
				}
				$response->setResult($ret_msg);
			} else if ($isActive && $support_end_date <= $current_date) {
				throw new Exception('Access to the portal was disabled on '.$support_end_date, 1413);
			} else if ($isActive == 0) {
				throw new Exception('Portal access has not been enabled for this account.', 1414);
			}
		} else {
			$response->setError('1412', 'Invalid email');
		}
		return $response;
	}

	function authenticatePortalUser($username, $password) {
		// always return true
		return true;
	}

	public function getCurrentPortalUser() {
		$db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT prefvalue FROM vtiger_customerportal_prefs WHERE prefkey = 'userid' AND tabid = 0", array());
		if ($db->num_rows($result)) {
			return $db->query_result($result, 0, 'prefvalue');
		}
		return false;
	}

}
