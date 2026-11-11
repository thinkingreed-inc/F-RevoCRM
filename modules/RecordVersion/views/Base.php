<?php

/**
 * 版管理の画面の共通部分
 *
 * 版管理は vtiger_tab に登録しないため、ヘッダー・メニューは元のレコードのモジュールとして出す。
 * 見られるかは元のレコードの参照権限だけで決める（版管理用の画面はプロファイルの設定に出さない）。
 * 版管理「いいえ」のモジュールのレコードは、URL から直接開いても出さない（仕様 動作定義 No.29）。
 */
class RecordVersion_Base_View extends Vtiger_Index_View
{
    protected int $recordId = 0;
    protected string $sourceModule = '';

    /**
     * @return array<int, array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [];
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        $this->resolveRecord($request);
        if ($this->recordId <= 0) {
            throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND', RecordVersion_Config_Model::MODULE_NAME));
        }
        if (!Users_Privileges_Model::isPermitted($this->sourceModule, 'DetailView', $this->recordId)) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        if (!RecordVersion_Config_Model::isEnabled($this->sourceModule)) {
            throw new AppException(vtranslate('LBL_NOT_TARGET_MODULE', RecordVersion_Config_Model::MODULE_NAME));
        }
        return true;
    }

    public function preProcess(Vtiger_Request $request, mixed $display = true): void
    {
        // ヘッダー・メニューは元のレコードのモジュールとして出す
        $headerRequest = clone $request;
        $headerRequest->set('module', $this->sourceModule);
        parent::preProcess($headerRequest, $display);
    }

    public function postProcess(Vtiger_Request $request): void
    {
        $headerRequest = clone $request;
        $headerRequest->set('module', $this->sourceModule);
        parent::postProcess($headerRequest);
    }

    /**
     * @return array<mixed>
     */
    public function getHeaderScripts(Vtiger_Request $request): array
    {
        $scripts = parent::getHeaderScripts($request);
        return array_merge($scripts, $this->checkAndConvertJsScripts(['modules.RecordVersion.resources.RecordVersion']));
    }

    public function process(Vtiger_Request $request): void
    {
        throw new AppException(vtranslate('LBL_HANDLER_NOT_FOUND'));
    }

    /**
     * URL の record から、削除されていないレコードとそのモジュールを決める（URL の module は信じない）
     */
    protected function resolveRecord(Vtiger_Request $request): void
    {
        $recordId = RecordVersion_Util_Helper::toInt($request->get('record'));
        if ($recordId <= 0) {
            return;
        }
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
            [$recordId]
        ));
        if (empty($rows)) {
            return;
        }
        $this->recordId = $recordId;
        $this->sourceModule = RecordVersion_Util_Helper::toStr($rows[0]['setype'] ?? '');
    }

    /**
     * 元のレコードの詳細画面の URL
     */
    protected function getRecordDetailUrl(): string
    {
        return 'index.php?module=' . rawurlencode($this->sourceModule) . '&view=Detail&record=' . $this->recordId;
    }
}
