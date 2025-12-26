<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 *************************************************************************************/

require_once 'modules/Users/handlers/UserSendPasswordMailHandler.php';
class Users_ResendPasswordMail_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        if (!Users_Record_Model::getCurrentUserModel()->isAdminUser()) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
        return true;
    }
    public function process(Vtiger_Request $request) {
        $userId = $request->get('userid');
        $result = UserSendPasswordMailHandler::sendPasswordMail($userId);
        $response = new Vtiger_Response();
        if ($result === true) {
            $message = vtranslate('LBL_PASSWORD_SETUP_MAIL_SENT', 'Users');
            $response->setResult([
                'success' => true,
                'message' => $message
            ]);
        } else {
            $message = vtranslate('LBL_PASSWORD_SETUP_MAIL_FAILED', 'Users');
            $response->setResult([
                'success' => false,
                'message' => $message
            ]);
        }
        $response->emit();
    }
}
