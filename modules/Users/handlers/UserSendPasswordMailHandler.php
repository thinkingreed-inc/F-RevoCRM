<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once 'vtlib/Vtiger/Mailer.php';
require_once 'include/utils/utils.php';
require_once 'include/events/VTEventHandler.inc';
require_once 'modules/Vtiger/helpers/ShortURL.php';

class UserSendPasswordMailHandler extends VTEventHandler {

    public function handleEvent($eventName, $entityData) {
        if ($eventName !== 'vtiger.entity.aftersave.final') {
            return;
        }
        if (!$entityData->isNew()) {
        return;
        }   
        $userId = $entityData->getId();
        self::sendPasswordMail($userId);
    }

    // パスワード設定メールを送信する
    public static function sendPasswordMail($userId) {
        global $current_user;

        $userRecord = Users_Record_Model::getInstanceById($userId,'Users');
        if(!$userRecord){
            return false;
        }
        $userName = $userRecord->get('user_name');
        $email    = $userRecord->get('email1');
        if(empty($email)){
            return false;
        }
        $lifetimeSeconds = 3 * 24 * 60 * 60;
        $timeNow = time();
        $options = array(
            'handler_path' => 'modules/Users/handlers/ForgotPassword.php',
            'handler_class' => 'Users_ForgotPassword_Handler',
            'handler_function' => 'changePassword',
            'onetime' => 1,
            'handler_data' => array(
                'username' => $userName,
                'email' => $email,
                'time' => $timeNow,
                'hash' => md5($userName . $timeNow),
                'lifetime' => $lifetimeSeconds
            )
        );
        $expireDateTime = date("Y-m-d H:i:s", $timeNow + $lifetimeSeconds);
        $tz = $current_user->time_zone ?: date_default_timezone_get();
        $expireAtWithTz = $expireDateTime . ' (' . $tz . ')';
        $trackURL = Vtiger_ShortURL_Helper::generateURL($options);
        $content = vtranslate('LBL_Account_Created', 'Vtiger', $trackURL, $expireAtWithTz);

        $subject = vtranslate('F-RevoCRM: Account Created', 'Vtiger');

        $mail = new Vtiger_Mailer();
        $mail->IsHTML();
        $mail->Body = $content;
        $mail->Subject = $subject;
        $mail->AddAddress($email);
        try {
            $status = $mail->Send(true);
        } catch (Exception $e) {
            error_log('UserSendPasswordMailHandler Send exception: '.$e->getMessage());
            return false;
        }
        if ($status === 1 || $status === true) {
            return true;
        }
        return false;
    }
}
