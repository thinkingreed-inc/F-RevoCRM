<?php

/**
 * 過去の版の表示（版履歴の「版」から開く。表示のみ）
 *
 * 画面の上部に「版 N を表示しています（日時 に 改版者 が改版）。過去の版は変更できません。」と改版理由を出し、
 * 今の項目設定のブロックと並び順で当時の値を出す。品目明細・版に含めた関連レコードも当時の内容で出す。
 * 編集・削除・複製などのボタン、直接編集の鉛筆、関連タブは出さない。
 */
class RecordVersion_Show_View extends RecordVersion_Base_View
{
    public function process(Vtiger_Request $request): void
    {
        $module = RecordVersion_Config_Model::MODULE_NAME;
        $versionNo = RecordVersion_Util_Helper::toInt($request->get('version'));
        $version = RecordVersion_Version_Model::getByNumber($this->recordId, $versionNo);
        if ($version === null) {
            throw new AppException(vtranslate('LBL_VERSION_NOT_FOUND', $module));
        }
        $snapshot = $version->getSnapshot();
        $record = Vtiger_Record_Model::getInstanceById($this->recordId, $this->sourceModule);
        $labels = new RecordVersion_Label_Model($snapshot);
        $display = new RecordVersion_Display_Model(RecordVersion_Display_Model::getCurrencySymbol($snapshot), $labels);

        $fieldBlocks = $display->buildFieldBlocks($this->sourceModule, RecordVersion_Util_Helper::toArray($snapshot['fields'] ?? []));
        $inventory = isset($snapshot['inventory']) && is_array($snapshot['inventory'])
            ? $display->buildInventory($this->sourceModule, RecordVersion_Util_Helper::toArray($snapshot['inventory']))
            : null;
        // 関連レコードの金額は、親の品目明細の通貨ではなく当時の基本通貨の記号で出す
        $relationDisplay = new RecordVersion_Display_Model(RecordVersion_Display_Model::getBaseCurrencySymbol($snapshot), $labels);
        $relations = [];
        foreach (RecordVersion_Util_Helper::toList($snapshot['relations'] ?? []) as $index => $relation) {
            $built = $relationDisplay->buildRelation($record, RecordVersion_Util_Helper::toArray($relation), RecordVersion_Util_Helper::toInt($index), $versionNo);
            if ($built !== null) {
                $relations[] = $built;
            }
        }

        [$prevNo, $nextNo] = RecordVersion_Version_Model::getAdjacentVersionNos($this->recordId, $versionNo);
        $baseUrl = 'index.php?module=RecordVersion&view=Show&record=' . $this->recordId . '&version=';

        $viewer = $this->getViewer($request);
        $viewer->assign('RV_MODULE', $module);
        $viewer->assign('RECORD_LABEL', RecordVersion_Util_Helper::toStr($snapshot['record_label'] ?? ''));
        $viewer->assign('VERSION_MESSAGE', sprintf(
            RecordVersion_Util_Helper::toStr(vtranslate('LBL_VIEWING_VERSION', $module)),
            $version->getVersionNo(),
            $version->getRevisedAtDisplay(),
            $version->getRevisedByName()
        ));
        $viewer->assign('REASON', $version->getReason());
        $viewer->assign('FIELD_BLOCKS', $fieldBlocks['blocks']);
        $viewer->assign('DELETED_FIELDS', $fieldBlocks['deleted']);
        $viewer->assign('INVENTORY', $inventory);
        $viewer->assign('RELATIONS', $relations);
        $viewer->assign('PREV_URL', $prevNo === null ? '' : $baseUrl . $prevNo);
        // いちばん新しい過去の版の「次の版」は、レコードの詳細画面（現在の版）を開く
        $viewer->assign('NEXT_URL', $nextNo === null ? $this->getRecordDetailUrl() : $baseUrl . $nextNo);
        $viewer->assign('HISTORY_URL', 'index.php?module=RecordVersion&view=History&record=' . $this->recordId);
        $viewer->assign('DETAIL_URL', $this->getRecordDetailUrl());
        $viewer->view('Show.tpl', $module);
    }
}
