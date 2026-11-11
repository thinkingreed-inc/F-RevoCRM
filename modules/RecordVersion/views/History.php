<?php

/**
 * 版履歴の画面（詳細画面のヘッダーの「版履歴」から開く）
 *
 * 最上段に「現在の版」（版番号・最終更新日時・最終更新者）を出し、その下に過去の版を新しい順に出す。
 */
class RecordVersion_History_View extends RecordVersion_Base_View
{
    public function process(Vtiger_Request $request): void
    {
        $record = Vtiger_Record_Model::getInstanceById($this->recordId, $this->sourceModule);
        $versions = [];
        foreach (RecordVersion_Version_Model::getAllForRecord($this->recordId) as $version) {
            $versions[] = [
                'version_no' => $version->getVersionNo(),
                'revised_at' => $version->getRevisedAtDisplay(),
                'revised_by' => $version->getRevisedByName(),
                'reason' => $version->getReason(),
                'url' => 'index.php?module=RecordVersion&view=Show&record=' . $this->recordId . '&version=' . $version->getVersionNo(),
            ];
        }
        $modifiedBy = RecordVersion_Util_Helper::toInt($record->get('modifiedby'));
        $viewer = $this->getViewer($request);
        $viewer->assign('RECORD_LABEL', decode_html(RecordVersion_Util_Helper::toStr($record->getName())));
        $viewer->assign('SOURCE_MODULE', $this->sourceModule);
        $viewer->assign('DETAIL_URL', $this->getRecordDetailUrl());
        $viewer->assign('CURRENT_VERSION_NO', RecordVersion_Config_Model::getCurrentVersionNo($this->recordId));
        $viewer->assign('LAST_MODIFIED_AT', Vtiger_Datetime_UIType::getDisplayDateTimeValue(RecordVersion_Util_Helper::toStr($record->get('modifiedtime'))));
        $viewer->assign('LAST_MODIFIED_BY', $modifiedBy > 0 ? getUserFullName($modifiedBy) : '');
        $viewer->assign('VERSIONS', $versions);
        $viewer->assign('RV_MODULE', RecordVersion_Config_Model::MODULE_NAME);
        $viewer->view('History.tpl', RecordVersion_Config_Model::MODULE_NAME);
    }
}
