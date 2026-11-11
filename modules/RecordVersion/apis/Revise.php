<?php

/**
 * 改版する（JSON）
 *
 * POST record=..&reason=..&expected_version=..
 *   成功：{"version_no": 新しい版番号, "edit_url": 編集画面の URL}
 *   失敗：{"success": false, "error": {"message": 画面に出すメッセージ}}
 */
class RecordVersion_Revise_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * レコードを書き換えるので、POST と CSRF トークンも確かめる
     */
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    /**
     * 権限（編集権限・版管理の対象）は RecordVersion_Revision_Model::revise() で確かめる
     *
     * @return array<int, array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $recordId = RecordVersion_Util_Helper::toInt($request->get('record'));
        $expected = RecordVersion_Util_Helper::toInt($request->get('expected_version'));
        $result = RecordVersion_Revision_Model::revise(
            $recordId,
            RecordVersion_Util_Helper::toStr($request->getRaw('reason')),
            $expected > 0 ? $expected : null
        );
        if (!$result['success']) {
            $this->sendError($result['message'], 400);
        }
        $moduleName = RecordVersion_Util_Helper::toStr(getSalesEntityType($recordId));
        return $this->sendSuccess([
            'version_no' => $result['version_no'],
            'edit_url' => 'index.php?module=' . rawurlencode($moduleName) . '&view=Edit&record=' . $recordId,
        ]);
    }
}
