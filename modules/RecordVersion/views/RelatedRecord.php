<?php

/**
 * 過去の版に含めた関連レコードの、当時の全項目（小窓。HTML の断片を返す）
 *
 * 項目は過去の版の表示と同じ決まり（表C）で絞る。行を出してよいかは表D で判定する。
 */
class RecordVersion_RelatedRecord_View extends RecordVersion_Base_View
{
    public function preProcess(Vtiger_Request $request, mixed $display = true): void
    {
    }

    public function postProcess(Vtiger_Request $request): void
    {
    }

    public function process(Vtiger_Request $request): void
    {
        $module = RecordVersion_Config_Model::MODULE_NAME;
        $version = RecordVersion_Version_Model::getByNumber($this->recordId, RecordVersion_Util_Helper::toInt($request->get('version')));
        if ($version === null) {
            throw new AppException(vtranslate('LBL_VERSION_NOT_FOUND', $module));
        }
        $snapshot = $version->getSnapshot();
        $relations = RecordVersion_Util_Helper::toList($snapshot['relations'] ?? []);
        $relation = RecordVersion_Util_Helper::toArray($relations[RecordVersion_Util_Helper::toInt($request->get('relation'))] ?? []);
        $relatedModule = RecordVersion_Util_Helper::toStr($relation['related_module'] ?? '');
        $relatedId = RecordVersion_Util_Helper::toInt($request->get('related'));

        $item = null;
        foreach (RecordVersion_Util_Helper::toList($relation['records'] ?? []) as $candidate) {
            $candidate = RecordVersion_Util_Helper::toArray($candidate);
            if (RecordVersion_Util_Helper::toInt($candidate['id'] ?? 0) === $relatedId) {
                $item = $candidate;
                break;
            }
        }
        $display = new RecordVersion_Display_Model(RecordVersion_Display_Model::getCurrencySymbol([]));
        $relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModule);
        $moduleVisible = $relatedModuleModel && $relatedModuleModel->isActive()
            ? (bool) Users_Privileges_Model::isPermitted($relatedModule, 'DetailView')
            : $display->isAdmin();
        if ($item === null || !$moduleVisible || !$display->canShowRelatedRecord($relatedModule, $relatedId)) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        $fieldBlocks = $display->buildFieldBlocks($relatedModule, RecordVersion_Util_Helper::toArray($item['fields'] ?? []));
        $viewer = $this->getViewer($request);
        $viewer->assign('RV_MODULE', $module);
        $viewer->assign('TITLE', RecordVersion_Util_Helper::toStr($item['label'] ?? '') . ' - ' . vtranslate('LBL_RELATED_RECORD_AT_THAT_TIME', $module));
        $viewer->assign('FIELD_BLOCKS', $fieldBlocks['blocks']);
        $viewer->assign('DELETED_FIELDS', $fieldBlocks['deleted']);
        $viewer->view('RelatedRecord.tpl', $module);
    }
}
