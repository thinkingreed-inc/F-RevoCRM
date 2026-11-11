<?php

/**
 * 「版に含める関連モジュール」の編集（モーダル）
 *
 * 項目設定「関連」タブの「1対多と多対多の関連」のうち、レコードの一覧を出す関連を
 * チェックボックスの一覧で出す（同じモジュールへの関連が複数あれば、関連タブの名前で関連ごとに出す）。
 */
class Settings_RecordVersion_EditRelations_View extends Settings_Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request): void
    {
        $qualifiedModuleName = $request->getModule(false);
        $tabId = RecordVersion_Util_Helper::toInt($request->get('tabid'));
        if (!RecordVersion_Setup_Model::isSupportedTab($tabId)) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        $moduleName = RecordVersion_Util_Helper::toStr(getTabModuleName($tabId));
        $selected = RecordVersion_Config_Model::getIncludedRelationIds($tabId);
        $candidates = [];
        foreach (RecordVersion_Setup_Model::getRelationCandidates($moduleName) as $relationId => $candidate) {
            $candidates[] = [
                'relation_id' => $relationId,
                'label' => RecordVersion_Util_Helper::toStr(vtranslate($candidate['label'], $candidate['related_module'])),
                'related_module_label' => RecordVersion_Util_Helper::toStr(vtranslate($candidate['related_module'], $candidate['related_module'])),
                'checked' => in_array($relationId, $selected, true),
            ];
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('TABID', $tabId);
        $moduleLabel = RecordVersion_Util_Helper::toStr(vtranslate($moduleName, $moduleName));
        $titleFormat = RecordVersion_Util_Helper::toStr(vtranslate('LBL_EDIT_RELATIONS_TITLE', $qualifiedModuleName));
        $viewer->assign('TITLE', sprintf($titleFormat, $moduleLabel));
        $viewer->assign('CANDIDATES', $candidates);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('EditRelations.tpl', $qualifiedModuleName);
    }
}
