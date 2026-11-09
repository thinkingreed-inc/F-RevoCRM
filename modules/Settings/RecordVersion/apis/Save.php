<?php

/**
 * 版管理の設定を保存する（JSON）
 *
 * POST mode=module&tabid=..&enabled=1|0        … 版管理「はい／いいえ」
 * POST mode=relations&tabid=..&relation_ids[]=.. … 版に含める関連（次の改版から反映）
 */
class Settings_RecordVersion_Save_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * 設定を書き換えるので、POST と CSRF トークンも確かめる
     */
    public function validateRequest(Vtiger_Request $request): bool
    {
        return $request->validateWriteAccess();
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser || !$currentUser->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $tabId = RecordVersion_Util_Helper::toInt($request->get('tabid'));
        if (!RecordVersion_Setup_Model::isSupportedTab($tabId)) {
            $this->sendError('Unsupported module', 400);
        }
        $mode = RecordVersion_Util_Helper::toStr($request->get('mode'));
        $db = PearDatabase::getInstance();
        $db->startTransaction();
        try {
            if ($mode === 'module') {
                if (RecordVersion_Util_Helper::toStr($request->get('enabled')) === '1') {
                    RecordVersion_Setup_Model::enable($tabId);
                } else {
                    RecordVersion_Setup_Model::disable($tabId);
                }
            } elseif ($mode === 'relations') {
                $this->saveRelations($tabId, $request);
            } else {
                $this->sendError('Invalid mode', 400);
            }
        } catch (Exception $e) {
            $db->database->FailTrans();
            $db->completeTransaction();
            throw $e;
        }
        $db->completeTransaction();

        $moduleName = RecordVersion_Util_Helper::toStr(getTabModuleName($tabId));
        return $this->sendSuccess(Settings_RecordVersion_Index_View::buildRow($tabId, $moduleName));
    }

    private function saveRelations(int $tabId, Vtiger_Request $request): void
    {
        $moduleName = RecordVersion_Util_Helper::toStr(getTabModuleName($tabId));
        $candidates = RecordVersion_Setup_Model::getRelationCandidates($moduleName);
        $relationIds = [];
        foreach (RecordVersion_Util_Helper::toList($request->get('relation_ids')) as $relationId) {
            $id = RecordVersion_Util_Helper::toInt($relationId);
            // 候補にない関連（履歴・活動など）は保存しない
            if (isset($candidates[$id])) {
                $relationIds[] = $id;
            }
        }
        RecordVersion_Config_Model::saveIncludedRelationIds($tabId, $relationIds);
    }
}
