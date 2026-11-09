<?php

/**
 * 改版のダイアログ（詳細画面の「改版」から開く。HTML の断片を返す）
 *
 * 現在の版番号、改版したあとの版番号、改版理由の入力欄（必須、1,000 文字まで）を出す。
 * 開けない場合（権限がない・対象外など）も、ダイアログの中にメッセージを出す。
 */
class RecordVersion_ReviseDialog_View extends RecordVersion_Base_View
{
    private string $error = '';

    public function checkPermission(Vtiger_Request $request): bool
    {
        try {
            parent::checkPermission($request);
            if (!Users_Privileges_Model::isPermitted($this->sourceModule, 'EditView', $this->recordId)) {
                throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
            }
        } catch (AppException $e) {
            $this->error = $e->getMessage();
        }
        return true;
    }

    public function preProcess(Vtiger_Request $request, mixed $display = true): void
    {
    }

    public function postProcess(Vtiger_Request $request): void
    {
    }

    public function process(Vtiger_Request $request): void
    {
        $module = RecordVersion_Config_Model::MODULE_NAME;
        $currentVersionNo = $this->error === '' ? max(1, RecordVersion_Config_Model::getCurrentVersionNo($this->recordId)) : 0;
        $viewer = $this->getViewer($request);
        $viewer->assign('TITLE', vtranslate('LBL_REVISE', $module));
        $viewer->assign('ERROR', $this->error);
        $viewer->assign('RECORD_ID', $this->recordId);
        $viewer->assign('CURRENT_VERSION_NO', $currentVersionNo);
        $viewer->assign('NEXT_VERSION_NO', $currentVersionNo + 1);
        $viewer->assign('MAX_REASON_LENGTH', RecordVersion_Config_Model::MAX_REASON_LENGTH);
        $viewer->assign('RV_MODULE', $module);
        $viewer->view('ReviseDialog.tpl', $module);
    }
}
