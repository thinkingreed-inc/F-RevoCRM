<?php

/**
 * 通知ブロックの小さな画面（ブロックの読み直し・送信ダイアログ・送信履歴）の共通部分
 *
 * 通知ブロックは vtiger_tab に登録しないので、モジュールの権限ではなく、
 * 元のレコードの参照権限だけで見られるかを決める。
 * URL の module は信じず、record から元のレコードのモジュールを決める。
 */
abstract class NotificationBlock_Base_View extends Vtiger_IndexAjax_View
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
        $recordId = NotificationBlock_Util_Helper::toInt($request->get('record'));
        $moduleName = '';
        if ($recordId > 0) {
            $db = PearDatabase::getInstance();
            $rows = NotificationBlock_Util_Helper::fetchRawRows($db->pquery(
                'SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
                [$recordId]
            ));
            $moduleName = empty($rows) ? '' : NotificationBlock_Util_Helper::toStr($rows[0]['setype'] ?? '');
        }
        if ($moduleName === '') {
            throw new AppException(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RECORD_NOT_FOUND', NotificationBlock_Config_Model::MODULE_NAME)));
        }
        if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
            throw new AppException(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_PERMISSION_DENIED')));
        }
        $this->recordId = $recordId;
        $this->sourceModule = $moduleName;
        return true;
    }

    public function process(Vtiger_Request $request): void
    {
        throw new AppException(NotificationBlock_Util_Helper::toStr(vtranslate('LBL_HANDLER_NOT_FOUND')));
    }
}
