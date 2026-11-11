<?php

/**
 * 詳細画面で通知先を直接編集して保存する（JSON。保存しても通知は送らない）
 *
 * POST record=..&targets=[{"type": "role", "id": "H2"}, ...]
 *   成功：{"targets": [{"type", "id", "label", "disabled", "text"}], "message": 画面に出すメッセージ}
 */
class NotificationBlock_SaveTargets_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * 通知先を書き換えるので、POST と CSRF トークンも確かめる
     */
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    /**
     * 権限（レコードの編集権限）は processApi() で確かめる（URL の module は信じない）
     *
     * @return array<int, array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $module = NotificationBlock_Config_Model::MODULE_NAME;
        $recordId = NotificationBlock_Util_Helper::toInt($request->get('record'));
        $db = PearDatabase::getInstance();
        $rows = NotificationBlock_Util_Helper::fetchRawRows($db->pquery(
            'SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
            [$recordId]
        ));
        $moduleName = empty($rows) ? '' : NotificationBlock_Util_Helper::toStr($rows[0]['setype'] ?? '');
        if ($moduleName === '') {
            $this->sendError(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_DELETED', $module)), 400);
        }
        if (!NotificationBlock_Config_Model::hasBlock($moduleName)) {
            $this->sendError(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_BLOCK', $module)), 400);
        }
        if (!Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId)) {
            $this->sendError(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_PERMISSION', $module)), 403);
        }
        $saved = NotificationBlock_Target_Model::saveTargets(
            $recordId,
            NotificationBlock_Target_Model::parseJson(NotificationBlock_Util_Helper::toStr($request->getRaw('targets')))
        );
        return $this->sendSuccess([
            'targets' => json_decode(NotificationBlock_Display_Helper::targetsJson($saved), true),
            'message' => NotificationBlock_Util_Helper::toStr(vtranslate('LBL_TARGETS_SAVED', $module)),
        ]);
    }
}
