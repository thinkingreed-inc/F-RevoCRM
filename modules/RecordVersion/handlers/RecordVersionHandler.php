<?php

require_once 'include/events/VTEventHandler.inc';

/**
 * 版管理（#1964）のイベントハンドラ
 *
 * - 保存の前：「版」の値を、改版の処理以外から変えさせない（画面・一括編集・インポート・API・ワークフロー）
 * - 保存のあと：版管理「はい」のモジュールで新しく作ったレコードを版 1 にする（複製・変換を含む）
 * - ごみ箱から完全に削除したあと：そのレコードの過去の版を削除する
 */
class RecordVersionHandler extends VTEventHandler
{
    public const PERMANENT_DELETE_EVENT = 'vtiger.batchevent.afterpermanentdelete';

    /**
     * @param string $eventName
     * @param mixed $data
     */
    public function handleEvent($eventName, $data): void
    {
        switch ($eventName) {
            case 'vtiger.entity.beforesave':
                if ($data instanceof VTEntityData) {
                    $this->keepVersionValue($data);
                }
                break;
            case 'vtiger.entity.aftersave':
                if ($data instanceof VTEntityData) {
                    $this->initializeVersionOnCreate($data);
                }
                break;
            case self::PERMANENT_DELETE_EVENT:
                if (is_array($data)) {
                    RecordVersion_Version_Model::deleteByRecordIds(array_keys($data));
                }
                break;
        }
    }

    /**
     * 送られてきた「版」の値を捨て、DB の値（新規なら最初の値）に戻す
     *
     * 版の値は vtiger_crmentity.version に持ち、通常の保存はこの列を書かない。
     * ただ、送られた値が保存中のデータに残ると、更新履歴に「変わった」と記録されたり、
     * ワークフローの条件に使われたりするため、保存の前に元の値へ戻しておく（エラーにはしない）。
     *
     */
    private function keepVersionValue(VTEntityData $data): void
    {
        $tabId = (int) getTabid($data->getModuleName());
        if ($tabId <= 0 || !RecordVersion_Config_Model::hasVersionField($tabId)) {
            return;
        }
        // isNew() は戻り値の型の注釈が誤っている（@return True）ため、プロパティを直接見る
        if ($data->isNew === true) {
            $value = RecordVersion_Config_Model::isEnabledTab($tabId) ? 1 : 0;
        } else {
            $value = RecordVersion_Config_Model::getCurrentVersionNo(RecordVersion_Util_Helper::toInt($data->getId()));
        }
        $data->set(RecordVersion_Config_Model::FIELD_NAME, (string) $value);
    }

    private function initializeVersionOnCreate(VTEntityData $data): void
    {
        if ($data->isNew !== true) {
            return;
        }
        $tabId = (int) getTabid($data->getModuleName());
        if ($tabId <= 0 || !RecordVersion_Config_Model::isEnabledTab($tabId)) {
            return;
        }
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE ' . RecordVersion_Config_Model::FIELD_TABLE . ' SET ' . RecordVersion_Config_Model::FIELD_COLUMN
            . ' = 1 WHERE crmid = ? AND ' . RecordVersion_Config_Model::FIELD_COLUMN . ' = 0',
            [RecordVersion_Util_Helper::toInt($data->getId())]
        );
    }
}
